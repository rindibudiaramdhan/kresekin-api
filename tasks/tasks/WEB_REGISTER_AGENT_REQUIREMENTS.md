# Web Register Agent Requirements

Dokumen ini merangkum kebutuhan halaman registrasi agent berdasarkan desain UI, membandingkannya dengan kondisi sistem saat ini, dan memberi tahapan pengembangan yang dapat diikuti oleh junior software engineer.

## Ringkasan Desain

Halaman registrasi agent adalah halaman web publik untuk calon agent Kresek.in. Tujuan utamanya adalah mengumpulkan informasi dasar akun, dokumen identitas, dan persetujuan pemrosesan data sebelum user menjadi agent.

Elemen utama pada desain:

1. Header branding:
   - `Kresek.in Agent`
   - Subtitle: `Bantu UMKM Tumbuh & Dapatkan Penghasilan`

2. Card form:
   - Judul section: `Informasi Akun`
   - Deskripsi: `Isi data berikut untuk mulai menjadi Agent Kresek`

3. Field form:
   - `Nama Lengkap *`
   - `Email *`
   - `No Whatsapp *`
   - `Dokumen Identitas *`
   - Checkbox persetujuan pemrosesan data

4. Upload dokumen:
   - Format: JPG, PNG, PDF
   - Maksimal 5MB
   - Copy: `Klik atau unggah file KTP/SIM/Passport ke sini`

5. Actions:
   - `Kembali`
   - `Daftar Sekarang`

6. Footer:
   - Copyright Kresek.in

## Kondisi Sistem Saat Ini

### Yang Sudah Ada

1. API register agent sudah tersedia:
   - `POST /api/agent/register`
   - `POST /api/users/{role}/register`

2. API register saat ini menggunakan controller:
   - `App\Http\Controllers\Api\RegisterUserController`

3. Register API saat ini:
   - membuat user dengan role dari route
   - generate `agent_code` jika role adalah `agent`
   - membuat OTP
   - mengirim OTP via email atau WhatsApp

4. Tabel `users` sudah memiliki field yang relevan untuk desain:
   - `name`
   - `email`
   - `phone`
   - `password`
   - `agent_code`
   - `identity_document_path`

5. Model `User` sudah:
   - fillable untuk `identity_document_path`
   - memiliki cast `password` sebagai `hashed`
   - memiliki method `generateAgentCode()`

6. Halaman publik `/` saat ini sudah menjadi portal login Agent/Finance berbasis OTP.

### Yang Belum Ada

1. Belum ada halaman web register agent.
   - Belum ada route seperti `GET /agent/register`.
   - Belum ada view seperti `resources/views/agent/register.blade.php`.

2. Belum ada web controller untuk proses registrasi agent.
   - Saat ini baru ada web auth controller untuk logout seller.

3. API register yang ada belum cocok langsung dengan kebutuhan web register agent.
   - API hanya menerima salah satu kontak berdasarkan `type`: email atau phone.
   - Form web meminta email dan no WhatsApp sekaligus.
   - API belum menerima `name`.
   - API belum menerima upload dokumen identitas.
   - API belum menerima consent checkbox.

4. Flow login agent saat ini berbasis OTP dan bearer token di `localStorage`.
   - Password dikeluarkan dari scope MVP ini.
   - Sistem belum punya flow login password untuk agent.
   - Belum ada forgot password/reset password untuk agent.

5. Belum ada audit consent.
   - Checkbox persetujuan di desain sebaiknya disimpan sebagai data audit, bukan hanya validasi UI.

## Gap Utama

| Area | Desain | Sistem Saat Ini | Gap |
| --- | --- | --- | --- |
| Halaman web | Ada halaman registrasi agent | Belum ada | Perlu route, controller, view |
| Nama lengkap | Required | Field DB ada, API register belum isi | Perlu validasi dan simpan |
| Email | Required | API bisa email jika `type=email` | Perlu email dan phone sekaligus |
| No WhatsApp | Required | API bisa phone jika `type=phone` | Perlu email dan phone sekaligus |
| Password | Tidak dipakai pada MVP ini | Field DB ada, login agent masih OTP | Jangan tampilkan field password dan jangan isi password |
| Dokumen identitas | Required upload | Field DB ada | Perlu upload handling dan storage policy |
| Consent | Required | Belum ada field audit | Perlu migration jika consent perlu disimpan |
| Setelah register | Verifikasi OTP | Existing portal `/` punya OTP step setelah login submit | Perlu halaman verifikasi OTP khusus setelah register |

