Bisa, tapi jangan pakai password Gmail biasa. Untuk Gmail Anda harus pakai SMTP + App Password.

Yang perlu Anda lakukan di akun Gmail:
1. Aktifkan 2-Step Verification di akun Google
2. Buat App Password
3. Pakai App Password itu di `.env`, bukan password login Gmail

Config `.env`:
```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=alamatgmailanda@gmail.com
MAIL_PASSWORD=app_password_16_karakter
MAIL_FROM_ADDRESS=alamatgmailanda@gmail.com
MAIL_FROM_NAME="Kresekin API"
```

Lalu jalankan:
```bash
php artisan config:clear
php artisan cache:clear
```

Setelah itu coba hit endpoint register email lagi.

Catatan penting:
- Kalau 2-Step Verification belum aktif, App Password tidak akan tersedia
- Google bisa tetap membatasi pengiriman jika dianggap mencurigakan
- Untuk development/testing ini bisa dipakai, tapi untuk production saya tidak sarankan pakai Gmail pribadi
- Pastikan `MAIL_MAILER` tidak lagi `log`, karena kalau masih `log` email tetap tidak akan terkirim
