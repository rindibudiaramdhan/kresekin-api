# 99 — Glossary

| | |
|---|---|
| Status | Living | 
| Last updated | 2026-06-13 |

Terms used across the requirement docs. ITIL terms follow ITIL 4 usage.

## ITIL & Process

| Term | Definition |
|---|---|
| **ITSM** | IT Service Management — managing IT services across their lifecycle. |
| **ITIL 4** | The framework of best-practice ITSM practices this platform aligns to. |
| **Practice** | An ITIL capability area (Incident Management, Change Enablement, …). |
| **Service** | A means of enabling value for consumers; here, a customer-facing/product service (e.g. eClinic). Top-level catalog entry. |
| **Business service** | A service as consumed (the customer-facing layer); modeled as a business-service CI in R3. |
| **Application service** | A technical service component; here a **Module** (e.g. eClinic–Registration); a service CI in R3. |
| **Module** | A component of a Service; the routing key (maintained by one Team). |
| **Service Catalog** | The list of services incidents are filed against. |
| **Request Catalog** | The menu of requestable items service requests are raised from. |
| **Incident** | An unplanned interruption or degradation of a service. |
| **Service Request** | A user-initiated request for something pre-defined (access, provisioning, info). |
| **Problem** | The underlying cause of one or more incidents. |
| **Known Error** | A problem with documented root cause and workaround. |
| **Workaround** | A temporary means of reducing impact before a permanent fix. |
| **RCA** | Root-Cause Analysis (e.g. 5-whys, fishbone/Ishikawa). |
| **Change** | Addition, modification, or removal affecting a service/CI. |
| **Change Enablement** | The ITIL 4 practice for assessing, authorizing, and managing changes. |
| **Standard / Normal / Emergency change** | Pre-approved / async-approved / expedited-with-retroactive-review change types ([ADR-008](../adr/008-async-change-approval-no-cab.md)). |
| **CAB** | Change Advisory Board — a change-review body; **not used here** (async approval instead). |
| **PIR** | Post-Implementation Review of a change. |
| **Release Management** | Coordinating release of changes; **out of scope** (CI/CD covers it). |
| **CMDB** | Configuration Management Database — record of CIs and relationships. |
| **CI** | Configuration Item — any managed (production) component. |
| **CI Class** | A type of CI with its own attribute schema. |
| **SLA** | Service Level Agreement — a target (response/resolution/fulfillment time). |
| **OLA** | Operational Level Agreement — internal team-to-team target; **deferred**. |
| **Assignment Group / Team** | The people-container that work routes to; members + one Group Lead. |
| **Service Owner** | Owner of a service's catalog entry and SLA targets. |
| **MoSCoW** | Prioritization: Must / Should / Could / Won't. |

## Metrics

| Term | Definition |
|---|---|
| **MTTD** | Mean Time To Detect — fault start → detection. |
| **MTTA** | Mean Time To Acknowledge — detection → ownership. |
| **MTTR** | Mean Time To Resolve — detection → resolved. |
| **MTRS** | Mean Time to Restore Service — fault start → resolved (impact window). |
| **MTBF** | Mean Time Between Failures — uptime ÷ failures, per service. |
| **Availability %** | (period − downtime) ÷ period. |
| **FCR** | First-Contact Resolution — resolved at L1 without reassignment. |
| **Reopen rate** | Reopened ÷ total — a quality signal. |
| **SLA compliance %** | Targets met ÷ total. |
| **Change failure rate** | Failed/rolled-back changes ÷ total. |

## Technical

| Term | Definition |
|---|---|
| **NFR** | Non-Functional Requirement — a quality (speed, security, availability), not a feature. |
| **SSO** | Single Sign-On. |
| **OIDC** | OpenID Connect — the SSO protocol used with Google Workspace. |
| **JIT provisioning** | Just-In-Time — user created on first successful login. |
| **RBAC** | Role-Based Access Control. |
| **RPO** | Recovery Point Objective — max acceptable data loss (time). |
| **RTO** | Recovery Time Objective — max acceptable downtime to restore. |
| **WAL** | Write-Ahead Log — Postgres change log; archived for recovery. |
| **PITR** | Point-In-Time Recovery — restore to any moment via snapshot + WAL. |
| **FTS** | Full-Text Search (Postgres). |
| **BullMQ** | Background-job library running on Valkey. |
| **Valkey** | Redis-compatible cache/queue store. |
| **OTel** | OpenTelemetry — traces/metrics/logs pipeline to Grafana. |
| **Presigned URL** | Time-limited S3 URL for direct attachment access. |
| **Webhook** | Inbound HTTP callback (e.g. Grafana → incident). |
| **Work-item base** | Shared core for incidents/requests/problems/changes ([ADR-013](../adr/013-shared-work-item-base.md)). |

## Project & Org

| Term | Definition |
|---|---|
| **Infokes** | The company; builds healthcare products (eClinic, ePuskesmas) and others. |
| **eClinic / Payment Gateway** | Example product services. |
| **Zoho Desk** | The customer-facing ticketing tool retained for customer tickets. |
| **Mattermost** | The chat tool used for notifications. |
| **L1 / L2 / L3** | Support tiers: Customer Service / Technical Support / Infra-Engineering. |
| **Requester** | Anyone who files a ticket; distinct from User ([ADR-002](../adr/002-defer-multitenancy-with-guardrails.md)). |
| **ISO 27001** | Information-security certification Infokes holds; drives the audit trail. |
| **UU PDP** | Indonesia's Personal Data Protection law. |
| **Incident/Problem/Change Manager, CMDB Owner, Knowledge Editor, Admin** | Process-owner and admin roles ([02 §3](02-roles-permissions.md)). |
