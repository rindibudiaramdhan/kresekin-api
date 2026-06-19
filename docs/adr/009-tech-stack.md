# ADR-009: Tech stack

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

| Concern | Choice |
| --- | --- |
| Backend framework | Laravel 13 |
| Language/runtime | PHP 8.3 |
| Database | PostgreSQL for production |
| Test database | SQLite in-memory |
| ORM | Eloquent |
| Validation | Laravel FormRequest |
| Styling/build | Tailwind CSS and Vite for Blade assets |
| Object storage | Laravel filesystem / Flysystem, including S3 support |
| Testing | PHPUnit through `php artisan test` |
| Formatting | Laravel Pint |
| Deployment target | Laravel Cloud with PostgreSQL resource |

## Context

- The repository is already a Laravel application with `composer.json`, migrations, Eloquent models, feature/unit tests, Blade views, Vite, and Laravel Cloud deployment notes.
- Laravel gives a practical default for API routing, middleware, validation, storage, database migrations, and testing.
- PostgreSQL is the production database target, while SQLite keeps automated tests lightweight.

## Consequences

- New backend code should follow Laravel conventions rather than introducing parallel frameworks.
- Database behavior that differs between SQLite and PostgreSQL needs explicit test awareness.
- Deviations such as new queues, frontend frameworks, or external providers should be documented when they affect architecture.
