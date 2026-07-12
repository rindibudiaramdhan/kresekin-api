# Fitur Atur Ongkir Seller

Tanggal: 2026-07-12

## Ringkasan

Seller membutuhkan fitur untuk mengatur ongkir toko agar biaya pengiriman tidak hanya bergantung pada master global `delivery_methods.fee`. Pengaturan ini terutama berlaku untuk metode pengiriman `store_courier` atau `Antar Kurir Toko`, sedangkan metode `pickup` tetap tanpa ongkir.

Dokumen ini berisi task dan requirement awal sebelum implementasi. Belum ada perubahan kode aplikasi, database, test, atau dokumentasi API.

## Acuan Dokumen

Task ini mengikuti aturan pada:

1. `docs/requirements/00-vision-scope.md`
2. `docs/requirements/02-roles-permissions.md`
3. `docs/requirements/03-service-catalog.md`
4. `docs/requirements/04-incident-mgmt.md`
5. `docs/requirements/13-engineering-standards.md`
6. `docs/adr/004-role-based-access-with-ownership-scoping.md`
7. `docs/adr/007-snapshot-transaction-data.md`
8. `docs/adr/008-server-side-financial-calculation.md`
9. `docs/adr/012-configuration-driven-domain-values.md`

Aturan penting yang perlu dijaga:

1. Endpoint authenticated wajib memakai middleware `session.token`.
2. Endpoint seller wajib memakai middleware `role:seller`.
3. Seller hanya boleh mengatur ongkir untuk tenant miliknya melalui `tenants.owner_user_id`.
4. Resource di luar scope seller sebaiknya dikembalikan sebagai `404`.
5. Nominal uang harus berupa integer.
6. Ongkir dan total transaksi harus dihitung atau divalidasi server-side.
7. Nilai ongkir yang dipakai saat checkout harus disnapshot ke `transactions.delivery_fee`.
8. Perubahan ongkir setelah checkout tidak boleh mengubah histori transaksi lama.
9. Response API harus memakai allowlist field, bukan model mentah.
10. Setiap perubahan behavior harus memiliki regression test.

## Kondisi Saat Ini

1. Master metode pengiriman berada pada tabel `delivery_methods`.
2. Seeder saat ini memiliki:
   - `store_courier` dengan fee default `2500`.
   - `pickup` dengan fee default `0`.
3. Cart menyimpan `delivery_method_code`.
4. Checkout mengambil `delivery_fee` dari master `delivery_methods.fee`.
5. Transaksi menyimpan snapshot:
   - `delivery_fee`
   - `delivery_method`
   - `delivery_method_code`
   - `pickup_time_option`
   - `pickup_scheduled_at`
6. Seller order list/detail sudah menampilkan `delivery_fee`, `delivery_method`, dan `delivery_method_code`.
7. Belum ada konfigurasi ongkir per seller atau per tenant.

## Tujuan

Menyediakan mekanisme agar seller dapat mengatur ongkir toko sendiri secara aman, terukur, dan tidak mematahkan checkout maupun histori transaksi.

## Keputusan yang Perlu Dikonfirmasi

1. Scope pengaturan ongkir:
   - Per seller.
   - Per tenant/toko.
   - Per housing area.
   - Per radius/jarak.
2. Apakah seller boleh mengatur ongkir hanya untuk `store_courier`.
3. Apakah `pickup` wajib selalu `0`.
4. Apakah ongkir boleh dibuat gratis oleh seller.
5. Batas minimum dan maksimum ongkir.
6. Apakah perubahan ongkir membutuhkan approval internal atau langsung aktif.
7. Apakah buyer perlu melihat ongkir sebelum checkout melalui cart/catalog.
8. Apakah perlu audit log untuk perubahan ongkir.

## Rekomendasi MVP

