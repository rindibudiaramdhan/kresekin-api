# Fitur Email OTP Tambahan

Tanggal: 2026-06-21

## Ringkasan

Email OTP memiliki varian desain berdasarkan konteks dan role. Desain Seller dipakai untuk login Seller dan resend OTP Seller, sedangkan desain umum tetap dipakai untuk non-Seller sampai varian Agent dan Finance tersedia dari tim desain.

## Keputusan Produk

1. Login Seller via email memakai desain Seller.
2. Registrasi Seller memakai desain verifikasi akun Seller.
3. Registrasi non-Seller memakai desain umum verifikasi akun.
4. Resend OTP:
   - Seller selalu memakai desain Seller.
   - Non-Seller memakai desain umum.
5. Logo Seller sementara memakai logo umum dengan label `Seller` sampai asset resmi dari tim desain tersedia.
6. Agent dan Finance sementara memakai desain umum; varian khusus akan menyusul.
7. OTP tetap berlaku 5 menit sesuai standar auth yang sudah dicatat di dokumen requirement dan ADR.

## Copy Final

### Login Seller

1. Heading: `Masuk ke Kresek.in Seller`
2. Intro: `Gunakan kode verifikasi berikut untuk masuk ke akun Anda:`
3. Closing: `Jika Anda tidak merasa melakukan login, abaikan email ini`
4. Download label: `kresek.in seller`

### Registrasi Seller

1. Heading: `Verifikasi Akun Kresek.in Seller`
2. Intro: `Masukkan kode berikut untuk menyelesaikan pendaftaran:`
3. Catatan tambahan: `Gunakan kode ini untuk mengaktifkan akun Anda.`
4. Closing: `Selamat datang di kresek.in seller`
5. Download label: `kresek.in seller`

### Registrasi Umum

1. Heading: `Verifikasi Akun Anda`
2. Intro: `Masukkan kode berikut untuk menyelesaikan pendaftaran:`
3. Catatan tambahan: `Gunakan kode ini untuk mengaktifkan akun Anda.`
4. Closing: `Selamat datang di kresek.in`
5. Download label: `kresek.in`

## Implementasi

1. Varian email ditentukan dari `User::ROLE_SELLER` pada notifiable user.
2. Template HTML dan text tetap memakai file reusable:
   - `resources/views/emails/otp.blade.php`
   - `resources/views/emails/otp-text.blade.php`
3. Data template mencakup label brand, label download, catatan tambahan, dan closing message per konteks.
4. Tidak ada perubahan kontrak API atau skema database.

## Verifikasi

Test yang perlu dijalankan:

```bash
./vendor/bin/pint app/Notifications/Concerns/BuildsOtpMail.php app/Notifications/LoginOtpNotification.php app/Notifications/RegistrationOtpNotification.php app/Notifications/ResendOtpNotification.php tests/Unit/NotificationTest.php
php artisan view:cache
php artisan view:clear
php artisan test tests/Unit/NotificationTest.php tests/Feature/AuthApiTest.php
```
