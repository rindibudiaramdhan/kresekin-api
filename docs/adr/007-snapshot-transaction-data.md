# ADR-007: Snapshot transaction data at checkout

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Snapshot important catalog, payment, delivery, promo, and item data into transactions and transaction items at checkout.

## Context

- Transaction fields include subtotal, delivery fee, total, delivery method/code, pickup option, payment method/code/option, promo code/name/type/value, and discount amount.
- Transaction history must remain understandable even if product, tenant, promo, delivery, or payment master data changes later.
- Finance, seller, buyer, and agent dashboards all rely on transaction history for operational and financial reporting.

## Consequences

- Checkout must calculate and persist authoritative values server-side.
- Master data changes do not rewrite history.
- Transaction response mapping should distinguish historical snapshot fields from current catalog fields.
