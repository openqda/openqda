from contextlib import asynccontextmanager
from typing import Annotated
from fastapi import FastAPI, File, UploadFile, Form, HTTPException, status, Request
from pydantic import BaseModel
from fastapi.responses import FileResponse
from aTrain_core import transcribe, check_inputs, outputs, globals, load_resources
from datetime import datetime
import shutil
import os
import uuid
import base64
import asyncio
from process import post_process
from audio import get_audio_length

app = FastAPI()
model = 'medium'
language = 'auto-detect'
device = 'CPU'
speaker_detection = True
num_speakers = 'auto-detect'
compute_type = 'int8'


class ServiceStatus(BaseModel):
    worker_id: int
    status: str


class FileStatus(BaseModel):
    file_id: str
    status: str
    length: str | None = None
    file_name: str | None = None


@app.get('/status/service')
def run():
    return ServiceStatus(worker_id=os.getpid(), status='running')

@app.get('/status/{file_id}')
def run(file_id: str):
    status = FileStatus(file_id=file_id, status="notFound")
    upload_dir = get_upload_dir(file_id)
    if not os.path.exists(upload_dir):
        return status
    status.status = "uploaded"
    files_dir = get_files_dir(file_id)
    if not os.path.exists(files_dir):
        return status
    status.status = "processed"
    return status


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



    # step 2: move to upload dir
    file_hash = uuid.uuid4().hex
    file_extension = os.path.splitext(uploaded.filename)[1]
    file_extension_lower = str(file_extension).lower()
    file_name = f"{file_hash}{file_extension}"
    print('prepare uploaded file', file_name)
    file_location = get_upload_dir(file_name)

    file_id = outputs.create_file_id(file_location, file_name)

    print('save uploaded file', file_location)
    with open(file_location, "wb+") as file_object:
        file_object.write(uploaded.file.read())

    print('upload: try to get audio length', uploaded.filename)
    try:
        status.length = get_audio_length(file_location)
    except Exception as e:
        print(e)
        status.length = 300 # seconds

    print('uploaded complete', file_id)
    # 3. return file status
    return FileStatus(
        file_id=file_id,
        status="uploaded"
    )

@app.post('/process/{file_id}')
def process(file_id: str) -> FileResponse:
    print('start processing', file_id)
    file_location = get_upload_dir(file_id)
    timestamp = datetime.now().strftime(globals.TIMESTAMP_FORMAT)
    print('run transcription for file at ', file_location)
    check_inputs.check_inputs_transcribe(file_location, model, language, device)
    transcribe.transcribe(file_location, file_id, model, language,
                          speaker_detection, num_speakers, device,
                          compute_type, timestamp)

    # post processing
    files_dir = get_files_dir(file_id)
    if not os.path.exists(files_dir):
        raise HTTPException(status_code=404, detail=f"File {file_id} not found")

    print('transcription complete, start post processing ', file_id)
    files_path = os.path.join(files_dir, 'transcription_maxqda.txt')

    try:
        post_process(files_path)
    except Exception as e:
        print(e)

    print('processing complete', file_id)
    return FileStatus(
        file_id=file_id,
        status="processed"
    )

@app.get('/result/{file_id}')
def run(file_id: str) -> FileResponse:
    print('get result for ', file_id)
    files_dir = get_files_dir(file_id)
    if not os.path.exists(files_dir):
        raise HTTPException(status_code=404, detail=f"File {file_id} not found")
    files_path = os.path.join(files_dir, 'transcription_maxqda.txt')
    return FileResponse(files_path)


@app.delete('/{file_id}')
def run(file_id) -> FileStatus:
    print('delete all for ', file_id)
    files_dir = get_files_dir(file_id)
    upload_dir = get_upload_dir(file_id)
    try:
        shutil.rmtree(files_dir)
    except FileNotFoundError as e:
        print(e)
    try:
        os.remove(upload_dir)
    except FileNotFoundError as e:
        print(e)
    return FileStatus(file_id=file_id, status="deleted")

def get_upload_dir(file_id, check: bool = False):
    split = file_id.split()
    return f"/uploads/{split[0]}"

def get_files_dir(file_id: str):
    return os.path.join(globals.TRANSCRIPT_DIR, file_id)
