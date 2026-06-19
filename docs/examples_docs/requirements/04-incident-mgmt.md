# 04 — Incident Management

|              |                 |
| ------------ | --------------- |
| Status       | Draft           |
| Last updated | 2026-06-13      |
| Owner        | Iqbal (Infokes) |

Goal (ITIL 4): restore normal service operation as quickly as possible and minimize impact. This doc owns the Incident entity, its lifecycle, the **routing algorithm**, and major-incident handling. SLA timer mechanics live in [08-sla-engine.md](08-sla-engine.md); notification delivery in [11-integrations.md](11-integrations.md).

Several behaviors here are admin-configurable per [ADR-012](../adr/012-configuration-driven-design.md).

## 1. Incident Entity

The incident sits on the shared **work-item base** ([ADR-013](../adr/013-shared-work-item-base.md)) — `number`, `requester`, `channel`, `external_ref`, assignment, comments/work-notes, attachments, watchers, links, and audit are common with service requests. Incident-specific core fields (admins may add custom fields — see §16):

| Field                                 | Notes                                                                                                              |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `id`                                  | UUIDv7                                                                                                             |
| `number`                              | Human-friendly sequence, e.g. `INC-00042`                                                                          |
| `title`, `description`                |                                                                                                                    |
| `requester`                           | Requester entity ([ADR-002](../adr/002-defer-multitenancy-with-guardrails.md)); may be `system` for machine-origin |
| `affected_service`, `affected_module` | Service + optional Module ([03](03-service-catalog.md))                                                            |
| `category`, `subcategory`             | From configurable taxonomy                                                                                         |
| `impact`, `urgency`                   | Drive priority (§4)                                                                                                |
| `priority`                            | P0–P3, derived from matrix, overridable                                                                            |
| `state`                               | See §3                                                                                                             |
| `pending_reason`                      | When state = Pending                                                                                               |
| `assigned_group`, `assignee`          | Group + optional individual                                                                                        |
| `channel`                             | portal / email / api ([ADR-002](../adr/002-defer-multitenancy-with-guardrails.md))                                 |
| `external_ref`                        | Free text / URL — Zoho link ([ADR-003](../adr/003-defer-zoho-bridge.md))                                           |
| `resolution_code`, `resolution_notes` | On resolution                                                                                                      |
| `linked_*`                            | problem (R2), change (R3), KB, parent/child incidents                                                              |
| `fault_started_at` | When the fault began; nullable (auto from monitoring where possible, else estimated). Feeds MTTD/MTRS |
| `detected_at` | When detected — `= created_at` for portal/email, alert fire time for Grafana |
| `acknowledged_at` | When an agent took ownership; MTTA endpoint |
| `first_response_at` | First public reply to the requester; response-SLA endpoint |
| `service_impacting` | Boolean — does this incident impair a service (feeds MTBF/availability) |
| `impaired_from`, `impaired_to` | Service-impaired window, when `service_impacting` |
| timestamps | created, updated, resolved_at, closed_at |

Reliability-metric definitions (MTTD, MTTA, MTTR, MTRS, MTBF, availability) live in [08-sla-engine.md](08-sla-engine.md); this entity captures the source timestamps. Dashboards are R2; capture is R1.

| ID      | Requirement                                                         | Priority |
| ------- | ------------------------------------------------------------------- | -------- |
| INC-001 | Incident entity with the core fields above; human-friendly `number` | Must     |
| INC-002 | Requester may be a person or the `system` actor (machine-origin)    | Must     |
| INC-027 | Capture reliability timestamps: `fault_started_at` (nullable), `detected_at`, `acknowledged_at`, `first_response_at` | Must |
| INC-028 | Capture `service_impacting` flag + impaired window for MTBF/availability | Must |

## 2. Channels & Intake

- Channels: **portal**, **email**, **API** (Grafana webhook and future integrations) — one channel abstraction ([ADR-002](../adr/002-defer-multitenancy-with-guardrails.md)).
- Requester supplies title/description, affected service (+ module if known), and an **urgency** (their perception).
- `external_ref` lets an agent link a Zoho customer ticket.

| ID      | Requirement                                                      | Priority |
| ------- | ---------------------------------------------------------------- | -------- |
| INC-003 | Incidents accepted via portal, email, and API channels           | Must     |
| INC-004 | Requester provides affected service/module and urgency at intake | Must     |

## 3. Lifecycle & States

```
New ──▶ In Progress ──▶ Pending ──▶ Resolved ──▶ Closed
          ▲                │            │
          └────────────────┘            └──▶ Reopened ──▶ In Progress
Cancelled  (from any state before Resolved)
```

- **New** — created, not yet picked up / triaged.
- **In Progress** — actively being worked.
- **Pending** — waiting; `pending_reason` ∈ { awaiting-requester, awaiting-third-party, awaiting-change }.
- **Resolved** — fix applied; awaiting confirmation / auto-close.
- **Closed** — terminal.
- **Reopened** — from Resolved/Closed within the reopen window (§12); returns to In Progress.
- **Cancelled** — terminal; for invalid/duplicate incidents before resolution.

