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
