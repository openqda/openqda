# Transcription Transform Service with aTrain

## Install and run

There are multiple ways to install an run the service.

### Using Pipenv

For this you need Python >= 3.10 and [pipenv](https://pipenv.pypa.io/en/latest/) installed.
You can then `cd` into this directory and initiate a new venv:

```shell
$ pipenv shell
```

From there you can either install packages via

```shell
$ pipenv install
```

Or after install run the app via

```shell
$ pipenv run dev
```

### Using Docker

You can either use the [docker compose file](../../../web/docker-compose.yml)
to build and run the image

```shell
# being in the web directory
$ docker compose up plugin.transform.atrain --build
```

Or manually build the service via

```shell
# being in this directory (atrain)
$ docker build -t plugin-transform-aTrain ./
$ docker run --name plugin-transform-aTrain-1 -p 4040:80 plugin-transform-aTrain
```

> Important: use the above names and ports if you manually
> build the image. Otherwise, you'll have to configure the
> URL for this service in the .env in order to use the service.

## Development


### Stack
We use Python + FastAPI to run the service
and Pipenv to manage dependencies etc.
