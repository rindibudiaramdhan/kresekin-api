# 05 — Service Request Management

| | |
|---|---|
| Status | Draft |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

Goal (ITIL 4): handle pre-defined, user-initiated requests for something (access, provisioning, information) through a repeatable, approved workflow. A request is *for something*, not *something broken* (contrast [04-incident-mgmt.md](04-incident-mgmt.md)).

This doc owns request **instances**, **forms**, **approval**, and **fulfillment**. Catalog item *definitions* live in [03-service-catalog.md](03-service-catalog.md); SLA timer mechanics in [08-sla-engine.md](08-sla-engine.md). The Service Request sits on the shared **work-item base** ([ADR-013](../adr/013-shared-work-item-base.md)). Behaviors are admin-configurable per [ADR-012](../adr/012-configuration-driven-design.md).

## 1. Service Request Entity

On the shared work-item base (`number`, `requester`, `channel`, assignment, comments/work-notes, attachments, watchers, links, audit), plus request-specific fields:

| Field | Notes |
|---|---|
| `catalog_item` | The requested item ([03 §5](03-service-catalog.md)) |
| `form_responses` | Answers to the item's form (§3), stored as structured data |
| `state` | See §4 |
| `pending_reason` | When state = Pending |
| `approvals` | Approval records (§5) |
| `fulfillment_checklist` | Checklist instance (§6) |
| `fulfillment_notes` | On fulfillment |
| `sla_target` | Fulfillment SLA from the catalog item ([08](08-sla-engine.md)) |
| timestamps | created, updated, approved_at, fulfilled_at, closed_at |

| ID | Requirement | Priority |
|---|---|---|
| REQ-001 | Service Request entity on the shared work-item base + request-specific fields | Must |
| REQ-002 | Request is always tied to a catalog item; form responses stored structurally | Must |

## 2. Intake

- Raised from the **request catalog** ([03](03-service-catalog.md)) — requester selects an item they are eligible for (eligibility enforced server-side, [03 §7](03-service-catalog.md)).
- Requester fills the item's form (§3) and submits.

| ID | Requirement | Priority |
|---|---|---|
| REQ-003 | Requests raised only from catalog items the requester is eligible for | Must |

## 3. Request Form

Each catalog item has an **admin-built form** ([ADR-012](../adr/012-configuration-driven-design.md)). A form is an ordered set of fields:

| Field attribute | Notes |
|---|---|
| `key`, `label` | |
| `type` | text, textarea, number, date, select, multiselect, checkbox, file |
| `required` | |
| `options` | for select / multiselect |
| `validation` | e.g. regex, min/max, file type/size (file rules per [01 §9.1](01-architecture-nfr.md)) |
| `help_text` | |

Admins compose forms per item; the field-type set above is the R1 baseline and is itself the custom-field mechanism for requests.

| ID | Requirement | Priority |
|---|---|---|
| REQ-004 | Admin form builder supporting the listed field types, required flags, options, validation | Must |
| REQ-005 | Form responses validated server-side on submit | Must |

## 4. Lifecycle & States

```
Submitted ──▶ [Pending Approval ──▶ Approved] ──▶ In Fulfillment ──▶ Fulfilled ──▶ Closed
     │               └──▶ Rejected (terminal)            │
     └──▶ Cancelled (pre-fulfillment)              Pending (awaiting requester)
```

- **Submitted** — created. If the item has an approval flow → **Pending Approval**; otherwise → **In Fulfillment**.
- **Pending Approval** — awaiting approver(s) (§5).
- **Approved** → **In Fulfillment**; **Rejected** is terminal.
- **In Fulfillment** — fulfillment team working it.
- **Pending** — awaiting requester input; `pending_reason`; pauses SLA by default (configurable, [ADR-012](../adr/012-configuration-driven-design.md)).
- **Fulfilled** — work complete; starts the auto-close timer.
- **Closed** — terminal; auto-closes after the configurable window (shared with incidents, default 3 business days).
- **Cancelled** — terminal; by requester (pre-fulfillment) or agent.

