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

## E20261003 category transition — 2026-10-04

Product categories use the `product_categories` database master and environment-specific UUIDs. API and web writers select `product_category_id`; admin code uses the same master selection and dual-write contract. Tenant constants remain only for legacy readers/fixtures during the transition, and are no longer the source of product choices. Roles, transaction statuses and cancellation reasons retain their existing domain rules.

During expansion, writers preserve `products.category` as the selected master name alongside its ID. Only legacy API edits that omit the ID may resolve an exact unique name, then a unique whitespace/case-normalized name. No fuzzy matching or automatic master creation is permitted. Master seeding preserves existing UUIDs by slug; clients fetch IDs from their own environment. Tenant response removal and schema cleanup remain gated by E20261003 readiness. This records local implementation; deployment and client readiness are not yet verified.
