# ADR-002: Use a Laravel modular monolith

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Keep Kresekin API as a Laravel modular monolith: one codebase and one deployable application, with domain boundaries expressed through route groups, middleware, FormRequest classes, controllers, models, services, support classes, migrations, and tests.

## Context

- Current domains are tightly related: auth/session, buyer cart/checkout, seller tenant/product/order, agent dashboard/commission, finance transaction/disbursement, catalog/master data, and storage.
- Splitting these domains into services would add operational overhead before there is a scale or team-boundary reason.
- Laravel already provides the needed primitives for routing, validation, Eloquent relations, middleware, jobs, storage, tests, and deployment.

## Consequences

- Feature development stays simple and reviewable.
- Domain boundaries must be enforced by convention and tests, not by network boundaries.
- Shared behavior should live in `app/Support` or `app/Services` when it grows beyond controller orchestration.
