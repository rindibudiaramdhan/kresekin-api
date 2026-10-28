# Dokumentasi Endpoint Fitur Atur Ongkir

Tanggal: 27 Oktober 2026.

Dokumen ini merangkum endpoint yang dibuat dan terdampak selama pengembangan
fitur Atur Ongkir. Cakupannya adalah pengaturan seller, estimasi ongkir buyer,
metode pengiriman, cart, checkout, detail pesanan, riwayat transaksi, dan data struk.

## Daftar endpoint

| Status | Method | Endpoint | Role | Kegunaan / perubahan |
| --- | --- | --- | --- | --- |
| Baru | GET | `/api/seller/tenants/{tenantId}/shipping-settings` | seller | Membaca pengaturan ongkir toko milik seller. |
| Baru | PUT | `/api/seller/tenants/{tenantId}/shipping-settings` | seller | Menyimpan mode gratis, flat, atau berdasarkan jarak. |
| Baru | POST | `/api/tenants/{tenantId}/shipping-quote` | buyer | Menghitung estimasi langsung berdasarkan koordinat tujuan. |
| Baru | GET | `/api/seller/orders/{id}/receipt` | seller | Mengambil data JSON untuk cetak struk pesanan miliknya. |
| Terdampak | GET | `/api/delivery-methods` | buyer | Tarif kurir toko mengikuti isi cart dan pengaturan toko. |
| Terdampak | PATCH | `/api/cart/delivery-method` | buyer | Validasi ketersediaan dan estimasi tarif sebelum menyimpan pilihan. |
| Terdampak | GET | `/api/cart` | buyer | Ongkir dan total mengikuti pengaturan toko; menampilkan alasan jika tidak tersedia. |
| Terdampak | POST | `/api/checkout` | buyer | Menghitung ulang ongkir, membatasi satu toko, dan menyimpan snapshot. |
| Terdampak | GET | `/api/seller/orders/{id}` | seller | Menambahkan rincian ongkir tersimpan dan ongkir bagian seller. |
| Terdampak | GET | `/api/users/transactions/{transactionId}` | buyer | Menambahkan snapshot ongkir transaksi sendiri. |
| Terdampak | GET | `/api/users/transactions` | buyer | Menambahkan snapshot ongkir pada setiap item riwayat. |

Total: **4 endpoint baru dan 7 endpoint terdampak**.

## Header dan parameter umum

```http
Authorization: Bearer <session-token>
Accept: application/json
Content-Type: application/json
```

`Content-Type` digunakan untuk request dengan body JSON. Semua path sudah
mencakup prefix `/api`; tambahkan domain API sesuai environment.

| Parameter path | Nilai |
| --- | --- |
| `tenantId` | UUID toko yang dituju. |
| `id` | UUID transaksi/pesanan pada endpoint seller. |
| `transactionId` | UUID transaksi pada endpoint buyer. |

Seller hanya dapat membaca/mengubah pengaturan toko miliknya dan mengakses
pesanan yang mengandung item tokonya. Buyer hanya dapat melihat detail dan
riwayat transaksi sendiri.

## Alur pemakaian

1. Seller membaca lalu menyimpan pengaturan ongkir tokonya.
2. Buyer mengisi cart dengan produk dari satu toko dan memperbarui alamat profilnya.
3. Buyer membaca metode pengiriman, lalu memilih kurir toko atau pickup.
4. Aplikasi membaca kembali cart untuk menampilkan ongkir dan total terbaru.
5. Saat checkout, server menghitung ulang ongkir dan menyimpan rincian perhitungannya.
6. Detail pesanan, riwayat, dan struk membaca nilai transaksi yang sudah disimpan.

Estimasi langsung `shipping-quote` memakai koordinat dalam request. Estimasi
metode pengiriman, cart, dan checkout memakai koordinat pada profil buyer.
Memanggil `shipping-quote` tidak menyimpan alamat atau mengganti pilihan cart.

## Kontrak pengaturan ongkir dan aturan bisnis

Semua endpoint memerlukan `Authorization: Bearer <session-token>`.
Pengaturan ini digunakan oleh metode pengiriman buyer, cart, checkout, detail
pesanan seller/buyer, riwayat transaksi, dan data cetak struk.

