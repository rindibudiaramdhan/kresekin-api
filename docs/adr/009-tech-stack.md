# ADR-009: Tech stack

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

| Concern | Choice |
| --- | --- |
| Backend framework | Laravel 13 or newer major release supported by this codebase |
| Language/runtime | PHP 8.3 minimum; follow the supported PHP range for the active Laravel major |
| Database | PostgreSQL for production; MySQL only if a future ADR approves the switch |
| Test database | SQLite in-memory |
| ORM | Eloquent |
| Validation | Laravel FormRequest |
| Styling/build | Tailwind CSS and Vite for Blade assets |
| Cache / key-value | Laravel cache APIs backed by Laravel Cloud Redis-compatible resources when needed |
| Queue | Laravel queues backed by Laravel Cloud managed queues or compatible worker resources |
| Scheduler | Laravel scheduler configured through Laravel Cloud scheduled tasks |
| Object storage | Laravel filesystem / Flysystem with S3-compatible durable object storage |
| Testing | PHPUnit through `php artisan test` |
| Formatting | Laravel Pint |
| Deployment target | Laravel Cloud with managed production resources |
| Production runtime assumptions | Laravel Cloud web compute, worker/queue compute, scheduled tasks, environment variables, domains/TLS, logs/metrics, managed database, Redis-compatible cache/KV, WebSockets if needed, and durable object storage |

## Context

- The repository is already a Laravel application with `composer.json`, migrations, Eloquent models, feature/unit tests, Blade views, Vite, and Laravel Cloud deployment notes.
- Laravel gives a practical default for API routing, middleware, validation, storage, database migrations, and testing.
- PostgreSQL is the production database target, while SQLite is limited to automated tests and must not be used as the production database on Laravel Cloud.
- Shareholders require Laravel Cloud as the production deployment platform because it simplifies Laravel operations.
- Laravel Cloud application filesystems are ephemeral, so production uploads and generated durable files must use object storage rather than local disk persistence.
- Laravel Cloud provides first-party or platform-managed resources for web compute, workers/queues, scheduler, PostgreSQL/MySQL databases, Redis-compatible cache/KV, object storage, logs, domains/TLS, preview environments, and WebSockets; architecture decisions should prefer these resources before adding external infrastructure.

## Consequences

- New backend code should follow Laravel conventions rather than introducing parallel frameworks.
- Database behavior that differs between SQLite and PostgreSQL needs explicit test awareness.
- Deployment, queue, scheduler, storage, and observability choices should prefer Laravel Cloud platform primitives.
- Deviations such as new queues, cache backends, WebSocket providers, frontend frameworks, non-Laravel runtimes, or external providers should be documented when they affect architecture.
