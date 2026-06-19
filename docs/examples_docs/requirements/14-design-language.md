# 14 — Design Language

| | |
|---|---|
| Status | Draft |
| Last updated | 2026-06-13 |
| Owner | Iqbal (Infokes) |

The visual language for **Infokes ITSM**. Implements the frontend stack in [ADR-009](../adr/009-tech-stack.md) (Tailwind 4, Iconify, PrimeVue 4 unstyled) and the frontend conventions in [13 §8](13-engineering-standards.md).

## 1. Principles

An all-day operations tool: agents live in queues, tables, and dashboards. Therefore — **calm, low-fatigue, information-dense but readable, professional and trustworthy** (health-tech, ISO 27001). Neutral-forward with one confident accent. Reference feel: Linear's restraint + Jira SM's function.

## 2. Brand & Identity

- **Name:** Infokes ITSM. Wordmark: "Infokes" in bold slate, "ITSM" in teal/lighter weight.
- **Logo:** **Concept 2 — pulse mark** (rounded tile + heartbeat line) in pine-teal. Final assets in [`docs/design/brand/`](../design/brand/index.html): `mark.svg`, `mark-on-dark.svg`, `favicon.svg`, `lockup.svg`, `lockup-on-dark.svg`. The mark is **pine `#0F766E` on light / teal `#2DD4BF` on dark** and stays pine/teal regardless of the user's chosen UI palette. Favicon (32/16px) and app icons build from `favicon.svg`.
- Assets live in `docs/design/` (source SVG) → built to `apps/web/public` (favicon, PWA icons).

| ID | Requirement | Priority |
|---|---|---|
| DES-001 | Pulse-mark logo + wordmark lockup (pine-teal); light/dark variants | Must |

## 3. Color System

Three token layers: **primitive** (raw scale) → **semantic** (role) → **component**. Only semantic/component tokens are used in app code. A **theme** is a `palette × mode` pair (§6).

### Default palette — Warm Sand + Pine

Editorial-warm neutrals with a deep pine-teal accent (low-fatigue, human, distinct from cool admin tools).

**Accent — Pine/Teal:** `#5EEAD4` `#2DD4BF` `#14B8A6` `#0F766E` (base) `#115E59`.
**Neutrals — Warm Stone:** `#F7F3EC` `#E7E0D5` `#A8A29E` `#78716C` `#44403C` `#292420` `#1C1917`.

**Semantic tokens (light / dark):**

| Token | Light | Dark |
|---|---|---|
| `--color-bg` | `#F7F3EC` | `#1C1917` |
| `--color-surface` | `#FFFDF9` | `#292420` |
| `--color-border` | `#E7E0D5` | `#44403C` |
| `--color-text` | `#1C1917` | `#EDE7DD` |
| `--color-text-muted` | `#78716C` | `#A8A29E` |
| `--color-primary` | `#0F766E` | `#2DD4BF` |
| `--color-on-primary` | `#FFFFFF` | `#1C1917` |
| `--color-focus` | `#0F766E` | `#2DD4BF` |

### Selectable palette themes

Users may switch palette (each defines its own semantic tokens for light + dark). Status/priority colors (§4) are **fixed across all palettes**. Full token sets live in the theme files; summary:

| Palette | Primary (light / dark) | Neutral family | |
|---|---|---|---|
| **Warm Sand + Pine** | `#0F766E` / `#2DD4BF` | warm stone | **default** |
| Teal + Slate | `#0D9488` / `#2DD4BF` | cool slate | |
| Midnight Indigo | `#4F46E5` / `#818CF8` | indigo-slate (dark-leaning) | |
| Mono + Electric Blue | `#2563EB` / `#3B82F6` | neutral grey | |
| Royal Violet | `#7C3AED` / `#A78BFA` | violet-tinted | |

Live previews: [`docs/design/palette-options/index.html`](../design/palette-options/index.html).

| ID | Requirement | Priority |
|---|---|---|
| DES-002 | Primitive→semantic→component token layers; app code uses semantic/component tokens only | Must |
| DES-003 | Default palette Warm Sand + Pine; four additional user-selectable palettes; semantic tokens defined per palette × mode | Must |
| DES-017 | Status/priority colors are fixed across all palettes | Must |

## 4. Status & Priority Colors

Encoded **with an icon + label, never color alone** (colorblind safety). Each has tuned light/dark variants meeting §7 contrast.

| Meaning | Base | Note |
|---|---|---|
| P0 Critical | red `#DC2626` | + filled icon |
| P1 High | orange `#EA580C` | |
| P2 Medium | amber `#CA8A04` | watch contrast on light |
| P3 Low | slate/blue | de-emphasized |
| Resolved / success | green `#16A34A` | |
| SLA warning | amber `#D97706` | |
| Pending | slate | neutral |
| Info | blue `#2563EB` | |

