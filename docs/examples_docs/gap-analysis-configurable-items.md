# Gap Analysis — Configurable Items (Requirements vs Implementation)

**Date:** 2026-06-14
**Scope:** Admin-configurable surfaces defined by ADR-012 and the numbered specs, checked against the current backend on branch `feat/team-management`.
**Method:** Cross-referenced every "configurable" requirement with DB schema (`packages/db/src/schema`), TypeBox schemas, services, and routes (`apps/api/src/modules`).

> **Note on project state:** Per memory, R1–R3 backend is "complete" and the frontend is the gap. This report scores *backend* configurability — storage + admin write API + runtime use. A surface that is hardcoded in code (no persistence, no admin endpoint) is a gap regardless of whether a future admin UI is planned.

> **Update 2026-06-14 — settings store landed.** A general configuration store now exists (`settings` table + `@itsm/shared` registry + `settings:manage` admin API + `SettingsView.vue` admin UI), with each change validated against a per-key TypeBox schema and audited in-transaction. The remediation prerequisite (item 1 below) is **done**. Configurables that fit a scalar/matrix/toggle/list shape have been migrated onto it and are now read at runtime; the items still open are the ones needing their own storage (field-definition tables, per-entity columns, enum→table migrations) or admin CRUD over existing tables. Rows flipped to ✅ below carry a `(settings)` marker.

---

## Root cause (resolved)

~~**There is no general settings / configuration store.**~~ **Resolved.** The `settings` table (`packages/db/src/schema/settings.ts`) + registry (`packages/shared/src/settings/index.ts`) + service/routes (`apps/api/src/modules/settings/`) are the store ADR-012 called for: key + JSONB value, schema-validated on write, audited in the mutation's transaction, with a registry-driven admin UI. Scalar/matrix configurables that used to default to a hardcoded constant are now registry keys read at runtime. What remains are the surfaces that need *more than* a settings key.

---

## Summary scoreboard

> **Update 2026-06-13/14.** 2nd pass: change risk matrix (CHG-007, settings), per-service intake mode (INC-011, `services.intake_mode` + migration 0014), Grafana severity/label map CRUD (INC-023) and category taxonomy CRUD (INC-010); resolution codes (INC §11) **decided fixed**. 3rd pass: **incident custom fields (INC-026)** — `incident_field_definitions` table (global/per-service) + `incidents.custom_fields` jsonb (migration 0015), admin CRUD + a field-builder UI, dynamic intake rendering, and detail display; values validated with the shared form validator.

| Status | Count | Items |
|---|---|---|
| ✅ Implemented (configurable) | 19 | Request custom fields, SLA stop-event, SLA warning threshold, Change freeze windows, CMDB impact depth, Priority matrix, Pending→SLA-pause, Auto-close window, Reopen window, Alert auto-resolve, KB review gate, KB review interval, CMDB verification interval, CMDB cert-expiry thresholds, Change risk matrix, Intake mode per service, Grafana severity/label map CRUD, Category update/delete, **Incident custom fields (INC-026)** |
| ⚠️ Partial | 1 | Session timeouts (env-only) |
| ❌ Not configurable (hardcoded / missing) | 1 | CMDB cert auto-incident (needs recurring job) |
| 🔒 Fixed by decision | 1 | Resolution codes — controlled vocabulary, not admin-editable (see note) |

(Counts overlap categories where a requirement bundles several knobs.)

> **Resolution codes (INC §11) — decision: NOT admin-configurable.** Kept as the `RESOLUTION_CODES` pgEnum. They are a stable controlled vocabulary that reporting (change-failure-rate, resolution-reason analysis) and the Grafana auto-resolve path depend on; letting them drift at runtime would break those. Changing the set is a deliberate schema/enum migration, not a setting. The seed comment in `packages/shared/src/incident/enums.ts` records this.

---

## Detailed findings

### ADR-012 bounded R1 list

