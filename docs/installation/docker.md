# Install OpenQDA using Docker

This guide explains how to install OpenQDA on your own infrastructure using Docker and Docker Compose.
We aim to provide guidance for all major operating systems (Windows, MacOS, Linux-based).
Currently, this guide is tested using the following environments:

|OS|Version| Notes |
|---|------|-------|
|Ubuntu|22.04. LTS| -     |


## 1. Install Docker

First of all, make sure you have Docker installed and operable on your system.

> Warning: **Do not install Docker Desktop** but the original Docker CLI (unless you are on macOS, then you can install Docker Desktop which will also install the CLI).

A comprehensive installation guide can be found on the
[official Docker installation guide](https://docs.docker.com/engine/install/) or alternatively on the
[Digital Ocean blog](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-20-04).

Make sure, Docker is running without issues, then move to the next step.

## 2. Install Laravel Sail

Laravel Sail provides a Docker-compose-based toolset  to manage all your development needs.
However, in order to install and use Sail, you need composer, which needs php, which you all need to install, too.

Fortunately there is a way to install php and composer into a Docker container and use it
to locally install Sail and the 
[composer dependencies](https://laravel.com/docs/10.x/sail#installing-composer-dependencies-for-existing-projects).

> First, make sure to be located within the `/web` folder!

```shell
cd web # if not already in the web folder
```

There you have to run the following multiline command:

```shell
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
```

Finally, you can use `sail` (located under `openqda/web/vendor/bin/sail`)
to perform container-based operations.

### UnderstandING Sail

Sail uses docker-compose with optimized presets.
Some commands are therefore identical to docker-compose
but Sail also provides helpful commands that save you trouble.

Use `./vendor/bin/sail --help` to see a list of all available commands
and their descriptions.

Read more on Sail at the Laravel docs:
https://laravel.com/docs/11.x/sail

## 3. Install OpenQDA

> First of all, make sure to be located within the `/web` folder!

### Build the containers for the first time

Before your very first run, you need to build the
containers. This may take some time, but is usually only
required once in a while unless you change PHP version, Laravel version etc.
(If you get an error regarding "invalid hostPort: your reverb port", edit the .env file and set REVERB_HOST=0.0.0.0 and REVERB_PORT=8080. Also you may comment out in the docker-compose.yml file all of the plugin.transform.atrain - services configurations)

```shell
./vendor/bin/sail build --no-cache
```

### Run the backend applications

The commands to run the backend are the same as with Docker Compose but
using the `sail` binaries:

```shell
./vendor/bin/sail up # starts all backend services
```

If you wish to stop the applications, you need to enter `ctrl + c` (Windows, Linux) or `cmd + c` (MacOS),
or, you run sail in detached mode (using the `-d` option) the you need to enter

```shell
./vendor/bin/sail down
```

At this point, you should get familiar with how Sail works and the commands
it provides:

- [Laravel Sail documentation](https://laravel.com/docs/11.x/sail)
- [Docker compose documentation](https://docs.docker.com/reference/cli/docker/compose/)


### Populate Database

On your **first run** you need to populate the database tables.

```shell
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan migrate
```

Additionally, you need some development data (such as initial Users) seeded
into the database:

```shell
./vendor/bin/sail artisan db:seed
```

The default users + their login credentials are located at `web/database/seeders/UserSeeder.php`.

### Connect Filesystem

On your **first run** you also need to make images locally available:

```shell
./vendor/bin/sail artisan storage:link
```


### Run the Collaboration Service

Collaborative features are enabled through a WebSocket connected, which in turn requires a Websocket server
(Laravel Reverb), which you can start in a new terminal via:

```shell
docker exec -it web-laravel.test-1 /var/www/html/start_debug_services.sh
```

This services needs to start everytime you start your backend apps, because it automatically stops,
once you stop the backend containers!

Note, OpenQDA is expected to run without errors, in case you didn't start it.

### Run the client application

OpenQDA comes with a Vue 3 based HTML/CSS/JS client application that interacts with the backend.
While it runs entirely in the browser, the development tools require NodeJs and NPM, which are fortuntely
also bundled with Laravel Sail.

So, in order to run the client you can either use the builtin binaries
or your own local NodeJs/npm installation.

**Builtin NodeJs environment**

```shell
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

**Your local NodeJs environment**

Tested with NodeJs 20 LTS but you should generally stick with the current NodeJs LTS for best
compatibility:

```shell
npm install
npm run dev
```

### View the local files, logs and E-Mails

Some actions in development mode require you to view the logs and other app data.

The docker containers are connected to a local store, located at `/web/storage`.

- Use the `/logs` folder there, to view the latest live-logging updates, including
E-Mails sent to the dev-users (password-reset etc.).

- Uploaded profile images are located at `/web/storage/app/public/profile-photos`.

- Project files are "uploaded" to the storage under `/web/storage/app/projects/<project-id>`.
