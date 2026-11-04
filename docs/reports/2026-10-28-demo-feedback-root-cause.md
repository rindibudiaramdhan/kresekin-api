# Report Feedback Demo dan Analisis Root Cause

Tanggal analisis: 28 Oktober 2026  
Repository: `kresekin-api`  
Baseline kode: `ca736ae`  
Status: analisis awal untuk tindak lanjut; belum dilakukan perbaikan aplikasi.

## Ringkasan

Dari delapan feedback demo, sebagian merupakan gap backend yang terlihat di kode, sebagian membutuhkan penyesuaian aplikasi dan keputusan bisnis. Feedback 3 dan 7 berhubungan erat, tetapi tetap dicatat terpisah untuk membedakan lokasi toko dan penyaringan daftar toko.

Temuan utama:

1. Validasi duplikasi akun **sudah ada per role**, sehingga email/nomor yang sama masih boleh digunakan pada buyer dan seller. Ini juga merupakan perilaku yang sengaja diuji dalam test saat ini.
2. Area profil pengguna dan area layanan toko disimpan terpisah. API seller menyediakan pembuatan toko, tetapi belum menyediakan update umum toko beserta area layanannya.
3. Daftar toko hanya disaring berdasarkan area bila aplikasi mengirim `housing_area_id`. Backend belum otomatis menggunakan area profil buyer.
4. Relasi agent sudah tersedia, tetapi respons toko seller hanya menyediakan ID dan kode agent, belum nama/kontak agent pendamping.
5. Nomor versi aplikasi mobile berada di luar repository ini. Backend hanya menyediakan versi API melalui healthcheck.
6. Biaya layanan sudah memiliki endpoint dan kolom transaksi, tetapi belum masuk ke perhitungan dan penyimpanan saat checkout.
7. Pembatalan hanya mengubah status pesanan dan riwayat. Belum ada alur refund atau pembatalan penyaluran dana yang terhubung.

**Prioritas tertinggi: pembatalan dan penanganan dana, lalu konsistensi biaya layanan.** Status pesanan dan status keuangan saat ini dapat tidak sejalan.

## Batas analisis dan penanda

- **[BACKEND]**: perubahan diperlukan di repository ini.
- **[LINTAS SCOPE]**: melibatkan backend serta aplikasi, QA, atau operasional.
- **[DI LUAR BACKEND]**: perubahan utama berada di aplikasi mobile, pengelolaan rilis, atau keputusan bisnis.
- **Terbukti di kode**: perilaku/ketiadaan implementasi terlihat dari jalur kode yang ditinjau; bukan bukti bahwa skenario demo sudah direproduksi.
- **Dugaan penyebab demo**: masuk akal berdasarkan kode, tetapi perlu payload, respons, data, atau langkah reproduksi untuk memastikan.

Analisis memakai kode lokal, migrasi, route, ADR, dan test terkait. Build aplikasi demo, log request demo, database demo, bukti transfer, dan hasil diskusi Bimasakti belum diperiksa. Karena itu, lokasi dana aktual dan penyebab pasti setiap kejadian demo tidak dapat dipastikan dari repository saja.

## Daftar feedback dan kepemilikan

| No. | Feedback | Hasil analisis awal | Scope | Prioritas usulan |
| --- | --- | --- | --- | --- |
| 1 | Validasi daftar seller/buyer dengan WA atau email sama | Unique dibatasi per role; normalisasi nomor belum seragam | [LINTAS SCOPE] Backend + produk + aplikasi | P1 |
| 2 | Area seller tidak sesuai setelah simpan/update | Area profil berbeda dengan area toko; endpoint update umum toko belum tersedia | [LINTAS SCOPE] Backend + aplikasi | P1 |
| 3 | Lokasi toko tidak sesuai area buyer | Alamat, koordinat, dan area layanan terpisah; tampilan lokasi perlu ditelusuri | [LINTAS SCOPE] Backend + aplikasi + data | P1 |
| 4 | Informasi agent belum tersedia di seller | ID/kode sudah ada; nama/kontak pendamping belum ada dalam respons daftar toko | [LINTAS SCOPE] Backend + aplikasi | P2 |
| 5 | Nomor versi aplikasi perlu disesuaikan | Versi mobile bukan versi API | [DI LUAR BACKEND] Mobile/release; backend bila versi API ikut diubah | P2 |
| 6 | Bisnis memakai biaya layanan sesuai diskusi Bimasakti | Fondasi biaya layanan ada, penerapan checkout belum lengkap | [LINTAS SCOPE] Backend + produk/Bimasakti + aplikasi | P0 |
| 7 | Area list toko tidak sesuai lokasi buyer | Filter area opsional, tanpa default area buyer | [LINTAS SCOPE] Backend + aplikasi | P1 |
| 8 | Dana setelah buyer/seller membatalkan order | Belum ada refund dan sinkronisasi pembatalan dengan penyaluran dana | [LINTAS SCOPE] Backend + finance + produk/provider | P0 |

