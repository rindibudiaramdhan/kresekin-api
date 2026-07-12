# Fitur Email OTP

Tanggal: 2026-06-20

## Ringkasan

Email OTP Kresek.in menggunakan template HTML branded yang sama untuk login, registrasi, dan resend OTP. Template menampilkan logo Kresek.in, kode OTP 6 digit, catatan keamanan, masa berlaku 5 menit, badge Google Play, dan footer bantuan.

## Keputusan Produk

1. OTP berlaku 5 menit sejak dikirim.
2. Template desain email OTP dipakai untuk semua email OTP: login, registrasi, dan resend.
3. Subject email:
   - Login: `Kode OTP Masuk Kresek.in`
   - Registrasi: `Kode OTP Registrasi Kresek.in`
   - Resend: `Kode OTP Kresek.in`
4. Support email final: `cs-support@kresek.in`.
5. Badge Google Play selalu ditampilkan, tetapi belum diberi link sampai URL Play Store final tersedia.

## Implementasi

1. Masa berlaku OTP disimpan sebagai konstanta domain `User::OTP_EXPIRES_IN_MINUTES`.
2. Verifikasi OTP menolak kode yang sudah melewati 5 menit dari `otp_sent_at`.
3. Email OTP menggunakan view HTML `resources/views/emails/otp.blade.php` dan fallback text `resources/views/emails/otp-text.blade.php`.
4. Konfigurasi email OTP tersedia di `config/mail.php`:
   - `MAIL_SUPPORT_EMAIL`, default `cs-support@kresek.in`
   - `KRESEKIN_PLAYSTORE_URL`, optional dan sementara boleh kosong
5. Asset brand menggunakan file production di `public/images`.

## Dokumen Terkait

1. `docs/requirements/01-architecture-nfr.md`
2. `docs/requirements/02-roles-permissions.md`
3. `docs/requirements/11-integrations.md`
4. `docs/adr/003-use-otp-session-token-auth.md`

## Verifikasi

Test yang perlu dijalankan:

```bash
./vendor/bin/pint
php artisan test tests/Unit/NotificationTest.php tests/Feature/AuthApiTest.php
```
