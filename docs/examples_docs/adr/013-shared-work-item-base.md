# ADR-013: Incidents and service requests share a work-item base

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

Incidents and Service Requests are built on a shared **work-item base** rather than as fully separate entities. The base owns what they have in common:

- `number`, `requester`, `channel`, `external_ref`
- assignment (`assigned_group`, `assignee`)
- comments (public) and work notes (internal)
- attachments, watchers
- links (to other work items, KB, etc.)
- state-transition history and audit
- notification hooks

Type-specific extensions sit on top:

- **Incident:** impact/urgency/priority, affected service/module, resolution code/notes.
- **Service Request:** catalog item, form responses, approvals, fulfillment checklist.

Whether Problems (R2) and Changes (R3) reuse the base is decided in their own docs.

## Context

- The two types share a large surface; duplicating comments/attachments/notifications/audit twice would be wasteful and error-prone.
- The builder is an AI agent — one well-specified base subsystem is easier to build correctly and test once.
- Reclassification between incident and request ([05](../requirements/05-service-request.md)) is natural when both share a base.

## Consequences

- A `type` discriminator on the base; shared services for comments, attachments, notifications, and audit.
- Discipline required so type-specific logic does not leak into the base.
- Incident (04) is described as sitting on this base.
