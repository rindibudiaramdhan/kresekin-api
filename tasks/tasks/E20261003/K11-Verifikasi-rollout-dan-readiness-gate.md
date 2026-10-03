# E20261003-K11 — Verifikasi rollout dan readiness gate

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: QA + Release Owner; nama personel ditetapkan pada K01
- Target baseline: M5–M7
- Estimasi: 3–4 hari kerja effort, bukan janji durasi kalender
- Dependensi: K03–K10; persiapan paralel, sign-off setelah semuanya siap
- Tautan dependensi: [K03](K03-Standardisasi-writer-produk-API.md), [K10](K10-Dokumentasi-kontrak-dan-koordinasi-migrasi-client.md)

## Tujuan

Bukti lintas fitur dan operasional cukup untuk menyetujui penghentian kontrak lama.

## Cakupan

1. Gabungkan regression tenant/produk/kategori/filter/web/admin/agent/finance dengan auth, ownership, transaksi dan visibility tetap.
2. Rehearsal upgrade data lama pada PostgreSQL serta fresh install; ukur query list/dashboard yang berubah.
3. Verifikasi writer/reader lama tidak aktif, semua produk valid, client ready dan periode observasi selesai.
4. Siapkan backup/pemetaan sebelum drop, langkah restore dan simulasi pemulihan; down() bukan pemulihan nilai tenant.
5. Catat release owner dan bukti gate; gunakan deployment/runbook Laravel Cloud.

## Acceptance criteria dan verifikasi

- [ ] Pint dan pengujian relevan lulus; matrix acceptance epic mempunyai bukti dan pemilik.
- [ ] Upgrade PostgreSQL dan fresh migration/seeding terverifikasi; hasil query/performance direview.
- [ ] Gate data: null/orphan nol termasuk soft-deleted; gate client/kontrak: K10 selesai dan tidak ada writer/reader lama.
- [ ] Prosedur pemulihan diuji di lingkungan pengujian dan menjelaskan batas rollback setelah drop.
- [ ] Release owner mencatat keputusan go/no-go dengan tautan bukti; tiket tidak menggantikan test per implementasi.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
