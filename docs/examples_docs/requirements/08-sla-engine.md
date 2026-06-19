# 08 — SLA Engine & Service Reliability Metrics

| | |
|---|---|
| Status | Draft |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

Owns SLA target definitions, the timer engine, breach handling, and the **definitions** of service-reliability metrics. SLA targets *attach* per [03](03-service-catalog.md); incident/request states that drive the clock are defined in [04](04-incident-mgmt.md)/[05](05-service-request.md); the source timestamps are captured there. Reliability **dashboards** are R2; **timestamp capture and breach recording are R1**.

Behaviors are admin-configurable per [ADR-012](../adr/012-configuration-driven-design.md).

## 1. SLA Policy

An SLA Policy binds a scope to targets and a clock basis.

| Field | Notes |
|---|---|
| `name` | |
| scope match | `service` (+ optional `module`), `priority` |
| `response_target` | Duration to first response |
| `resolution_target` | Duration to resolved |
| `clock_basis` | `wall_clock` (default) or `business_calendar:<id>` (§4) |
| `pause_rules` | Which Pending sub-reasons pause (default: all) |
| `warning_threshold` | % of target elapsed that triggers a warning (default 80%) |

- **Matching:** most specific wins — `(service, module, priority)` > `(service, priority)` > a default policy.
- **Requests:** fulfillment target comes from the catalog item ([03 §5](03-service-catalog.md), [05 §7](05-service-request.md)); the same engine applies.

| ID | Requirement | Priority |
|---|---|---|
| SLA-001 | SLA Policy binds (service[+module], priority) to response + resolution targets, clock basis, pause/warning config | Must |
| SLA-002 | Most-specific policy wins; a default policy always exists | Must |
| SLA-003 | Request fulfillment targets run on the same engine | Must |

## 2. Clocks

Concurrent clocks per work item:

| Clock | Starts | Stops | Applies to |
|---|---|---|---|
| **Response** | `detected_at` / created | `first_response_at` | Incident |
| **Resolution** | `detected_at` / created | `resolved_at` | Incident |
| **Fulfillment** | post-approval (or submit if none) | `fulfilled_at` | Request |

- **Pause:** Pending states pause the clock by default; configurable per sub-reason ([ADR-012](../adr/012-configuration-driven-design.md)). A pause log records each pause/resume for audit and active-time computation.
- The **response clock stops at `first_response_at`** by default; the stop event is **per-policy configurable** (e.g. `acknowledged_at`).
- **Active time** (elapsed minus pauses) is reported alongside MTTR (§6) to separate working effort from wall-clock duration.

| ID | Requirement | Priority |
|---|---|---|
| SLA-004 | Response, resolution, and fulfillment clocks with the start/stop events above | Must |
| SLA-005 | Pending pauses the clock (configurable); pause/resume logged | Must |
| SLA-012 | Response-clock stop event configurable per policy (default `first_response_at`) | Should |
| SLA-013 | Active time (elapsed − pauses) reported alongside MTTR | Should |

## 3. Breach & Warnings

- At `warning_threshold` (default 80% of target, on the clock basis) → notify assignee + Group Lead.
- At 100% → **breach**: mark breached, hierarchic escalation Group Lead → Incident Manager ([04 §8](04-incident-mgmt.md)).
- Breach outcomes are recorded (met/breached + elapsed) for R2 SLA-compliance reporting.

| ID | Requirement | Priority |
|---|---|---|
| SLA-006 | Warning at configurable threshold; breach escalates Lead → Incident Manager | Must |
| SLA-007 | Per-clock outcome (met/breached, elapsed) recorded for reporting | Must |

## 4. Clock Basis & Business Calendars

- **Default: wall-clock** — continuous 24/7 elapsed time.
- A policy may instead use a **business calendar**; then only business hours count toward elapsed. Selected per policy ([ADR-012](../adr/012-configuration-driven-design.md)).
- **Business calendar** (admin-defined): name, timezone (default Asia/Jakarta), working days + hours, holiday list (Indonesia public + company holidays). **Multiple calendars** supported (e.g. 24/7 for P0, 8×5 for P1–P3). A 24/7 calendar is equivalent to wall-clock.

