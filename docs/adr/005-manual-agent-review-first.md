# ADR-005: Keep agent verification review manual first

- **Status:** Accepted
- **Date:** 2026-06-19

## Decision

Agent registration collects verification data and starts with `pending_review`. Automated KYC or fully automated approval is deferred.

## Context

- Agent registration collects identity, contact, housing area, address, payout data, identity document path, terms acceptance, and privacy acceptance.
- The `users` table supports `agent_verification_status`, `agent_verified_at`, terms/privacy timestamps, and identity document path.
- The final review owner and allowed capabilities for `pending_review` agents are still open questions in the requirements.
- Implementing automated identity verification before policy and provider selection would add complexity without a stable business process.

## Consequences

- Agent review requires an internal manual process or future admin/operations screen.
- Sensitive features such as withdrawal may need status-based authorization.
- Review status changes must be audited once the review workflow is implemented.