1. Ongkir diatur per tenant milik seller.
2. Konfigurasi hanya berlaku untuk metode `store_courier`.
3. Metode `pickup` tetap menggunakan ongkir `0`.
4. Seller dapat melihat dan mengubah ongkir toko miliknya.
5. Ongkir disimpan sebagai integer rupiah, misalnya `5000`.
6. Nilai ongkir minimum `0`.
7. Nilai ongkir maksimum mengikuti konfigurasi server, misalnya `config('api.max_delivery_fee')`.
8. Checkout memakai ongkir tenant jika tersedia; jika belum tersedia, gunakan fallback master `delivery_methods.fee`.
9. Nilai ongkir yang dipakai checkout tetap disnapshot ke `transactions.delivery_fee`.
10. Perubahan ongkir hanya memengaruhi checkout baru.

## Scope

1. Menambahkan rancangan penyimpanan ongkir tenant/seller.
2. Menambahkan rancangan API seller untuk melihat pengaturan ongkir.
3. Menambahkan rancangan API seller untuk update ongkir.
4. Menyesuaikan perhitungan cart dan checkout agar memakai ongkir seller/tenant.
5. Menjaga snapshot ongkir pada transaksi.
6. Menyiapkan test untuk authorization, ownership, validasi nominal, cart, checkout, dan histori transaksi.

## Out of Scope Awal

1. Implementasi kode aplikasi.
2. Perubahan database/migration.
3. Integrasi frontend/mobile.
4. Ongkir berdasarkan jarak, koordinat, atau integrasi maps.
5. Ongkir berdasarkan berat/volume produk.
6. Ongkir multi-zona atau per housing area.
7. Promo gratis ongkir.
8. Approval workflow finance/admin untuk perubahan ongkir.
9. Integrasi provider logistik eksternal.

## Rancangan Data

Opsi yang direkomendasikan untuk MVP adalah menambahkan kolom ongkir pada tabel `tenants` karena tenant adalah toko yang dimiliki seller dan sudah menjadi boundary ownership.

Field yang disarankan:

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `delivery_fee` | `unsignedBigInteger`, nullable | Ongkir khusus tenant untuk `store_courier` |
| `delivery_fee_updated_at` | `timestamp`, nullable | Waktu terakhir seller mengubah ongkir |

Aturan nilai:

1. `delivery_fee = null` berarti tenant memakai fallback master `delivery_methods.fee`.
2. `delivery_fee = 0` berarti seller sengaja membuat ongkir gratis.
3. `delivery_fee` tidak boleh negatif.
4. `pickup` tetap menghasilkan ongkir `0`, walaupun tenant memiliki `delivery_fee`.

Alternatif jika di masa depan butuh konfigurasi lebih kompleks:

1. Buat tabel `tenant_delivery_settings`.
2. Relasikan ke `tenant_id` dan `delivery_method_code`.
3. Tambahkan dukungan area, radius, status aktif, dan audit.

Untuk MVP, tabel terpisah belum diperlukan bila hanya ada satu fee untuk `store_courier`.

## Rancangan Perhitungan Ongkir

Prioritas sumber ongkir:

1. Jika `delivery_method_code = pickup`, ongkir `0`.
2. Jika `delivery_method_code = store_courier` dan tenant memiliki `delivery_fee` tidak `null`, pakai `tenants.delivery_fee`.
3. Jika tenant belum memiliki `delivery_fee`, pakai fallback `delivery_methods.fee`.
4. Jika metode pengiriman tidak aktif, checkout harus gagal sesuai validasi existing.

Catatan multi-tenant:

1. Jika satu cart hanya mendukung satu tenant, ongkir diambil dari tenant cart tersebut.
2. Jika cart dapat berisi produk dari beberapa tenant, requirement harus diputuskan sebelum implementasi.
3. Untuk multi-tenant, opsi yang perlu dipilih:
   - Checkout ditolak jika cart berisi beberapa tenant.
   - Ongkir dijumlahkan per tenant.
   - Ongkir memakai aturan khusus platform.

Rekomendasi MVP: pastikan checkout hanya memakai satu tenant atau tambahkan validasi eksplisit agar cart multi-tenant tidak menghasilkan ongkir ambigu.

## Task Analisis

