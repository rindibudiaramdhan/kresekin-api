# API Documentation

Dokumentasi ini dibuat dari route, controller, request validation, model, dan feature test pada codebase Laravel ini.

## Ringkasan

- Base URL lokal default: `http://127.0.0.1:8000`
- Prefix API: `/api`
- Format body: `application/json`, kecuali upload gambar memakai `multipart/form-data`
- Semua field identifier entity aplikasi memakai UUID string, misalnya `id`, `user_id`, `tenant_id`, `product_id`, `transaction_id`, `housing_area_id`, `category_id`, dan path parameter seperti `{id}` atau `{transactionId}`. Field numerik bisnis seperti `quantity`, `price`, `amount`, `sort_order`, `limit`, dan `page` tetap integer.
- Format response umum:

```json
{
  "message": "Pesan hasil request.",
  "data": {}
}
```

Endpoint list yang memakai pagination biasanya mengembalikan:

```json
{
  "message": "Daftar berhasil diambil.",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "last_page": 1,
    "total": 0,
    "from": null,
    "to": null
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": null
  }
}
```

## Autentikasi

Endpoint yang membutuhkan login memakai bearer token:

```bash
curl -H "Authorization: Bearer $TOKEN" \
  http://127.0.0.1:8000/api/users/profile
```

Role yang tersedia:

- `buyer`
- `seller`
- `agent`
- `finance`

Authentication flow:

1. Register atau login dengan email/phone untuk menerima OTP.
2. Verifikasi OTP melalui `POST /api/users/verify-otp`.
3. Simpan `data.token` dari response verifikasi OTP sebagai bearer token.

Nomor telepon menerima format `+6281234567890`. Beberapa endpoint login/OTP menormalisasi nomor yang diawali `0` menjadi `+62`.

## Status dan Error Umum

- `200 OK`: request berhasil.
- `201 Created`: data berhasil dibuat.
- `401 Unauthorized`: bearer token kosong, salah, atau kedaluwarsa.
- `403 Forbidden`: token valid, tetapi role tidak sesuai.
- `404 Not Found`: data tidak ditemukan.
- `422 Unprocessable Entity`: validasi gagal atau business rule tidak terpenuhi.
- `502 Bad Gateway`: dependency eksternal gagal, misalnya data wilayah BPS.

Contoh validasi gagal:

```json
{
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "email": ["The email field is required when type is email."]
  }
}
```

## Healthcheck

### GET `/api/vershealthcheck`

Mengecek status API, versi API, versi Laravel, dan timestamp server.

```bash
curl http://127.0.0.1:8000/api/vershealthcheck
```

## Auth dan Session

Endpoint berikut tidak butuh bearer token, kecuali ditandai.

### POST `/api/users/{role}/register`

Mendaftarkan user berdasarkan role dan mengirim OTP. Role: `buyer`, `seller`, `agent`, `finance`.

Body:

- `type` wajib: `email` atau `phone`
- `email` wajib jika `type=email`, unik per role
- `phone` wajib jika `type=phone`, unik per role, regex `+?[0-9]{8,15}`

```bash
curl -X POST http://127.0.0.1:8000/api/users/buyer/register \
  -H "Content-Type: application/json" \
  -d '{"type":"email","email":"buyer@example.com"}'
```

Alias khusus:

- `POST /api/agent/register`
- `POST /api/finance/register`

```bash
curl -X POST http://127.0.0.1:8000/api/agent/register \
  -H "Content-Type: application/json" \
  -d '{"type":"email","email":"agent@example.com"}'

curl -X POST http://127.0.0.1:8000/api/finance/register \
  -H "Content-Type: application/json" \
  -d '{"type":"email","email":"finance@example.com"}'
```

### POST `/api/users/{role}/login`

Mengirim OTP login ke user yang sudah terdaftar.

Body sama dengan register.

```bash
curl -X POST http://127.0.0.1:8000/api/users/buyer/login \
  -H "Content-Type: application/json" \
  -d '{"type":"phone","phone":"081234567890"}'
```

Alias khusus:

- `POST /api/agent/login`
- `POST /api/finance/login`

```bash
curl -X POST http://127.0.0.1:8000/api/agent/login \
  -H "Content-Type: application/json" \
  -d '{"type":"email","email":"agent@example.com"}'

curl -X POST http://127.0.0.1:8000/api/finance/login \
  -H "Content-Type: application/json" \
  -d '{"type":"email","email":"finance@example.com"}'
```

### POST `/api/users/{role}/resend-otp`

Mengirim ulang OTP untuk role tertentu.

```bash
curl -X POST http://127.0.0.1:8000/api/users/buyer/resend-otp \
  -H "Content-Type: application/json" \
  -d '{"type":"email","email":"buyer@example.com"}'
```

Alias khusus:

- `POST /api/agent/resend-otp`
- `POST /api/finance/resend-otp`

```bash
curl -X POST http://127.0.0.1:8000/api/agent/resend-otp \
  -H "Content-Type: application/json" \
  -d '{"type":"email","email":"agent@example.com"}'

curl -X POST http://127.0.0.1:8000/api/finance/resend-otp \
  -H "Content-Type: application/json" \
  -d '{"type":"email","email":"finance@example.com"}'
```

### POST `/api/users/verify-otp`

Memverifikasi OTP 6 digit dan membuat session token.

Body:

- `type` wajib: `email` atau `phone`
- `otp` wajib: 6 digit
- `email` wajib jika `type=email`
- `phone` wajib jika `type=phone`

```bash
curl -X POST http://127.0.0.1:8000/api/users/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"type":"email","email":"buyer@example.com","otp":"123456"}'
```

### POST `/api/users/logout`

Butuh token. Menghapus session token aktif.

```bash
curl -X POST http://127.0.0.1:8000/api/users/logout \
  -H "Authorization: Bearer $TOKEN"
```

### POST `/api/users/refresh-session`

Butuh token. Membuat token baru dari session token aktif.

```bash
curl -X POST http://127.0.0.1:8000/api/users/refresh-session \
  -H "Authorization: Bearer $TOKEN"
```

### POST `/api/users/devices`

Butuh token. Mendaftarkan atau memperbarui device token user.

Body:

- `device_token` wajib, string, max 500
- `platform` wajib: `android`, `ios`, atau `web`
- `device_name` opsional, max 100

```bash
curl -X POST http://127.0.0.1:8000/api/users/devices \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_token":"firebase-token","platform":"android","device_name":"Pixel 8"}'
```

## Profile dan Master Data Umum

### GET `/api/users/profile`

Butuh token. Mengambil profil user aktif.

```bash
curl http://127.0.0.1:8000/api/users/profile \
  -H "Authorization: Bearer $TOKEN"
```

### PUT `/api/users/profile`

Butuh token. Memperbarui profil user aktif.

Body:

- `name` wajib, max 255
- `email` wajib, email, unik per role
- `phone` opsional, regex `+?[0-9]{8,15}`, unik per role
- `housing_area_id` wajib UUID, harus ada di `housing_areas`
- `address` wajib, max 1000
- `landmark` opsional, max 255

```bash
curl -X PUT http://127.0.0.1:8000/api/users/profile \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Budi","email":"budi@example.com","phone":"+6281234567890","housing_area_id":"eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee","address":"Jl. Melati No. 1","landmark":"Dekat masjid"}'
```

### GET `/api/housing-areas`

Butuh token. Mengambil daftar area perumahan.

```bash
curl http://127.0.0.1:8000/api/housing-areas \
  -H "Authorization: Bearer $TOKEN"
```

### GET `/api/product-categories`

Butuh token. Mengambil kategori produk master.

```bash
curl http://127.0.0.1:8000/api/product-categories \
  -H "Authorization: Bearer $TOKEN"
```