| # | Configurable surface | Req IDs | Status | Evidence |
|---|---|---|---|---|
| 1 | **Priority matrix** (Impact × Urgency → Priority) | INC-008, ADR-012 | ✅ Implemented (settings) | Registry key `incident.priority_matrix` (`packages/shared/src/settings/index.ts`); read on intake/triage via `settings.getValue('incident.priority_matrix')` (`incident/service.ts`). Editable as a matrix grid in `SettingsView.vue`. `DEFAULT_PRIORITY_MATRIX` is now only the seeded fallback. |
| 2 | **Custom fields — incidents** | INC §16 / INC-026, ADR-012 | ✅ Implemented | `incident_field_definitions` table (typed field defs, optional/required, `service_id` null = global / set = per-service) + `incidents.custom_fields` jsonb (migration 0015). Admin CRUD at `/api/v1/incidents/field-definitions` (settings:manage, audited) and `GET /incidents/fields?serviceId=` for intake; values validated with the shared `validateFormResponses` against the applicable defs, unknown keys dropped. UI: `IncidentFieldsView` field builder, dynamic `DynamicFields` renderer on the intake drawer (refreshes per selected service), and a Custom fields panel on incident detail. |
| 2b | **Custom fields — requests** | REQ §3, ADR-012 | ✅ Implemented | `catalog_items.form_schema` (jsonb `FormSchema`) + `service_requests.form_responses` (jsonb), admin-built via catalog-item create/update routes. The R1 custom-field mechanism for requests exists. |
| 3 | **Pending → SLA pause** (per sub-reason toggle) | INC-006, SLA-005, ADR-012 | ✅ Implemented (settings) | Registry key `incident.pending_sla_pause` (per-sub-reason boolean map); incident service consults it before pausing the clock. Edited as a per-reason switch list in `SettingsView.vue`. |
| 4 | **Intake mode per service** (L1 triage-first vs auto-route) | INC-011, ADR-012 | ✅ Implemented (column) | `services.intake_mode` enum (`triage_first` default / `auto_route`), migration `0014`. `resolveRouting` returns it; `incident.create` auto-routes only when `auto_route` (or an automated source forces it), else holds in L1. Editable on the service create form; shown on service detail. Grafana mapped alerts pass `forceAutoRoute` to route directly (INC-023). |
| 5 | **Auto-close window** | INC-019, REQ-008, ADR-012 | ✅ Implemented (settings) | Registry key `incident.auto_close_business_days` (1–30); read by the incident service when scheduling auto-close. Edited as a numeric stepper in `SettingsView.vue`. |
| 6 | **Reopen window** | INC-020, ADR-012 | ✅ Implemented (settings) | Registry key `incident.reopen_window_business_days` (1–30); enforced on the reopen path (`incident/service.ts`). |
| 7 | **Grafana severity → priority map** | INC-023, INT, ADR-012 | ✅ Implemented (CRUD) | Admin routes `GET /integrations/grafana/map`, `PUT`/`DELETE /integrations/grafana/severity-map/:severity`, `POST`/`DELETE /integrations/grafana/label-map[/:id]` (settings:manage, audited in-transaction). Admin UI in `IntegrationsView.vue`. The webhook handler reads the live tables. |
| 8 | **Category / subcategory taxonomy** | INC-010, ADR-012 | ✅ Implemented (CRUD) | Added `PATCH`/`DELETE /categories/:id` with rename + delete; delete is blocked (`CATEGORY_IN_USE`) when catalog items or subcategories reference it. Inline edit/delete in the Catalog → Categories tab. |

### Other spec-level configurables