P0 berarti perlu diselesaikan sebelum alur uang dipakai secara operasional. Prioritas ini merupakan usulan analisis, bukan komitmen jadwal.

## 1. Validasi duplikasi WA/email saat registrasi

**Root cause yang terbukti:** aturan unique menggabungkan identitas dengan role. `RegisterUserRequest` membatasi pengecekan menggunakan `where('role', $role)`. Migrasi users juga menggunakan unique index `['email', 'role']` dan `['phone', 'role']`. Akibatnya, identitas sama pada buyer dan seller diperbolehkan; identitas sama dalam role yang sama ditolak.

Test `test_registration_allows_same_email_for_different_roles` dan `test_registration_allows_same_phone_for_different_roles` menunjukkan bahwa ini adalah kontrak saat ini. Feedback demo kemungkinan meminta perubahan kontrak menjadi unique lintas role, bukan penambahan validasi dari nol.

**Gap tambahan:** registrasi menerima string nomor tanpa normalisasi ke format kanonis. Bentuk `08...`, `628...`, dan `+628...` dapat berbeda saat dibandingkan walaupun merujuk ke nomor yang sama. Ini dugaan untuk kasus duplikasi nomor, belum bukti penyebab demo. Normalisasi email dan perilaku pembandingan database juga perlu diperiksa jika kasusnya hanya berbeda kapitalisasi.

**Dampak:** pengguna dapat memiliki identitas yang sama pada beberapa role; risiko kebingungan login bila kebutuhan produk menghendaki satu identitas global.

**Tindak lanjut:** sepakati apakah satu identitas boleh menjadi buyer sekaligus seller. Jika tidak, ubah validasi dan constraint database, audit data duplikat sebelum migrasi, serta seragamkan normalisasi nomor pada registrasi, login, dan pembaruan profil. Aplikasi perlu menampilkan pesan validasi backend.

**Kriteria verifikasi:** identitas identik diuji pada role sama dan berbeda; variasi format nomor diuji; pembaruan profil/pemilik toko tidak dapat melewati kebijakan yang disepakati. Jika identitas lintas role memang dilarang, request bersamaan juga tidak boleh membuat duplikat.

Bukti: [RegisterUserRequest](../../app/Http/Requests/RegisterUserRequest.php), [UpdateUserProfileRequest](../../app/Http/Requests/UpdateUserProfileRequest.php), [migrasi users](../../database/migrations/2026_06_03_000002_create_users_table.php), [AuthApiTest](../../tests/Feature/AuthApiTest.php).

## 2. Area seller berubah/tidak sesuai setelah simpan atau update

**Gap yang terbukti:** terdapat dua sumber data area:

- `users.housing_area_id`: area profil pengguna, diperbarui lewat `PUT /api/users/profile`.
- Relasi `housing_area_tenant`: area layanan toko, disimpan lewat `housing_area_ids` pada `POST /api/seller/tenants`.

Update profil hanya menyimpan data user dan tidak menyinkronkan area layanan toko. Route seller belum memiliki `PUT/PATCH /seller/tenants/{id}` untuk update umum toko/area. Route update status toko dan pengaturan ongkir memiliki fungsi tersendiri.

