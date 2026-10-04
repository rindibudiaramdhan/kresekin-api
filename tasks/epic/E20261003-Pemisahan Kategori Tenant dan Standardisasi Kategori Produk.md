# E20261003 — Pemisahan Kategori Tenant dan Standardisasi Kategori Produk

- Tanggal: 2026-10-03
- Status: Planned — rencana disetujui, implementasi belum dimulai
- Jenis: Epic
- Target: kategori hanya dipilih pada produk; tenant tidak memiliki kategori sendiri.

## Latar belakang dan kondisi saat ini

Tenant adalah toko/merchant milik akun seller. Akun seller dan tenant merupakan entitas berbeda; registrasi serta edit profil akun seller tidak membutuhkan kategori.

Temuan dari repository:

1. `POST /api/seller/tenants` mewajibkan `category_id` melalui `CreateSellerTenantRequest`. Controller mengambil master kategori dan menyimpan nama ke `tenants.category` serta ID ke `tenants.product_category_id`.
2. `tenants.category` tidak nullable; `tenants.product_category_id` nullable dan memiliki foreign key. Menghapus validasi saja akan membuat penyimpanan tenant tanpa kategori gagal.
3. Belum ada endpoint API edit tenant. `AgentTenantRequest` mewajibkan teks `category`, tetapi tidak ditemukan pemakaiannya pada route/controller saat pemeriksaan.
4. Create produk mewajibkan `product_category_id`, tetapi controller hanya menyimpan nama master ke `products.category` dan membuang ID. Model/tabel produk belum memiliki relasi ID kategori.
5. Edit produk API, form web seller, dan admin Filament masih menggunakan teks kategori dari `Tenant::CATEGORIES`.
6. Filter buyer sudah memilih tenant melalui kategori produknya, tetapi pencocokan masih memakai nama kategori. Response tenant masih membaca kategori tunggal tenant beserta metadata tampilannya.
7. Daftar/detail seller agent dan dashboard agent/finance masih menampilkan kategori tenant.
8. `Tenant::CATEGORIES` dan `Tenant::categoryUiMetadata()` juga dipakai fitur produk. Keduanya tidak boleh langsung dihapus sebelum seluruh pemakai dipindahkan.
9. `GetTenantListController` masih membaca/filter kategori tenant, tetapi tidak terdaftar pada route API saat pemeriksaan.

## Keputusan yang disepakati

1. Tambah/ubah tenant tidak meminta, menyimpan, atau mengubah kategori tenant.
2. Kategori hanya dimiliki produk. Satu produk memiliki satu kategori master; satu tenant dapat memiliki banyak kategori melalui produknya.
3. `product_categories` menjadi sumber kategori bersama untuk API, web seller, dan admin. Daftar konstanta pada `Tenant` bukan sumber pilihan kategori lagi.
4. Create dan edit produk menggunakan field yang sama: `product_category_id`, berupa UUID yang terdapat pada master.
5. ID kategori disimpan sebagai relasi pada produk. Nama, slug, gambar, dan metadata kategori dibaca dari master untuk tampilan.
6. Kategori master yang masih digunakan produk tidak boleh dihapus. Tidak ada cascade delete produk atau pengosongan kategori otomatis.
7. Migrasi dilakukan bertahap dengan kompatibilitas client lama, kemudian kolom dan kontrak lama dibersihkan.
8. Endpoint edit tenant baru bukan bagian epic ini. Jika dibuat pada pekerjaan terpisah, kontraknya mengikuti aturan tenant tanpa kategori.

## Cakupan dan batasan

Dalam cakupan: validasi/controller tenant, model dan migration, migrasi kategori produk lama, create/edit/read/filter produk, response tenant dan dashboard terdampak, form web/admin dalam repository, seeder/fixture, dokumentasi API, regression test, serta koordinasi kontrak dengan client eksternal.

