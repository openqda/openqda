from typing import Annotated
from fastapi import FastAPI, File, UploadFile, Form, HTTPException, status, \
    Request
from pydantic import BaseModel
from fastapi.responses import FileResponse
from aTrain_core import transcribe, check_inputs, outputs, globals
from datetime import datetime
import shutil
import os

app = FastAPI()


class FileStatus(BaseModel):
    file_id: str
    status: str
    file_name: str | None = None


# fastapi handles blocking code for us
# if we define the function the usual way (not using def async)
@app.post('/upload')
def run(uploaded: UploadFile) -> FileStatus:
    # step 1: check mime
    content_type = uploaded.content_type
    if not content_type.startswith("audio/"):
        raise HTTPException(
            status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
            detail=f"Unsupported media type {content_type}")

    # step 2: move to dir
    file_location = f"/tmp/{uploaded.filename}"
    with open(file_location, "wb+") as file_object:
        file_object.write(uploaded.file.read())

    # step 3: run transcription
    timestamp = datetime.now().strftime(globals.TIMESTAMP_FORMAT)
    model = 'tiny'
    language = 'auto-detect'
    device = 'CPU'
    speaker_detection = True
    num_speakers = 'auto-detect'
    compute_type = 'int8'
    file_id = outputs.create_file_id(file_location, timestamp)
    check_inputs.check_inputs_transcribe(file_location, model, language, device)
    transcribe.transcribe(file_location, file_id, model, language,
                          speaker_detection, num_speakers, device,
                          compute_type, timestamp)
    return FileStatus(
        file_id=file_id,
        file_name=uploaded.filename,
        status="uploaded"
    )


@app.get('/download/{file_id}')
def run(file_id: str) -> FileResponse:
    files_dir = get_files_dir(file_id)
    files_path = os.path.join(files_dir, 'transcription_maxqda.txt')
    return FileResponse(files_path)


@app.delete('/transcription/{file_id}')
def run(file_id) -> FileStatus:
    files_dir = get_files_dir(file_id)
    shutil.rmtree(files_dir)
    return FileStatus(file_id=file_id, status="deleted")


def get_files_dir(file_id):
    files_dir = os.path.join(globals.TRANSCRIPT_DIR, file_id)
    print('get', files_dir)
    if not os.path.exists(files_dir):
        print(files_dir, 'does not exist')
        raise HTTPException(status_code=404, detail=f"File {file_id} not found")
    return files_dir
