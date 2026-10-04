# K01 — Matriks audit kontrak dan keputusan transisi

Status: audit backend/API contract selesai; penutupan K01 pada 2026-10-04.

Tanggal audit: 2026-10-04. Sumber: kode repository dan konfirmasi pengguna pada grooming. Dokumen ini mencatat kondisi backend saat audit serta kontrak yang disepakati; bukan klaim perubahan sudah diterapkan atau deployment berhasil.

## Client dan pemilik

| Client | Status berdasarkan bukti | PIC | Versi dan tindakan |
| --- | --- | --- | --- |
| Mobile seller Android | Aktif di produksi menurut pengguna; kode di repository terpisah yang tidak tersedia | Rindi, dikonfirmasi pengguna | Upgrade wajib; perubahan sudah dikembangkan menurut pengguna. Nomor build, rilis dan hasil uji belum tersedia |
| Mobile seller iOS | Aktif di produksi menurut pengguna; kode tidak tersedia | Rindi, dikonfirmasi pengguna | Kebijakan sama dengan Android; bukti versi/rilis belum tersedia |
| Mobile seller versi lama | Ada risiko kompatibilitas karena aplikasi sudah dipakai; daftar versi aktif belum tersedia | Rindi | Upgrade wajib menurut pengguna; mekanisme enforcement dan tanggal berakhir dukungan belum diverifikasi |
| Web seller dalam repository | Route/form ditemukan; penggunaan produksi belum dikonfirmasi | Backend Web, personel belum ditetapkan | Tetap migrasikan writer agar tidak menghasilkan produk tanpa ID |
| Dashboard web agent/finance dalam repository | Pemanggil API ditemukan di view; penggunaan produksi belum dikonfirmasi | Backend Dashboard, personel belum ditetapkan | Tetap perbaiki reader/template; bukan bukti ada client produksi tambahan |
| API buyer/agent/finance eksternal | Route tersedia; pengguna menyatakan client API aktif hanya mobile seller | Backend API, personel belum ditetapkan | Audit kontrak tetap diperlukan; tidak mengasumsikan ada client eksternal aktif |
| Admin Filament | Resource ditemukan; paket Filament tidak ditemukan di manifest/lockfile | Backend Admin, personel belum ditetapkan | Audit kode resource tetap masuk K04; aktivasi resource belum terbukti |

Nama pemilik teknis berikut adalah peran dari tiket, bukan identitas personel terverifikasi. PIC mapping kategori lama, operations/database dan release owner adalah Rindi, dikonfirmasi pengguna pada follow-up evaluasi PM. Engineering lead dan personel pelaksana backend lain belum ditetapkan. Pernyataan perubahan mobile sudah dikembangkan tidak disamakan dengan bukti rilis atau hasil uji.

## Matriks endpoint, input dan response

Path API memakai prefix `/api`. Semua route di tabel ditemukan pada `routes/api.php`, kecuali baris yang dinyatakan tidak terdaftar. Lokasi controller API relatif terhadap `app/Http/Controllers/Api/`; lokasi request terhadap `app/Http/Requests/`.