**SLA pause:** by default Pending pauses the SLA clock. Whether each Pending sub-reason pauses is **admin-configurable** ([ADR-012](../adr/012-configuration-driven-design.md)).

| ID      | Requirement                                                                                    | Priority |
| ------- | ---------------------------------------------------------------------------------------------- | -------- |
| INC-005 | Incident state machine as above, with enforced legal transitions                               | Must     |
| INC-006 | Pending carries a sub-reason; SLA-pause per sub-reason is admin-configurable (default: pauses) | Must     |
| INC-007 | Cancelled and Closed are terminal; Reopen allowed only within the window (§12)                 | Must     |

## 4. Priority

Priority derives from an **Impact × Urgency** matrix. Default:

| Impact ↓ / Urgency → | High | Medium | Low |
| -------------------- | ---- | ------ | --- |
| **High**             | P0   | P1     | P2  |
| **Medium**           | P1   | P2     | P3  |
| **Low**              | P2   | P3     | P3  |

**P0** Critical · **P1** High · **P2** Medium · **P3** Low.

- The matrix is **admin-configurable** ([ADR-012](../adr/012-configuration-driven-design.md)).
- Priority is pre-seeded using the affected service's criticality tier ([03 §6](03-service-catalog.md)) + requester urgency; the triaging agent sets impact and may override the final priority.
- SLA targets attach per **priority × service** ([08](08-sla-engine.md)).

| ID      | Requirement                                                                | Priority |
| ------- | -------------------------------------------------------------------------- | -------- |
| INC-008 | Priority derived from a configurable Impact × Urgency matrix; levels P0–P3 | Must     |
| INC-009 | Priority pre-seeded from service criticality + urgency; agent-overridable  | Must     |

## 5. Categorization

- `category` / `subcategory` from an admin-configurable taxonomy ([ADR-012](../adr/012-configuration-driven-design.md)).
- Used for routing (category teams), reporting, and KB matching.

**Seed taxonomy (default, editable):**

| Category | Subcategories |
|---|---|
| Access & Authentication | login, permissions, account, SSO |
| Availability / Outage | service down, partial outage, degraded |
| Performance | slowness, timeout, high latency |
| Functional Bug / Defect | wrong behavior, error message, incorrect data |
| Data | inconsistency, missing data, reporting/export |
| Integration | API failure, third-party, webhook |
| Configuration | settings, feature flag, deploy config |
| Infrastructure | server, database, network, storage, certificate |
| Security | vulnerability, suspicious activity, access issue |
| How-to / Question | info request, usage guidance |

| ID      | Requirement                                | Priority |
| ------- | ------------------------------------------ | -------- |
| INC-010 | Configurable category/subcategory taxonomy | Must     |

## 6. Routing

**Default intake mode: L1 triage-first** — new incidents land in the L1 (Service Desk) triage queue; L1 categorizes, sets priority, and routes. Intake mode is **admin-configurable per service**: a service may be set to **auto-route** on submit, skipping L1 ([ADR-012](../adr/012-configuration-driven-design.md)).

Routing resolution (applied by L1 at triage, or automatically when auto-route is on):

1. **Module specified** → `Module.maintained_by_team`.
2. **Service specified, no Module** → `Service.default_team` if set.
3. **Category-based** (infra / non-product) → category → team mapping.
4. **Otherwise** → remains in the L1 triage queue.

| ID      | Requirement                                                                | Priority |
| ------- | -------------------------------------------------------------------------- | -------- |
| INC-011 | Default L1 triage-first intake; per-service auto-route configurable        | Must     |
| INC-012 | Routing resolves via module → service default → category → triage fallback | Must     |

## 7. Assignment

Group-then-individual, both push and pull ([02 §7](02-roles-permissions.md)):

- **Pull:** a group member self-assigns from the group queue.
- **Push:** Group Lead / Incident Manager / Admin assigns to a member.

| ID      | Requirement                                                                | Priority |
| ------- | -------------------------------------------------------------------------- | -------- |
| INC-013 | Incident assigned to a group, then optionally to an individual (push/pull) | Must     |

## 8. Escalation

- **Functional escalation:** hand to a higher tier or different group when the current owner can't resolve (L1→L2→L3). Manual; SLA-timer breach risk may prompt it.
- **Hierarchic escalation:** notify Group Lead / Incident Manager on SLA breach risk or P0. Timer-driven; thresholds defined with SLA ([08](08-sla-engine.md)).

| ID      | Requirement                                                                           | Priority |
| ------- | ------------------------------------------------------------------------------------- | -------- |
| INC-014 | Functional escalation (tier/group) and hierarchic escalation (lead/manager) supported | Must     |

## 9. Work Notes vs Public Comments

- **Work notes:** internal, visible to agents only.
- **Public comments:** visible to the requester; drive requester notifications.

| ID      | Requirement                                                        | Priority |
| ------- | ------------------------------------------------------------------ | -------- |
| INC-015 | Distinct internal work notes and requester-visible public comments | Must     |

## 10. Major Incident