Di luar cakupan: endpoint edit tenant baru, kategori multi-pilihan pada satu produk, hierarki kategori, fitur pengelolaan master kategori baru, perubahan registrasi/profil seller, dan perubahan aturan harga, stok, satuan, transaksi, rating, ownership, atau visibility produk. Pembaruan aplikasi frontend/mobile di repository lain dicatat sebagai dependensi rollout.

## Kontrak target

### Tenant

- `POST /api/seller/tenants` tidak mewajibkan `category_id` atau `category`.
- Field kategori tenant yang masih dikirim client lama diabaikan dan tidak disimpan. Perilaku ini berlaku juga setelah cleanup; jangan menambahkan validasi yang menolak payload lama tanpa keputusan kontrak terpisah.
- Field tenant lain tetap mengikuti validasi yang berlaku.
- Hapus field kategori tunggal dari response tenant: `category_id`, `category`, `category_master`, `category_slug`, `category_icon_key`, `category_background_color`, dan `category_icon_color`.
- Daftar kategori tenant, jika dibutuhkan tampilan, berasal dari relasi produk, bersifat unik, dan kosong jika belum ada produk. Tidak memilih satu kategori sebagai kategori utama secara otomatis.
- Pertahankan bentuk field agregat `product_categories` dan `product_category_slugs` yang sudah tersedia; ubah sumbernya ke master melalui ID produk. Perubahan bentuk response agregat membutuhkan dokumentasi tersendiri.
- Pertahankan perilaku visibility/soft delete/filter produk saat ini. Jangan memperluas produk yang ditampilkan melalui refactor ini.
- Subtitle kartu toko yang sekarang memakai kategori tenant perlu dihapus/disesuaikan oleh client, tanpa membuat kategori tenant pengganti.

### Produk

| Operasi | Kontrak target |
| --- | --- |
| `POST /api/seller/products` | Wajib `product_category_id`; UUID valid pada master |
| `PUT /api/seller/products/{id}` | Wajib `product_category_id`; validasi field lainnya tetap mengikuti kontrak update saat ini |
| `POST /api/seller/products/{id}` | Alias update untuk multipart; aturan kategori sama dengan PUT |
| Response create/edit/list/detail produk | Tambahkan `product_category_id` dan `product_category` berisi `id`, `name`, `slug` |
| Field response `category` | Tetap tersedia sebagai nama master selama transisi, bukan input/sumber penyimpanan utama |
| Form web seller/admin | Pilihan dari master dan menyimpan ID |

Contoh bagian payload create/edit:

```json
{
  "product_category_id": "<uuid-master-kategori>"
}
```

Contoh bagian response produk selama transisi:

```json
{
  "product_category_id": "<uuid-master-kategori>",
  "product_category": {
    "id": "<uuid-master-kategori>",
    "name": "Nama kategori",
    "slug": "nama-kategori"
  },
  "category": "Nama kategori"
}
```

Kategori bernama baru di master harus bisa digunakan tanpa menambahkan konstanta kode. Perubahan nama master langsung tercermin pada response produk; slug response berasal dari master, bukan hasil slugifikasi nama produk/kategori tersimpan. Foreign key kategori menggunakan pembatasan penghapusan; kategori yang dipakai produk soft-deleted juga tetap terlindungi sampai produk tersebut dihapus permanen atau dipindahkan secara eksplisit.

### Kompatibilitas edit produk lama

1. Selama transisi, jika ID tidak dikirim, input teks `category` boleh dipetakan ke master yang valid dan unik.
2. Jika ID dikirim, ID menjadi acuan; teks kategori tidak boleh menimpa ID. ID invalid tetap ditolak meskipun teks valid.
3. Teks tidak ditemukan/ambigu ditolak dengan validasi 422; tidak membuat master baru atau memakai kategori default otomatis.
4. Setelah seluruh client beralih, hapus fallback teks input dan wajibkan ID pada create/edit. Field nama `category` pada response dapat dipertahankan sebagai nilai turunan untuk kompatibilitas, meskipun kolom database lama sudah dihapus.

