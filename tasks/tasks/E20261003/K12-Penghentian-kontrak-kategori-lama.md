# E20261003-K12 — Penghentian kontrak kategori lama

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Backend API + Client PIC; nama personel ditetapkan pada K01
- Target baseline: M8 awal
- Estimasi: 1–2 hari kerja effort, bukan janji durasi kalender
- Dependensi: K11 sign-off, K10 selesai
- Tautan dependensi: [K10](K10-Dokumentasi-kontrak-dan-koordinasi-migrasi-client.md), [K11](K11-Verifikasi-rollout-dan-readiness-gate.md)

## Tujuan

Mengakhiri kontrak input/endpoint lama setelah client siap, sebelum drop schema.

## Cakupan

1. Hapus fallback teks edit; product_category_id wajib create/PUT/POST alias.
2. Hapus GET tenants/categories berikut controller/test lama; tambah pengujian target sesuai route removal.
3. Hapus seluruh field kategori tunggal tenant yang masih dipertahankan sementara; tenant input lama tetap diabaikan.
4. Deploy terpisah sebelum cleanup schema, smoke test dan observasi minimal 2 hari kerja; error regresi menunda K13.

## Acceptance criteria dan verifikasi

- [ ] Edit teks-only/ID kosong ditolak 422; ID valid dan alias multipart tetap berhasil.
- [ ] Endpoint lama tidak terdaftar; client aktif tetap berjalan pada endpoint master.
- [ ] Tidak ada field kategori tunggal tenant di response target; input tenant lama tidak ditolak karena kategori.
- [ ] Kontrak removal terdokumentasi dan rilis terobservasi tanpa regresi terkait sebelum K13.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