**Dugaan penyebab demo:** form mengirim area ke endpoint profil tetapi kemudian membaca area toko, atau form edit toko tidak memiliki endpoint yang sesuai. Belum bisa memilih salah satu tanpa request dan respons demo. Pada jalur create, kode sudah menjalankan `housingAreas()->sync(...)`; ketidaksesuaian create tidak dapat disimpulkan hanya dari laporan demo.

**Tindak lanjut:** pastikan form tersebut mengubah area profil atau area layanan toko. Untuk edit area layanan, sediakan endpoint update toko dengan otorisasi pemilik dan penyimpanan atomik; aplikasi harus mengirim array ID area serta membaca ulang hasil penyimpanan. Jangan otomatis menyamakan area pribadi seller dengan seluruh area layanan toko tanpa keputusan produk.

**Kriteria verifikasi:** pilih area A/B, simpan, buka ulang form dan daftar toko; hasil tetap A/B. Update menjadi C harus mengganti relasi sesuai permintaan. Seller lain tidak dapat mengubah toko tersebut. Uji create dan update secara terpisah.

Bukti: [UpdateUserProfileController](../../app/Http/Controllers/Api/UpdateUserProfileController.php), [CreateSellerTenantController](../../app/Http/Controllers/Api/CreateSellerTenantController.php), [CreateSellerTenantRequest](../../app/Http/Requests/CreateSellerTenantRequest.php), [routes API](../../routes/api.php), [SellerApiTest](../../tests/Feature/SellerApiTest.php).

## 3. Lokasi toko tidak sesuai dengan area buyer

**Temuan yang terbukti:** lokasi fisik toko (`location`, `latitude`, `longitude`), area layanan toko (`housingAreas`), dan area profil buyer merupakan data yang terpisah. Pembuatan toko memvalidasi keberadaan ID area dan rentang koordinat, tetapi tidak memvalidasi konsistensi lokasi fisik dengan area layanan.

`GET /api/tenants` saat ini memakai `GetBuyerTenantListController`, yang mengembalikan area layanan, tetapi tidak mengembalikan alamat/koordinat/jarak toko. Terdapat `GetTenantListController` dengan marker peta dan perhitungan jarak, tetapi controller tersebut tidak dipakai oleh route daftar toko saat ini. Keberadaan kode peta ini tidak membuktikan fitur peta aktif.

`GET /api/users/profile` mengembalikan koordinat buyer, tetapi `UpdateUserProfileRequest` belum menerima latitude/longitude. Jika aplikasi mengirim koordinat lewat endpoint update profil ini, koordinat tidak termasuk data tervalidasi yang disimpan.

**Dugaan root cause demo:** ketidakselarasan sumber lokasi yang dibaca aplikasi, kontrak respons lokasi yang tidak sesuai kebutuhan layar, atau koordinat buyer lama/tidak tersimpan. Bila maksud feedback adalah toko dari area lain muncul, penyebab filter dijelaskan pada poin 7.

**Tindak lanjut:** bedakan alamat fisik toko, jangkauan layanan, area profil, dan lokasi GPS aktif dalam kontrak aplikasi. Sepakati apakah toko boleh melayani area di luar alamat fisiknya; hal itu bisa valid secara bisnis. Audit ID/label area serta koordinat contoh toko/buyer demo, lalu perbaiki kontrak penyimpanan dan respons yang benar-benar dipakai layar.

**Kriteria verifikasi:** label area sesuai ID sumbernya; marker memakai koordinat toko; alamat layanan buyer yang dipilih menjadi acuan penyaringan/ongkir sesuai keputusan produk. Koordinat yang diperbarui harus terbaca kembali bila fitur tersebut disediakan.

Bukti: [GetBuyerTenantListController](../../app/Http/Controllers/Api/GetBuyerTenantListController.php), [GetTenantListController](../../app/Http/Controllers/Api/GetTenantListController.php), [GetUserProfileController](../../app/Http/Controllers/Api/GetUserProfileController.php), [UpdateUserProfileRequest](../../app/Http/Requests/UpdateUserProfileRequest.php), [CreateSellerTenantRequest](../../app/Http/Requests/CreateSellerTenantRequest.php).

