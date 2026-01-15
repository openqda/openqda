import logging
import subprocess
import os
from tempfile import TemporaryDirectory
from typing import Annotated
from fastapi import FastAPI, File, UploadFile, Form, HTTPException, status, Request, Response
from pydantic import BaseModel
from fastapi.responses import FileResponse
from logging.handlers import TimedRotatingFileHandler
from dotenv import dotenv_values
from uuid import uuid4
import shutil
from pathlib import Path

config = dotenv_values('.env')

# Set up the logging
log_file = config['LOG_PATH'] if 'LOG_PATH' in config else 'logfile.log'
logger = logging.getLogger(config['LOG_NAME'] if 'LOG_NAME' in config else 'convert-to-html')
logger.setLevel(logging.INFO)  # Capture all messages of level INFO and above

# Create a handler for rotating log files every 7 days
handler = TimedRotatingFileHandler(log_file, when="D", interval=7, backupCount=1)
formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
handler.setFormatter(formatter)
logger.addHandler(handler)


app = FastAPI()

# parse allowed ips and
# remove whitespaces
allowed_ips = config['ALLOWED_IPS'].split(',') if 'ALLOWED_IPS' in config else []
allowed_ips = [ip.strip() for ip in allowed_ips if ip.strip() != '']

@app.post('/convert')
async def convert(
    request: Request,
    file: Annotated[UploadFile, File(...)],
    password: Annotated[str, Form(...)],
    title: Annotated[str, Form(...)],
):
    # 1. IP address filtering if configured
    if len(allowed_ips) >= 1 and request.client.host not in allowed_ips:
        logger.info(f"Access denied - IP not allowed: {request.client.host}, allowed are {str(allowed_ips)}")
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied - wrong ip"
        )

    # 2. check password if configured
    expected_password = config['SECRET_PASSWORD'] if 'SECRET_PASSWORD' in config else None
    if (password != expected_password) and expected_password is not None:
        logger.info(
            f"Invalid password attempt from IP {request.client.host}"
        )
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied - invalid password"
        )

    # 3. create a hex hash of the file name
    logger.info(f"convert file: {file.filename} from IP: {request.client.host}")
    filename = file.filename
    extension = os.path.splitext(filename)[1]
    file_id = uuid4().hex

    # Create a temporary directory to store the uploaded file and the output HTML
    with TemporaryDirectory() as temp_dir:
        # Save the uploaded file to the temporary directory
        secure_name = file_id + extension
        file_path = Path(temp_dir)
        input_path = file_path / f"input{extension}"
        output_path = file_path / "output.html"

        logger.info(f"Store input file at path: {input_path}")
        logger.info(f"Store outpt file at path: {output_path}")

        # Save uploaded file
        with input_path.open("wb") as f:
            shutil.copyfileobj(file.file, f)
        file.file.close()

        # Run the script to convert the file
        # Sanitize the user-provided title before passing it to pandoc
        safe_title = (title or "").strip()
        # Limit length to avoid excessively large arguments
        max_title_length = 200
        if len(safe_title) > max_title_length:
            safe_title = safe_title[:max_title_length]
        # Remove newline and carriage return characters to keep the argument well-formed
        safe_title = safe_title.replace("\n", " ").replace("\r", " ")

        args = [
            'pandoc',
            str(input_path),
            "-t", "html5",
            "-s",
            "-o", str(output_path),
            "-M", f"title={safe_title}",
        ]
        try:
            result = subprocess.run(args, capture_output=True, text=True, check=True)
        except subprocess.CalledProcessError as e:
            # pandoc returned non-zero
            raise HTTPException(status_code=500, detail=f"conversion error: {e.stderr or e.stdout}")
        except subprocess.TimeoutExpired:
            raise HTTPException(status_code=504, detail="Conversion timed out")

        # integrity check
        if not output_path.exists():
            raise HTTPException(status_code=500, detail="Conversion failed: output not created")

        html_bytes = output_path.read_bytes()
        # Return the generated HTML file
        #return FileResponse(path=str(output_path), media_type="text/html", filename=f"${filename}.html")
    return Response(content=html_bytes, media_type="text/html; charset=utf-8", headers={"Content-Disposition": 'attachment; filename="output.html"'})
