# 03 — Service Catalog

|              |                 |
| ------------ | --------------- |
| Status       | Draft           |
| Last updated | 2026-06-13      |
| Owner        | Iqbal (Infokes) |

Defines the **catalog** — the taxonomy of services and the menu of requestable items. This doc owns the entities; **routing** is defined in [04-incident-mgmt.md](04-incident-mgmt.md), **request forms/workflow/approval** in [05-service-request.md](05-service-request.md), and **SLA mechanics** in [08-sla-engine.md](08-sla-engine.md). This doc fixes only _where_ SLA attaches.

## 1. Overview — Two Catalogs, One Structure

| Catalog             | Purpose                                                                                      | Consumed by                |
| ------------------- | -------------------------------------------------------------------------------------------- | -------------------------- |
| **Service catalog** | What can break — incidents are filed _against_ a Service (+ Module). Drives routing and SLA. | Incident Management        |
| **Request catalog** | Orderable items — requests are raised _from_ a catalog item. Drives request workflow.        | Service Request Management |

**R1 scope: product services only** (eClinic, Payment Gateway, …). Internal/workplace IT services (email, VPN, accounts, laptop support) are deferred — see [ADR-011](../adr/011-catalog-product-services-only.md). The request catalog is therefore product/infra-oriented in R1 (e.g. staging access, provision a VM), not workplace IT (e.g. new laptop). The taxonomy does not preclude adding workplace IT services later.

## 2. Service

A customer-facing/product service — the top-level catalog entry. Per [ADR-010](../adr/010-services-modules-as-cis.md), a Service becomes a business-service CI in R3.

| Field                 | Notes                                                                                                           |
| --------------------- | --------------------------------------------------------------------------------------------------------------- |
| `id`                  | UUIDv7, stable across R1→R3                                                                                     |
| `name`, `description` |                                                                                                                 |
| `service_owner`       | User ref; one Service Owner per service                                                                         |
| `criticality_tier`    | Tier 1 / 2 / 3 (see §6)                                                                                         |
| `default_team`        | Optional. Assignment group used when no Module is specified; if unset, such incidents go to the L1 triage queue |
| `lifecycle_state`     | draft / active / retired (see §8)                                                                               |

A Service has many Modules.

| ID      | Requirement                                                                           | Priority |
| ------- | ------------------------------------------------------------------------------------- | -------- |
| CAT-001 | Service entity with owner, criticality tier, default team, lifecycle state, stable ID | Must     |
| CAT-002 | A Service has exactly one Service Owner                                               | Must     |

## 3. Module

A component of a Service (e.g. eClinic–Registration). The routing key. Per [ADR-010](../adr/010-services-modules-as-cis.md), a Module becomes an application-service CI in R3.

| Field                 | Notes                                                |
| --------------------- | ---------------------------------------------------- |
| `id`                  | UUIDv7, stable across R1→R3                          |
| `name`, `description` |                                                      |
| `service_id`          | Parent Service (exactly one)                         |
| `maintained_by_team`  | Assignment group — **the routing target** (required) |
| `lifecycle_state`     | draft / active / retired                             |

| ID      | Requirement                                                                                 | Priority |
| ------- | ------------------------------------------------------------------------------------------- | -------- |
| CAT-003 | Module belongs to exactly one Service and is maintained by exactly one Team                 | Must     |
| CAT-004 | A Service with no Modules routes via its `default_team` if set, else to the L1 triage queue | Must     |

## 4. Category

Browse grouping, primarily for the request catalog (e.g. "Access", "Environments", "Infrastructure").

| Field             | Notes                             |
| ----------------- | --------------------------------- |
| `id`, `name`      |                                   |
| `parent_category` | Optional, for a shallow hierarchy |

| ID      | Requirement                                       | Priority |
| ------- | ------------------------------------------------- | -------- |
| CAT-005 | Category entity with optional single-level parent | Should   |

## 5. Catalog (Request) Item

A requestable item in the request catalog. The item _definition_ lives here; its form schema, approval flow, and fulfillment process live in [05-service-request.md](05-service-request.md).