## 4. Informasi agent di sisi seller

**Root cause yang terbukti:** model toko sudah memiliki `agent_user_id` dan relasi agent. Create toko menerima `agent_code` secara opsional. Respons create/list toko seller mengembalikan ID dan kode, tetapi belum nama dan kontak agent pendamping. Profil user mengembalikan `agent_code` milik user tersebut, bukan otomatis agent pendamping toko seller.

**Dampak:** aplikasi tidak mendapat detail yang cukup untuk menampilkan informasi pendamping; toko yang dibuat tanpa kode agent memang dapat memiliki relasi agent kosong.

**Tindak lanjut:** tambahkan objek agent pendamping pada respons toko seller dengan data yang disepakati, misalnya ID, kode, nama, dan kontak yang boleh ditampilkan. Tampilkan kondisi belum memiliki agent. Jika perlu penugasan agent setelah toko dibuat, sediakan alur yang diotorisasi; jangan menganggap seluruh seller pasti sudah terhubung ke agent.

**Kriteria verifikasi:** toko dengan agent menampilkan pendamping yang benar; toko tanpa agent ditangani tanpa error; seller tidak dapat mengambil informasi pendamping toko milik seller lain.

Bukti: [Tenant](../../app/Models/Tenant.php), [CreateSellerTenantRequest](../../app/Http/Requests/CreateSellerTenantRequest.php), [GetSellerTenantListController](../../app/Http/Controllers/Api/GetSellerTenantListController.php), [GetUserProfileController](../../app/Http/Controllers/Api/GetUserProfileController.php).

## 5. Penyesuaian nomor versi aplikasi

**[DI LUAR BACKEND] untuk versi build aplikasi mobile.** Repository ini tidak menyediakan sumber build Android/iOS yang diperiksa untuk menetapkan nomor versi aplikasi demo.

Backend menyediakan `config('api.version')`, bersumber dari `API_VERSION` dengan default `v1`, pada `GET /api/vershealthcheck`. Nilai ini adalah versi API, bukan otomatis versi rilis aplikasi mobile.

**Root cause demo belum dapat ditentukan:** belum diketahui nomor yang tampil, target nomor rilis, dan apakah layar membaca metadata build atau healthcheck API.

**Tindak lanjut:** tim mobile/release menyesuaikan versi tampilan dan nomor build sesuai target rilis. Jika layar ternyata mengambil versi API untuk label aplikasi, perbaiki sumber datanya. Ubah konfigurasi backend hanya bila versi API memang perlu diperbarui.

**Kriteria verifikasi:** versi pada aplikasi terpasang, metadata paket, dan catatan rilis konsisten. Versi API tetap dapat diidentifikasi secara terpisah.

Bukti: [config API](../../config/api.php), [healthcheck route](../../routes/api.php).

## 6. Model bisnis memakai biaya layanan

**Gap backend yang terbukti:** `GET /api/service-fee` membaca konfigurasi biaya layanan; kolom `transactions.service_fee` juga sudah tersedia dengan default 0. Namun checkout menghitung:

`total = max(subtotal + ongkir - diskon, 0)`

Checkout belum menambahkan biaya layanan dan belum mengisi `service_fee` saat membuat transaksi. Total keranjang juga menghitung subtotal + ongkir. Karena itu, menampilkan biaya dari endpoint saja belum berarti biaya tersebut ditagihkan dan tercatat pada order.

Komisi agent masih dihitung dari omzet item transaksi selesai × rate konfigurasi. Penyaluran seller memakai jumlah `line_total` per toko. Belum terlihat aturan pembagian biaya layanan kepada para pihak atau rekonsiliasinya dengan diskon/ongkir.

**Bagian di luar backend:** isi kesepakatan Bimasakti, nominal/persentase, pihak yang menanggung biaya, dasar pengenaan, pembulatan, pembagian pendapatan, dan ketentuan refund merupakan keputusan bisnis. Report ini tidak mengasumsikan hasil diskusi tersebut. Nilai default konfigurasi bukan bukti nilai yang disepakati atau nilai yang aktif di demo.

