# Notifikasi Firebase

Tanggal: 2026-07-12

## Ringkasan

Berdasarkan catatan meeting, perlu disiapkan fitur notifikasi via Firebase Cloud Messaging untuk mengirim push notification pada event penting aplikasi.

Dokumen ini berisi task dan requirement awal sebelum implementasi. Belum ada perubahan kode aplikasi, database, test, atau dokumentasi API.

## Acuan Dokumen

Task ini mengikuti aturan pada:

1. `docs/requirements/01-architecture-nfr.md`
2. `docs/requirements/11-integrations.md`
3. `docs/requirements/13-engineering-standards.md`
4. `docs/adr/002-modular-monolith-laravel.md`

Aturan penting yang perlu dijaga:

1. Dependency eksternal harus dibungkus service boundary.
2. Provider notifikasi tidak boleh dipanggil langsung dari controller.
3. Request user-facing tidak boleh menunggu pekerjaan eksternal yang lambat bila bisa diproses async.
4. Pengiriman notifikasi sebaiknya menggunakan queue/job.
5. Job harus idempotent atau memiliki deduplication key.
6. Failure provider harus dicatat dengan aman dan tidak memuat secret.
7. Credential, token, OTP, dokumen sensitif, atau secret tidak boleh disimpan di repository, log, response, atau dokumentasi contoh.
8. Device token untuk notifikasi harus terikat ke user dan dapat diperbarui tanpa membuat duplikasi tidak terkendali.
9. Queue worker production harus kompatibel dengan Laravel Cloud.

## Tujuan

Mengirim push notification ke user melalui Firebase Cloud Messaging untuk event penting di aplikasi, terutama perubahan status pesanan dan event transaksi yang perlu segera diketahui user.

## Keputusan yang Perlu Dikonfirmasi

1. Platform target:
   - Android.
   - iOS.
   - Web.
2. Event yang wajib mengirim notifikasi.
3. Role penerima notifikasi untuk setiap event:
   - Customer/buyer.
   - Seller/merchant.
   - Driver.
   - Admin.
   - Finance.
   - Agent.
4. Format dan kebutuhan tampilan inbox/riwayat notifikasi di aplikasi.
5. Format payload yang dibutuhkan frontend/mobile.
6. Strategi broadcast/promo masuk scope awal atau ditunda.
7. Queue connection dan worker production yang akan dipakai di Laravel Cloud.

## Rekomendasi MVP

1. Gunakan Firebase Cloud Messaging.
2. Simpan device token per user.
3. Satu user dapat memiliki banyak device token.
4. Token bisa dibuat, diperbarui, dan dihapus saat logout.
5. Pengiriman notifikasi dilakukan melalui service khusus, bukan langsung dari controller.
6. Pengiriman notifikasi menggunakan queue/job agar request utama tidak lambat.
7. Daftar notifikasi disimpan di database sebagai inbox/riwayat aplikasi.
8. Setiap notifikasi memiliki penanda status belum dibaca atau sudah dibaca.
9. Token invalid dari Firebase perlu dinonaktifkan atau dihapus.
10. Event promo atau broadcast ditunda dari MVP.

## Scope

1. Menambahkan rancangan penyimpanan device token.
2. Menambahkan rancangan API register dan delete device token.
3. Menambahkan rancangan Firebase notification service.
4. Menambahkan rancangan job pengiriman notifikasi.
5. Menambahkan rancangan trigger notifikasi dari event order/payment yang disepakati.
6. Menambahkan rancangan penyimpanan daftar notifikasi di database.
7. Menambahkan rancangan penanda notifikasi belum dibaca/sudah dibaca.
8. Menyiapkan test dengan mock/fake Firebase client.

## Out of Scope Awal

1. Implementasi kode aplikasi.
2. Perubahan database/migration.
3. Integrasi frontend/mobile.
4. Broadcast promo massal.
5. Segmentasi user untuk campaign notifikasi.
6. Dashboard manajemen template notifikasi.
7. Topic subscription Firebase, kecuali ada kebutuhan eksplisit dari mobile/frontend.

## Event Awal yang Direkomendasikan

1. Pesanan berhasil dibuat.
2. Status pesanan berubah.
3. Pesanan selesai.
4. Pembayaran berhasil.
5. Pembayaran gagal.
6. Pesanan dibatalkan.

Event candidate dari `docs/requirements/11-integrations.md` yang dapat dipertimbangkan setelah MVP:

1. Agent registration submitted.
2. Agent review approved/rejected.
3. Withdrawal approved/rejected/paid.
4. Buyer payment confirmed.
5. Seller disbursement completed.

## Task Analisis

1. Tentukan platform target notifikasi.
2. Tentukan event final yang mengirim notifikasi.
3. Tentukan penerima untuk setiap event.
4. Tentukan kebutuhan tampilan in-app inbox/riwayat notifikasi.
5. Tentukan payload data untuk kebutuhan deep link.
6. Tentukan apakah payload harus memakai template.
7. Tentukan retry policy, failure handling, dan deduplication key untuk job.
8. Tentukan resource/env var yang dibutuhkan untuk Laravel Cloud.

## Task Konfigurasi

1. Siapkan Firebase project.
2. Ambil Firebase service account credential.
3. Simpan credential di environment/config server.
4. Pastikan credential tidak masuk git.
5. Pilih package/library Firebase untuk backend.
6. Tentukan konfigurasi direct token messaging.
7. Dokumentasikan env var yang dibutuhkan.
8. Dokumentasikan kebutuhan queue worker production.

## Task Database

