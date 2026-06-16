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
   - `Password *`
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

3. API register yang ada belum cocok langsung dengan desain.
   - API hanya menerima salah satu kontak berdasarkan `type`: email atau phone.
   - Desain meminta email dan no WhatsApp sekaligus.
   - API belum menerima `name`.
   - API belum menerima `password`.
   - API belum menerima upload dokumen identitas.
   - API belum menerima consent checkbox.

4. Flow login agent saat ini berbasis OTP dan bearer token di `localStorage`.
   - Desain meminta password, tetapi sistem belum punya flow login password untuk agent.
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
| Password | Required | Field DB ada, login agent masih OTP | Perlu keputusan auth |
| Dokumen identitas | Required upload | Field DB ada | Perlu upload handling dan storage policy |
| Consent | Required | Belum ada field audit | Perlu migration jika consent perlu disimpan |
| Setelah register | Tidak terlihat di desain | Existing API kirim OTP | Perlu tentukan post-register flow |

## Rekomendasi Arsitektur

Rekomendasi terbaik adalah membuat flow web register agent khusus, bukan memaksa endpoint API register existing untuk menanggung kebutuhan desain ini.

Alasannya:

- Endpoint API register existing adalah kontrak umum untuk role `buyer`, `seller`, `agent`, dan `finance`.
- Mengubah request API existing menjadi wajib `name`, `password`, dan file upload berisiko mematahkan mobile/API client.
- Halaman web register agent memiliki kebutuhan khusus: upload dokumen, consent, dan dua kontak sekaligus.

Route yang direkomendasikan:

```php
Route::get('/agent/register', [AgentRegistrationController::class, 'create'])->name('agent.register');
Route::post('/agent/register', [AgentRegistrationController::class, 'store'])->name('agent.register.store');
```

Class yang direkomendasikan:

- `App\Http\Controllers\Web\AgentRegistrationController`
- `App\Http\Requests\RegisterAgentWebRequest`
- `resources/views/agent/register.blade.php`

Storage yang direkomendasikan:

- Simpan dokumen di disk private jika dokumen identitas tidak boleh diakses publik.
- Path contoh: `agent-identities/{user_id}/document.ext`.
- Simpan path ke `users.identity_document_path`.

## Keputusan Produk Yang Harus Dikunci

Sebelum implementasi final, tim perlu mengunci keputusan berikut:

1. Password dipakai untuk apa?
   - Opsi A: password hanya disimpan untuk kebutuhan masa depan, login tetap OTP.
   - Opsi B: agent akan login dengan password.

2. Rekomendasi:
   - Untuk scope pertama, tetap gunakan OTP sebagai login utama.
   - Setelah register berhasil, kirim OTP dan arahkan user ke flow login/verifikasi yang sudah ada.
   - Jangan membangun password login setengah matang tanpa forgot password, reset password, rate limit, dan session strategy.

3. Channel OTP setelah register:
   - Karena form meminta email dan WhatsApp, tentukan default channel.
   - Rekomendasi awal: kirim OTP ke email, karena email required dan existing notification sudah tersedia.
   - WhatsApp tetap disimpan sebagai nomor kontak agent.

4. Status approval agent:
   - Jika dokumen identitas harus direview manual, perlu field status baru seperti `agent_verification_status`.
   - Jika belum ada proses review, agent dapat langsung aktif setelah OTP verified.

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
    'password' => ['required', 'string', 'min:8'],
    'identity_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
    'consent' => ['accepted'],
];
```

Catatan:

- Jika UI menambahkan konfirmasi password, gunakan `confirmed` dan field `password_confirmation`.
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
   - `password`
   - `identity_document_path`
   - `otp_code`
   - `otp_sent_at`
4. Kirim OTP registrasi.
5. Redirect ke `/` atau halaman OTP dengan flash message:
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
    'password' => $validated['password'],
    'identity_document_path' => $identityDocumentPath,
    'otp_code' => Hash::make($otp),
    'otp_sent_at' => now(),
]);
```

## Kebutuhan Database Tambahan

Untuk implementasi minimal, tabel `users` sudah cukup karena `identity_document_path` sudah ada.

Untuk implementasi yang lebih lengkap dan siap audit, tambahkan migration field:

- `terms_accepted_at` nullable timestamp
- `terms_version` nullable string
- `privacy_accepted_at` nullable timestamp
- `agent_verification_status` nullable string, jika dokumen perlu direview
- `agent_verified_at` nullable timestamp, jika ada proses approval

Rekomendasi minimal untuk fase pertama:

- Tambahkan `terms_accepted_at`.
- Tambahkan `privacy_accepted_at`.
- Tunda `agent_verification_status` jika belum ada proses operasional review dokumen.

## Tahapan Pengembangan

### Tahap 1 - Kunci Scope dan Flow

- Tetapkan bahwa halaman ini adalah web public page untuk agent.
- Tetapkan route final: `/agent/register`.
- Tetapkan post-register flow:
  - redirect ke `/`
  - tampilkan flash message sukses
  - user lanjut login/verifikasi OTP dari portal existing
