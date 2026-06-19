# 07 — Problem Management

| | |
|---|---|
| Status | Draft (R2) |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

Goal (ITIL 4): reduce the likelihood and impact of incidents by finding and addressing their underlying causes, and by managing known errors and workarounds. Ships in **R2**, alongside Knowledge Management ([06](06-knowledge-mgmt.md)). A Problem sits on the shared work-item base ([ADR-013](../adr/013-shared-work-item-base.md)).

## 1. Problem Entity

On the shared work-item base (`number`, assignment, comments/work-notes, attachments, watchers, links, audit), plus problem-specific fields:

| Field | Notes |
|---|---|
| `number` | e.g. `PRB-00007` |
| `title`, `description` | |
| `status` | See §2 |
| `pending_reason` | When status = Pending |
| `affected_service`, `affected_module` | ([03](03-service-catalog.md)); CI in R3 |
| `impact`, `priority` | Prioritized by frequency × impact of linked incidents |
| `linked_incidents[]` | Many incidents → one problem |
| `rca_notes`, `rca_method` | Root-cause analysis (§4) |
| `root_cause` | Identified cause |
| `workaround` | Temporary mitigation (§5) |
| `is_known_error` | Set when root cause + workaround documented (§6) |
| `linked_kb_article` | Known-error article ([06](06-knowledge-mgmt.md)) |
| `linked_change` | Permanent fix (R3, [10](10-change-enablement.md)) |
| `resolution_notes` | Permanent fix description |
| timestamps | created, updated, resolved_at, closed_at |

| ID | Requirement | Priority |
|---|---|---|
| PRB-001 | Problem entity on the shared work-item base + problem-specific fields | Must |
| PRB-002 | A problem links many incidents; priority reflects frequency × impact | Must |

## 2. Lifecycle & States

```
Logged ──▶ Under Investigation ──▶ Known Error ──▶ Resolved ──▶ Closed
                    │                                   
                 Pending                Cancelled (pre-resolution)
```

- **Logged** — recorded; not yet investigated.
- **Under Investigation** — RCA in progress (§4).
- **Known Error** — root cause + workaround documented (§6).
- **Resolved** — permanent fix applied. In R2 the fix is tracked manually; in R3 it links to a Change ([10](10-change-enablement.md)).
- **Closed** — terminal.
- **Pending** — awaiting input; pauses any problem-level timers.
- **Cancelled** — terminal; invalid/duplicate before resolution.

| ID | Requirement | Priority |
|---|---|---|
| PRB-003 | Problem state machine as above with enforced legal transitions | Must |
| PRB-004 | Resolved tracks fix manually in R2; links to a Change in R3 | Must |

## 3. Detection

- **Reactive:** created from recurring/related incidents — "create problem from incident" or link an existing problem.
- **Proactive:** Problem Manager spots trends. R2 provides **basic trend reports** (top recurring categories/services/CIs from incident history); automated detection is deferred.

| ID | Requirement | Priority |
|---|---|---|
| PRB-005 | Reactive creation from incidents (create-from / link) | Must |
| PRB-006 | Basic trend reports for proactive identification; automated detection deferred | Should |

## 4. Root-Cause Analysis

- **Freeform `rca_notes`** plus an `rca_method` tag (e.g. 5-whys, fishbone/Ishikawa, none).
- Structured method templates (guided 5-whys, etc.) are optional.

| ID | Requirement | Priority |
|---|---|---|
| PRB-007 | Freeform RCA notes with a method tag | Must |
| PRB-008 | Structured RCA method templates | Should |

## 5. Workaround

- A documented workaround is **surfaced on all linked open incidents** so agents can apply it immediately.
- Updating the workaround propagates to currently linked incidents.

| ID | Requirement | Priority |
|---|---|---|
| PRB-009 | Workaround surfaced on linked open incidents; updates propagate | Must |

## 6. Known Error

- When root cause + workaround are documented, the problem is flagged `is_known_error` and moves to (or is eligible for) **Known Error**.
- A **known-error KB article** can be published from the problem (create-from-problem), linked both ways ([06](06-knowledge-mgmt.md)).

| ID | Requirement | Priority |
|---|---|---|
| PRB-010 | Known-error flag; publish a linked known-error KB article from the problem | Must |

## 7. Linking

- **Incident ↔ Problem** (many-to-one), **Problem → KB** (known error), **Problem → Change** (R3), **Problem → Service/Module/CI**.

| ID | Requirement | Priority |
|---|---|---|
| PRB-011 | Link problems to incidents, KB articles, changes (R3), and services | Must |

## 8. Permissions

Extends the reserved Problem Manager role ([02 §3](02-roles-permissions.md)):

- `problem:manage` — create/triage/transition/close problems: **Problem Manager**.
- `problem:investigate` — contribute RCA, workaround, work notes: Problem Manager, **L3 Technical Specialist** (and L2 as needed).
- Read: agent roles (per [02 §6](02-roles-permissions.md) all-tickets visibility).

| ID | Requirement | Priority |
|---|---|---|
| PRB-012 | Problem Manager manages; L2/L3 contribute investigation | Must |

## 9. Configurability

Admin-configurable ([ADR-012](../adr/012-configuration-driven-design.md)): RCA method list, problem categories, trend-report parameters.

## 10. Audit Requirements

Feeds the [01 §5](01-architecture-nfr.md) audit log:

- Create, every state transition.
- RCA / root-cause / workaround edits.
- Known-error flagging and KB publication.
- Incident/change/KB linking.

## 11. Open Questions

_None outstanding._ Resolved: no formal problem SLA in R2 — track time-to-known-error as a metric (no breach); auto-suggest-problem deferred (manual identification in R2); resolving a problem **prompts the agent to review** its linked open incidents (no auto-close).
