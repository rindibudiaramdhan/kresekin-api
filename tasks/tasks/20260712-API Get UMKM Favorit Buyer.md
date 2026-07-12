# API Get UMKM Favorit Buyer

Tanggal: 2026-07-12

## Ringkasan

Buyer membutuhkan API untuk mengambil daftar UMKM/tenant yang sudah ditandai sebagai favorit. Endpoint ini dipakai oleh client buyer untuk menampilkan halaman favorit tanpa mengambil seluruh katalog tenant lalu memfilter di sisi client.

Dokumen ini berisi task dan requirement awal sebelum implementasi. Belum ada perubahan kode aplikasi, database, test, atau dokumentasi API.

## Acuan Dokumen

Task ini mengikuti aturan pada:

1. `docs/requirements/00-vision-scope.md`
2. `docs/requirements/02-roles-permissions.md`
3. `docs/requirements/03-service-catalog.md`
4. `docs/requirements/13-engineering-standards.md`
5. `docs/adr/002-modular-monolith-laravel.md`
6. `docs/adr/004-role-based-access-with-ownership-scoping.md`

Aturan penting yang perlu dijaga:

1. Endpoint authenticated wajib memakai middleware `session.token`.
2. Endpoint buyer wajib memakai middleware `role:buyer`.
3. Buyer hanya boleh membaca daftar favorit miliknya sendiri.
4. Query list wajib menerapkan scope user sebelum pagination.
5. Response API harus memakai allowlist field, bukan model mentah.
6. Response sukses minimal memiliki `message`, dan payload list dikirim melalui `data`, `meta`, dan `links`.
7. Resource di luar scope user tidak boleh muncul di response.
8. Setiap perubahan behavior harus memiliki regression test.

## Kondisi Saat Ini

1. Buyer sudah memiliki endpoint katalog UMKM melalui `GET /api/tenants`.
2. Endpoint katalog buyer berada di group middleware `session.token` dan `role:buyer`.
3. Tenant/UMKM disimpan pada tabel `tenants`.
4. Tenant memiliki field dan response buyer-facing seperti:
   - `id`
   - `name`
   - `profile_picture_url`
   - `rating`
   - `category`
   - `housing_areas`
   - `product_categories`
   - `product_count`
   - `is_open`
   - `store_status`
   - `open_time`
   - `close_time`
   - `operating_hours_label`
5. Belum terlihat tabel atau endpoint khusus untuk favorit UMKM buyer.

## Tujuan

Menyediakan API agar buyer dapat mengambil daftar UMKM favorit miliknya secara aman, terpaginated, dan konsisten dengan response katalog tenant buyer-facing yang sudah ada.

## Keputusan yang Perlu Dikonfirmasi

1. Apakah fitur favorit hanya untuk tenant/UMKM atau juga produk.
2. Apakah API ini hanya `GET` daftar favorit, atau sekalian mencakup tambah dan hapus favorit.
3. Apakah tenant nonaktif/tutup tetap muncul di daftar favorit.
4. Apakah daftar favorit perlu mendukung filter `housing_area_id` dan `product_category` seperti katalog tenant.
5. Apakah sorting default berdasarkan waktu difavoritkan terbaru atau mengikuti sorting katalog.
6. Apakah response katalog `GET /api/tenants` perlu menambahkan field `is_favorited`.
7. Apakah perlu batas maksimum jumlah favorit per buyer.

## Rekomendasi MVP

1. Favorit berlaku untuk tenant/UMKM.
2. Simpan favorit pada tabel pivot `buyer_favorite_tenants`.
3. Satu buyer hanya bisa memfavoritkan tenant yang sama satu kali.
4. Endpoint `GET` hanya mengembalikan favorit milik buyer yang sedang login.
5. Response item mengikuti allowlist field buyer-facing dari `GET /api/tenants`, ditambah metadata favorit:
   - `is_favorited`
   - `favorited_at`
6. Sorting default berdasarkan `favorited_at` terbaru.
7. Endpoint mendukung pagination `page` dan `limit`.
8. Jika tenant dihapus atau tidak ditemukan, record favorit tidak boleh membuat endpoint gagal.

## Scope