### GET `/api/product-units`

Butuh token. Mengambil daftar satuan produk aktif yang dapat digunakan pada field `product_unit_id` saat membuat atau mengubah produk.

```bash
curl http://127.0.0.1:8000/api/product-units \
  -H "Authorization: Bearer $TOKEN"
```

## Data Wilayah Indonesia

Endpoint wilayah mengambil data dari service BPS. Endpoint ini tidak memakai bearer token.

### GET `/api/indonesia/provinces`

```bash
curl http://127.0.0.1:8000/api/indonesia/provinces
```

### GET `/api/indonesia/regencies?province_code={code}`

`province_code` wajib 2 digit kode BPS.

```bash
curl "http://127.0.0.1:8000/api/indonesia/regencies?province_code=32"
```

### GET `/api/indonesia/districts?regency_code={code}`

`regency_code` wajib 4 digit kode BPS.

```bash
curl "http://127.0.0.1:8000/api/indonesia/districts?regency_code=3273"
```

### GET `/api/indonesia/villages?district_code={code}`

`district_code` wajib 7 digit kode BPS.

```bash
curl "http://127.0.0.1:8000/api/indonesia/villages?district_code=3273141"
```

## Buyer API

Semua endpoint pada bagian ini butuh token dengan role `buyer`.

### GET `/api/delivery-methods`

Mengambil metode pengiriman aktif.

```bash
curl http://127.0.0.1:8000/api/delivery-methods \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/order-time-options`

Mengambil opsi waktu pengambilan aktif.

```bash
curl http://127.0.0.1:8000/api/order-time-options \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/payment-methods`

Mengambil metode pembayaran aktif beserta opsi aktifnya.

```bash
curl http://127.0.0.1:8000/api/payment-methods \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/products`

Mengambil daftar produk aktif.

Query opsional:

- `limit`: 1 sampai 100, default 10
- `page`: halaman pagination Laravel
- `category`: slug kategori produk
- `tenant_id`: UUID tenant
- `name`: pencarian nama produk
- `is_promo`: `true`, `false`, `1`, atau `0`

```bash
curl "http://127.0.0.1:8000/api/products?limit=10&category=sembako&is_promo=true" \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/products/{id}`

Mengambil detail produk aktif.

```bash
curl http://127.0.0.1:8000/api/products/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/tenants`

Mengambil daftar tenant untuk buyer.

Query opsional:

- `limit`: 1 sampai 100, default 10
- `page`: halaman
- `product_category`: slug kategori produk

```bash
curl "http://127.0.0.1:8000/api/tenants?limit=10&product_category=sembako" \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/tenants/categories`

Mengambil daftar kategori tenant dari konstanta `Tenant::CATEGORIES`, lengkap dengan metadata UI.

```bash
curl http://127.0.0.1:8000/api/tenants/categories \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/cart`

Mengambil cart user aktif. Jika cart belum ada, sistem akan membuat cart kosong.

```bash
curl http://127.0.0.1:8000/api/cart \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### PATCH `/api/cart/delivery-method`

Mengatur metode pengiriman cart.

Body:

- `delivery_method_code` wajib, harus kode delivery method aktif

```bash
curl -X PATCH http://127.0.0.1:8000/api/cart/delivery-method \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"delivery_method_code":"store_courier"}'
```

### POST `/api/cart/items`

Menambahkan produk ke cart. Jika produk sudah ada di cart, quantity akan di-upsert sesuai logic controller.

Body:

- `product_id` wajib UUID, harus ada di `products`
- `quantity` wajib, integer 1 sampai 999

```bash
curl -X POST http://127.0.0.1:8000/api/cart/items \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id":"bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb","quantity":2}'
```

### PATCH `/api/cart/items/{id}`

Mengubah quantity item cart milik user aktif.

Body:

- `quantity` wajib, integer 1 sampai 999

```bash
curl -X PATCH http://127.0.0.1:8000/api/cart/items/cccccccc-cccc-4ccc-8ccc-cccccccccccc \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"quantity":3}'
```

### DELETE `/api/cart/items/{id}`

Menghapus item cart milik user aktif.

```bash
curl -X DELETE http://127.0.0.1:8000/api/cart/items/cccccccc-cccc-4ccc-8ccc-cccccccccccc \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### POST `/api/promo-codes/validate`

Memvalidasi kode promo aktif.

Body:

- `code` wajib, string max 50

```bash
curl -X POST http://127.0.0.1:8000/api/promo-codes/validate \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"code":"HEMAT10"}'
```

### POST `/api/checkout`

Membuat transaksi dari cart buyer. Setelah checkout berhasil, cart item dihapus, delivery method cart direset, stok produk dikurangi, dan promo usage bertambah jika promo dipakai.

Body:

- `delivery_method_code` wajib, kode delivery method aktif
- `payment_method_code` wajib, kode payment method aktif
- `payment_method_option_code` opsional, wajib jika payment method membutuhkan opsi
- `pickup_time_option` opsional, wajib jika delivery method membutuhkan order time
- `pickup_scheduled_at` opsional, format `H:i`, wajib jika order time option membutuhkan schedule
- `promo_code` opsional

Business rule:

- Cart tidak boleh kosong.
- Semua produk harus aktif.
- Stok produk harus cukup.
- Promo harus aktif, belum kedaluwarsa, kuota tersedia, dan minimum order terpenuhi.

```bash
curl -X POST http://127.0.0.1:8000/api/checkout \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"delivery_method_code":"store_courier","payment_method_code":"bank_transfer","payment_method_option_code":"bca","promo_code":"HEMAT10"}'
```

### GET `/api/users/transactions`

Mengambil riwayat transaksi buyer.

```bash
curl http://127.0.0.1:8000/api/users/transactions \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/users/transactions/{transactionId}`

Mengambil detail transaksi buyer.

```bash
curl http://127.0.0.1:8000/api/users/transactions/dddddddd-dddd-4ddd-8ddd-dddddddddddd \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/cancellation-reason-categories`

Mengambil kategori alasan pembatalan aktif untuk buyer.

```bash
curl http://127.0.0.1:8000/api/cancellation-reason-categories \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

## Seller API

Semua endpoint pada bagian ini butuh token dengan role `seller`.

### Dashboard Seller

| Method | Endpoint | Deskripsi |
| --- | --- | --- |
| GET | `/api/seller/dashboard` | Ringkasan dashboard seller. |
| GET | `/api/seller/dashboard/profile` | Profil toko utama seller. |
| GET | `/api/seller/dashboard/revenue-today` | Revenue hari ini. |
| GET | `/api/seller/dashboard/revenue-change` | Perubahan revenue. |
| GET | `/api/seller/dashboard/transactions-today` | Jumlah transaksi hari ini. |
| GET | `/api/seller/dashboard/orders-today/counts` | Count order hari ini per status. |
| GET | `/api/seller/dashboard/orders/new-preview` | Preview order baru. |
| GET | `/api/seller/dashboard/top-products-today` | Produk terlaris hari ini. |

```bash
curl http://127.0.0.1:8000/api/seller/dashboard \
  -H "Authorization: Bearer $SELLER_TOKEN"

curl http://127.0.0.1:8000/api/seller/dashboard/profile \
  -H "Authorization: Bearer $SELLER_TOKEN"

curl http://127.0.0.1:8000/api/seller/dashboard/revenue-today \
  -H "Authorization: Bearer $SELLER_TOKEN"

curl http://127.0.0.1:8000/api/seller/dashboard/revenue-change \
  -H "Authorization: Bearer $SELLER_TOKEN"

curl http://127.0.0.1:8000/api/seller/dashboard/transactions-today \
  -H "Authorization: Bearer $SELLER_TOKEN"