- A **P0** auto-flags the incident as a **major-incident candidate**.
- The **Incident Manager declares** it major (manual gate) — declaration is not automatic.
- Major incident enables: **parent-child linking** (related incidents attach as children), dedicated comms/notifications, and a maintained timeline/log.
- The Incident Manager drives resolution and communications.

| ID      | Requirement                                                               | Priority |
| ------- | ------------------------------------------------------------------------- | -------- |
| INC-016 | P0 auto-flags a major-incident candidate; Incident Manager declares major | Must     |
| INC-017 | Major incidents support parent-child linking and a maintained timeline    | Must     |

## 11. Resolution

- Requires `resolution_code` (from a configurable set) + `resolution_notes`.
- Moves the incident to **Resolved**; starts the auto-close timer (§12).

**Seed resolution codes (default, editable):** Fixed — permanent · Fixed — workaround applied · Configuration change · User error / guidance given · No fault found / not reproducible · Duplicate · Withdrawn by requester · Third-party / vendor resolved · Known error (linked to problem).

| ID      | Requirement                                     | Priority |
| ------- | ----------------------------------------------- | -------- |
| INC-018 | Resolution requires a resolution code and notes | Must     |

## 12. Closure & Reopen

- An incident in **Resolved** **auto-closes after N business days** (configurable, **default 3**) if not reopened ([ADR-012](../adr/012-configuration-driven-design.md)). The window counts **business days on the company default calendar** ([08](08-sla-engine.md)), independent of the incident's SLA clock basis.
- The **requester or an agent** may **reopen within the window** → back to In Progress (agents reopen on recurrence).
- After the window, reopening is not allowed; a **new incident is created and linked** to the old one.

| ID      | Requirement                                                                  | Priority |
| ------- | ---------------------------------------------------------------------------- | -------- |
| INC-019 | Auto-close after a configurable window (default 3 business days, company default calendar) in Resolved | Must     |
| INC-020 | Requester or agent may reopen within window; afterward a new linked incident is required | Must     |

## 13. Linking

- Link to: **Problem** (R2), **Change** (R3), **KB article**, **parent/child incidents**, and **`external_ref`** (Zoho).

| ID      | Requirement                                                                                 | Priority |
| ------- | ------------------------------------------------------------------------------------------- | -------- |
| INC-021 | Incidents link to problems, changes, KB articles, parent/child incidents, and external refs | Must     |

## 14. Grafana Webhook Incidents

- Alerts arrive on `/webhooks/grafana` (token-auth, rate-limited — [01 §2](01-architecture-nfr.md)), authored by the `system` actor.
- **Dedup:** an alert fingerprint maps to an existing open incident rather than creating duplicates on re-fire.
- **Mapping:** alert labels → affected service/module; **severity → priority** via a configurable map ([ADR-012](../adr/012-configuration-driven-design.md)). Mapped alerts route directly; unmapped alerts go to the L1 triage queue.
- **Auto-resolve (optional, configurable):** when the alert clears, the linked incident may auto-resolve.
- Self-monitoring guard: alerts about the ITSM app itself also notify Mattermost directly ([01 §10](01-architecture-nfr.md)).

| ID      | Requirement                                                                                   | Priority |
| ------- | --------------------------------------------------------------------------------------------- | -------- |
| INC-022 | Grafana webhook creates incidents as `system`, deduped by alert fingerprint                   | Must     |
| INC-023 | Alert labels map to service/module; severity→priority via configurable map; unmapped → triage | Must     |
| INC-024 | Optional configurable auto-resolve when the alert clears                                      | Should   |

## 15. Notifications

Events that notify (delivery via Email + Mattermost — [11](11-integrations.md)):

- Assignment to a group / individual.
- State change (esp. Resolved, Closed).
- Public comment added.
- SLA breach warning / breach ([08](08-sla-engine.md)).
- Major incident declared / updated.

| ID      | Requirement                                                                                  | Priority |
| ------- | -------------------------------------------------------------------------------------------- | -------- |
| INC-025 | Notifications on assignment, state change, public comment, SLA breach, major-incident events | Must     |

## 16. Custom Fields & Configurability

Admin-configurable surfaces for incidents ([ADR-012](../adr/012-configuration-driven-design.md)):

- **Custom fields** — add fields beyond the core schema (types: text, number, select, date, etc.; optional/required; global or per-service).
- Priority matrix, category taxonomy, Pending→SLA-pause, intake mode, auto-close/reopen windows, Grafana severity map, resolution codes.

| ID      | Requirement                                                                                | Priority |
| ------- | ------------------------------------------------------------------------------------------ | -------- |
| INC-026 | Admins can define custom incident fields (typed, optional/required, global or per-service) | Must     |

## 17. Audit Requirements

Feeds the [01 §5](01-architecture-nfr.md) audit log:

- Create, every state transition, reassignment (group/individual).
- Priority/impact/urgency change.
- Resolution and closure (incl. auto-close).
- Major-incident declaration.
- Public comment and work note addition.

## 18. Open Questions

_None outstanding._

Resolved: seed resolution codes (§11) and category taxonomy (§5); email-reply threading by `[INC-xxxxx]` token ([11 §8](11-integrations.md)); agents may reopen within the window (§12); auto-close counts business days on the company default calendar (§12).