| Endpoint/jalur | Kontrak saat audit dan lokasi kode | Client/pemilik | Tindakan yang dialokasikan |
| --- | --- | --- | --- |
| POST seller/tenants | `CreateSellerTenantRequest.php`: wajib UUID `category_id`. `CreateSellerTenantController.php`: lookup master, simpan nama dan ID tenant; response `category_id`, `category`, `category_master` | Mobile seller; Backend API | K02 nullable schema; K09 abaikan input kategori, hentikan penyimpanan dan hapus field response sesuai gate client |
| GET seller/tenants | `GetSellerTenantListController.php`: eager-load `productCategory`, response tiga field kategori yang sama | Mobile seller; Backend API | K09 hilangkan ketergantungan relasi kategori tunggal |
| Edit tenant/merchant | Tidak ada route update tenant pada repository. `AgentTenantRequest.php` mewajibkan teks konstanta, tidak ditemukan pemakai | Kontrak masa depan; Backend API | K09 bersihkan request tidak dipakai; tidak membuat endpoint edit baru. Jika dibuat terpisah, tanpa input kategori |
| POST seller/products | `CreateSellerProductRequest.php`: wajib UUID `product_category_id`. `CreateSellerProductController.php`: membuang ID, menyimpan nama, response `category` tanpa ID/master | Mobile seller; Backend API | K03 simpan ID + nama sementara, response ID/master; create tetap wajib ID |
| PUT dan POST seller/products/{id} | `UpdateSellerProductRequest.php`: wajib teks `category` dari `Tenant::CATEGORIES`. `UpdateSellerProductController.php`: update teks, response kategori teks. Kedua method memakai controller yang sama | Mobile seller; Backend API | K03 ID menjadi acuan; fallback hanya bila key ID tidak ada; invalid/null/kosong tetap 422 |
| GET seller/products dan seller/products/{id} | `GetSellerProductListController.php`, `GetSellerProductDetailController.php`: kategori teks; belum ada ID/master | Mobile seller; Backend API | K07 reader relasi dan response ID/master; ownership tetap |
| GET product-categories | `GetProductCategoriesController.php`, `ProductCategory.php`: master id/name/slug/image; auth session token | Pilihan kategori mobile seller; Backend API | Pertahankan sumber pilihan, UUID sesuai environment; K10 dokumentasikan |
| GET products dan products/{id} | `GetProductListController.php`: query slug `category` diresolusikan ke master lalu filter nama teks; produk aktif. Detail memakai teks, slugifikasi nama, metadata helper Tenant | API buyer, client aktif tidak dikonfirmasi; Backend API | K07 reader/metadata master; K08 filter ID; pertahankan visibility masing-masing endpoint |
| GET tenants | `GetBuyerTenantListController.php`: query slug `product_category` → master → filter nama produk; response kategori tunggal + metadata; agregat `product_categories`/`product_category_slugs` dari produk yang sudah difilter | API buyer, client aktif tidak dikonfirmasi; Backend API | K08 filter/agregat ID; K09 hapus kategori tunggal. Jangan mengubah count/agregat menjadi total toko saat filter aktif |
| GET tenants/{tenantId}/product-categories | `GetTenantProductCategoryListController.php`: produk aktif, distinct teks → master `whereIn(name)` → unique nama; 404 tenant tidak ditemukan | API buyer; Backend API | K08 master unik berdasarkan ID, tetap hanya produk aktif/non-soft-deleted |
| GET tenants/categories | `GetTenantCategoriesController.php`: konstanta dan metadata Tenant; route buyer masih aktif | API buyer; Backend API | K10 dokumentasi deprecation + telemetry, K12 removal setelah gate |
| GET agent/sellers dan agent/sellers/{sellerId} | `GetAgentSellerListController.php`, `GetAgentSellerDetailController.php`: nested tenant `category` | API agent; Backend API | K09 hapus field kategori tunggal dan uji nested response |
| GET agent/dashboard dan agent/managed-umkm | `GetAgentDashboardController.php`, `GetAgentManagedUmkmPerformanceController.php` → aggregator/query pada tabel berikut | Dashboard web agent; Backend Dashboard | K09 hapus kategori tunggal dari payload dan tampilan |
| GET finance/dashboard | `GetFinanceDashboardController.php` → `FinanceDashboardAggregator.php`: kategori spotlight | Dashboard web finance; Backend Dashboard | K09 selaraskan payload/template |
| GetTenantListController, tidak terdaftar | `GetTenantListController.php`: filter kategori tenant, metadata dan subtitle kartu; tidak ada route/pemanggil yang ditemukan | Kode tidak aktif; Backend API | K09 hapus atau lepaskan ketergantungan; tidak mengaktifkan route lama |

## Writer, reader bersama, schema dan fixture