## Filter, agregasi, dan endpoint kategori

- Pertahankan `GET /api/product-categories` sebagai sumber pilihan kategori produk.
- Pertahankan `GET /api/tenants/{tenantId}/product-categories`; ambil master kategori unik melalui relasi ID produk, menggantikan pencocokan nama.
- Pertahankan parameter slug yang digunakan client: `category` pada daftar produk dan `product_category` pada daftar tenant buyer. Resolusi slug mengambil master lalu filter berdasarkan ID.
- Tenant dengan produk kategori A dan B muncul pada filter A maupun B. Tenant tanpa produk tetap muncul pada daftar tanpa filter, tetapi tidak pada filter kategori tertentu.
- Pertahankan perilaku agregasi daftar buyer saat filter aktif: kategori/count berasal dari kumpulan produk yang sudah difilter seperti saat ini. Mengubahnya menjadi total seluruh toko di luar scope.
- Deprecate `GET /api/tenants/categories`; koordinasikan perpindahan client ke master produk sebelum menghapus route/controller/test endpoint lama.
- Bersihkan controller tenant lama yang tidak terdaftar; jika dipertahankan, tidak boleh bergantung pada kategori tenant.
- Metadata ikon/warna dipindahkan ke pengelola kategori produk, memakai slug master sebagai identitas stabil dan fallback netral untuk kategori baru. Tidak perlu menambah fitur konfigurasi metadata baru pada epic ini.

## Rancangan database dan migrasi data

### Tahap ekspansi

1. Buat migration baru, jangan mengedit migration historis yang sudah dijalankan.
2. Ubah `tenants.category` menjadi nullable; pertahankan sementara kolom tenant lama untuk kompatibilitas deployment.
3. Tambahkan `products.product_category_id` nullable, berindeks, dengan foreign key ke `product_categories` yang membatasi penghapusan.
4. Tambahkan relasi model produk. Selama reader lama masih aktif, writer menyimpan ID dan mengisi kolom teks produk dari master untuk kompatibilitas; ID tetap menjadi acuan utama.

### Backfill produk

1. Inventarisasi kategori teks dan master, termasuk produk soft-deleted, sebelum mengisi ID.
2. Cocokkan nama persis terlebih dahulu; pencocokan normalisasi spasi/huruf hanya diterima jika hasilnya unik.
3. Hasilkan laporan jumlah cocok, tidak cocok, ambigu, dan ID master tujuan. Laporan tidak perlu memuat data personal seller.
4. Kategori tidak cocok/ambigu diselesaikan lewat pemetaan eksplisit yang direview saat follow-up. Tidak mengambil kategori tenant sebagai fallback.
5. Backfill harus dapat dijalankan ulang tanpa menimpa ID produk yang sudah valid, memakai batch untuk menghindari transaksi/lock besar.
6. Rekonsiliasi jumlah produk dan cek orphan/null ID. Cleanup diblokir sampai seluruh produk yang tersimpan memiliki ID valid.
7. Selama backfill belum selesai, reader boleh memakai fallback teks untuk record lama; sesudah rekonsiliasi, seluruh reader/filter menggunakan relasi ID.

### Tahap cleanup

1. Setelah seluruh writer/reader/client siap, jadikan `products.product_category_id` non-nullable.
2. Hapus kolom `products.category`; response nama kategori tetap dapat diturunkan dari master.
3. Hapus foreign key dan kolom `tenants.product_category_id`, serta kolom `tenants.category`.
4. Hapus fillable/relasi kategori tunggal tenant dan konstanta/helper lama setelah tidak ada pemakai.
5. Sesuaikan seeder, factory/fixture, serta dokumentasi agar instalasi baru langsung memakai model target.

Sebelum drop kolom, simpan backup/pemetaan data lama sesuai proses operasional deployment. Migration `down()` dapat memulihkan struktur, tetapi tidak otomatis memulihkan nilai kategori tenant yang sudah dihapus. Rollback penuh setelah cleanup membutuhkan restore backup atau prosedur pemulihan yang disiapkan; jangan mengklaim drop kolom sepenuhnya reversible.

