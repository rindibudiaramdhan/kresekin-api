# Peningkatan Logging & Tracing Issues

Tanggal: 2026-06-21

## Ringkasan

Peningkatan observability berikutnya difokuskan pada traceability request, log hygiene, dan alert deployment dasar. Laravel Nightwatch sudah terkoneksi di production Laravel Cloud, sehingga dokumen ini tidak lagi memuat tahapan pemasangan Nightwatch.

## Latar Belakang

Standar observability di `docs/requirements/01-architecture-nfr.md` mewajibkan aplikasi menghasilkan log terstruktur untuk event operasional penting, memiliki correlation/request id bila tersedia, dan tidak menyimpan OTP, token, password, dokumen, credential bank, atau payload sensitif penuh di log.

Hasil pengecekan codebase saat ini:

1. Belum ada middleware global untuk `request_id`.
2. Belum ada pemanggilan `Log::withContext()` atau `Log::shareContext()` untuk menambahkan request context otomatis.
3. Logging aktif yang ditemukan berada di `app/Services/LogWhatsappOtpSender.php`.
4. Logging WhatsApp OTP masih mencatat nomor telepon secara penuh dan perlu ditinjau agar sesuai standar masking data sensitif.

## Keputusan Teknis

1. Tambahkan request id sebagai fondasi tracing lintas response, Laravel log, Laravel Cloud Logs, dan Nightwatch.
2. Request id harus tersedia di:
   - response header `X-Request-Id`
   - log context Laravel
   - request attributes untuk kebutuhan debugging internal
3. Jika request masuk membawa `X-Request-Id`, gunakan nilai tersebut setelah validasi format sederhana.
4. Jika tidak ada `X-Request-Id`, generate UUID baru.
5. Gunakan `Log::withContext()` atau `Log::shareContext()` untuk memasukkan context:
   - `request_id`
   - `method`
   - `path`
   - `environment`
6. Jangan memasukkan bearer token, OTP, password, credential bank, identity document path, payload mentah, atau PII penuh ke log context.
7. Laravel Nightwatch production sudah terkoneksi dan diperlakukan sebagai channel tracing aktif.
8. Audit trail formal untuk aksi finance dan aksi sensitif tetap diperlakukan sebagai pekerjaan terpisah dari observability runtime.

## Keputusan Biaya

Laravel Nightwatch sudah terkoneksi di production dengan Free plan. Event dihitung dari banyak jenis telemetry, bukan hanya request. Event dapat mencakup request, exception, query, log, job, mail, command, cache, dan scheduled task.

Untuk menjaga biaya tetap aman:

1. Pastikan spending cap aktif di Nightwatch.
2. Pertahankan sample rate rendah pada production.
3. Monitor event usage setelah production traffic berjalan.
4. Jangan mengirim debug log noise ke Nightwatch.

Konfigurasi production yang perlu dipertahankan:

```env
NIGHTWATCH_REQUEST_SAMPLE_RATE=0.1
NIGHTWATCH_COMMAND_SAMPLE_RATE=1.0
NIGHTWATCH_EXCEPTION_SAMPLE_RATE=1.0
```

Jika traffic atau query count tinggi, turunkan request sample rate di production.

## Scope Implementasi

### Phase 0 - Laravel Cloud Email Notification

Konfigurasi ini dilakukan melalui dashboard Laravel Cloud dan tidak memerlukan perubahan codebase.

1. Buka Laravel Cloud dashboard.
2. Masuk ke profile/account user yang bertanggung jawab terhadap deployment.
3. Buka menu `Notifications`.
4. Pastikan email notification untuk `Deployments` aktif, minimal untuk failed deployments.
5. Pastikan email akun Laravel Cloud adalah email operasional yang dipantau.
6. Gunakan email notification terlebih dahulu karena Slack workspace belum tersedia.
7. Saat Slack workspace sudah tersedia, evaluasi ulang apakah alert production perlu dikirim ke Slack juga.

### Phase 1 - Request ID dan Log Hygiene

1. Buat middleware baru, misalnya `App\Http\Middleware\AssignRequestId`.
2. Daftarkan middleware sebagai global middleware di `bootstrap/app.php`.
3. Tambahkan response header `X-Request-Id`.
4. Tambahkan log context aman memakai `Log::withContext()` atau `Log::shareContext()`.
5. Tambahkan test feature untuk memastikan:
   - response selalu punya `X-Request-Id`
   - request id dari header valid dipertahankan
   - request id otomatis dibuat bila header tidak ada