**Tindak lanjut:** dokumentasikan keputusan Bimasakti, lalu terapkan satu perhitungan server yang konsisten untuk preview/checkout, snapshot transaksi, detail/riwayat, dan laporan finance. Tinjau keterkaitan komisi agent serta payout seller agar tidak menjanjikan pembagian yang tidak didanai.

**Kriteria verifikasi:** biaya sesuai keputusan bisnis, total UI cocok dengan total backend, nilai order tersimpan, dan perubahan konfigurasi tidak mengubah biaya order lama. Rekonsiliasi mencakup barang, ongkir, diskon, biaya layanan, komisi, serta refund. Perlakuan transaksi lama ditentukan secara eksplisit.

Bukti: [GetServiceFeeController](../../app/Http/Controllers/Api/GetServiceFeeController.php), [CheckoutController](../../app/Http/Controllers/Api/CheckoutController.php), [GetCartController](../../app/Http/Controllers/Api/GetCartController.php), [migrasi service fee](../../database/migrations/2026_08_04_000001_add_service_fee_and_item_image_to_transactions.php), [AgentCommissionCalculator](../../app/Support/AgentCommissionCalculator.php), [FinanceDisbursementSyncer](../../app/Support/FinanceDisbursementSyncer.php).

## 7. Area daftar toko tidak mengikuti lokasi buyer

**Root cause yang terbukti:** `housing_area_id` pada endpoint daftar toko bersifat opsional. Query baru menambahkan `whereHas('housingAreas', ...)` bila parameter dikirim. Tanpa parameter, tidak ada default ke `request->user()->housing_area_id`, tidak ada penyaringan GPS/radius, dan daftar diurutkan berdasarkan waktu pembuatan.

Parameter area juga tidak diwajibkan sama dengan area profil buyer. Ini dapat mendukung pemilihan area lain, tetapi harus ditetapkan sebagai perilaku produk. Test saat ini membuktikan filter eksplisit, belum menjamin daftar otomatis mengikuti area profil.

**Dugaan penyebab demo:** aplikasi tidak mengirim area, memakai area lama, atau mengirim ID berbeda dari lokasi yang ditampilkan. Celah backend yang memungkinkan daftar tanpa batas area sudah terlihat; pemicu aktual perlu dibuktikan dari request demo.

**Tindak lanjut:** tetapkan sumber area aktif. Jika kebutuhan adalah otomatis mengikuti profil, gunakan area profil sebagai default di backend dan tangani buyer tanpa area secara jelas. Jika pemilihan area lain diperbolehkan, kontrak parameter serta label UI harus konsisten. Penyaringan GPS memerlukan aturan tambahan; koordinat tidak otomatis setara dengan ID area perumahan.

**Kriteria verifikasi:** buyer area A hanya melihat toko yang melayani A ketika memakai default; toko A+B tetap muncul; toko khusus B tidak muncul. Uji buyer tanpa area, perpindahan area, parameter eksplisit, dan data relasi toko yang kosong.

Bukti: [GetBuyerTenantListController](../../app/Http/Controllers/Api/GetBuyerTenantListController.php), [TenantApiTest](../../tests/Feature/TenantApiTest.php), [routes API](../../routes/api.php).

## 8. Pembatalan buyer/seller dan posisi dana

**Root cause yang terbukti:** pembatalan mengubah status transaksi, alasan, dan riwayat, tetapi tidak menjalankan refund, tidak membatalkan record penyaluran dana, dan tidak membuat pencatatan kewajiban pengembalian. Model penyaluran hanya memiliki status menunggu pembayaran, pembayaran dikonfirmasi, dan disalurkan ke seller.

Buyer diizinkan membatalkan pada status menunggu pembayaran, diterima toko, diproses, dalam perjalanan, dan siap diambil. Seller dapat membatalkan order aktif sebelum status terminal. Kedua jalur tidak menghubungkan pembatalan dengan status uang.

