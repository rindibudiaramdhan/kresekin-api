# Role Owner dan Online Monitoring Cabang

Tanggal: 2026-08-05

## Ringkasan

Menambahkan role baru bernama `owner` dan portal/menu `Online Monitoring` yang bersifat read-only untuk memantau penjualan toko-toko dalam cabang yang menjadi scope owner tersebut.

Dokumen ini adalah task dan requirement awal sebelum development. Belum ada perubahan pada kode aplikasi, database, API, UI, test, atau konfigurasi deployment.

## Temuan Kondisi Sistem Saat Ini

1. Role yang tersedia baru `buyer`, `seller`, `agent`, dan `finance`, disimpan di `users.role` serta didaftarkan melalui constant dan `User::roles()`.
2. Authorization memakai middleware `session.token`, middleware `role:*`, dan ownership scoping di query.
3. Login portal web saat ini hanya menyediakan pilihan Agent dan Finance serta mengarahkan user berdasarkan role yang tersimpan di `localStorage`.
4. Belum ada route, API, halaman, menu sidebar, aggregator, seeder, maupun test untuk role `owner`.
5. Cabang dikonfirmasi bukan entitas baru: satu akun user dengan role `seller` merepresentasikan satu cabang.
6. Toko tetap direpresentasikan oleh `tenant`. Relasi `tenants.owner_user_id` yang sudah ada menghubungkan satu toko ke tepat satu seller/cabang.
7. Penjualan dapat dihitung dari `transactions` dan `transaction_items`. Scope toko paling aman diterapkan melalui `transaction_items.tenant_id` agar transaksi yang berisi item dari beberapa toko tidak salah dihitung sebagai seluruh nilai transaksi milik satu cabang.
8. Istilah `owner` sudah dipakai oleh `tenants.owner_user_id` untuk relasi tenant ke seller/cabang. Role `owner` baru adalah pemilik/pengelola cabang dan harus dibedakan melalui nama relasi yang eksplisit agar tidak ambigu.
9. Proyek belum memiliki Laravel broadcasting, WebSocket, Reverb, Echo, atau infrastruktur event streaming. Implementasi real-time penuh akan memerlukan komponen dan operasional tambahan.

## Tujuan

1. User dengan role `owner` dapat login ke management portal.
2. Owner hanya dapat melihat cabang yang diberikan kepadanya dan toko yang berada di cabang tersebut.
3. Owner dapat membuka menu `Online Monitoring` untuk melihat aktivitas penjualan terbaru dan ringkasan performa toko.
4. Data monitoring diperbarui otomatis tanpa refresh manual halaman.
5. Owner tidak dapat mengubah order, produk, data toko, transaksi, atau workflow finance dari menu monitoring.
6. API tidak membocorkan data cabang atau toko di luar scope owner.

## Keputusan MVP yang Sudah Dikonfirmasi

