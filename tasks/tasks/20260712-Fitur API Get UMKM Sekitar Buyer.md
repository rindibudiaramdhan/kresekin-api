# Fitur API Get UMKM Sekitar Buyer

Tanggal: 2026-07-12

## Ringkasan

Buyer membutuhkan API untuk melihat daftar UMKM/tenant sekitar berdasarkan lokasi buyer, area perumahan, kategori, dan ketersediaan katalog. Fitur ini dipakai untuk halaman eksplorasi toko/UMKM terdekat agar buyer dapat memilih toko yang relevan sebelum melihat produk atau melakukan checkout.

Dokumen ini berisi task dan requirement awal sebelum implementasi. Belum ada perubahan kode aplikasi, database, test, atau dokumentasi API.

## Acuan Dokumen

Task ini mengikuti aturan pada:

1. `docs/requirements/00-vision-scope.md`
2. `docs/requirements/01-architecture-nfr.md`
3. `docs/requirements/02-roles-permissions.md`
4. `docs/requirements/03-service-catalog.md`
5. `docs/requirements/09-cmdb.md`
6. `docs/requirements/13-engineering-standards.md`
7. `docs/adr/002-modular-monolith-laravel.md`
8. `docs/adr/004-role-based-access-with-ownership-scoping.md`

Aturan penting yang perlu dijaga:

1. Endpoint buyer wajib memakai middleware `session.token` dan `role:buyer`.
2. Query list wajib menerapkan filter dan sorting sebelum pagination.
3. Endpoint buyer hanya boleh menampilkan tenant yang relevan untuk katalog buyer.
4. Response API harus memakai allowlist field, bukan serialize model `Tenant` mentah.
5. Endpoint list terpaginasikan harus memakai struktur `data`, `meta`, dan `links`.
6. Error validasi memakai status `422`.
7. Role selain buyer harus mendapat `403`.
8. Endpoint authenticated tanpa token atau token tidak valid harus mendapat `401`.
9. Field response existing tidak boleh dihapus atau diganti tipe tanpa migration/deprecation.
10. Setiap perubahan behavior harus memiliki regression test.

## Kondisi Saat Ini

1. Route buyer aktif sudah memiliki `GET /api/tenants` di group middleware `session.token` dan `role:buyer`.
2. `GET /api/tenants` saat ini diarahkan ke `GetBuyerTenantListController`.
3. `GetBuyerTenantListController` sudah mendukung:
   - `limit`
   - `page`
   - `housing_area_id`
   - `product_category`
   - metadata kategori produk tenant
   - metadata housing area tenant
4. Codebase masih memiliki `GetTenantListController` lain yang menghitung `distance_km`, `distance_label`, dan sorting jarak, tetapi tidak dipakai oleh route aktif.
5. `tests/Feature/TenantApiTest.php` sudah memiliki beberapa ekspektasi terkait jarak dan sorting UMKM terdekat.
6. `API_DOCUMENTATION.md` sudah mendokumentasikan `GET /api/tenants`, tetapi belum lengkap untuk parameter lokasi/jarak dan belum menyebut behavior UMKM sekitar secara eksplisit.
7. `Tenant` memiliki field koordinat `latitude` dan `longitude`.
8. `User` memiliki field koordinat `latitude` dan `longitude`, serta relasi area perumahan.

## Tujuan

Menyediakan kontrak API buyer-facing yang stabil untuk mengambil UMKM sekitar buyer dengan filter dan sorting jarak yang jelas, sekaligus merapikan implementasi agar route aktif, test, dan dokumentasi API konsisten.

## Keputusan yang Perlu Dikonfirmasi

1. Apakah fitur memakai endpoint existing `GET /api/tenants` atau endpoint alias baru seperti `GET /api/buyer/nearby-umkm`.
2. Apakah sumber lokasi utama memakai koordinat profil buyer atau query override dari client.
3. Apakah query override `latitude` dan `longitude` diperbolehkan untuk lokasi sementara.
4. Apakah tenant tanpa koordinat tetap ditampilkan di akhir list atau disembunyikan.
5. Apakah radius maksimal diperlukan pada MVP.
6. Apakah filter housing area wajib mengikuti area profil buyer secara default.
7. Apakah hanya tenant yang memiliki produk aktif dan tersedia yang boleh tampil.
8. Apakah tenant tutup tetap tampil dengan status `Tutup`, atau disembunyikan.
9. Apakah perlu field ringkas produk unggulan per tenant.

## Rekomendasi MVP