## Seller: membaca pengaturan

`GET /api/seller/tenants/{tenantId}/shipping-settings`

Hanya role seller pemilik toko. Respons 200:

```json
{
  "message": "Pengaturan ongkir berhasil diambil.",
  "data": {
    "tenant_id": "<uuid>",
    "mode": "flat",
    "flat_fee": 5000,
    "fee_per_km": null,
    "max_distance_km": 10
  }
}
```

Jika belum pernah diatur, `data` bernilai `null`; belum diatur tidak berarti gratis.

## Seller: menyimpan pengaturan

`PUT /api/seller/tenants/{tenantId}/shipping-settings`

Hanya role seller pemilik toko. Payload untuk masing-masing mode:

```json
{"mode":"free","flat_fee":null,"fee_per_km":null,"max_distance_km":10}
```

```json
{"mode":"flat","flat_fee":5000,"fee_per_km":null,"max_distance_km":10}
```

```json
{"mode":"distance","flat_fee":null,"fee_per_km":2000,"max_distance_km":15}
```

Respons 200 memiliki bentuk data yang sama dengan GET, dengan message
`Pengaturan ongkir berhasil disimpan.` PUT menggantikan seluruh pengaturan:
field tarif yang tidak digunakan dihapus menjadi null. Tarif yang relevan wajib
berupa integer rupiah 0–1.000.000.000. Tarif yang tidak relevan hanya boleh null
atau tidak dikirim. `max_distance_km` opsional/nullable (tanpa batas jangkauan),
lebih besar dari 0, maksimal 50.000, hingga tiga angka desimal.

## Buyer: estimasi ongkir

`POST /api/tenants/{tenantId}/shipping-quote`

Hanya role buyer. Request:

```json
{"latitude":0,"longitude":0.01}
```

Respons 200 untuk toko di koordinat 0,0 dengan mode distance, tarif Rp2.000/km:

```json
{
  "message": "Estimasi ongkir berhasil dihitung.",
  "data": {
    "tenant_id": "<uuid>",
    "mode": "distance",
    "distance_km": 1.112,
    "distance_method": "straight_line",
    "delivery_fee": 2224,
    "is_deliverable": true
  }
}
```

Jarak memakai Haversine (radius bumi 6.371 km), bukan rute jalan. Tarif distance
menggunakan jarak sebelum pembulatan × fee_per_km, dibulatkan ke atas ke rupiah
penuh. Jarak respons dibulatkan ke tiga desimal. Mode free menghasilkan 0,
flat menggunakan flat_fee. Jika tidak ada batas jangkauan pada mode free/flat,
jarak tidak dihitung dan distance_km serta distance_method bernilai null.

Koordinat toko wajib tersedia untuk mode distance atau jika batas jangkauan
ada. Latitude tujuan harus -90 sampai 90; longitude -180 sampai 180. Jarak
tepat pada batas masih dilayani; di luar batas mengembalikan 200 dengan
is_deliverable=false dan delivery_fee=null.

## Error

- 401: tanpa session token yang valid.
- 403: role tidak sesuai.
- 404: toko tidak ditemukan, atau seller bukan pemilik toko.
- 422: payload invalid, koordinat toko yang diperlukan belum tersedia, atau
  pengaturan ongkir belum tersedia untuk estimasi.

Contoh respons validasi:

```json
{
  "message": "The flat fee field is required when mode is flat.",
  "errors": {"flat_fee":["The flat fee field is required when mode is flat."]}
}
```

## Migrasi

Jalankan migrasi sebelum menggunakan endpoint baru. Tabel baru
`tenant_shipping_settings` menyimpan satu pengaturan per toko dan tidak mengubah
data lama. Rollback menghapus tabel beserta pengaturannya.


## Integrasi metode pengiriman, cart, dan checkout

Checkout hanya boleh berisi produk dari **satu toko**, termasuk untuk pickup.
Cart boleh menyimpan beberapa toko, tetapi harus disisakan satu toko sebelum
checkout. Penolakan tidak menghapus cart, mengurangi stok, atau memakai promo.

