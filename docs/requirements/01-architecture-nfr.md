# Architecture and Non-Functional Requirements Kresekin API

Dokumen ini mendefinisikan kebutuhan arsitektur dan non-functional requirements untuk Kresekin API. Fokusnya adalah bentuk sistem, batas integrasi, keamanan, reliability, observability, data convention, dan standar delivery agar pengembangan fitur tetap konsisten, aman, dan dapat dioperasikan.

Dokumen ini melengkapi:

1. `docs/requirements/00-vision-scope.md` sebagai arah produk dan scope.
2. `docs/requirements/13-engineering_standard.md` sebagai standar implementasi harian.
3. `API_DOCUMENTATION.md` sebagai kontrak endpoint aktif.

## Architecture Shape

Kresekin API menggunakan arsitektur modular monolith berbasis Laravel. Semua domain utama tetap berada dalam satu codebase dan satu deployment unit, tetapi boundary domain harus dipisahkan melalui route group, controller, FormRequest, model, service, support class, dan test yang jelas.

### Requirements

1. Backend utama adalah Laravel API dengan PostgreSQL sebagai database production.
2. Sistem harus mempertahankan modular monolith sampai ada alasan operasional kuat untuk memecah service.
3. Boundary domain minimal meliputi auth/session, buyer, seller/UMKM, agent, finance, transaction/order, catalog/product, commission/withdrawal, attachment/media, dan master data.
4. Controller harus menjadi orchestration layer, bukan tempat utama business logic panjang.
5. Logic domain yang dipakai ulang harus ditempatkan di `app/Support`, `app/Services`, atau model bila benar-benar melekat ke data model.
6. Operasi yang menyentuh uang, stok, promo, transaksi, komisi, dan disbursement harus memakai transaksi database.
7. Dependency eksternal harus dibungkus service boundary agar mudah diganti, diuji, dan diberi fallback.
8. Sistem harus menghindari coupling langsung antara client dan struktur database internal.
9. Perubahan besar pada domain boundary harus dicatat sebagai requirement atau architecture decision baru sebelum implementasi.

### Target Shape

| Layer | Tanggung Jawab |
| --- | --- |
| Route | Prefix, middleware, role boundary, dan mapping endpoint ke controller. |
| Middleware | Authentication token, role guard, request boundary, dan cross-cutting control. |
| FormRequest | Validasi input, normalisasi request, dan business validation request-level. |
| Controller | Orkestrasi use case, response mapping, status code, dan error boundary. |
| Model | Relation, casts, constants, scope, dan domain helper yang melekat ke data. |
| Support/Service | Query aggregator, kalkulasi, integrasi, dan business capability yang reusable. |
| Database | Source of truth untuk transaksi, status, histori, token hash, dan audit record. |
| Queue/Jobs | Pekerjaan asynchronous, retryable, dan tidak wajib selesai dalam request utama. |

## API

API harus stabil untuk web, mobile, dan integrasi internal. Kontrak response lebih penting daripada preferensi implementasi internal.

### Requirements

1. Semua endpoint API public memakai prefix `/api`.
2. Endpoint baru harus mengikuti pola JSON response existing: minimal `message`, lalu `data` bila ada payload.
3. Endpoint list terpaginasikan harus memakai struktur `data`, `meta`, dan `links`.
4. Status HTTP harus semantik: `200`, `201`, `204`, `401`, `403`, `404`, `422`, dan `5xx` sesuai kasus.
5. Error validasi harus menggunakan status `422` dan struktur `message` plus `errors`.
6. Error auth harus konsisten untuk token kosong, token invalid, token expired, dan role tidak sesuai.
7. API harus memakai versioning strategy sebelum kontrak breaking change dilepas ke client production.
8. Field response yang sudah dipakai client tidak boleh dihapus, diganti nama, atau diganti tipe tanpa migrasi kontrak.
9. Field uang harus dikirim sebagai integer nominal mentah. Label display boleh ditambahkan sebagai field terpisah.
10. Timestamp penuh di response harus memakai ISO 8601.
11. Semua endpoint mutasi harus punya validasi input eksplisit.
12. Endpoint role-specific harus berada di prefix role atau group middleware role yang jelas.
13. API documentation harus diperbarui untuk endpoint baru, perubahan response, perubahan auth, atau business rule penting.

### Compatibility Rules