| ID | Requirement | Priority |
|---|---|---|
| DES-004 | Status/priority always conveyed by icon + label + color, never color alone | Must |
| DES-005 | Status palette has accessible light/dark variants | Must |

## 5. Typography

- **UI font: Inter**; **mono: Geist Mono** (ticket numbers, IDs, logs, code).
- **Set globally** on `:root`/`body` via Tailwind 4 `@theme` and base element styles — **never per-element font declarations** in component classes.
- **Scale (px):** 12, 14, 16, 18, 20, 24, 30. **Base 14** for dense views (tables, forms); 16 for reading views.
- **Weights:** 400 / 500 / 600 / 700. **Line-height:** 1.5 body, 1.25 headings.

| ID | Requirement | Priority |
|---|---|---|
| DES-006 | Inter (UI) + Geist Mono (code/IDs); fonts/typography set globally, not per element | Must |
| DES-007 | Defined type scale, weights, and line-heights as tokens | Must |

## 6. Theming

A theme is a **`palette × mode`** pair.

- **Palette:** user-selectable from the registry (§3); **default Warm Sand + Pine**.
- **Mode:** **light / dark / system**; **system default**.
- Both choices **persisted** (localStorage + user profile); applied instantly with no reload; `system` mode follows `prefers-color-scheme`.
- Tokens are **CSS custom properties**; Tailwind 4 `@theme` maps to them; **PrimeVue passthrough reads the same tokens** so widgets retheme automatically — no parallel theme config per palette.

| ID | Requirement | Priority |
|---|---|---|
| DES-008 | Theme = palette × mode; palette default Warm Sand + Pine, mode default system; both user-selectable and persisted | Must |
| DES-009 | Single token source drives Tailwind and PrimeVue (passthrough) across all palettes | Must |

## 7. Accessibility

- **WCAG 2.1 AA minimum:** 4.5:1 body text, 3:1 large text / UI components — **both themes**. **Aim AAA (7:1)** for primary body text. This is the formal "no low-contrast" rule.
- **Visible focus** ring (`--color-focus`, 2px + offset) on all interactive elements.
- **Color never the sole indicator** (§4).
- Min hit target ~32px; honor `prefers-reduced-motion`; full keyboard navigability.

| ID | Requirement | Priority |
|---|---|---|
| DES-010 | All text/UI meets WCAG AA contrast in both themes; AAA for body text | Must |
| DES-011 | Visible focus states; keyboard navigable; reduced-motion honored | Must |

## 8. Spacing, Density & Layout

- **Spacing scale:** Tailwind default 4px base (4/8/12/16/24/32…).
- **Density:** **compact default** (table row ~36px), with an optional **comfortable** mode (~44px) — user-selectable.
- **Fully responsive.** Desktop-optimised (agents live on desktop) but **smoothly operable on tablets and phones** — no horizontal page scroll, no clipped controls, all primary flows usable down to a 360px viewport. Breakpoints: the persistent sidebar collapses to an off-canvas drawer (hamburger) below `lg` (1024px); dense data tables degrade gracefully on narrow screens (horizontal scroll within the table, or a stacked card layout per view); hit targets and tap spacing respect §7 on touch. Test light/dark at phone, tablet, and desktop widths.

| ID | Requirement | Priority |
|---|---|---|
| DES-012 | 4px spacing scale; compact default density with optional comfortable mode | Must |
| DES-021 | Fully responsive; primary flows usable down to 360px (no h-scroll/clipping); sidebar→drawer below `lg`; tables degrade to scroll or stacked cards on narrow screens | Must |

## 9. Component Language

- **Radius:** controls 8px, cards 14px, pills full.
- **Elevation:** subtle shadows in light; in dark, lean on borders/surface contrast (shadows read poorly).
- **Interactive states:** every control has rest / hover / active / focus / disabled defined via tokens.
- Built from Tailwind for layout + PrimeVue unstyled for complex widgets, themed through tokens ([ADR-009](../adr/009-tech-stack.md)).

| ID | Requirement | Priority |
|---|---|---|
| DES-013 | Defined radius/elevation; all controls specify rest/hover/active/focus/disabled | Must |

## 10. Iconography

- One set: **Lucide** (line, ~1.5px stroke) via Iconify + `@iconify/tailwind`. Consistent sizing (16/20/24).

| ID | Requirement | Priority |
|---|---|---|
| DES-014 | Single icon set (Lucide) via Iconify; consistent sizes | Must |

## 11. Styling Conventions