curl http://127.0.0.1:8000/api/seller/dashboard/orders-today/counts \
  -H "Authorization: Bearer $SELLER_TOKEN"

curl http://127.0.0.1:8000/api/seller/dashboard/orders/new-preview \
  -H "Authorization: Bearer $SELLER_TOKEN"

curl http://127.0.0.1:8000/api/seller/dashboard/top-products-today \
  -H "Authorization: Bearer $SELLER_TOKEN"
```

### GET `/api/seller/tenants`

Mengambil daftar tenant milik seller aktif.

```bash
curl http://127.0.0.1:8000/api/seller/tenants \
  -H "Authorization: Bearer $SELLER_TOKEN"
```

### POST `/api/seller/tenants`

Membuat tenant/toko untuk seller aktif.

Body:

- `owner_name` wajib
- `owner_phone` opsional, dinormalisasi dari `08...` ke `+628...`
- `owner_email` opsional
- `agent_code` opsional, harus milik user role `agent`
- `name` wajib
- `profile_picture_url` opsional
- `category_id` wajib UUID, harus ada di `product_categories`
- `category` opsional, salah satu `Tenant::CATEGORIES`
- `location` wajib
- `housing_area_ids` wajib array UUID, minimal 1 maksimal 3
- `rating` opsional, 0 sampai 5
- `latitude` opsional, -90 sampai 90
- `longitude` opsional, -180 sampai 180
- `open_time` dan `close_time` opsional, format `H:i`

```bash
curl -X POST http://127.0.0.1:8000/api/seller/tenants \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"owner_name":"Asep Pemilik","owner_phone":"081234567890","owner_email":"asep@example.com","agent_code":"KA-20265","name":"Toko Asep","category_id":"ffffffff-ffff-4fff-8fff-ffffffffffff","location":"Jl Asri Raya No 45","housing_area_ids":["eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee"],"latitude":-6.2,"longitude":106.8,"open_time":"07:00","close_time":"21:00"}'
```

### GET `/api/seller/orders`

Mengambil daftar order yang berisi item tenant seller aktif.

Query opsional:

- `status_code`: `pending_payment`, `accepted_by_store`, `processing`, `on_the_way`, `completed`, `canceled`

```bash
curl "http://127.0.0.1:8000/api/seller/orders?status_code=processing" \
  -H "Authorization: Bearer $SELLER_TOKEN"
```

### GET `/api/seller/orders/{id}`

Mengambil detail order seller.

```bash
curl http://127.0.0.1:8000/api/seller/orders/dddddddd-dddd-4ddd-8ddd-dddddddddddd \
  -H "Authorization: Bearer $SELLER_TOKEN"
```

### PATCH `/api/seller/orders/{id}/status`

Mengubah status order seller.

Body:

- `status_code` wajib: `accepted_by_store`, `processing`, `on_the_way`, `completed`, atau `canceled`
- `description` opsional, max 255
- `cancellation_reason_category_id` wajib UUID jika status `canceled`
- `cancellation_reason_text` wajib jika kategori alasan pembatalan mengizinkan free text

```bash
curl -X PATCH http://127.0.0.1:8000/api/seller/orders/dddddddd-dddd-4ddd-8ddd-dddddddddddd/status \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status_code":"accepted_by_store","description":"Pesanan diterima toko"}'
```

### GET `/api/seller/products`

Mengambil daftar produk milik tenant seller aktif.

```bash
curl http://127.0.0.1:8000/api/seller/products \
  -H "Authorization: Bearer $SELLER_TOKEN"
```

### POST `/api/seller/product-images`

Upload gambar produk. Mengembalikan path/url yang bisa dipakai saat create/update product.

Body multipart:

- `image` wajib, jpg/jpeg/png, max 5120 KB

```bash
curl -X POST http://127.0.0.1:8000/api/seller/product-images \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -F "image=@/path/to/product.jpg"
```

### POST `/api/seller/products`

Membuat produk seller.

Mapping input form Tambah Produk:

| Input UI | Field API | Keterangan |
|---|---|---|
| Tambah Foto Produk | `image` / `image_path` / `image_url` | Salah satu wajib. `image` untuk multipart upload langsung. |
| Nama Produk | `name` | Wajib. |
| Kategori Produk | `category` | Wajib, nama kategori dari master kategori produk. |
| Deskripsi Produk | `description` | Opsional. |
| Harga Produk | `price` | Wajib, integer. |
| Harga Diskon | `original_price` | Opsional. Dipakai sebagai harga coret sebelum diskon. |
| Stok | `stock` | Wajib, integer. |
| Satuan | `product_unit_id` | Wajib, ID dari `GET /api/product-units`. |
| Peringatan stok minimum | `minimum_stock` | Opsional, default `1`. |
| Status Produk | `is_active` | Opsional boolean, default `true`. |

Catatan: client tidak mengirim field `unit`. Field `unit` pada response diisi otomatis dari `product_units.name` berdasarkan `product_unit_id`.

Body:

- `tenant_id` wajib UUID, tenant harus milik seller aktif
- `name` wajib
- `category` wajib, salah satu `Tenant::CATEGORIES`
- salah satu dari `image`, `image_path`, atau `image_url` wajib
- `image`: file jpg/jpeg/png max 5120 KB
- `image_path`: string max 255, harus diawali `products/`
- `image_url`: URL max 255
- `price` wajib, integer minimal 0
- `original_price` opsional, harus lebih besar atau sama dengan `price`
- `stock` wajib, 0 sampai 999999
- `product_unit_id` wajib UUID, harus terdaftar dan aktif di master satuan produk (`GET /api/product-units`)
- `minimum_stock` opsional
- `is_active` opsional boolean
- `weight_label` opsional, max 100
- `description` opsional
- `delivery_estimate` opsional, max 100

```bash
curl -X POST http://127.0.0.1:8000/api/seller/products \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"tenant_id":"aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa","name":"Bayam","category":"Sayur","image_url":"https://example.com/bayam.png","price":7000,"original_price":9000,"stock":100,"product_unit_id":"22222222-2222-4222-8222-222222222222","minimum_stock":5,"is_active":true,"weight_label":"250gr","description":"Sayur segar.","delivery_estimate":"Hari ini"}'
```

Untuk multipart:

```bash
curl -X POST http://127.0.0.1:8000/api/seller/products \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -F "tenant_id=aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa" \
  -F "name=Bayam" \
  -F "category=Sayur" \
  -F "image=@/path/to/bayam.jpg" \
  -F "price=7000" \
  -F "stock=100" \
  -F "product_unit_id=22222222-2222-4222-8222-222222222222"
```

### GET `/api/seller/products/{id}`

Mengambil detail produk seller.

```bash
curl http://127.0.0.1:8000/api/seller/products/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb \
  -H "Authorization: Bearer $SELLER_TOKEN"
```

### PUT `/api/seller/products/{id}`

Mengubah produk seller. Body mirip create product, tetapi gambar opsional.

```bash
curl -X PUT http://127.0.0.1:8000/api/seller/products/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"tenant_id":"aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa","name":"Bayam Super","category":"Sayur","image_url":"https://example.com/bayam-super.png","price":8000,"original_price":10000,"stock":80,"product_unit_id":"22222222-2222-4222-8222-222222222222","minimum_stock":5,"is_active":true}'
```

### PATCH `/api/seller/products/{id}/status`

Mengubah status aktif/nonaktif produk seller tanpa perlu mengirim seluruh data produk.

Body:

- `is_active` wajib boolean

```bash
curl -X PATCH http://127.0.0.1:8000/api/seller/products/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb/status \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"is_active":false}'
```

### POST `/api/seller/products/{id}`

Alias update produk seller, berguna untuk form multipart yang sulit memakai method `PUT`.

```bash
curl -X POST http://127.0.0.1:8000/api/seller/products/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -F "tenant_id=aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa" \
  -F "name=Bayam Super" \
  -F "category=Sayur" \
  -F "image=@/path/to/bayam-super.jpg" \
  -F "price=8000" \
  -F "stock=80" \
  -F "product_unit_id=22222222-2222-4222-8222-222222222222"
