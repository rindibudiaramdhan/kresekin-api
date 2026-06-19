# ADR-006: Keep finance payout workflow manual-first

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Model finance workflow internally first: finance users approve/reject agent withdrawals, mark withdrawals as paid, confirm buyer payment, and mark seller disbursement as completed. External payment or payout provider automation is deferred.

## Context

- Current finance routes cover dashboard, commission withdrawal review, seller transaction submissions, transactions, buyer payment confirmation, seller disbursement, and cancellation reason management.
- `agent_commission_withdrawals` stores requested/approved/rejected/paid states and finance actor/timestamp fields.
- `finance_transaction_disbursements` stores pending buyer payment, buyer payment confirmed, and disbursed to seller states.
- Provider choice for payment and payout is not finalized.

## Consequences

- Finance actions must be protected by role middleware and current-state guards.
- Manual workflow can be shipped and tested before external provider risk is introduced.
- Future provider integration must be idempotent and map provider events to internal state transitions.
