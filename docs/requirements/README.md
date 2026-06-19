# Kresekin API Requirements

Dokumen bernomor untuk requirement Kresekin API. Seri ini mengikuti pembagian topik dari `docs/examples_docs/requirements`, tetapi isi dan prioritasnya disesuaikan dengan codebase Laravel Kresekin API yang aktif.

| Dokumen | Scope |
| --- | --- |
| [00 — Vision & Scope](00-vision-scope.md) | visi produk, batas scope, persona, risiko, dan pertanyaan terbuka |
| [01 — Architecture & NFR](01-architecture-nfr.md) | arsitektur Laravel modular monolith, API, auth, audit, performa, DR, security |
| [02 — Roles & Permissions](02-roles-permissions.md) | role buyer/seller/agent/finance, data scoping, ownership, lifecycle user |
| [03 — Catalog & Master Data](03-service-catalog.md) | tenant, produk, kategori, satuan, metode pembayaran/pengiriman, promo |
| [04 — Transaction & Order Management](04-incident-mgmt.md) | cart, checkout, transaksi, status order, histori, pembatalan |
| [05 — Agent & Seller Operations](05-service-request.md) | registrasi agent, UMKM binaan, seller tenant/product/order workflow |
| [06 — Dashboard & Reporting](06-knowledge-mgmt.md) | dashboard agent/seller/finance, metrik, filter periode, data table |
| [07 — Commission & Finance Workflow](07-problem-mgmt.md) | komisi agent, withdrawal, konfirmasi pembayaran, disbursement seller |
| [08 — Reliability & Metrics Engine](08-sla-engine.md) | target performa, availability, reliability metric, dashboard metric calculation |
| [09 — Data Model & Ownership](09-cmdb.md) | entity utama, relasi, source of truth, audit data quality |
| [10 — Operational Change & Review](10-change-enablement.md) | perubahan status sensitif, approval/reject, rollout schema/API |
| [11 — Integrations & Notifications](11-integrations.md) | OTP sender, BPS region, storage, future payment/payout/notification provider |
| [12 — Release Plan](12-release-plan.md) | urutan delivery dan exit criteria per release |
| [13 — Engineering Standards](13-engineering-standards.md) | standar implementasi, testing, review, dan definition of done |
| [14 — Design Language](14-design-language.md) | prinsip UI web/dashboard, warna, komponen, empty state, aksesibilitas |
| [99 — Glossary](99-glossary.md) | istilah domain, teknis, status, dan metrik |

## Referensi Tambahan

- [`API_DOCUMENTATION.md`](../../API_DOCUMENTATION.md) untuk endpoint aktif.
- [`docs/tasks/WEB_REGISTER_AGENT_REQUIREMENTS.md`](../tasks/WEB_REGISTER_AGENT_REQUIREMENTS.md) untuk detail web registration agent.
- [`docs/tasks/WEB_DASHBOARD_AGENT_REQUIREMENT.md`](../tasks/WEB_DASHBOARD_AGENT_REQUIREMENT.md) untuk detail dashboard agent.
- [`docs/tasks/endpoint_finance_dashboard.md`](../tasks/endpoint_finance_dashboard.md) untuk endpoint finance dashboard.
- [`docs/api/ADMIN_DASHBOARD_ANALYSIS.md`](../api/ADMIN_DASHBOARD_ANALYSIS.md) untuk analisis data dashboard.

## Cara Membaca

Mulai dari `00`, `01`, dan `02` untuk memahami scope, bentuk sistem, dan access control. Setelah itu baca dokumen domain sesuai fitur yang dikerjakan. Untuk perubahan endpoint public, selalu cocokkan dengan `API_DOCUMENTATION.md`.