| Perubahan | Aturan |
| --- | --- |
| Menambah field optional response | Boleh, selama tidak mengubah makna field existing. |
| Mengubah nama field | Breaking change, perlu versi/migrasi. |
| Mengubah tipe field | Breaking change, perlu versi/migrasi. |
| Mengubah default pagination | Perlu catatan dokumentasi dan test. |
| Mengubah status code sukses/error | Perlu evaluasi compatibility dan test. |
| Menghapus endpoint | Tidak boleh tanpa deprecation window. |

## Authentication & Session

Authentication menggunakan OTP dan bearer session token. Session token harus dianggap sebagai credential sensitif.

### Requirements

1. Login/register role utama harus mengikuti flow OTP yang sudah berjalan.
2. Token session hanya boleh disimpan server-side dalam bentuk hash, bukan plain token.
3. Client mengirim token melalui header `Authorization: Bearer <token>`.
4. Endpoint authenticated wajib memakai middleware `session.token`.
5. Token harus bisa di-refresh melalui endpoint resmi, bukan dibuat manual oleh client.
6. Logout harus mencabut session aktif.
7. OTP, plain token, password, credential bank, dan dokumen identitas tidak boleh masuk log atau response.
8. OTP harus memiliki expiry, batas resend, dan batas percobaan verifikasi.
9. Session harus punya expiry dan mekanisme revocation.
10. Device token untuk notifikasi harus terikat ke user dan dapat diperbarui tanpa membuat duplikasi tidak terkendali.
11. Sistem harus mendukung invalidasi session bila user dinonaktifkan atau role berubah.

### Session Risk Controls

| Risiko | Kontrol |
| --- | --- |
| Token bocor dari client | Token hash di server, expiry, logout, dan revocation. |
| OTP brute force | Expiry, attempt limit, resend limit, dan rate limiting. |
| Session lama tetap aktif setelah perubahan role | Revocation saat role/status sensitif berubah. |
| Data auth bocor di log | Masking dan larangan log secret. |

## Authorization (RBAC)

Authorization menggunakan role-based access control dengan ownership check untuk resource yang dimiliki user tertentu.

### Roles

Role utama:

1. `buyer`
2. `seller`
3. `agent`
4. `finance`

### Requirements

1. Endpoint role-specific wajib memakai middleware `role:*`.
2. Pengecekan role manual di controller harus dihindari bila bisa ditangani middleware.
3. Ownership resource wajib dicek server-side.
4. Seller hanya boleh mengakses tenant, produk, order, dan transaksi miliknya.
5. Buyer hanya boleh mengakses cart dan transaksi miliknya.
6. Agent hanya boleh mengakses UMKM/seller yang memang berada dalam scope kelola agent tersebut.
7. Finance hanya boleh mengakses workflow finance sesuai scope endpoint finance.
8. Aksi sensitif seperti approve, reject, mark as paid, confirm payment, dan disburse harus dibatasi ke role yang tepat.
9. Status user/agent dapat menjadi authorization condition tambahan selain role.
10. Setiap endpoint role-specific harus punya test minimal untuk unauthorized dan forbidden role.

### Permission Matrix

| Area | Buyer | Seller | Agent | Finance |
| --- | --- | --- | --- | --- |
| Auth/session sendiri | Yes | Yes | Yes | Yes |
| Catalog read public/authenticated | Yes | Limited | Limited | Limited |
| Cart/checkout | Yes | No | No | No |
| Seller tenant/product/order | No | Own only | Managed seller read only | Finance read where needed |
| Agent dashboard/profile | No | No | Own only | No |
| Commission withdrawal request | No | No | Own only | Review/manage |
| Finance transaction/disbursement | No | No | No | Yes |
| Master cancellation reason finance CRUD | No | No | No | Yes |

## Audit Trail (ISO 27001)

Audit trail diperlukan untuk mendukung akuntabilitas, investigasi insiden, dan kontrol perubahan data sensitif. Target kontrol mengikuti prinsip ISO 27001: traceability, integrity, least privilege, retention, dan reviewability.

### Requirements

