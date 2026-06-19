# ADR-011: Catalog covers product services only in R1

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

The R1 service catalog contains **product services only** (e.g. eClinic, Payment Gateway) and their modules. Internal/workplace IT services (email, VPN, network access, account provisioning, laptop/end-user support) are out of scope for R1. The request catalog is correspondingly product/infra-oriented (staging access, VM provisioning, environment requests), not workplace IT (new laptop, password reset).

## Context

- The platform's first job is replacing spreadsheet tracking for the product-engineering organization and giving structure to product incidents (including those escalated from Zoho).
- Workplace IT support is a separate domain with different fulfillment teams and would expand R1 scope significantly. It also sits awkwardly with the production-only CMDB ([ADR-005](005-cmdb-production-only.md)) — e.g. laptops are not CIs.
- The catalog is a taxonomy: adding a "Workplace IT" service grouping later is additive, not a redesign.

## Consequences

- Smaller R1 surface; routing and SLA examples are all product-service shaped.
- A ticket like "my laptop won't boot" has no catalog home in R1 — workplace IT is tracked elsewhere until this is revisited.
- Revisiting workplace IT services is an open question carried in [03-service-catalog.md](../requirements/03-service-catalog.md).
