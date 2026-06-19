# 11 — Integrations & Notifications

| | |
|---|---|
| Status | Draft |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

Owns the notification system, the Grafana webhook mechanics, inbound/outbound email, and the integration surface. Identity (Google OIDC) is owned by [01 §3](01-architecture-nfr.md)/[02 §9](02-roles-permissions.md) and only referenced here. Behaviors are admin-configurable per [ADR-012](../adr/012-configuration-driven-design.md).

## 1. Notification System

A **channel-adapter** interface ([ADR-004](../adr/004-notification-adapter-defer-whatsapp.md)). R1 adapters: **Email** (AWS SES) and **Mattermost** (bot). WhatsApp / Google Chat are deferred — adding them means a new adapter, not new notification logic.

Pipeline: **event → recipient resolution → per-user preference filter → per-channel render → deliver**. Delivery is asynchronous via BullMQ (retried, dead-lettered — [01 §6](01-architecture-nfr.md)).

| ID | Requirement | Priority |
|---|---|---|
| NOTIF-001 | Channel-adapter interface; R1 ships Email (SES) and Mattermost adapters | Must |
| NOTIF-002 | Notifications dispatched asynchronously via BullMQ with retry/DLQ | Must |

## 2. Events & Recipients

| Event | Requester | Assignee | Group | Watchers | Approver | Lead / Inc Mgr |
|---|---|---|---|---|---|---|
| Assigned to group | – | – | ✓ | ✓ | – | – |
| Assigned to individual | – | ✓ | – | ✓ | – | – |
| Public comment | ✓ | ✓ | – | ✓ | – | – |
| State change (resolved/closed) | ✓ | ✓ | – | ✓ | – | – |
| SLA warning | – | ✓ | – | – | – | Lead |
| SLA breach | – | ✓ | – | – | – | Lead → Inc Mgr |
| Major incident declared/updated | ✓* | ✓ | ✓ | ✓ | – | Inc Mgr |
| Approval requested | – | – | – | – | ✓ | – |
| Approval approved/rejected | ✓ | ✓ | – | ✓ | – | – |

\* requester of child incidents linked to the major. Group-queue notifications default to a **digest** to avoid per-ticket spam (see §4).

| ID | Requirement | Priority |
|---|---|---|
| NOTIF-003 | Recipient resolution per the event matrix above | Must |

## 3. Channels

**Email (SES):**
- Outbound transactional email from a reply-enabled address; `Reply-To` carries a per-ticket token so replies thread back (§6).
- Inbound via SES receipt rule (§6).

**Mattermost (bot):**
- A bot account **DMs individuals** (assignee, requester, approver) — ITSM user mapped to MM user by email; if no MM account, **fall back to email**.
- **Channel posts** for group queues (optional per-group channel mapping) and a dedicated channel for **major incidents**.

| ID | Requirement | Priority |
|---|---|---|
| NOTIF-004 | Mattermost bot DMs individuals (email fallback) and posts to mapped group/major-incident channels | Must |
| NOTIF-005 | Outbound email uses a per-ticket reply token for threading | Must |

## 4. Preferences & Digests

- **Defaults** are sensible out of the box; each user may set channel preference (email / Mattermost / both) and **mute or digest non-critical** notifications.
- **Critical events always deliver**, overriding mute/digest: assigned-to-you, SLA breach, major incident, approval-needed-by-you.
- **Digest:** batched periodic summary for non-critical events (e.g. group-queue activity).

| ID | Requirement | Priority |
|---|---|---|
| NOTIF-006 | Per-user channel preference + mute/digest for non-critical events | Must |
| NOTIF-007 | Critical events always delivered regardless of preferences | Must |

## 5. Templates

- Notification templates are **admin-editable per event × channel**, **shipped with sensible defaults** ([ADR-012](../adr/012-configuration-driven-design.md)).
- Templates expose variables (ticket number, title, link, requester, assignee, state, SLA status, …).

