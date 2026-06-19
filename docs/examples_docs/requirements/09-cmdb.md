# 09 — CMDB (Service Configuration Management)

| | |
|---|---|
| Status | Draft (R3) |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

Goal (ITIL 4): maintain accurate information about configuration items (CIs) and their relationships, to enable impact analysis for incidents and changes. Ships in **R3**, before Change Enablement ([ADR-006](../adr/006-change-after-cmdb.md)). Covers **production assets only** ([ADR-005](../adr/005-cmdb-production-only.md)). Behaviors are admin-configurable per [ADR-012](../adr/012-configuration-driven-design.md).

## 1. CI Class Model

CI classes are **admin-definable** with typed attribute schemas, shipped with a **seed set**:

- **business-service** (← catalog Service, §8), **application-service** (← catalog Module, §8)
- **server/VM**, **database**, **network-device**, **load-balancer**
- **TLS-certificate**, **external-SaaS-dependency**

A CI Class defines:

| Field | Notes |
|---|---|
| `name` | |
| `attributes[]` | each: `key`, `label`, `type` (text/number/date/bool/enum/reference), `required`, `is_identifier` |
| `identifier` | one or more `is_identifier` attributes used for dedup/reconciliation (e.g. server FQDN; cert domain+issuer) |

| ID | Requirement | Priority |
|---|---|---|
| CMDB-001 | Admin-definable CI classes with typed attribute schemas; seed set shipped | Must |
| CMDB-002 | Each class declares identifier attribute(s) for dedup/reconciliation | Must |

## 2. CI Entity

| Field | Notes |
|---|---|
| `id` | UUIDv7 |
| `class` | CI Class (§1) |
| `name` | |
| `attributes` | per-class schema (structured) |
| `lifecycle_state` | planned / active / in-maintenance / retired (§3) |
| `owner` | user or team |
| `environment` | production ([ADR-005](../adr/005-cmdb-production-only.md)) |
| `source` | manual / import / api |
| `last_verified_at` | verification cycle (§9) |
| timestamps | created, updated |

| ID | Requirement | Priority |
|---|---|---|
| CMDB-003 | CI entity with class, structured attributes, lifecycle, owner, source, last_verified_at | Must |

## 3. CI Lifecycle

```
planned ──▶ active ──▶ in-maintenance ──▶ active ──▶ retired
```

Retired CIs are excluded from active impact analysis but retained for history.

| ID | Requirement | Priority |
|---|---|---|
| CMDB-004 | CI lifecycle planned→active→in-maintenance→retired; retired kept for history | Must |

## 4. Relationships

- Typed, **directional** edges forming the dependency graph.
- **Seed types** (each with an inverse): `depends-on` / `depended-on-by`, `runs-on` / `runs`, `hosted-on` / `hosts`, `connects-to` / `connected-from`, `member-of` / `has-member`.
- Admins may add relationship types (name + inverse).
- An edge is stored once; **the inverse is always navigable in the graph** — traversal works in both directions automatically.

| ID | Requirement | Priority |
|---|---|---|
| CMDB-005 | Directional typed relationships with inverse labels; admin-extensible; seed set shipped | Must |
| CMDB-006 | Each edge stored once; inverse always traversable in the graph | Must |

## 5. Impact Analysis

- From any CI, traverse:
  - **Downward (dependencies):** what this CI relies on.
  - **Upward (dependents/impact):** what relies on this CI (via inverse).
- **Depth-limited** (configurable). Returns affected CIs and the business/application services they roll up to.
- Consumed by Incident (show underlying/affected CIs) and Change (risk/impact of a change, [10](10-change-enablement.md)).

| ID | Requirement | Priority |
|---|---|---|
| CMDB-007 | Bidirectional, depth-limited impact traversal returning affected CIs and services | Must |
| CMDB-008 | Impact analysis surfaced in incident and change workflows | Must |

## 6. Population & Import