- Tetapkan bahwa password belum dipakai untuk login sampai flow password login dibuat lengkap.

Output tahap ini:

- Scope tertulis jelas.
- Tidak ada perubahan API existing yang mematahkan client lain.

### Tahap 2 - Buat Route dan Controller

- Tambahkan import controller di `routes/web.php`.
- Tambahkan route `GET /agent/register`.
- Tambahkan route `POST /agent/register`.
- Buat `AgentRegistrationController` dengan method:
  - `create()`
  - `store(RegisterAgentWebRequest $request)`

Acceptance criteria:

- `GET /agent/register` menampilkan halaman.
- `POST /agent/register` tidak memakai controller API existing.

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
- Redirect dengan flash success.

Acceptance criteria:

- User dibuat dengan `role = agent`.
- `agent_code` terisi.
- `password` ter-hash.
- `identity_document_path` terisi.
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

### Tahap 7 - Hubungkan Dari Halaman Login

- Ubah CTA di halaman `/`:
  - dari `Hubungi administrator`
  - menjadi link ke `route('agent.register')`, jika product sudah menyetujui self-registration.
- Jika self-registration belum boleh dibuka publik, tetap biarkan CTA email admin dan expose `/agent/register` hanya saat siap.

Acceptance criteria:

- User bisa menemukan halaman register dari halaman login.
- Tidak ada broken route.

### Tahap 8 - Testing

Tambahkan feature test:

1. `GET /agent/register` berhasil render.
2. Agent dapat register dengan data valid.
3. Register menyimpan user role agent.
4. Register generate `agent_code`.
5. Register menyimpan `identity_document_path`.
6. Register mengirim OTP notification.
7. Register menolak data kosong.
8. Register menolak email duplikat untuk role agent.
9. Register menolak phone duplikat untuk role agent.
10. Register menolak file invalid.
11. Register menolak file lebih dari 5MB.
12. Register mewajibkan consent.

Tambahkan unit/feature test tambahan jika migration consent dibuat:

- `terms_accepted_at` terisi saat consent accepted.
- `privacy_accepted_at` terisi saat consent accepted.

### Tahap 9 - Security dan Compliance

- Tambahkan rate limit untuk `POST /agent/register`.
- Pastikan dokumen identitas tidak bisa diakses tanpa authorization.
- Jangan expose `identity_document_path` di response publik.
- Jangan log password atau file upload.
- Pertimbangkan malware scan jika upload dokumen akan dipakai production.
- Pastikan kebijakan privasi dan ketentuan layanan sudah tersedia jika checkbox mengarah ke dokumen legal.

## Checklist Implementasi Junior Engineer

Gunakan checklist ini saat mengerjakan:

- [ ] Buat branch feature.
- [ ] Tambahkan route web register agent.
- [ ] Buat `AgentRegistrationController`.
- [ ] Buat `RegisterAgentWebRequest`.
- [ ] Buat view `resources/views/agent/register.blade.php`.
- [ ] Implement upload dokumen.
- [ ] Implement create user agent dalam database transaction.
- [ ] Kirim OTP setelah user dibuat.
- [ ] Tambahkan flash success setelah registrasi.
- [ ] Tambahkan link dari halaman login jika self-registration sudah disetujui.
- [ ] Tambahkan feature tests.
- [ ] Jalankan `php artisan test`.
- [ ] Jalankan `./vendor/bin/pint` jika ada perubahan PHP.
- [ ] Review manual UI di desktop dan mobile.

## Risiko Implementasi

1. Password membuat ekspektasi login password.
   - Mitigasi: komunikasikan bahwa login tetap OTP untuk fase pertama, atau bangun login password secara lengkap.

2. Upload dokumen identitas adalah data sensitif.
   - Mitigasi: gunakan private storage, batasi akses, dan jangan tampilkan URL publik sembarangan.

3. Mengubah endpoint API register existing bisa mematahkan client lain.
   - Mitigasi: buat web flow khusus dan biarkan API existing tetap backward compatible.

4. Consent tanpa audit tidak kuat secara compliance.
   - Mitigasi: simpan timestamp consent dan versi terms/privacy.

5. Self-registration agent bisa membuka spam akun.
   - Mitigasi: rate limit, OTP verification, dan jika perlu approval manual sebelum agent aktif.

## Rekomendasi Scope Fase Pertama

Scope paling aman untuk fase pertama:

- Buat halaman `/agent/register`.
- Simpan `name`, `email`, `phone`, `password`, dan `identity_document_path`.
- Generate `agent_code`.
- Kirim OTP email.
- Redirect ke halaman login dengan flash message.
- Login tetap memakai OTP existing.
- Simpan consent timestamp jika migration disetujui.

Scope yang sebaiknya ditunda:

- Login agent dengan password.
- Forgot password/reset password.
- Approval dokumen agent.
- Dashboard review dokumen agent oleh admin.
- Public URL untuk dokumen identitas.
