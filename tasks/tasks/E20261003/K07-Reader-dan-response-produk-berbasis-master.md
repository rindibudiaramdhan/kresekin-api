# E20261003-K07 — Reader dan response produk berbasis master

- Tanggal: 2026-10-03
- Status: Reader lokal teruji — aktivasi penuh menunggu K06/G3
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](timeline.md)
- PIC peran: Backend API; nama personel ditetapkan pada K01
- Target baseline: M3–M4
- Estimasi: 3–4 hari kerja effort, bukan janji durasi kalender
- Dependensi: K03, K06; pengembangan dapat dimulai setelah K03
- Tautan dependensi: [K03](K03-Standardisasi-writer-produk-API.md), [K06](K06-Eksekusi-dan-rekonsiliasi-backfill.md)

## Tujuan

Semua reader produk membaca ID/master dengan bentuk response konsisten.

## Cakupan

1. Audit list/detail dan nested product responses; sediakan product_category_id, product_category id/name/slug dan category turunan.
2. Pindahkan metadata ikon/warna ke pengelola kategori produk berbasis slug master dengan fallback netral.
3. Eager load master; jangan membaca nama tersimpan sebagai sumber utama; fallback record lama hanya selama backfill belum tuntas.

## Acceptance criteria dan verifikasi

- [x] Create/edit/list/detail konsisten; rename nama master langsung tampil, slug berasal dari master.
- [x] Kategori baru memiliki metadata netral dan tidak membutuhkan Tenant::CATEGORIES.
- [ ] Setelah rekonsiliasi seluruh reader produk memakai ID; tidak ada dependency aktif ke teks produk untuk pembacaan kategori.
- [x] Test rename, master baru, soft-delete/visibility dan nested response lulus; pemeriksaan query membuktikan tidak ada N+1 master.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.

## Implementasi dan bukti lokal — 2026-10-04

- Presenter bersama menyajikan `product_category_id`, master allowlist id/name/slug serta category turunan pada create/edit, list/detail seller/buyer dan nested product keranjang. Semua query tersebut eager-load master. Web index dan kolom admin membaca nama master; form legacy K04 tetap menampilkan teks lama untuk membantu pemilihan eksplisit.
- `ProductCategoryMetadata` memetakan slug master ke metadata existing. Nama master dapat diganti tanpa mengganti slug/icon/warna. Master baru memperoleh ikon `category`, background #F3F4F6 dan icon #6B7280; tidak memakai Tenant::CATEGORIES.
- Fallback baca teks hanya untuk record dengan ID null selama backfill belum tuntas; ID yang terisi tetapi relasinya hilang tidak dikarang dari teks. Setelah G3 membuktikan null/orphan nol, semua record memakai master. Penghapusan sisa kode fallback/schema menunggu K12/K13.
- Audit nested product: GET /api/cart menyajikan product live sehingga mendapat field master. Response item transaksi menyimpan snapshot nama/harga/gambar, tidak mempunyai kategori dan tidak diubah. Agregat kategori tenant/filter tetap lingkup K08.
- `ProductCategoryReaderTest`: 6 test meliputi rename, master baru, soft-delete/active visibility, ownership seller, legacy null-ID, nested cart/subtotal, web display, serta query log (satu query master untuk enam produk pada list seller, buyer dan cart).
- `php artisan test --filter='ProductCategoryReaderTest|ProductCategoryWriterApiTest|ProductCategoryWebWriterTest|ProductApiTest|CartApiTest|SellerApiTest'`: **67 test lolos, 555 assertion**, SQLite in-memory. Verifikasi K02/K05 sebelumnya tetap 58 test/484 assertion pada gabungan yang berbeda; angka tidak dijumlahkan karena ada test tumpang tindih.
- K06 dan G3 belum mempunyai rekonsiliasi produksi; checklist tanpa dependency teks pada seluruh data tetap terbuka. Filter slug buyer masih memakai teks sampai K08 dan tidak dinyatakan tahan rename. Jangan mengaktifkan perpindahan penuh reader/filter sebelum gate. Tiket belum DONE.

- Pemeriksaan tambahan `php artisan test --filter=ProductDetailApiTest`: 1 lolos, 1 gagal (4 assertion). Kegagalan autentikasi mengharapkan `Unauthenticated.` sedangkan middleware existing memberi `Tidak terautentikasi.`; identik dengan kegagalan baseline yang sudah dibuktikan sebelum perubahan K02. Middleware dan ekspektasi test tersebut tidak diubah.