```

### DELETE `/api/seller/products/{id}`

Menghapus produk seller.

```bash
curl -X DELETE http://127.0.0.1:8000/api/seller/products/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb \
  -H "Authorization: Bearer $SELLER_TOKEN"
```

## Agent API

Semua endpoint pada bagian ini butuh token dengan role `agent`.

### GET `/api/agent/dashboard`

Mengambil ringkasan dashboard agent, termasuk tenant, transaksi terbaru, komisi, dan withdrawal summary.

```bash
curl http://127.0.0.1:8000/api/agent/dashboard \
  -H "Authorization: Bearer $AGENT_TOKEN"
```

### GET `/api/agent/sellers`

Mengambil daftar seller/tenant yang terkait dengan agent aktif.

```bash
curl http://127.0.0.1:8000/api/agent/sellers \
  -H "Authorization: Bearer $AGENT_TOKEN"
```

### GET `/api/agent/sellers/{sellerId}`

Mengambil detail seller dan performanya untuk agent aktif.

```bash
curl http://127.0.0.1:8000/api/agent/sellers/22222222-2222-4222-8222-222222222222 \
  -H "Authorization: Bearer $AGENT_TOKEN"
```

### GET `/api/agent/profile`

Mengambil profil agent, termasuk data bank dan status kelengkapan payout.

```bash
curl http://127.0.0.1:8000/api/agent/profile \
  -H "Authorization: Bearer $AGENT_TOKEN"
```

### PUT `/api/agent/profile`

Memperbarui profil payout agent.

Body:

- `name` wajib
- `email` wajib, unik per role
- `phone` opsional, unik per role
- `bank_name` wajib
- `bank_account_name` wajib
- `bank_account_number` wajib

```bash
curl -X PUT http://127.0.0.1:8000/api/agent/profile \
  -H "Authorization: Bearer $AGENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Agent Satu","email":"agent@example.com","phone":"+6281200000099","bank_name":"BCA","bank_account_name":"Agent Satu","bank_account_number":"1234567890"}'
```

### GET `/api/agent/commission-withdrawals`

Mengambil daftar withdrawal komisi agent.

```bash
curl http://127.0.0.1:8000/api/agent/commission-withdrawals \
  -H "Authorization: Bearer $AGENT_TOKEN"
```

### POST `/api/agent/commission-withdrawals`

Membuat permintaan pencairan komisi agent.

Body:

- `amount` wajib, integer minimal 1
- `note` opsional, max 1000

Catatan: profil bank agent harus lengkap.

```bash
curl -X POST http://127.0.0.1:8000/api/agent/commission-withdrawals \
  -H "Authorization: Bearer $AGENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount":50000,"note":"Pencairan mingguan"}'
```

## Finance API

Semua endpoint pada bagian ini butuh token dengan role `finance`.

### GET `/api/finance/dashboard`

Mengambil ringkasan finance: total transaksi, nominal transaksi, toko aktif, dan transaksi terbaru.

```bash
curl http://127.0.0.1:8000/api/finance/dashboard \
  -H "Authorization: Bearer $FINANCE_TOKEN"
```

### GET `/api/finance/transactions`

Mengambil daftar disbursement transaksi finance. Endpoint ini juga melakukan sinkronisasi disbursement untuk transaksi yang belum punya record finance disbursement.

Query opsional:

- `status`: `pending_buyer_payment`, `buyer_payment_confirmed`, atau `disbursed_to_seller`

```bash
curl "http://127.0.0.1:8000/api/finance/transactions?status=pending_buyer_payment" \
  -H "Authorization: Bearer $FINANCE_TOKEN"
```

### GET `/api/finance/transactions/{id}`

Mengambil detail transaksi finance.

```bash
curl http://127.0.0.1:8000/api/finance/transactions/dddddddd-dddd-4ddd-8ddd-dddddddddddd \
  -H "Authorization: Bearer $FINANCE_TOKEN"
```

### PATCH `/api/finance/transactions/{id}/confirm-buyer-payment`

Mengonfirmasi pembayaran buyer untuk transaksi.

```bash
curl -X PATCH http://127.0.0.1:8000/api/finance/transactions/dddddddd-dddd-4ddd-8ddd-dddddddddddd/confirm-buyer-payment \
  -H "Authorization: Bearer $FINANCE_TOKEN"
```

### PATCH `/api/finance/disbursements/{id}/disburse-to-seller`

Menandai disbursement sebagai sudah dicairkan ke seller.

```bash
curl -X PATCH http://127.0.0.1:8000/api/finance/disbursements/99999999-9999-4999-8999-999999999999/disburse-to-seller \
  -H "Authorization: Bearer $FINANCE_TOKEN"
```

### GET `/api/finance/cancellation-reason-categories`

Mengambil semua kategori alasan pembatalan untuk finance.

```bash
curl http://127.0.0.1:8000/api/finance/cancellation-reason-categories \
  -H "Authorization: Bearer $FINANCE_TOKEN"
```

### POST `/api/finance/cancellation-reason-categories`

Membuat kategori alasan pembatalan.

Body:

- `name` wajib, unik
- `sort_order` opsional, integer minimal 0
- `allows_free_text` opsional boolean
- `is_active` opsional boolean

```bash
curl -X POST http://127.0.0.1:8000/api/finance/cancellation-reason-categories \
  -H "Authorization: Bearer $FINANCE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Stok kosong","sort_order":1,"allows_free_text":false,"is_active":true}'
```

### PUT `/api/finance/cancellation-reason-categories/{id}`

Mengubah kategori alasan pembatalan.

```bash
curl -X PUT http://127.0.0.1:8000/api/finance/cancellation-reason-categories/77777777-7777-4777-8777-777777777777 \
  -H "Authorization: Bearer $FINANCE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Stok tidak tersedia","sort_order":1,"allows_free_text":false,"is_active":true}'
```

### DELETE `/api/finance/cancellation-reason-categories/{id}`

Menghapus kategori alasan pembatalan.

```bash
curl -X DELETE http://127.0.0.1:8000/api/finance/cancellation-reason-categories/77777777-7777-4777-8777-777777777777 \
  -H "Authorization: Bearer $FINANCE_TOKEN"
