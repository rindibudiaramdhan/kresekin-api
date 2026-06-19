# ADR-012: Use constants and configuration for domain values

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Use model constants, configuration, and seeders for stable domain values such as roles, statuses, categories, rejection reasons, and commission rate.

## Context

- `User`, `Transaction`, `Tenant`, `AgentCommissionWithdrawal`, and `FinanceTransactionDisbursement` expose constants for roles, statuses, categories, and valid reason codes.
- `AgentCommissionCalculator` reads commission rate from `config('api.agent_commission_rate', 0.05)`.
- Seeders exist for product categories, product units, payment methods, delivery methods, order time options, housing areas, promo codes, and tenant/product data.

## Consequences

- Avoid scattering raw strings across controllers and tests.
- Changing domain values should be deliberate and documented when it affects API contracts or reports.
- If business users need runtime-editable configuration later, it should be introduced as a separate feature with audit requirements.