- `GET /api/delivery-methods`: biaya `store_courier` mengikuti toko dalam cart dan
  koordinat profil buyer. Respons per metode menambahkan `shipping_snapshot`,
  `is_deliverable`, dan `shipping_error`. Tanpa isi cart, fee tetap tarif master.
- `PATCH /api/cart/delivery-method`: menghitung tarif yang sama sebelum menyimpan
  pilihan. Jika tidak tersedia, mengembalikan 422 dengan errors.delivery_method_code.
  `data.delivery_method` menyertakan shipping_snapshot dan is_deliverable.
- `GET /api/cart`: delivery_method.fee dan delivery_fee memakai hasil perhitungan
  yang sama. Menambahkan shipping_snapshot, is_deliverable, shipping_error.
  Jika tidak tersedia, delivery_fee, fee_label, grand_total, dan label total
  bernilai null. Cart tetap bisa ditampilkan dan diedit. Tanpa pilihan metode,
  ongkir tetap 0 sampai buyer memilih metode pengiriman.
- `POST /api/checkout`: menghitung ulang menggunakan alamat profil buyer terbaru
  di server. Fee/snapshot yang dikirim client tidak dipakai. Pengaturan ongkir
  dibaca kembali di dalam transaksi dan hasilnya disimpan ke transaksi pesanan.
  total_amount = max(subtotal_amount + delivery_fee - discount_amount, 0),
  mengikuti rumus total yang sudah ada. Respons checkout menambahkan shipping_snapshot.

Aturan pengiriman hanya mengganti tarif metode `store_courier`. Pickup mengikuti
master metode pickup (default Rp0) dan tidak terikat radius pengantaran toko.
Toko yang belum mengatur ongkir tetap memakai fee master untuk kompatibilitas,
dengan snapshot mode `legacy`. Ini berbeda dari endpoint estimasi langsung
shipping-quote yang tetap menolak toko tanpa pengaturan.

Jika mode distance atau batas jangkauan aktif, koordinat toko dan profil buyer
wajib tersedia. Di luar radius atau tanpa koordinat, preview mengembalikan
is_deliverable=false, shipping_error berisi alasan, dan fee=null. Checkout
mengembalikan 422 dalam format message/errors dan tidak membuat pesanan.

Contoh tambahan data cart untuk mode flat:

```json
{
  "delivery_fee": 5000,
  "is_deliverable": true,
  "shipping_error": null,
  "shipping_snapshot": {
    "tenant_id": "<uuid>",
    "tenant_name": "Toko Contoh",
    "mode": "flat",
    "flat_fee": 5000,
    "fee_per_km": null,
    "max_distance_km": null,
    "distance_km": null,
    "distance_method": null,
    "delivery_fee": 5000,
    "is_deliverable": true
  }
}
```

Snapshot untuk pickup null; biaya pickup tetap disimpan pada delivery_fee.
Snapshot legacy tidak memuat is_deliverable, nilainya sudah dipastikan tersedia
sebelum checkout. Snapshot transaksi lama juga null dan tidak dihitung ulang.

## Detail pesanan dan riwayat

Endpoint berikut menambahkan `shipping_snapshot` dari transaksi yang tersimpan:

- `GET /api/users/transactions/{transactionId}`
- `GET /api/users/transactions`
- `GET /api/seller/orders/{id}`

Perubahan pengaturan ongkir/alamat buyer setelah checkout tidak mengubah biaya,
total, atau snapshot pesanan lama. Detail seller menambahkan `is_single_store`,
`seller_delivery_fee`, dan `seller_delivery_fee_label`. Untuk transaksi lama
beberapa toko tanpa alokasi ongkir, seller_delivery_fee bernilai null; field
lama delivery_fee dan total_amount tetap berarti keseluruhan transaksi.

## Data cetak struk seller

`GET /api/seller/orders/{id}/receipt`

Memerlukan role seller pemilik toko pada pesanan; 401 jika tanpa token, 403
untuk role lain, dan 404 untuk pesanan tidak ditemukan atau milik seller lain.
Respons 200 adalah JSON siap dipakai client untuk merender/mencetak struk,
bukan PDF atau perintah ke printer:

```json
{
  "message": "Data struk pesanan berhasil diambil.",
  "data": {
    "id": "<uuid>",
    "order_number": "ORD-...",
    "store_name": "Toko Contoh",
    "buyer": {"id":"<uuid>","name":"Buyer Contoh","email":null,"phone":null,"address":"Alamat checkout","landmark":null,"latitude":null,"longitude":null},
    "items": [
      {
        "id": "<transaction-item-uuid>",
        "product_id": "<product-uuid>",
        "tenant_id": "<tenant-uuid>",
        "tenant_name": "Toko Contoh",
        "product_name": "Produk Contoh",
        "quantity": 2,
        "unit_price": 10000,
        "unit_price_label": "Rp. 10.000",
        "line_total": 20000,
        "line_total_label": "Rp. 20.000"
      }
    ],
    "delivery_method": "Antar Kurir Toko",
    "delivery_method_code": "store_courier",
    "payment_method": "COD",
    "subtotal_amount": 20000,
    "subtotal_amount_label": "Rp. 20.000",
    "delivery_fee": 5000,
    "delivery_fee_label": "Rp. 5.000",
    "shipping_snapshot": null,
    "service_fee": 0,
    "service_fee_label": "Rp. 0",
    "discount_amount": 0,
    "discount_amount_label": "Rp. 0",
    "total_amount": 25000,
    "total_amount_label": "Rp. 25.000",
    "transaction_at": "2026-10-27T00:00:00+00:00",
    "transaction_at_label": "27 Okt 2026, 07:00 WIB"
  }
}
```

Items mengikuti bentuk items pada detail seller (product_name, quantity,
unit_price, line_total, dan label nominal). shipping_snapshot sama dengan detail
pesanan, termasuk null untuk transaksi lama. Struk transaksi lama dengan lebih
dari satu toko mengembalikan 422 karena alokasi ongkir/diskon belum tersedia;
tidak memakai total gabungan sebagai total struk satu toko.

## Migrasi snapshot

Jalankan migration `2026_10_27_000002_add_shipping_snapshot_to_transactions`
sebelum menggunakan kode ini. Kolom JSON nullable tidak mengubah nominal
transaksi lama. Rollback hanya menghapus rincian snapshot, bukan delivery_fee
atau total_amount yang sudah disimpan.

## Contoh integrasi endpoint terdampak

Contoh respons di bagian ini adalah **potongan field yang relevan dengan ongkir**,
kecuali respons PATCH yang ditampilkan lengkap. Field lain pada endpoint yang
sudah ada tetap mengikuti kontrak masing-masing. UUID berupa placeholder.

Semua contoh ongkir flat memakai tarif Rp5.000, subtotal Rp20.000, tanpa diskon.
Nilai `shipping_snapshot` pada preview masih estimasi; nilai yang dikembalikan
checkout dan endpoint pesanan adalah hasil yang sudah tersimpan.

### GET `/api/delivery-methods`

Request tanpa body. Status sukses **200**. `data` adalah array metode aktif;
contoh berikut menunjukkan satu metode kurir toko. Pickup tetap tersedia jika
alamat tidak terjangkau kurir, selama cart tidak berisi beberapa toko.

```json
{
  "message": "Daftar metode pengiriman berhasil diambil.",
  "data": [
    {
      "id": "<delivery-method-uuid>",
      "code": "store_courier",
      "name": "Antar Kurir Toko",
      "description": "Diantar hari ini",
      "fee": 5000,
      "fee_label": "Rp 5.000",
      "shipping_snapshot": {
        "tenant_id": "<tenant-uuid>",
        "mode": "flat",
        "distance_km": null,
        "distance_method": null,
        "delivery_fee": 5000,
        "is_deliverable": true,
        "tenant_name": "Toko Contoh",
        "flat_fee": 5000,
        "fee_per_km": null,
        "max_distance_km": null
      },
      "is_deliverable": true,
      "shipping_error": null,
      "requires_order_time": false
    }
  ]
}
```

Jika metode tidak tersedia, `fee` dan `fee_label` bernilai null,
`is_deliverable` false, dan `shipping_error` berisi alasan.

