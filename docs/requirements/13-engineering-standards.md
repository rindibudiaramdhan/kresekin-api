# Engineering Standards Kresekin API

Dokumen ini adalah versi canonical bernama hyphenated untuk mengikuti seri requirements. Isi standarnya mengacu pada [`13-engineering_standard.md`](13-engineering_standard.md).

## Prinsip Utama

1. Jaga kontrak API lebih ketat daripada preferensi implementasi.
2. Tempatkan logic pada boundary yang tepat: route, middleware, FormRequest, controller, model, service, support class, atau database.
3. Setiap perubahan behavior harus memiliki regression test.
4. Jangan menyimpan credential, token, OTP, dokumen sensitif, atau data pribadi nyata di repository, log, response, atau dokumentasi contoh.
5. Operasi uang, stok, promo, status transaksi, komisi, dan disbursement harus mengutamakan integritas data.

## Stack

1. Laravel 13.
2. PHP 8.3.
3. PostgreSQL untuk production.
4. SQLite in-memory untuk test sesuai konfigurasi project.
5. Laravel Pint untuk style PHP.
6. PHPUnit 12 untuk test.
7. Vite/Tailwind untuk asset Blade bila dibutuhkan.
8. Laravel Cloud sebagai platform production wajib.

## Struktur Codebase

| Lokasi | Fungsi |
| --- | --- |
| `routes/api.php` | Endpoint API, middleware, role boundary |
| `routes/web.php` | Web flow Blade/server-rendered |
| `app/Http/Controllers/Api` | Controller API |
| `app/Http/Controllers/Web` | Controller web |
| `app/Http/Requests` | Validasi request |
| `app/Http/Middleware` | Session token dan role guard |
| `app/Models` | Eloquent model, relation, cast, constants |
| `app/Services` | Integrasi/provider boundary |
| `app/Support` | Calculator, query aggregator, formatter |
| `tests/Feature` | Endpoint dan middleware behavior |
| `tests/Unit` | Model/support/service kecil |
| `docs` | Requirement, API docs, task docs |

## API Standard

1. Response sukses minimal memiliki `message`.
2. Payload data dikirim di `data`.
3. Pagination memakai `data`, `meta`, dan `links`.
4. Error validasi memakai status `422`.
5. Error auth memakai `401`; role mismatch memakai `403`.
6. Nominal uang dikirim integer.
7. Timestamp penuh memakai ISO 8601.
8. Jangan mengembalikan model mentah untuk API public.
9. Jangan mengubah field response existing tanpa migration/deprecation.

## Access Control

1. Endpoint authenticated wajib memakai `session.token`.
2. Endpoint role-specific wajib memakai `role:buyer`, `role:seller`, `role:agent`, atau `role:finance`.
3. Ownership resource harus dicek server-side.
4. Resource di luar scope sebaiknya dikembalikan sebagai `404`.
5. Endpoint role-specific wajib punya test unauthorized/forbidden.

## Data Integrity

1. Gunakan `DB::transaction` untuk operasi multi-table.
2. Gunakan row lock untuk record yang rentan race condition.
3. Hitung ulang uang, diskon, fee, stok, komisi, dan status di server.
4. Snapshot data historis ke transaksi dan item transaksi.
5. State transition harus memvalidasi current state.

## Testing

Perintah minimum:

```bash
./vendor/bin/pint
php artisan test
```

Jika perubahan menyentuh asset frontend:

```bash
npm run build
```

Jika perubahan menyentuh deployment, queue, scheduler, storage, atau environment production, pastikan kompatibel dengan Laravel Cloud dan dokumentasikan resource/env var yang dibutuhkan.

Test harus mencakup happy path, input invalid, unauthorized, forbidden, not found, dan edge case bisnis yang relevan.

## Definition of Done

1. Behavior sesuai requirement.
2. Middleware, validasi, authorization, dan ownership eksplisit.
3. Data penting tersimpan dengan integrity benar.
4. Test relevan lulus.
5. Dokumentasi diperbarui bila kontrak API atau flow bisnis berubah.
6. Tidak ada perubahan unrelated.
7. Kebutuhan Laravel Cloud terdokumentasi bila perubahan menyentuh runtime production.
