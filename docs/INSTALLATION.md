# OpenQDA installation

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Initial setup](#initial-setup)
- [Install Dependencies](#install-dependencies)
- [Database and Application Setup](#database-and-application-setup)
- [Real-Time Configuration](#real-time-configuration)
- [Install using Laravel Sail (Docker)](#install-using-laravel-sail-docker)
  - [First install](#first-install)
  - [Start local development setup](#start-local-development-setup)
  - [Stop local setup](#stop-local-setup)
  - [Run client](#run-client)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->




## Initial setup
0. Install a web server using our [Tech Stack](TECH-STACK.md) or use the sail installation (see below)
1. Clone the repository.
2. Create a `.env` file by copying the contents from `.env.example`.
3. Configure the database by editing the DB section in `.env` to reflect your database connection details.
For local email logging, consider using [MailTrap](https://mailtrap.io/) (free with a usage limit) or [Laravel Herd](https://herd.laravel.com/windows) (paid service).

## Install Dependencies
Execute the following commands in the `web` directory:

- `php artisan key:generate` - Generates a unique `APP_KEY`. [Learn more](https://medium.com/@kirinyetbrian/unlocking-the-importance-of-laravels-app-key-protecting-your-application-s-security-and-1302b98e4e72) about it.
- `composer install` - Installs PHP dependencies.
- `npm install` - Installs Node.js packages.
- `npm run dev` - Runs Vite locally for development.
- `npm run build` - Compiles assets for production.

## Database and Application Setup
1. Run `php artisan migrate` to create database tables.
2. Customize and run the seeder ( `database/seeders`) to populate the database with initial data via `php artisan db:seed`.
3. create a HMAC key, [there are several ways to do it](https://unix.stackexchange.com/questions/610039/how-to-do-hmacsha256-using-openssl-from-terminal). This is used by ALTCHA.
4. set the `ALTCHA_HMAC_KEY`.

`RTFENDPOINT` and `RTFPASSWORD` are necessary only if you set your `.env` file to "Production", otherwise it will use the internal python script called `convert_rtf_to_html_locally.py`.

Now you should be able to run OpenQDA, but you still need further steps to enable the websocket server and enable the collaboration tools.

## Real-Time Configuration
1. Configure [Laravel Reverb](https://reverb.laravel.com/) for real-time capabilities by setting the following in your `.env`:
```
REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```
2. Start the WebSocket server with `php artisan reverb:start`.
3. Use `php artisan queue:work --queue=conversion,default`.
OpenQDA uses [Laravel Queues](https://laravel.com/docs/11.x/queues) and [Laravel Echo](https://github.com/laravel/echo) to process the presence of users and convert files on production.
For the online presence we use the "default" queue, for converting rtf files we use the "conversion" queue.

On Production, please make sure to install SSL certificates correctly on your web engine (apache, nginx, etc.) as well as our `.env` production file with the absolute path of your certificate, private key and CAfile:

```
LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT=
LARAVEL_WEBSOCKETS_SSL_LOCAL_PK=
LARAVEL_WEBSOCKETS_SSL_CAFILE=
```

## Install using Laravel Sail (Docker)

In order to use Laravel without any installation you need to bootstrap-install Sail.
To do that, you need composer, which needs php. Yikes.
Fortunately there is a way to install composer only in a Docker container and use it
to locally install Sail.

### First install

You can [install composer dependencies](https://laravel.com/docs/10.x/sail#installing-composer-dependencies-for-existing-projects)
with the following docker command:

```shell
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
```

From here you can use `sail` to perform container-based operations (see next steps).

### Start local development setup

```shell
$ ./vendor/bin/sail up -d # start laravel backend
$ ./vendor/bin/sail npm run dev # start vue.js frontend
```

If you have issues with the database connection, for example after pulling new changes from remote, please run:

```shell
$ ./vendor/bin/sail artisan config:cache
$ ./vendor/bin/sail artisan config:clear
$ ./vendor/bin/sail artisan migrate
```

### Stop local setup

```shell
$ ./vendor/bin/sail down
```

### Run client

After you have successfully started the server, you will have to run the client as well.
We use Vue3 + Vite + Tailwind as our main client-side stack.
You need at least [NodeJS and NPM](https://nodejs.org/en) to build the client-side app.

Once they are installed

### Retrieve logs from docker container

You can copy the logs to a local file via:

```shell
$ docker cp web-laravel.test-1:/var/www/html/storage/logs/laravel-2024-06-26.log laravel.log
```

Note that you need to adjust the date to the current one.

### Run the Websocket

OpenQDA additionally uses websockets for messaging in collaborative setups.
To start the Websocket in development mode and as a second process within your 
container you can execute the following command:

```shell
$ docker exec -it web-laravel.test-1 /var/www/html/start_debug_services.sh
```