### PATCH `/api/cart/delivery-method`

Request:

```json
{
  "delivery_method_code": "store_courier"
}
```

Untuk pickup, gunakan `delivery_method_code: "pickup"`.
Respons sukses **200**:

```json
{
  "message": "Metode pengiriman keranjang berhasil diperbarui.",
  "data": {
    "delivery_method": {
      "id": "<delivery-method-uuid>",
      "code": "store_courier",
      "name": "Antar Kurir Toko",
      "description": "Diantar hari ini",
      "fee": 5000,
      "fee_label": "Rp 5.000",
      "shipping_snapshot": {
        "tenant_id": "<tenant-uuid>",
        "mode": "flat",
        "distance_km": null,
        "distance_method": null,
        "delivery_fee": 5000,
        "is_deliverable": true,
        "tenant_name": "Toko Contoh",
        "flat_fee": 5000,
        "fee_per_km": null,
        "max_distance_km": null
      },
      "is_deliverable": true,
      "requires_order_time": false
    }
  }
}
```

Kode metode wajib aktif. Jika pengiriman tidak tersedia, respons **422** dan
pilihan metode sebelumnya tidak diganti.

### GET `/api/cart`

Request tanpa body. Respons sukses **200**, termasuk jika pengiriman belum tersedia:

```json
{
  "message": "Keranjang berhasil diambil.",
  "data": {
    "subtotal": 20000,
    "subtotal_label": "Rp 20.000",
    "delivery_method": {
      "id": "<delivery-method-uuid>",
      "code": "store_courier",
      "name": "Antar Kurir Toko",
      "description": "Diantar hari ini",
      "fee": 5000,
      "fee_label": "Rp 5.000",
      "requires_order_time": false
    },
    "delivery_fee": 5000,
    "delivery_fee_label": "Rp 5.000",
    "shipping_snapshot": {
      "tenant_id": "<tenant-uuid>",
      "mode": "flat",
      "distance_km": null,
      "distance_method": null,
      "delivery_fee": 5000,
      "is_deliverable": true,
      "tenant_name": "Toko Contoh",
      "flat_fee": 5000,
      "fee_per_km": null,
      "max_distance_km": null
    },
    "is_deliverable": true,
    "shipping_error": null,
    "grand_total": 25000,
    "grand_total_label": "Rp 25.000"
  }
}
```

Contoh potongan cart di luar jangkauan:

```json
{
  "data": {
    "delivery_fee": null,
    "delivery_fee_label": null,
    "shipping_snapshot": null,
    "is_deliverable": false,
    "shipping_error": "Alamat pembeli berada di luar jangkauan pengantaran toko.",
    "grand_total": null,
    "grand_total_label": null
  }
}
```

Client perlu menangani nominal null sebagai belum tersedia, bukan gratis.
Jika cart beberapa toko, `shipping_error` meminta buyer menyisakan satu toko.

### POST `/api/checkout`

Contoh request kurir toko dengan COD:

```json
{
  "delivery_method_code": "store_courier",
  "payment_method_code": "cod"
}
```

Contoh request pickup segera:

```json
{
  "delivery_method_code": "pickup",
  "payment_method_code": "cod",
  "pickup_time_option": "sekarang"
}
```

Field request terkait alur checkout:

| Field | Ketentuan |
| --- | --- |
| `delivery_method_code` | Wajib; metode pengiriman aktif. |
| `payment_method_code` | Wajib; metode pembayaran aktif. |
| `payment_method_option_code` | Wajib jika metode pembayaran memerlukan opsi. |
| `pickup_time_option` | Wajib jika metode pengiriman memerlukan opsi waktu. |
| `pickup_scheduled_at` | Format `H:i`; wajib jika opsi waktu memerlukan jadwal. |
| `promo_code` | Opsional; tetap mengikuti validasi promo yang ada. |

Nominal ongkir tidak perlu dikirim client. Server menentukan `delivery_fee` dan
`shipping_snapshot` dari pengaturan yang berlaku saat checkout.

Respons sukses **201**, potongan field:

```json
{
  "message": "Checkout berhasil dibuat.",
  "data": {
    "transaction_id": "<transaction-uuid>",
    "order_number": "ORD-...",
    "subtotal_amount": 20000,
    "subtotal_amount_label": "Rp 20.000",
    "delivery_fee": 5000,
    "delivery_fee_label": "Rp 5.000",
    "shipping_snapshot": {
      "tenant_id": "<tenant-uuid>",
      "mode": "flat",
      "distance_km": null,
      "distance_method": null,
      "delivery_fee": 5000,
      "is_deliverable": true,
      "tenant_name": "Toko Contoh",
      "flat_fee": 5000,
      "fee_per_km": null,
      "max_distance_km": null
    },
    "discount_amount": 0,
    "discount_amount_label": "Rp 0",
    "total_amount": 25000,
    "total_amount_label": "Rp 25.000"
  }
}
```

Checkout ditolak **422** jika cart beberapa toko, koordinat yang diperlukan
belum tersedia, alamat di luar jangkauan, cart kosong, toko tutup, atau syarat
produk/stok tidak terpenuhi. Validasi pembayaran dan promo tetap berlaku.

### GET `/api/seller/orders/{id}`

Request tanpa body. Respons sukses **200**. Tambahan field:

| Field | Keterangan |
| --- | --- |
| `shipping_snapshot` | Rincian ongkir saat checkout, atau null untuk pesanan lama/pickup. |
| `is_single_store` | Apakah transaksi hanya berisi satu toko. |
| `seller_delivery_fee` | Ongkir bagian seller; null jika alokasi transaksi lama beberapa toko tidak diketahui. |
| `seller_delivery_fee_label` | Label rupiah atau null. |

Field lama `delivery_fee` dan `total_amount` tetap berarti nilai keseluruhan
transaksi, termasuk untuk transaksi lama beberapa toko.

Potongan respons pesanan satu toko:

```json
{
  "message": "Detail order seller berhasil diambil.",
  "data": {
    "id": "<transaction-uuid>",
    "order_number": "ORD-...",
    "is_single_store": true,
    "seller_delivery_fee": 5000,
    "seller_delivery_fee_label": "Rp. 5.000",
    "delivery_fee": 5000,
    "delivery_fee_label": "Rp. 5.000",
    "shipping_snapshot": {
      "tenant_id": "<tenant-uuid>",
      "mode": "flat",
      "distance_km": null,
      "distance_method": null,
      "delivery_fee": 5000,
      "is_deliverable": true,
      "tenant_name": "Toko Contoh",
      "flat_fee": 5000,
      "fee_per_km": null,
      "max_distance_km": null
    },
    "total_amount": 25000,
    "total_amount_label": "Rp. 25.000"
  }
}
```

### GET `/api/users/transactions/{transactionId}`

Request tanpa body. Respons sukses **200** untuk transaksi milik buyer; **404** jika tidak ditemukan atau milik buyer lain.

```json
{
  "message": "Detail riwayat transaksi berhasil diambil.",
  "data": {
    "id": "<transaction-uuid>",
    "delivery_method": "Antar Kurir Toko",
    "delivery_method_code": "store_courier",
    "delivery_fee": 5000,
    "delivery_fee_label": "Rp. 5.000",
    "shipping_snapshot": {
      "tenant_id": "<tenant-uuid>",
      "mode": "flat",
      "distance_km": null,
      "distance_method": null,
      "delivery_fee": 5000,
      "is_deliverable": true,
      "tenant_name": "Toko Contoh",
      "flat_fee": 5000,
      "fee_per_km": null,
      "max_distance_km": null
    },
    "total_amount": 25000,
    "total_amount_label": "Rp. 25.000"
  }
}
```

### GET `/api/users/transactions`

Request tanpa body. Respons sukses **200**. Query opsional `page` untuk
pagination dan `status_code` untuk filter status yang valid. Setiap transaksi
menambahkan `shipping_snapshot`. Endpoint ini tidak menambahkan field total
transaksi baru; contoh hanya memuat field ongkir.

