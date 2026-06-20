# ADR-014: Use Laravel Cloud as the mandatory production deployment platform

- **Status:** Accepted
- **Date:** 2026-06-20

## Decision

Deploy Kresekin API production workloads on Laravel Cloud. The production runtime, database connectivity, environment variables, deployment commands, queues, scheduled tasks, logs, and persistent file storage strategy must be compatible with Laravel Cloud.

This decision does not decide the future frontend architecture. Any future frontend stack must be evaluated separately while remaining compatible with Laravel Cloud as the required production platform.

## Context

- Shareholders prefer Laravel Cloud because it reduces production operations overhead for a Laravel application.
- The codebase is already a Laravel modular monolith with PostgreSQL as the production database target.
- Laravel Cloud provides managed Laravel deployment, environment variables, PostgreSQL resources, queues, scheduled tasks, logs, TLS, and object storage integration.
- Laravel Cloud application filesystems are ephemeral, so runtime-generated files must not rely on local disk persistence.
- Build and deploy commands run under platform constraints and must stay deterministic, bounded, and repeatable.

## Consequences

- Production changes must assume Laravel Cloud as the default platform unless a new ADR approves an exception.
- Persistent uploads and sensitive documents must use durable object storage through Laravel filesystem/Flysystem, not local application disk.
- Queue workers and scheduled tasks should use Laravel Cloud platform primitives where available.
- Deployment commands must cover dependency installation, cache/build steps, migration strategy, and rollback/mitigation expectations.
- Operational requirements must explicitly account for Laravel Cloud limits, log retention, environment separation, backup/restore, and resource scaling.
- Introducing a long-running non-Laravel runtime, custom server process, or external platform dependency requires architecture review before implementation.
