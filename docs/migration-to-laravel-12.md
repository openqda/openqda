# Migration

## Migration Guide: Laravel 11 to Laravel 12

This section outlines the steps required to migrate OpenQDA from **Laravel 11** to **Laravel 12**.

> **Note:** Always work on a feature branch and test in a staging environment before touching production. Do not execute these steps on a running production system without a maintenance window and rollback plan.

### Table of Contents

1. [Prerequisites and Overview](#prerequisites-and-overview)
2. [Updating Composer Dependencies](#updating-composer-dependencies)
3. [Updating npm and Frontend Dependencies](#updating-npm-and-frontend-dependencies)
4. [Breaking Changes and Code Migration](#breaking-changes-and-code-migration)
5. [Database Migrations](#database-migrations)
6. [Updating Docker Files](#updating-docker-files)
7. [Deployment on Ubuntu Server](#deployment-on-ubuntu-server)
8. [Post-Migration Checklist](#post-migration-checklist)

### Prerequisites and Overview

Laravel 12 brings:

- Minimum **PHP 8.2** (PHP 8.3+ recommended).
- Minimum **Composer 2.2+**.
- Refreshed application skeleton with updated starter kits.
- Continuation of the Laravel 11 application bootstrap approach (no `Http/Kernel.php`).
- Updated third-party package minimums: Symfony 7, Carbon 3.

Full changelog: <https://laravel.com/docs/changelog>

#### Recommended approach

1. Create a Git branch: `git checkout -b feat/laravel-12-upgrade`.
2. Work through the sections below in order.
3. Run the test suite after each section: `./vendor/bin/sail artisan test`.
4. Deploy to staging and smoke-test before merging to `main`.

### Updating Composer Dependencies

#### PHP version requirement

Laravel 12 requires **PHP >= 8.2**. OpenQDA already targets PHP 8.2+. Verify the constraint in `web/composer.json`:

```json
"require": {
    "php": "^8.2"
}
```

#### Core framework

Update the Laravel framework constraint in `web/composer.json`:

```json
"laravel/framework": "^12.0",
```

#### First-party Laravel packages

| Package | Action |
|---|---|
| `laravel/sanctum` | `^4.0` — no change needed (v4 supports L12) |
| `laravel/tinker` | Update to `^2.10` |
| `laravel/pint` | `^1.0` — no change needed |
| `laravel/sail` | Update to `^1.36` |

> Check the exact latest minor versions on [Packagist](https://packagist.org) before pinning.

#### Third-party packages

Laravel 12 raises the Symfony component floor to **7.x** and Carbon to **3.x**:

```bash
cd web
composer outdated
```

Key packages to review:

| Package | Action |
|---|---|
| `nunomaduro/collision` | Upgrade to `^8.0` |
| `spatie/*` packages | Check Spatie's changelog; most already support L12 |
| Any `symfony/*: ^6` constraint | Must update to `^7` |

Run the full dependency update:

```bash
cd web
composer update --with-all-dependencies
```

Resolve any remaining conflicts before proceeding.

### Updating npm and Frontend Dependencies

Laravel 12 does not change the frontend toolchain, but updating to current versions is recommended:

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