1. `owner` adalah pemilik/pengelola cabang, bukan seller pemilik toko.
2. Cabang direpresentasikan oleh akun user dengan role `seller`; tidak dibuat tabel `branches` baru.
3. Satu owner dapat membawahi banyak seller/cabang, tetapi satu seller/cabang hanya boleh berada di bawah satu owner.
4. Satu toko/tenant hanya berada pada satu seller/cabang melalui `tenants.owner_user_id` yang sudah ada.
5. Penjualan dan seluruh summary omzet, jumlah order, serta item terjual hanya menghitung transaksi berstatus `completed`.
6. Online monitoring menggunakan near-real-time polling setiap 10 detik untuk MVP.
7. Akun owner dibuat secara internal oleh admin/seeder; tidak ada public registration owner pada MVP.
8. Data wajib adalah omzet, jumlah order, item terjual, toko aktif, status order, dan daftar order terbaru.
9. Semua angka uang dihitung server-side, dikirim sebagai integer, dan disertai label Rupiah bila diperlukan UI.
10. Monitoring bersifat read-only.
11. Assignment owner ke seller/cabang dilakukan melalui seeder pada MVP.
12. Login owner menggunakan OTP email/WhatsApp seperti agent dan finance.
13. `Toko aktif` adalah tenant yang memiliki minimal satu order `completed` pada periode terpilih.
14. Status order dan daftar order terbaru menampilkan semua status, sedangkan summary penjualan hanya menghitung `completed`.
15. Omzet adalah jumlah `transaction_items.line_total` sebelum alokasi diskon, ongkir, dan service fee.
16. Owner dapat memilih satu tanggal, dengan default hari berjalan dalam zona `Asia/Jakarta`; date range belum didukung pada MVP.
17. Tampilan awal mengagregasi seluruh cabang dalam scope owner; owner kemudian dapat memilih satu seller/cabang tertentu.
18. Nama cabang menggunakan `users.name` milik user ber-role `seller`.
19. Daftar order tidak menampilkan PII buyer; field yang boleh tampil hanya nomor order, daftar cabang, daftar toko, waktu, nominal dalam scope/filter, dan status.
20. Transaksi lintas toko/cabang ditampilkan sebagai satu baris per transaksi, dengan daftar cabang/toko yang termasuk scope/filter.
21. Nominal pada baris order adalah subtotal `transaction_items.line_total` yang termasuk scope/filter, bukan `transactions.total_amount`.
22. Filter cabang, toko, dan tanggal memengaruhi seluruh dashboard. Filter status dan pencarian nomor order hanya memengaruhi daftar order; `order_status_counts` tetap menampilkan distribusi seluruh status dalam scope cabang, toko, dan tanggal.
23. Performa toko menampilkan nama toko, nama cabang, omzet completed, jumlah distinct order completed, item terjual, dan waktu order terakhir; urutan default adalah omzet terbesar.
24. Daftar order terbaru diurutkan berdasarkan `transactions.transaction_at`; perubahan status pada order lama tetap terlihat setelah polling tetapi tidak memindahkan order tersebut ke urutan teratas.
25. API monitoring dipisahkan menjadi endpoint summary/status, performa toko, dan daftar order agar pagination dan polling lebih efisien.
26. MVP menggunakan snapshot polling. Endpoint incremental dan test cursor dikeluarkan dari acceptance MVP dan baru dievaluasi setelah pengukuran performa snapshot.

## Batasan Produk untuk MVP

1. Assignment hanya melalui seeder; belum ada UI atau Artisan command pengelolaan assignment.
2. Owner tidak memiliki status lifecycle tambahan di luar user/session yang sudah ada.
3. Detail order interaktif, notifikasi suara, export, dan laporan historis belum masuk scope.
4. Filter global MVP mencakup seller/cabang, toko, dan satu tanggal. Status serta pencarian nomor order hanya berlaku pada daftar order.
5. Perubahan assignment berlaku pada request berikutnya karena semua query selalu membaca scope terbaru dari database; session owner tidak perlu diakhiri.
6. Date range, laporan historis berbasis rentang, dan incremental cursor belum masuk scope MVP.

## Model Data yang Disepakati

Cabang menggunakan user ber-role `seller`, sehingga tidak diperlukan model atau tabel `branches`. Cardinality one-to-many paling tepat disimpan sebagai self-referencing foreign key pada akun seller, bukan pivot.

1. Tambahkan kolom nullable `users.branch_owner_user_id`:
   - UUID foreign key ke `users.id`;
   - hanya diisi pada user ber-role `seller`;
   - menunjuk user ber-role `owner`;
   - indexed untuk query seluruh seller/cabang milik owner;
   - `nullOnDelete()` agar penghapusan owner mencabut assignment tanpa menghapus seller.
2. Satu kolom foreign key pada row seller secara langsung menegakkan satu seller hanya mempunyai nol atau satu owner, sementara nilai yang sama dapat digunakan oleh banyak seller.
3. Seeder wajib memvalidasi role kedua sisi sebelum mengisi `branch_owner_user_id`.
4. Toko tidak memerlukan migration relasi baru:
   - satu toko adalah satu `tenant`;
   - satu toko berada pada satu seller/cabang melalui `tenants.owner_user_id`;
   - seluruh tenant dengan `owner_user_id = seller_user_id` termasuk dalam cabang seller tersebut.

Relasi Eloquent yang direkomendasikan:

- `User::managedSellerBranches()` sebagai `hasMany(User::class, 'branch_owner_user_id')` pada owner.
- `User::branchManager()` sebagai `belongsTo(User::class, 'branch_owner_user_id')` pada seller.
- Relasi `User::ownedTenants()` dan `Tenant::owner()` yang ada tetap dipakai untuk seller ke toko.

Nama kolom dan relasi sengaja menggunakan `branch_owner`/`branch_manager` agar tidak tertukar dengan `Tenant::owner()` yang berarti seller pemilik toko.

## Provisioning Owner Pertama yang Disepakati

1. Akun owner awal dibuat otomatis oleh seeder internal tanpa credential dari environment.
2. Seeder menghasilkan UUID dan email development unik `@example.test`, lalu menampilkan ID dan email tersebut pada console.
3. Seeder menggunakan `users.internal_provisioning_key` sebagai identitas teknis non-secret agar idempotent dan tetap dapat menemukan owner awal ketika jumlah owner bertambah.
4. Seeder tidak menyimpan OTP atau password. OTP tetap dikirim melalui mail/WhatsApp driver aplikasi.
5. Pada eksekusi pertama, seluruh seller yang belum mempunyai owner di-assign kepada owner awal.
6. Eksekusi ulang hanya meng-assign seller dengan `branch_owner_user_id = null`; assignment milik owner lain tidak diambil alih.
7. Seller yang dibuat kemudian tetap tidak memiliki owner sampai assignment dilakukan eksplisit atau seeder dijalankan kembali.
8. Email development harus diganti melalui proses administrasi dengan email atau nomor WhatsApp yang dapat menerima OTP sebelum penggunaan production.
9. Portal owner menyediakan pilihan email dan WhatsApp untuk mendukung banyak owner dengan kombinasi kontak berbeda; request login tetap hanya berhasil bila kontak terdaftar pada akun owner.
10. Menambahkan role owner ke `User::roles()` tidak boleh membuka public registration owner; allowlist role autentikasi dan role yang boleh registrasi harus dipisahkan.

## Kontrak Data Monitoring yang Disepakati

### Summary dan Status

```http
GET /api/owner/online-monitoring/summary
```

Query:

```text
?seller_id={uuid}
&store_id={uuid}
&date=2026-08-06
```

`seller_id` dan `store_id` opsional. Tanpa keduanya, endpoint mengagregasi seluruh cabang dan toko dalam scope owner. Zona bisnis ditetapkan server-side ke `Asia/Jakarta` dan tidak menerima timezone arbitrer dari client.

Response minimum:

```json
{
  "data": {
    "generated_at": "2026-08-06T14:30:10+07:00",
    "refresh_after_seconds": 10,
    "scope": {
      "seller_id": null,
      "store_id": null,
      "date": "2026-08-06",
      "timezone": "Asia/Jakarta"
    },
    "summary": {
      "sales_amount": 12500000,
      "sales_amount_label": "Rp 12.500.000",
      "order_count": 142,
      "item_quantity": 386,
      "active_store_count": 18
    },
    "order_status_counts": []
  }
}
```

### Performa Toko

```http
GET /api/owner/online-monitoring/stores
```

Query:

```text
?seller_id={uuid}
&store_id={uuid}
&date=2026-08-06
&sort=sales_amount
&direction=desc
&page=1
&per_page=25
```

Setiap row minimum berisi `store_id`, `store_name`, `seller_id`, `branch_name`, `sales_amount`, `sales_amount_label`, `order_count`, `item_quantity`, dan waktu order terakhir. Pagination wajib memiliki metadata yang eksplisit. `per_page` default 25 dan maksimum 100.

### Daftar Order

```http
GET /api/owner/online-monitoring/orders
```

Query:

```text
?seller_id={uuid}
&store_id={uuid}
&date=2026-08-06
&status={status_code}
&search={order_number}
&page=1
&per_page=25
```

Setiap transaksi hanya muncul satu kali. `branches` dan `stores` berbentuk daftar unik yang hanya berisi resource dalam scope/filter. Nominal row adalah subtotal item dalam scope/filter. Pagination wajib memiliki metadata yang eksplisit. `per_page` default 25 dan maksimum 100.

