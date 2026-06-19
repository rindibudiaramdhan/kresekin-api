# 01 — Architecture & Non-Functional Requirements

|              |                 |
| ------------ | --------------- |
| Status       | Draft           |
| Last updated | 2026-06-13      |
| Owner        | Iqbal (Infokes) |

> **NFR** = Non-Functional Requirement: a quality the system must have (speed, security, availability, traceability), as opposed to a feature it performs. The requirements below are testable and apply across all practices.

## 1. Architecture Shape — Modular Monolith

One codebase, one deployable image. Modules per practice: `identity`, `catalog`, `incident`, `request`, `sla`, `notification`, `audit`, `knowledge`, `problem`, `cmdb`, `change`.

- Two runtime roles from the **same image**: `web` (Elysia API) and `worker` (BullMQ consumers).
- Backing services: PostgreSQL, Valkey, AWS S3.
- **Module boundaries enforced:** modules interact only through a published interface, no reaching into another module's internals. Keeps future service extraction possible without committing to microservices now.

Rationale: ~300 users, single human reviewer, AI builder. Microservice sprawl would make review and operation disproportionately costly. See [ADR-009](../adr/009-tech-stack.md).

| ID       | Requirement                                                                                   | Priority |
| -------- | --------------------------------------------------------------------------------------------- | -------- |
| ARCH-001 | System is a modular monolith; practices are modules with enforced public-interface boundaries | Must     |
| ARCH-002 | `web` and `worker` run from the same OCI image, differing only by entrypoint/config           | Must     |

## 2. API

- REST/JSON, versioned under `/api/v1`.
- OpenAPI spec auto-generated from Elysia TypeBox schemas — documentation cannot drift from implementation.
- Webhooks isolated under `/webhooks/*` (e.g. `/webhooks/grafana`), token-authenticated, separately rate-limited.
- Vue 3 SPA (built with Vite; Tailwind CSS 4, Iconify, PrimeVue 4 unstyled — [ADR-009](../adr/009-tech-stack.md)) consumes the API and is served as static assets via **CDN/static hosting** (not an app-server sidecar).

| ID      | Requirement                                                        | Priority |
| ------- | ------------------------------------------------------------------ | -------- |
| API-001 | All application endpoints under `/api/v1`, REST/JSON               | Must     |
| API-002 | OpenAPI spec generated from code-level schemas                     | Must     |
| API-003 | Webhook endpoints isolated, token-auth, independently rate-limited | Must     |
| API-004 | SPA served as static assets via CDN/static hosting                 | Must     |

## 3. Authentication & Sessions

- Google Workspace SSO via OIDC authorization-code flow; `hd` claim restricted to the Infokes domain. No local passwords. See [ADR-007](../adr/007-jit-provisioning-google-groups.md).
- **Server-side sessions in Valkey** (revocable — required for clean ISO 27001 access control; avoids JWT revocation pain).
- httpOnly + Secure cookies, SameSite, CSRF protection on state-changing requests.
- Idle timeout 8h, absolute timeout 24h (both configurable).

| ID       | Requirement                                            | Priority |
| -------- | ------------------------------------------------------ | -------- |
| AUTH-001 | OIDC code flow with Google; domain-restricted via `hd` | Must     |
| AUTH-002 | Server-side sessions in Valkey; individually revocable | Must     |
| AUTH-003 | httpOnly/Secure/SameSite cookies; CSRF protection      | Must     |
| AUTH-004 | Idle 8h / absolute 24h session timeouts, configurable  | Should   |

## 4. Authorization (RBAC)

- Permissions expressed as `practice:action` (e.g. `incident:assign`, `change:approve`).
- A role is a bundle of permissions; a user may hold multiple roles.
- Two enforcement layers: (a) permission middleware per route, (b) data scoping in queries (role + team/group), per [ADR-002](../adr/002-defer-multitenancy-with-guardrails.md) — never "authenticated = see everything."
- Full role/permission catalog lives in [02-roles-permissions.md](02-roles-permissions.md); this section fixes the mechanism.

| ID        | Requirement                                                            | Priority |
| --------- | ---------------------------------------------------------------------- | -------- |
| AUTHZ-001 | Permissions modeled as `practice:action`; roles are permission bundles | Must     |
| AUTHZ-002 | Route-level permission checks AND query-level data scoping             | Must     |

## 5. Audit Trail (ISO 27001)

Infokes is ISO 27001 certified; the audit trail is a mandatory R1 foundation.

- Append-only `audit_log`: `timestamp`, `actor` (user or `system`), `action`, `entity_type`, `entity_id`, `before`/`after` (JSONB diff), `request_id` (= OTel trace id), `source_ip`.
- **Immutability at the database level:** the application's Postgres role is granted `INSERT` + `SELECT` only on `audit_log`; no `UPDATE`/`DELETE`.
- Audit entry is written **in the same transaction** as the mutation it records — no fire-and-forget.
- Covers: all ticket/CI/change mutations, role & permission changes, login events, configuration changes, data exports.
- Read access restricted to Admin; CSV export for auditors.
- Retention: **3 years** (pending confirmation against ISO scope doc).