1. Menambahkan rancangan penyimpanan favorit UMKM buyer jika belum tersedia.
2. Menambahkan rancangan API `GET` daftar UMKM favorit buyer.
3. Menambahkan validasi query pagination dan filter bila dipilih.
4. Menjaga ownership buyer melalui `user_id`.
5. Menyiapkan test untuk authorization, ownership, pagination, empty state, dan format response.

## Out of Scope Awal

1. Implementasi kode aplikasi.
2. Perubahan database/migration.
3. Integrasi frontend/mobile.
4. Favorit produk.
5. Rekomendasi UMKM berbasis favorit.
6. Push notification terkait UMKM favorit.
7. Analitik jumlah favorit untuk seller atau finance.

## Rancangan Data

Jika belum ada tabel favorit, buat tabel `buyer_favorite_tenants`.

Field yang disarankan:

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | uuid | Primary key |
| `user_id` | uuid | Buyer pemilik favorit |
| `tenant_id` | uuid | Tenant/UMKM yang difavoritkan |
| `created_at` | timestamp | Waktu buyer menambahkan favorit |
| `updated_at` | timestamp | Waktu update record bila dibutuhkan |

Constraint dan index:

1. Foreign key `user_id` ke `users.id`.
2. Foreign key `tenant_id` ke `tenants.id`.
3. Unique constraint pada kombinasi `user_id` dan `tenant_id`.
4. Index pada `user_id, created_at` untuk list favorit buyer.
5. Index pada `tenant_id` jika nanti dibutuhkan agregasi jumlah favorit per tenant.

Catatan:

1. Nama model dapat memakai `BuyerFavoriteTenant`.
2. Relasi dapat ditambahkan pada `User` dan `Tenant` bila membantu query dan test.
3. Jika tabel menggunakan pivot tanpa model, tetap pastikan response tidak mengambil field mentah dari pivot tanpa allowlist.

## Task Backend/API

1. Buat route API buyer dengan prefix `/api`.
2. Pastikan endpoint memakai middleware `session.token` dan `role:buyer`.
3. Buat controller khusus untuk list favorit buyer.
4. Validasi query:
   - `limit` nullable integer minimum `1` maksimum `100`.
   - `page` nullable integer minimum `1`.
   - `housing_area_id` nullable uuid exists jika filter ini disetujui.
   - `product_category` nullable slug exists jika filter ini disetujui.
5. Query favorit harus dimulai dari `user_id = current user`.
6. Eager load relasi tenant yang dibutuhkan untuk response:
   - `housingAreas`
   - `products`
7. Terapkan pagination setelah scope buyer dan filter.
8. Mapping response harus memakai allowlist field, bukan model mentah.
9. Tambahkan `is_favorited: true` untuk setiap item.
10. Tambahkan `favorited_at` dari waktu record favorit dibuat.
11. Pastikan tenant milik user lain tetap bisa tampil jika buyer memang memfavoritkan tenant tersebut; ownership yang dijaga adalah ownership record favorit, bukan ownership tenant.
12. Pastikan record favorit milik buyer lain tidak pernah muncul.
13. Pastikan empty state mengembalikan `data: []` dengan `meta.total = 0`.

## Rekomendasi Endpoint

```http
GET /api/buyer/favorite-tenants
```

Alternatif jika ingin mengikuti naming katalog existing tanpa prefix tambahan:

```http
GET /api/tenants/favorites
```

Rekomendasi MVP: gunakan `GET /api/buyer/favorite-tenants` agar maknanya eksplisit dan tidak ambigu dengan detail tenant.

Query opsional:

```http
GET /api/buyer/favorite-tenants?page=1&limit=10
```

Jika filter disetujui:

```http
GET /api/buyer/favorite-tenants?page=1&limit=10&housing_area_id=aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa&product_category=sayur
```

## Rekomendasi Response

Response berhasil:

```json
{
  "message": "Daftar UMKM favorit berhasil diambil.",
  "data": [
    {
      "id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
      "name": "Warung Sayur Segar",
      "profile_picture_url": "https://example.test/storage/tenants/warung-sayur.jpg",
      "rating": 4.8,
      "category": "Sayur",
      "category_slug": "sayur",
      "category_icon_key": "vegetables",
      "category_background_color": "#E7F6EB",
      "category_icon_color": "#67B97A",
      "housing_areas": [
        {
          "id": "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
          "name": "Perumahan Melati",
          "code": "MELATI",
          "village_code": "3171010001"
        }
      ],
      "product_categories": ["Sayur", "Buah"],
      "product_category_slugs": ["sayur", "buah"],
      "product_count": 12,
      "is_open": true,
      "store_status": "Buka",
      "open_time": "07:00",
      "close_time": "17:00",
      "operating_hours_label": "Buka 07:00 sd 17:00",
      "is_favorited": true,
      "favorited_at": "2026-07-12T10:30:00+07:00"
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
    "first": "https://api.example.test/api/buyer/favorite-tenants?page=1",
    "last": "https://api.example.test/api/buyer/favorite-tenants?page=1",
    "prev": null,
    "next": null
  }
}
```

Empty state:

```json
{
  "message": "Daftar UMKM favorit berhasil diambil.",
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
    "first": "https://api.example.test/api/buyer/favorite-tenants?page=1",
    "last": "https://api.example.test/api/buyer/favorite-tenants?page=1",
    "prev": null,
    "next": null
  }
}
```

Validasi gagal harus mengikuti standar error `422` dengan struktur `message` dan `errors`.

## Acceptance Criteria

1. Buyer authenticated dapat mengambil daftar UMKM favorit miliknya.
2. User tanpa token mendapat `401`.
3. Role selain buyer mendapat `403`.
4. Daftar favorit hanya berisi record dengan `user_id` buyer yang sedang login.
5. Favorit buyer lain tidak muncul di response.
6. Response memakai `message`, `data`, `meta`, dan `links`.
7. Item response memakai allowlist field buyer-facing dan tidak mengembalikan model mentah.
8. Setiap item memiliki `is_favorited: true`.
9. Setiap item memiliki `favorited_at` dalam format ISO 8601.
10. Pagination `page` dan `limit` berjalan benar.
11. Empty state mengembalikan `data: []` dan `meta.total = 0`.
12. Query invalid mengembalikan `422`.
13. Sorting default menampilkan favorit terbaru lebih dulu.

## Task Testing

1. Test buyer bisa mengambil daftar UMKM favorit.
2. Test unauthenticated mendapat `401`.
3. Test role seller/agent/finance mendapat `403`.
4. Test response tidak berisi favorit milik buyer lain.
5. Test response empty state untuk buyer tanpa favorit.
6. Test pagination `page` dan `limit`.
7. Test validasi `limit` kurang dari `1`.
8. Test validasi `limit` lebih dari `100`.
9. Test validasi `page` kurang dari `1`.
10. Test sorting berdasarkan `favorited_at` terbaru.
11. Test response item berisi `is_favorited` dan `favorited_at`.
12. Test response tidak mengandung field sensitif user, owner seller, token, atau data mentah pivot yang tidak dibutuhkan.
13. Jika filter `housing_area_id` dibuat, test hanya tenant favorit di area tersebut yang muncul.
14. Jika filter `product_category` dibuat, test hanya tenant favorit dengan kategori produk tersebut yang muncul.

## Dependensi Fitur

Jika fitur tambah dan hapus favorit belum tersedia, buat task lanjutan untuk:

1. `POST /api/buyer/favorite-tenants/{tenant}` untuk menambahkan favorit.
2. `DELETE /api/buyer/favorite-tenants/{tenant}` untuk menghapus favorit.
3. Field `is_favorited` pada `GET /api/tenants` dan endpoint detail produk/tenant yang membutuhkan status favorit.
4. Unique constraint agar aksi tambah favorit idempotent atau mengembalikan response yang jelas saat sudah favorit.

## Verification Checklist

1. Jalankan Laravel Pint.
2. Jalankan feature test terkait favorite tenants.
3. Jalankan seluruh test bila perubahan menyentuh query katalog tenant bersama.
4. Pastikan dokumentasi API diperbarui jika endpoint sudah diimplementasikan.
5. Pastikan tidak ada credential, token, OTP, atau data sensitif masuk log/response.