| ID | Requirement | Priority |
|---|---|---|
| NOTIF-008 | Admin-editable templates per event×channel with default content and safe variable substitution | Must |

## 6. Watchers

- Users may **watch/unwatch** a work item and receive its public updates.
- **Auto-watch:** requester and current assignee. Agents may add other watchers.
- Watchers are part of the shared work-item base ([ADR-013](../adr/013-shared-work-item-base.md)).

| ID | Requirement | Priority |
|---|---|---|
| NOTIF-009 | Watch/unwatch on work items; requester and assignee auto-watch | Must |

## 7. Grafana Webhook

Mechanics behind [04 §14](04-incident-mgmt.md):

- Endpoint `/webhooks/grafana`, **token-auth** ([02 §10](02-roles-permissions.md)), **rate-limited** ([01 §2](01-architecture-nfr.md)); records authored by the `system` actor.
- Payload parsing: alert **labels → service/module** (admin mapping); **severity → priority** (admin map); **fingerprint → dedup**.
- An open incident for a fingerprint is updated/annotated rather than duplicated; unmapped alerts go to the L1 triage queue.
- **Auto-resolve** (optional, configurable): an alert-cleared notification resolves the linked incident.
- Self-monitoring guard: app-self alerts also notify Mattermost directly ([01 §10](01-architecture-nfr.md)).

| ID | Requirement | Priority |
|---|---|---|
| INT-001 | Grafana webhook: token-auth, rate-limited, `system`-authored, deduped by fingerprint | Must |
| INT-002 | Admin-managed label→service/module and severity→priority mappings | Must |
| INT-003 | Optional configurable auto-resolve on alert clear | Should |

## 8. Inbound Email

- **SES inbound** receipt rule → SNS/S3 → worker parses the message.
- **Threading:** a `[INC-xxxxx]` token (from the `Reply-To`/subject) appends the body as a **public comment** on that incident; attachments handled per [01 §9.1](01-architecture-nfr.md).
- **No token / no match:** create a new incident with the sender as requester (channel = email).
- **Sender resolution:** matched to an internal user by email. Unknown senders are quarantined in R1 (internal-only; external contact handling is a multi-tenancy guardrail, [ADR-002](../adr/002-defer-multitenancy-with-guardrails.md), not enabled).
- **Loop protection:** ignore auto-replies/bounces (`Auto-Submitted`, `Precedence: bulk`).

| ID | Requirement | Priority |
|---|---|---|
| INT-004 | SES inbound parsed by a worker; reply token threads into public comments | Must |
| INT-005 | Unmatched inbound creates a new incident; unknown senders quarantined | Must |
| INT-006 | Auto-reply/bounce loop protection | Must |

## 9. Identity (Reference)

Google Workspace OIDC, server-side sessions, JIT provisioning, first-admin bootstrap — defined in [01 §3](01-architecture-nfr.md) and [02 §9](02-roles-permissions.md). No additional requirements here.

## 10. Future Integrations

- **Zoho Desk bridge** — deferred ([ADR-003](../adr/003-defer-zoho-bridge.md)); `external_ref` is the R1 substitute. The webhook framework (token-auth, rate-limit, mapping) is reusable when a bridge owner is found.

## 11. Audit Requirements

Feeds the [01 §5](01-architecture-nfr.md) audit log:

- Template change; Grafana mapping change.
- Inbound-email-driven record creation/append (as `system`).
- Webhook token usage ([02 §10](02-roles-permissions.md)).
- Notification delivery failures.

## 12. Open Questions

_None outstanding for R1._ Resolved: group-queue notifications default to **digest** with per-user opt-in to instant (§4); per-ticket reply addressing uses the **`[INC-xxxxx]` subject token** (§5/§8); unknown inbound senders are **quarantined** (§8); Mattermost channels are **manually mapped per group** in R1 (§3). Sub-addressing and auto-provisioned channels remain later options.
