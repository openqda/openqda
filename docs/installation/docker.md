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


```bash
cd web
npm outdated
npm update
```

Recommended minimum versions:

```json
"devDependencies": {
    "laravel-vite-plugin": "^1.0",
    "vite": "^6.0"
}
```

Verify the frontend builds after updating:

```bash
./vendor/bin/sail npm run build
```

### Breaking Changes and Code Migration

#### Application bootstrap (`bootstrap/app.php`)

Laravel 11 already moved routing and middleware to `bootstrap/app.php`. Laravel 12 continues this approach — no changes are needed if the app was already on the L11 skeleton.

If `app/Http/Kernel.php` still exists from a pre-L11 skeleton, remove it:

```bash
ls web/app/Http/Kernel.php
# If found, remove it:
rm web/app/Http/Kernel.php
```

#### `dispatch_sync()` helper removed

The global `dispatch_sync()` helper was removed in Laravel 12. Replace all usages:

```php
// Before
dispatch_sync(new MyJob());

// After
MyJob::dispatchSync();
// or:
app(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchSync(new MyJob());
```

Search for usages:

```bash
grep -rn "dispatch_sync" web/app web/tests
```

#### Carbon 3 (`nesbot/carbon`)

Carbon 3 has stricter type handling:

- `diffInSeconds()`, `diffInMinutes()`, etc. now return `float` instead of `int`. Cast explicitly if integer precision is required.
- Check any code that mutates a `Carbon` instance in-place.

```bash
grep -rn "Carbon" web/app | grep -v vendor
```

#### Symfony 7 Http Foundation

If code directly instantiates `Symfony\Component\HttpFoundation\Request` or manipulates headers/bags, review the [Symfony 7 upgrade guide](https://github.com/symfony/symfony/blob/7.0/UPGRADE-7.0.md).

#### `password` validation rule

If `Password::defaults()` is customised in `AppServiceProvider`, verify it still compiles and behaves correctly under L12:

```php
Password::defaults(function () {
    return Password::min(12)->mixedCase()->numbers();
});
```

### Database Migrations

Laravel 12 does not introduce new framework-level database tables. After deploying the upgraded application, run pending migrations:

```bash
php artisan migrate --force
```

Verify factories and seeders still work in the staging environment:

```bash
php artisan db:seed --class=DatabaseSeeder
```

### Updating Docker Files

#### PHP image

Update the base PHP version in `docker-compose.yml` from `8.2` to **`8.3`**:

```yaml
# docker-compose.yml
services:
  laravel.test:
    build:
      context: ./vendor/laravel/sail/runtimes/8.3   # was 8.2
      dockerfile: Dockerfile
```

Rebuild the Sail image:

```bash
./vendor/bin/sail build --no-cache
```

#### Composer stage (multi-stage builds)

If using a multi-stage `Dockerfile`:

```dockerfile
FROM composer:2.8 AS composer
```

#### Node.js stage (multi-stage builds)

Update the Node.js image to **Node 20 LTS** or **Node 22 LTS**:

```dockerfile
FROM node:20-alpine AS node-build
```

#### Environment variables

No new required `.env` variables are introduced in Laravel 12. Confirm the following L11 variables are present in `.env.example`:

```dotenv
APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database
```

### Deployment on Ubuntu Server

This sub-section describes upgrading a **running Laravel 11 application** on Ubuntu 22.04 or 24.04.

#### PHP upgrade

```bash
# Add Ondrej Sury's PPA
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Install PHP 8.3 and required extensions
sudo apt install -y \
    php8.3 php8.3-cli php8.3-fpm \
    php8.3-mbstring php8.3-xml php8.3-bcmath \
    php8.3-curl php8.3-zip php8.3-pgsql php8.3-mysql \
    php8.3-redis php8.3-gd php8.3-intl

# Verify
php8.3 --version

# Switch CLI default
sudo update-alternatives --set php /usr/bin/php8.3
```

#### Apache 2 + PHP-FPM

```bash
# Enable required modules
sudo a2enmod proxy_fcgi setenvif

# Swap FPM configuration
sudo a2disconf php8.2-fpm
sudo a2enconf php8.3-fpm

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl reload apache2
```

Update the virtual host to use the new FPM socket:

```apache
# /etc/apache2/sites-available/openqda.conf
<FilesMatch "\.php$">
    SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
</FilesMatch>
```

#### Composer upgrade

```bash
composer self-update
composer --version   # should be 2.2+
```

#### Deployment steps

```bash
# 1. Maintenance mode
php artisan down --secret="your-bypass-token"

# 2. Pull latest code
git pull origin main

# 3. PHP dependencies
composer install --no-dev --optimize-autoloader

# 4. Frontend assets
npm ci && npm run build

# 5. Database migrations
php artisan migrate --force

# 6. Clear and re-cache
php artisan optimize:clear
php artisan optimize

# 7. Restart queue workers
php artisan queue:restart
sudo systemctl restart openqda-worker.service

# 8. Go live
php artisan up
```

#### Supervisor / Queue workers

Update the Supervisor config to reference `php8.3`:

```ini
[program:openqda-worker]
command=/usr/bin/php8.3 /var/www/openqda/web/artisan queue:work --sleep=3 --tries=3
```

Restart workers:

```bash
sudo supervisorctl restart openqda-worker:*
```

#### Scheduler (cron)

```
* * * * * www-data /usr/bin/php8.3 /var/www/openqda/web/artisan schedule:run >> /dev/null 2>&1
```

### Post-Migration Checklist

- [ ] `composer show laravel/framework` returns `12.x.x`.
- [ ] `php artisan --version` returns `Laravel Framework 12.x.x`.
- [ ] All tests pass: `./vendor/bin/sail artisan test`.
- [ ] Frontend builds without errors: `./vendor/bin/sail npm run build`.
- [ ] Frontend lint passes: `./vendor/bin/sail npm run lint:write`.
- [ ] Backend lint passes: `./vendor/bin/sail pint`.
- [ ] No deprecation warnings in `storage/logs/laravel.log`.
- [ ] Smoke-test all major user-facing features (login, project creation, coding, export).
- [ ] Verify API endpoints return expected responses.
- [ ] Verify queue workers are processing jobs.
- [ ] Confirm scheduled tasks run: `php artisan schedule:test`.
- [ ] Production server runs PHP 8.3.
- [ ] Docker images rebuild successfully after PHP version bump.
- [ ] `.env.example` is up-to-date with any new environment variables.

### References

- [Laravel 12 Documentation](https://laravel.com/docs/12.x/)
- [Laravel 11 Documentation](https://laravel.com/docs/11.x/)
- [Laravel 12 Changelog](https://laravel.com/docs/changelog)
- [Laravel Upgrade Guide (11 to 12)](https://laravel.com/docs/12.x/upgrade)
- [Symfony 7 Upgrade Guide](https://github.com/symfony/symfony/blob/7.0/UPGRADE-7.0.md)
- [Carbon 3 Migration Guide](https://carbon.nesbot.com/docs/#api-carbon-3)
- [Laravel Sail Documentation](https://laravel.com/docs/12.x/sail)

## Troubleshooting


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

#### Disable Services

You may comment out in the `docker-compose.yml` file some services, such as the
`plugin.transform.atrain` if you intend to not build it.
