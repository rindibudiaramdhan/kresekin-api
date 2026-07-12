# Fitur Rating Pemesanan

Tanggal: 2026-07-12

## Ringkasan

Berdasarkan catatan meeting, perlu disiapkan fitur rating pemesanan agar customer dapat memberi penilaian setelah pesanan selesai.

Dokumen ini berisi task dan requirement awal sebelum implementasi. Belum ada perubahan kode aplikasi, database, test, atau dokumentasi API.

## Acuan Dokumen

Task ini mengikuti aturan pada:

1. `docs/requirements/01-architecture-nfr.md`
2. `docs/requirements/13-engineering-standards.md`
3. `docs/requirements/11-integrations.md`
4. `docs/adr/002-modular-monolith-laravel.md`

Aturan penting yang perlu dijaga:

1. Endpoint baru harus berada di prefix `/api`.
2. Response sukses minimal memiliki `message`, dan payload dikirim melalui `data`.
3. Endpoint authenticated wajib memakai middleware `session.token`.
4. Endpoint role-specific wajib memakai middleware role yang sesuai.
5. Ownership resource wajib dicek server-side.
6. Jangan mengembalikan model mentah untuk API public.
7. Setiap perubahan behavior harus memiliki regression test.
8. Controller hanya menjadi orchestration layer; logic yang lebih panjang ditempatkan di FormRequest, model, service, atau support class.

## Tujuan

Menyediakan mekanisme agar customer dapat memberi rating terhadap pesanan setelah pesanan selesai, sehingga sistem memiliki data kualitas layanan untuk evaluasi, tampilan rating, atau laporan internal.

## Keputusan yang Perlu Dikonfirmasi

1. Objek yang dinilai:
   - Pesanan secara keseluruhan.
   - Merchant.
   - Driver.
   - Produk.
   - Layanan.
2. Rating bisa diberikan hanya setelah status pesanan `completed`.
3. Satu pesanan hanya boleh memiliki satu rating dari customer.
4. Komentar rating bersifat opsional atau wajib.
5. Rating bisa diedit atau tidak setelah submit.
6. Rating ditampilkan publik atau hanya untuk internal/admin.
7. Perlu periode edit rating atau tidak, misalnya maksimal 24 jam atau 7 hari.

## Rekomendasi MVP

1. Rating hanya bisa diberikan setelah pesanan selesai.
2. Satu pesanan hanya boleh dirating satu kali oleh customer pemilik pesanan.
3. Skala rating memakai angka `1-5`.
4. Komentar dibuat opsional.
5. Rating tidak bisa diberikan untuk pesanan yang dibatalkan.
6. Average rating ditampilkan hanya jika jumlah rating sudah memenuhi batas minimum, misalnya 3 rating.
7. Jika terjadi komplain/dispute, rating tetap disimpan tetapi dapat diberi status review internal pada pengembangan berikutnya.

## Scope

1. Menambahkan rancangan API submit rating.
2. Menambahkan rancangan API melihat rating pesanan.
3. Menambahkan rancangan penyimpanan rating.
4. Menambahkan validasi role, ownership, status pesanan, dan nilai rating.
5. Menyiapkan test untuk alur utama dan edge case bisnis.

## Out of Scope Awal

1. Implementasi kode aplikasi.
2. Perubahan database/migration.
3. Integrasi frontend/mobile.
4. Dashboard analitik rating.
5. Moderasi komentar rating.
6. Sistem dispute/komplain.
7. Rating multi-aspek seperti kualitas produk, kecepatan, dan pelayanan.

## Task Analisis

1. Tentukan objek rating final: order, merchant, driver, produk, layanan, atau kombinasi.
2. Review status pesanan yang sudah ada dan tentukan status yang memenuhi syarat rating.
3. Tentukan aturan rating ulang dan edit rating.
4. Tentukan kebutuhan komentar, lampiran, atau kategori penilaian tambahan.
5. Tentukan apakah data rating dibutuhkan oleh customer app, seller app, dashboard admin, atau semuanya.
6. Tentukan format response rating yang dibutuhkan frontend/mobile.
7. Tentukan apakah aksi submit rating perlu audit event.

## Task Database