1. Desain tabel `device_tokens`.
2. Relasikan token ke user.
3. Dukung banyak device untuk satu user.
4. Siapkan field minimum:
   - `id`
   - `user_id`
   - `token`
   - `platform`
   - `last_used_at`
   - `created_at`
   - `updated_at`
5. Tambahkan unique constraint untuk `token`.
6. Tambahkan index untuk `user_id`.
7. Pertimbangkan field `disabled_at` atau `revoked_at` untuk token invalid.
8. Desain tabel `notifications` untuk menyimpan daftar notifikasi user.
9. Relasikan notifikasi ke user penerima.
10. Siapkan field minimum tabel `notifications`:
   - `id`
   - `user_id`
   - `type`
   - `title`
   - `body`
   - `data`
   - `read_at`
   - `created_at`
   - `updated_at`
11. Gunakan `read_at = null` sebagai penanda belum dibaca.
12. Gunakan `read_at` berisi timestamp sebagai penanda sudah dibaca.
13. Tambahkan index untuk `user_id` dan `read_at`.

## Task Backend/API

1. Buat route API dengan prefix `/api`.
2. Pastikan endpoint device token memakai middleware `session.token`.
3. Buat FormRequest untuk register device token.
4. Buat endpoint register device token.
5. Buat endpoint delete device token saat logout.
6. Buat service khusus Firebase notification di boundary `app/Services`.
7. Buat job/queue untuk pengiriman notifikasi.
8. Buat event handler untuk perubahan status pesanan.
9. Kirim notifikasi ke semua device aktif milik user penerima.
10. Tangani token invalid dari Firebase.
11. Hapus atau nonaktifkan token invalid.
12. Simpan notifikasi ke database agar tersedia sebagai daftar/inbox notifikasi aplikasi.
13. Notifikasi baru harus tersimpan sebagai belum dibaca.
14. Buat endpoint daftar notifikasi milik user.
15. Buat endpoint untuk menandai satu notifikasi sebagai sudah dibaca.
16. Buat endpoint untuk menandai semua notifikasi user sebagai sudah dibaca.
17. Tambahkan logging untuk kegagalan pengiriman notifikasi tanpa mencatat credential atau token lengkap.
18. Pastikan job idempotent atau memiliki deduplication key per event.

## Rekomendasi Endpoint

```http
POST /api/device-tokens
DELETE /api/device-tokens/{token}
POST /api/device-tokens/remove
GET /api/notifications
POST /api/notifications/{notification}/read
POST /api/notifications/read-all
```

Gunakan salah satu pola delete token yang paling sesuai dengan pola API project saat implementasi. Jika token terlalu panjang untuk path parameter, endpoint `POST /api/device-tokens/remove` dengan body lebih aman.

## Rekomendasi Payload

```json
{
  "title": "Status pesanan diperbarui",
  "body": "Pesanan kamu sedang diproses.",
  "data": {
    "type": "order_status_changed",
    "order_id": "123",
    "status": "processing"
  }
}
```

Payload final perlu mengikuti kebutuhan frontend/mobile, terutama untuk deep link ke detail pesanan.

## Acceptance Criteria

1. User bisa mendaftarkan device token.
2. Satu user bisa memiliki lebih dari satu device token.
3. Device token yang sama tidak tersimpan duplikat.
4. User bisa menghapus device token saat logout.
5. Service Firebase dapat mengirim notifikasi ke token user.
6. Event status pesanan dapat memicu notifikasi ke user terkait.
7. Token invalid dari Firebase ditangani agar tidak terus dikirimi notifikasi.
8. Pengiriman notifikasi tidak memperlambat response utama karena menggunakan job/queue.
9. Kegagalan kirim notifikasi tercatat di log secara aman.
10. Credential Firebase tidak tersimpan di repository.
11. Endpoint device token memakai `session.token`.
12. Queue worker production terdokumentasi untuk Laravel Cloud saat implementasi.
13. Daftar notifikasi tersimpan di database.
14. Notifikasi baru memiliki status belum dibaca.
15. User bisa melihat daftar notifikasi miliknya.
16. User bisa menandai notifikasi sebagai sudah dibaca.
17. User tidak bisa melihat atau mengubah notifikasi milik user lain.

## Task Testing

1. Test register device token.
2. Test gagal register device token jika unauthenticated.
3. Test update token jika token sudah ada.
4. Test delete device token.
5. Test satu user bisa memiliki banyak token.
6. Test service Firebase dengan mock/fake client.
7. Test event status pesanan memicu job notifikasi.
8. Test notifikasi dikirim ke semua device user terkait.
9. Test token invalid ditangani dengan benar.
10. Test notifikasi tidak dikirim ke user yang tidak terkait dengan pesanan.
11. Test job tidak mengirim duplikasi untuk event yang sama jika deduplication diterapkan.
12. Test notifikasi tersimpan di database saat event dibuat.
13. Test notifikasi baru memiliki `read_at = null`.
14. Test daftar notifikasi hanya menampilkan notifikasi milik user login.
15. Test mark as read mengisi `read_at`.
16. Test user tidak bisa menandai notifikasi milik user lain sebagai sudah dibaca.

## Verification Checklist

1. Jalankan Laravel Pint.
2. Jalankan test feature terkait device token.
3. Jalankan test unit/service untuk Firebase notification dengan mock/fake client.
4. Jalankan test job/event notifikasi.
5. Pastikan tidak ada credential Firebase di repository.
6. Pastikan log tidak mencatat token lengkap atau secret.
7. Pastikan dokumentasi API diperbarui jika endpoint device token sudah diimplementasikan.
8. Pastikan kebutuhan env var dan queue worker Laravel Cloud terdokumentasi saat implementasi.