Ketiga endpoint menggunakan snapshot, mengembalikan `generated_at` dan `refresh_after_seconds = 10`, serta dipoll berdasarkan interval response. Endpoint incremental belum menjadi bagian MVP.

### Aturan Perhitungan

1. Query wajib dimulai dari seller/cabang dengan `users.branch_owner_user_id = current owner id`, lalu tenant dengan `tenants.owner_user_id` yang sesuai.
2. Agregasi per toko menggunakan `transaction_items.tenant_id` dan `line_total`.
3. Satu transaksi lintas toko tidak boleh membuat total transaksi penuh terhitung berulang pada setiap toko/cabang.
4. `omzet` hanya menjumlahkan `line_total` item dari transaksi `completed` yang tenant-nya termasuk scope owner.
5. `jumlah order` menghitung distinct `transactions.id` berstatus `completed`; pada summary lintas cabang, satu transaksi tetap dihitung satu kali meskipun berisi toko dari beberapa seller/cabang.
6. `item terjual` menjumlahkan `transaction_items.quantity` hanya dari transaksi `completed`.
7. `toko aktif` adalah distinct tenant yang memiliki sedikitnya satu transaksi `completed` pada periode terpilih.
8. `status order` menghitung seluruh status transaksi dalam scope cabang, toko, dan tanggal agar proses berjalan dapat dipantau; filter status/search tabel order tidak memengaruhi angka ini.
9. `daftar order terbaru` menampilkan seluruh status, diurutkan dari `transaction_at` terbaru.
10. Diskon transaksi, ongkir, dan service fee tidak dimasukkan ke omzet per toko.
11. Periode default adalah awal sampai akhir hari berjalan dalam timezone `Asia/Jakarta`.
12. Pagination wajib diterapkan untuk daftar order dan performa toko.
13. Daftar order hanya mengembalikan nomor order, daftar seller/cabang, daftar toko, waktu, nominal item dalam scope/filter, dan status; data buyer tidak di-query atau dipetakan ke response.
14. Satu transaksi lintas toko/cabang tetap dihitung sekali untuk jumlah order dan ditampilkan sekali pada daftar order.
15. Waktu order terakhir pada performa toko berasal dari `transactions.transaction_at`, bukan waktu perubahan status.
16. Filter `seller_id` dan `store_id` di luar scope owner menghasilkan `404`; kombinasi seller dan toko yang tidak berelasi juga menghasilkan `404`.

## Rekomendasi Target Performa dan Kapasitas — Menunggu Persetujuan

Angka berikut merupakan baseline teknis yang direkomendasikan dan belum menjadi acceptance criteria final sampai dikonfirmasi:

| Parameter | Rekomendasi MVP | Target stress test |
| --- | ---: | ---: |
| Owner aktif bersamaan | 50 | 100 |
| Cabang per owner | 100 | 200 |
| Toko per cabang | 20 | 50 |
| Total toko dalam scope owner | 2.000 | 5.000 |
| Transaksi per hari dalam scope owner | 25.000 | 100.000 |
| Item per transaksi | rata-rata 5, maksimum 50 | 100 |
| Ukuran halaman order | default 25, maksimum 100 | 100 row |
| Ukuran halaman performa toko | default 25, maksimum 100 | 100 row |

Target response time yang direkomendasikan pada p95 adalah 500 ms untuk summary/status dan daftar order, serta 750 ms untuk performa toko. Target p99 setiap endpoint adalah 1.500 ms, initial dashboard lengkap maksimal 2 detik, dan error rate monitoring di bawah 1%. Query di atas 1 detik dicatat sebagai slow query.

Dengan tiga endpoint dan 50 owner aktif, estimasi trafik rata-rata adalah 15 request/detik. Client direkomendasikan memberi random jitter 0–2 detik, menghentikan polling saat tab tidak aktif, hanya memuat ulang halaman pagination yang sedang terlihat, dan mencegah request tumpang tindih. Rate limit yang direkomendasikan adalah 60 request/menit per user, bukan per IP.

## Task Pengerjaan

### Fase 0 — Finalisasi Requirement dan Desain Teknis

