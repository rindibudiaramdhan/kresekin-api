# Pengaturan ongkir toko

Semua endpoint memerlukan `Authorization: Bearer <session-token>`.
Implementasi ini belum mengubah cart, checkout, transaksi, atau struk.

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