1. Gunakan endpoint existing `GET /api/tenants` sebagai kontrak utama agar tidak menambah endpoint duplikat.
2. Perjelas nama fitur di dokumentasi sebagai "UMKM sekitar buyer".
3. Lokasi default memakai `users.latitude` dan `users.longitude`.
4. Client boleh mengirim `latitude` dan `longitude` opsional untuk lokasi sementara bila buyer belum menyimpan lokasi atau sedang memilih lokasi lain.
5. Jika koordinat buyer dan query override sama-sama tidak tersedia, API tetap mengembalikan list tenant terpaginasikan tanpa jarak, dengan `distance_km = null`.
6. Jika koordinat tersedia, tenant dengan koordinat valid diurutkan dari yang terdekat.
7. Tenant tanpa koordinat tetap tampil di akhir list dengan `distance_km = null`.
8. Tambahkan filter opsional `radius_km` untuk membatasi hasil jika koordinat tersedia.
9. Pertahankan filter `housing_area_id` dan `product_category`.
10. Response tetap additive terhadap kontrak existing.

## Scope

1. Menetapkan kontrak buyer API untuk daftar UMKM sekitar.
2. Mengonsolidasikan logic jarak agar route aktif menghasilkan field jarak yang diuji.
3. Menambahkan validasi query lokasi dan radius.
4. Menambahkan sorting UMKM terdekat.
5. Menjaga pagination dan filter existing.
6. Memastikan response memakai allowlist field eksplisit.
7. Memperbarui test dan dokumentasi API.

## Out of Scope Awal

1. Implementasi kode aplikasi pada task dokumen ini.
2. Perubahan database/migration.
3. Integrasi maps/geocoding eksternal.
4. Reverse geocoding alamat buyer.
5. Estimasi ongkir berdasarkan jarak.
6. Estimasi waktu pengantaran.
7. Rekomendasi personalisasi berbasis histori transaksi.
8. Ranking berbayar/promosi tenant.
9. Halaman detail UMKM baru.
10. Perubahan flow checkout.

## Rancangan Endpoint

Rekomendasi endpoint:

```http
GET /api/tenants
```

Query opsional:

| Query | Tipe | Aturan | Keterangan |
| --- | --- | --- | --- |
| `limit` | integer | `1..100`, default `10` | Jumlah item per halaman |
| `page` | integer | minimal `1`, default `1` | Nomor halaman |
| `housing_area_id` | UUID | harus ada di `housing_areas.id` | Filter area perumahan |
| `product_category` | string | slug kategori produk valid | Filter tenant yang memiliki produk kategori tersebut |
| `latitude` | decimal | `-90..90`, wajib berpasangan dengan `longitude` | Override latitude buyer |
| `longitude` | decimal | `-180..180`, wajib berpasangan dengan `latitude` | Override longitude buyer |
| `radius_km` | decimal | `> 0`, maksimum sesuai konfigurasi | Batas jarak hasil jika koordinat tersedia |
| `open_now` | boolean | opsional | Jika `true`, hanya tenant yang sedang buka |

Catatan:

1. `latitude` dan `longitude` harus dikirim berpasangan.
2. `radius_km` hanya boleh efektif bila API memiliki koordinat sumber.
3. Jika `radius_km` dikirim tanpa koordinat profil buyer dan tanpa query koordinat, response harus `422` atau parameter diabaikan sesuai keputusan implementasi. Rekomendasi MVP: kembalikan `422` agar perilaku eksplisit.
4. Bila endpoint alias baru tetap dibutuhkan oleh client, alias harus memakai controller/use case yang sama agar tidak ada dua kontrak berbeda.

Alternatif alias jika diputuskan perlu:

```http
GET /api/buyer/nearby-umkm
```

Alias ini harus tetap berada dalam middleware `session.token` dan `role:buyer`.

## Rancangan Response

Response berhasil:

```json
{
  "message": "Daftar tenant berhasil diambil.",
  "data": [
    {
      "id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
      "name": "Toko Asep",
      "profile_picture_url": "https://example.com/toko-asep.png",
      "rating": 4.8,
      "category": "Sembako",
      "category_slug": "sembako",
      "category_icon_key": "groceries",
      "category_background_color": "#EAF8EF",
      "category_icon_color": "#24945A",
      "latitude": -6.2005,
      "longitude": 106.8165,
      "distance_km": 0.2,
      "distance_label": "0.2 km",
      "housing_areas": [
        {
          "id": "eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee",
          "name": "Komp Antapani Indah",
          "code": "AREA-001",
          "village_code": "3273141004"
        }
      ],
      "product_categories": ["Sembako", "Minuman"],
      "product_category_slugs": ["sembako", "minuman"],
      "product_count": 12,
      "is_open": true,
      "store_status": "Buka",
      "open_time": "07:00",
      "close_time": "21:00",
      "operating_hours_label": "07:00 - 21:00",
      "map_marker": {
        "title": "Toko Asep",
        "subtitle": "Sembako",
        "latitude": -6.2005,
        "longitude": 106.8165,
        "is_open": true
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
    "first": "http://127.0.0.1:8000/api/tenants?page=1",
    "last": "http://127.0.0.1:8000/api/tenants?page=1",
    "prev": null,
    "next": null
  }
}
```

