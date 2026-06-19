# ADR-012: Configuration-driven design for key taxonomies and behaviors

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

Rather than hardcoding certain taxonomies, matrices, and behaviors, the platform exposes them as **admin-configurable** settings. The explicitly configurable surfaces (R1):

- **Priority matrix** — the Impact × Urgency → Priority mapping.
- **Custom fields** — admins may add fields to incidents (and other ticket types) beyond the core schema.
- **Pending → SLA pause** — whether each Pending sub-reason pauses the SLA clock.
- **Intake mode** — per service: L1 triage-first (default) vs auto-route to the maintaining team.
- **Auto-close window** and **reopen window** durations.
- **Grafana severity → priority mapping.**
- **Category / subcategory taxonomy.**

Configurability is **bounded to this list**, not "everything is configurable." Anything outside it requires a new decision.

## Context

- Infokes' processes will evolve; the Incident Manager and Admins need to tune behavior without code changes.
- The builder is an AI agent — open-ended configurability would balloon scope and test surface. A bounded, enumerated list keeps it tractable.

## Consequences

- Each configurable surface needs storage, an admin UI, validation, and audit on change — real build cost, accepted deliberately.
- Specs reference this ADR rather than re-litigating "should X be configurable" per field.
- Configuration changes are themselves audited.
