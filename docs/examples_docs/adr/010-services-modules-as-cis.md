# ADR-010: Services and modules are catalog entities in R1, service-type CIs from R3

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

Adopt ITIL's layered service model:

- **Customer-facing service** (e.g. eClinic) — top-level catalog entry; SLA and Service Owner attach here.
- **Module / application service** (e.g. eClinic–Registration, eClinic–Logistics) — a component of a service; maintained by exactly one Team.
- **Infrastructure CIs** (servers, databases, load balancers) — what modules run on.

Implementation phasing:

- **R1:** Service and Module are plain **catalog entities** (taxonomy used for routing and SLA), each with a stable ID. No CMDB exists yet.
- **R3:** Service and Module are represented as **service-type CIs** in the CMDB (business-service CI and application-service CI). The CMDB becomes the single source of truth; the catalog references those CIs rather than holding a second copy.

## Context

- A service is layered/recursive in ITIL, not flat: one customer-facing service is composed of multiple application services and many infrastructure CIs. Modeling services as CIs is what enables impact analysis ("what breaks if this server dies?").
- Building the full CMDB in R1 would violate the release ordering (CMDB is R3, after the practices that consume it). But choosing the taxonomy now avoids a painful migration later.

## Consequences

- R1 catalog code treats Service/Module as first-class entities with stable IDs, so R3 can promote/link them to service-CIs without rekeying historical tickets.
- SLA attachment level (service vs per-module) is an open design point for the catalog and SLA docs.
- Routing uses `Module.maintained_by → Team` (see [02-roles-permissions.md](../requirements/02-roles-permissions.md) and [03-service-catalog.md](../requirements/03-service-catalog.md)).
