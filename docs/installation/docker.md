# Install OpenQDA using Docker



## Install Docker

First, make sure you have Docker installed on your system.
Please do not install Docker Desktop but the original Docker CLI.

A comprehensive installation guide can be found on the 
[official Docker installation guide9](https://docs.docker.com/engine/install/) or alternatively on the
[Digital Ocean blog](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-20-04).

## Install Laravel Sail

Laravel Sail provides a docker-compose-based system
to manage your development using Docker.

To install and use Sail, you need composer, which needs php. Yikes!

Fortunately there is a way to install composer only in a Docker container and use it
to locally install Sail and the [composer dependencies](https://laravel.com/docs/10.x/sail#installing-composer-dependencies-for-existing-projects)
with the following command:

```shell
$ cd web # if not already in the web folder
$ docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
```

From here you can use `sail` (located under `openqda/web/vendor/bin/sail`)
to perform container-based operations (see next steps).

## Understand Sail

Sail uses docker-compose with optimized presets.
Some commands are therefore identical to docker-compose
but Sail also provides helpful commands that save you trouble.

Use `./vendor/bin/sail --help` to see a list of all available commands 
and their descriptions.

Read more on Sail at the Laravel docs:
https://laravel.com/docs/11.x/sail

## Build the containers

Before your very first run, you need to build the
containers. This may take some time, but is usually only
required once in a while unless you change PHP version, Laravel version etc.

```shell
$ ./vendir/bin/sail build --no-cache
```

## Run the app

```shell
$ ./vendor/bin/sail up -d # start laravel backend
$ ./vendor/bin/sail npm run dev # start vue.js frontend
```


## Populate Database

On your first run you need to populate the database tables:

```shell
$ ./vendor/bin/sail artisan config:cache
$ ./vendor/bin/sail artisan config:clear
$ ./vendor/bin/sail artisan migrate
```

Additionally, you may want to have a few development data (such as initial Users) seeded
into the database:

```shell
$ ./vendor/bin/sail artisan db:seed
```

The users + credentials are located at [`web/database/seeders/UserSeeder.php`](../../web/database/seeders/UserSeeder.php).

### Stop local setup

```shell
$ ./vendor/bin/sail down
```

## Start the Collaboration Service

Collaborative features are enabled through a WebSocket, which
you can start in a new terminal via

```shell
$ docker exec -it web-laravel.test-1 /var/www/html/web/start_debug_services.sh
```
