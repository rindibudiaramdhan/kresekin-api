# E20261003-K13 — Cleanup schema dan verifikasi akhir

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Backend / Database + Release Owner; nama personel ditetapkan pada K01
- Target baseline: M8 akhir
- Estimasi: 2–3 hari kerja effort, bukan janji durasi kalender
- Dependensi: K12 observasi lulus, backup dan gate K11 masih valid
- Tautan dependensi: [K11](K11-Verifikasi-rollout-dan-readiness-gate.md), [K12](K12-Penghentian-kontrak-kategori-lama.md)

## Tujuan

Menyelesaikan model target dan menutup epic setelah cleanup terverifikasi.

## Cakupan

1. Recheck seluruh produk valid; backup/pemetaan sebelum drop tersimpan dan dapat diakses sesuai runbook.
2. Migration baru: products.product_category_id non-null; drop products.category; drop FK/kolom tenants.product_category_id serta tenants.category.
3. Hapus fillable/relasi/konstanta/helper tenant lama setelah audit pemakai nol; finalisasi seeder/factory/fixture target.
4. Verifikasi fresh install dan upgrade data lama pada PostgreSQL; smoke test API, web/admin, dashboard setelah deploy.
5. Dokumentasi final dan link seluruh bukti sign-off epic; down() hanya struktur, pemulihan data memerlukan restore.

## Acceptance criteria dan verifikasi

- [ ] Tidak ada null/orphan dan referensi runtime ke kolom/helper kategori tenant atau Tenant::CATEGORIES.
- [ ] Response category produk tetap nama turunan master meski kolom teks sudah dihapus.
- [ ] Payload tenant lama tetap diabaikan setelah drop; kategori digunakan soft-deleted product tetap terlindungi.
- [ ] Fresh migration/seeding serta upgrade PostgreSQL berhasil; pengujian relevan dan Pint lulus.
- [ ] Backup dan pemulihan tercatat; semua acceptance epic termasuk client/removal/cleanup dipenuhi sebelum epic ditutup.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