Aturan field:

1. `distance_km` berupa decimal satu angka di belakang koma atau `null`.
2. `distance_label` berupa string display seperti `0.2 km` atau `null`.
3. `distance_km = null` jika koordinat sumber atau koordinat tenant tidak lengkap.
4. `map_marker` hanya memuat field yang dibutuhkan peta, bukan seluruh model tenant.
5. `product_count` menghitung produk yang relevan dengan filter buyer-facing.

## Rancangan Logic

Prioritas koordinat sumber:

1. Pakai query `latitude` dan `longitude` jika keduanya dikirim valid.
2. Jika query tidak dikirim, pakai `request->user()->latitude` dan `request->user()->longitude`.
3. Jika koordinat sumber tidak tersedia, jangan menghitung jarak.

Aturan sorting:

1. Jika koordinat tersedia:
   - tenant dengan `distance_km` tidak `null` tampil lebih dulu.
   - urutkan dari `distance_km` terkecil.
   - tie breaker memakai `name` ascending.
2. Jika koordinat tidak tersedia:
   - gunakan ordering existing yang stabil, misalnya terbaru atau nama sesuai keputusan produk.
3. Tenant tanpa koordinat tampil terakhir saat mode nearby aktif.

Aturan filter:

1. `housing_area_id` memfilter tenant melalui pivot housing area.
2. `product_category` memfilter tenant yang memiliki produk kategori tersebut.
3. `radius_km` memfilter tenant setelah jarak dihitung.
4. `open_now=true` memfilter berdasarkan `Tenant::isOpenAt()`.
5. Filter harus diterapkan sebelum pagination.

Catatan performa:

1. Untuk MVP dan dataset kecil, kalkulasi jarak di PHP masih dapat diterima.
2. Jika data tenant besar, pindahkan kalkulasi/filter radius ke query database dengan formula Haversine atau fitur geospatial yang kompatibel dengan PostgreSQL.
3. Hindari N+1 query dengan eager loading `housingAreas` dan produk/kategori yang diperlukan.
4. Jangan mengambil field produk penuh jika hanya butuh kategori dan count.

## Task Analisis

1. Putuskan apakah endpoint final tetap `GET /api/tenants` atau perlu alias `GET /api/buyer/nearby-umkm`.
2. Review perbedaan `GetBuyerTenantListController` dan `GetTenantListController`.
3. Tentukan controller/use case canonical untuk daftar UMKM buyer.
4. Tentukan apakah `category` legacy masih didukung atau diganti penuh oleh `product_category`.
5. Tentukan behavior tenant tanpa koordinat.
6. Tentukan maksimum `radius_km`, misalnya melalui `config('api.max_nearby_radius_km')`.
7. Tentukan apakah tenant tanpa produk aktif boleh tampil.
8. Tentukan apakah filter `open_now` masuk MVP atau ditunda.

## Task Backend/API

1. Pastikan route buyer memakai middleware `session.token` dan `role:buyer`.
2. Konsolidasikan logic jarak dari `GetTenantListController` ke controller/use case aktif.
3. Hapus duplikasi controller jika sudah tidak dipakai, atau jadikan satu controller sebagai wrapper yang jelas.
4. Tambahkan validasi query:
   - `limit`
   - `page`
   - `housing_area_id`
   - `product_category`
   - `latitude`
   - `longitude`
   - `radius_km`
   - `open_now` jika masuk scope
5. Pastikan `latitude` dan `longitude` wajib berpasangan.
6. Pastikan validasi `radius_km` tidak ambigu saat koordinat sumber tidak tersedia.
7. Hitung `distance_km` server-side memakai koordinat sumber dan koordinat tenant.
8. Format `distance_label` server-side.
9. Sorting tenant berdasarkan jarak dilakukan sebelum pagination.
10. Filter radius dilakukan sebelum pagination.
11. Tambahkan `map_marker` pada response jika belum ada di controller aktif.
12. Pastikan response memakai allowlist field.
13. Pastikan pagination link mempertahankan query string.
14. Pastikan role selain buyer mendapat `403`.
15. Pastikan unauthenticated request mendapat `401`.