| # | Configurable surface | Req IDs | Status | Evidence |
|---|---|---|---|---|
| 9 | **Session idle / absolute timeout** | AUTH-004 | ⚠️ Env-only | `SESSION_IDLE_TIMEOUT_SECONDS` (28800) + `SESSION_ABSOLUTE_TIMEOUT_SECONDS` (86400) in `packages/config/src/env.ts:29-30`. Deploy-time configurable, **not** admin-runtime configurable. Spec is "Should". |
| 10 | **CMDB cert-expiry thresholds (30/14/7) + optional auto-incident** | CMDB-012 | ⚠️ Partial (settings) | **Thresholds done:** registry key `cmdb.cert_expiry_threshold_days` (sorted int set, default `[30,14,7]`); `GET /cmdb/certs/expiring` defaults its window to the widest configured threshold via `settings.getValue(...)`. Edited as a removable-chip list in `SettingsView.vue`. **Auto-incident still open:** raising an incident on expiry needs a recurring job + an incident cross-dependency, so that toggle was deliberately not added as an unused setting. |
| 11 | **CMDB verification interval** (default quarterly) | CMDB-015 | ✅ Implemented (settings) | Registry key `cmdb.verification_interval_days` (1–365, default 90); `GET /cmdb/stale` falls back to it when no `days` override is given (`cmdb/service.ts`, `cmdb/routes.ts`). |
| 12 | **CMDB impact-analysis depth** | CMDB-007 | ✅ Per-request | Query param, default 3 / max 10 (`cmdb/schema.ts:46`, `cmdb/routes.ts:77`). Caller-configurable as the spec intends. |
| 13 | **KB review gate** (default off / self-publish) | KB-002 | ✅ Implemented (settings) | Registry key `knowledge.review_gate_enabled` (default `false`). When on, `publish()` rejects a direct draft→published transition and forces the submit-for-review → approve path (`knowledge/service.ts`). Edited as a self-publish/review-required toggle in `SettingsView.vue`. |
| 14 | **KB periodic review interval** | KB-012 | ✅ Implemented (settings) | Registry key `knowledge.review_interval_days` (1–365, default 180). On publish/approve, `kb_articles.review_due_at` is stamped `now + interval`; `reviewDueAt` is now on the article record. (Overdue-flagging *job* still TODO, but the configurable interval + due date are wired.) |
| 15 | **Change risk matrix / questionnaire** | CHG-007 | ✅ Implemented (settings) | Registry key `change.risk_matrix` (`{ questions[], thresholds }`). `change.assess` reads it and feeds `computeRiskLevel(answers, questions, thresholds)`; manual override still wins. Edited as a per-answer weight grid + score thresholds in `SettingsView.vue` (`risk-matrix` control). |
| 16 | **Change freeze windows** | CHG-011 | ✅ Implemented | `change_freeze_windows` table + `createFreezeWindow` service + `POST`/`GET /changes/freeze-windows` routes; overlap-blocking with emergency override (`change/service.ts:470-488`). |
| 17 | **SLA response-clock stop event** (per policy) | SLA-012 | ✅ Implemented | `sla_policies.response_stop_event` column default `first_response_at` (`packages/db/src/schema/sla.ts:67`), settable via policy schema. |
| 18 | **SLA warning threshold** (per policy) | SLA-006 | ✅ Implemented | `warning_threshold_pct` on SLA policy schema/types (`sla/schema.ts:36`). |
| 19 | **Resolution codes** (editable set) | INC §11 | 🔒 Fixed by decision | Kept as the `resolutionCodeEnum` pgEnum. Decided NOT admin-configurable: a stable controlled vocabulary that reporting + the auto-resolve path rely on; changing it is a schema/enum migration, not a setting. See the scoreboard note and the comment in `packages/shared/src/incident/enums.ts`. |
| 20 | **Incident auto-resolve on alert clear** | INC-024, INT-003 | ✅ Implemented (settings) | Registry key `incident.alert_auto_resolve` (default `true`); the Grafana webhook handler reads `settings.getValue('incident.alert_auto_resolve')` at clear time instead of a static config flag. The `autoResolve` field was removed from `IntegrationConfig`. Edited as a toggle in `SettingsView.vue`. |

---

## Recommended remediation (priority order)

1. ~~**Add a settings store.**~~ **Done** — see the update note above. Every scalar/matrix/toggle/list configurable now lives on it, schema-validated and audited.
2. ~~Priority matrix, Pending→SLA-pause, Auto-close, Reopen~~ **done (settings)**. **Still hardcoded and needing their own storage/migration:**
   - **Resolution codes (INC §11)** — currently a `pgEnum`; an editable set needs a `resolution_codes` table (or seeded settings list) + a migration changing `incidents.resolution_code` from enum to a text/FK column. The riskiest item (data migration on existing rows).
   - **Change risk matrix (CHG-007)** — make `computeRiskLevel`'s scoring weights/questionnaire a registry key (e.g. `change.risk_matrix`) read by the change service; manual override already exists.
   - **Intake mode per service (INC-011)** — per-service, not global: add an `intake_mode` column to `services` + expose it on the catalog/service admin forms; route incident intake through it.
3. **Incident custom fields (INC §16)** — needs a field-definition table + per-incident value storage; mirror the request `form_schema` pattern. Largest single item (storage + validation + form builder UI + render).
4. **Add admin CRUD where storage already exists** — Grafana severity map (#7), category taxonomy update/delete (#8). These don't fit the settings registry (they're row collections); they need their own admin routes + views.
5. **CMDB cert auto-incident (#10)** — add the recurring cert-expiry check job; when a cert crosses its earliest threshold, notify the owner and (behind a new `cmdb.cert_auto_incident` toggle) raise an incident. Deferred because it needs a job + an incident cross-dependency, not just a setting.
6. **Should-priority** — session-timeout admin override (AUTH-004); KB overdue-flagging job tied to the now-configurable `knowledge.review_interval_days`.

---

## What's correctly configurable today (no action)

Request custom fields, SLA per-policy stop-event + warning threshold, Change freeze windows, CMDB impact depth, **and everything now on the settings store** (incident priority matrix / pending-pause / auto-close / reopen / alert-auto-resolve, KB review gate + interval, CMDB verification interval + cert-expiry thresholds). The settings registry (`packages/shared/src/settings/index.ts`) is now the model to copy: add a key + TypeBox schema + `control` hint, read it via `SettingsReader.getValue(...)`, and the admin UI renders it generically.
