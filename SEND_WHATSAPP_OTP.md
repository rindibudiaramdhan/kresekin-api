# Send WhatsApp OTP

Dokumen ini menjelaskan setup yang perlu dilakukan developer agar OTP registrasi via WhatsApp bisa dipakai di project ini.

## Kondisi Saat Ini

Flow register phone sudah tersedia di endpoint:

```text
POST /api/users/register
```

Payload:

```json
{
  "type": "phone",
  "phone": "+6281234567890"
}
```

Saat request valid:

1. User dibuat di tabel `users`
2. OTP 6 digit dibuat
3. Aplikasi memanggil `App\Contracts\WhatsappOtpSender`

Implementasi aktif saat ini masih `log` driver. Artinya OTP belum dikirim ke provider WhatsApp sungguhan. OTP hanya ditulis ke log aplikasi untuk keperluan development.

File terkait:

- `app/Contracts/WhatsappOtpSender.php`
- `app/Services/LogWhatsappOtpSender.php`
- `app/Providers/AppServiceProvider.php`
- `config/services.php`
- `app/Http/Controllers/Api/RegisterUserController.php`

## Variabel Environment

Tambahkan atau isi variabel berikut:

```env
WHATSAPP_OTP_DRIVER=log
WHATSAPP_OTP_BASE_URL=
WHATSAPP_OTP_API_KEY=
WHATSAPP_OTP_SENDER=
WHATSAPP_OTP_TIMEOUT=10
```

Penjelasan:

- `WHATSAPP_OTP_DRIVER`: driver pengiriman OTP WhatsApp
- `WHATSAPP_OTP_BASE_URL`: base URL API provider
- `WHATSAPP_OTP_API_KEY`: token / API key provider
- `WHATSAPP_OTP_SENDER`: sender ID, device, atau nomor pengirim sesuai provider
- `WHATSAPP_OTP_TIMEOUT`: timeout request outbound dalam detik

## Setup Minimum Untuk Development

Jika Anda baru ingin menguji flow register phone tanpa provider sungguhan:

1. Biarkan `WHATSAPP_OTP_DRIVER=log`
2. Panggil endpoint register phone
3. Cek file log:

```text
storage/logs/laravel.log
```

Dengan mode ini Anda bisa memastikan:

- validasi request berjalan
- user tersimpan
- OTP dibuat
- hook pengiriman WhatsApp sudah terpanggil

## Setup Untuk Provider WhatsApp Sungguhan

Project ini sengaja belum terikat ke provider tertentu. Developer perlu memilih provider yang dipakai tim, lalu menambahkan adapter-nya ke codebase.

Langkah yang perlu dilakukan:

1. Pilih provider WhatsApp API yang mendukung pengiriman OTP outbound
2. Ambil credential yang dibutuhkan dari provider
3. Isi `.env`:

```env
WHATSAPP_OTP_DRIVER=provider_name
WHATSAPP_OTP_BASE_URL=https://your-provider.example
WHATSAPP_OTP_API_KEY=your-secret-key
WHATSAPP_OTP_SENDER=your-sender-id
WHATSAPP_OTP_TIMEOUT=10
```

4. Buat class service baru yang mengimplementasikan `App\Contracts\WhatsappOtpSender`
5. Tambahkan binding driver tersebut di `app/Providers/AppServiceProvider.php`
6. Uji register phone end-to-end

## Kontrak Implementasi

Class provider-specific harus mengimplementasikan method berikut:

```php
public function send(string $phone, string $otp): void;
```

Di dalam implementasi itu, developer perlu:

1. format nomor tujuan sesuai aturan provider
2. kirim request HTTP ke endpoint provider
3. kirim teks OTP
4. tangani error response dan timeout
5. tulis log yang cukup jelas bila pengiriman gagal

## Menambah Driver Baru

Saat ini binding driver ada di `app/Providers/AppServiceProvider.php` dan baru mendukung:

- `log`

Jika ingin menambah driver baru, extend blok `match (...)` yang sudah ada. Contoh:

```php
return match (config('services.whatsapp_otp.driver', 'log')) {
    'log' => new LogWhatsappOtpSender(),
    'provider_name' => $this->app->make(ProviderWhatsappOtpSender::class),
    default => throw new InvalidArgumentException(...),
};
```

## Hal Yang Harus Dicek Developer

- nomor tujuan menggunakan format internasional, misalnya `+6281234567890`
- API key benar
- sender/device/channel provider aktif
- nomor tujuan memang aktif di WhatsApp
- payload pesan sesuai requirement provider
- credential tidak dikomit ke repository

## Cara Uji

```bash
curl --location 'http://127.0.0.1:8000/api/users/register' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--data-raw '{
  "type": "phone",
  "phone": "+6281234567890"
}'
```

Ekspektasi:

- response `201`
- user tersimpan di tabel `users`
- OTP dikirim ke log atau ke provider, tergantung driver aktif

## Setelah Ubah Environment

Setelah mengubah `.env`, jalankan:

```bash
php artisan config:clear
php artisan cache:clear
```

Kalau tidak, Laravel bisa tetap memakai konfigurasi lama.