## Task Testing

1. Test buyer authenticated bisa mengambil daftar UMKM sekitar.
2. Test response memiliki `data`, `meta`, dan `links`.
3. Test tenant terdekat tampil lebih dulu saat buyer memiliki koordinat.
4. Test query override `latitude` dan `longitude` mengubah basis sorting jarak.
5. Test tenant tanpa koordinat memiliki `distance_km = null` dan tampil terakhir.
6. Test `distance_label` sesuai format.
7. Test `radius_km` hanya mengembalikan tenant dalam radius.
8. Test `radius_km` invalid menghasilkan `422`.
9. Test `latitude` tanpa `longitude` menghasilkan `422`.
10. Test `longitude` tanpa `latitude` menghasilkan `422`.
11. Test `housing_area_id` tetap memfilter tenant.
12. Test `product_category` tetap memfilter tenant.
13. Test `open_now=true` jika masuk MVP.
14. Test pagination diterapkan setelah filter dan sorting.
15. Test request tanpa token mendapat `401`.
16. Test role seller/agent/finance mendapat `403`.
17. Test response tidak memuat field sensitif owner, agent, atau data internal yang tidak diperlukan buyer.

## Task Dokumentasi

1. Update `API_DOCUMENTATION.md` pada bagian `GET /api/tenants`.
2. Dokumentasikan bahwa endpoint ini adalah API UMKM sekitar buyer.
3. Tambahkan seluruh query parameter baru.
4. Tambahkan contoh response dengan `distance_km`, `distance_label`, dan `map_marker`.
5. Jelaskan fallback saat koordinat buyer tidak tersedia.
6. Jelaskan sorting dan behavior tenant tanpa koordinat.
7. Jika endpoint alias ditambahkan, dokumentasikan alias dan pastikan kontraknya sama.

## Acceptance Criteria

1. Buyer dapat mengambil daftar UMKM sekitar melalui endpoint yang disepakati.
2. Endpoint berada di boundary `session.token` dan `role:buyer`.
3. UMKM dengan jarak terdekat tampil lebih dulu saat koordinat tersedia.
4. Tenant tanpa koordinat tidak menyebabkan error dan tampil terakhir saat mode nearby aktif.
5. Filter `housing_area_id` dan `product_category` tetap bekerja.
6. Pagination menggunakan hasil setelah filter dan sorting.
7. Response memakai allowlist field dan tidak mengembalikan model mentah.
8. Unauthorized, forbidden role, dan validation error memiliki status sesuai standar.
9. Feature test mencakup happy path, invalid input, unauthorized, forbidden, filter, sorting, radius, dan edge case koordinat kosong.
10. `API_DOCUMENTATION.md` mencerminkan kontrak endpoint terbaru.

## Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Route aktif tidak memakai controller yang punya logic jarak | Response tidak sesuai test dan kebutuhan buyer | Konsolidasikan controller daftar tenant buyer sebelum menambah fitur |
| Kalkulasi jarak dilakukan setelah pagination | Tenant terdekat bisa tidak muncul di halaman pertama | Terapkan filter dan sorting sebelum pagination |
| Dataset tenant membesar | Endpoint lambat karena kalkulasi jarak di memory | Tambahkan index koordinat dan pindahkan kalkulasi radius/sorting ke database saat diperlukan |
| Tenant tanpa koordinat bercampur di atas list | Pengalaman nearby tidak akurat | Sort tenant dengan jarak `null` ke akhir |
| Query override lokasi disalahgunakan untuk data scraping | Beban endpoint meningkat | Batasi pagination, radius maksimum, dan pertimbangkan rate limit |
| Response membocorkan data internal tenant | Data owner/agent tidak perlu terlihat buyer | Mapping response memakai allowlist eksplisit |

## Catatan Implementasi

1. Pertimbangkan support/helper kecil untuk kalkulasi jarak agar `GetProductDetailController` dan daftar tenant tidak menduplikasi formula Haversine.
2. Jika `GetTenantListController` sudah tidak dipakai setelah konsolidasi, hapus atau refactor agar tidak membingungkan.
3. Pastikan perubahan field response bersifat additive terhadap `GET /api/tenants`.
4. Hindari migration baru kecuali requirement radius/coverage membutuhkan data tambahan.