**Gap pengaman terkait:** endpoint penyaluran hanya menolak record yang masih menunggu pembayaran buyer; belum memeriksa apakah order dibatalkan atau sudah selesai. Endpoint konfirmasi pembayaran juga langsung menetapkan status diterima toko tanpa guard status sebelumnya, sehingga order dibatalkan dapat berubah menjadi diterima melalui jalur ini. Ini adalah perilaku yang terlihat pada kode, belum bukti bahwa kejadian tersebut terjadi saat demo.

### Jawaban awal: uangnya ke mana?

| Kondisi | Perilaku yang terlihat di backend | Yang perlu dipastikan secara operasional |
| --- | --- | --- |
| Buyer belum membayar lalu membatalkan | Status order dibatalkan; belum ada refund | Pastikan memang tidak ada dana masuk, termasuk transfer terlambat |
| Buyer sudah membayar, belum disalurkan ke seller | Order dapat dibatalkan; status penyaluran tidak otomatis dibatalkan; tidak ada refund otomatis | Rekening penerima dan mutasi dana harus diperiksa finance; catat kewajiban refund |
| Dana sudah disalurkan lalu order aktif dibatalkan | Tidak ada reversal/recovery otomatis pada jalur pembatalan | Konfirmasi transfer aktual ke seller, lalu jalankan mekanisme pengembalian yang disepakati |
| Finance mencoba penyaluran setelah order dibatalkan | Guard status pembatalan belum ada pada endpoint penyaluran | Perlu blokir dan pengujian sebelum alur dipakai untuk dana nyata |
| Pembayaran datang setelah pembatalan | Konfirmasi finance belum melindungi status canceled | Perlu rekonsiliasi pembayaran terlambat tanpa mengaktifkan kembali order secara keliru |

**Tidak dapat disimpulkan bahwa uang hilang, otomatis kembali, atau pasti masuk rekening tertentu.** ADR finance menetapkan proses manual lebih dulu; controller konfirmasi/penyaluran mencatat status internal, bukan menjalankan transfer bank. Status “disalurkan” saja bukan bukti transfer aktual.

**Tindak lanjut backend:** pisahkan status order, pembayaran, penyaluran, dan refund; buat pencatatan refund dengan nominal, tujuan, status, referensi, dan audit. Hubungkan pembatalan dengan penahanan/pembatalan payout dan guard konfirmasi pembayaran. Lindungi transisi bersamaan serta pengulangan request agar tidak terjadi pembayaran/refund ganda. Cakup pembatalan oleh buyer maupun seller.

**Tindak lanjut di luar backend:** finance merekonsiliasi order demo yang dibatalkan dengan bukti pembayaran/penyaluran. Produk dan Bimasakti menetapkan siapa yang mengembalikan, ke mana, SLA, perlakuan biaya layanan/ongkir, serta skenario dana sudah diterima seller. Integrasi provider mengikuti layanan yang benar-benar disepakati; refund manual tetap membutuhkan pencatatan dan audit.

**Kriteria verifikasi:** batalkan sebelum bayar, setelah bayar, dan setelah penyaluran; uji pembayaran terlambat, pembatalan ulang, serta pembatalan bersamaan dengan konfirmasi/payout. Order canceled tidak boleh disalurkan lewat alur biasa atau diaktifkan kembali oleh konfirmasi pembayaran tanpa aturan eksplisit. Setiap dana masuk memiliki penyelesaian yang dapat ditelusuri; nominal dan status refund tidak sekadar diturunkan dari status pesanan.

Bukti: [CancelBuyerTransactionController](../../app/Http/Controllers/Api/CancelBuyerTransactionController.php), [UpdateSellerOrderStatusController](../../app/Http/Controllers/Api/UpdateSellerOrderStatusController.php), [Transaction](../../app/Models/Transaction.php), [ConfirmFinanceBuyerPaymentController](../../app/Http/Controllers/Api/ConfirmFinanceBuyerPaymentController.php), [DisburseFinanceTransactionController](../../app/Http/Controllers/Api/DisburseFinanceTransactionController.php), [FinanceTransactionDisbursement](../../app/Models/FinanceTransactionDisbursement.php), [ADR-006](../adr/006-finance-workflow-manual-first.md).

## Urutan tindak lanjut

