# E20261003-K06 — Eksekusi dan rekonsiliasi backfill

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Database / Operations + Domain Owner; nama personel ditetapkan pada K01
- Target baseline: M3
- Estimasi: 2–3 hari kerja effort, bukan janji durasi kalender
- Dependensi: K03, K04, K05 sudah dirilis
- Tautan dependensi: [K03](K03-Standardisasi-writer-produk-API.md), [K04](K04-Standardisasi-writer-web-dan-admin.md), [K05](K05-Alat-audit-dan-backfill-kategori-produk.md)

## Tujuan

Seluruh produk tersimpan mempunyai relasi master valid dengan bukti operasional.

## Cakupan

1. Jalankan dry-run pada data operasional; domain owner menyetujui mapping tidak cocok/ambigu.
2. Pastikan semua writer dual-write sudah aktif, termasuk jalur worker/import bila ditemukan audit.
3. Jalankan backfill bertahap sesuai runbook, pantau lock/error dan tangani konflik concurrent edit.
4. Rekonsiliasi seluruh produk termasuk soft-deleted; simpan mapping dan hasil operasi secara durable.

## Acceptance criteria dan verifikasi

- [ ] Mapping memiliki persetujuan domain owner dan jejak ID tujuan tanpa data personal.
- [ ] Jumlah seluruh produk direkonsiliasi; null/orphan nol pada waktu pemeriksaan tercatat.
- [ ] Rerun tidak menimpa ID valid; konflik dan kategori tak terpetakan selesai secara eksplisit.
- [ ] Bukti produksi terlampir; tiket tidak selesai hanya karena tool lolos unit test.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