| ID | Requirement | Priority |
|---|---|---|
| SLA-008 | Clock basis is wall-clock by default; per-policy business calendar selectable | Must |
| SLA-009 | Admin-defined business calendars (timezone, working hours, holidays); multiple supported | Must |

## 5. Engine Implementation

- Warning and breach are **BullMQ delayed jobs** scheduled at the computed warning/breach instants on the clock basis.
- On pause/resume, target change, or reassignment that changes the policy, scheduled jobs are **recomputed/rescheduled**.
- Handlers are idempotent; firing twice does not double-notify ([01 §6](01-architecture-nfr.md)).

| ID | Requirement | Priority |
|---|---|---|
| SLA-010 | Warning/breach scheduled as BullMQ jobs; rescheduled on pause/resume/target/policy change | Must |
| SLA-011 | Idempotent SLA jobs (no double-fire) | Must |

## 6. Service Reliability Metrics

Source timestamps are captured on incidents ([04 §1](04-incident-mgmt.md)). Metrics are computed **on a wall-clock 24/7 basis** (distinct from SLA's configurable basis). Means/trends are R2 dashboards; R1 captures the data.

| Metric | Definition | Formula |
|---|---|---|
| **MTTD** | Mean Time To Detect | `detected_at − fault_started_at` |
| **MTTA** | Mean Time To Acknowledge | `acknowledged_at − detected_at` |
| **MTTR** | Mean Time To Resolve | `resolved_at − detected_at` |
| **MTRS** | Mean Time to Restore Service | `resolved_at − fault_started_at` |
| **MTBF** | Mean Time Between Failures | `total_uptime ÷ #failures` (per service) |
| **Availability %** | Uptime ratio | `(period − downtime) ÷ period` |
| **FCR** | First-Contact Resolution rate | resolved at L1 without reassignment ÷ total |
| **Reopen rate** | Reopen quality signal | reopened ÷ total |
| **SLA compliance %** | Targets met | met ÷ total |

- `fault_started_at` is nullable; incidents without it are excluded from MTTD and MTRS.
- MTBF / availability use the `service_impacting` flag + impaired window ([04 §1](04-incident-mgmt.md)).
- **Aggregation dimensions:** per service, per module, per team, per priority, over a time range.
- Change failure rate joins this set with Change Enablement (R3).

| ID | Requirement | Priority |
|---|---|---|
| MET-001 | Compute MTTD, MTTA, MTTR, MTRS, MTBF, availability, FCR, reopen rate, SLA compliance from captured timestamps | Must (R2 dashboards) |
| MET-002 | Metrics on wall-clock 24/7 basis; nullable `fault_started_at` excluded from MTTD/MTRS | Must |
| MET-003 | Aggregation by service / module / team / priority / time range | Must |
| MET-004 | R1 captures all source timestamps and breach outcomes so R2 can compute history | Must |

## 7. Configurability

Admin-configurable ([ADR-012](../adr/012-configuration-driven-design.md)): SLA policies and targets, clock basis per policy, business calendars + holidays, warning threshold, Pending→pause rules.

## 8. Audit Requirements

Feeds the [01 §5](01-architecture-nfr.md) audit log:

- SLA policy create/edit, target change.
- Calendar/holiday change.
- Manual SLA override/pause ([02 §4](02-roles-permissions.md): Incident Manager, Service Owner).
- Breach events.

## 9. Open Questions

_None outstanding._ Resolved: response-clock stop event configurable per policy, default `first_response_at` (§2, SLA-012); active time reported alongside MTTR (§2, SLA-013); OLA deferred beyond R2; availability downtime = `service_impacting` incidents, planned-maintenance windows excluded (§6).
