# Integrations and Notifications Kresekin API

Dokumen ini mendefinisikan integrasi aktif dan integrasi masa depan untuk Kresekin API.

## Notification System

Saat ini OTP sender dibungkus melalui contract `WhatsappOtpSender` dan implementasi log sender untuk development/test.

Requirement:

1. Pengiriman OTP harus berada di service boundary.
2. Provider OTP production tidak boleh dipanggil langsung dari controller tanpa adapter.
3. OTP harus dimasking di log.
4. Failure provider harus menghasilkan response yang aman dan dapat diinvestigasi.
5. Retry OTP harus dibatasi agar tidak memicu abuse atau biaya tidak terkendali.
6. OTP register/login/resend harus berlaku 5 menit sejak dikirim, dan notifikasi OTP harus menyampaikan masa berlaku tersebut.

## Events and Recipients

Candidate notification event:

1. OTP register/login/resend.
2. Agent registration submitted.
3. Agent review approved/rejected.
4. Order status changed.
5. Withdrawal approved/rejected/paid.
6. Buyer payment confirmed.
7. Seller disbursement completed.

Recipient:

1. Buyer untuk order/payment.
2. Seller untuk order baru/status finance.
3. Agent untuk komisi dan review.
4. Finance untuk withdrawal baru atau disbursement pending.

## BPS Region Integration

Endpoint Indonesia region menggunakan service `BpsRegionService`.

Requirement:

1. Endpoint region harus memvalidasi parameter parent region.
2. Failure provider eksternal harus dikembalikan sebagai error yang jelas, misalnya `502`.
3. Response region harus stabil untuk form registration/profile.
4. Cache dapat ditambahkan untuk mengurangi dependency langsung ke provider.
5. Log failure tidak boleh memuat data user sensitif.

## Storage Integration

Storage dipakai untuk product image dan identity document.

Requirement:

1. Dokumen identitas agent harus berada di private disk.
2. Product image boleh public hanya bila memang asset katalog.
3. Upload harus validasi mime, ukuran, dan ownership.
4. Path file tidak boleh berasal dari input absolut client.
5. Penghapusan/replacement file harus mempertimbangkan referensi data existing.
6. S3/object storage/Flysystem config harus berasal dari environment atau resource Laravel Cloud.
7. Production upload tidak boleh bergantung pada local application disk karena filesystem Laravel Cloud bersifat ephemeral.
8. File private harus diakses melalui endpoint terotorisasi atau signed URL berdurasi pendek.
9. Object storage production harus kompatibel dengan Flysystem S3 driver atau resource Laravel Cloud object storage.

## Laravel Cloud Platform Integration

Production deployment wajib berjalan di Laravel Cloud.

Requirement:

1. Database, queue, scheduler, cache/KV, object storage, log/metric, domain/TLS, dan environment variable harus dikonfigurasi dengan resource atau mekanisme yang kompatibel dengan Laravel Cloud.
2. Integrasi provider eksternal tidak boleh membutuhkan long-running non-Laravel runtime tanpa architecture review.
3. Build/deploy command harus deterministic, tidak membutuhkan input manual, dan tidak bergantung pada state lokal yang tidak tersedia di Laravel Cloud.
4. Perubahan credential atau environment variable production harus tercatat di proses operasional dan diikuti redeploy bila diperlukan.
5. Resource platform yang memiliki quota, retention, atau scaling limit harus dicatat sebelum production launch.
6. PostgreSQL adalah database production default; MySQL hanya boleh dipakai bila kebutuhan bisnis/operasional disetujui melalui ADR.
7. SQLite tidak boleh dipakai untuk production karena hanya ditujukan untuk test/local ringan.
8. Managed queue, scheduled task, Redis-compatible cache/KV, WebSockets, object storage, preview environment, dan scale-to-zero Laravel Cloud harus dievaluasi sebelum memilih provider eksternal.
9. Setiap resource Laravel Cloud yang dipakai harus memiliki owner, environment, credential/env var, backup/retention, alerting, dan scaling note.

## Payment and Payout Provider

Belum ada payment/payout provider final.

Requirement saat provider ditambahkan:

1. Bungkus provider dalam service boundary.
2. Simpan external reference/id transaksi.
3. Webhook harus diverifikasi signature/token.
4. Webhook harus idempotent.
5. Provider failure tidak boleh menghasilkan double payment/disbursement.
6. Status internal tetap source of truth setelah event provider tervalidasi.

## Inbound Webhooks

Belum ada webhook public aktif.

Jika ditambahkan:

1. Gunakan prefix jelas seperti `/api/webhooks/*` atau `/webhooks/*`.
2. Terapkan auth/signature verification dan rate limit.
3. Simpan raw event minimal yang aman untuk debugging.
4. Dedup event provider.
5. Jangan percaya nominal/status dari provider tanpa validasi terhadap data internal.

## Audit Requirements

1. OTP verified dan resend limit event.
2. Upload dokumen/gambar.
3. Payment webhook accepted/rejected bila ada.
4. Payout request/response provider bila ada.
5. Perubahan konfigurasi provider atau credential rotation harus tercatat di proses operasional.

## Open Questions

1. Provider OTP production apa yang akan digunakan?
2. Apakah WhatsApp OTP tetap wajib atau email cukup untuk beberapa role?
3. Payment gateway dan payout provider apa yang dipilih?
4. Apakah region BPS perlu cache database lokal?
5. Object storage production akan memakai resource Laravel Cloud atau provider S3 eksternal?
6. Apakah log retention Laravel Cloud cukup untuk kebutuhan audit dan incident investigation?
7. Apakah aplikasi membutuhkan Redis-compatible cache/KV untuk lock, rate limiter, session, atau cache lintas replica sejak release production pertama?
8. Apakah WebSockets diperlukan untuk dashboard real-time, atau polling API cukup untuk MVP?
