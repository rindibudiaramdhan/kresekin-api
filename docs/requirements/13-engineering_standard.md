# Engineering Standard Kresekin API

Dokumen ini adalah standar kerja engineering untuk seluruh engineer yang melakukan perubahan pada codebase Kresekin API. Tujuannya bukan menambah birokrasi, tetapi memastikan setiap perubahan aman, konsisten, mudah diuji, dan tidak merusak kontrak API yang dipakai client.

## Prinsip Utama

1. Jaga kontrak API lebih ketat daripada preferensi implementasi.
2. Selesaikan masalah bisnis di boundary yang tepat: route, middleware, FormRequest, controller, model, service, atau support class.
3. Setiap perubahan perilaku harus punya regression test.
4. Jangan menambah kompleksitas abstraksi sebelum ada kebutuhan nyata.
5. Jangan menyimpan credential, token, OTP, atau data sensitif di repository, log, response, atau dokumentasi contoh.
6. Utamakan perubahan kecil yang bisa direview jelas daripada perubahan besar yang mencampur banyak concern.

## Stack dan Konvensi Dasar

- Backend menggunakan Laravel 13 dan PHP 8.3.
- Database utama adalah PostgreSQL. Test suite menggunakan SQLite in-memory melalui `phpunit.xml`.
- Production deployment wajib kompatibel dengan Laravel Cloud.
- Style PHP mengikuti Laravel Pint. Jalankan `./vendor/bin/pint` sebelum merge untuk perubahan PHP.
- Test dijalankan dengan `php artisan test` atau `composer test`.
- Indentasi mengikuti `.editorconfig`: 4 spasi untuk source code, 2 spasi untuk YAML.
- Gunakan LF line ending dan pastikan file berakhir dengan newline.

## Struktur Codebase

Gunakan lokasi berikut secara konsisten:

- `routes/api.php`: definisi endpoint API, middleware, prefix role, dan route defaults.
- `routes/web.php`: halaman web/server-rendered flow.
- `app/Http/Controllers/Api`: controller API. Default-nya single-action controller dengan `__invoke`.
- `app/Http/Controllers/Web`: controller untuk halaman web.
- `app/Http/Requests`: validasi input, normalisasi input, dan resolve dependency request-level.
- `app/Http/Middleware`: autentikasi token, role guard, dan request boundary.
- `app/Models`: Eloquent model, relation, casts, constants, scopes, dan helper domain yang melekat pada model.
- `app/Services`: integrasi atau capability yang bisa diganti implementasinya, misalnya OTP sender.
- `app/Support`: business helper/query/aggregator yang tidak cocok ditempatkan langsung di controller atau model.
- `database/migrations`: perubahan schema.
- `database/seeders`: reference data dan data awal.
- `tests/Feature`: perilaku endpoint, middleware, dan integrasi request-response.
- `tests/Unit`: perilaku model, support class, formatter, calculator, dan unit domain kecil.
- `docs`: dokumentasi API, task, SOP, dan keputusan teknis.

## API Design

Semua endpoint baru atau perubahan endpoint harus mengikuti pola API yang sudah ada.

- Response sukses menggunakan JSON dengan minimal `message`, lalu `data` bila ada payload.
- Response list yang dipaginasi menggunakan `data`, `meta`, dan `links`.
- Error validasi menggunakan status `422` dan struktur:

```json
{
    "message": "Data yang diberikan tidak valid.",
    "errors": {}
}
```

- Error autentikasi menggunakan status `401` dengan message `Tidak terautentikasi.`.
- Error role menggunakan status `403` dengan message yang menjelaskan role yang dibutuhkan.
- Gunakan status HTTP semantik: `200` untuk read/update umum, `201` untuk create, `204` hanya jika benar-benar tanpa body, `404` untuk resource tidak ditemukan, `422` untuk business validation.
- Jangan mengganti nama field response, tipe data, format tanggal, atau struktur pagination tanpa kebutuhan versi/migrasi yang jelas.
- Untuk nominal uang, kirim nilai integer mentah dan label display bila endpoint yang sejenis sudah melakukannya, misalnya `total_amount` dan `total_amount_label`.
- Tanggal dan waktu di response API gunakan ISO 8601 saat mengirim timestamp penuh.
- Field enum/status harus berasal dari constants atau method model, bukan string literal yang tersebar.

## Routing dan Access Control

- Endpoint yang butuh login harus menggunakan middleware `session.token`.
- Endpoint role-specific harus berada dalam group `role:buyer`, `role:seller`, `role:agent`, atau `role:finance`.
- Jangan melakukan pengecekan role manual di controller bila bisa ditangani middleware.
- Gunakan prefix route sesuai domain role, misalnya `/seller/*`, `/agent/*`, dan `/finance/*`.
- Untuk endpoint auth lintas role, gunakan `User::roles()` atau constants role dari model `User`.
- Jangan membuat endpoint baru yang melewati access control hanya untuk kemudahan frontend atau testing.

## Controller Standard

Controller harus tipis dan fokus pada orkestrasi request-response.