1. Pastikan apakah cart saat ini boleh berisi produk dari lebih dari satu tenant.
2. Tentukan apakah ongkir seller berlaku per seller atau per tenant.
3. Tentukan apakah buyer-facing cart harus menampilkan sumber ongkir tenant.
4. Tentukan batas maksimum ongkir yang masuk akal untuk validasi.
5. Tentukan apakah perubahan ongkir perlu audit event.
6. Tentukan apakah perubahan ongkir langsung aktif untuk semua checkout baru.
7. Review semua endpoint yang menghitung atau menampilkan `delivery_fee`.
8. Pastikan perubahan tidak mematahkan kontrak `GET /api/delivery-methods`.

## Task Database

1. Buat migration baru untuk menambahkan `delivery_fee` dan `delivery_fee_updated_at` ke tabel `tenants`.
2. Buat seluruh kolom nullable agar tenant existing tetap kompatibel.
3. Jangan melakukan backfill massal kecuali ada keputusan produk yang eksplisit.
4. Tambahkan cast pada model `Tenant`:
   - `delivery_fee` sebagai integer.
   - `delivery_fee_updated_at` sebagai datetime.
5. Pastikan `delivery_fee = 0` tetap tersimpan dan tidak diperlakukan sebagai `null`.

## Task Backend/API

1. Buat route API seller dengan prefix `/api/seller`.
2. Pastikan endpoint memakai middleware `session.token` dan `role:seller`.
3. Buat FormRequest untuk update ongkir seller.
4. Buat endpoint melihat pengaturan ongkir tenant.
5. Buat endpoint update ongkir tenant.
6. Pastikan tenant di-query dengan scope `owner_user_id = current user`.
7. Kembalikan `404` untuk tenant di luar ownership seller.
8. Validasi `delivery_fee` wajib integer, minimum `0`, dan maksimum sesuai konfigurasi.
9. Pastikan response tidak mengembalikan model `Tenant` mentah.
10. Buat helper/service kecil untuk resolusi ongkir efektif agar cart dan checkout tidak menduplikasi logic.
11. Update cart calculation agar `delivery_fee` memakai ongkir efektif tenant.
12. Update checkout calculation agar `delivery_fee` memakai ongkir efektif tenant di server.
13. Snapshot nilai ongkir final ke `transactions.delivery_fee`.
14. Pastikan `total_amount = subtotal_amount + delivery_fee - discount_amount` tetap dihitung server-side.
15. Pastikan perubahan ongkir setelah checkout tidak mengubah transaksi lama.

## Rekomendasi Endpoint

```http
GET /api/seller/tenants/{tenant}/delivery-fee
PATCH /api/seller/tenants/{tenant}/delivery-fee
```

Payload update:

```json
{
  "delivery_fee": 5000
}
```

Response berhasil:

```json
{
  "message": "Ongkir toko berhasil diperbarui.",
  "data": {
    "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
    "delivery_method_code": "store_courier",
    "delivery_fee": 5000,
    "delivery_fee_label": "Rp5.000",
    "uses_default_delivery_fee": false,
    "updated_at": "2026-07-12T10:30:00+07:00"
  }
}
```

Response lihat konfigurasi saat masih memakai default:

```json
{
  "message": "Pengaturan ongkir toko berhasil diambil.",
  "data": {
    "tenant_id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
    "delivery_method_code": "store_courier",
    "delivery_fee": 2500,
    "delivery_fee_label": "Rp2.500",
    "uses_default_delivery_fee": true,
    "updated_at": null
  }
}
```

## Dampak Endpoint Existing

Endpoint yang perlu direview saat implementasi:

1. `GET /api/delivery-methods`
2. `GET /api/cart`
3. `PATCH /api/cart/delivery-method`
4. `POST /api/checkout`
5. `GET /api/transactions`
6. `GET /api/transactions/{id}`
7. `GET /api/seller/orders`
8. `GET /api/seller/orders/{id}`

Ketentuan kompatibilitas:

1. `GET /api/delivery-methods` tetap boleh menampilkan master global.
2. Cart buyer harus menampilkan ongkir efektif sesuai tenant cart jika delivery method dipilih.
3. Checkout harus memakai ongkir efektif yang sama dengan cart.
4. Response transaksi dan seller order tetap memakai snapshot `transactions.delivery_fee`.
5. Field baru harus additive jika dokumentasi API diperbarui.

