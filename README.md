# Kresekin API

REST API project built with Laravel 13.

## Stack

- Laravel 13
- PostgreSQL 18
- Laravel Cloud

## Local Environment

The repository is now configured to use PostgreSQL by default.

1. Copy `.env.example` to `.env` if needed.
2. Fill in your PostgreSQL credentials.
3. Run migrations:

```bash
php artisan migrate
```

The important database variables are:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kresekin_api
DB_USERNAME=postgres
DB_PASSWORD=
DB_SCHEMA=public
DB_SSLMODE=require
```

## Laravel Cloud

This app is ready to run on Laravel Cloud with a PostgreSQL resource attached.

According to the official Laravel Cloud docs:
- Attached databases inject `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_DATABASE` into the environment automatically.
- Build and deploy commands are configured from the environment settings.
- Environment variable changes require a new deployment.

Recommended Laravel Cloud environment variables:

```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_PORT=5432
DB_SCHEMA=public
DB_SSLMODE=require
```

If your PostgreSQL resource is already attached in Laravel Cloud, do not commit its credentials into this repository. Let Laravel Cloud provide them at deploy time.

Recommended commands in Laravel Cloud:

- Build command: `composer install --no-interaction --prefer-dist --optimize-autoloader`
- Deploy command: `php artisan migrate --force`

If you also build frontend assets, use a build command such as:

```bash
composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
```

## Current API

- `GET /api/healthcheck`
- `GET /api/vershealthcheck`
- `POST /api/users/register`

## References

- Laravel Cloud docs: https://cloud.laravel.com/docs
- Applications: https://cloud.laravel.com/docs/applications
- Databases: https://cloud.laravel.com/docs/resources/databases
- Deployments: https://cloud.laravel.com/docs/deployments
- Environments: https://cloud.laravel.com/docs/environments