## Peta perubahan awal

| Area | File/komponen utama |
| --- | --- |
| Create tenant | `app/Http/Requests/CreateSellerTenantRequest.php`, `app/Http/Controllers/Api/CreateSellerTenantController.php` |
| Request tenant lama | `app/Http/Requests/AgentTenantRequest.php` |
| Model/data | `app/Models/Tenant.php`, `Product.php`, `ProductCategory.php`, migration baru, seeders |
| Create/edit produk API | `CreateSellerProductRequest.php`, `UpdateSellerProductRequest.php`, `CreateSellerProductController.php`, `UpdateSellerProductController.php` |
| Web seller | `SellerProductRequest.php`, `Controllers/Web/SellerProductController.php`, view form produk |
| Admin | `app/Filament/Resources/ProductResource.php` |
| Read/filter API | Controller list/detail produk, `GetBuyerTenantListController`, `GetSellerTenantListController`, `GetTenantProductCategoryListController` |
| Endpoint lama | `GetTenantCategoriesController`, `GetTenantListController`, `routes/api.php` |
| Agent/finance | `GetAgentSellerListController`, `GetAgentSellerDetailController`, `AgentDashboardAggregator`, `AgentManagedUmkmPerformanceQuery`, `FinanceDashboardAggregator` |
| Verifikasi | `tests/Feature/SellerApiTest.php`, `TenantApiTest.php`, `TenantProductCategoryApiTest.php`, test web/admin/dashboard terkait |

Daftar ini titik awal, bukan batas pencarian. Saat implementasi, audit seluruh referensi kategori tenant, `Tenant::CATEGORIES`, helper metadata, pencocokan kategori berbasis nama, dan dokumentasi API. Selaraskan ADR-012 agar menjelaskan master database untuk kategori produk tanpa mengubah aturan domain lain.

## Breakdown pekerjaan dan dependensi

- [ ] T1 — Audit kontrak/read/write dan client terdampak; catat daftar final, lokasi dokumentasi API, serta jadwal penghentian kontrak lama.
- [ ] T2 — Migration ekspansi, relasi model produk, dan alat backfill beserta laporan pemetaan. Bergantung T1.
- [ ] T3 — Samakan create/edit produk API, writer kompatibel, response ID/master, form web dan admin. Bergantung T2.
- [ ] T4 — Jalankan/review backfill dan selesaikan kategori tidak cocok/ambigu. Bergantung T2–T3.
- [ ] T5 — Alihkan reader/filter/agregasi/metadata ke relasi master; kategori nama response tetap turunan. Bergantung T3–T4.
- [ ] T6 — Lepas input dan pembacaan kategori tenant pada API/dashboard; tangani request/controller lama. Bergantung T2 dan T5.
- [ ] T7 — Perbarui dokumentasi dan koordinasikan client: form tenant tanpa kategori, produk berbasis ID, kartu toko tanpa kategori tunggal, endpoint master pengganti. Bergantung kontrak T1; harus selesai sebelum cleanup.
- [ ] T8 — Regression test dan verifikasi tahap transisi, termasuk ownership dan record lama. Dilakukan bersama T2–T7.
- [ ] T9 — Setelah readiness gate terpenuhi, migration cleanup, hapus fallback input teks/endpoint lama, dan verifikasi instalasi baru serta upgrade database. Bergantung T4–T8.

## Urutan rollout dan readiness gate