1. Turunkan keputusan final dokumen ini menjadi kontrak API dan wireframe UI.
2. Tetapkan bentuk visual metric serta kolom tabel tanpa menambah PII buyer.
3. Buat wireframe atau acuan UI final untuk menu `Online Monitoring`.
4. Buat ADR untuk self-reference owner–seller dan strategi polling 10 detik.
5. Perbarui permission matrix sebelum implementasi endpoint.

### Fase 1 — Role, Authentication, dan Authorization

1. Tambahkan `User::ROLE_OWNER = 'owner'` dan masukkan ke `User::roles()`.
2. Tambahkan endpoint login/resend OTP owner atau gunakan route generic yang tersedia, tetapi pisahkan allowlist login dari allowlist registration agar public registration owner tetap tertutup.
3. Implementasikan provisioning akun owner awal yang digenerate dan idempotent melalui `internal_provisioning_key`; jangan membuka public registration owner.
4. Tambahkan group API `session.token` dan `role:owner` dengan prefix `/owner`.
5. Tambahkan route web portal owner dan redirect login berdasarkan role.
6. Tambahkan tab/login copy owner di portal; email dan WhatsApp sama-sama dapat dipilih bila tersedia, metode utama menjadi default, dan metode tanpa kontak tidak ditampilkan.
7. Pastikan owner tidak dapat mengakses endpoint seller, agent, finance, atau owner lain.
8. Audit perubahan assignment owner–seller; perubahan scope berlaku pada request berikutnya tanpa invalidasi session.

### Fase 2 — Assignment Owner ke Seller/Cabang

1. Buat migration `users.branch_owner_user_id` dengan self-referencing foreign key dan index.
2. Tambahkan relasi Eloquent `managedSellerBranches()` dan `branchManager()` pada `User`.
3. Gunakan `User::ownedTenants()` yang sudah ada untuk mengambil toko di bawah seller/cabang.
4. Buat seeder/factory untuk akun owner dan assignment owner–seller sesuai kontrak provisioning: generate owner awal, idempotent berdasarkan `internal_provisioning_key`, dan hanya meng-assign seller yang belum mempunyai owner.
5. Validasi kedua sisi assignment berdasarkan role `owner` dan `seller`.
6. Pastikan assignment ulang seller mengganti owner lama dan tidak dapat menghasilkan assignment ganda.
7. Pastikan seller tanpa owner tidak dapat terlihat oleh owner mana pun.
8. Pastikan penghapusan assignment hanya mencabut akses dan tidak menghapus seller, tenant, atau histori transaksi.

### Fase 3 — Backend Online Monitoring

1. Buat controller dan support/aggregator khusus owner; jangan menaruh query agregat besar langsung di controller.
2. Implementasikan tiga endpoint snapshot: summary/status, performa toko, dan daftar order.
3. Implementasikan filter `seller_id`, `store_id`, satu tanggal, status khusus order, search khusus order, sort performa toko, dan pagination sesuai keputusan final.
4. Terapkan owner scope sebelum agregasi dan pagination.
5. Kembalikan `404` untuk seller/cabang atau toko di luar scope agar keberadaan resource tidak bocor.
6. Gunakan allowlist response; jangan mengembalikan model mentah.
7. Jangan query atau kembalikan PII buyer pada response monitoring.
8. Optimalkan query dengan eager loading, aggregate query, index, dan batas jumlah row.
9. Ukur performa snapshot; endpoint incremental hanya dievaluasi setelah MVP dan bukan bagian acceptance criteria saat ini.
10. Tambahkan cache singkat hanya jika diperlukan, dengan cache key yang memasukkan owner/seller/filter agar tidak terjadi cross-tenant data leak.
11. Tambahkan rate limit yang masih mendukung interval refresh yang disetujui.

### Fase 4 — Web Portal dan Menu Owner

