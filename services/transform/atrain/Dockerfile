FROM tiangolo/uvicorn-gunicorn:python3.10

LABEL maintainer="OpenQDA Project <openqda@uni-bremen.de>"

COPY ./requirements.txt /app/requirements.txt

RUN pip install --no-cache-dir --upgrade -r /app/requirements.txt
RUN pip install git+https://github.com/JuergenFleiss/aTrain_core.git@v1.1.0#egg=aTrain_core --extra-index-url https://download.pytorch.org/whl/cu121

COPY ./app /app