| ID      | Requirement                                                                 | Priority |
| ------- | --------------------------------------------------------------------------- | -------- |
| AUD-001 | Append-only audit_log with actor/action/entity/before-after/request_id/IP   | Must     |
| AUD-002 | DB-enforced immutability (no UPDATE/DELETE for app role)                    | Must     |
| AUD-003 | Audit write in same transaction as the recorded mutation                    | Must     |
| AUD-004 | Covers data mutations, auth events, role/permission/config changes, exports | Must     |
| AUD-005 | Admin-only read; CSV export; 3-year retention                               | Must     |

Each practice doc carries an "Audit requirements" section enumerating its auditable events.

## 6. Background Jobs

BullMQ on Valkey. Job families: SLA timers & escalations, notification dispatch, digests, media transcoding, recurring maintenance (e.g. cert-expiry checks post-R3).

| ID      | Requirement                                                  | Priority |
| ------- | ------------------------------------------------------------ | -------- |
| JOB-001 | Background jobs on BullMQ/Valkey                             | Must     |
| JOB-002 | Handlers idempotent; retry with exponential backoff          | Must     |
| JOB-003 | Dead-letter queue for exhausted jobs; alerting on DLQ growth | Must     |
| JOB-004 | Job metrics exported to OTel                                 | Should   |

## 7. Performance

| ID       | Requirement                                                                           | Priority |
| -------- | ------------------------------------------------------------------------------------- | -------- |
| PERF-001 | p95 API latency < 300ms (excl. transcode/report jobs)                                 | Must     |
| PERF-002 | List endpoints paginated; default 25, max 100 per page                                | Must     |
| PERF-003 | p95 SPA initial load < 2s on office network                                           | Should   |
| PERF-004 | Webhook ingest absorbs ≥ 100 alerts/min; dedup before incident creation; burst queued | Must     |

## 8. Availability & Disaster Recovery

**Target: 99.5% monthly availability** — about 3.6 hours of allowed downtime per month. Chosen because the deployment is single-EC2, self-managed, without a hot failover replica; 99.9% would require multi-AZ redundant Postgres and load-balanced app servers, roughly doubling infra cost for a business-hours-centric internal tool. Pre-announced maintenance windows are excluded from the figure.

