# 02 — Roles & Permissions

|              |                 |
| ------------ | --------------- |
| Status       | Draft           |
| Last updated | 2026-06-13      |
| Owner        | Iqbal (Infokes) |

Defines the access model: how users, roles, permissions, and teams compose; the role catalog; visibility rules; user lifecycle; and SSO mechanics. The enforcement _mechanism_ is fixed in [01 §4](01-architecture-nfr.md); this doc fixes the _content_.

## 1. Core Model — Capability vs Scope

The load-bearing idea: separate **what you can do** from **what you can do it to**.

- **Role = capability.** A bundle of permissions (`incident:resolve`, `change:approve`). Global to the user.
- **Assignment Group (Team) = scope.** Which work routes to you. Membership, not role, decides which queues you see work in.

A **user** is therefore: a set of **roles** + a set of **group memberships**. A ticket routes to a _group_; any member of that group holding the relevant role can work it.

This prevents role explosion: there is no "eClinic-Backend-Engineer" role — just a `Technical Specialist` capability plus membership in the `eClinic A` team.

| ID       | Requirement                                                                                      | Priority |
| -------- | ------------------------------------------------------------------------------------------------ | -------- |
| ROLE-001 | A user holds a set of roles (capabilities) and a set of group memberships (scope), independently | Must     |
| ROLE-002 | Roles are global; assignment groups determine work routing/scope                                 | Must     |

## 2. Permission Model

- Permissions are strings of the form `practice:action` — e.g. `incident:create`, `incident:assign`, `catalog:manage`, `change:approve`.
- A role is a named bundle of permissions.
- A user's effective permissions = union of all their roles' permissions.
- Permissions activate as practices ship: `change:*` and `cmdb:*` are dormant until R3.

| ID       | Requirement                                                                                | Priority |
| -------- | ------------------------------------------------------------------------------------------ | -------- |
| ROLE-003 | Permissions modeled as `practice:action`; effective set is the union across a user's roles | Must     |
| ROLE-004 | Permissions for not-yet-shipped practices are inert until that practice releases           | Must     |

## 3. Role Catalog

One person may hold several roles. "Active" = release in which the role's permissions first do something.

| Role                          | Scope       | Active | Capability summary                                                                                                      |
| ----------------------------- | ----------- | ------ | ----------------------------------------------------------------------------------------------------------------------- |
| **Requester**                 | self        | R1     | Create tickets; view/comment on own tickets. Default role for every user.                                               |
| **Service Desk Agent (L1)**   | global read | R1     | Triage, categorize, route, resolve known issues. Reassign across groups.                                                |
| **Technical Support (L2)**    | group       | R1     | Work escalated tickets, KB-driven fixes, escalate to L3.                                                                |
| **Technical Specialist (L3)** | group       | R1     | Work escalated tickets, RCA, implement changes (R3).                                                                    |
| **Group Lead**                | own group   | R1     | Manage own group's membership; assign work within group.                                                                |
| **Incident Manager**          | global      | R1     | Reassign anything, declare/drive major incidents, override/pause SLA.                                                   |
| **Service Owner**             | own service | R1     | Edit own service's catalog entry + SLA targets; view and work tickets for own service (self-assign, escalate, resolve). |
| **Problem Manager**           | global      | R2     | Own problems; link incidents; manage known errors.                                                                      |
| **Knowledge Editor**          | global      | R2     | Create/edit/publish KB articles.                                                                                        |
| **Change Manager**            | global      | R3     | Own change process; maintain standard-change catalog; approve.                                                          |
| **CMDB Owner**                | global      | R3     | Manage CIs, CI classes, relationships; own audit cycle.                                                                 |
| **Admin**                     | global      | R1     | Platform config; user/role/group management; API token management; audit-log read.                                      |

R2/R3 role permission detail is specified in those practices' docs; the rows above reserve the roles.

## 4. Role → Permission Matrix (R1)

`✓` = allowed; `own` = scoped to records the user owns; `svc` = scoped to the user's owned service; `grp` = scoped to the user's group(s); `–` = not allowed. Incident-specific state-transition permissions are finalized in [04-incident-mgmt.md](04-incident-mgmt.md); this is the role-level overview.

