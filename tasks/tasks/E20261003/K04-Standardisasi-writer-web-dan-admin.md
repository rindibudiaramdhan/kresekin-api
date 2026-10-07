# E20261003-K04 — Standardisasi writer web dan admin

- Tanggal: 2026-10-03
- Status: Implementasi web teruji; admin belum terverifikasi runtime
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](timeline.md)
- PIC peran: Backend Web / Admin; nama personel ditetapkan pada K01
- Target baseline: M2
- Estimasi: 2–3 hari kerja effort, bukan janji durasi kalender
- Dependensi: K02
- Tautan dependensi: [K02](K02-Ekspansi-schema-dan-relasi-produk.md)

## Tujuan

Form seller dan admin membaca pilihan kategori dari master dan menyimpan ID.

## Cakupan

1. Audit SellerProductRequest, Web/SellerProductController, view produk, ProductResource dan seluruh writer web/admin yang ditemukan K01.
2. Ganti pilihan konstanta dengan master; form edit memilih ID tersimpan; dual-write selama transisi.
3. Tangani produk lama dengan teks belum terpetakan secara eksplisit; jangan mengisi kategori default diam-diam.

## Acceptance criteria dan verifikasi

- [ ] Create/edit web dan admin menyimpan ID valid dan teks master selama transisi.
- [ ] Kategori baru di master dapat dipilih tanpa perubahan kode/konstanta; kategori edit tersimpan terpilih dengan benar.
- [ ] Record lama belum terpetakan tidak menyebabkan crash atau penyimpanan kategori default.
- [ ] Test relevan mencakup validasi, ownership serta preselection; build asset dijalankan jika asset berubah.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.

## Implementasi dan bukti lokal — 2026-10-04

- Form web mengambil pilihan master database, mengirim ID, memvalidasi UUID/existence dan ownership tenant, serta dual-write lewat `ProductCategoryWriter`. Edit memilih ID tersimpan; produk legacy tanpa ID menampilkan penjelasan dan tetap meminta pilihan eksplisit. Tidak ada default/fuzzy mapping otomatis pada form.
- ProductResource admin memakai relasi master, validasi ID dan penjelasan legacy. Hook create/save menjalankan dual-write dan memeriksa tenant tujuan milik user aktif.
- TenantSeeder/PromoProductSeeder sebagai writer fixture memakai resolver master unik, menyimpan ID dan nama master, serta gagal eksplisit bila master tidak tersedia. ProductCategorySeeder harus dijalankan lebih dahulu; identitas master dipertahankan.
- `ProductCategoryWebWriterTest`: 5 test, 70 assertion lolos (web create/edit/preselection, record legacy, ownership, helper writer admin, fixture repeat). Test helper tidak membuktikan UI/hook Filament berfungsi.
- Filament tidak ditemukan di composer.json/composer.lock/vendor; tidak memasang dependensi baru. Verifikasi UI/admin pada environment yang mengaktifkan Filament masih diperlukan sebelum checklist lintas web/admin dinyatakan selesai. PHP admin diperiksa syntax/format secara lokal.
- Hanya template Blade berubah; tidak ada CSS/JS/Vite asset berubah. Render form sudah diuji melalui HTTP test, sehingga build asset tidak diperlukan untuk perubahan ini.
- Verifikasi gabungan: 58 test lolos, 484 assertion. Gate K02/deployment tetap belum terpenuhi; tiket belum DONE.
