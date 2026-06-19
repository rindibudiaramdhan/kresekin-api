# ADR-008: Async change approval; no CAB meetings

- **Status:** Accepted
- **Date:** 2026-06-13

## Decision

Change approval is asynchronous in-tool: a change record is created, designated approvers are notified, and they approve/reject with comments. There is no Change Advisory Board (CAB) meeting. Three change types:

| Type          | Approval                                                                         |
| ------------- | -------------------------------------------------------------------------------- |
| **Standard**  | Pre-approved (recurring, low-risk, documented procedure); no per-change approval |
| **Normal**    | Async approval by designated approvers before implementation                     |
| **Emergency** | Implement first, retroactive review required                                     |

## Context

- Infokes is < 300 people with 10–20 infra staff. Weekly CAB meetings at this size are process theater that delays changes without reducing risk.
- ITIL 4 itself moved away from mandatory CABs toward decentralized, risk-based approval.

## Consequences

- Change throughput is limited by approver responsiveness, not meeting cadence; escalation/reminder rules belong in the SLA/notification design.
- The Standard-change catalog (which procedures are pre-approved) is owned by the Change Manager.
