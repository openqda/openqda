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

```shell
./vendor/bin/sail build --no-cache
```

### Run the backend applications

The commands to run the backend are the same as with Docker Compose but
using the `sail` binaries:

```shell
./vendor/bin/sail up
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

### Generate Application Keys

The command `artisan key:generate` is used in Laravel to generate a secure 
application key, which is essential for encrypting data and ensuring
security within your Laravel project. This key is automatically set in the 
.env file, which is crucial for the application's functionality.
You can generate the key with the artisan command:

```shell
./vendor/bin/sail artisan key:generate
```

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

### Troubleshooting


#### Connection issues with Websocket Server

> General note: If you changed the Websocket configuration then you always have to restart both
> the Websocket server and the OpenQDA application server.

If you get errors like *"invalid hostPort: your reverb port"* or *cannot connect to wss://...*
then open the `.env` file and change the following Reverb settings:

```dotenv
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

Alternatively you may want to issue your own ssl certificate for local development.

Depending on your platform it might be necessary to change in `docker-compose.yml` the entry under ports `"127.0.0.1:{REVERB_PORT:-8080}:8080"`
to `'${REVERB_PORT:-8080}:8080'`

If you still have issues connecting to the Websocket server then you can make use 
of the global debug function in the client.
Open the **browser** console and enter the following command:

```js
debugSocket()
```

It will print a log of the attempted connection steps and additional
connection info.

A successful connection log for local dev and unencrypted transport looks similar to this one:

```
0: "initWebSocket"
1: "get echo"
2: 'echo options are {"broadcaster":"reverb","key":"xxxxx","wsHost":"localhost","wsPort":"8080","wssPort":"8080","forceTLS":false,"encrypted":false,"enabledTransports":["ws"]}'
3: "echo state: connecting"
4: "add connecting listener"
5: "add connected listener"
6: "add unavailable listener"
7: "add failed listener"
8: "add disconnected listener"
9: "add error listener"
10: "complete init"
```

A success connection log for production with encrypted traffic may look like this:

```

```

#### Disable Services

You may comment out in the `docker-compose.yml` file some services, such as the
`plugin.transform.atrain` if you intend to not build it.
