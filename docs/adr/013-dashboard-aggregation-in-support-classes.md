# ADR-013: Keep dashboard aggregation in support classes

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Keep dashboard aggregation, formatting, and period calculations in support classes under `app/Support/Dashboard` instead of embedding complex reporting queries directly in controllers.

## Context

- Dashboard endpoints exist for seller, agent, and finance.
- The codebase already includes support classes such as `AgentDashboardAggregator`, `FinanceDashboardAggregator`, `DashboardPeriod`, `DashboardFormatter`, and `AgentManagedUmkmPerformanceQuery`.
- Dashboard data combines transactions, transaction items, tenants, withdrawals, and disbursements, with role-specific scoping.

## Consequences

- Controllers stay focused on request orchestration and response delivery.
- Aggregation logic can be unit tested independently.
- Agent and finance dashboard calculations can diverge safely without mixing data scopes.
