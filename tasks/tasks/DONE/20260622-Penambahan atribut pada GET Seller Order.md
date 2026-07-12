# Penambahan Atribut pada GET Seller Order

Tanggal: 2026-06-22

## Ringkasan

Endpoint daftar dan detail order Seller perlu menampilkan alamat Buyer, metode pembayaran, dan metode pengiriman secara konsisten. Alamat untuk order baru harus disimpan sebagai snapshot transaksi agar tidak berubah ketika Buyer memperbarui profil. Order lama yang belum memiliki snapshot tetap menggunakan alamat terkini dari relasi `user` sebagai fallback.

Endpoint yang termasuk dalam scope:

1. `GET /api/seller/orders`
2. `GET /api/seller/orders/{id}`

Dokumen ini hanya berisi rancangan. Belum ada perubahan kode aplikasi, skema database, atau test.

## Kondisi Saat Ini

1. Response list Seller hanya menampilkan `buyer.id`, `buyer.name`, `buyer.email`, dan `buyer.phone`.
2. Response detail Seller sudah menampilkan nama metode pengiriman dan pembayaran, tetapi belum menampilkan kode keduanya secara lengkap.
3. Response list Seller belum menampilkan metode pengiriman maupun pembayaran.
4. Alamat Buyer hanya tersimpan pada tabel `users` melalui `address`, `landmark`, `latitude`, dan `longitude`.
5. Tabel `transactions` sudah menyimpan snapshot metode pengiriman dan pembayaran, tetapi belum menyimpan snapshot alamat Buyer.
6. `Antar Kurir Toko` merupakan metode pengiriman dengan kode `store_courier`, bukan status order.
7. Status order tetap menggunakan kontrak yang sudah ada, seperti `pending_payment`, `processing`, `on_the_way`, `ready_for_pickup`, `completed`, dan `canceled`.

## Keputusan Teknis

### 1. Snapshot Alamat pada Transaksi

Tambahkan kolom nullable berikut pada tabel `transactions`:

| Kolom | Tipe yang disarankan | Keterangan |
| --- | --- | --- |
| `buyer_address` | `text`, nullable | Alamat Buyer saat checkout |
| `buyer_landmark` | `string`, nullable | Patokan alamat saat checkout |
| `buyer_latitude` | `decimal(10,7)`, nullable | Latitude saat checkout |
| `buyer_longitude` | `decimal(10,7)`, nullable | Longitude saat checkout |
| `buyer_address_snapshot_at` | `timestamp`, nullable | Penanda bahwa order menggunakan snapshot alamat |

Kolom `buyer_address_snapshot_at` diperlukan untuk membedakan dua kondisi berikut secara eksplisit:

1. Order lama yang belum pernah mempunyai snapshot alamat.
2. Order baru yang snapshot-nya valid tetapi salah satu atribut opsional, seperti landmark atau koordinat, memang bernilai `null`.

Menggunakan `buyer_address IS NULL` sebagai satu-satunya penanda tidak cukup aman karena checkout saat ini belum memvalidasi bahwa profil Buyer selalu memiliki alamat.

Migration tidak melakukan backfill alamat dari profil Buyer. Backfill akan mengubah data profil saat ini menjadi seolah-olah merupakan alamat historis saat order dibuat, padahal kebenarannya tidak dapat dipastikan.

### 2. Penyimpanan Saat Checkout

Di dalam database transaction checkout, baca ulang row user terautentikasi dan kunci dengan `lockForUpdate()`. Snapshot alamat harus diambil dari row hasil query tersebut, bukan dari instance user yang telah dimuat sebelum database transaction dimulai. Dengan demikian, perubahan profil yang berjalan bersamaan harus menunggu checkout selesai dan tidak dapat menghasilkan snapshot campuran.

Ketika transaksi dibuat, salin data berikut dari user yang telah dibaca ulang dan dikunci:

```php
'buyer_address' => $user->address,
'buyer_landmark' => $user->landmark,
'buyer_latitude' => $user->latitude,
'buyer_longitude' => $user->longitude,
'buyer_address_snapshot_at' => now(),
```

Seluruh nilai disimpan di dalam transaksi database checkout yang sama dengan pembuatan order. Lock dipertahankan sampai database transaction selesai.

Setelah order dibuat, perubahan `users.address`, `users.landmark`, `users.latitude`, atau `users.longitude` tidak boleh mengubah alamat yang ditampilkan untuk order tersebut.

### 3. Strategi Fallback Order Lama

Pemilihan sumber alamat dilakukan pada level order, bukan per field:

