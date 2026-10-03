# E20261003-K09 — Tenant tanpa kategori dan dashboard terdampak

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Backend API / Dashboard; nama personel ditetapkan pada K01
- Target baseline: M4–M5
- Estimasi: 3–4 hari kerja effort, bukan janji durasi kalender
- Dependensi: K02, K08, keputusan dan bukti kompatibilitas K01/K10
- Tautan dependensi: [K01](K01-Audit-kontrak-dan-keputusan-transisi.md), [K02](K02-Ekspansi-schema-dan-relasi-produk.md), [K08](K08-Filter-dan-agregasi-kategori-tenant-berbasis-ID.md), [K10](K10-Dokumentasi-kontrak-dan-koordinasi-migrasi-client.md)

## Tujuan

Pembuatan dan pembacaan tenant tidak lagi membutuhkan kategori tunggal.

## Cakupan

1. Hilangkan kewajiban dan penyimpanan category/category_id pada create tenant; payload lama tetap diabaikan, termasuk setelah cleanup.
2. Audit response seller/agent/finance, dashboard, helper nullable dan seluruh reader sebelum aktivasi create tenant tanpa kategori.
3. Tangani AgentTenantRequest serta GetTenantListController yang tidak aktif: hapus bila terbukti tidak digunakan atau lepaskan dependency kategori.
4. Field kategori tunggal target dihapus; jika client belum siap, gunakan bentuk sementara yang disepakati K01 tanpa mengambil kategori utama produk.

## Acceptance criteria dan verifikasi

- [ ] Create tenant tanpa kategori berhasil; payload lama tidak menyimpan kategori tenant; field lain tetap validasi existing.
- [ ] Semua reader/dashboard aman untuk tenant baru tanpa produk/kategori; tidak bergantung pada nilai kategori tenant.
- [ ] Aktivasi create dilakukan setelah semua jalur reader null-safe dan bukti kompatibilitas client tersedia.
- [ ] Field category_id/category/category_master/category_slug/category_icon_key/category_background_color/category_icon_color dihapus bila siap; sisa bentuk sementara tercatat untuk K12.
- [ ] Regression auth/ownership/dashboard lulus; tidak membuat endpoint edit tenant baru.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
