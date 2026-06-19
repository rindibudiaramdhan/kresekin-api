# 13 — Engineering Standards

| | |
|---|---|
| Status | Draft |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

Code-level conventions for a production-grade, maintainable codebase. Complements [01-architecture-nfr.md](01-architecture-nfr.md) (system shape) and [ADR-009](../adr/009-tech-stack.md) (stack). The builder is an AI agent; these rules are mechanical and enforced in CI where possible.

## 1. Monorepo

**Bun's built-in workspaces** — single lockfile, hoisted deps, cross-package tasks via `bun run --filter`. No separate monorepo task runner (no Turborepo/Nx).

```
apps/
  api/    Elysia backend. Modular monolith: src/modules/<practice>/{routes,service,repository,schema}.ts
          Runs as `web` or `worker` by entrypoint only (one image, ENV-001).
  web/    Vue 3 SPA (Vite, Tailwind 4, Iconify, PrimeVue 4).
packages/
  shared/ TypeBox schemas + derived types shared FE↔BE; domain enums; error codes.
  db/     Drizzle schema + migrations; the DB client factory.
  config/ shared tsconfig base, ESLint config, env schema.
```

| ID | Requirement | Priority |
|---|---|---|
| ENG-001 | Bun-workspace monorepo with the apps/packages layout above; cross-package tasks via `bun run --filter` (no separate task runner) | Must |
| ENG-002 | `api` produces one image run as web or worker by entrypoint (no duplicate build) | Must |
| ENG-003 | No app imports another app; cross-cutting code lives in `packages/*` | Must |

## 2. Backend Layering

Strict **controller → service → repository**, one direction, no skipping.

| Layer | Does | Must not |
|---|---|---|
| **Controller** (Elysia route) | parse/validate input (TypeBox), authn/authz check, call one service, map result/error to HTTP | contain business logic or touch the DB |
| **Service** | business logic, orchestration, transactions, cross-module calls via interfaces | know about HTTP or raw SQL |
| **Repository** | data access via Drizzle; returns domain objects | contain business rules |

- A **module** = `src/modules/<practice>/` with `routes.ts`, `service.ts`, `repository.ts`, `schema.ts` (TypeBox), `types.ts`.
- **Cross-module access only through a module's exported service interface** ([01 ARCH-001](01-architecture-nfr.md)); never import another module's repository or reach into internals.
- The shared work-item base ([ADR-013](../adr/013-shared-work-item-base.md)) is its own module that incident/request/problem/change services compose.

| ID | Requirement | Priority |
|---|---|---|
| ENG-004 | Controller→service→repository layering; each layer's responsibilities enforced | Must |
| ENG-005 | Modules expose a service interface; cross-module calls go through it only | Must |
| ENG-006 | Controllers are thin: validate, authorize, delegate, map — no logic/DB | Must |

## 3. SOLID in Practice

- **SRP** — thin controllers; one service method = one use case; repositories only persist.
- **OCP** — extend via interfaces, not edits: notification channel adapters ([ADR-004](../adr/004-notification-adapter-defer-whatsapp.md)), CI-class/attribute config ([ADR-012](../adr/012-configuration-driven-design.md)), approval-flow strategies.
- **LSP** — adapter implementations (Email/Mattermost) are substitutable behind the channel interface.
- **ISP** — narrow interfaces (a service depends on `IncidentRepository`, not a god-repo).
- **DIP** — services depend on **repository/adapter interfaces**, not concretes.

**Dependency injection: explicit constructor injection + a composition root.** No DI-container magic. Each module exports a factory that wires concretes; the composition root (per app entrypoint) assembles them. Tests inject fakes/mocks directly.

```ts
// service depends on an interface, injected
class IncidentService {
  constructor(private readonly repo: IncidentRepository,
              private readonly notifier: Notifier) {}
}
```

| ID | Requirement | Priority |
|---|---|---|
| ENG-007 | Services depend on interfaces (repos, adapters); concretes wired at a composition root | Must |
| ENG-008 | No runtime DI container; explicit constructor injection | Must |

## 4. Type Safety

- **Single source of truth:** request/response and domain schemas defined once as **TypeBox** in `packages/shared`; TS types **derived** from them. No hand-written duplicate types.
- **End-to-end types:** the web app calls the API through the **Elysia Eden** typed client, type-checked against the server.
- **TS strict**, `noUncheckedIndexedAccess`, **no `any`** (lint-enforced).