```php
$usesSnapshot = $order->buyer_address_snapshot_at !== null;

$address = $usesSnapshot ? $order->buyer_address : $order->user?->address;
$landmark = $usesSnapshot ? $order->buyer_landmark : $order->user?->landmark;
$latitude = $usesSnapshot ? $order->buyer_latitude : $order->user?->latitude;
$longitude = $usesSnapshot ? $order->buyer_longitude : $order->user?->longitude;
```

Aturan ini menghasilkan perilaku berikut:

1. Order baru selalu menggunakan snapshot transaksi secara utuh.
2. Order lama dengan `buyer_address_snapshot_at = null` menggunakan relasi `user` secara utuh.
3. Snapshot dan profil terbaru tidak dicampur dalam satu alamat.
4. Bila data alamat fallback tidak tersedia, atribut alamat tetap dikirim dengan nilai `null`.

Fallback pada order lama merupakan mekanisme kompatibilitas, bukan jaminan alamat historis. Alamat yang ditampilkan dapat merupakan alamat terbaru Buyer karena tidak ada snapshot pada saat order lama dibuat.

Relasi `transactions.user_id` tetap menggunakan foreign key non-nullable dengan `cascadeOnDelete()`. Oleh karena itu, dalam kondisi database normal tidak ada transaksi tanpa relasi user: penghapusan Buyer juga menghapus transaksinya. Mapping tetap menggunakan akses null-safe sebagai defensive code, tetapi skenario relasi user hilang tidak perlu dibuat sebagai feature test.

## Kontrak Response

Struktur berikut harus sama pada endpoint list dan detail:

```json
{
  "buyer": {
    "id": "uuid-buyer",
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "phone": "+628111111111",
    "address": "Jl. Mawar No. 10, Blok A2",
    "landmark": "Dekat portal komplek",
    "latitude": -6.914744,
    "longitude": 107.60981
  },
  "delivery_method": "Antar Kurir Toko",
  "delivery_method_code": "store_courier",
  "payment_method": "Transfer Bank",
  "payment_method_code": "bank_transfer",
  "payment_method_option_name": "BCA",
  "payment_method_option_code": "bca"
}
```

Ketentuan nilai:

1. `delivery_method` adalah label snapshot transaksi, misalnya `Antar Kurir Toko` atau `Ambil ke Toko`.
2. `delivery_method_code` adalah kode stabil untuk logika client, misalnya `store_courier` atau `pickup`.
3. `payment_method` adalah label snapshot transaksi, misalnya `Transfer Bank` atau `QRIS`.
4. `payment_method_code` adalah kode stabil untuk logika client, misalnya `bank_transfer` atau `qr_payment`.
5. `payment_method_option_name` dan `payment_method_option_code` berisi opsi bank untuk Transfer Bank.
6. Untuk metode tanpa opsi seperti QRIS, kedua atribut opsi pembayaran dikirim dengan nilai `null` dan tidak dihilangkan dari response.
7. Field baru bersifat additive sehingga tidak menghapus atau mengganti field response yang sudah digunakan client.

Contoh QRIS:

```json
{
  "payment_method": "QRIS",
  "payment_method_code": "qr_payment",
  "payment_method_option_name": null,
  "payment_method_option_code": null
}
```

Contoh pickup:

```json
{
  "delivery_method": "Ambil ke Toko",
  "delivery_method_code": "pickup"
}
```

## Rancangan Perubahan Kode

### Migration

1. Buat migration baru untuk menambahkan lima kolom snapshot alamat ke tabel `transactions`.
2. Seluruh kolom dibuat nullable untuk menjaga kompatibilitas order lama.
3. `down()` hanya menghapus kolom yang ditambahkan migration tersebut.
4. Tidak ada update massal atau backfill terhadap transaksi lama.

### Model Transaction

1. Tambahkan kelima atribut snapshot ke daftar fillable.
2. Tambahkan cast:
   - `buyer_latitude` menjadi `float`.
   - `buyer_longitude` menjadi `float`.
   - `buyer_address_snapshot_at` menjadi `datetime`.
3. Pertimbangkan helper pada model atau mapper khusus untuk menyelesaikan alamat efektif agar aturan fallback tidak diduplikasi antara list dan detail.

### Checkout

1. Di dalam `DB::transaction()`, baca ulang Buyer berdasarkan ID user terautentikasi menggunakan `lockForUpdate()`.
2. Salin snapshot alamat dan timestamp penanda dari row Buyer yang telah dikunci ketika `Transaction` dibuat.
3. Lock dipertahankan sampai checkout selesai agar update profil yang berjalan bersamaan menunggu database transaction checkout selesai.
4. Snapshot dilakukan di dalam database transaction checkout yang sama dengan pembuatan order.
5. Perubahan ini tidak menambah payload request checkout.
6. Perubahan ini tidak mengubah validasi checkout dalam scope pekerjaan ini.

