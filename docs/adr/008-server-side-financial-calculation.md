# ADR-008: Calculate money and commission server-side

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

All money, discount, total, commission, withdrawal availability, and disbursement values are calculated or validated on the server.

## Context

- Buyer checkout, promo validation, agent commission, withdrawal requests, and finance disbursement all affect financial state.
- `AgentCommissionCalculator` calculates completed revenue, commission rate, locked withdrawal amount, and available commission from database records.
- Client-provided totals or status values would be unsafe and could create reconciliation errors.

## Consequences

- Financial workflows should use database transactions and current-state checks.
- Tests must assert database values, not only response shape.
- Monetary values are represented as integers in API responses and database fields.