- Gunakan single-action controller `__invoke` untuk endpoint baru kecuali endpoint tersebut memang satu resource kecil dengan beberapa aksi yang sangat terkait.
- Validasi input kompleks harus masuk ke FormRequest.
- Query dan mapping response boleh berada di controller selama masih sederhana dan spesifik endpoint.
- Business logic yang dipakai ulang atau mulai panjang harus dipindahkan ke `app/Support` atau `app/Services`.
- Operasi multi-step yang mengubah beberapa tabel wajib dibungkus `DB::transaction`.
- Gunakan eager loading untuk relation yang dipakai di mapping response agar tidak memicu N+1 query.
- Gunakan `response()->json(...)` secara eksplisit agar status dan struktur mudah dibaca.
- Jangan mengembalikan model Eloquent mentah untuk API public. Map field response secara eksplisit.

## FormRequest dan Validasi

Gunakan FormRequest untuk create/update/action yang memiliki body kompleks.

- `authorize()` default `true` hanya boleh dipakai bila authorization sudah dijamin middleware atau logic lain yang eksplisit.
- Gunakan rules Laravel standar dan `Rule::*` untuk validasi database, enum, dan constraint aktif.
- Normalisasi input seperti `strtolower(trim(...))` atau `strtoupper(trim(...))` harus konsisten dengan domain.
- Validasi business rule tambahan ditempatkan di `withValidator()` bila membutuhkan data lain.
- Jika FormRequest resolve model/data turunan, cache hasil resolve di property private agar tidak query berulang.
- Untuk business error yang bukan field validation murni, gunakan `HttpResponseException` dengan response JSON yang konsisten.
- Jangan menyembunyikan error validasi dengan fallback diam-diam. Input invalid harus gagal secara eksplisit.

## Model dan Domain Logic

Model boleh memuat logic domain yang melekat langsung pada data model.

- Gunakan constants untuk role, status, code, tipe diskon, dan nilai domain lain yang dipakai lintas file.
- Gunakan method seperti `roles()`, `statusMap()`, atau helper serupa untuk daftar nilai valid.
- Definisikan `casts()` untuk integer, boolean, datetime, float, dan hashed password.
- Definisikan relation Eloquent dengan return type yang tepat.
- Gunakan scope query untuk filter yang berulang, misalnya `active()` atau `available()`.
- Gunakan `HasUuids` untuk model yang memang menggunakan UUID sesuai pola schema saat ini.
- Hindari mass assignment tanpa fillable yang jelas.
- Jangan menaruh formatting response yang terlalu spesifik endpoint di model kecuali dipakai luas dan memang domain-level.

## Database dan Migration

Perubahan schema harus menjaga data production dan test.

- Migration baru tidak boleh mengubah migration lama yang sudah pernah dipakai bersama, kecuali masih benar-benar belum masuk branch bersama.
- Gunakan foreign key, index, nullable, default, dan unique constraint sesuai kebutuhan query dan integritas data.
- Untuk uang, stok, jumlah, dan counter gunakan integer, bukan float.
- Untuk operasi yang rentan race condition, gunakan transaksi database dan row lock seperti `lockForUpdate()`.
- Seeder harus idempotent bila akan dijalankan berulang di environment lokal/staging.
- Perubahan schema wajib diikuti update model casts/fillable/relation dan test terkait.

## Transactional Integrity

Flow yang menyentuh pembayaran, checkout, komisi, disbursement, stok, promo, dan status transaksi harus diperlakukan sebagai high-risk.

- Bungkus perubahan multi-tabel dengan `DB::transaction`.
- Lock record yang bisa dipakai bersamaan, misalnya promo, product stock, withdrawal, atau disbursement.
- Hitung ulang nilai kritikal di server. Jangan percaya subtotal, diskon, fee, total, status, atau role dari client.
- Simpan snapshot data transaksi yang perlu historis, seperti nama produk, harga, metode pembayaran, promo, dan status saat transaksi dibuat.
- Jangan menghapus audit trail status atau histori pembayaran.
- Update status harus valid dari state saat ini, bukan sekadar menerima status target dari request.

## Auth, Session, dan Security

- Token session disimpan dalam bentuk hash SHA-256, bukan plain token.
- Endpoint authenticated wajib membaca user dari resolver yang dipasang middleware `session.token`.
- Jangan log OTP, plain token, password, credential bank, identity document path, atau data pribadi sensitif tanpa masking.
- Model `User` sudah menyembunyikan `password`, `remember_token`, dan `otp_code`; jangan expose field tersebut manual di response.
- Upload file harus divalidasi tipe, ukuran, dan ownership-nya.
- Jangan menerima path file dari client sebagai path absolut.
- Semua authorization ownership harus dicek di server, misalnya seller hanya boleh mengubah tenant/product/order miliknya.
- Gunakan konfigurasi dari `config/*` dan environment variable. Jangan hardcode credential atau endpoint eksternal di source code.
- Jangan mengandalkan local application disk untuk file production yang perlu persisten; gunakan Laravel filesystem/Flysystem dengan object storage.

## Testing Standard