| ID | Requirement | Priority |
|---|---|---|
| ENG-009 | Schemas defined once (TypeBox) in shared; types derived; shared FE↔BE | Must |
| ENG-010 | Web↔API calls go through the Eden typed client | Must |
| ENG-011 | TypeScript strict; `any` disallowed | Must |

## 5. Error Handling

- **Typed domain-error hierarchy** (e.g. `NotFoundError`, `ValidationError`, `ConflictError`, `ForbiddenError`, `DomainRuleError`) with stable machine-readable `code`s (in `packages/shared`).
- A single error-mapping middleware converts domain errors → a **consistent API envelope** and HTTP status:

```json
{ "error": { "code": "INCIDENT_NOT_FOUND", "message": "…", "details": {} } }
```

- Exceptions for exceptional paths (not Result types); never throw strings; never leak stack traces/SQL to clients.

| ID | Requirement | Priority |
|---|---|---|
| ENG-012 | Typed domain-error hierarchy with stable codes | Must |
| ENG-013 | Central error→HTTP mapping; consistent `{error:{code,message,details}}` envelope | Must |
| ENG-014 | No internal details (stack/SQL) leaked to clients | Must |

## 6. Database

- **Repository pattern over Drizzle:** services depend on repository **interfaces**; Drizzle implementations live in the repo layer.
- **Transactions are owned by the service layer** (a use case = a transaction); repositories accept a transaction handle.
- **Migrations:** Drizzle, **forward-only**, applied on deploy ([ENV-003](01-architecture-nfr.md)); never edit a shipped migration.
- Conventions: **UUIDv7** PKs ([DATA-001](01-architecture-nfr.md)), `created_at`/`updated_at` timestamptz, **soft-delete** for ticket/CI data ([DATA-005](01-architecture-nfr.md)), snake_case columns, no business logic in the DB.
- The **audit_log** write happens in the same transaction as its mutation ([01 §5](01-architecture-nfr.md)); the app DB role has no UPDATE/DELETE on it.

| ID | Requirement | Priority |
|---|---|---|
| ENG-015 | Repository interfaces wrap Drizzle; services never call Drizzle directly | Must |
| ENG-016 | Transactions controlled in the service layer; repos accept a tx handle | Must |
| ENG-017 | Forward-only migrations; shipped migrations never edited | Must |

## 7. API Conventions

- REST/JSON under `/api/v1` ([API-001](01-architecture-nfr.md)); resource-oriented plural nouns (`/incidents`).
- **List endpoints:** cursor or page pagination (default 25, max 100 — [PERF-002](01-architecture-nfr.md)), consistent `sort`, `filter`, `q` params.
- **Mutations** validated at the boundary (TypeBox); write endpoints support an idempotency key where retries matter (webhooks, jobs).
- **OpenAPI** generated from TypeBox ([API-002](01-architecture-nfr.md)).

| ID | Requirement | Priority |
|---|---|---|
| ENG-018 | Resource-oriented REST; consistent pagination/sort/filter conventions | Must |
| ENG-019 | Boundary validation via TypeBox; idempotency keys where retried | Must |

## 8. Frontend Architecture

- **Vue 3 Composition API**, `<script setup>`, TypeScript.
- **Feature-folder structure** mirroring backend modules (`src/features/<practice>/`): components, composables, store, routes.
- **State:** **Pinia** for app/UI state; **TanStack Query (vue-query)** for server state (caching, invalidation); the **Eden** client for typed calls. No ad-hoc fetch.
- **UI:** Tailwind 4 for layout/styling (source of truth); **PrimeVue 4 unstyled** for complex widgets only; **Iconify** for icons ([ADR-009](../adr/009-tech-stack.md)).
- **Dynamic forms:** the request-catalog/CMDB form schemas ([05](05-service-request.md)/[09](09-cmdb.md)) render via a schema-driven form renderer.
- **Auth guards** on routes; permission checks mirror server (`practice:action`), but the **server remains authoritative** ([01 §4](01-architecture-nfr.md)).

| ID | Requirement | Priority |
|---|---|---|
| ENG-020 | Composition API + feature folders mirroring backend modules | Must |
| ENG-021 | Pinia (app state) + TanStack Query (server state) + Eden client; no ad-hoc fetch | Must |
| ENG-022 | Schema-driven renderer for dynamic forms | Must |
| ENG-023 | Route auth guards; server stays authoritative for permissions | Must |

## 9. Testing

