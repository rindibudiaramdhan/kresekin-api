# 00 — Vision & Scope

|              |                 |
| ------------ | --------------- |
| Status       | Draft           |
| Last updated | 2026-06-13      |
| Owner        | Iqbal (Infokes) |

## 1. Vision

A single internal platform for Infokes IT Service Management, aligned with ITIL 4 practices. It replaces spreadsheet-based tracking and becomes the system of record for incidents, service requests, problems, changes, and production configuration (CMDB). Zoho Desk remains the customer-facing ticket front-end; this platform serves internal users only.

## 2. Problem Statement

Current state:

- **Google Sheets** used for IT tracking: no workflow, no SLA measurement, no audit trail, no accountability.
- **Zoho Desk** used for ticketing: poor usability, not structured around ITIL practices, will remain only for customer ticket management.
- **No CMDB**: knowledge of production infrastructure lives in people's heads. Impact analysis during outages is guesswork.
- **No change tracking**: "what changed before this incident?" is unanswerable.

**Why build instead of buy:** Jira Service Management is the UX reference but its per-agent licensing is not viable at Indonesian purchasing power for ~120 agent-role users. Zoho Desk has failed on usability. Open-source alternatives (GLPI, iTop) do not fit the desired UX and stack. Building in-house: license cost is zero, the tool fits Infokes processes exactly, and ISO 27001 audit requirements can be designed in from the start. See [ADR-001](../adr/001-build-vs-buy.md).

## 3. Goals

| ID  | Goal                                                                 | Measure                                                      |
| --- | -------------------------------------------------------------------- | ------------------------------------------------------------ |
| G1  | All internal IT incidents and service requests tracked in the system | Google Sheets tracking retired within 1 month of R1 adoption |
| G2  | Every incident has an SLA target                                     | SLA compliance measurable from R1                            |
| G3  | MTTR baseline established                                            | Within 1 month of R1 adoption                                |
| G4  | Production CI inventory accurate                                     | ≥ 95% accuracy, audited quarterly, post-R3                   |
| G5  | Every production infra change has a change record                    | Post-R3                                                      |
| G6  | "What changed before this incident?" answerable                      | < 1 minute via CMDB + change history, post-R3                |

## 4. Non-Goals (Out of Scope)

| Item                                                        | Reason / substitute                                                                                                                                                      |
| ----------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Multi-tenancy, customer portal, resale                      | Internal tool. Cheap guardrails baked in (see [ADR-002](../adr/002-defer-multitenancy-with-guardrails.md)); full support deferred indefinitely.                          |
| Zoho Desk bridge/integration                                | No integration owner identified. Substitute: `external_ref` field on incidents for manual linking. Future consideration. See [ADR-003](../adr/003-defer-zoho-bridge.md). |
| WhatsApp notifications                                      | Meta Business API cost/approval overhead. Notification adapter interface allows adding later. See [ADR-004](../adr/004-notification-adapter-defer-whatsapp.md).          |
| Hardware/laptop asset management, procurement, depreciation | CMDB covers production assets only. See [ADR-005](../adr/005-cmdb-production-only.md).                                                                                   |
| Product deploy tracking in Change Enablement                | MVP tracks infra changes manually. Change API designed in so CI/CD integration can come later.                                                                           |
| Release Management practice                                 | Product CI/CD pipelines already cover release coordination. Duplicating it adds no value.                                                                                |
| Financial Asset Management                                  | Out entirely.                                                                                                                                                            |
| Full 34-practice ITIL coverage                              | Only practices listed in §6.                                                                                                                                             |
| Bahasa Indonesia UI                                         | English only.                                                                                                                                                            |

## 5. Personas & Process Owners

One person may hold multiple roles. Roles are per-practice, not per-person.

### Support tiers

| Tier | Team                                        | Role in system                                                                         |
| ---- | ------------------------------------------- | -------------------------------------------------------------------------------------- |
| L1   | Customer Service                            | Service Desk Agent — intake, triage, categorization, resolve known issues, route to L2 |
| L2   | Technical Support                           | Technical Support — deeper troubleshooting, KB-driven fixes, escalate to L3            |
| L3   | Infra / Engineering (via assignment groups) | Technical Specialist — root cause work, code/infra fixes                               |

### Roles