1. **Backend + finance:** rekonsiliasi kasus pembatalan demo; lengkapi guard pembayaran/payout serta desain pencatatan refund.
2. **Produk/Bimasakti + backend:** finalisasi aturan biaya layanan dan pembatalan; lengkapi perhitungan checkout serta laporan keuangan.
3. **Backend + aplikasi:** perbaiki kontrak update area seller, sumber lokasi, dan default filter area buyer sebagai satu rangkaian konsistensi data.
4. **Produk + backend:** putuskan kebijakan identitas lintas role, lalu sesuaikan validasi, normalisasi, database, dan penanganan data lama.
5. **Backend + aplikasi:** lengkapi informasi agent pendamping.
6. **Mobile/release:** sesuaikan nomor versi aplikasi dan pastikan build yang diuji memakai backend yang sesuai.

## Data untuk memastikan root cause demo

- Versi/build aplikasi, environment backend, serta waktu kejadian demo.
- Endpoint, payload yang sudah disamarkan, respons, dan ID request untuk registrasi, simpan area, serta daftar toko.
- Contoh ID area yang dipilih dan yang muncul kembali, ID toko, serta area profil buyer dan area layanan toko.
- Untuk pembatalan: ID order, urutan status, metode pembayaran, bukti dana masuk, serta bukti penyaluran jika ada. Hindari menaruh data rekening atau identitas lengkap pada report umum.
- Keputusan tertulis biaya layanan dan pembatalan hasil diskusi Bimasakti.

## Verifikasi analisis

Review statis dilakukan pada jalur registrasi, profil, create/list toko seller, list toko buyer, checkout, komisi, pembatalan, konfirmasi pembayaran, dan penyaluran finance. Tidak dilakukan perubahan kode aplikasi atau migrasi database.

Test dijalankan dengan SQLite `:memory:` pada environment testing, tanpa menggunakan database demo/produksi:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' MAIL_MAILER=array QUEUE_CONNECTION=sync NIGHTWATCH_ENABLED=false vendor/bin/phpunit tests/Feature/AuthApiTest.php tests/Feature/TenantApiTest.php tests/Feature/SellerApiTest.php tests/Feature/ServiceFeeApiTest.php tests/Feature/TransactionApiTest.php tests/Feature/FinanceApiTest.php --no-progress
```

Hasil: **98 test, 791 assertions, 94 lolos, 4 gagal**. Keempat kegagalan berada pada `TenantApiTest`:

| Test | Hasil aktual | Implikasi |
| --- | --- | --- |
| `test_authenticated_user_can_get_paginated_tenant_list_with_default_limit` | Assertion latitude mengharapkan `-6.2`, tetapi hasilnya `null` karena field tidak tersedia | Kontrak test lokasi belum sesuai dengan respons controller yang aktif |
| `test_tenant_without_coordinates_has_null_distance_and_is_sorted_last` | Item pertama `Toko Tanpa Koordinat`, sementara test mengharapkan `Toko Dekat` | Ekspektasi urutan jarak tidak dipenuhi route daftar toko saat ini |
| `test_tenant_list_requires_authentication` | Pesan aktual `Tidak terautentikasi.`, ekspektasi `Unauthenticated.` | Perbedaan kontrak bahasa pesan, bukan bukti bug area |
| `test_tenant_categories_requires_authentication` | Pesan aktual `Tidak terautentikasi.`, ekspektasi `Unauthenticated.` | Perbedaan kontrak bahasa pesan, bukan bukti bug area |

Dua kegagalan pertama memperkuat temuan poin 3 bahwa respons dan urutan daftar toko aktif berbeda dari ekspektasi lokasi/jarak dalam test. Hasil ini belum membuktikan bahwa layar demo memakai field yang sama. Tidak ada kode aplikasi yang diubah selama analisis, sehingga kegagalan tersebut merupakan kondisi yang ditemukan pada baseline lokal.

Test yang lolos memverifikasi kontrak yang sudah diuji saat ini; tidak membuktikan bahwa gap refund, default area, atau biaya layanan checkout telah terselesaikan. Khusus biaya layanan, pengujian endpoint pembacaan biaya tidak setara dengan pengujian penerapan biaya pada checkout.