1. Deploy migration ekspansi sebelum kode yang membuat tenant tanpa kategori atau menulis ID produk.
2. Deploy writer/kontrak produk kompatibel, kemudian backfill dan rekonsiliasi data.
3. Deploy reader/filter berbasis ID serta backend tenant tanpa kewajiban/pembacaan kategori. Seluruh response path yang memanggil helper dengan kategori tenant harus diperbaiki sebelum tenant baru tanpa kategori dibuat.
4. Rilis client dengan pilihan kategori hanya pada produk. Koordinasikan penghapusan field response tenant agar client lama tidak rusak; bila masih membutuhkan field tersebut, tunda removal atau gunakan lapisan kompatibilitas sementara dengan bentuk yang disepakati. Jangan mengarang kategori tunggal dari produk.
5. Catat bukti client siap, semua produk terpetakan, tidak ada writer/reader lama, dan endpoint kategori tenant tidak digunakan lagi.
6. Backup lalu deploy cleanup. Verifikasi API dan migrasi sesudah deployment.

Tanggal cutover, durasi kompatibilitas, pemetaan data ambigu, dan bentuk lapisan kompatibilitas response tenant (jika diperlukan) ditentukan pada follow-up berdasarkan inventaris client/data. Ini keputusan operasional yang masih terbuka, bukan perubahan pada keputusan domain yang sudah disetujui.

## Acceptance criteria

- [ ] Create tenant tanpa field kategori berhasil; payload lama yang mengirim kategori tidak menyimpan kategori tenant.
- [ ] Tenant tanpa produk dapat ditampilkan tanpa error, kategori agregat kosong, dan tidak muncul pada filter kategori tertentu.
- [ ] Response tenant/seller/agent/finance dan dashboard target tidak bergantung pada kolom kategori tenant.
- [ ] Create/edit produk API, web, dan admin menyimpan ID master yang sama; kategori baru di master tidak membutuhkan perubahan konstanta.
- [ ] ID invalid ditolak 422; fallback teks hanya berlaku selama transisi dan menolak nama tidak cocok/ambigu.
- [ ] ID valid menjadi acuan jika payload juga mengirim teks; ID invalid tidak tertutupi fallback teks.
- [ ] Response create/edit/list/detail produk menyediakan ID dan objek master yang konsisten; form edit memilih kategori tersimpan.
- [ ] Rename kategori master tercermin pada response dan tidak memutus relasi/filter berbasis ID. Slug response memakai slug master.
- [ ] Penghapusan master kategori yang masih digunakan produk diblokir.
- [ ] Tenant dengan produk dari kategori A/B muncul pada filter A/B; hasil filter tidak terpengaruh kategori tenant lama.
- [ ] Endpoint kategori produk per tenant mengambil master unik melalui relasi dan tetap mengikuti visibility saat ini.
- [ ] Backfill mencakup semua record produk, dapat diulang, tidak menimpa ID valid, dan tidak meninggalkan null/orphan sebelum cleanup.
- [ ] Ownership seller, autentikasi, serta status/soft-delete produk tetap sesuai perilaku yang berlaku.
- [ ] Seeder/fixture dan fresh migration berhasil; upgrade database dengan data lama terverifikasi.
- [ ] Dokumentasi payload/response, deprecation, rollout, dan pemulihan diperbarui; kesiapan client dicatat sebelum removal.
- [ ] Tidak ada referensi aktif ke kategori tenant atau sumber kategori `Tenant::CATEGORIES` setelah cleanup.

## Risiko dan mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Insert tenant gagal karena kolom wajib | Migration nullable mendahului writer baru |
| Response/helper error pada kategori null | Alihkan seluruh reader tenant sebelum create tanpa kategori diaktifkan |
| Client masih membaca field/endpoint lama | Inventaris client, fase deprecation, readiness gate sebelum removal |
| Nama kategori produk tidak cocok/ambigu | Laporan dan mapping eksplisit; jangan isi default otomatis |
| Writer web/admin lama membuat produk tanpa ID | Migrasikan seluruh writer sebelum non-null dan cleanup |
| Filter tidak konsisten akibat rename | Filter berdasarkan ID; slug diresolusikan lewat master |
| Data kategori hilang saat drop kolom | Backup/pemetaan sebelum cleanup dan prosedur restore |
| Query relasi menambah N+1 | Eager loading master kategori dan indeks foreign key; cek list/dashboard terkait |