| ID | Requirement | Priority |
|---|---|---|
| REQ-006 | Request state machine as above with enforced legal transitions | Must |
| REQ-007 | No-approval items skip Pending Approval straight to In Fulfillment | Must |
| REQ-008 | Fulfilled auto-closes after the configurable window; Pending pauses SLA (configurable) | Must |

## 5. Approval

- Approval flow per catalog item: **none** / **single-approver** / **sequential multi-step**.
- **Approver source** per step (R1): named user, role, or the linked Service's Owner. **Manager-based approval is deferred** (would require importing org hierarchy from Google Workspace) — see [ADR-007](../adr/007-jit-provisioning-google-groups.md).
- Each step: approve or reject, with a comment. A reject at any step sets the request **Rejected** (terminal).
- Parallel approval is deferred (sequential only in R1).
- Approval records are part of the request and audited.

| ID | Requirement | Priority |
|---|---|---|
| REQ-009 | Approval flows: none / single / sequential multi-step | Must |
| REQ-010 | Approver source: named user, role, or linked Service Owner (manager-based deferred) | Must |
| REQ-011 | Any rejection terminates the request as Rejected; approvals audited | Must |

## 6. Fulfillment

Single-team fulfillment with an optional checklist (Option A; multi-task cross-team workflow deferred to R2):

- The request is assigned to the catalog item's `fulfillment_team`; one owner is responsible.
- An optional **checklist template** (admin-defined per catalog item) instantiates onto the request — tickable steps to guide fulfillment. Cross-team steps are coordinated manually or via separate requests.
- Checklist is **advisory** in R1 (not a hard gate on Fulfilled). Owner marks the request **Fulfilled** with optional notes.

| ID | Requirement | Priority |
|---|---|---|
| REQ-012 | Request fulfilled by a single team with an optional admin-defined checklist (advisory) | Must |
| REQ-013 | Multi-task cross-team fulfillment workflow — deferred to R2 | Won't (R1) |

## 7. SLA

- **Fulfillment SLA** = time-to-fulfill, from the catalog item ([03 §5](03-service-catalog.md)), mechanics in [08](08-sla-engine.md).
- The clock starts **after approval** (or at Submitted when there is no approval); approval duration is tracked separately. Pending (awaiting requester) pauses it (configurable).

| ID | Requirement | Priority |
|---|---|---|
| REQ-014 | Fulfillment SLA starts post-approval; approval time tracked separately | Must |

## 8. Reclassification

An agent may convert a Service Request to an Incident, or vice versa, when it was filed under the wrong type. Shared work-item fields carry over; type-specific fields are reset/prompted. The conversion is audited and the original number preserved with a link.

| ID | Requirement | Priority |
|---|---|---|
| REQ-015 | Agent can reclassify request ↔ incident; shared fields carry over; audited | Should |

## 9. Notifications

Events that notify (Email + Mattermost, [11](11-integrations.md)):

- Submitted (to fulfillment group; to approver if approval pending).
- Approval requested / approved / rejected (to relevant parties + requester).
- In Fulfillment, Fulfilled, Closed.
- Pending — awaiting requester.
- SLA breach warning / breach.

| ID | Requirement | Priority |
|---|---|---|
| REQ-016 | Notifications on submit, each approval transition, fulfillment milestones, pending, SLA breach | Must |

## 10. Configurability

Admin-configurable surfaces ([ADR-012](../adr/012-configuration-driven-design.md)):

- Form schema per catalog item.
- Approval flow per catalog item.
- Checklist template per catalog item.
- Pending → SLA-pause, auto-close window (shared with incidents).

## 11. Audit Requirements

Feeds the [01 §5](01-architecture-nfr.md) audit log:

- Create/submit, every state transition.
- Each approval action (approve/reject + approver + comment).
- Assignment/reassignment.
- Fulfillment and closure (incl. auto-close).
- Reclassification.

## 12. Open Questions

_None outstanding._ Resolved: fulfillment SLA excludes approval time (starts post-approval, §7); checklist is advisory, not a hard gate (§6); rejection is terminal — a new request is raised (§4); parallel approval deferred, sequential only in R1 (§5).