```json
{
  "message": "Riwayat transaksi berhasil diambil.",
  "data": [
    {
      "id": "<transaction-uuid>",
      "delivery_method": "Antar Kurir Toko",
      "delivery_method_code": "store_courier",
      "delivery_fee": 5000,
      "delivery_fee_label": "Rp. 5.000",
      "shipping_snapshot": {
        "tenant_id": "<tenant-uuid>",
        "mode": "flat",
        "distance_km": null,
        "distance_method": null,
        "delivery_fee": 5000,
        "is_deliverable": true,
        "tenant_name": "Toko Contoh",
        "flat_fee": 5000,
        "fee_per_km": null,
        "max_distance_km": null
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
  }
}
```

Respons sebenarnya juga memuat `links` untuk pagination. Nominal pada riwayat
tetap berasal dari transaksi tersimpan, bukan pengaturan toko saat riwayat dibuka.

## Contoh error integrasi

Contoh checkout beberapa toko, status **422**:

```json
{
  "message": "Checkout hanya dapat berisi produk dari satu toko.",
  "errors": {
    "delivery_method_code": [
      "Checkout hanya dapat berisi produk dari satu toko."
    ]
  }
}
```

Contoh checkout di luar jangkauan, status **422**:

```json
{
  "message": "Alamat pembeli berada di luar jangkauan pengantaran toko.",
  "errors": {
    "delivery_method_code": [
      "Alamat pembeli berada di luar jangkauan pengantaran toko."
    ]
  }
}
```

| Kondisi | Hasil |
| --- | --- |
| Tanpa token / token tidak valid | 401. |
| Role tidak sesuai | 403. |
| Seller mengakses toko/pesanan seller lain | 404. |
| Buyer mengakses transaksi buyer lain | 404. |
| Mode, tarif, koordinat, atau kode metode invalid | 422 dengan `errors`. |
| Estimasi langsung di luar jangkauan | 200, `is_deliverable=false`, `delivery_fee=null`. |
| Metode pengiriman/cart tidak terjangkau | 200 untuk preview, nominal null dan alasan tersedia. |
| Memilih metode tidak tersedia / checkout tidak terjangkau | 422 dengan `errors.delivery_method_code`. |
| Toko belum mengatur ongkir pada estimasi langsung | 422 dengan `errors.shipping_settings`. |
| Toko belum mengatur ongkir pada cart/checkout | Memakai tarif master, snapshot mode `legacy`. |
| Cetak struk transaksi lama beberapa toko | 422, alokasi ongkir/diskon per toko belum tersedia. |

## Ringkasan penyesuaian aplikasi

| Area | Penyesuaian |
| --- | --- |
| Seller — Atur Ongkir | Baca GET dan simpan PUT; kosongkan tarif mode lain menjadi null. |
| Seller — detail pesanan | Tampilkan ongkir tersimpan, gunakan `seller_delivery_fee` untuk porsi seller. |
| Seller — cetak struk | Ambil endpoint receipt, render struk di aplikasi dari JSON yang dikembalikan. |
| Buyer — metode pengiriman | Tampilkan fee terbaru; nonaktifkan pilihan jika `is_deliverable=false`. |
| Buyer — cart | Tangani ongkir/total null dan tampilkan `shipping_error`. |
| Buyer — checkout | Satu toko; tampilkan validasi server dan nominal checkout sebagai hasil final. |
| Buyer — detail / riwayat | Tampilkan `delivery_fee` tersimpan; mode dan tarif tersedia di snapshot. |

## Catatan rilis

- Terapkan kedua migration ongkir sebelum menggunakan endpoint dan integrasi ini:
  `2026_10_27_000001_create_tenant_shipping_settings_table` dan
  `2026_10_27_000002_add_shipping_snapshot_to_transactions`.
- Perintah migrasi mengikuti proses deployment proyek: `php artisan migrate`.
- Dokumen ini tidak menjalankan migrasi atau mengubah data aplikasi.
- Respons data struk berupa JSON; proses render PDF atau pengiriman ke printer
  dilakukan pada aplikasi pemakai API.
- Label uang pada cart/checkout memakai `Rp `, sedangkan endpoint detail/riwayat
  dan struk mengikuti format yang sudah ada, `Rp. `.