1. Buat route dan halaman dashboard owner.
2. Tambahkan sidebar owner dengan menu aktif `Online Monitoring`.
3. Tampilkan agregat seluruh cabang sebagai default dan selector cabang bila owner memiliki lebih dari satu cabang.
4. Tampilkan summary cards, status order, performa toko, dan daftar order terbaru sesuai desain final.
5. Tambahkan auto-refresh dengan interval dari response API, pause saat tab browser tidak aktif, dan resume saat aktif kembali.
6. Tampilkan indikator koneksi: `Live`, `Menghubungkan ulang`, `Gagal memperbarui`, dan `Terakhir diperbarui`.
7. Cegah request polling tumpang tindih dan batalkan request lama ketika filter berubah.
8. Tangani loading, empty, `401`, `403`, `404`, `422`, `429`, dan server error.
9. Logout dan bersihkan token/role lokal secara konsisten.
10. Pastikan layout responsif untuk desktop dan tablet yang menjadi target operasional.

### Fase 5 — Real-time Push (Opsional Setelah MVP)

1. Evaluasi Laravel Reverb/WebSocket atau provider managed berdasarkan volume koneksi dan kesiapan deployment.
2. Broadcast event hanya setelah transaksi/order tersimpan sukses.
3. Gunakan private channel per seller/cabang dan authorize channel melalui `branch_owner_user_id`.
4. Payload event harus minimal dan tidak memuat data sensitif.
5. Tetap sediakan snapshot/reconciliation API karena event dapat terlambat atau terlewat.
6. Siapkan reconnect, cursor, observability, capacity limit, dan fallback polling.

### Fase 6 — Testing dan Quality

1. Unit test constant/daftar role dan seluruh relasi baru.
2. Feature test login, OTP verify, refresh session, dan logout owner.
3. Feature test unauthenticated menghasilkan `401` dan role selain owner menghasilkan `403`.
4. Test owner hanya melihat seller/cabang dengan `branch_owner_user_id` miliknya dan tenant dari seller tersebut.
5. Test seller/cabang atau toko di luar scope menghasilkan `404`.
6. Test satu owner dapat membawahi banyak seller dan satu seller tidak dapat memiliki lebih dari satu owner.
7. Test seller tanpa owner tidak muncul pada owner mana pun.
8. Test agregasi transaksi multi-toko agar nominal tidak double count.
9. Test batas hari berjalan pada timezone `Asia/Jakarta`.
10. Test summary hanya menghitung `completed`, sedangkan status dan daftar terbaru mencakup semua status.
11. Test response tidak memuat OTP, token, password, dokumen identitas, rekening, atau PII buyer.
12. Test filter, sort, pagination snapshot, dan validation error; cursor incremental tidak diuji pada MVP.
13. Test polling/rate-limit contract dan response `generated_at`.
14. Jalankan regression test seluruh auth dan dashboard seller/agent/finance.
15. Lakukan query/performance test dengan volume data representatif dan tetapkan target response time.
16. Test public registration owner tetap tertutup walaupun owner termasuk role yang dapat login dan verifikasi OTP.
17. Test seeder owner idempotent, validasi UUID/role/kontak, pembaruan kontak, assignment seluruh seller existing, dan perilaku future seller yang tetap unassigned.

### Fase 7 — Dokumentasi, Observability, dan Release

1. Perbarui `docs/requirements/02-roles-permissions.md` dengan role owner dan scope seller/cabang.
2. Perbarui ADR-004 atau buat ADR baru untuk owner-to-seller assignment scoping.
3. Perbarui `API_DOCUMENTATION.md` dengan endpoint, query, response, error, dan polling contract.
4. Dokumentasikan definisi setiap metric agar frontend, backend, dan bisnis memakai angka yang sama.
5. Tambahkan structured log untuk kegagalan monitoring tanpa mencatat token atau PII sensitif.
6. Tambahkan metric operasional: latency endpoint, error rate, jumlah request polling, dan slow query.
7. Siapkan migration/seed assignment untuk data production dan rencana rollback yang tidak menghapus histori.
8. Lakukan UAT dengan minimal dua owner dan cabang berbeda untuk membuktikan isolasi data.
9. Rilis bertahap menggunakan feature flag bila tersedia.

## Permission Matrix Awal

| Area | Owner |
| --- | --- |
| Auth/session sendiri | Yes |
| Daftar cabang | Assigned read |
| Daftar toko | Assigned branch read |
| Online monitoring | Assigned branch read |
| Detail order | No untuk MVP |
| Produk toko | No untuk MVP |
| Ubah status order | No |
| Finance/disbursement | No |
| Kelola assignment cabang | No; melalui seeder internal |