```

## Nilai Referensi

### Status transaksi

| Code | Label internal |
| --- | --- |
| `pending_payment` | `menunggu pembayaran` |
| `accepted_by_store` | `diterima toko` |
| `processing` | `sedang diproses` |
| `on_the_way` | `dalam perjalanan` |
| `completed` | `pesanan selesai` |
| `canceled` | `pesanan dibatalkan` |

### Status disbursement finance

| Code |
| --- |
| `pending_buyer_payment` |
| `buyer_payment_confirmed` |
| `disbursed_to_seller` |

### Kategori tenant/produk legacy

Nilai `category` yang divalidasi oleh beberapa endpoint produk/tenant:

- `Sayur`
- `Buah`
- `Daging`
- `Toiletries`
- `Minuman`
- `Obat`
- `Makanan`
- `Frozen Food`
- `Bayi & Anak`
- `Home Care`
- `Alat Tulis`
- `Bumbu Dapur`
- `Personal Care`
- `Peralatan Rumah`
- `Sembako`

### Payment dan delivery code

Kode `payment_method_code`, `payment_method_option_code`, `delivery_method_code`, dan `pickup_time_option` berasal dari database seed/table:

- `payment_methods.code`
- `payment_method_options.code`
- `delivery_methods.code`
- `order_time_options.code`

Gunakan endpoint list terkait untuk mengambil nilai aktif sebelum checkout.

## Checklist Integrasi Cepat

1. Jalankan app lokal: `php artisan serve`.
2. Register buyer: `POST /api/users/buyer/register`.
3. Ambil OTP dari channel notifikasi/log sesuai environment.
4. Verify OTP: `POST /api/users/verify-otp`.
5. Simpan token ke environment shell:

```bash
export BUYER_TOKEN="token-dari-response"
```

6. Ambil master data: delivery methods, payment methods, order time options, product categories.
7. Tambahkan item ke cart.
8. Checkout.

## Contoh Response Per Endpoint

Contoh response di bawah memakai data dummy. Field tambahan bisa muncul tergantung relasi database dan perubahan controller.

### Healthcheck

#### GET `/api/vershealthcheck`

```json
{
  "status": "ok",
  "message": "API sehat",
  "version": "v1",
  "framework": {
    "name": "Laravel",
    "version": "13.x"
  },
  "timestamp": "2026-05-27T10:00:00+07:00"
}
```

### Auth dan Session

#### POST `/api/users/{role}/register`

Berlaku juga untuk `POST /api/agent/register` dan `POST /api/finance/register`.

```json
{
  "message": "Registrasi berhasil. Kode OTP telah dikirim.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "email": "buyer@example.com",
    "phone": null,
    "type": "email",
    "role": "buyer"
  }
}
```

#### POST `/api/agent/register`

```json
{
  "message": "Registrasi berhasil. Kode OTP telah dikirim.",
  "data": {
    "id": "33333333-3333-4333-8333-333333333333",
    "email": "agent@example.com",
    "phone": null,
    "type": "email",
    "role": "agent"
  }
}
```

#### POST `/api/finance/register`

```json
{
  "message": "Registrasi berhasil. Kode OTP telah dikirim.",
  "data": {
    "id": "44444444-4444-4444-8444-444444444444",
    "email": "finance@example.com",
    "phone": null,
    "type": "email",
    "role": "finance"
  }
}
```

#### POST `/api/users/{role}/login`

Berlaku juga untuk `POST /api/agent/login` dan `POST /api/finance/login`.

```json
{
  "message": "Kode OTP login telah dikirim.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "email": "buyer@example.com",
    "phone": null,
    "type": "email",
    "role": "buyer"
  }
}
```

#### POST `/api/agent/login`

```json
{
  "message": "Kode OTP login telah dikirim.",
  "data": {
    "id": "33333333-3333-4333-8333-333333333333",
    "email": "agent@example.com",
    "phone": null,
    "type": "email",
    "role": "agent"
  }
}
```

#### POST `/api/finance/login`

```json
{
  "message": "Kode OTP login telah dikirim.",
  "data": {
    "id": "44444444-4444-4444-8444-444444444444",
    "email": "finance@example.com",
    "phone": null,
    "type": "email",
    "role": "finance"
  }
}
```

#### POST `/api/users/{role}/resend-otp`

Berlaku juga untuk `POST /api/agent/resend-otp` dan `POST /api/finance/resend-otp`.

```json
{
  "message": "Kode OTP berhasil dikirim ulang.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "email": "buyer@example.com",
    "phone": null,
    "type": "email",
    "role": "buyer"
  }
}
```

#### POST `/api/agent/resend-otp`

```json
{
  "message": "Kode OTP berhasil dikirim ulang.",
  "data": {
    "id": "33333333-3333-4333-8333-333333333333",
    "email": "agent@example.com",
    "phone": null,
    "type": "email",
    "role": "agent"
  }
}
```

#### POST `/api/finance/resend-otp`

```json
{
  "message": "Kode OTP berhasil dikirim ulang.",
  "data": {
    "id": "44444444-4444-4444-8444-444444444444",
    "email": "finance@example.com",
    "phone": null,
    "type": "email",
    "role": "finance"
  }
}
```

#### POST `/api/users/verify-otp`

```json
{
  "message": "OTP berhasil diverifikasi.",
  "data": {
    "token": "plain-text-session-token",
    "token_type": "Bearer",
    "user": {
      "id": "11111111-1111-4111-8111-111111111111",
      "name": null,
      "email": "buyer@example.com",
      "phone": null,
      "type": "email",
      "role": "buyer"
    }
  }
}
```

#### POST `/api/users/logout`

```json
{
  "message": "Logout berhasil."
}
```

#### POST `/api/users/refresh-session`

```json
{
  "message": "Session berhasil diperbarui.",
  "data": {
    "token": "new-plain-text-session-token",
    "token_type": "Bearer"
  }
}
```

#### POST `/api/users/devices`

```json
{
  "message": "Perangkat berhasil didaftarkan.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "user_id": "11111111-1111-4111-8111-111111111111",
    "device_token": "firebase-token",
    "platform": "android",
    "device_name": "Pixel 8"
  }
}
```

### Profile dan Master Data Umum

#### GET `/api/users/profile`

```json
{
  "message": "Profil user berhasil diambil.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "name": "Budi",
    "email": "budi@example.com",
    "phone": "+6281234567890",
    "role": "buyer",
    "housing_area_id": "eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee",
    "address": "Jl. Melati No. 1",
    "landmark": "Dekat masjid"
  }
}
```

#### PUT `/api/users/profile`

```json
{
  "message": "Profil user berhasil diperbarui.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "name": "Budi",
    "email": "budi@example.com",
    "phone": "+6281234567890",
    "housing_area_id": "eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee",
    "address": "Jl. Melati No. 1",
    "landmark": "Dekat masjid"
  }
}
```

#### GET `/api/housing-areas`

```json
{
  "message": "Daftar area perumahan berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "name": "Komp Setra Dago",
      "code": "AREA-001",
      "city": "Kota Bandung",
      "district": "Antapani",
      "subdistrict": "Antapani Wetan",
      "village_code": "3273141003"
    }
  ]
}
```

#### GET `/api/product-categories`

```json
{
  "message": "Daftar kategori produk berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "name": "Sembako",
      "slug": "sembako",
      "image_url": "http://127.0.0.1:8000/images/ic_groceries_category.svg"
    }
  ]
}
```

#### GET `/api/product-units`

```json
{
  "message": "Daftar satuan produk berhasil diambil.",
  "data": [
    {
      "id": "22222222-2222-4222-8222-222222222222",
      "name": "ikat",
      "slug": "ikat"
    },
    {
      "id": "33333333-3333-4333-8333-333333333333",
      "name": "kilogram",
      "slug": "kilogram"
    }
  ]
}
```

### Data Wilayah Indonesia

#### GET `/api/indonesia/provinces`

```json
{
  "message": "Daftar provinsi berhasil diambil.",
  "data": [
    {
      "code": "32",
      "name": "JAWA BARAT"
    }
  ]
}
```

#### GET `/api/indonesia/regencies`

```json
{
  "message": "Daftar kabupaten/kota berhasil diambil.",
  "data": [
    {
      "code": "3273",
      "name": "KOTA BANDUNG",
      "province_code": "32"
    }
  ]
}
```

#### GET `/api/indonesia/districts`

```json
{
  "message": "Daftar kecamatan berhasil diambil.",
  "data": [
    {
      "code": "3273141",
      "name": "ANTAPANI",
      "regency_code": "3273"
    }
  ]
}
```

#### GET `/api/indonesia/villages`

```json
{
  "message": "Daftar desa/kelurahan berhasil diambil.",
  "data": [
    {
      "code": "3273141003",
      "name": "ANTAPANI WETAN",
      "district_code": "3273141"
    }
  ]
}
```

### Buyer API

#### GET `/api/delivery-methods`

```json
{
  "message": "Daftar metode pengiriman berhasil diambil.",
  "data": [
    {
      "code": "store_courier",
      "name": "Antar Kurir Toko",
      "description": "Diantar hari ini",
      "fee": 2500,
      "fee_label": "Rp 2.500",
      "requires_order_time": false
    }
  ]
}
```

#### GET `/api/order-time-options`

```json
{
  "message": "Daftar opsi waktu pesanan berhasil diambil.",
  "data": [
    {
      "code": "sekarang",
      "name": "Sekarang",
      "description": "estimasi 15-30 menit",
      "requires_schedule": false
    }
  ]
}
```

#### GET `/api/payment-methods`

```json
{
  "message": "Daftar metode pembayaran berhasil diambil.",
  "data": [
    {
      "code": "bank_transfer",
      "name": "Transfer Bank",
      "icon_key": "bank_transfer",
      "requires_option": true,
      "options": [
        {
          "code": "bca",
          "name": "BCA",
          "icon_key": "bank_bca"
        }
      ]
    }
  ]
}
```

#### GET `/api/products`

```json
{
  "message": "Daftar barang berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
      "tenant_name": "Toko Asep",
      "name": "Bayam",
      "category": "Sayur",
      "category_slug": "sayur",
      "image_url": "http://127.0.0.1:8000/storage/products/bayam.jpg",
      "price": 7000,
      "price_label": "Rp 7.000",
      "original_price": 9000,
      "discount_percentage": 22,
      "stock": 100,
      "unit": "ikat"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "last_page": 1,
    "total": 1,
    "from": 1,
    "to": 1
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/products?page=1",
    "last": "http://127.0.0.1:8000/api/products?page=1",
    "prev": null,
    "next": null
  }
}
```

#### GET `/api/products/{id}`

```json
{
  "message": "Detail barang berhasil diambil.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
    "tenant_name": "Toko Asep",
    "name": "Bayam",
    "category": "Sayur",
    "image_url": "http://127.0.0.1:8000/storage/products/bayam.jpg",
    "price": 7000,
    "price_label": "Rp 7.000",
    "stock": 100,
    "unit": "ikat",
    "description": "Sayur segar.",
    "delivery_estimate": "Hari ini"
  }
}
```

#### GET `/api/tenants`

```json
{
  "message": "Daftar tenant berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "name": "Toko Asep",
      "profile_picture_url": "https://example.com/toko.png",
      "rating": 4.8,
      "category": "Sembako",
      "product_count": 20,
      "is_open": true,
      "store_status": "Buka",
      "operating_hours_label": "Buka 07:00 sd 21:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "last_page": 1,
    "total": 1,
    "from": 1,
    "to": 1
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/tenants?page=1",
    "last": "http://127.0.0.1:8000/api/tenants?page=1",
    "prev": null,
    "next": null
  }
}
```

#### GET `/api/tenants/categories`

```json
{
  "message": "Daftar kategori tenant berhasil diambil.",
  "data": [
    {
      "name": "Sayur",
      "slug": "sayur",
      "icon_key": "vegetables",
      "background_color": "#E7F6EB",
      "icon_color": "#67B97A"
    }
  ]
}
```

#### GET `/api/cart`

```json
{
  "message": "Keranjang berhasil diambil.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "delivery_method_code": "store_courier",
    "delivery_method": {
      "code": "store_courier",
      "name": "Antar Kurir Toko",
      "fee": 2500
    },
    "items": [
      {
        "id": "11111111-1111-4111-8111-111111111111",
        "product_id": "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
        "name": "Bayam",
        "quantity": 2,
        "unit_price": 7000,
        "line_total": 14000
      }
    ],
    "subtotal_amount": 14000,
    "delivery_fee": 2500,
    "total_amount": 16500
  }
}
```

#### PATCH `/api/cart/delivery-method`

```json
{
  "message": "Metode pengiriman keranjang berhasil diperbarui.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "delivery_method_code": "store_courier"
  }
}
```

#### POST `/api/cart/items`

```json
{
  "message": "Barang berhasil ditambahkan ke keranjang.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "product_id": "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
    "quantity": 2,
    "product": {
      "id": "11111111-1111-4111-8111-111111111111",
      "name": "Bayam",
      "price": 7000,
      "stock": 100
    }
  }
}
```

#### PATCH `/api/cart/items/{id}`

```json
{
  "message": "Jumlah barang keranjang berhasil diperbarui.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "product_id": "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
    "quantity": 3
  }
}
```

#### DELETE `/api/cart/items/{id}`

```json
{
  "message": "Barang berhasil dihapus dari keranjang."
}
```

#### POST `/api/promo-codes/validate`

```json
{
  "message": "Promo berhasil divalidasi.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "code": "HEMAT10",
    "name": "Hemat 10%",
    "description": "Diskon 10% untuk pesanan minimal Rp 10.000.",
    "discount_type": "percentage",
    "discount_value": 10,
    "minimum_order_amount": 10000,
    "maximum_discount_amount": 10000,
    "remaining_quantity": 3
  }
}
```

#### POST `/api/checkout`

```json
{
  "message": "Checkout berhasil.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "order_number": "INV-20260527-0001",
    "status": "menunggu pembayaran",
    "status_code": "pending_payment",
    "subtotal_amount": 19998,
    "delivery_fee": 2500,
    "discount_amount": 1999,
    "total_amount": 20499,
    "delivery_method": "Antar Kurir Toko",
    "payment_method": "Transfer Bank",
    "payment_method_option_name": "BCA",
    "promo_code": "HEMAT10",
    "items": [
      {
        "product_id": "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
        "product_name": "Bayam",
        "quantity": 2,
        "unit_price": 9999,
        "line_total": 19998
      }
    ]
  }
}
```

#### GET `/api/users/transactions`

```json
{
  "message": "Riwayat transaksi berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "order_number": "INV-20260527-0001",
      "status": "menunggu pembayaran",
      "status_code": "pending_payment",
      "total_amount": 20499,
      "total_amount_label": "Rp 20.499",
      "transaction_at": "2026-05-27T10:00:00+07:00"
    }
  ]
}
```

#### GET `/api/users/transactions/{transactionId}`

```json
{
  "message": "Detail transaksi berhasil diambil.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "order_number": "INV-20260527-0001",
    "status": "menunggu pembayaran",
    "status_code": "pending_payment",
    "subtotal_amount": 19998,
    "delivery_fee": 2500,
    "discount_amount": 1999,
    "total_amount": 20499,
    "items": [
      {
        "id": "11111111-1111-4111-8111-111111111111",
        "product_id": "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
        "product_name": "Bayam",
        "quantity": 2,
        "line_total": 19998
      }
    ],
    "status_histories": [
      {
        "status": "menunggu pembayaran",
        "description": "Transaksi dibuat.",
        "sequence": 1
      }
    ]
  }
}
```

#### GET `/api/cancellation-reason-categories`

```json
{
  "message": "Daftar kategori alasan pembatalan berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "name": "Stok kosong",
      "allows_free_text": false,
      "sort_order": 1
    }
  ]
}
```

### Seller API

#### GET `/api/seller/dashboard`

```json
{
  "message": "Dashboard seller berhasil diambil.",
  "data": {
    "profile": {
      "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
      "tenant_name": "Toko Asep"
    },
    "revenue_today": 150000,
    "transactions_today": 6,
    "orders_today_counts": {
      "pending_payment": 1,
      "processing": 2,
      "completed": 3
    }
  }
}
```

#### GET `/api/seller/dashboard/profile`

```json
{
  "message": "Profil dashboard seller berhasil diambil.",
  "data": {
    "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
    "tenant_name": "Toko Asep",
    "category": "Sembako",
    "is_open": true,
    "operating_hours_label": "Buka 07:00 sd 21:00"
  }
}
```

#### GET `/api/seller/dashboard/revenue-today`

```json
{
  "message": "Revenue hari ini berhasil diambil.",
  "data": {
    "amount": 150000,
    "amount_label": "Rp. 150.000"
  }
}
```

#### GET `/api/seller/dashboard/revenue-change`

```json
{
  "message": "Perubahan revenue berhasil diambil.",
  "data": {
    "current_amount": 150000,
    "previous_amount": 100000,
    "change_percentage": 50,
    "trend": "up"
  }
}
```

#### GET `/api/seller/dashboard/transactions-today`

```json
{
  "message": "Transaksi hari ini berhasil diambil.",
  "data": {
    "count": 6
  }
}
```

#### GET `/api/seller/dashboard/orders-today/counts`

```json
{
  "message": "Jumlah order hari ini berhasil diambil.",
  "data": {
    "pending_payment": 1,
    "accepted_by_store": 1,
    "processing": 2,
    "on_the_way": 0,
    "completed": 3,
    "canceled": 0
  }
}
```

#### GET `/api/seller/dashboard/orders/new-preview`

```json
{
  "message": "Preview order baru berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "order_number": "INV-20260527-0001",
      "buyer_name": "Budi",
      "total_amount": 20499,
      "status_code": "pending_payment"
    }
  ]
}
```

#### GET `/api/seller/dashboard/top-products-today`

```json
{
  "message": "Produk teratas hari ini berhasil diambil.",
  "data": [
    {
      "product_id": "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
      "product_name": "Bayam",
      "quantity_sold": 12,
      "revenue": 84000,
      "revenue_label": "Rp. 84.000"
    }
  ]
}
```

#### GET `/api/seller/tenants`

```json
{
  "message": "Daftar tenant seller berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "owner_user_id": "22222222-2222-4222-8222-222222222222",
      "agent_user_id": "33333333-3333-4333-8333-333333333333",
      "agent_code": "KA-20265",
      "name": "Toko Asep",
      "category_id": "ffffffff-ffff-4fff-8fff-ffffffffffff",
      "category": "Sembako",
      "location": "Jl Asri Raya No 45",
      "housing_areas": [
        {
          "id": "11111111-1111-4111-8111-111111111111",
          "name": "Komp Setra Dago"
        }
      ],
      "is_open": true
    }
  ]
}
```

#### POST `/api/seller/tenants`

```json
{
  "message": "Tenant berhasil dibuat.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "owner_user_id": "22222222-2222-4222-8222-222222222222",
    "agent_user_id": "33333333-3333-4333-8333-333333333333",
    "agent_code": "KA-20265",
    "owner": {
      "id": "22222222-2222-4222-8222-222222222222",
      "name": "Asep Pemilik",
      "phone": "+6281234567890",
      "email": "asep@example.com"
    },
    "name": "Toko Asep",
    "category_id": "ffffffff-ffff-4fff-8fff-ffffffffffff",
    "category": "Sembako",
    "location": "Jl Asri Raya No 45",
    "housing_areas": [
      {
        "id": "11111111-1111-4111-8111-111111111111",
        "name": "Komp Setra Dago"
      }
    ]
  }
}
```

#### GET `/api/seller/orders`

```json
{
  "message": "Daftar order seller berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "order_number": "INV-20260527-0001",
      "buyer": {
        "id": "11111111-1111-4111-8111-111111111111",
        "name": "Budi",
        "email": "budi@example.com",
        "phone": "+6281234567890"
      },
      "store_name": "Toko Asep",
      "total_items": 2,
      "seller_subtotal_amount": 19998,
      "status": "menunggu pembayaran",
      "status_code": "pending_payment"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "last_page": 1,
    "total": 1,
    "from": 1,
    "to": 1
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/seller/orders?page=1",
    "last": "http://127.0.0.1:8000/api/seller/orders?page=1",
    "prev": null,
    "next": null
  }
}
```

#### GET `/api/seller/orders/{id}`

```json
{
  "message": "Detail order seller berhasil diambil.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "order_number": "INV-20260527-0001",
    "buyer": {
      "id": "11111111-1111-4111-8111-111111111111",
      "name": "Budi"
    },
    "status": "menunggu pembayaran",
    "status_code": "pending_payment",
    "items": [
      {
        "product_id": "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
        "product_name": "Bayam",
        "quantity": 2,
        "line_total": 19998
      }
    ]
  }
}
```

#### PATCH `/api/seller/orders/{id}/status`

```json
{
  "message": "Status order berhasil diperbarui.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "order_number": "INV-20260527-0001",
    "status": "diterima toko",
    "status_code": "accepted_by_store"
  }
}
```

#### GET `/api/seller/products`

```json
{
  "message": "Daftar produk seller berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
      "name": "Bayam",
      "category": "Sayur",
      "image_url": "https://example.com/bayam.png",
      "price": 7000,
      "stock": 100,
      "unit": "ikat",
      "product_unit_id": "22222222-2222-4222-8222-222222222222",
      "product_unit": {
        "id": "22222222-2222-4222-8222-222222222222",
        "name": "ikat",
        "slug": "ikat"
      },
      "is_active": true
    }
  ]
}
```

#### POST `/api/seller/product-images`

```json
{
  "message": "Gambar produk berhasil diupload.",
  "data": {
    "image_path": "products/bayam.jpg",
    "image_url": "http://127.0.0.1:8000/storage/products/bayam.jpg"
  }
}
```

#### POST `/api/seller/products`

```json
{
  "message": "Produk seller berhasil dibuat.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
    "name": "Bayam",
    "category": "Sayur",
    "price": 7000,
    "original_price": 9000,
    "stock": 100,
    "unit": "ikat",
    "product_unit_id": "22222222-2222-4222-8222-222222222222",
    "product_unit": {
      "id": "22222222-2222-4222-8222-222222222222",
      "name": "ikat",
      "slug": "ikat"
    },
    "minimum_stock": 5,
    "is_active": true,
    "image_url": "https://example.com/bayam.png"
  }
}
```

#### GET `/api/seller/products/{id}`

```json
{
  "message": "Detail produk seller berhasil diambil.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
    "name": "Bayam",
    "category": "Sayur",
    "price": 7000,
    "stock": 100,
    "unit": "ikat",
    "product_unit_id": "22222222-2222-4222-8222-222222222222",
    "product_unit": {
      "id": "22222222-2222-4222-8222-222222222222",
      "name": "ikat",
      "slug": "ikat"
    },
    "description": "Sayur segar.",
    "is_active": true
  }
}
```

#### PUT `/api/seller/products/{id}`

Berlaku juga untuk `POST /api/seller/products/{id}`.

```json
{
  "message": "Produk seller berhasil diperbarui.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
    "name": "Bayam Super",
    "category": "Sayur",
    "price": 8000,
    "stock": 80,
    "unit": "ikat",
    "product_unit_id": "22222222-2222-4222-8222-222222222222",
    "product_unit": {
      "id": "22222222-2222-4222-8222-222222222222",
      "name": "ikat",
      "slug": "ikat"
    },
    "is_active": true
  }
}
```

#### PATCH `/api/seller/products/{id}/status`

```json
{
  "message": "Status produk seller berhasil diperbarui.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "is_active": false,
    "status_label": "Nonaktif"
  }
}
```

#### POST `/api/seller/products/{id}`

```json
{
  "message": "Produk seller berhasil diperbarui.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
    "name": "Bayam Super",
    "category": "Sayur",
    "price": 8000,
    "stock": 80,
    "unit": "ikat",
    "product_unit_id": "22222222-2222-4222-8222-222222222222",
    "product_unit": {
      "id": "22222222-2222-4222-8222-222222222222",
      "name": "ikat",
      "slug": "ikat"
    },
    "is_active": true
  }
}
```

#### DELETE `/api/seller/products/{id}`

```json
{
  "message": "Produk berhasil dihapus."
}
```

### Agent API

#### GET `/api/agent/dashboard`

```json
{
  "message": "Dashboard agent berhasil diambil.",
  "data": {
    "summary": {
      "tenant_count": 3,
      "seller_count": 3,
      "completed_revenue": 1000000,
      "commission_amount": 50000
    },
    "recent_transactions": [
      {
        "id": "11111111-1111-4111-8111-111111111111",
        "order_number": "INV-20260527-0001",
        "total_amount": 20499
      }
    ]
  }
}
```

#### GET `/api/agent/sellers`

```json
{
  "message": "Daftar seller agent berhasil diambil.",
  "data": [
    {
      "id": "22222222-2222-4222-8222-222222222222",
      "name": "Asep Pemilik",
      "email": "asep@example.com",
      "phone": "+6281234567890",
      "tenant_count": 1,
      "completed_revenue": 1000000,
      "commission_amount": 50000
    }
  ]
}
```

#### GET `/api/agent/sellers/{sellerId}`

```json
{
  "message": "Detail seller agent berhasil diambil.",
  "data": {
    "id": "22222222-2222-4222-8222-222222222222",
    "name": "Asep Pemilik",
    "email": "asep@example.com",
    "phone": "+6281234567890",
    "tenants": [
      {
        "id": "11111111-1111-4111-8111-111111111111",
        "name": "Toko Asep",
        "completed_revenue": 1000000,
        "commission_amount": 50000
      }
    ]
  }
}
```

#### GET `/api/agent/profile`

```json
{
  "message": "Profil agent berhasil diambil.",
  "data": {
    "id": "33333333-3333-4333-8333-333333333333",
    "name": "Agent Satu",
    "email": "agent@example.com",
    "phone": "+6281200000099",
    "role": "agent",
    "agent_code": "KA-20265",
    "bank_name": "BCA",
    "bank_account_name": "Agent Satu",
    "bank_account_number": "1234567890",
    "payout_profile_completed": true
  }
}
```

#### PUT `/api/agent/profile`

```json
{
  "message": "Profil pencairan agent berhasil diperbarui.",
  "data": {
    "id": "33333333-3333-4333-8333-333333333333",
    "name": "Agent Satu",
    "email": "agent@example.com",
    "phone": "+6281200000099",
    "role": "agent",
    "bank_name": "BCA",
    "bank_account_name": "Agent Satu",
    "bank_account_number": "1234567890"
  }
}
```

#### GET `/api/agent/commission-withdrawals`

```json
{
  "message": "Daftar pencairan komisi agent berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "amount": 50000,
      "amount_label": "Rp. 50.000",
      "status": "pending",
      "note": "Pencairan mingguan",
      "requested_at": "2026-05-27T10:00:00+07:00"
    }
  ]
}
```

#### POST `/api/agent/commission-withdrawals`

```json
{
  "message": "Permintaan pencairan komisi berhasil dibuat.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "agent_user_id": "33333333-3333-4333-8333-333333333333",
    "amount": 50000,
    "status": "pending",
    "note": "Pencairan mingguan"
  }
}
```

### Finance API

#### GET `/api/finance/dashboard`

```json
{
  "message": "Dashboard finance berhasil diambil.",
  "data": {
    "summary": {
      "total_transactions": 12,
      "total_transaction_amount": 2500000,
      "total_transaction_amount_label": "Rp. 2.500.000",
      "active_store_count": 8,
      "all_store_count": 10
    },
    "recent_transactions": [
      {
        "id": "11111111-1111-4111-8111-111111111111",
        "order_number": "INV-20260527-0001",
        "total_amount": 20499
      }
    ]
  }
}
```

#### GET `/api/finance/transactions`

```json
{
  "message": "Daftar transaksi finance berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "unique_code": "DISB-0001",
      "status": "pending_buyer_payment",
      "amount": 19998,
      "amount_label": "Rp. 19.998",
      "store": {
        "id": "11111111-1111-4111-8111-111111111111",
        "name": "Toko Asep"
      },
      "seller": {
        "id": "22222222-2222-4222-8222-222222222222",
        "name": "Asep Pemilik"
      },
      "transaction": {
        "id": "11111111-1111-4111-8111-111111111111",
        "order_number": "INV-20260527-0001",
        "status_code": "pending_payment",
        "total_amount": 20499
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "last_page": 1,
    "total": 1,
    "from": 1,
    "to": 1
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/finance/transactions?page=1",
    "last": "http://127.0.0.1:8000/api/finance/transactions?page=1",
    "prev": null,
    "next": null
  }
}
```

#### GET `/api/finance/transactions/{id}`

```json
{
  "message": "Detail transaksi finance berhasil diambil.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "unique_code": "DISB-0001",
    "status": "pending_buyer_payment",
    "amount": 19998,
    "store": {
      "id": "11111111-1111-4111-8111-111111111111",
      "name": "Toko Asep"
    },
    "seller": {
      "id": "22222222-2222-4222-8222-222222222222",
      "name": "Asep Pemilik",
      "bank_name": "BCA",
      "bank_account_name": "Asep Pemilik",
      "bank_account_number": "1234567890"
    },
    "transaction": {
      "id": "11111111-1111-4111-8111-111111111111",
      "order_number": "INV-20260527-0001",
      "total_amount": 20499,
      "items": [
        {
          "product_name": "Bayam",
          "quantity": 2,
          "line_total": 19998
        }
      ]
    }
  }
}
```

#### PATCH `/api/finance/transactions/{id}/confirm-buyer-payment`

```json
{
  "message": "Pembayaran buyer berhasil dikonfirmasi.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "transaction_id": "dddddddd-dddd-4ddd-8ddd-dddddddddddd",
    "status": "buyer_payment_confirmed",
    "buyer_payment_confirmed_at": "2026-05-27T10:00:00+07:00"
  }
}
```

#### PATCH `/api/finance/disbursements/{id}/disburse-to-seller`

```json
{
  "message": "Dana berhasil ditandai sudah dicairkan ke seller.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "transaction_id": "dddddddd-dddd-4ddd-8ddd-dddddddddddd",
    "status": "disbursed_to_seller",
    "disbursed_at": "2026-05-27T10:05:00+07:00"
  }
}
```

#### GET `/api/finance/cancellation-reason-categories`

```json
{
  "message": "Daftar kategori alasan pembatalan berhasil diambil.",
  "data": [
    {
      "id": "11111111-1111-4111-8111-111111111111",
      "name": "Stok kosong",
      "sort_order": 1,
      "allows_free_text": false,
      "is_active": true
    }
  ]
}
```

#### POST `/api/finance/cancellation-reason-categories`

```json
{
  "message": "Kategori alasan pembatalan berhasil dibuat.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "name": "Stok kosong",
    "sort_order": 1,
    "allows_free_text": false,
    "is_active": true
  }
}
```

#### PUT `/api/finance/cancellation-reason-categories/{id}`

```json
{
  "message": "Kategori alasan pembatalan berhasil diperbarui.",
  "data": {
    "id": "11111111-1111-4111-8111-111111111111",
    "name": "Stok tidak tersedia",
    "sort_order": 1,
    "allows_free_text": false,
    "is_active": true
  }
}
```

#### DELETE `/api/finance/cancellation-reason-categories/{id}`

```json
{
  "message": "Kategori alasan pembatalan berhasil dihapus."
}
```