### GET Seller Order List

1. Pertahankan eager loading relasi `user` karena diperlukan untuk identitas Buyer dan fallback order lama.
2. Tambahkan alamat efektif ke object `buyer`.
3. Tambahkan seluruh field metode pengiriman dan pembayaran beserta kode dan opsi.

### GET Seller Order Detail

1. Pertahankan eager loading relasi `user`.
2. Tambahkan alamat efektif ke object `buyer` dengan aturan yang sama seperti list.
3. Lengkapi `delivery_method_code`, `payment_method_code`, dan `payment_method_option_code`.
4. Pastikan struktur dan tipe atribut sama dengan endpoint list.

### Pengurangan Duplikasi

List dan detail saat ini mempunyai mapping order terpisah. Untuk mencegah kontrak kembali berbeda, gunakan class mapper khusus `app/Support/SellerOrderResponseMapper.php` untuk memusatkan bagian berikut:

1. Mapping Buyer dan resolusi fallback alamat.
2. Mapping metode pengiriman.
3. Mapping metode pembayaran.

Controller list dan detail tetap menyusun field khusus masing-masing, sedangkan mapper menyediakan shared fields tersebut. Mapping tidak ditempatkan pada model `Transaction` agar representasi API Seller tidak tercampur dengan model domain. Refactor dibatasi pada kebutuhan konsistensi response Seller Order dan tidak perlu mengubah seluruh endpoint transaksi lain dalam pekerjaan ini.

### Dokumentasi API

1. Perbarui bagian `GET /api/seller/orders` dan `GET /api/seller/orders/{id}` pada `API_DOCUMENTATION.md`.
2. Dokumentasikan seluruh field Buyer, metode pengiriman, metode pembayaran, dan opsi pembayaran yang ditambahkan.
3. Jelaskan bahwa alamat order baru berasal dari snapshot checkout, sedangkan order lama tanpa marker menggunakan profil Buyer terbaru sebagai fallback.
4. Jelaskan bahwa field opsi pembayaran tetap dikirim dengan nilai `null` untuk metode tanpa opsi.

## Rancangan Feature Test

### Snapshot Checkout

1. Checkout menyimpan `buyer_address`, `buyer_landmark`, `buyer_latitude`, `buyer_longitude`, dan `buyer_address_snapshot_at`.
2. Setelah checkout, ubah alamat profil Buyer.
3. Pastikan list dan detail Seller tetap menampilkan alamat snapshot saat checkout.
4. Pastikan atribut snapshot opsional yang `null` tetap `null` dan tidak mengambil nilai profil terbaru.

### Fallback Order Lama

1. Buat transaksi dengan seluruh kolom snapshot dan `buyer_address_snapshot_at` bernilai `null`.
2. Pastikan list Seller menggunakan alamat dari relasi `user`.
3. Pastikan detail Seller menggunakan alamat dari relasi `user`.
4. Pastikan response tetap valid dengan nilai `null` jika atribut alamat user tidak tersedia.
5. Tidak perlu membuat skenario transaksi tanpa relasi user karena foreign key `transactions.user_id` tetap non-nullable dan menggunakan `cascadeOnDelete()`.

### Konsistensi List dan Detail

Untuk order yang sama, pastikan endpoint list dan detail mengembalikan nilai identik untuk:

1. Seluruh object `buyer`.
2. `delivery_method` dan `delivery_method_code`.
3. `payment_method` dan `payment_method_code`.
4. `payment_method_option_name` dan `payment_method_option_code`.

### Authorization dan Ownership

1. Pastikan Buyer atau role selain Seller mendapat response `403` ketika mengakses list maupun detail Seller Order.
2. Pastikan list Seller tidak memuat order Seller lain, termasuk object Buyer, alamat, dan koordinatnya.
3. Pastikan detail order Seller lain mengembalikan `404` dan tidak membocorkan data Buyer.
4. Ownership scoping harus diterapkan pada query sebelum pagination dan sebelum response mapping.
5. Data Buyer dipetakan menggunakan allowlist field eksplisit; jangan serialize model `User` secara langsung.

### Transfer Bank

1. Buat order dengan `payment_method = Transfer Bank` dan `payment_method_code = bank_transfer`.
2. Gunakan opsi bank, misalnya `BCA` dengan kode `bca`.
3. Pastikan nama dan kode metode serta opsi tampil pada list dan detail.

### QRIS

1. Buat atau aktifkan fixture metode QRIS untuk kebutuhan test.
2. Gunakan `payment_method = QRIS` dan `payment_method_code = qr_payment`.
3. Pastikan field opsi nama dan kode bernilai `null`.
4. Test tidak boleh bergantung pada QRIS dari seeder production selama konfigurasi QRIS masih dinonaktifkan pada seeder.