Tests are the contract for an AI builder ([01 §13](01-architecture-nfr.md)).

| Level | Scope | Notes |
|---|---|---|
| **Unit** | services, pure logic | repos/adapters mocked via interfaces |
| **Integration** | repositories, module wiring | **real Postgres** ([TEST-002](01-architecture-nfr.md)), no mocks for SQL |
| **E2E** | golden paths | Playwright ([TEST-003](01-architecture-nfr.md)) |

- Every requirement ID maps to ≥1 test ([TEST-001](01-architecture-nfr.md)).
- Shared **factories/fixtures** for entities; tests isolated (transaction rollback or schema-per-test).

| ID | Requirement | Priority |
|---|---|---|
| ENG-024 | Unit (mocked deps) + integration (real Postgres) + E2E layering | Must |
| ENG-025 | Test factories/fixtures; isolated test data | Must |

## 10. Code Quality

- **ESLint + Prettier**; TS strict; **`any` banned**.
- **Import-boundary lint** enforcing module isolation and layer direction (controller→service→repo; no cross-module internals) — encodes [ARCH-001](01-architecture-nfr.md)/ENG-005.
- **Git hooks (lefthook):** lint-staged + typecheck on commit; full suite in CI.
- **Conventional Commits**; commit messages explain *why*.
- Naming: `kebab-case` files, `PascalCase` types/components, `camelCase` vars; one public concept per file.
- **Self-documenting code:** expressive names and small functions over comments. Write a comment **only** for a non-obvious *why* or a constraint the code cannot express — never to restate what the code already says, nor to record authorship/history. No commented-out code; no redundant docblocks. Public package APIs may carry a brief TSDoc summary.

| ID | Requirement | Priority |
|---|---|---|
| ENG-026 | ESLint/Prettier/strict TS enforced in CI; `any` banned | Must |
| ENG-027 | Import-boundary lint enforcing module + layer isolation | Must |
| ENG-028 | lefthook pre-commit (lint-staged, typecheck); Conventional Commits | Must |
| ENG-034 | Self-documenting code; comments only for non-obvious why/constraints; no commented-out code or redundant docblocks | Must |

## 11. Configuration & Secrets

- **12-factor:** all config via environment ([ENV-001](01-architecture-nfr.md)).
- **Env validated at startup** against a schema in `packages/config`; the app refuses to boot on invalid/missing config.
- **No secrets in the repo**; sourced from the secret manager / GitLab CI variables ([01 §9](01-architecture-nfr.md)).

| ID | Requirement | Priority |
|---|---|---|
| ENG-029 | Schema-validated env at startup; fail fast on misconfig | Must |
| ENG-030 | No secrets committed; injected at runtime/CI | Must |

## 12. Observability in Code

- **Structured (JSON) logging** with a correlation/request ID.
- The request ID **is the OTel trace ID and the `audit_log.request_id`** ([01 §5](01-architecture-nfr.md)) — logs, traces, and audit entries correlate.
- OTel instrumentation at controller, service, repository, and job boundaries; spans named consistently.

| ID | Requirement | Priority |
|---|---|---|
| ENG-031 | Structured logging keyed by request id = OTel trace id = audit request_id | Must |
| ENG-032 | OTel spans at controller/service/repo/job boundaries | Must |

## 13. Git Workflow

- **Trunk-based** with short-lived feature branches; **small, reviewable PRs** (the single reviewer + AI builder model — [12](12-release-plan.md)).
- CI gate per PR: lint, typecheck, unit + integration tests, dependency audit, build ([ENV-004](01-architecture-nfr.md)).
- Merge only on green CI + review.

| ID | Requirement | Priority |
|---|---|---|
| ENG-033 | Trunk-based, small PRs; merge only on green CI + review | Must |

## 14. Open Questions

_None outstanding._ Resolved:

- **Pagination:** **cursor-based** default for large/append-only sets (incidents, requests, audit log, CIs); **offset** allowed for small bounded admin lists (catalog, users, groups).
- **Schema library:** **TypeBox end-to-end** (Elysia-native, required by Eden); no Zod — one library, no dual maintenance.
- **Monorepo tasks:** Bun-native (`bun run --filter`); no Turborepo/Nx. A dedicated task runner is reconsidered only if build orchestration outgrows Bun scripts.
- **Coverage:** requirement-ID→test mapping ([TEST-001](01-architecture-nfr.md)) is primary; a **80% line-coverage floor** enforced in CI as a guardrail.