1. Sistem harus mencatat audit event untuk aksi sensitif.
2. Audit event minimal menyimpan actor, action, target resource, timestamp, result, dan request context yang aman.
3. Audit log tidak boleh menyimpan secret, OTP, plain token, password, credential bank lengkap, atau isi dokumen identitas.
4. Audit log harus append-only secara aplikasi. Update/delete manual tidak boleh menjadi operasi normal.
5. Audit event harus dibuat dalam transaksi yang sama untuk aksi database kritikal bila aksi dan audit harus atomic.
6. Audit log harus bisa difilter berdasarkan actor, target, action, dan periode.
7. Retention audit log production harus ditentukan sebelum compliance review.
8. Export audit log harus dibatasi untuk role internal yang disetujui.
9. Kegagalan audit pada aksi high-risk harus membuat aksi utama gagal, kecuali ada keputusan eksplisit sebaliknya.
10. Audit trail harus memiliki test untuk aksi high-risk.

### Minimum Audited Events

| Area | Event |
| --- | --- |
| Auth/session | Login OTP verified, logout, refresh session, failed verification limit. |
| User/role | Create user, update role/status, deactivate/reactivate user. |
| Agent | Register agent, update profile/payout data, review status change. |
| Seller/UMKM | Create/update tenant, product mutation, status active/inactive. |
| Transaction | Checkout created, payment confirmed, order status changed, cancellation. |
| Commission | Withdrawal requested, approved, rejected, marked as paid. |
| Finance | Buyer payment confirmation, seller disbursement, cancellation reason CRUD. |
| Attachments | Upload, replace, delete, private file access. |

### Audit Data Shape

| Field | Keterangan |
| --- | --- |
| `id` | UUID audit event. |
| `actor_user_id` | User yang melakukan aksi, nullable untuk system job. |
| `actor_role` | Role actor saat aksi terjadi. |
| `action` | Nama aksi stabil, misalnya `commission_withdrawal.approved`. |
| `target_type` | Tipe resource target. |
| `target_id` | UUID/resource id target bila ada. |
| `result` | `success`, `failed`, atau `denied`. |
| `ip_address` | IP request bila tersedia. |
| `user_agent` | User agent bila tersedia, boleh dimasking/truncated. |
| `metadata` | JSON aman tanpa secret. |
| `created_at` | Timestamp immutable event. |

## Background Jobs

Background jobs digunakan untuk pekerjaan retryable, lambat, atau tidak wajib selesai dalam request-response utama.

### Requirements

1. Request user-facing tidak boleh menunggu pekerjaan eksternal yang lambat bila bisa diproses async.
2. Job harus idempotent atau memiliki key deduplication untuk menghindari efek ganda.
3. Job harus memiliki retry policy dan failure handling yang eksplisit.
4. Job yang mengubah uang, status, stok, atau disbursement harus memakai transaksi database dan guard status current-state.
5. Job yang memanggil dependency eksternal harus mencatat failure reason yang aman.
6. Queue worker production harus dimonitor.
7. Failed job harus dapat diinspeksi dan di-retry secara aman.
8. Job tidak boleh menerima payload secret mentah bila bisa mengambil data dari database saat eksekusi.

### Candidate Jobs

| Job | Trigger | Catatan |
| --- | --- | --- |
| Send OTP | Register/login/resend OTP | Retry terbatas, masking log. |
| Send notification | Perubahan order/withdrawal/payment | Async dan idempotent per event. |
| Sync finance disbursement | Finance/payment workflow | Guard status dan audit event. |
| Generate report/export | Dashboard/report besar | Async bila query berat. |
| Media cleanup | Upload diganti/dihapus | Jangan hapus file yang masih direferensikan. |

## Performance

Sistem harus cukup cepat untuk dashboard operasional, checkout, dan API mobile/web tanpa mengorbankan integritas data.

### Requirements

1. Endpoint read umum harus menghindari N+1 query melalui eager loading atau query agregasi.
2. Endpoint list harus memakai pagination.
3. Query filter yang sering dipakai harus memiliki index database.
4. Dashboard aggregator harus menghitung di server dan menghindari kalkulasi besar di client.
5. Endpoint mutasi kritikal harus menjaga integrity lebih tinggi daripada latency.
6. Upload file harus memiliki batas ukuran dan tidak memblokir proses bisnis lebih lama dari yang diperlukan.
7. Response API tidak boleh mengirim field besar yang tidak diperlukan client.
8. Cache boleh dipakai untuk master data atau agregasi non-kritikal, tetapi tidak boleh membuat data uang/status menjadi salah.
9. Rate limiting harus diterapkan untuk endpoint auth, OTP, upload, dan endpoint mahal.

### Initial Targets