| Lokasi | Temuan audit | Pemilik dan tindakan |
| --- | --- | --- |
| `app/Models/Tenant.php` | Fillable kategori teks/ID; relasi `productCategory`; konstanta kategori/metadata; `productCategories()` distinct teks, tidak ditemukan pemanggil | Backend Model: K08/K09 pindahkan pemakai; K13 hapus sisa helper/relasi/konstanta |
| `app/Models/Product.php`, `ProductCategory.php` | Produk fillable teks, soft delete, tanpa ID/relasi kategori; master name/slug/image | Backend Model: K02 relasi/FK restrict termasuk produk soft-deleted; K07 metadata berdasarkan slug dengan fallback netral |
| `database/migrations/2026_06_03_000014_create_tenants_table.php` | Teks kategori wajib; FK tenant nullable memakai null-on-delete | Database: K02 migration baru nullable; K13 drop kolom/FK lama setelah backup |
| `database/migrations/2026_06_03_000016_create_products_table.php` | Teks kategori wajib, tanpa ID; soft delete tersedia | Database: K02 ekspansi ID nullable + indeks/FK; K05/K06 backfill semua record; K13 non-null ID dan drop teks |
| `app/Http/Controllers/Web/SellerProductController.php`, `SellerProductRequest.php` | Form create/edit konstanta Tenant; store/update validated teks | Backend Web: K04 master + ID + dual-write, ownership tetap |
| `resources/views/seller/products/{form,index}.blade.php`, `routes/web.php` | Select/display teks; GET create/edit, POST create, PUT update produk web | Backend Web: K04 pilihan master, pilihan edit sesuai ID, K07 display turunan master |
| `app/Filament/Resources/ProductResource.php` | Select konstanta Tenant dan kolom tabel teks | Backend Admin: K04 master/ID; verifikasi keberfungsian admin pada environment yang mengaktifkannya |
| `app/Support/Dashboard/{AgentDashboardAggregator,AgentManagedUmkmPerformanceQuery,FinanceDashboardAggregator}.php` | Membaca kategori tunggal tenant untuk spotlight/performance | Backend Dashboard: K09 hapus ketergantungan tanpa mengubah angka transaksi/keuangan |
| `resources/views/dashboard/{agent,index}.blade.php`, `resources/views/components/dashboard/spotlight-card.blade.php` | Fetch API dashboard/managed-umkm dan render kategori; komponen Blade membaca key kategori langsung | Backend Dashboard: K09 selaraskan tampilan, jangan mengganti dengan kategori utama produk |
| `database/seeders/{TenantSeeder,PromoProductSeeder,ProductCategorySeeder}.php` | Fixture tenant/produk teks/konstanta; master di-upsert berdasarkan slug | Backend Data: K04 sesuaikan writer fixture; K13 fresh seed target. Pertahankan UUID/slug master selama rollout |
| `tests/Feature/{SellerApiTest,TenantApiTest,TenantProductCategoryApiTest,ProductApiTest,ProductDetailApiTest,AgentApiTest,FinanceApiTest}.php`, `tests/Unit/TenantTest.php` | Assertion/fixture kategori lama dan metadata | QA/Backend: regression di K02–K09 sesuai endpoint, cleanup fixture K13 |
| `tests/Feature/{CartApiTest,CheckoutApiTest,OwnerMonitoringApiTest}.php` | Kategori ditemukan pada fixture tenant/produk; bukan alasan mengubah kontrak cart/transaksi/owner | QA/Backend: K13 sesuaikan fixture, pertahankan perilaku domain |
| `database/factories/` | Hanya UserFactory ditemukan, tidak ada factory tenant/produk | Backend Data: tidak ada writer kategori factory tambahan yang ditemukan |
| `API_DOCUMENTATION.md` | Ringkasan endpoint dan contoh response berulang; bagian kategori legacy juga ada | Backend API: K10 perbarui kedua bagian, deprecation dan kontrak target. Jangan mendokumentasikan target sebagai sudah aktif |
| `docs/adr/012-configuration-driven-domain-values.md` | Kategori masih dijelaskan sebagai konstanta model | Engineering Lead: K10 selaraskan sumber master database kategori produk |

Kategori alasan pembatalan pada `Transaction`, controller/request cancellation, dan seedernya adalah domain lain dan di luar perubahan. Audit pencarian juga menemukan kategori pada log WhatsApp; tidak terkait kategori merchant/produk. Tidak ditemukan writer kategori lain pada `app`/`database` dalam pencarian create/update dan referensi kategori; operasi manual atau integrasi di luar repository belum terverifikasi.

Catatan visibility: buyer list tenant saat ini tidak membatasi relasi produk dengan `is_active`, sedangkan endpoint kategori per tenant dan daftar produk buyer membatasinya. K08 harus mempertahankan perbedaan ini serta scope soft delete, bukan menyamakan perilaku secara tidak sengaja.

## Keputusan kontrak hasil grooming

1. Pengguna mengonfirmasi pengisian kategori di tenant/merchant dihapus untuk create/ubah. Target create API mengabaikan `category_id`/`category` dari payload lama dan tidak menyimpannya; tidak menolak payload lama. Endpoint edit baru di luar lingkup.
2. Target response menghapus kategori tunggal tenant (`category_id`, `category`, `category_master`, `category_slug`, `category_icon_key`, `category_background_color`, `category_icon_color`). Tidak memilih kategori utama produk. Agregat tetap berbentuk seperti epic, kosong jika belum ada produk.
3. Belum ada bentuk response kosong sementara yang disepakati atau diuji. Acuan pekerjaan adalah response target tanpa field tunggal. Aktivasi produksi K09 tetap mengikuti G4: jika versi client aktif masih membutuhkan field lama, tahan aktivasi sampai bukti tersedia atau sepakati lapisan sementara di K10. Persetujuan bisnis bukan bukti parse/render mobile.
4. Kode mobile tidak tersedia; pengguna meminta pengerjaan berbasis API contract. K01 tidak mengklaim audit kode atau uji client selesai. Penghapusan input tidak menghapus kebutuhan kategori produk.
5. Fallback teks edit, prioritas ID, matching unik, dual-write, perlindungan delete master, dua siklus rilis, observasi 14 hari dan dua hari kerja setelah removal mengikuti keputusan epic. Upgrade wajib tidak otomatis membuktikan semua versi lama telah berhenti digunakan.

