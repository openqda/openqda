# Webapp

## Installation

### Manual

### Sail (Docker)

In order to use Laravel without any installation you need to bootstrap-install Sail.
To do that, you need composer, which needs php. Yikes.
Fortunately there is a way to install composer only in a Docker container and use it
to locally install Sail.

#### First install

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

#### Start local development setup

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

#### Stop local setup

```shell
$ ./vendor/bin/sail down
```

#### Run client

After you have successfully started the server, you will have to run the client as well.
We use Vue3 + Vite + Tailwind as our main client-side stack. 
You need at least [NodeJS and NPM](https://nodejs.org/en) to build the client-side app.

Once they are installed
