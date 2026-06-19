# 10 — Change Enablement

| | |
|---|---|
| Status | Draft (R3) |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

Goal (ITIL 4): maximize the number of successful changes by ensuring risks are assessed, changes are authorized, and a change schedule is managed. Ships in **R3**, after the CMDB ([ADR-006](../adr/006-change-after-cmdb.md)), because impact assessment relies on CI relationships. Approval is asynchronous with three change types and no CAB ([ADR-008](../adr/008-async-change-approval-no-cab.md)). A Change sits on the shared work-item base ([ADR-013](../adr/013-shared-work-item-base.md)).

## 1. Change Entity

On the shared work-item base (`number`, assignment, comments/work-notes, attachments, watchers, links, audit), plus change-specific fields:

| Field | Notes |
|---|---|
| `number` | e.g. `CHG-00031` |
| `title`, `description` | |
| `type` | standard / normal / emergency (§2) |
| `state` | per-type lifecycle (§3) |
| `requested_by`, `implementer` | |
| `affected_cis[]`, `affected_services[]` | drives impact ([09 §5](09-cmdb.md)) |
| `risk_level`, `risk_assessment` | (§6) |
| `approvals` | reuse SR approval engine (§7) |
| `planned_start`, `planned_end` | scheduling window (§8) |
| `implementation_plan`, `test_plan`, `rollback_plan` | |
| `actual_start`, `actual_end` | recorded at implementation |
| `outcome` | successful / failed / rolled-back (§10) |
| `pir` | post-implementation review (§10) |
| `linked_*` | incidents (caused/fixed), problems, CIs |

| ID | Requirement | Priority |
|---|---|---|
| CHG-001 | Change entity on the shared work-item base + change-specific fields | Must |

## 2. Change Types

Per [ADR-008](../adr/008-async-change-approval-no-cab.md):

| Type | Approval |
|---|---|
| **Standard** | Pre-approved template (§5); no per-change approval |
| **Normal** | Async approval before implementation (§7) |
| **Emergency** | Expedited/parallel approval; retroactive review |

## 3. Lifecycles

**Normal:**
```
Draft → Assessment → Approval → Scheduled → Implementation → Review → Closed
                        │                                                  
                     Rejected                  Cancelled (pre-implementation)
```

**Standard:**
```
Draft (from template) → Scheduled → Implementation → Closed
```

**Emergency:**
```
Draft → Expedited Approval → Implementation → Review (retroactive) → Closed
```

| ID | Requirement | Priority |
|---|---|---|
| CHG-002 | Per-type state machines (normal full, standard pre-approved, emergency retroactive) | Must |
| CHG-003 | Legal transitions enforced; Rejected/Cancelled handled | Must |

## 4. Standard Change Catalog

- **Pre-approved change templates** maintained by the **Change Manager** ([ADR-008](../adr/008-async-change-approval-no-cab.md)).
- A template carries default fields, risk level, implementation/rollback plans, and skips per-change approval.
- Raising a standard change instantiates from a template.

| ID | Requirement | Priority |
|---|---|---|
| CHG-004 | Change Manager maintains a catalog of pre-approved standard-change templates | Must |
| CHG-005 | Standard changes instantiate from templates and skip approval | Must |

## 5. Risk & Impact Assessment

- **Impact auto-surfaced from the CMDB**: affected CIs + their dependents/dependencies via impact analysis ([09 §5](09-cmdb.md)).
- **Risk level** (low/medium/high) via a **configurable matrix/questionnaire** ([ADR-012](../adr/012-configuration-driven-design.md)) with manual override, plus freeform `risk_assessment`.

**Seed risk questionnaire (default, editable; weighted answers sum to a score → low / medium / high):**

| Question | Answers (low → high risk) |
|---|---|
| Blast radius — CIs/services affected (from CMDB impact) | one · a few · many |
| Customer-facing impact | none · internal only · customer-facing production |
| Reversibility | easy rollback · difficult · irreversible |
| Tested in staging | yes · partial · no |
| Downtime required | none · brief · extended |
| Familiarity | routine/repeated · occasional · first-time |

Thresholds (score → level) are admin-configurable; manual override is always allowed and recorded.

| ID | Requirement | Priority |
|---|---|---|
| CHG-006 | CMDB impact (affected CIs + dependents) surfaced on the change | Must |
| CHG-007 | Risk level via configurable matrix/questionnaire with manual override | Must |

## 6. Approval