**Backups (self-managed Postgres on EC2 — this is the infra team's responsibility, no managed-service safety net):**

- **WAL** (Write-Ahead Log): Postgres records every change to a log before applying it. Continuously archiving the WAL to S3 makes replay possible.
- **PITR** (Point-In-Time Recovery): daily base snapshot + archived WAL = restore to any moment (e.g. just before a bad migration), not merely the last snapshot.
- **RPO** (Recovery Point Objective) — max acceptable data loss: **≤ 1 hour**.
- **RTO** (Recovery Time Objective) — max acceptable downtime to restore: **≤ 4 hours**.
- Tooling: **pgBackRest or WAL-G** (both free), pushing snapshots + WAL to S3.
- S3 versioning enabled on the attachments bucket.
- **Restore drills quarterly** — an untested backup is not a backup; auditors will ask for evidence.

| ID        | Requirement                                                             | Priority |
| --------- | ----------------------------------------------------------------------- | -------- |
| AVAIL-001 | 99.5% monthly availability target; planned maintenance excluded         | Must     |
| DR-001    | Continuous WAL archiving to S3 + daily base snapshot (pgBackRest/WAL-G) | Must     |
| DR-002    | RPO ≤ 1h, RTO ≤ 4h                                                      | Must     |
| DR-003    | S3 versioning on attachments bucket                                     | Should   |
| DR-004    | Quarterly restore drill with recorded evidence                          | Must     |

## 9. Security

| ID      | Requirement                                                        | Priority |
| ------- | ------------------------------------------------------------------ | -------- |
| SEC-001 | TLS for all traffic                                                | Must     |
| SEC-002 | Encryption at rest: encrypted EBS volumes (Postgres), S3 SSE       | Must     |
| SEC-003 | Secrets in a secret manager; never committed to the repo           | Must     |
| SEC-004 | Least-privilege Postgres roles (esp. audit_log INSERT/SELECT only) | Must     |
| SEC-005 | Input validation at the API boundary (TypeBox schemas)             | Must     |
| SEC-006 | Rate limiting per-user and per-IP                                  | Must     |
| SEC-007 | Dependency vulnerability audit in CI                               | Should   |

### 9.1 Attachments & Media

Internal users only → low risk. Inline rendering is **kept** (download-only would hurt UX and effectiveness). The conversion rules make inline rendering safe: webp/webm cannot execute scripts.

- **Images → WebP, converted client-side** (Canvas API): cheap, instant preview, smaller uploads.
- **Video → WebM, converted server-side** (ffmpeg in a worker): client-side video transcoding (ffmpeg.wasm) is too heavy/unreliable. Flow: upload original → `processing` state → worker transcodes → ready.
- **The server is the security boundary, never the client:** validate true type by magic bytes, strip metadata, re-encode or reject. Client-side conversion is a UX/bandwidth optimization only.
- Serve attachments via **presigned S3 URLs** — S3 domain is a different origin from the app, so a hostile file is not same-origin with app session cookies.
- **Renderable allowlist:** webp, webm, PDF render inline; everything else downloads. **SVG is blocked or rasterized** (can carry scripts).
- Private bucket; 25MB upload cap; correct `Content-Type` on stored objects.

| ID      | Requirement                                                                      | Priority |
| ------- | -------------------------------------------------------------------------------- | -------- |
| ATT-001 | Images converted to WebP client-side; server validates and may re-encode         | Must     |
| ATT-002 | Video converted to WebM server-side via worker; `processing` state until ready   | Must     |
| ATT-003 | Server validates file type by magic bytes; strips metadata; rejects on mismatch  | Must     |
| ATT-004 | Attachments served via presigned S3 URLs (separate origin)                       | Must     |
| ATT-005 | Inline-render allowlist (webp/webm/pdf); others download; SVG blocked/rasterized | Must     |
| ATT-006 | Private bucket; 25MB cap                                                         | Must     |

> No antivirus scanning in R1; mitigations above stand in. ClamAV (async, quarantine→scan→release) or AWS GuardDuty S3 malware scan remain future options — confirm sufficiency with the ISO auditor at the next surveillance audit.

## 10. Observability

- OTel traces, metrics, logs → Grafana.
- `/health` (liveness) and `/ready` (readiness) endpoints.
- **Self-monitoring recursion guard:** this app's own alerts flow Grafana → webhook → incident _in this app_. If the app is down it cannot create an incident about itself. Therefore Grafana alerts for the ITSM app itself must **also** notify Mattermost directly, bypassing the app.

| ID      | Requirement                                                                                        | Priority |
| ------- | -------------------------------------------------------------------------------------------------- | -------- |
| OBS-001 | OTel traces/metrics/logs exported to Grafana                                                       | Must     |
| OBS-002 | `/health` and `/ready` endpoints                                                                   | Must     |
| OBS-003 | Alerts about the ITSM app itself notify Mattermost directly, not only via the in-app incident path | Must     |

## 11. Environments & Delivery

- Deliverable artifact: an **OCI image**. Runtime starts as Docker Compose on EC2; Kubernetes is a later option with no code change (12-factor: all config via environment).
- Environments: **dev** (docker-compose, single-command up), **staging**, **prod**.
- Drizzle migrations, forward-only, applied on deploy.
- Infra team owns OS and Postgres patching (self-managed EC2).
- SPA hosted as static assets on **S3 + CloudFront**.
- CI pipeline (**GitLab CI**): lint, typecheck, tests, dependency audit, image build.

| ID      | Requirement                                                       | Priority |
| ------- | ----------------------------------------------------------------- | -------- |
| ENV-001 | Single deliverable OCI image; config entirely via env (12-factor) | Must     |
| ENV-002 | dev / staging / prod environments; dev up with one command        | Must     |
| ENV-003 | Forward-only Drizzle migrations applied on deploy                 | Must     |
| ENV-004 | GitLab CI runs lint, typecheck, tests, dependency audit, image build | Must     |
| ENV-005 | SPA hosted as static assets on S3 + CloudFront                    | Must     |

## 12. Data Conventions

| ID       | Requirement                                                                        | Priority |
| -------- | ---------------------------------------------------------------------------------- | -------- |
| DATA-001 | UUIDv7 primary keys                                                                | Must     |
| DATA-002 | `created_at` / `updated_at` (timestamptz) on all tables                            | Must     |
| DATA-003 | Timestamps stored in UTC; displayed in Asia/Jakarta                                | Must     |
| DATA-004 | Business-hours calendars drive SLA math (see [08-sla-engine.md](08-sla-engine.md)) | Must     |
| DATA-005 | Soft-delete for ticket/CI data; never hard-deleted                                 | Must     |

## 13. Testing (AI-Builder Clause)

The builder is an AI agent; the spec is the contract. Verification must be mechanical.

| ID       | Requirement                                                                                     | Priority |
| -------- | ----------------------------------------------------------------------------------------------- | -------- |
| TEST-001 | Every requirement ID maps to at least one automated test                                        | Must     |
| TEST-002 | Integration tests run against a real Postgres in CI (not mocks-only)                            | Must     |
| TEST-003 | Playwright E2E for golden paths: login, create incident, SLA breach escalation, role assignment | Must     |

## 14. Open Questions

- [ ] How common are video attachments? If rare, drop server-side WebM transcoding and store video as-is (saves worker RAM/CPU).
- [ ] Secret manager choice (AWS Secrets Manager, SSM Parameter Store, Vault?) — **open**.

Resolved: audit retention **3 years**; repo + CI = **GitLab CI**; SPA hosting = **S3 + CloudFront**.