### Store Courier

1. Buat order dengan `delivery_method = Antar Kurir Toko` dan `delivery_method_code = store_courier`.
2. Pastikan kedua field tampil pada list dan detail.
3. Pastikan status order tetap berada pada field `status` dan `status_code`, serta tidak menggunakan label `Antar Kurir Toko`.

### Pickup

1. Buat order dengan `delivery_method = Ambil ke Toko` dan `delivery_method_code = pickup`.
2. Pastikan kedua field tampil pada list dan detail.
3. Pastikan field pickup yang sudah ada tetap dikirim oleh detail tanpa perubahan perilaku.

## Acceptance Criteria

1. Order baru menyimpan snapshot alamat Buyer saat checkout.
2. Perubahan profil Buyer setelah checkout tidak mengubah alamat order baru.
3. Order lama tanpa marker snapshot mengambil alamat dari relasi `user`.
4. Endpoint list dan detail Seller mempunyai struktur Buyer, metode pengiriman, dan metode pembayaran yang konsisten.
5. Transfer Bank menampilkan opsi bank beserta kode.
6. QRIS mengembalikan opsi pembayaran sebagai `null`.
7. `store_courier` ditampilkan sebagai metode `Antar Kurir Toko`.
8. `pickup` ditampilkan sebagai metode `Ambil ke Toko`.
9. Filter dan transisi status order yang sudah ada tidak berubah.
10. Seluruh feature test lama tetap lulus.
11. Role selain Seller tidak dapat mengakses endpoint list maupun detail Seller Order.
12. Seller tidak dapat melihat object Buyer, alamat, atau koordinat dari order Seller lain.
13. `API_DOCUMENTATION.md` mencerminkan kontrak response list dan detail terbaru beserta aturan snapshot/fallback alamat.

## Risiko dan Mitigasi

1. Alamat order lama tidak mencerminkan kondisi saat order dibuat.
   - Mitigasi: dokumentasikan bahwa fallback memakai profil terbaru dan hanya berlaku untuk transaksi tanpa marker snapshot.
2. Snapshot parsial tercampur dengan profil terbaru.
   - Mitigasi: pilih sumber pada level order menggunakan `buyer_address_snapshot_at`, bukan fallback per field.
3. Profil Buyer berubah bersamaan dengan proses checkout.
   - Mitigasi: baca ulang dan kunci row Buyer dengan `lockForUpdate()` di dalam database transaction checkout, lalu ambil snapshot dari row tersebut.
4. Kontrak list dan detail kembali berbeda.
   - Mitigasi: gunakan `SellerOrderResponseMapper` dan test konsistensi kedua endpoint.
5. Client menganggap `Antar Kurir Toko` sebagai status.
   - Mitigasi: pertahankan pemisahan eksplisit antara `delivery_method_code` dan `status_code`.
6. QRIS belum aktif pada seeder aplikasi.
   - Mitigasi: fixture test membuat metode yang diperlukan secara eksplisit; aktivasi metode pembayaran production diperlakukan sebagai keputusan terpisah.
7. Paparan data pribadi Buyer bertambah pada response Seller.
   - Mitigasi: endpoint tetap dibatasi ke Seller yang memiliki item pada order tersebut, mapping memakai allowlist field eksplisit, regression test memastikan tidak ada akses silang, dan hanya atribut alamat yang diperlukan untuk fulfillment yang dikirim. Data Buyer tidak boleh ditulis ke log.

## Out of Scope

1. Mengaktifkan QRIS atau COD pada seeder dan environment production.
2. Mengubah alur atau validasi status order.
3. Menambah filter berdasarkan metode pengiriman atau pembayaran.
4. Melakukan backfill alamat historis ke transaksi lama.
5. Menyimpan snapshot nama, email, atau nomor telepon Buyer.
6. Mengubah kontrak endpoint transaksi Buyer, dashboard Seller, Finance, atau Agent.
7. Menambah validasi bahwa profil Buyer wajib lengkap sebelum checkout.

## Rencana Verifikasi Implementasi

Setelah rancangan ini diimplementasikan, jalankan formatting dan test yang relevan:

```bash
./vendor/bin/pint app/Models/Transaction.php app/Http/Controllers/Api/CheckoutController.php app/Http/Controllers/Api/GetSellerOrderListController.php app/Http/Controllers/Api/GetSellerOrderDetailController.php tests/Feature/CheckoutApiTest.php tests/Feature/SellerApiTest.php database/migrations
php artisan test tests/Feature/CheckoutApiTest.php tests/Feature/SellerApiTest.php
```

Setelah test terfokus lulus, jalankan seluruh test suite:

```bash
php artisan test
```