- **Reuses the service-request approval engine** ([05 §5](05-service-request.md)): none / single / sequential multi-step.
- **Approver sources:** Change Manager and the **Service Owner(s)** of affected services; named user / role also available. (Manager-based deferred, [ADR-007](../adr/007-jit-provisioning-google-groups.md).)
- Normal changes require approval before Scheduled; emergency changes use expedited/parallel approval; standard changes are pre-approved.

| ID | Requirement | Priority |
|---|---|---|
| CHG-008 | Approval via the shared SR approval engine; approvers incl. Change Manager + affected Service Owners | Must |
| CHG-009 | Normal requires pre-approval; emergency expedited; standard pre-approved | Must |

## 7. Scheduling

- **Change calendar:** a view of scheduled changes with their windows.
- **Freeze/blackout windows:** admin-configurable periods during which **normal and standard** changes are blocked (e.g. month-end) ([ADR-012](../adr/012-configuration-driven-design.md)). **Emergency** changes may proceed during a freeze **with approval** (the override is recorded).
- **Conflict detection:** warn when changes overlap on a shared CI/service (direct CI plus dependents via the impact graph).

| ID | Requirement | Priority |
|---|---|---|
| CHG-010 | Change calendar showing scheduled windows | Must |
| CHG-011 | Configurable freeze windows block normal/standard changes; emergency may override with approval (recorded) | Must |
| CHG-012 | Conflict warning for overlapping changes on a shared CI/service (direct + dependents via impact graph) | Should |

## 8. Implementation & Rollback

- Implementer executes per the implementation plan, records `actual_start`/`actual_end`.
- On failure, the rollback plan is followed; outcome recorded as failed or rolled-back.

| ID | Requirement | Priority |
|---|---|---|
| CHG-013 | Record actual implementation window; rollback plan on failure | Must |

## 9. Post-Implementation Review & Outcome

- **PIR** for normal and emergency changes (and failed standard): outcome + lessons.
- `outcome` ∈ { successful, failed, rolled-back }.
- Outcomes feed the **change failure rate** metric ([08 §6](08-sla-engine.md)).

| ID | Requirement | Priority |
|---|---|---|
| CHG-014 | PIR with outcome; outcomes feed change failure rate | Must |

## 10. Change API

- REST API to create/update changes — especially **standard changes from CI/CD pipelines** ([00 §4](00-vision-scope.md)).
- Token-authenticated, scoped ([02 §10](02-roles-permissions.md)); pipeline-created changes are authored by a service account.

| ID | Requirement | Priority |
|---|---|---|
| CHG-015 | Token-authed Change API for programmatic (CI/CD) change creation | Must |

## 11. Linking

- **Change ↔ incident** (caused-by-change; or change that fixes an incident), **Change ↔ problem** (permanent fix, [07](07-problem-mgmt.md)), **Change ↔ CI** ([09](09-cmdb.md)).
- "What changed before this incident?" is answerable by correlating incident time with change windows on affected CIs — goal **G6** ([00 §3](00-vision-scope.md)).

| ID | Requirement | Priority |
|---|---|---|
| CHG-016 | Link changes to incidents, problems, and CIs; support change-vs-incident correlation (G6) | Must |

## 12. Permissions

Extends the reserved Change Manager role ([02 §3](02-roles-permissions.md)):

- `change:manage` — create/transition changes, maintain standard-change catalog, set freeze windows: **Change Manager**.
- `change:approve` — Change Manager and affected **Service Owner(s)**.
- `change:implement` — **L3 Technical Specialist** (and infra/sysadmin) as assigned implementers.

| ID | Requirement | Priority |
|---|---|---|
| CHG-017 | Change Manager manages; Service Owners approve; L3/infra implement | Must |

## 13. Configurability

Admin-configurable ([ADR-012](../adr/012-configuration-driven-design.md)): change types' approval routing, risk matrix/questionnaire, standard-change templates, freeze windows.

## 14. Audit Requirements

Feeds the [01 §5](01-architecture-nfr.md) audit log:

- Create, every state transition.
- Each approval action (approver + decision + comment).
- Risk level changes; schedule changes.
- Implementation start/end; outcome and rollback.
- Standard-change template and freeze-window changes.
- API-created changes (service account actor).

## 15. Open Questions

_None outstanding._ Resolved: seed risk questionnaire (§5, editable, refine with the Change Manager); freeze blocks normal/standard, emergency overrides with recorded approval (§7); R3 ships the Change API only, no reference pipeline; conflict detection covers direct CI + dependents via the impact graph (§7).
