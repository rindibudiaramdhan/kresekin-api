# ADR-011: Keep Indonesia region lookup behind a service boundary

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Keep Indonesia region lookup behind `BpsRegionService` instead of coupling controllers directly to the external BPS data source.

## Context

- Public endpoints expose province, regency, district, and village lookups.
- Region data is used by registration/profile flows and should remain stable for clients.
- External provider failures need consistent error handling and are not the same as validation failures.

## Consequences

- Provider behavior can be cached, replaced, or mocked without changing controller contracts.
- External failures should map to clear API errors such as `502`.
- Region response shape should be treated as a client contract.
