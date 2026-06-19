# ADR-009: Tech stack

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

| Concern           | Choice                                                |
| ----------------- | ----------------------------------------------------- |
| Runtime           | Bun                                                   |
| Backend framework | Elysia (TypeScript)                                   |
| ORM               | Drizzle                                               |
| Database          | PostgreSQL                                            |
| Frontend          | Vue 3 + Vite                                          |
| Styling           | Tailwind CSS 4                                         |
| Icons             | Iconify (+ `@iconify/tailwind` plugin)                |
| UI components      | PrimeVue 4 (unstyled + Tailwind passthrough)          |
| Cache / queues    | Valkey (Redis-compatible); BullMQ for background jobs |
| Object storage    | AWS S3 (attachments)                                  |
| Email             | AWS SES                                               |
| Observability     | OpenTelemetry → Grafana                               |
| Packaging         | Docker, cloud-hosted                                  |

## Context

- Stack matches what Infokes already runs and knows — internal tools rot when they need exotic skills to maintain.
- SLA timers, escalations, and notifications require a reliable background-job system from R1; BullMQ on Valkey covers scheduled and delayed jobs.
- The platform observes itself with the same OTel + Grafana pipeline it integrates with for alert-driven incident creation.

## Frontend rationale

- **Tailwind 4** is the styling source of truth; **Iconify** provides icons across sets via the Tailwind plugin.
- **PrimeVue 4** is included for complex, behavior-heavy components an ITSM needs — DataTable/TreeTable (sort/filter/paginate/lazy/virtual-scroll), DatePicker, MultiSelect, Dialog, Toast, FileUpload — which would take weeks to rebuild. It runs **unstyled** with **Tailwind passthrough** so Tailwind owns the look; PrimeVue owns structure/behavior. A headless alternative (Reka UI + TanStack Table) was rejected as more build for no gain on an internal ops tool.

## Consequences

- No stack debates per feature; deviations require a new ADR.
- Audit log entries carry the OTel request/trace ID, linking compliance records to traces.
- PrimeVue is used for complex widgets only; simple layout/markup uses Tailwind directly, avoiding two styling systems fighting.