## Rekomendasi Arsitektur

Rekomendasi terbaik adalah membuat flow web register agent khusus, bukan memaksa endpoint API register existing untuk menanggung kebutuhan desain ini.

Alasannya:

- Endpoint API register existing adalah kontrak umum untuk role `buyer`, `seller`, `agent`, dan `finance`.
- Mengubah request API existing menjadi wajib `name`, file upload, dan consent berisiko mematahkan mobile/API client.
- Halaman web register agent memiliki kebutuhan khusus: upload dokumen, consent, dan dua kontak sekaligus.

Route yang direkomendasikan:

```php
Route::get('/agent/register', [AgentRegistrationController::class, 'create'])->name('agent.register');
Route::post('/agent/register', [AgentRegistrationController::class, 'store'])->name('agent.register.store');
Route::get('/agent/verify-otp', [AgentRegistrationController::class, 'verifyOtp'])->name('agent.register.verify-otp');
```

Class yang direkomendasikan:

- `App\Http\Controllers\Web\AgentRegistrationController`
- `App\Http\Requests\RegisterAgentWebRequest`
- `resources/views/agent/register.blade.php`
- `resources/views/agent/verify-otp.blade.php`

Storage yang direkomendasikan:

- Simpan dokumen di disk private jika dokumen identitas tidak boleh diakses publik.
- Path contoh: `agent-identities/{user_id}/document.ext`.
- Simpan path ke `users.identity_document_path`.

## Keputusan Produk Terkunci

Keputusan berikut sudah dikonfirmasi dan menjadi acuan implementasi:

1. Password dikeluarkan dari scope MVP.
   - Field password tidak ditampilkan di halaman register.
   - Password tidak divalidasi dan tidak disimpan saat register agent.
   - Login agent tetap menggunakan OTP.

2. OTP setelah register dikirim ke email.
   - Email wajib diisi.
   - WhatsApp tetap disimpan sebagai nomor kontak agent.
   - Notification yang digunakan tetap notification registrasi OTP existing.

3. Setelah registrasi berhasil, user diarahkan ke halaman verifikasi OTP khusus.
   - Route target: `GET /agent/verify-otp?email=...`.
   - Halaman langsung menampilkan input OTP.
   - Submit OTP memakai endpoint existing `POST /api/users/verify-otp`.
   - Payload OTP menggunakan `role=agent`, `type=email`, dan email dari registrasi.
   - Setelah OTP berhasil, token disimpan ke `localStorage` seperti portal existing, lalu redirect ke `agent.dashboard`.

4. Consent audit wajib disimpan.
   - Simpan `terms_accepted_at`.
   - Simpan `terms_version`.
   - Simpan `privacy_accepted_at`.
   - Versi awal terms/privacy: `agent-registration-v1`.

5. Dokumen identitas tetap perlu review manual oleh admin.
   - Status awal agent setelah register: `pending_review`.
   - Agent tetap dapat login setelah OTP verified.
   - Pembatasan fitur/halaman selama status masih `pending_review` akan dilanjutkan pada MVP berikutnya.
   - Admin review dan perubahan status menjadi `approved` atau `rejected` juga di luar scope implementasi ini.

6. Dokumen identitas disimpan di private disk Laravel `local`.
   - Path: `agent-identities/{user_id}/document.ext`.
   - Path disimpan ke `users.identity_document_path`.

7. Endpoint API register existing tidak diubah kontraknya.
   - Flow web register agent dibuat khusus.
   - `POST /api/agent/register` dan `POST /api/users/{role}/register` tetap backward compatible.