## Acceptance Criteria

1. Seller dapat melihat ongkir efektif tenant miliknya.
2. Seller dapat mengubah ongkir tenant miliknya.
3. Seller tidak dapat melihat atau mengubah ongkir tenant milik seller lain.
4. Role selain seller mendapat `403` saat mengakses endpoint seller.
5. Request tanpa token mendapat `401`.
6. Ongkir hanya menerima integer minimum `0`.
7. Ongkir di atas batas maksimum ditolak dengan `422`.
8. `delivery_fee = 0` valid dan berarti gratis ongkir.
9. Cart menampilkan ongkir tenant untuk `store_courier`.
10. Checkout memakai ongkir tenant untuk `store_courier`.
11. `pickup` tetap memakai ongkir `0`.
12. Checkout menyimpan snapshot ongkir final ke `transactions.delivery_fee`.
13. Perubahan ongkir setelah checkout tidak mengubah transaksi lama.
14. Total transaksi dihitung server-side memakai ongkir final.
15. Response API memakai struktur `message` dan `data`.

## Task Testing

1. Test seller bisa melihat ongkir tenant miliknya.
2. Test seller bisa update ongkir tenant miliknya.
3. Test seller tidak bisa akses tenant seller lain dan mendapat `404`.
4. Test unauthenticated mendapat `401`.
5. Test role buyer/agent/finance mendapat `403`.
6. Test validasi `delivery_fee` wajib integer.
7. Test validasi `delivery_fee` tidak boleh negatif.
8. Test validasi `delivery_fee` boleh `0`.
9. Test validasi `delivery_fee` tidak boleh melebihi batas maksimum.
10. Test cart `store_courier` memakai ongkir tenant.
11. Test cart `pickup` tetap `0`.
12. Test checkout `store_courier` memakai ongkir tenant.
13. Test checkout fallback ke master `delivery_methods.fee` jika tenant belum punya ongkir.
14. Test checkout snapshot ongkir ke `transactions.delivery_fee`.
15. Test transaksi lama tidak berubah setelah ongkir tenant diupdate.
16. Test total transaksi dihitung ulang dari subtotal, ongkir, dan diskon.
17. Jika cart multi-tenant masih memungkinkan, test validasi atau aturan ongkir yang dipilih.

## Dokumentasi API

Jika fitur sudah diimplementasikan, perbarui `API_DOCUMENTATION.md` untuk:

1. Endpoint lihat pengaturan ongkir seller.
2. Endpoint update pengaturan ongkir seller.
3. Aturan `delivery_fee = 0` dan fallback default.
4. Perubahan perhitungan ongkir pada cart dan checkout.
5. Penjelasan bahwa transaksi menyimpan snapshot ongkir dan tidak berubah setelah ongkir toko diperbarui.

## Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Cart multi-tenant membuat ongkir ambigu | Total checkout salah | Validasi satu tenant atau definisikan aturan multi-tenant sebelum implementasi |
| Client mengira `GET /api/delivery-methods` adalah ongkir final | UI menampilkan ongkir berbeda dari checkout | Dokumentasikan bahwa ongkir final ada pada cart/checkout |
| Perubahan ongkir mengubah histori order lama | Rekonsiliasi finance dan seller keliru | Tetap gunakan snapshot `transactions.delivery_fee` |
| Seller mengisi ongkir terlalu besar | Buyer dirugikan dan checkout tidak wajar | Terapkan maksimum server-side |
| Ownership check terlewat | Seller bisa mengubah tenant lain | Query tenant wajib memakai `owner_user_id` dan test out-of-scope |

## Verification Checklist

1. Jalankan Laravel Pint.
2. Jalankan test feature terkait seller delivery fee.
3. Jalankan test cart dan checkout terkait ongkir.
4. Jalankan test seller order bila response ongkir ikut terdampak.
5. Pastikan dokumentasi API diperbarui bila endpoint sudah diimplementasikan.
6. Pastikan nominal uang tetap integer.
7. Pastikan tidak ada perubahan unrelated.
