# ADR-004: Use role-based access with ownership scoping

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Use coarse role-based access control for endpoint boundaries and explicit ownership scoping inside queries/use cases.

Roles are:

- `buyer`
- `seller`
- `agent`
- `finance`

## Context

- Routes already group buyer, seller, agent, and finance capabilities behind `session.token` and `role:*` middleware.
- Role alone is not enough: sellers must only access their tenants/products/orders, buyers their cart/transactions, agents their managed UMKM, and finance only finance workflow data.
- The current data model encodes ownership through fields such as `transactions.user_id`, `tenants.owner_user_id`, `tenants.agent_user_id`, and `agent_commission_withdrawals.agent_user_id`.

## Consequences

- Endpoint tests must cover unauthorized, forbidden, and out-of-scope resource access.
- Controllers and support queries must filter by ownership before pagination or response mapping.
- If granular permissions become necessary, they should be added on top of this role model rather than replacing ownership checks.
