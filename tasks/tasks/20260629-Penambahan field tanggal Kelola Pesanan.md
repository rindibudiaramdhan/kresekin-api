# Penambahan Field Tanggal Kelola Pesanan pada Counts Dashboard Seller

Tanggal: 2026-06-29

## Ringkasan

Endpoint `GET /api/seller/dashboard/orders-today/counts` perlu menyediakan field tanggal untuk mendukung label pada UI Kelola Pesanan, seperti contoh:

```text
Hari ini - 05 April 2026
```

Dokumen ini adalah analisis dampak dan requirement enhancement sebelum implementasi. Belum ada perubahan kode aplikasi, test, atau dokumentasi API.

## Endpoint Terdampak

```http
GET /api/seller/dashboard/orders-today/counts
```

Controller:

```php
App\Http\Controllers\Api\GetSellerDashboardController::todayOrderCounts()
```

Helper terkait:

```php
GetSellerDashboardController::ordersToday()
GetSellerDashboardController::orderStatusCounts()
```

## Kondisi Saat Ini

Response endpoint saat ini hanya mengembalikan `message` dan `data` berisi count per status:

```json
{
  "message": "Count tab pesanan seller hari ini berhasil diambil.",
  "data": {
    "new": {
      "status_code": "accepted_by_store",
      "label": "Baru",
      "count": 1
    },
    "processing": {
      "status_code": "processing",
      "label": "Diproses",
      "count": 0
    },
    "on_the_way": {
      "status_code": "on_the_way",
      "label": "Dikirim",
      "count": 1
    },
    "ready_for_pickup": {
      "status_code": "ready_for_pickup",
      "label": "Siap Diambil",
      "count": 0
    },
    "completed": {
      "status_code": "completed",
      "label": "Selesai",
      "count": 1
    }
  }
}
```

Catatan penting dari kode saat ini:

1. Endpoint bernama `orders-today/counts` dan message menyebut "hari ini".
2. `todayRevenue()` dan `todayTransactions()` memakai tanggal `CarbonImmutable::now('Asia/Jakarta')`.
3. Helper `ordersToday()` saat ini tidak memfilter `transaction_at` ke tanggal hari ini karena baris filter tanggal sedang dikomentari.
4. Karena filter tanggal tidak aktif, count yang dikembalikan dapat mencakup order lintas tanggal walaupun UI menampilkan label "Hari ini".

## Tujuan

1. Menambahkan metadata tanggal yang dapat dipakai mobile app untuk label Kelola Pesanan.
2. Menyeragamkan acuan tanggal dengan timezone bisnis aplikasi, yaitu `Asia/Jakarta`.
3. Menjaga backward compatibility response count yang sudah digunakan client.
4. Membuat kontrak response cukup eksplisit agar client tidak perlu membentuk label tanggal sendiri dari waktu device.

## Analisis Dampak

### Dampak API Contract

Perubahan ini bersifat additive jika field tanggal ditambahkan di luar `data`.

Rekomendasi kontrak:

```json
{
  "message": "Count tab pesanan seller hari ini berhasil diambil.",
  "data": {
    "new": {
      "status_code": "accepted_by_store",
      "label": "Baru",
      "count": 1
    },
    "processing": {
      "status_code": "processing",
      "label": "Diproses",
      "count": 0
    },
    "on_the_way": {
      "status_code": "on_the_way",
      "label": "Dikirim",
      "count": 1
    },
    "ready_for_pickup": {
      "status_code": "ready_for_pickup",
      "label": "Siap Diambil",
      "count": 0
    },
    "completed": {
      "status_code": "completed",
      "label": "Selesai",
      "count": 1
    }
  },
  "meta": {
    "period": "today",
    "date": "2026-04-05",
    "date_label": "05 April 2026",
    "display_label": "Hari ini - 05 April 2026",
    "timezone": "Asia/Jakarta"
  }
}
```

Alasan field tanggal direkomendasikan di `meta`, bukan di `data`:

1. `data` saat ini adalah map status order.
2. Client lama bisa saja melakukan iterasi semua key di `data` untuk render tab atau card status.
3. Menambahkan key non-status di `data`, seperti `date_label`, berisiko ikut dianggap sebagai status baru.
4. Dokumentasi API project sudah memakai `meta` untuk metadata response, sehingga pola ini konsisten.

### Dampak Frontend Mobile

Mobile app dapat langsung memakai:

```text
meta.display_label
```

untuk menampilkan label di bawah title `Kelola Pesanan`.

Fallback yang disarankan untuk client:

1. Pakai `meta.display_label` bila tersedia.
2. Jika belum tersedia, bentuk sementara dari tanggal device atau sembunyikan label tanggal agar kompatibel dengan API lama.

### Dampak Query dan Data

Ada dua opsi terkait helper `ordersToday()`:

1. Scope kecil: hanya tambah metadata tanggal, tidak mengubah query count.
2. Scope benar secara domain: tambah metadata tanggal dan aktifkan filter `whereDate('transaction_at', $today->toDateString())`.

Rekomendasi: gunakan opsi 2 dalam implementasi endpoint ini, karena label "Hari ini" harus merepresentasikan data yang dihitung. Jika filter tidak diaktifkan, UI akan menampilkan tanggal hari ini tetapi angka count bisa berasal dari semua tanggal.

Risiko opsi 2:

1. Angka count di UI dapat turun setelah deploy jika sebelumnya endpoint tanpa sengaja menghitung order historis.
2. Test existing yang bergantung pada data lintas hari perlu disesuaikan agar eksplisit.
3. Perlu dipastikan endpoint `GET /api/seller/dashboard/orders/new-preview` dan `GET /api/seller/dashboard` tidak ikut berubah tanpa sengaja jika masih memakai helper yang sama.

Mitigasi:

1. Jadikan perubahan filter tanggal sebagai keputusan eksplisit di acceptance criteria.
2. Tambahkan test order kemarin tidak dihitung di counts hari ini.
3. Jika product owner belum ingin mengubah perilaku count, implementasi pertama cukup menambah `meta` dan buat tiket terpisah untuk koreksi filter tanggal.

### Dampak Timezone

Tanggal harus dihitung dari server dengan timezone `Asia/Jakarta`, bukan timezone device client.

Ketentuan:

1. `date` memakai format ISO date `YYYY-MM-DD`.
2. `date_label` memakai format Indonesia `dd Month yyyy`, contoh `05 April 2026`.
3. `display_label` memakai format siap tampil `Hari ini - 05 April 2026`.
4. `timezone` dikirim agar client mengetahui basis tanggal.

### Dampak Dokumentasi

Perlu memperbarui bagian `GET /api/seller/dashboard/orders-today/counts` di `API_DOCUMENTATION.md` dengan object `meta` baru.

### Dampak Test

Feature test yang perlu ditambahkan atau diperbarui:

1. `GET /api/seller/dashboard/orders-today/counts` mengembalikan:
   - `meta.period = today`
   - `meta.date = YYYY-MM-DD`
   - `meta.date_label`
   - `meta.display_label`
   - `meta.timezone = Asia/Jakarta`
2. Tanggal memakai `Carbon::setTestNow()` agar deterministic.
3. Jika filter today diaktifkan, order dengan `transaction_at` kemarin tidak masuk count.
4. Struktur `data.new`, `data.processing`, `data.on_the_way`, `data.ready_for_pickup`, dan `data.completed` tetap sama.

## Requirement Enhancement

### User Story

Sebagai seller, saya ingin melihat tanggal acuan pada area Kelola Pesanan agar saya tahu count pesanan yang ditampilkan berlaku untuk hari apa.

### Functional Requirements

1. Endpoint `GET /api/seller/dashboard/orders-today/counts` harus mengembalikan metadata tanggal pada response.
2. Metadata tanggal harus berada pada top-level `meta`.
3. Field `data` tetap berisi count status dengan struktur yang sama seperti saat ini.
4. `meta.period` bernilai `today`.
5. `meta.date` berisi tanggal server dalam timezone `Asia/Jakarta` dengan format `YYYY-MM-DD`.
6. `meta.date_label` berisi tanggal siap tampil tanpa prefix, contoh `05 April 2026`.
7. `meta.display_label` berisi label siap tampil, contoh `Hari ini - 05 April 2026`.
8. `meta.timezone` bernilai `Asia/Jakarta`.
9. Endpoint harus tetap hanya bisa diakses seller terautentikasi sesuai middleware yang sudah ada.
10. Count status tetap scoped ke toko milik seller yang login.
11. Jika disetujui sebagai bagian scope implementasi, count harus difilter ke `transaction_at` pada tanggal `meta.date`.

### Non-Functional Requirements

1. Perubahan harus backward compatible untuk consumer yang membaca `data`.
2. Tidak boleh mengubah nama key status yang sudah ada.
3. Tidak boleh menghapus field existing.
4. Tidak boleh menambah query berat yang tidak diperlukan.
5. Format tanggal harus deterministic pada test.

### Acceptance Criteria

1. Ketika seller memanggil `GET /api/seller/dashboard/orders-today/counts`, response tetap `200 OK`.
2. Response memiliki top-level `meta`.
3. `meta.period` bernilai `today`.
4. `meta.date` sesuai tanggal `Asia/Jakarta`.
5. `meta.date_label` tampil seperti `05 April 2026`.
6. `meta.display_label` tampil seperti `Hari ini - 05 April 2026`.
7. `meta.timezone` bernilai `Asia/Jakarta`.
8. Object `data` tetap memiliki key:
   - `new`
   - `processing`
   - `on_the_way`
   - `ready_for_pickup`
   - `completed`
9. Setiap status tetap memiliki `status_code`, `label`, dan `count`.
10. Client lama yang hanya membaca `data.*.count` tetap berjalan.
11. Jika koreksi filter tanggal masuk scope, order kemarin tidak dihitung pada count hari ini.

## Rekomendasi Implementasi

1. Di `todayOrderCounts()`, buat `$today = CarbonImmutable::now('Asia/Jakarta')`.
2. Tambahkan helper kecil untuk metadata tanggal, misalnya `todayPeriodMeta(CarbonImmutable $today): array`.
3. Return response dengan `data` existing dan `meta` baru.
4. Ubah `ordersToday()` agar menerima tanggal:

```php
private function ordersToday(string $sellerId, ?CarbonImmutable $date = null): Collection
```

5. Jika filter tanggal disetujui, aktifkan:

```php
->whereDate('transaction_at', $date->toDateString())
```

6. Pastikan pemanggil lain, seperti dashboard utama dan `newOrderPreview()`, tetap memakai tanggal yang sama bila memang semantik endpoint-nya "today".
7. Update `API_DOCUMENTATION.md`.
8. Update `tests/Feature/SellerApiTest.php`.

## Out of Scope

1. Perubahan UI mobile.
2. Perubahan endpoint list order seller.
3. Penambahan filter tanggal custom dari request.
4. Perubahan status order.
5. Perubahan database schema.
6. Perubahan timezone global aplikasi.

## Open Questions

1. Apakah implementasi langsung harus mengaktifkan kembali filter `transaction_at` hari ini pada `ordersToday()`?
2. Apakah label harus selalu `Hari ini - ...`, atau mobile ingin menyusun prefix sendiri dari `date_label`?
3. Apakah endpoint dashboard utama `GET /api/seller/dashboard` juga perlu metadata tanggal serupa untuk `orders_today.status_counts`?