- **Tailwind is the styling source of truth.** Global tokens/typography in `@theme` and base layers — not in component classes ([DES-006](#5-typography)).
- **`@apply` for repeated semantic component classes** (`.btn`, `.btn-primary`, `.card`, `.field`, `.badge-priority`) to remove duplication; tokens via CSS vars. One-off layout stays inline utilities. Don't `@apply` long opaque utility lists.

| ID | Requirement | Priority |
|---|---|---|
| DES-015 | Repeated patterns become `@apply`-composed semantic classes; one-offs stay inline | Must |
| DES-016 | No per-element font/color literals; use tokens | Must |

## 12. Imagery & Assets

- **Final brand assets** (Concept 2, pine-teal) in [`docs/design/brand/`](../design/brand/index.html); favicon + PWA/app icons build from `favicon.svg` into `apps/web/public`.

| ID | Requirement | Priority |
|---|---|---|
| DES-018 | Favicon/app icons built from `favicon.svg`; brand mark stays pine/teal across all UI palettes | Must |

### Empty States

Every no-data view is a deliberate panel, never a blank area. **Pattern: icon + heading + one-line subtext + primary action** (action where one applies). States to cover:

- empty queue / list (no tickets for a group or filter)
- no search results (KB, ticket/CI search)
- first-run (no services, no CIs, no problems yet)
- filtered-to-nothing
- "all caught up" (no notifications) — a positive empty state
- 404 / permission-denied / error

**Style:** line-based spot graphics consistent with the Lucide icons and the pulse mark; **pine accent + warm-stone neutrals only**, no full-colour illustrations. **Default treatment** is a large Lucide icon + copy + action (no custom illustration). Bespoke/recoloured line illustrations are reserved for marquee moments (first-run onboarding, "all caught up").

| ID | Requirement | Priority |
|---|---|---|
| DES-019 | Empty states use icon + heading + subtext + action; cover queue/search/first-run/filter/all-caught-up/error | Must |
| DES-020 | Empty-state graphics are line-based in pine/stone; routine states use a Lucide icon, not an illustration | Must |

## 13. Onboarding & Guided Walkthroughs (Coach Marks)

ITSM/ITIL is a **new process at Infokes**; the product must teach itself so no workshops or trainer time are needed. **Every page — existing and future — ships an in-app guided walkthrough** ("coach marks") so a first-time user can operate it with zero prior training.

- **Coach marks** = a stepped overlay that spotlights one target element at a time with a tooltip (title + plain-language body), step progress (e.g. "2 / 5"), and **Back / Next / Skip**. The walkthrough scrolls each target into view and dims the rest of the screen.
- **First-run + repeatable:** a page's walkthrough auto-runs once per user on first visit (a per-user "seen" flag, persisted), and is **repeatable any time from the user menu** (top-right) — "Guided walkthrough".
- **Bilingual (English + Bahasa Indonesia):** every coach-mark step is authored in both languages, with a **language toggle inside the coach-mark UI**. The choice is a **global preference** — switching language in one coach mark applies to all subsequent coach marks (persisted).
- **Jargon stays English in Bahasa Indonesia copy:** ITSM/ITIL domain terms are **not translated** (e.g. *Incident, Problem, Change, Service Request, Major Incident, Change Enablement, SLA, CMDB, CI, Knowledge Base, catalog*) so meaning is not lost; only the connective prose is in Indonesian.
- **Styling & a11y:** coach marks use the design tokens (pine-accent spotlight ring echoing the pulse mark, `surface` tooltip card) and stay consistent across all palettes/modes. Keyboard-navigable (Esc skips, arrows/Enter advance), focus managed, honors `prefers-reduced-motion`. On narrow screens where a target is off-canvas (e.g. the drawer nav), the step degrades to a centered card rather than a broken spotlight (§8 / DES-021).

| ID | Requirement | Priority |
|---|---|---|
| DES-022 | Every page (existing + future) ships an in-app guided coach-mark walkthrough; auto-runs once per user per page, repeatable from the user menu. No external training required | Must |
| DES-023 | Coach marks spotlight a target + stepped tooltip (title, body, progress, Back/Next/Skip); scroll-into-view, keyboard-navigable, focus-managed, honor reduced-motion; degrade to centered card when target is unavailable | Must |
| DES-024 | Coach marks support English + Bahasa Indonesia with a language toggle inside the coach-mark UI; the choice is a persisted global preference applied to all subsequent coach marks | Must |
| DES-025 | In Bahasa Indonesia copy, ITSM/ITIL domain jargon stays in English to avoid mistranslation | Must |
| DES-026 | Coach-mark visuals use design tokens (pine spotlight, surface card), consistent across palettes/modes; per-user "seen" state persisted | Must |

## 14. Open Questions

- [ ] Sourcing of the few marquee empty-state illustrations — bespoke SVG vs a recolourable open set (e.g. unDraw). Pattern + style fixed (§12); decide at build.
- [ ] Whether coach-mark "seen" state and language also sync to the server user profile (like theme) or stay local-only — local for now; revisit when a prefs endpoint exists.

Resolved: **logo = Concept 2 pulse mark** (pine-teal; assets in `docs/design/brand/`); **UI font Inter, mono Geist Mono**; **palette = Warm Sand + Pine default** + four user-selectable palettes; status colors fixed across all (§3, §6).
