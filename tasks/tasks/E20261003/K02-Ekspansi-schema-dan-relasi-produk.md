# E20261003-K02 — Ekspansi schema dan relasi produk

- Tanggal: 2026-10-03
- Status: Implementasi lokal tersedia — rehearsal PostgreSQL dan deployment belum terverifikasi
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](timeline.md)
- PIC peran: Backend / Database; nama personel ditetapkan pada K01
- Target baseline: M1–M2
- Estimasi: 2–3 hari kerja effort, bukan janji durasi kalender
- Dependensi: K01
- Tautan dependensi: [K01](../../DONE/E20261003/K01-Audit-kontrak-dan-keputusan-transisi.md)

## Tujuan

Menyediakan schema kompatibel untuk writer baru tanpa menghapus kolom lama.

## Cakupan

1. Migration baru: tenants.category nullable; products.product_category_id UUID nullable, indeks dan FK pembatasan penghapusan.
2. Tambahkan relasi Product → ProductCategory; audit relasi/delete path master, termasuk soft-deleted products.
3. Audit seeder kategori agar idempotent dan tidak mengganti UUID yang sudah direferensikan; client selalu memperoleh ID dari master environment terkait.

## Acceptance criteria dan verifikasi

- [ ] Upgrade database berisi produk/tenant lama berhasil tanpa kehilangan nilai kategori.
- [ ] FK menolak orphan dan penghapusan master yang dipakai produk aktif maupun soft-deleted; tidak ada cascade/pengosongan otomatis.
- [ ] Seeder dapat diulang tanpa merusak relasi master; migration historis tidak diubah.
- [ ] Uji migration pada PostgreSQL sesuai produksi tersedia; ekspansi dideploy sebelum writer ID atau tenant tanpa kategori.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.

## Implementasi lokal — 2026-10-04

- Migration tambahan `2026_10_04_000001_expand_product_category_schema.php`: `tenants.category` menjadi nullable tanpa mengubah nilainya; `products.product_category_id` UUID nullable dengan indeks dan FK `ON DELETE RESTRICT`. Kolom kategori lama serta migration historis tetap dipertahankan. Tidak ada backfill atau perubahan writer pada K02.
- `Product::productCategory()` dan fillable ID kategori ditambahkan. Audit delete path: seller menghapus produk lewat soft delete; FK tetap melindungi master karena record produk masih ada. Tidak ditemukan endpoint penghapusan master kategori. FK tenant lama tetap `SET NULL` sesuai schema historis; FK produk baru mencegah penghapusan master jika masih direferensikan produk.
- Seeder kategori tidak perlu diubah: `updateOrCreate` berdasarkan slug unik hanya memperbarui nama/gambar, tidak menghapus master atau mengganti UUID. UUID tidak dijamin sama lintas environment. Client harus mengambil ID dari master environment yang dipakai (`GET /api/product-categories`); jangan menyalin UUID dari environment lain.
- Regression test `tests/Feature/ProductCategorySchemaTest.php` mencakup upgrade data lama termasuk soft-deleted, relasi, indeks, orphan, pembatasan delete master, seeder berulang, serta rollback.

### Rehearsal PostgreSQL dan urutan rollout

Gunakan database **test khusus** PostgreSQL 18.6 sesuai baseline produksi. Suite membuat schema acak `k02_<uuid>`, menjalankan migration hanya di schema tersebut, lalu menghapusnya. Akun test perlu izin CREATE schema. Jangan arahkan URL ke database bersama/produksi. URL koneksi diberikan melalui environment, tidak disimpan di repository:

```bash
# K02_TEST_PGSQL_URL diisi melalui environment dengan URL database test khusus.
php artisan test --filter=ProductCategorySchemaTest
```

Tanpa `K02_TEST_PGSQL_URL`, suite memakai SQLite in-memory; kelulusan SQLite tidak menggantikan rehearsal PostgreSQL. Default suite lain tetap mengikuti `phpunit.xml`.

Deploy migration ekspansi dan verifikasi schema **sebelum** mengaktifkan writer ID kategori (K03) atau writer tenant tanpa kategori (K04). Produk lama tetap mempunyai ID null hingga backfill tiket berikutnya. Rehearsal PostgreSQL dan gate deployment tetap wajib; pekerjaan lokal tidak membuktikan deployment produksi.

Rollback aplikasi lama tidak memerlukan penghapusan schema ekspansi. Jika rollback migration benar-benar diperlukan, hentikan writer baru, pastikan data kategori teks produk sudah kompatibel dengan reader lama, dan simpan referensi ID sebelum kolom dihapus. `down()` menolak rollback bila ada `tenants.category IS NULL`; tentukan nilai tenant yang benar melalui keputusan domain terlebih dahulu. Rollback yang berhasil menghapus kolom ID baru beserta FK/indeksnya, mempertahankan teks kategori lama, dan mengembalikan kategori tenant non-nullable. Jangan menjalankan rollback destruktif saat writer ID masih aktif.

### Bukti verifikasi lokal

- `vendor/bin/pint --dirty`: selesai; formatting test disesuaikan.
- `vendor/bin/pint --test app/Models/Product.php database/migrations/2026_10_04_000001_expand_product_category_schema.php tests/Feature/ProductCategorySchemaTest.php`: lolos.
- `php artisan test --filter=ProductCategorySchemaTest`: 7 test lolos, 30 assertion, SQLite in-memory.
- `git diff --check`: lolos.
- PostgreSQL belum diuji: binary/server PostgreSQL tidak tersedia di WSL dan `docker version` gagal karena integrasi Docker Desktop WSL belum aktif. Driver PHP `pdo_pgsql` tersedia; suite PostgreSQL siap dijalankan setelah database test khusus tersedia. Belum ada deployment atau perubahan database bersama/produksi. K02 belum memenuhi verifikasi PostgreSQL/gate rollout untuk penutupan.
- Suite terkait: `php artisan test --filter='ProductCategorySchemaTest|ProductApiTest|ProductDetailApiTest|TenantApiTest|TenantProductCategoryApiTest|SellerApiTest|ModelRelationAndCastTest'`: 63 lolos, 7 gagal (437 assertion). Seluruh test K02, product list, seller, relasi model, dan kategori produk tenant lolos.
- Pembandingan baseline: `php artisan test --filter='ProductDetailApiTest|TenantApiTest'` dengan model Product dari HEAD dan tanpa migration ekspansi menghasilkan 7 kegagalan yang sama (5 lolos, 53 assertion); perubahan K02 kemudian dipulihkan. Kegagalan yang sudah ada mencakup ekspektasi pesan autentikasi bahasa Inggris, koordinat/urutan daftar tenant, serta filter/validasi kategori tenant. Tidak diperbaiki di luar lingkup K02.