## Verification checklist dan Definition of Done

- [ ] Jalankan Laravel Pint pada perubahan PHP.
- [ ] Jalankan feature/unit test yang relevan untuk tenant, produk, kategori per tenant, seller web/admin, dan dashboard agent/finance.
- [ ] Tambahkan regression test bermakna untuk kontrak baru, transisi, backfill, rename kategori, pembatasan delete master, dan filter multi-kategori tenant.
- [ ] Verifikasi migration upgrade dengan data lama serta fresh migration/seeding pada database pengujian.
- [ ] Verifikasi response menggunakan allowlist field dan eager loading yang sesuai.
- [ ] Catat hasil pengujian, hasil rekonsiliasi, status client, dan keputusan cutover di tiket/subtask.
- [ ] Seluruh acceptance criteria selesai; cleanup serta dokumentasi final selesai. Epic tidak ditutup hanya karena validasi create tenant sudah dihapus.

## Catatan follow-up

Tiket ini mendokumentasikan rencana yang disetujui. Pembuatan tiket tidak menjalankan migration, backfill, deploy, atau perubahan kode aplikasi. Subtask dan bukti implementasi berikutnya ditautkan di bagian ini.

- Subtask: K01 selesai sebagai audit backend/API contract; K02–K13 tetap Planned pada [timeline dan indeks tiket](../tasks/E20261003/timeline.md). PR implementasi aplikasi belum tersedia.
- Hasil audit backend/API contract: [K01 selesai](../DONE/E20261003/K01-Audit-kontrak-dan-keputusan-transisi.md) pada 2026-10-04; [matriks](../DONE/E20261003/K01-Matriks-audit-kontrak.md) tersedia. Bukti pengguna: PostgreSQL 18.6 (4e955f5), 17 produk (10 non-soft-deleted, 7 soft-deleted). Client mobile seller Android/iOS; PIC mobile/mapping/operations/release: Rindi. Bukti versi/uji mobile dikecualikan dari penutupan audit K01, bukan diklaim terverifikasi; readiness produksi tetap mengikuti gate epic.
- Cutover/deprecation: belum ditentukan.
- Hasil verifikasi: belum tersedia.


## Follow-up 2026-10-03 — Breakdown dan baseline timeline

Rekomendasi analisis disetujui untuk perencanaan tiket. [Timeline](../timeline.md) mencatat baseline 5 Oktober–27 November 2026, asumsi kapasitas, keputusan transisi, dependensi dan gate. Tanggal bersyarat pada kesiapan data/client dan tidak menggantikan readiness gate epic.

Breakdown T1–T9 di atas tetap menjadi pengelompokan awal; eksekusi memakai K01–K13 berikut agar implementasi, operasi produksi, removal kontrak dan cleanup dapat direview terpisah:

| Kelompok awal | Tiket eksekusi |
| --- | --- |
| T1 audit | K01 |
| T2 ekspansi dan alat backfill | K02, K05 |
| T3 writer API/web/admin | K03, K04 |
| T4 backfill operasional | K06 |
| T5 reader/filter/metadata | K07, K08 |
| T6 tenant/dashboard | K09 |
| T7 dokumentasi/client | K10 |
| T8 regression dan readiness | Test di setiap tiket; verifikasi lintas fitur K11 |
| T9 cleanup | K12 removal kontrak, kemudian K13 cleanup schema |

Keputusan operasional: fallback teks hanya edit saat key ID tidak ada; dua siklus rilis client dan observasi nol penggunaan 14 hari sebelum removal; minimal dua hari kerja observasi removal sebelum cleanup. Bentuk response tenant sementara diputuskan lewat audit dan uji client, tanpa kategori utama produk. PIC personel, mapping aktual, jadwal client terverifikasi dan bukti gate tetap harus diisi saat pelaksanaan. Penutupan epic menunggu client eksternal dan cleanup selesai.
