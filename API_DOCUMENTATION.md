# API Documentation

Dokumentasi ini dibuat dari route, controller, request validation, model, dan feature test pada codebase Laravel ini.

## Ringkasan

- Base URL lokal default: `http://127.0.0.1:8000`
- Prefix API: `/api`
- Format body: `application/json`, kecuali upload gambar memakai `multipart/form-data`
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
- `housing_area_id` wajib, harus ada di `housing_areas`
- `address` wajib, max 1000
- `landmark` opsional, max 255

```bash
curl -X PUT http://127.0.0.1:8000/api/users/profile \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Budi","email":"budi@example.com","phone":"+6281234567890","housing_area_id":1,"address":"Jl. Melati No. 1","landmark":"Dekat masjid"}'
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
- `tenant_id`: ID tenant
- `name`: pencarian nama produk
- `is_promo`: `true`, `false`, `1`, atau `0`

```bash
curl "http://127.0.0.1:8000/api/products?limit=10&category=sembako&is_promo=true" \
  -H "Authorization: Bearer $BUYER_TOKEN"
```

### GET `/api/products/{id}`

Mengambil detail produk aktif.

```bash
curl http://127.0.0.1:8000/api/products/1 \
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

- `product_id` wajib, harus ada di `products`
- `quantity` wajib, integer 1 sampai 999

```bash
curl -X POST http://127.0.0.1:8000/api/cart/items \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":2}'
```

### PATCH `/api/cart/items/{id}`

Mengubah quantity item cart milik user aktif.

Body:

- `quantity` wajib, integer 1 sampai 999

```bash
curl -X PATCH http://127.0.0.1:8000/api/cart/items/1 \
  -H "Authorization: Bearer $BUYER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"quantity":3}'
```

### DELETE `/api/cart/items/{id}`

Menghapus item cart milik user aktif.

```bash
curl -X DELETE http://127.0.0.1:8000/api/cart/items/1 \
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
curl http://127.0.0.1:8000/api/users/transactions/1 \
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
- `category_id` wajib, harus ada di `product_categories`
- `category` opsional, salah satu `Tenant::CATEGORIES`
- `location` wajib
- `housing_area_ids` wajib array, minimal 1 maksimal 3
- `rating` opsional, 0 sampai 5
- `latitude` opsional, -90 sampai 90
- `longitude` opsional, -180 sampai 180
- `open_time` dan `close_time` opsional, format `H:i`

```bash
curl -X POST http://127.0.0.1:8000/api/seller/tenants \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"owner_name":"Asep Pemilik","owner_phone":"081234567890","owner_email":"asep@example.com","agent_code":"KA-20265","name":"Toko Asep","category_id":1,"location":"Jl Asri Raya No 45","housing_area_ids":[1],"latitude":-6.2,"longitude":106.8,"open_time":"07:00","close_time":"21:00"}'
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
curl http://127.0.0.1:8000/api/seller/orders/1 \
  -H "Authorization: Bearer $SELLER_TOKEN"
```

### PATCH `/api/seller/orders/{id}/status`

Mengubah status order seller.

Body:

- `status_code` wajib: `accepted_by_store`, `processing`, `on_the_way`, `completed`, atau `canceled`
- `description` opsional, max 255
- `cancellation_reason_category_id` wajib jika status `canceled`
- `cancellation_reason_text` wajib jika kategori alasan pembatalan mengizinkan free text

```bash
curl -X PATCH http://127.0.0.1:8000/api/seller/orders/1/status \
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

Body:

- `tenant_id` wajib, tenant harus milik seller aktif
- `name` wajib
- `category` wajib, salah satu `Tenant::CATEGORIES`
- salah satu dari `image`, `image_path`, atau `image_url` wajib
- `image`: file jpg/jpeg/png max 5120 KB
- `image_path`: string max 255, harus diawali `products/`
- `image_url`: URL max 255
- `price` wajib, integer minimal 0
- `original_price` opsional, harus lebih besar atau sama dengan `price`
- `stock` wajib, 0 sampai 999999
- `unit` wajib, max 50
- `minimum_stock` opsional
- `is_active` opsional boolean
- `weight_label` opsional, max 100
- `description` opsional
- `delivery_estimate` opsional, max 100

```bash
curl -X POST http://127.0.0.1:8000/api/seller/products \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"tenant_id":1,"name":"Bayam","category":"Sayur","image_url":"https://example.com/bayam.png","price":7000,"original_price":9000,"stock":100,"unit":"ikat","minimum_stock":5,"is_active":true,"weight_label":"250gr","description":"Sayur segar.","delivery_estimate":"Hari ini"}'
```

Untuk multipart:

```bash
curl -X POST http://127.0.0.1:8000/api/seller/products \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -F "tenant_id=1" \
  -F "name=Bayam" \
  -F "category=Sayur" \
  -F "image=@/path/to/bayam.jpg" \
  -F "price=7000" \
  -F "stock=100" \
  -F "unit=ikat"
```

### GET `/api/seller/products/{id}`

Mengambil detail produk seller.

```bash
curl http://127.0.0.1:8000/api/seller/products/1 \
  -H "Authorization: Bearer $SELLER_TOKEN"
```

### PUT `/api/seller/products/{id}`

Mengubah produk seller. Body mirip create product, tetapi gambar opsional.

```bash
curl -X PUT http://127.0.0.1:8000/api/seller/products/1 \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"tenant_id":1,"name":"Bayam Super","category":"Sayur","image_url":"https://example.com/bayam-super.png","price":8000,"original_price":10000,"stock":80,"unit":"ikat","minimum_stock":5,"is_active":true}'
```

### POST `/api/seller/products/{id}`

Alias update produk seller, berguna untuk form multipart yang sulit memakai method `PUT`.

```bash
curl -X POST http://127.0.0.1:8000/api/seller/products/1 \
  -H "Authorization: Bearer $SELLER_TOKEN" \
  -F "tenant_id=1" \
  -F "name=Bayam Super" \
  -F "category=Sayur" \
  -F "image=@/path/to/bayam-super.jpg" \
  -F "price=8000" \
  -F "stock=80" \
  -F "unit=ikat"
```

### DELETE `/api/seller/products/{id}`

Menghapus produk seller.

```bash
curl -X DELETE http://127.0.0.1:8000/api/seller/products/1 \
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
curl http://127.0.0.1:8000/api/agent/sellers/1 \
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
curl http://127.0.0.1:8000/api/finance/transactions/1 \
  -H "Authorization: Bearer $FINANCE_TOKEN"
```

### PATCH `/api/finance/transactions/{id}/confirm-buyer-payment`

Mengonfirmasi pembayaran buyer untuk transaksi.

```bash
curl -X PATCH http://127.0.0.1:8000/api/finance/transactions/1/confirm-buyer-payment \
  -H "Authorization: Bearer $FINANCE_TOKEN"
```

### PATCH `/api/finance/disbursements/{id}/disburse-to-seller`

Menandai disbursement sebagai sudah dicairkan ke seller.

```bash
curl -X PATCH http://127.0.0.1:8000/api/finance/disbursements/1/disburse-to-seller \
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
curl -X PUT http://127.0.0.1:8000/api/finance/cancellation-reason-categories/1 \
  -H "Authorization: Bearer $FINANCE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Stok tidak tersedia","sort_order":1,"allows_free_text":false,"is_active":true}'
```

### DELETE `/api/finance/cancellation-reason-categories/{id}`

Menghapus kategori alasan pembatalan.

```bash
curl -X DELETE http://127.0.0.1:8000/api/finance/cancellation-reason-categories/1 \
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
