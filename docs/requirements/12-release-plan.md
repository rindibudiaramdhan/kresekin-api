# Release Plan Kresekin API

Dokumen ini mengusulkan urutan delivery berdasarkan fitur yang sudah ada di codebase dan requirement yang masih terbuka.

## Release Overview

| Release | Fokus | Tujuan |
| --- | --- | --- |
| R1 | Stabilization & Contract Baseline | Rapikan kontrak API aktif, auth/session, role guard, docs, dan test regression |
| R2 | Agent/Seller Operational Completion | Lengkapi agent registration review, seller operation, dashboard agent/seller |
| R3 | Finance Workflow Hardening | Perkuat withdrawal, buyer payment confirmation, seller disbursement, audit, aging report |
| R4 | Integration & Laravel Cloud Production Readiness | Provider OTP/payment/payout, Laravel Cloud setup, storage production, observability, backup/DR |

## R1 — Stabilization & Contract Baseline

Scope:

1. Finalisasi `API_DOCUMENTATION.md` sesuai route aktif.
2. Pastikan semua endpoint role-specific memakai `session.token` dan `role:*`.
3. Tambahkan/rapikan test unauthorized dan forbidden untuk endpoint penting.
4. Stabilkan response format list/detail/error.
5. Rapikan requirement docs bernomor.
6. Pastikan upload dan secret tidak bocor di response/log.

Exit criteria:

1. `php artisan test` lulus.
2. Endpoint aktif terdokumentasi.
3. Tidak ada endpoint role-specific tanpa middleware.
4. Regression test ada untuk auth, role guard, cart/checkout, seller product/order, agent dashboard/withdrawal, finance workflow.

## R2 — Agent/Seller Operational Completion

Scope:

1. Finalisasi policy agent `pending_review`.
2. Tambahkan review status agent bila belum ada endpoint/admin flow.
3. Lengkapi agent dashboard berdasarkan data real.
4. Lengkapi seller dashboard endpoint dan web seller flow yang dibutuhkan.
5. Kuatkan ownership check seller tenant/product/order.
6. Rapikan product image lifecycle.

Exit criteria:

1. Agent dapat register, verify OTP, login, melihat dashboard/profil/UMKM, dan mengajukan withdrawal sesuai policy.
2. Seller dapat mengelola tenant/product/order tanpa akses silang.
3. Dashboard agent dan seller memiliki test data-scope.

## R3 — Finance Workflow Hardening

Scope:

1. Withdrawal review approve/reject/paid dengan state guard lengkap.
2. Konfirmasi buyer payment dan seller disbursement dengan audit actor/timestamp.
3. Finance dashboard dan list transaksi/disbursement dengan filter status/periode.
4. Aging report untuk withdrawal dan disbursement pending.
5. Master cancellation reason governance.

Exit criteria:

1. Finance action tidak bisa dilakukan dari status invalid.
2. Semua aksi finance high-risk memakai transaksi database.
3. Test database memastikan amount, status, actor, dan timestamp benar.

## R4 — Integration & Production Readiness

Scope:

1. Provider OTP production.
2. Payment/payout provider bila sudah dipilih.
3. S3/private storage production.
4. Queue worker dan failed job monitoring.
5. Backup/restore drill dan runbook.
6. Observability untuk latency, error, queue, upload, dan provider failure.
7. Laravel Cloud environment, build/deploy command, database resource, queue, scheduler, object storage, domain/TLS, dan log access.

Exit criteria:

1. Secret hanya berasal dari environment/secret manager.
2. Provider integration idempotent dan teruji.
3. Healthcheck, backup, rollback, dan runbook siap untuk production.
4. Upload production memakai durable object storage, bukan local application disk.
5. Queue worker dan scheduled task berjalan melalui mekanisme yang kompatibel dengan Laravel Cloud.
6. Deployment Laravel Cloud memiliki rollback/mitigation path dan migration strategy yang terdokumentasi.

## Cross-Release Dependencies

1. Agent policy harus selesai sebelum membuka fitur finance/withdrawal sensitif.
2. Formula komisi harus final sebelum reporting finance dianggap stabil.
3. Payment/payout provider harus dipilih sebelum otomasi paid/disbursed.
4. Audit trail formal sebaiknya disiapkan sebelum finance workflow production penuh.
5. Object storage production harus siap sebelum membuka upload dokumen atau bukti pembayaran di production.
6. Laravel Cloud plan/resource production harus dipilih sebelum menetapkan target availability, log retention, dan scaling final.

## Open Questions

1. Release mana yang menjadi target production pertama?
2. Apakah web dashboard termasuk deliverable production atau hanya prototype internal?
3. Apakah audit table formal masuk R1 atau R3?
4. Laravel Cloud plan mana yang menjadi target production pertama?
5. Apakah staging memakai Laravel Cloud juga atau environment non-production lain?
