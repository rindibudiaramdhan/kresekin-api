# ADR-003: Defer Zoho Desk bridge; use external_ref field instead

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

No automated Zoho Desk ↔ ITSM integration. Instead, incidents (and other tickets) carry an `external_ref` field (free text / URL) where agents paste the Zoho ticket link when a customer issue escalates to an internal incident.

## Context

- Zoho Desk remains the customer ticket front-end; this platform is internal ITSM.
- No owner has been identified for maintaining a field mapping between the two systems. An integration without an owner is a dead integration.

## Consequences

- Manual but immediate traceability between customer tickets and internal incidents from R1.
- An automated bridge (webhook-based escalation, status sync) remains a future consideration, gated on someone owning the mapping.