6. Review logging WhatsApp OTP:
   - mask `phone`
   - mask `provider_phone`
   - jangan log OTP
   - jangan log response provider penuh bila response bisa berisi payload sensitif

### Phase 2 - Production Monitoring Follow Up

1. Pantau Nightwatch selama 24-72 jam setelah production terkoneksi:
   - event usage
   - exception volume
   - slow request
   - query volume
   - job failure
   - log noise
2. Turunkan sample rate bila event usage mendekati batas Free plan.
3. Pastikan spending cap aktif.
4. Tambahkan alert internal untuk issue penting setelah pola event jelas.

## Log Event Yang Direkomendasikan

Gunakan structured log untuk event operasional penting:

1. OTP provider failure, tanpa OTP dan tanpa nomor telepon penuh.
2. Payment confirmation failure.
3. Seller disbursement failure.
4. Agent withdrawal status transition failure.
5. Upload private document failure, tanpa path dokumen sensitif.
6. External integration latency/failure category.
7. Queue job failure context yang aman.

Contoh context aman:

```php
Log::warning('External integration failed.', [
    'request_id' => $requestId,
    'integration' => 'whatsapp_otp',
    'status' => $status,
    'failure_category' => 'provider_4xx',
    'duration_ms' => $durationMs,
]);
```

## Data Yang Tidak Boleh Masuk Log atau Nightwatch

1. OTP.
2. Plain bearer token.
3. Password.
4. Credential bank lengkap.
5. Identity document path.
6. Isi dokumen identitas.
7. Payload request/response penuh yang berisi data pribadi.
8. Nomor telepon atau email penuh bila tidak diperlukan.
9. API key atau secret provider.

## Verifikasi

Verifikasi manual setelah Phase 0:

1. Cek Laravel Cloud account notification settings.
2. Pastikan kategori deployment email aktif untuk failed deployments.
3. Pastikan email tujuan dapat menerima email dari Laravel Cloud.
4. Pada failed deployment berikutnya, pastikan email alert diterima.

Test yang perlu dijalankan setelah Phase 1:

```bash
./vendor/bin/pint app/Http/Middleware/AssignRequestId.php app/Services/LogWhatsappOtpSender.php tests/Feature
php artisan test tests/Feature
```

Verifikasi manual di Laravel Cloud dan Nightwatch setelah Phase 1:

1. Hit endpoint `/up`.
2. Hit satu endpoint API authenticated dan satu endpoint unauthenticated.
3. Cek response header `X-Request-Id`.
4. Cek Laravel Cloud Logs untuk `request_id`.
5. Cek Nightwatch dashboard untuk request, exception, query, dan log event.
6. Cek event usage di Nightwatch.
7. Pastikan spending cap aktif.

## Risiko dan Mitigasi

1. Event usage Nightwatch melebihi Free plan.
   - Mitigasi: spending cap, sample rate rendah, matikan log noise.
2. PII terkirim ke log atau Nightwatch.
   - Mitigasi: masking, redaction config, review log context, jangan log payload penuh.
3. Request id dari client tidak valid atau terlalu panjang.
   - Mitigasi: validasi format dan panjang; generate UUID bila tidak valid.
4. Nightwatch agent menambah overhead.
   - Mitigasi: monitor latency production dan turunkan sample rate bila diperlukan.
5. Log Cloud retention tidak cukup untuk investigasi jangka panjang.
   - Mitigasi: gunakan audit trail database untuk aksi sensitif dan pertimbangkan plan/tool tambahan bila dibutuhkan.

## Out of Scope

1. Audit trail formal untuk semua aksi finance.
2. Integrasi Sentry, Datadog, New Relic, atau tool eksternal lain.
3. Alerting bisnis berbasis SLA.
4. Long-term log archive di object storage.
5. Dashboard internal observability custom.

## Urutan Prioritas

1. Aktifkan Laravel Cloud email notification untuk failed deployments.
2. Tambah request id middleware.
3. Mask logging WhatsApp OTP.
4. Tambah test regression.
5. Pastikan spending cap Nightwatch aktif.
6. Pertahankan production sample rate rendah.
7. Evaluasi event usage dan noise Nightwatch.
