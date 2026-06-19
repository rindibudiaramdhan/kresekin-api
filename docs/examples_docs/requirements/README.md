# Infokes ITSM — Requirements

Numbered specs for the Infokes ITSM + CMDB platform. Read alongside the ADRs in [`../adr/`](../adr/) and the design assets in [`../design/`](../design/). Every requirement carries an ID + MoSCoW priority.

| Doc | Scope | Release |
|---|---|---|
| [00 — Vision & Scope](00-vision-scope.md) | goals, non-goals, personas, build rationale | — |
| [01 — Architecture & NFR](01-architecture-nfr.md) | modular monolith, API, auth, audit, jobs, perf, DR, security, observability | R1 |
| [02 — Roles & Permissions](02-roles-permissions.md) | capability/scope model, roles, teams, RBAC, user lifecycle, SSO | R1 |
| [03 — Service Catalog](03-service-catalog.md) | services, modules, categories, catalog items, criticality | R1 |
| [04 — Incident Management](04-incident-mgmt.md) | incident lifecycle, priority, routing, major incidents, reliability timestamps | R1 |
| [05 — Service Request](05-service-request.md) | request forms, approval, fulfillment | R1 |
| [06 — Knowledge Management](06-knowledge-mgmt.md) | KB articles, search, known errors | R2 |
| [07 — Problem Management](07-problem-mgmt.md) | problems, RCA, workarounds, known errors | R2 |
| [08 — SLA Engine & Reliability](08-sla-engine.md) | SLA policies, clocks, calendars, breach; MTTD/MTTR/MTRS/MTBF metrics | R1 (engine) / R2 (dashboards) |
| [09 — CMDB](09-cmdb.md) | CI classes, relationships, impact analysis, import, cert monitoring | R3 |
| [10 — Change Enablement](10-change-enablement.md) | change types, approval, calendar, Change API, PIR | R3 |
| [11 — Integrations & Notifications](11-integrations.md) | Email/Mattermost, Grafana webhook, inbound email | R1 |
| [12 — Release Plan](12-release-plan.md) | R1/R2/R3 build order + exit criteria | — |
| [13 — Engineering Standards](13-engineering-standards.md) | monorepo, layering, SOLID, types, errors, testing, quality | — |
| [14 — Design Language](14-design-language.md) | palettes, tokens, typography, theming, a11y, brand, empty states | — |
| [99 — Glossary](99-glossary.md) | ITIL, metrics, technical, project terms | — |

## ADRs ([`../adr/`](../adr/))

001 build-vs-buy · 002 defer-multitenancy-with-guardrails · 003 defer-zoho-bridge · 004 notification-adapter-defer-whatsapp · 005 cmdb-production-only · 006 change-after-cmdb · 007 jit-provisioning-google-groups · 008 async-change-approval-no-cab · 009 tech-stack · 010 services-modules-as-cis · 011 catalog-product-services-only · 012 configuration-driven-design · 013 shared-work-item-base

## Start here

New to the project: read `00` → `01` → `02`, then `12` for the build order and `13`/`14` for how to build it. Begin implementation with **R1** per [12](12-release-plan.md).