| Action                            | Requester | L1  | L2  | L3  | Lead | Inc Mgr | Svc Owner | Admin |
| --------------------------------- | --------- | --- | --- | --- | ---- | ------- | --------- | ----- |
| Create ticket                     | ✓         | ✓   | ✓   | ✓   | ✓    | ✓       | ✓         | ✓     |
| View own tickets                  | ✓         | ✓   | ✓   | ✓   | ✓    | ✓       | ✓         | ✓     |
| View all tickets                  | –         | ✓   | ✓   | ✓   | ✓    | ✓       | svc       | ✓     |
| Comment / work note               | own       | ✓   | ✓   | ✓   | ✓    | ✓       | ✓         | ✓     |
| Self-assign from group queue      | –         | ✓   | ✓   | ✓   | ✓    | ✓       | svc       | ✓     |
| Assign within group               | –         | ✓   | –   | –   | ✓    | ✓       | –         | ✓     |
| Reassign across groups            | –         | ✓   | –   | –   | grp  | ✓       | –         | ✓     |
| Escalate to higher tier           | –         | ✓   | ✓   | ✓   | ✓    | ✓       | svc       | ✓     |
| Resolve / close                   | –         | ✓   | ✓   | ✓   | ✓    | ✓       | svc       | ✓     |
| Declare / drive major incident    | –         | –   | –   | –   | –    | ✓       | –         | ✓     |
| Override / pause SLA              | –         | –   | –   | –   | –    | ✓       | svc       | ✓     |
| Manage own catalog entry + SLA    | –         | –   | –   | –   | –    | –       | ✓         | ✓     |
| Manage all catalog                | –         | –   | –   | –   | –    | –       | –         | ✓     |
| Manage own group membership       | –         | –   | –   | –   | ✓    | –       | –         | ✓     |
| Manage users / roles / all groups | –         | –   | –   | –   | –    | –       | –         | ✓     |
| Mint / revoke API tokens          | –         | –   | –   | –   | –    | –       | –         | ✓     |
| Read audit log                    | –         | –   | –   | –   | –    | –       | –         | ✓     |

## 5. Assignment Groups (Teams)

A Team is the people-container and the unit of work routing. Team = Assignment Group (1:1, no separate concepts).

- Fields: name, description, members, Group Lead (exactly one per team), type.
- **Type:**
  - **Product team** — maintains one or more Modules (e.g. `eClinic A` maintains `eClinic–Registration`). Tickets reach it via service/module routing.
  - **Infra / category team** — not tied to a product service (e.g. `Infra/Network`, `Database`). Tickets reach it via category routing.
- A Team `maintains` many Modules; a Module is maintained by exactly one Team (primary). Cross-team reassignment is allowed manually; secondary-team ownership is deferred.

The **Service → Module → Team** model (per [ADR-010](../adr/010-services-modules-as-cis.md)):

```
Service: eClinic ──┬─ Module: Registration ─ maintained by ─▶ Team: eClinic A
                   └─ Module: Logistics    ─ maintained by ─▶ Team: eClinic B
Service: Payment Gateway ─ (default team) ──────────────────▶ Team: IC C
Cross-cutting: Team: Infra/Network, Team: Database  (category-routed)
```

- A **Service** has one **Service Owner** (across all its teams). A **Team** has **exactly one Group Lead**; a person may be Group Lead of multiple teams.
- Service and Module entities, and the `maintained_by` link, are defined in [03-service-catalog.md](03-service-catalog.md). The routing algorithm is defined in [04-incident-mgmt.md](04-incident-mgmt.md). This doc owns only the **Team/Assignment Group** entity.

| ID      | Requirement                                                                                              | Priority |
| ------- | -------------------------------------------------------------------------------------------------------- | -------- |
| GRP-001 | Assignment Group (Team) entity with members, exactly one Group Lead, and type (product / infra-category) | Must     |
| GRP-004 | One Group Lead per team; a person may lead multiple teams                                                | Must     |
| GRP-002 | A Team maintains many Modules; a Module has exactly one primary maintaining Team                         | Must     |
| GRP-003 | Manual cross-team reassignment supported; secondary-team ownership deferred                              | Should   |

## 6. Visibility & Data Scoping

- **Agents (L1/L2/L3, Lead, Incident Manager) can read all tickets** — internal transparency, and operationally simpler at ~300 users. Write actions remain gated by role + assignment.
- **Requesters see only their own tickets** in R1. Team/department-wide visibility is deferred.
- **Service Owners** see all tickets for their own service.
- Data scoping is enforced in queries, never only in the UI (per [01 §4](01-architecture-nfr.md)).