- **Manual** CRUD on CIs and relationships.
- **CSV import:** column → attribute mapping; **upsert with dedup** by class identifier (§1).
- **API:** create/update CIs and relationships (for future automated feeds).
- **Automated discovery** (agents, network scan, cloud-provider APIs) is **deferred**.

| ID | Requirement | Priority |
|---|---|---|
| CMDB-009 | Manual CRUD, CSV import (upsert by identifier), and API population | Must |
| CMDB-010 | Reconciliation/dedup by class identifier on import/API | Must |
| CMDB-011 | Automated discovery deferred | Won't (R3) |

## 7. Certificate & Expiry Monitoring

- The TLS-certificate (and domain) classes carry an expiry attribute.
- A recurring job checks expiry against configurable thresholds (e.g. 30/14/7 days) → notify owner; **optional auto-incident** on imminent/expired.

| ID | Requirement | Priority |
|---|---|---|
| CMDB-012 | Recurring expiry checks with configurable thresholds; notify owner; optional auto-incident | Should |

## 8. Service / Module Reconciliation (Migration)

Per [ADR-010](../adr/010-services-modules-as-cis.md), at R3:

- Catalog **Services → business-service CIs**, **Modules → application-service CIs**, preserving stable IDs and links.
- The CMDB becomes the single source of truth; the catalog references these CIs. Routing (`Module.maintained_by_team`, [03](03-service-catalog.md)) and Service Owner are preserved.
- Going forward, creating a service/module creates its corresponding CI.

| ID | Requirement | Priority |
|---|---|---|
| CMDB-013 | Migrate catalog Services/Modules to business/application-service CIs, preserving IDs and links | Must |
| CMDB-014 | Catalog references service CIs post-migration; new services/modules create CIs | Must |

## 9. Verification & Audit Cycle

- Each CI carries `last_verified_at`; a **configurable verification interval** (default quarterly) drives staleness.
- Overdue CIs are flagged and surfaced to the **CMDB Owner**.
- Supports goal **G4** ([00 §3](00-vision-scope.md): ≥95% CI accuracy, quarterly audit).

| ID | Requirement | Priority |
|---|---|---|
| CMDB-015 | last_verified_at with configurable verification interval; stale CIs flagged to CMDB Owner | Must |

## 10. Linking

- **CI ↔ incident** (affected/underlying CI), **CI ↔ problem**, **CI ↔ change** ([10](10-change-enablement.md)).

| ID | Requirement | Priority |
|---|---|---|
| CMDB-016 | Link CIs to incidents, problems, and changes | Must |

## 11. Permissions

Extends the reserved CMDB Owner role ([02 §3](02-roles-permissions.md)):

- `cmdb:manage` — CRUD CIs/relationships, import, class & relationship-type definition: **CMDB Owner** (class/type definition shared with Admin).
- `cmdb:read` — agent roles, for impact analysis during incidents/changes.

| ID | Requirement | Priority |
|---|---|---|
| CMDB-017 | CMDB Owner manages CIs/classes/relationships; agents read for impact analysis | Must |

## 12. Configurability

Admin-configurable ([ADR-012](../adr/012-configuration-driven-design.md)): CI classes + attribute schemas, relationship types, impact-traversal depth limit, verification interval, certificate warning thresholds.

## 13. Audit Requirements

Feeds the [01 §5](01-architecture-nfr.md) audit log:

- CI create/update/retire and attribute changes (before/after).
- Relationship add/remove.
- Class and attribute-schema changes; relationship-type changes.
- Import runs (summary + per-record outcome).
- Verification updates.

## 14. Open Questions

_None outstanding._ Resolved: CI classes are flat in R3 (no inheritance); CI attribute history via audit-log reconstruction (no point-in-time snapshots); non-production/staging CIs out (production-only per [ADR-005](../adr/005-cmdb-production-only.md)); AWS-native import deferred (CSV/API covers R3).
