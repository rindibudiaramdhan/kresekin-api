# 12 — Release Plan

| | |
|---|---|
| Status | Draft |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

Consolidates scope into three releases with build order and exit criteria. The builder is an AI agent and the reviewer is a single person ([00 §7](00-vision-scope.md)); releases are sized to be reviewable in increments. No calendar dates are committed here — sequencing and dependencies only.

## Release Overview

| Release | Scope | Docs |
|---|---|---|
| **R1** | Auth/SSO, users/roles/groups, catalog, incident, service request, SLA engine, notifications, Grafana webhook, inbound email, audit trail, reliability-timestamp capture | 01, 02, 03, 04, 05, 08, 11 |
| **R2** | Knowledge management, problem management, SLA & reliability dashboards/reporting | 06, 07, 08 (dashboards) |
| **R3** | CMDB (incl. Service/Module→CI migration), Change Enablement (incl. Change API) | 09, 10 |

Ordering rationale: Change after CMDB ([ADR-006](../adr/006-change-after-cmdb.md)); Problem after incident history exists; dashboards after the data they aggregate is captured.

## R1 — Foundation & Core ITSM

**Build order (dependency-first):**

1. **Foundation** — project skeleton, Postgres + Drizzle, **audit log** ([01 §5](01-architecture-nfr.md)), Google OIDC + sessions, RBAC mechanism, users/roles/**assignment groups** ([02](02-roles-permissions.md)), **shared work-item base** ([ADR-013](../adr/013-shared-work-item-base.md)).
2. **Catalog** — services, modules, categories, request items ([03](03-service-catalog.md)).
3. **SLA engine** — policies, calendars, clocks, breach jobs ([08](08-sla-engine.md)) — needed by incident & request.
4. **Incident** — entity, lifecycle, routing, major incidents, reliability timestamps ([04](04-incident-mgmt.md)).
5. **Service request** — forms, approval, fulfillment ([05](05-service-request.md)).
6. **Notifications** — Email + Mattermost adapters, preferences, templates, watchers ([11 §1–6](11-integrations.md)).
7. **Integrations** — Grafana webhook, inbound email ([11 §7–8](11-integrations.md)).

**Exit criteria:**

- [ ] Incidents and service requests fully tracked; Google Sheets retired (**G1**).
- [ ] Every incident has an SLA target; breaches detected and escalated (**G2**).
- [ ] Reliability timestamps captured on every incident; MTTR baseline computable (**G3**).
- [ ] Audit trail covers all R1 mutations, auth, and config changes.
- [ ] Notifications delivering on Email + Mattermost.
- [ ] Golden-path E2E tests green ([01 §13](01-architecture-nfr.md)).

## R2 — Knowledge, Problem, Reporting

**Build order:**

1. **Knowledge** — KB articles, FTS, suggest-on-triage, audience ([06](06-knowledge-mgmt.md)).
2. **Problem** — RCA, workaround propagation, known error → KB ([07](07-problem-mgmt.md)).
3. **Dashboards & reporting** — MTTD/MTTA/MTTR/MTRS/MTBF/availability/FCR/reopen, SLA compliance ([08 §6](08-sla-engine.md)).

**Exit criteria:**

- [ ] KB searchable; relevant articles suggested during triage; self-service audience live.
- [ ] Problems link incidents; known errors publish to KB; workarounds surface on linked incidents.
- [ ] Reliability + SLA dashboards live with service/module/team/priority/time-range breakdowns.

## R3 — CMDB & Change

**Build order:**

1. **CMDB** — classes, CIs, relationships, impact analysis, import, cert monitoring ([09](09-cmdb.md)).
2. **Service/Module → CI migration** ([ADR-010](../adr/010-services-modules-as-cis.md), [09 §8](09-cmdb.md)).
3. **Change Enablement** — types, approval (reused SR engine), calendar/freeze, Change API, PIR ([10](10-change-enablement.md)).
4. **Change failure rate** metric ([08 §6](08-sla-engine.md), [10 §9](10-change-enablement.md)).

**Exit criteria:**

- [ ] Production CI inventory ≥ 95% accurate, quarterly verification running (**G4**).
- [ ] Every production infra change has a change record (**G5**).
- [ ] "What changed before this incident?" answerable in < 1 min via CMDB + change history (**G6**).
- [ ] Catalog references service CIs post-migration.

## Cross-Release Dependencies

| Capability | Depends on |
|---|---|
| Incident / Request SLA | SLA engine (R1) |
| Problem (reactive) | Incident history (R1) |
| Reliability dashboards (R2) | Timestamp capture (R1) |
| Change impact/risk | CMDB relationships (R3, before Change) |
| Change failure rate | Change outcomes (R3) |
| Catalog → service CIs | CMDB migration (R3) |

## Assumptions & Open Questions

- **Build model:** solo — Iqbal (reviewer) + AI agent (builder). **No committed dates**; releases are **paced by review throughput**, sized as small reviewable increments.
- R2 dashboards **compute from operational tables** at this scale (no dedicated reporting store); revisit only if query performance demands it.
- [ ] Workplace-IT-services expansion ([ADR-011](../adr/011-catalog-product-services-only.md)) — no scheduled date; slot into a release when revisited.