1. Desain tabel `order_ratings`.
2. Tambahkan relasi ke `orders` dan `users`.
3. Tambahkan relasi ke entitas yang dinilai.
4. Pertimbangkan penggunaan kolom polymorphic jika objek rating fleksibel:
   - `rateable_type`
   - `rateable_id`
5. Siapkan field minimum:
   - `id`
   - `order_id`
   - `user_id`
   - `rating`
   - `comment`
   - `created_at`
   - `updated_at`
6. Tambahkan unique constraint agar satu order hanya punya satu rating dari customer.
7. Tambahkan index untuk query average rating per merchant/driver jika dibutuhkan.

## Task Backend/API

1. Buat route API dengan prefix `/api`.
2. Pastikan endpoint memakai middleware `session.token`.
3. Pastikan endpoint submit rating memakai role buyer/customer yang sesuai dengan pola project.
4. Buat FormRequest untuk validasi input rating.
5. Buat endpoint submit rating.
6. Buat endpoint melihat rating pesanan.
7. Buat endpoint list rating per entitas yang dinilai jika dibutuhkan.
8. Validasi `rating` wajib berupa angka antara `1-5`.
9. Validasi user hanya bisa memberi rating untuk pesanan miliknya.
10. Validasi pesanan sudah selesai sebelum rating dibuat.
11. Validasi pesanan belum pernah dirating oleh user yang sama.
12. Hitung average rating dan total rating pada endpoint agregasi.
13. Jangan mengembalikan model mentah pada response API.
14. Pastikan response error jelas untuk kasus:
   - Pesanan belum selesai.
   - Pesanan bukan milik user.
   - Pesanan sudah pernah dirating.
   - Nilai rating tidak valid.

## Rekomendasi Endpoint

```http
POST /api/orders/{order}/rating
GET /api/orders/{order}/rating
GET /api/merchants/{merchant}/ratings
```

Endpoint agregasi dapat disesuaikan dengan objek rating final. Jika objek yang dinilai bukan merchant, endpoint perlu mengikuti domain tersebut.

## Rekomendasi Response

Submit rating berhasil:

```json
{
  "message": "Rating pesanan berhasil disimpan.",
  "data": {
    "id": 1,
    "order_id": 123,
    "rating": 5,
    "comment": "Pesanan sesuai dan cepat.",
    "created_at": "2026-07-12T10:30:00+07:00"
  }
}
```

Validasi gagal harus mengikuti standar error `422` dengan struktur `message` dan `errors`.

## Acceptance Criteria

1. Customer bisa submit rating untuk pesanan miliknya yang sudah selesai.
2. Customer tidak bisa submit rating untuk pesanan yang belum selesai.
3. Customer tidak bisa submit rating untuk pesanan milik user lain.
4. Customer tidak bisa submit rating lebih dari satu kali untuk pesanan yang sama.
5. Rating hanya menerima nilai `1-5`.
6. Komentar rating tersimpan jika dikirim.
7. Rating pesanan dapat diambil kembali melalui API.
8. Average rating dan total rating dapat dihitung untuk entitas yang dinilai jika fitur agregasi masuk scope.
9. Response API mengikuti standar `message` dan `data`.
10. Endpoint authenticated memakai `session.token`.
11. Endpoint role-specific memakai middleware role yang sesuai.

## Task Testing

1. Test submit rating berhasil.
2. Test gagal submit rating jika unauthenticated.
3. Test gagal submit rating jika role tidak sesuai.
4. Test gagal submit rating jika pesanan belum selesai.
5. Test gagal submit rating jika pesanan milik user lain.
6. Test gagal submit rating lebih dari sekali.
7. Test validasi rating di bawah `1`.
8. Test validasi rating di atas `5`.
9. Test komentar opsional.
10. Test get rating pesanan.
11. Test not found untuk pesanan di luar ownership.
12. Test average rating jika endpoint agregasi dibuat.

## Verification Checklist

1. Jalankan Laravel Pint.
2. Jalankan test feature terkait rating.
3. Jalankan seluruh test bila perubahan menyentuh model/order flow bersama.
4. Pastikan dokumentasi API diperbarui jika endpoint rating sudah diimplementasikan.
5. Pastikan tidak ada credential, token, atau data sensitif masuk log/response.

