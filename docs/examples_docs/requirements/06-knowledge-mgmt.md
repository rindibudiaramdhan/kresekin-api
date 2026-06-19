# 06 — Knowledge Management

| | |
|---|---|
| Status | Draft (R2) |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

Goal (ITIL 4): capture, share, and reuse knowledge to speed resolution and enable self-service. Ships in **R2**. Designed alongside Problem Management ([07](07-problem-mgmt.md)) because known-error articles bridge the two. Behaviors are admin-configurable per [ADR-012](../adr/012-configuration-driven-design.md).

## 1. KB Article Entity

Articles are not work items; they sit beside the work-item base.

| Field | Notes |
|---|---|
| `id`, `slug`, `title` | |
| `body_markdown` | Markdown source (§4) |
| `type` | how-to / troubleshooting / faq / reference / known-error |
| `category`, `tags[]` | Configurable taxonomy |
| `status` | draft / in-review / published / archived (§3) |
| `author`, `reviewer` | reviewer nullable |
| `version` | Current published version + working draft (§3) |
| `audience` | internal-agents / all-employees (§7) |
| `linked_problem` | When `type = known-error` ([07](07-problem-mgmt.md)) |
| `linked_services[]` | Related services ([03](03-service-catalog.md)) |
| `review_due_at` | Periodic-review date (§9) |
| `view_count`, `helpful_count`, `not_helpful_count` | Analytics (§8) |
| timestamps | created, updated, published_at |

| ID | Requirement | Priority |
|---|---|---|
| KB-001 | KB article entity with type, taxonomy, status, audience, analytics, links | Must |

## 2. Article Types

how-to, troubleshooting, FAQ, reference, and **known-error** (links a Problem record, [07](07-problem-mgmt.md)).

## 3. Lifecycle & States

```
draft ──▶ (in-review) ──▶ published ──▶ archived
```

- **Default: self-publish** — an author with publish rights goes draft → published directly.
- **Optional review gate** (admin-configurable, [ADR-012](../adr/012-configuration-driven-design.md)): draft → **in-review** → published, where a Knowledge Editor approves.
- Editing a published article opens a **new draft version**; publishing replaces the current version (prior versions retained).
- **archived** — no longer surfaced in search; history kept.

| ID | Requirement | Priority |
|---|---|---|
| KB-002 | Lifecycle draft→(in-review)→published→archived; review gate configurable (default off/self-publish) | Must |
| KB-003 | Editing published opens a new draft version; publish replaces current; versions retained | Must |

## 4. Authoring

- Body stored as **Markdown**; edited in an **Obsidian-style Markdown editor** (live/split preview, Markdown syntax — not WYSIWYG).
- Images and attachments via the standard pipeline ([01 §9.1](01-architecture-nfr.md): WebP conversion, presigned URLs).
- **Internal cross-links** between articles (`[[article]]`-style) — Should.

| ID | Requirement | Priority |
|---|---|---|
| KB-004 | Markdown body with an Obsidian-style Markdown editor | Must |
| KB-005 | Images/attachments via the standard attachment pipeline | Must |
| KB-006 | Internal article-to-article cross-links | Should |

## 5. Search & Discovery

- **Postgres full-text search** over title + body + tags (no separate search infra).
- Filter by category, tag, type, audience.
- **Suggest-on-triage:** during incident creation/triage, surface relevant articles by category/keyword match ([04](04-incident-mgmt.md)).

| ID | Requirement | Priority |
|---|---|---|
| KB-007 | Postgres FTS over title/body/tags with category/tag/type/audience filters | Must |
| KB-008 | Relevant-article suggestions during incident triage | Must |

## 6. Linking

- **KB ↔ incident:** cite an article in a resolution; **create a KB draft from an incident** (prefilled from the incident).
- **KB ↔ problem:** known-error article links its Problem ([07](07-problem-mgmt.md)).
- **KB ↔ service:** articles tagged to services.

| ID | Requirement | Priority |
|---|---|---|
| KB-009 | Link articles to incidents, problems, and services; create-from-incident | Must |

## 7. Audience & Visibility

- Per-article `audience`: **internal-agents** (agent roles only) or **all-employees** (visible to requesters for self-service deflection).
- Enforced server-side in queries ([01 §4](01-architecture-nfr.md)), not only in the UI.

| ID | Requirement | Priority |
|---|---|---|
| KB-010 | Per-article audience (internal-agents / all-employees), enforced server-side | Must |

## 8. Feedback & Analytics

- **View count** per published article.
- **Helpful / not-helpful** vote (one per user, toggleable).
- Low-helpful or unviewed articles are surfaced for review.

| ID | Requirement | Priority |
|---|---|---|
| KB-011 | View counts and helpful/not-helpful votes; surface stale/low-value articles | Must |

## 9. Review Cycle

- `review_due_at` per article; overdue articles are flagged to the author / Knowledge Editor.
- Default review interval is configurable ([ADR-012](../adr/012-configuration-driven-design.md)).

| ID | Requirement | Priority |
|---|---|---|
| KB-012 | Periodic review dates with overdue flagging; configurable interval | Should |

## 10. Permissions

Extends the reserved Knowledge Editor role ([02 §3](02-roles-permissions.md)):

- `knowledge:author` — create/edit drafts: Knowledge Editor, L2 (Technical Support), L3 (Technical Specialist).
- `knowledge:publish` — Knowledge Editor always; authors when the review gate is **off** (self-publish).
- `knowledge:review` — Knowledge Editor (when the review gate is on).
- `knowledge:retire` — Knowledge Editor, Admin.
- Read: per article `audience`.

| ID | Requirement | Priority |
|---|---|---|
| KB-013 | Author (Editor/L2/L3), publish (Editor or author when gate off), review/retire (Editor) | Must |

## 11. Configurability

Admin-configurable ([ADR-012](../adr/012-configuration-driven-design.md)): review gate on/off, review interval, category/tag taxonomy, default audience.

## 12. Audit Requirements

Feeds the [01 §5](01-architecture-nfr.md) audit log:

- Create, edit (version), publish, archive/retire.
- Audience change.
- Review actions.

## 13. Open Questions

_None outstanding._ Resolved: article comments out of scope (R2); review gate is a single global toggle (per-category later); a basic self-service portal surfacing all-employees KB ships in R2.
