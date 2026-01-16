# Use official Python 3.12 image
FROM python:3.12-slim

# Avoid Python writing .pyc files and enable buffered output
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1

# Install system deps required for building wheels (add more if your packages need them)
RUN apt-get update && apt-get install -y --no-install-recommends \
        locales \
        build-essential \
        curl \
        pandoc \
    && rm -rf /var/lib/apt/lists/*

# set locales and encoding
RUN sed -i '/en_US.UTF-8/s/^# //g' /etc/locale.gen && \
    locale-gen
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

# Install pipenv
RUN pip install --upgrade pip setuptools wheel \
    && pip install pipenv

# Create app dir
WORKDIR /app

# Copy Pipfile and Pipfile.lock first to leverage Docker cache
# 2>/dev/null || true
COPY Pipfile* /app/

# Use pipenv to install dependencies into the system environment (no virtualenv)
# This ensures the installed packages are on PATH inside the container
# If you prefer an actual venv, remove --system.
RUN if [ -f Pipfile ]; then \
      pipenv install --deploy --system --ignore-pipfile || pipenv install --system; \
    fi

# Copy application code
COPY . /app

# Example default command — replace with your app's start command
EXPOSE 9666
CMD ["gunicorn", "-w", "4", "-b", "0.0.0.0:9666", "convert:app", "--worker-class", "uvicorn.workers.UvicornWorker"]
