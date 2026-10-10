# Laporan pengerjaan: checkout saat toko tutup

Tanggal: 10 Oktober 2026

## Kebutuhan bisnis

Seller dapat menutup toko untuk menghentikan pesanan baru. Pesanan yang sudah dibuat tetap harus bisa diproses, tanpa pembatalan otomatis. Toko tetap terlihat pada daftar tenant.

## Hasil implementasi

- `POST /api/checkout` membaca status tenant terbaru langsung dari database di dalam transaksi, menggunakan `lockForUpdate()` untuk menjaga status selama checkout disimpan pada database yang mendukung row lock.
- Status mengikuti `Tenant::isOpenAt()`: nilai manual `is_open` menjadi acuan; jika null, gunakan jam operasional dalam zona waktu Asia/Jakarta. Tenant tanpa status manual dan tanpa jam operasional dianggap tutup, sesuai perilaku GET tenant saat ini.
- Jika salah satu tenant dalam keranjang tutup, seluruh checkout ditolak dengan HTTP 422. Tidak ada checkout parsial.
- Transaksi dibatalkan secara atomik: transaksi baru, item transaksi, riwayat status, dan pengurangan stok tidak tersimpan. Keranjang, pilihan pengiriman, serta kuota promo tetap utuh.
- Penutupan toko tidak mengubah status atau riwayat pesanan yang sudah ada. Seller tetap dapat menerima dan memproses pesanan sampai selesai, termasuk pengiriman oleh kurir maupun pengambilan di toko.
- Dokumentasi API diperbarui dengan respons toko tutup dan perilaku pesanan berjalan.

## Kontrak API

Endpoint checkout dan payload tetap sama. Respons ketika toko tutup:

```json
{
  "message": "Toko sedang tutup. Checkout tidak dapat dilanjutkan.",
  "errors": {
    "tenant_id": "tenant-uuid",
    "tenant_name": "Toko Aminah"
  }
}
```

HTTP status: `422 Unprocessable Entity`. Mobile dapat menampilkan pesan ini dan mempertahankan keranjang agar pembeli bisa mencoba lagi setelah toko buka atau menghapus produk toko tersebut.

## File yang berubah

- `app/Http/Controllers/Api/CheckoutController.php`: pemeriksaan status toko terbaru saat checkout.
- `tests/Feature/CheckoutApiTest.php`: penolakan toko tutup, pemulihan setelah buka kembali, jam operasional, serta integritas keranjang, stok, transaksi, dan promo. Fixture sukses diperjelas sebagai toko buka.
- `tests/Feature/SellerApiTest.php`: penutupan toko melalui API tidak membatalkan pesanan; pesanan tetap bisa diproses sampai selesai untuk kurir dan pickup.
- `API_DOCUMENTATION.md`: kontrak error dan dampak status toko terhadap checkout.

## Verifikasi

Pengujian menggunakan SQLite `:memory:` sesuai `phpunit.xml`, tanpa mengubah database aplikasi.

- `php -d xdebug.mode=off vendor/bin/phpunit --filter 'CheckoutApiTest|closing_store_keeps_existing_orders|SellerTenantStatusApiTest' --no-progress`: **lulus, 24 test dan 174 assertion**. Meliputi seluruh suite checkout, suite toggle tenant, dan test pesanan berjalan saat toko ditutup.
- `vendor/bin/pint --test --dirty`: **lulus**.
- `git diff --check`: **lulus**.

Pada run awal, fixture pesanan kurir/pickup menghasilkan nomor telepon buyer yang sama. Fixture diperbaiki dengan kode pesanan berbeda, lalu pengujian diulang dan seluruhnya lulus. Seluruh suite repository tidak dijalankan; verifikasi difokuskan pada alur yang terdampak.

## Batas lingkup dan operasional

Tidak ada migrasi atau dependensi baru pada tahap ini. Migrasi kolom `is_open` dari fitur toggle sebelumnya harus sudah diterapkan sebelum aplikasi digunakan.

Jadwal hari operasional, batas waktu toggle manual, reminder H+1, banned super admin, penahanan dana, dan pembatalan karena fraud belum termasuk tahap ini. Status manual masih berlaku sampai seller mengubahnya kembali.

Jika checkout lebih dahulu memperoleh lock tenant, checkout tersebut dapat selesai sebelum penutupan toko tersimpan. Jika penutupan sudah tersimpan sebelum tenant dibaca checkout, checkout ditolak. Uji SQLite membuktikan perilaku dan rollback, bukan konkurensi row lock pada database produksi.