| Area | Target Awal |
| --- | --- |
| Healthcheck | p95 kurang dari 300 ms. |
| Auth OTP verify | p95 kurang dari 1 detik tanpa bottleneck eksternal. |
| Read detail/list umum | p95 kurang dari 1 detik untuk dataset normal. |
| Dashboard | p95 kurang dari 2 detik untuk dataset MVP. |
| Checkout/mutasi transaksi | p95 kurang dari 2 detik, integrity diprioritaskan. |
| Upload gambar | Ukuran dan waktu mengikuti limit yang ditentukan per jenis file. |

Target ini harus divalidasi ulang setelah traffic dan ukuran data production tersedia.

## Availability & Disaster Recovery

Kresekin API harus bisa dipulihkan dari kegagalan aplikasi, database, deployment, dan kehilangan data terbatas.

### Requirements

1. Production harus memiliki healthcheck endpoint yang bisa dipakai platform deployment.
2. Database production harus memiliki backup otomatis.
3. Backup harus diuji restore secara berkala, bukan hanya dibuat.
4. Deployment harus mendukung rollback bila release gagal.
5. Migration production harus backward-compatible atau memiliki release plan yang jelas.
6. Job queue production harus bisa dipulihkan setelah restart tanpa kehilangan pekerjaan penting.
7. Storage attachment penting harus memiliki backup atau durability sesuai kebutuhan bisnis.
8. Sistem harus memiliki runbook dasar untuk incident: API down, database down, queue stuck, upload/storage failure, dan provider eksternal gagal.
9. RTO dan RPO final harus disepakati sebelum launch production penuh.

### Initial Targets

| Metric | Target Awal |
| --- | --- |
| Availability API | 99.5% bulanan untuk MVP. |
| RTO | Maksimal 4 jam untuk pemulihan layanan inti. |
| RPO | Maksimal 24 jam sampai strategi backup final disetujui. |
| Backup restore drill | Minimal setiap major release atau per kuartal. |

## Security

Security requirement berlaku untuk source code, runtime, database, file storage, log, dan proses delivery.

### Requirements

1. Secret dan credential harus berasal dari environment variable atau secret manager platform.
2. Secret tidak boleh disimpan di repository, dokumentasi contoh, log, atau response API.
3. Input harus divalidasi melalui FormRequest atau validasi eksplisit.
4. Semua file upload harus divalidasi MIME, extension, ukuran, dan ownership.
5. File sensitif harus disimpan di private disk.
6. Public URL langsung untuk dokumen identitas atau file sensitif tidak boleh dibuat.
7. Endpoint auth, OTP, upload, dan mutasi penting harus memiliki rate limit.
8. CORS harus dibatasi ke origin client yang disetujui untuk environment production.
9. Response error production tidak boleh mengekspos stack trace.
10. Data pribadi harus diminimalkan pada response dan log.
11. Dependency harus diperiksa untuk vulnerability sebelum release penting.
12. Query database harus memakai query builder/Eloquent parameter binding, bukan concatenation raw input.
13. Authorization dan ownership check harus ada di server walaupun client menyembunyikan UI.
14. Backup dan export data harus dilindungi setara data production.
15. Admin/internal tools harus memakai role paling terbatas yang cukup untuk tugasnya.

### Sensitive Data

| Data | Perlakuan |
| --- | --- |
| OTP | Hash atau simpan dengan expiry, tidak masuk log/response. |
| Session token | Simpan hash SHA-256, plain token hanya muncul saat diterbitkan ke client. |
| Password/credential | Hash atau secret manager, tidak pernah plaintext. |
| Dokumen identitas | Private storage, akses terotorisasi, audit access. |
| Rekening/payout data | Masking di response/log bila tidak perlu penuh. |
| Nomor telepon/email | Validasi, masking di log tertentu, akses sesuai role. |

## Attachments & Media

Attachments dan media mencakup gambar produk, dokumen identitas agent, bukti pembayaran, dan file operasional lain yang akan ditambahkan.

### Requirements

