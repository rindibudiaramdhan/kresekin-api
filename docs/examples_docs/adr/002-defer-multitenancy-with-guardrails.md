# ADR-002: Defer multi-tenancy / customer-facing support, with cheap guardrails

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

The platform is single-tenant and internal-only. Customer-facing support is deferred indefinitely — but three cheap architectural guardrails are baked in now to avoid a brutal retrofit if it ever comes:

1. **`Requester` entity is separate from `Employee`/`User`.** Requester has a type: `employee`, `external_contact`, or `system`.
2. **Ticket origin is a channel abstraction**: portal, email, API. A future customer channel enters through the same mechanism as the Grafana webhook does today.
3. **Permission checks are scoped from day 1** (role + team scoping in every query). Never "logged in = see everything."

## Context

- Customer-facing tickets are currently handled in Zoho Desk, which stays as the customer front-end. That covers most of the foreseeable need.
- Not for resale, so tenant isolation, branding, customer portals, and per-customer SLAs would be speculative generality.

## Consequences

- MVP stays small; no tenant infrastructure to build or test.
- If customer-facing support is needed later, the data model and permission model do not need rework — only new channels and UI.
