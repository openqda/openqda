# Transcription Transform Service with aTrain

## Install and run

There are multiple ways to install and run the service.

> Expect ~10GB of being downloaded during installation / build!

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

We use Python + FastAPI to run the service
and Pipenv to manage dependencies etc.

The easiest way to install and run for development is to use
the docker compose file in combination with Laravel Sail.

Once the service runs, it's available on http://localhost:4040

### Laravel `.env` settings

You need to configure `.env`, in order to configure the core to communicate with
the service. See [.env.example](../../../web/.env.example) for our example
dotenv file.

The following lines are to be considered:

```dotenv
SERVICE_TRANSFORM_ATRAIN_UPLOAD="http://plugin.transform.atrain/upload"
SERVICE_TRANSFORM_ATRAIN_DOWNLOAD="http://plugin.transform.atrain/download/"
```

Note the domain host being the name of the service, defined in
the docker compose file as this is the way docker bridged networks
assign them to the services.

> Likely breaking: there will be a future change here as we
> will create a full plugin specification soon.

## Deployment

If you intend to deploy the service to your own infrastructure
then you need to keep in mind this service is currently intended to
run on a private network and not designed to "face the public".

You can use the [Dockerfile](./Dockerfile) on any machine that
has Docker installed.

### Proxy troubleshooting

When installing the service on a private target server using Docker
you will likely encounter issues during build, including "no host" erorrs.

This is likely to be an issue with your proxy settings.
Read more in the Docker documentation: https://docs.docker.com/network/proxy/

Depending on your setup you might either simply [use the cli](https://docs.docker.com/network/proxy/) args
or have to [reconfigure the daemon](https://docs.docker.com/network/proxy/#configure-proxy-settings-per-daemon).
We found that setting both will not work well.

You can test both methods using the following instant build:

```shell
$ docker build \
  --no-cache \
  --progress=plain \
  - <<EOF
FROM alpine
RUN env | grep -i _PROXY
EOF
```

If both not work you can still configure the [docker daemon with systemd](https://docs.docker.com/config/daemon/systemd/):

```shell
$ sudo mkdir -p /etc/systemd/system/docker.service.d
$ sudo nano /etc/systemd/system/docker.service.d/http-proxy.conf
```

Add then

```
[Service]
Environment="HTTP_PROXY=http://proxy.example.com:3128"
```

## License

This service is provided and published under the APGL-3.0 license.
aTrain is published by Armin Haberl, Jürgen Fleiß, Dominik Kowald, Stefan Thalmann under
[their respective licence](https://github.com/JuergenFleiss/atrain_core/blob/main/LICENSE).