Setiap perubahan behavior harus punya test yang gagal sebelum fix dan lulus setelah fix.

- Endpoint API diuji di `tests/Feature/*ApiTest.php`.
- Model, casts, relation, calculator, formatter, service kecil, dan support query diuji di `tests/Unit`.
- Gunakan `RefreshDatabase` untuk test yang menyentuh database.
- Test harus mencakup happy path, invalid input, unauthorized/forbidden access, resource not found, dan business edge case yang relevan.
- Untuk endpoint role-specific, test minimal memastikan role yang salah tidak bisa mengakses.
- Untuk flow uang/stok/promo/status, test nilai akhir database, bukan hanya response JSON.
- Jangan bergantung pada urutan test, state global, atau data lokal di luar setup test.
- Hindari snapshot test besar untuk JSON API. Assert field penting dengan `assertJsonPath`, `assertDatabaseHas`, dan assertion eksplisit.

Perintah minimum sebelum merge:

```bash
./vendor/bin/pint
php artisan test
```

Jika perubahan menyentuh frontend asset atau Blade yang bergantung Vite:

```bash
npm run build
```

Jika perubahan menyentuh deployment, queue, scheduler, storage, atau environment production:

- Pastikan behavior kompatibel dengan Laravel Cloud.
- Dokumentasikan build/deploy command, env var baru, queue worker, scheduled task, atau storage resource yang dibutuhkan.
- Jangan menambah long-running non-Laravel runtime tanpa ADR atau architecture review.

## Error Handling dan Observability

- Error yang diketahui harus dikembalikan sebagai JSON terstruktur, bukan exception mentah.
- Jangan menangkap exception hanya untuk mengembalikan response sukses palsu.
- Gunakan message Bahasa Indonesia yang jelas dan konsisten dengan endpoint lain.
- Log hanya untuk informasi yang membantu investigasi dan tidak membocorkan data sensitif.
- Integrasi eksternal harus punya failure mode yang jelas: retry, fallback, atau error response yang bisa dipahami client.

## Documentation Standard

- Perubahan endpoint public harus memperbarui `API_DOCUMENTATION.md` atau dokumen API terkait di `docs/api`.
- Perubahan requirement atau keputusan teknis yang penting harus ditulis di `docs/tasks` atau SOP yang relevan.
- Perubahan deployment, storage production, queue, scheduler, observability, atau platform dependency harus memperbarui ADR/requirement yang relevan.
- Dokumentasi harus memuat request, response sukses, response error penting, auth/role requirement, dan catatan business rule.
- Jangan dokumentasikan secret asli, token asli, nomor rekening asli, atau data pribadi nyata.

## Review Standard

Reviewer wajib memeriksa:

- Apakah endpoint dilindungi middleware yang benar.
- Apakah validasi dan authorization berada di boundary yang tepat.
- Apakah perubahan response kompatibel dengan client.
- Apakah query berpotensi N+1 atau terlalu mahal.
- Apakah operasi multi-tabel sudah transactional.
- Apakah state transition dan perhitungan uang/stok/promo aman.
- Apakah test menutup behavior penting dan edge case.
- Apakah dokumentasi diperbarui saat kontrak API berubah.
- Apakah perubahan runtime kompatibel dengan Laravel Cloud production.
- Apakah tidak ada credential, debug code, dump, atau log sensitif yang tertinggal.

## Definition of Done

Sebuah task dianggap selesai bila:

1. Behavior sesuai requirement dan tetap konsisten dengan pola codebase.
2. Access control, validation, dan error response sudah eksplisit.
3. Data penting tersimpan dengan integritas yang benar.
4. Test relevan sudah ditambahkan atau diperbarui.
5. `./vendor/bin/pint` lulus untuk perubahan PHP.
6. `php artisan test` lulus, atau ada alasan teknis jelas bila belum bisa dijalankan.
7. Dokumentasi diperbarui jika API, flow bisnis, atau operational behavior berubah.
8. Tidak ada perubahan unrelated yang ikut terbawa.
9. Jika menyentuh deployment/storage/queue/scheduler, kebutuhan Laravel Cloud sudah terdokumentasi.

## Anti-Pattern yang Harus Dihindari

- Menaruh seluruh business logic di controller panjang tanpa batas yang jelas.
- Mengulang string role/status/code di banyak file tanpa constants.
- Mengandalkan client untuk menghitung harga, diskon, fee, stok, atau status.
- Mengubah response API tanpa test dan dokumentasi.
- Query relation di dalam loop tanpa eager loading.
- Update stok, promo, komisi, atau disbursement tanpa transaksi database.
- Membuat endpoint bypass auth untuk kebutuhan sementara.
- Menambah dependency baru tanpa alasan teknis yang kuat.
- Menambah runtime atau proses non-Laravel yang tidak cocok dengan Laravel Cloud tanpa architecture review.
- Menyimpan file upload atau asset dengan nama/path yang bisa ditebak dan tidak divalidasi.
- Menyimpan file production penting hanya di local application disk.
- Menghapus test yang gagal tanpa memahami regression yang sedang ditangkap.