## Rencana observasi yang dialokasikan

Gunakan structured event melalui logging Laravel yang sudah tersedia, dengan request ID dari `app/Http/Middleware/AssignRequestId.php`. `config/logging.php` menyediakan channel termasuk stderr Laravel Cloud; channel/retensi produksi aktual belum diverifikasi. Nightwatch ada di dependensi, tetapi bukti konfigurasi/sampling produksi belum tersedia. Proposal ini tidak menganggap log existing telah mengukur pemakaian kontrak lama.

| Sinyal | Alokasi | Bukti yang diperlukan |
| --- | --- | --- |
| Edit produk tanpa key ID masuk fallback | K03: event `product_category_legacy_fallback`, outcome accepted/rejected, route template/method, environment, request ID | Hitungan harian seluruh instance; catat percobaan fallback termasuk yang ditolak. Tanpa teks kategori/payload/token/data personal |
| GET tenants/categories | K10: instrumentasi event `tenant_category_legacy_endpoint`, route template/method/status/request ID | Hitungan harian lengkap, bukan sampling; bedakan tidak ada request dari telemetry mati |
| Payload create tenant masih mengirim kategori | K10 koordinasikan dengan implementasi K09: event `tenant_category_legacy_input` dengan flag keberadaan field | Diagnostik rollout; input tetap diabaikan, event tidak mengubah validasi. Tidak mencatat nilai field |
| Field kategori response dibaca client | K10: checklist pengujian Android/iOS, versi/build, hasil parse/render response tanpa field, bukti release dan enforcement upgrade | Server tidak dapat mengamati pembacaan field JSON oleh client; tidak menyimpulkan dari nol request endpoint lama |

K10 harus memastikan retensi cukup untuk menyimpan 14 hari berturut-turut, event tidak disampling, collector aktif di semua instance, serta ekspor ringkasan durable. Header platform/version hanya dipakai jika kontraknya sudah tersedia dan terverifikasi; tidak mengarang versi client dari user-agent. Logging tidak menambah identitas/payload personal.

Ambang removal sesuai epic: semua versi aktif siap, dua siklus rilis terverifikasi, nol fallback/endpoint lama selama 14 hari berturut-turut dengan telemetry lengkap, dan bukti field read dari client. Pemakaian baru mengulang jendela; telemetry hilang tidak dihitung sebagai nol. Release owner: Rindi, dikonfirmasi pengguna; belum ada go/no-go.

## Data operasional yang masih diperlukan

Output query yang diberikan pengguna pada 2026-10-04 mengonfirmasi PostgreSQL **18.6 (4e955f5)** dan **17 produk: 10 non-soft-deleted, 7 soft-deleted** pada Laravel Cloud. Ini bukti yang disampaikan pengguna, bukan pemeriksaan langsung agent; waktu persis eksekusi tidak diberikan. Ritme rilis, versi/build mobile produksi/target, bukti uji/rilis/enforcement upgrade, serta akses observasi produksi belum tersedia. PIC mapping/operations/release owner telah dikonfirmasi: Rindi. Tidak ada koneksi atau mutation database produksi dijalankan pada audit ini.

Query read-only untuk dijalankan melalui SQL console produksi oleh pengguna/operations:

```sql
SELECT version();
SELECT COUNT(*) AS total,
       COUNT(*) FILTER (WHERE deleted_at IS NULL) AS non_soft_deleted,
       COUNT(*) FILTER (WHERE deleted_at IS NOT NULL) AS soft_deleted
FROM products;
```

`non_soft_deleted` bukan jumlah `is_active=true`. Inventaris kategori teks/master serta mapping aktual dilakukan K05/K06, mencakup record soft-deleted tanpa kategori tenant sebagai fallback.

## Verifikasi audit dan batas penyelesaian

- Pemeriksaan statis: route API/web; validasi → writer → model/schema → response; caller web/dashboard; seeder/fixture; metadata dan pencocokan nama; dokumentasi API/ADR.
- Tidak ada perubahan PHP, migration, asset, atau behavior aplikasi pada K01; Pint/test/build tidak dijalankan untuk perubahan Markdown ini.
- Matriks lokasi kontrak/client/peran/tindakan tersedia. Identitas PIC client berdasarkan konfirmasi pengguna; versi produksi dan kompatibilitas belum diverifikasi.
- Lokasi dokumentasi dan rencana observasi K03/K10 tersedia; operasional collector belum terbukti.
- K01 ditutup sebagai audit backend/API contract sesuai arahan pengguna; bukti versi dan uji mobile dikecualikan dari kriteria penutupan audit, bukan dinyatakan terpenuhi. Gate produksi tetap mengikuti epic dan tiket readiness yang sudah ada. Baseline database tersedia; mapping aktual belum dijalankan.