| Role                      | Likely team               | Responsibility                                            |
| ------------------------- | ------------------------- | --------------------------------------------------------- |
| Requester                 | All employees (~300)      | Files incidents and service requests                      |
| Service Desk Agent (L1)   | Customer Service          | Front-line triage and routing                             |
| Technical Support (L2)    | Technical Support         | Escalated troubleshooting                                 |
| Technical Specialist (L3) | Engineering, QA, Infra    | Escalated incidents, problem RCA, change implementation   |
| Group Lead                | Per assignment group      | Manages group membership, assigns work within group       |
| Incident Manager          | Customer Service          | Owns Incident Management practice, drives major incidents |
| Problem Manager           | Customer Service          | Owns Problem Management practice                          |
| Change Manager            | Infra/Sysadmin lead       | Owns Change Enablement practice                           |
| CMDB Owner                | Infra/Sysadmin            | Owns CI data quality and audit cycle                      |
| Knowledge Editor          | Technical Support + Infra | Curates knowledge base                                    |
| Service Owner             | Per catalog service       | Owns catalog entry and its SLA targets                    |
| Admin                     | Infra                     | Platform configuration, user/role management              |

**Assignment Groups** are a first-class concept: tickets route to groups (e.g., "eClinic Backend", "Infra/Network", "Database"), then to individuals. Group membership is managed by Admins and Group Leads. Detailed in [02-roles-permissions.md](02-roles-permissions.md).

## 6. Scope Summary

| Practice / capability                                                          | Release          |
| ------------------------------------------------------------------------------ | ---------------- |
| Auth (Google SSO, JIT provisioning), user management, roles, assignment groups | R1               |
| Service Catalog                                                                | R1               |
| Incident Management                                                            | R1               |
| Service Request Management                                                     | R1               |
| SLA engine (targets, timers, business calendars, escalations)                  | R1               |
| Notifications (Email + Mattermost; adapter interface)                          | R1               |
| Grafana webhook → incident creation                                            | R1               |
| Audit trail (ISO 27001)                                                        | R1, foundational |
| Knowledge Management                                                           | R2               |
| Problem Management                                                             | R2               |
| SLA reporting & dashboards                                                     | R2               |
| CMDB (CI classes, relationships, lifecycle)                                    | R3               |
| Change Enablement (incl. Change API)                                           | R3               |

Change Enablement deliberately follows CMDB: change impact assessment requires CI relationship data. See [ADR-006](../adr/006-change-after-cmdb.md). Full release detail in [12-release-plan.md](12-release-plan.md).

## 7. Constraints & Assumptions

- **Builder:** AI agent (Claude Code); human review by Iqbal. Specs must therefore be precise: explicit data models, API contracts, testable acceptance criteria.
- **Stack (fixed):** Bun, Elysia, TypeScript, Drizzle ORM, PostgreSQL, Vue 3 + Vite (Tailwind CSS 4, Iconify, PrimeVue 4), Valkey (Redis-compatible), AWS S3, OpenTelemetry, Grafana. See [ADR-009](../adr/009-tech-stack.md).
- **Deployment:** Dockerized, cloud-hosted.
- **Auth:** Google Workspace SSO mandatory; no local passwords. JIT provisioning. See [ADR-007](../adr/007-jit-provisioning-google-groups.md).
- **Compliance:** Infokes is ISO 27001 certified. Append-only audit trail is a mandatory R1 NFR (see [01-architecture-nfr.md](01-architecture-nfr.md)).
- **Language:** English UI only.
- **Notifications:** Email + Mattermost first, behind an adapter interface.
- **Scale:** ~300 total users; ~120 in agent-type roles (L1/L2/L3, managers).
- **Change approval:** async, no CAB meetings. Change types: standard / normal / emergency. See [ADR-008](../adr/008-async-change-approval-no-cab.md).

## 8. Risks

| Risk                                            | Mitigation sketch                                                                                      |
| ----------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| Adoption failure (gsheet habits persist)        | R1 must beat gsheet on speed of ticket entry; management mandate to retire sheet                       |
| CMDB goes stale post-R3                         | Incident/Change workflows must consume CI data (forcing function); quarterly audit owned by CMDB Owner |
| Single-reviewer bottleneck (one human reviewer) | Phased releases; small reviewable increments                                                           |
| Notification fatigue                            | Notification design reviewed per-practice; digest options                                              |
| Scope creep ("can it also do X")                | Non-goals list above; changes require ADR                                                              |

## 9. Open Questions

- [ ] Is G4 (95% CI accuracy, quarterly audit) realistic for the infra team's capacity? — needs infra-team input; revisit at R3.

Resolved: persona → team mapping (§5) confirmed; L2 = a **single "Technical Support" group**; audit retention **3 years** ([01 §5](01-architecture-nfr.md)); sole spec reviewer is **Iqbal** (solo build with AI agent, paced by review — [12](12-release-plan.md)).