1. File public dan file private harus dipisahkan secara storage dan akses.
2. Gambar produk boleh public bila memang dibutuhkan buyer, tetapi tetap harus divalidasi dan dimiliki seller yang benar.
3. Dokumen identitas, bukti pembayaran, dan file sensitif lain harus private.
4. Upload harus membatasi MIME type, extension, ukuran file, dan jumlah file per request.
5. Nama file/path storage harus dibuat server-side dan tidak mudah ditebak.
6. Client tidak boleh menentukan path absolut atau path final storage.
7. File harus dipindai atau divalidasi sesuai kemampuan platform sebelum dibuka publik.
8. Penghapusan atau penggantian file harus mempertimbangkan referensi database agar tidak menghapus file yang masih dipakai.
9. Akses file private harus melalui endpoint terotorisasi atau signed URL berdurasi pendek.
10. Upload, replace, delete, dan akses file private harus masuk audit trail.
11. Metadata file minimal menyimpan owner, disk, path, MIME, size, checksum bila tersedia, dan timestamp.

## Observability

Observability harus membantu tim mendeteksi masalah, menyelidiki insiden, dan memahami kesehatan sistem tanpa membocorkan data sensitif.

### Requirements

1. Aplikasi harus menghasilkan log terstruktur untuk event operasional penting.
2. Log harus memiliki correlation/request id bila tersedia.
3. Error production harus dapat ditelusuri melalui log atau error tracking.
4. Metric minimal harus mencakup request rate, latency, error rate, queue depth, failed jobs, dan database error.
5. Healthcheck harus tersedia dan tidak bergantung pada dependency eksternal yang tidak perlu.
6. Alert harus dibuat untuk API down, error spike, queue stuck, failed jobs tinggi, dan storage/database issue.
7. Dashboard observability harus memisahkan environment production dan staging.
8. Log tidak boleh memuat OTP, token, password, dokumen, credential bank, atau payload sensitif penuh.
9. Integrasi eksternal harus mencatat status, latency, dan failure category yang aman.

### Minimum Signals

| Signal | Kegunaan |
| --- | --- |
| Request latency p50/p95/p99 | Deteksi endpoint lambat. |
| HTTP error rate | Deteksi regression dan outage. |
| Auth/OTP failure rate | Deteksi abuse atau masalah auth. |
| Queue depth dan failed jobs | Deteksi backlog async. |
| Database connection/error | Deteksi gangguan persistence. |
| Upload/storage failure | Deteksi gangguan media. |
| Audit event count | Deteksi perubahan aksi sensitif. |

## Environments & Delivery

Environment harus dipisahkan agar testing, staging, dan production tidak saling mencemari data atau credential.

### Environments

| Environment | Tujuan |
| --- | --- |
| Local | Pengembangan individual dengan konfigurasi lokal. |
| Testing | Automated test, menggunakan SQLite in-memory sesuai konfigurasi project. |
| Staging | Validasi sebelum production dengan service dan data non-production. |
| Production | Layanan live untuk user dan operasi bisnis. |

### Requirements

1. Setiap environment harus memiliki environment variable sendiri.
2. Credential production tidak boleh digunakan di local, testing, atau staging.
3. Migration harus dijalankan melalui proses deployment yang tercatat.
4. Seeder production hanya boleh berisi reference data yang aman dan idempotent.
5. Release harus memiliki langkah rollback atau mitigation.
6. CI harus menjalankan minimal lint/style dan test relevan sebelum merge/release.
7. Perubahan config harus terdokumentasi bila memengaruhi runtime behavior.
8. Feature yang belum siap production harus dilindungi feature flag, role, atau tidak dirilis.
9. Build artifact dan dependency lockfile harus konsisten.
10. Deployment harus mencatat commit/version yang sedang aktif.

## Data Conventions

Konvensi data diperlukan agar database, API, test, dan reporting memakai bentuk yang konsisten.

### Requirements

1. Identifier entity aplikasi menggunakan UUID string.
2. Uang, stok, quantity, count, dan counter memakai integer.
3. Float tidak boleh dipakai untuk nominal uang.
4. Timestamp database mengikuti timezone aplikasi/server yang disepakati dan dikirim ke API sebagai ISO 8601.
5. Status dan enum harus didefinisikan sebagai constants atau method terpusat, bukan string literal tersebar.
6. Soft delete boleh dipakai untuk data yang tidak boleh hilang dari histori.
7. Data transaksi harus menyimpan snapshot nilai historis yang dibutuhkan, misalnya nama produk, harga, diskon, fee, dan status saat transaksi dibuat.
8. Foreign key dan index harus ditambahkan sesuai relasi dan query utama.
9. Field opsional harus benar-benar nullable hanya bila business rule mengizinkan.
10. Field unik seperti email/phone/code harus memiliki normalization rule yang konsisten.
11. PII harus diminimalkan dan dimasking pada log, export, atau response yang tidak membutuhkan nilai penuh.
12. Perubahan schema wajib disertai migration baru dan test bila mengubah behavior.