## Request Validation

Buat `RegisterAgentWebRequest` dengan rules berikut:

```php
return [
    'name' => ['required', 'string', 'max:255'],
    'email' => [
        'required',
        'email',
        'max:255',
        Rule::unique('users', 'email')->where('role', User::ROLE_AGENT),
    ],
    'phone' => [
        'required',
        'string',
        'max:20',
        'regex:/^\+?[0-9]{8,15}$/',
        Rule::unique('users', 'phone')->where('role', User::ROLE_AGENT),
    ],
    'identity_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
    'consent' => ['accepted'],
];
```

Catatan:

- Password tidak masuk scope MVP ini. Jangan menambahkan rule password pada `RegisterAgentWebRequest`.
- Nomor WhatsApp mengikuti pola validasi existing: `+6281234567890` atau `081234567890`.
- Error validasi harus tampil di halaman, bukan hanya redirect silent.

## Behavior Store

Flow `POST /agent/register` yang direkomendasikan:

1. Validasi request.
2. Simpan dokumen identitas.
3. Buat user:
   - `name`
   - `email`
   - `phone`
   - `type = email`
   - `role = agent`
   - `agent_code = User::generateAgentCode()`
   - `identity_document_path`
   - `terms_accepted_at`
   - `terms_version = agent-registration-v1`
   - `privacy_accepted_at`
   - `agent_verification_status = pending_review`
   - `otp_code`
   - `otp_sent_at`
4. Kirim OTP registrasi.
5. Redirect ke `route('agent.register.verify-otp', ['email' => $user->email])` dengan flash message:
   - `Registrasi berhasil. Kami telah mengirim OTP untuk verifikasi akun agent Anda.`

Contoh payload internal user:

```php
User::create([
    'name' => $validated['name'],
    'email' => $validated['email'],
    'phone' => $validated['phone'],
    'type' => User::AUTH_TYPE_EMAIL,
    'role' => User::ROLE_AGENT,
    'agent_code' => User::generateAgentCode(),
    'identity_document_path' => $identityDocumentPath,
    'terms_accepted_at' => now(),
    'terms_version' => 'agent-registration-v1',
    'privacy_accepted_at' => now(),
    'agent_verification_status' => 'pending_review',
    'otp_code' => Hash::make($otp),
    'otp_sent_at' => now(),
]);
```

## Kebutuhan Database Tambahan

Tambahkan migration field berikut ke tabel `users`:

- `terms_accepted_at` nullable timestamp
- `terms_version` nullable string
- `privacy_accepted_at` nullable timestamp
- `agent_verification_status` nullable string
- `agent_verified_at` nullable timestamp

Nilai awal saat register:

- `terms_accepted_at = now()`
- `terms_version = agent-registration-v1`
- `privacy_accepted_at = now()`
- `agent_verification_status = pending_review`
- `agent_verified_at = null`

## Tahapan Pengembangan

### Tahap 1 - Kunci Scope dan Flow

- Tetapkan bahwa halaman ini adalah web public page untuk agent.
- Tetapkan route final: `/agent/register`.
- Tetapkan post-register flow:
  - redirect ke `/agent/verify-otp?email=...`
  - tampilkan flash message sukses
  - user verifikasi OTP dari halaman khusus tersebut
- Tetapkan bahwa password tidak dipakai dan tidak ditampilkan pada MVP ini.
- Tetapkan bahwa agent dibuat dengan status awal `pending_review`.

Output tahap ini:

- Scope tertulis jelas.
- Tidak ada perubahan API existing yang mematahkan client lain.

### Tahap 2 - Buat Route dan Controller

- Tambahkan import controller di `routes/web.php`.
- Tambahkan route `GET /agent/register`.
- Tambahkan route `POST /agent/register`.
- Tambahkan route `GET /agent/verify-otp`.
- Buat `AgentRegistrationController` dengan method:
  - `create()`
  - `store(RegisterAgentWebRequest $request)`
  - `verifyOtp()`

Acceptance criteria:

- `GET /agent/register` menampilkan halaman.
- `POST /agent/register` tidak memakai controller API existing.
- `GET /agent/verify-otp?email=...` menampilkan halaman input OTP.

### Tahap 3 - Buat Form Request

- Buat `RegisterAgentWebRequest`.
- Implement rules sesuai bagian Request Validation.
- Tambahkan custom messages jika diperlukan agar copy error ramah user.
- Pastikan unique email dan phone scoped ke role `agent`.

Acceptance criteria:

- Form kosong mengembalikan validation errors.
- Email agent duplikat ditolak.
- Phone agent duplikat ditolak.
- File selain JPG/PNG/PDF ditolak.
- File lebih dari 5MB ditolak.
- Consent wajib dicentang.

### Tahap 4 - Implement Upload Dokumen

- Gunakan `$request->file('identity_document')->store(...)`.
- Simpan ke disk yang aman.
- Simpan path ke `users.identity_document_path`.
- Jangan menyimpan dokumen identitas di path publik tanpa kebutuhan bisnis yang jelas.

Acceptance criteria:

- File berhasil tersimpan.
- Path file tersimpan di user.
- File invalid tidak membuat user baru.

### Tahap 5 - Implement User Creation dan OTP

- Gunakan database transaction.
- Generate OTP.
- Create user role agent.
- Generate `agent_code`.
- Kirim OTP registrasi via notification existing.
- Redirect ke halaman verifikasi OTP dengan flash success.

Acceptance criteria:

- User dibuat dengan `role = agent`.
- `agent_code` terisi.
- `password` tetap null.
- `identity_document_path` terisi.
- `terms_accepted_at` terisi.
- `terms_version` terisi `agent-registration-v1`.
- `privacy_accepted_at` terisi.
- `agent_verification_status` terisi `pending_review`.
- `otp_code` terisi.
- `otp_sent_at` terisi.

### Tahap 6 - Buat Blade View

- Buat `resources/views/agent/register.blade.php`.
- Match desain UI:
  - centered layout
  - brand `Kresek.in Agent`
  - form card
  - upload area dashed border
  - checkbox consent
  - tombol kembali dan daftar
- Gunakan old input untuk menjaga value setelah validasi gagal.
- Tampilkan error per field.
- Tambahkan `enctype="multipart/form-data"`.
- Gunakan `@csrf`.

Acceptance criteria:

- Layout responsive di mobile dan desktop.
- Error field mudah dibaca.
- Submit button disabled/loading jika ditambahkan JavaScript.
- Upload input tetap accessible.

### Tahap 7 - Buat Blade View Verifikasi OTP

- Buat `resources/views/agent/verify-otp.blade.php`.
- Tampilkan email tujuan OTP dari query parameter.
- Gunakan hidden value:
  - `role = agent`
  - `type = email`
  - `email = query email`
- Submit OTP ke `POST /api/users/verify-otp` via fetch.
- Setelah sukses:
  - simpan token ke `localStorage`
  - simpan token type ke `localStorage`
  - simpan role ke `localStorage`
  - redirect ke `route('agent.dashboard')`

Acceptance criteria:

- Halaman langsung siap menerima OTP tanpa submit login ulang.
- Error OTP invalid tampil di halaman.
- Setelah OTP valid, user diarahkan ke dashboard agent.

### Tahap 8 - Hubungkan Dari Halaman Login

- Ubah CTA di halaman `/`:
  - dari `Hubungi administrator`
  - menjadi link ke `route('agent.register')`.

Acceptance criteria:

- User bisa menemukan halaman register dari halaman login.
- Tidak ada broken route.

### Tahap 9 - Testing

Tambahkan feature test:

1. `GET /agent/register` berhasil render.
2. Agent dapat register dengan data valid.
3. Register menyimpan user role agent.
4. Register generate `agent_code`.
5. Register menyimpan `identity_document_path`.
6. Register mengirim OTP notification.
7. Register tidak menyimpan password.
8. Register menyimpan `terms_accepted_at`.
9. Register menyimpan `terms_version = agent-registration-v1`.
10. Register menyimpan `privacy_accepted_at`.
11. Register menyimpan `agent_verification_status = pending_review`.
12. Register redirect ke halaman verifikasi OTP.
13. `GET /agent/verify-otp` berhasil render.
14. Register menolak data kosong.
15. Register menolak email duplikat untuk role agent.
16. Register menolak phone duplikat untuk role agent.
17. Register menolak file invalid.
18. Register menolak file lebih dari 5MB.
19. Register mewajibkan consent.

### Tahap 10 - Security dan Compliance

- Tambahkan rate limit untuk `POST /agent/register`.
- Pastikan dokumen identitas tidak bisa diakses tanpa authorization.
- Jangan expose `identity_document_path` di response publik.
- Jangan log file upload atau data dokumen identitas.
- Pertimbangkan malware scan jika upload dokumen akan dipakai production.
- Pastikan kebijakan privasi dan ketentuan layanan sudah tersedia jika checkbox mengarah ke dokumen legal.

## Checklist Implementasi Junior Engineer

Gunakan checklist ini saat mengerjakan:

- [ ] Buat branch feature.
- [ ] Tambahkan route web register agent.
- [ ] Tambahkan route web verifikasi OTP agent.
- [ ] Buat `AgentRegistrationController`.
- [ ] Buat `RegisterAgentWebRequest`.
- [ ] Buat view `resources/views/agent/register.blade.php`.
- [ ] Buat view `resources/views/agent/verify-otp.blade.php`.
- [ ] Tambahkan migration field audit consent dan status review agent.
- [ ] Implement upload dokumen.
- [ ] Implement create user agent dalam database transaction.
- [ ] Pastikan password tidak divalidasi dan tidak disimpan.
- [ ] Simpan consent audit.
- [ ] Simpan `agent_verification_status = pending_review`.
- [ ] Kirim OTP setelah user dibuat.
- [ ] Redirect ke halaman verifikasi OTP setelah registrasi.
- [ ] Tambahkan link dari halaman login ke register agent.
- [ ] Tambahkan feature tests.
- [ ] Jalankan `php artisan test`.
- [ ] Jalankan `./vendor/bin/pint` jika ada perubahan PHP.
- [ ] Review manual UI di desktop dan mobile.

## Risiko Implementasi

1. Field password membuat ekspektasi login password.
   - Mitigasi: password dikeluarkan dari form dan tidak disimpan pada MVP ini. Login tetap OTP.

2. Upload dokumen identitas adalah data sensitif.
   - Mitigasi: gunakan private storage, batasi akses, dan jangan tampilkan URL publik sembarangan.

3. Mengubah endpoint API register existing bisa mematahkan client lain.
   - Mitigasi: buat web flow khusus dan biarkan API existing tetap backward compatible.

4. Consent tanpa audit tidak kuat secara compliance.
   - Mitigasi: simpan timestamp consent dan versi terms/privacy.

5. Self-registration agent bisa membuka spam akun.
   - Mitigasi: rate limit, OTP verification, dan jika perlu approval manual sebelum agent aktif.

6. Agent `pending_review` tetap dapat masuk dashboard.
   - Mitigasi: status `pending_review` disimpan sejak awal. Pembatasan fitur berdasarkan status masuk scope MVP berikutnya.

## Rekomendasi Scope Fase Pertama

Scope paling aman untuk fase pertama:

- Buat halaman `/agent/register`.
- Buat halaman `/agent/verify-otp`.
- Simpan `name`, `email`, `phone`, dan `identity_document_path`.
- Generate `agent_code`.
- Kirim OTP email.
- Redirect ke halaman verifikasi OTP dengan flash message.
- Login tetap memakai OTP existing.
- Simpan consent audit.
- Simpan status review awal `pending_review`.

Scope yang sebaiknya ditunda:

- Login agent dengan password.
- Forgot password/reset password.
- Admin review dokumen agent.
- Pembatasan fitur/halaman berdasarkan status review.
- Dashboard review dokumen agent oleh admin.
- Public URL untuk dokumen identitas.