| ID      | Requirement                                                         | Priority |
| ------- | ------------------------------------------------------------------- | -------- |
| VIS-001 | Agent roles can read all tickets; writes gated by role + assignment | Must     |
| VIS-002 | Requesters can read only their own tickets (R1)                     | Must     |
| VIS-003 | Service Owners can read all tickets for their owned service         | Must     |

## 7. Assignment Mechanics

Both push and pull are supported (small teams need the flexibility):

- **Pull:** a group member self-assigns a ticket from the group queue.
- **Push:** a Group Lead (or Incident Manager / Admin) assigns a queued ticket to a specific member.

| ID      | Requirement                                                         | Priority |
| ------- | ------------------------------------------------------------------- | -------- |
| ASG-001 | Group members may self-assign (pull) from their group's queue       | Must     |
| ASG-002 | Group Lead / Incident Manager / Admin may assign (push) to a member | Must     |

## 8. User Lifecycle

- **Provisioning (JIT):** first successful Google SSO login creates the user with default role `Requester`. No bulk import. See [ADR-007](../adr/007-jit-provisioning-google-groups.md).
- **Role/group assignment:** by Admin (and Group Lead for own-group membership) via the admin UI.
- **Deactivation:** Admin deactivates in-app, or a disabled Google account blocks login. Deactivated users cannot authenticate.
- **No hard delete:** users are never deleted; historical records keep their user reference. A deactivated user's PII (name, email) is retained as long as any record references it — anonymizing the actor would break audit and ticket attribution.
- **Retention:** the audit log is retained **3 years** ([01 §5](01-architecture-nfr.md)). Operational/ticket data is retained **5 years**, after which requester **PII is anonymized** while the ticket (for trend/history) is kept. A UU PDP erasure request (anonymize PII, keep a tombstone reference) reuses the same anonymization path.
- All role grants/revokes and group membership changes are audited.

| ID      | Requirement                                                                        | Priority |
| ------- | ---------------------------------------------------------------------------------- | -------- |
| USR-001 | JIT provisioning on first SSO login; default role Requester                        | Must     |
| USR-002 | Admin manages roles and group memberships; Group Lead manages own-group membership | Must     |
| USR-003 | Deactivation blocks login; users are never hard-deleted                            | Must     |
| USR-004 | Role/group changes are audited                                                     | Must     |
| USR-005 | Operational data retained 5 years, then requester PII anonymized; same path serves UU PDP erasure | Must |

## 9. SSO Mechanics

- OIDC authorization-code flow with Google Workspace; `hd` claim restricted to the Infokes domain. No local passwords.
- **First-admin bootstrap:** an email allowlist in deploy config grants Admin on first login — otherwise nobody can assign roles. Documented as a deployment step.
- Google Group → role auto-mapping is deferred (manual assignment first); see [ADR-007](../adr/007-jit-provisioning-google-groups.md).

| ID      | Requirement                                             | Priority |
| ------- | ------------------------------------------------------- | -------- |
| SSO-001 | OIDC code flow with Google; domain-restricted via `hd`  | Must     |
| SSO-002 | First-admin bootstrap via deploy-config email allowlist | Must     |

## 10. Service Accounts & API Tokens

- A reserved **`system`** actor is the author of machine-originated records (e.g. Grafana-webhook incidents). It appears in the audit log as `system`.
- **API tokens** are minted and revoked by Admin, scoped to specific permissions, and audited on mint/revoke. Webhook endpoints authenticate with these tokens.

| ID      | Requirement                                                      | Priority |
| ------- | ---------------------------------------------------------------- | -------- |
| SVC-001 | Reserved `system` actor for machine-originated records           | Must     |
| SVC-002 | Admin-managed, scoped, revocable API tokens; mint/revoke audited | Must     |

## 11. Delegation / Out-of-Office

Deferred to **R3** (most valuable once Change approvals exist). In R1, a Group Lead / Incident Manager / Admin manually reassigns the work of an absent member.

| ID      | Requirement                                                   | Priority   |
| ------- | ------------------------------------------------------------- | ---------- |
| DEL-001 | Approver/assignee delegation (out-of-office) — deferred to R3 | Won't (R1) |

## 12. Audit Requirements

Auditable events for this domain (feed the [01 §5](01-architecture-nfr.md) audit log):

- Login success / failure.
- Role grant / revoke.
- Group membership add / remove.
- API token mint / revoke.
- User deactivation / reactivation.

## 13. Open Questions

_None outstanding._ Resolved: operational data retained **5 years** then requester PII anonymized (§8).