### Naming Conventions

| Item | Konvensi |
| --- | --- |
| Table | Snake case plural sesuai Laravel convention. |
| Column | Snake case, jelas secara domain. |
| Boolean | Prefix `is_`, `has_`, atau nama status yang eksplisit. |
| Timestamp | `*_at` untuk waktu kejadian. |
| Amount | Integer dengan suffix `_amount`, `_fee`, `_total`, atau domain setara. |
| Status | String enum dari constants domain. |
| Foreign key | `{model}_id` berisi UUID bila mengarah ke entity aplikasi. |

## Testing (AI-Builder Clause)

Testing adalah syarat perubahan behavior, termasuk bila kode dibuat atau diubah oleh AI builder. AI boleh membantu implementasi, tetapi tidak menggantikan kewajiban engineering verification.

### Requirements

1. Setiap perubahan behavior harus memiliki regression test yang relevan.
2. Endpoint API harus diuji di feature test untuk happy path, invalid input, unauthorized, forbidden, not found, dan edge case bisnis yang relevan.
3. Logic model/support/service harus diuji di unit test bila dapat dipisahkan dari HTTP.
4. Flow uang, stok, promo, transaksi, komisi, dan disbursement harus assert nilai database akhir.
5. Role-specific endpoint wajib memiliki test role salah tidak bisa mengakses.
6. Upload harus diuji untuk tipe file valid, tipe file invalid, ukuran file, dan ownership.
7. Audit trail high-risk harus diuji: event tercatat, actor benar, target benar, dan secret tidak terekam.
8. Job harus diuji untuk dispatch, idempotency, retry/failure behavior yang relevan.
9. AI-generated code harus tetap mengikuti `docs/requirements/13-engineering_standard.md`.
10. AI-generated code tidak boleh diterima hanya karena terlihat benar. Minimal harus ada test otomatis atau verifikasi manual tertulis bila test belum memungkinkan.
11. Jika AI mengubah kontrak API, dokumentasi API wajib diperbarui dalam perubahan yang sama.
12. Jika test tidak bisa dijalankan, alasan teknis dan risiko residual harus dicatat pada hasil kerja.

### Minimum Verification Before Merge

```bash
./vendor/bin/pint
php artisan test
```

Tambahkan command lain sesuai area perubahan, misalnya `npm run build` bila menyentuh frontend asset atau Blade yang bergantung Vite.

## Open Questions

1. Berapa RTO dan RPO final yang diterima bisnis untuk production?
2. Apakah audit trail akan dibuat sebagai tabel internal aplikasi, dikirim ke SIEM/log platform, atau keduanya?
3. Berapa retention policy untuk audit log, OTP/session history, dokumen identitas, dan file transaksi?
4. Apakah dokumen identitas agent wajib dienkripsi di storage selain private access?
5. Siapa role final yang boleh melihat dan mengekspor audit trail?
6. Apakah agent `pending_review` boleh mengakses seluruh dashboard atau hanya subset read-only?
7. Apakah diperlukan permission granular di luar role `buyer`, `seller`, `agent`, dan `finance`?
8. Apakah perlu API versioning formal seperti `/api/v1` sebelum mobile client production?
9. Provider apa yang akan dipakai untuk email/WhatsApp OTP, payment, payout, dan object storage production?
10. Apakah dashboard membutuhkan near-real-time data atau cukup berdasarkan query request-time/cache pendek?
11. Berapa batas final file upload untuk gambar produk, dokumen identitas, dan bukti pembayaran?
12. Apakah diperlukan antivirus/malware scanning untuk upload file production?
13. Apakah backup database dan storage dikelola Laravel Cloud/platform atau perlu mekanisme tambahan?
14. Apa requirement legal terkait consent, penghapusan akun, dan data pribadi pengguna Indonesia?
15. Apakah perlu environment khusus UAT selain staging?
16. Apakah target availability 99.5% cukup untuk MVP atau perlu dinaikkan sebelum launch publik?
17. Apakah financial workflow membutuhkan maker-checker untuk approve dan mark as paid?
18. Apakah semua aksi finance harus require alasan/catatan wajib untuk audit?
19. Apakah log observability akan memakai platform bawaan Laravel Cloud atau tool eksternal?
20. Apakah audit export dan report finance harus async sejak MVP?
