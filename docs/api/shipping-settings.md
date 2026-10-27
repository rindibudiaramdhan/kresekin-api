# Pengaturan ongkir toko

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
    "items": [],
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