| Field                       | Notes                                                                                 |
| --------------------------- | ------------------------------------------------------------------------------------- |
| `id`, `name`, `description` |                                                                                       |
| `category_id`               |                                                                                       |
| `linked_service`            | Optional — the Service this item relates to (e.g. "eClinic staging access" → eClinic) |
| `eligibility`               | `all` or restricted to a set of roles/groups (see §7)                                 |
| `fulfillment_team`          | Assignment group that fulfills the request                                            |
| `sla_target`                | Fulfillment SLA reference (mechanics in 08)                                           |
| `form_ref`                  | Reference to the request form (defined in 05)                                         |
| `approval_flow_ref`         | Reference to approval flow, if any (defined in 05)                                    |
| `lifecycle_state`           | draft / active / retired                                                              |

| ID      | Requirement                                                                                         | Priority |
| ------- | --------------------------------------------------------------------------------------------------- | -------- |
| CAT-006 | Catalog item with category, eligibility, fulfillment team, SLA, form/approval references, lifecycle | Must     |
| CAT-007 | Catalog item may optionally link to a Service                                                       | Should   |

## 6. Criticality Tiers

Drives default priority and SLA targets (consumed by 08).

| Tier   | Meaning                                                     | Example                            |
| ------ | ----------------------------------------------------------- | ---------------------------------- |
| Tier 1 | Mission-critical; outage has major business/customer impact | eClinic production                 |
| Tier 2 | Important; degraded but not catastrophic                    | internal-facing product tooling    |
| Tier 3 | Low impact                                                  | non-critical/experimental services |

| ID      | Requirement                                | Priority |
| ------- | ------------------------------------------ | -------- |
| CAT-008 | Every Service has a criticality tier (1–3) | Must     |

## 7. Eligibility & Visibility

- **Service catalog** (incident reporting): visible to all internal users — anyone can report an incident against any active service.
- **Request catalog items:** each item is either `all` or restricted to specific roles/groups. Restricted items are hidden from and unrequestable by ineligible users (enforced in queries, not only UI). Example: "provision production VM" → engineers only.

| ID      | Requirement                                                                            | Priority |
| ------- | -------------------------------------------------------------------------------------- | -------- |
| CAT-009 | Active services are visible to all internal users for incident reporting               | Must     |
| CAT-010 | Request items support `all` or role/group-restricted eligibility, enforced server-side | Must     |

## 8. Lifecycle & States

```
draft ──▶ active ──▶ retired
```

- **draft** — being defined; not visible in the catalog; no tickets.
- **active** — live; visible; accepts tickets.
- **retired** — no new tickets accepted; existing tickets continue to closure; history preserved.

| ID      | Requirement                                                                        | Priority |
| ------- | ---------------------------------------------------------------------------------- | -------- |
| CAT-011 | Service/Module/Item lifecycle: draft → active → retired                            | Must     |
| CAT-012 | Retired entities accept no new tickets; existing tickets and history are preserved | Must     |

## 9. Ownership & Governance

- **Admin** manages the catalog structure: create/retire Services and Modules, create categories, define request items.
- **Service Owner** edits their own service's entry and its SLA targets (per [02 §4](02-roles-permissions.md)).
- All catalog changes are audited.

| ID      | Requirement                                                                  | Priority |
| ------- | ---------------------------------------------------------------------------- | -------- |
| CAT-013 | Admin manages catalog structure; Service Owner edits own service entry + SLA | Must     |

## 10. Relationship to CMDB (R3)

Per [ADR-010](../adr/010-services-modules-as-cis.md): in R3, Service → business-service CI and Module → application-service CI; the CMDB becomes the single source of truth and the catalog references those CIs. R1 keeps Service/Module as catalog entities with stable IDs so no rekeying is needed at R3. No coupling in R1.

## 11. Audit Requirements

Feeds the [01 §5](01-architecture-nfr.md) audit log:

- Service / Module / catalog item create, edit, retire.
- SLA target change.
- Eligibility change.
- Criticality tier change.

## 12. Open Questions

- [ ] Internal/workplace IT services (deferred per [ADR-011](../adr/011-catalog-product-services-only.md)) — no scheduled date; revisit post-R1.

Resolved: retirement allows existing tickets to drain to closure, blocks new (§8); Category is optional for services too (not request-items-only); default priority-from-tier mapping lives in [08](08-sla-engine.md).
