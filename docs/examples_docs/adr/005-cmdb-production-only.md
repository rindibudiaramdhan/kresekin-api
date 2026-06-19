# ADR-005: CMDB covers production assets only

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

The CMDB tracks production assets only: servers/VMs, databases, application services, network devices, domains/TLS certificates, external SaaS dependencies. Hardware/end-user asset management (laptops, phones, peripherals), procurement, and depreciation are explicitly out of scope.

## Context

- The goal of the CMDB is impact analysis for incidents and changes on production systems.
- End-user hardware tracking is the classic sneaky scope creep in CMDB projects, and it serves a different process (asset/financial management) that Infokes has not prioritized.

## Consequences

- Smaller, maintainable CI inventory with a clear owner (Infra/Sysadmin).
- If hardware tracking is ever wanted, it is a new CI class and a separate decision — not assumed by the data model.