## Acceptance Criteria MVP

1. User role `owner` dapat login dan diarahkan ke portal owner.
2. Sidebar owner menampilkan menu `Online Monitoring` dan tidak menampilkan menu operasional role lain.
3. Owner hanya dapat memilih seller/cabang dengan `branch_owner_user_id` miliknya serta tenant milik seller tersebut.
4. Halaman menampilkan omzet, jumlah order, item terjual, toko aktif, status order, dan daftar order terbaru.
5. Omzet, jumlah order, dan item terjual hanya berasal dari transaksi `completed`.
6. Data diperbarui otomatis setiap 10 detik dan waktu update terakhir terlihat.
7. Query penjualan lintas toko menggunakan subtotal item toko dan tidak double count.
8. Akses seller/cabang atau toko di luar scope tidak mengungkap keberadaan data.
9. Monitoring bersifat read-only.
10. Tidak ada secret atau PII yang tidak disetujui dalam response.
11. Test auth, authorization, scoping, aggregation, timezone, polling, dan regression lulus.
12. Dokumentasi role, API, definisi metric, dan strategi refresh telah diperbarui.
13. Tampilan awal mengagregasi seluruh cabang dan filter cabang/toko/tanggal memperbarui seluruh dashboard.
14. Status dan pencarian nomor order hanya memengaruhi daftar order, bukan summary atau `order_status_counts`.
15. Transaksi lintas toko/cabang tampil satu kali dengan daftar cabang/toko dan subtotal item sesuai scope/filter.
16. API summary/status, performa toko, dan daftar order terpisah serta menggunakan snapshot polling 10 detik.
17. Seeder owner awal menghasilkan akun development, idempotent berdasarkan `internal_provisioning_key`, dan hanya meng-assign seller yang belum mempunyai owner.

## Out of Scope MVP

1. Mengubah status order dari portal owner.
2. Mengelola produk atau toko.
3. Workflow finance/disbursement.
4. Laporan akuntansi dan rekonsiliasi keuangan.
5. Export CSV/PDF, kecuali dikonfirmasi wajib.
6. Status online/offline toko berbasis heartbeat.
7. WebSocket/push real-time penuh; dapat menjadi fase lanjutan setelah polling MVP tervalidasi.
8. Mobile app khusus owner.

## Risiko dan Mitigasi

1. **Kebocoran data lintas cabang** — scope query dari assignment owner sebelum filter, aggregate, dan pagination; tambahkan negative authorization test.
2. **Double count transaksi multi-toko** — hitung nilai per toko dari `transaction_items`, bukan seluruh `transactions.total_amount`.
3. **Polling membebani database** — gunakan interval minimum, pause background tab, aggregate query/index, jitter, dan cache ter-scope bila perlu; incremental cursor hanya dievaluasi setelah pengukuran MVP.
4. **Definisi omzet berbeda antar halaman** — dokumentasikan bahwa omzet owner adalah subtotal item transaksi `completed` sebelum diskon, ongkir, dan service fee.
5. **Benturan istilah owner** — bedakan seller owner dan branch owner pada nama model, relasi, controller, serta dokumentasi.
6. **Data tampak real-time tetapi terlambat** — tampilkan `generated_at`, status koneksi, dan target freshness yang terukur.
7. **Public registration owner terbuka melalui route generic** — pisahkan allowlist role login/OTP dari allowlist role registration dan tambahkan negative test registration owner.
8. **Seeder membuat owner duplikat atau mengambil assignment owner lain** — gunakan `internal_provisioning_key` sebagai identitas stabil dan hanya update seller yang belum mempunyai owner.
9. **Lonjakan polling serempak** — tambahkan jitter client, rate limit per user, pause background tab, dan larang request tumpang tindih.

## Estimasi Urutan Delivery

1. Requirement/ADR/UI contract.
2. Role owner dan auth.
3. Self-referencing assignment owner ke seller/cabang pada tabel `users`.
4. API snapshot dan authorization scoping.
5. Portal/menu Online Monitoring dan polling.
6. Hardening test, performance, dokumentasi, dan UAT.
7. Evaluasi WebSocket hanya bila polling tidak memenuhi target bisnis.
