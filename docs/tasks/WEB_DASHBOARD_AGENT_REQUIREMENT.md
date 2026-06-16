# Web Dashboard Agent Requirements

Dokumen ini merangkum kebutuhan halaman Dashboard Agent berdasarkan UI design yang diberikan, kondisi sistem saat ini, komponen yang dapat digunakan ulang, gap pengembangan, dan checklist implementasi agar bisa dikerjakan oleh junior web engineer.

Catatan penting:

- Dokumen ini hanya analisis dan requirement. Jangan implementasi sebelum ada approval eksplisit.
- Semua keputusan produk dan teknis final tetap perlu dikonfirmasi oleh owner.
- Halaman ini muncul setelah user role `agent` berhasil login via OTP.

## Ringkasan UI

Dashboard Agent adalah halaman utama Management Portal untuk agent. Fokus halaman adalah memberi ringkasan performa komisi dan UMKM binaan agent.

Elemen utama pada UI:

1. Layout shell:
   - Sidebar kiri.
   - Header atas.
   - Area konten dashboard.

2. Sidebar:
   - Logo `Kresek.in`.
   - Subtitle `Management Portal`.
   - Menu:
     - `Dashboard`
     - `UMKM Binaan`
     - `Pencairan Dana`
     - `Pengaturan`
   - Bottom menu:
     - `Support`
     - `Logout`

3. Header:
   - Title utama: `Laporan Performa`.
   - Label konteks: `Agent Panel`.
   - Toggle periode: `Monthly`, `Weekly`.
   - Icon notifikasi.
   - User chip, contoh: `Agent XYZ`.

4. Summary utama:
   - Card besar turquoise:
     - Label: `TOTAL KOMISI SAYA`
     - Value: contoh `Rp 12.450.000` atau `Rp 0`
     - Growth badge: contoh `+14.2% dari bulan lalu`
     - Footer: `TOTAL UMKM BINAAN: 300`

5. Chart:
   - Card `Pertumbuhan Transaksi`.
   - Subtitle: `Aggregated volume across managed UMKM`.
   - Date range picker, contoh `Oct 1 - Oct 31`.
   - Bar chart harian/mingguan.
   - State date picker terbuka seperti pada desain kedua.

6. Metric cards:
   - `TOTAL TRANSAKSI UMKM BINAAN`
   - `TOTAL UMKM BINAAN`
   - `TOTAL AREA BINAAN`

7. Table `Performa UMKM Binaan`:
   - Search input, placeholder `Cari UMKM...`.
   - Button `Filter`.
   - Columns:
     - `NAMA UMKM`
     - `KATEGORI`
     - `TOTAL TRANSAKSI`
     - `GROWTH`
     - `KOMISI FEE`
     - `STATUS`
     - `ACTIONS`
   - Row content:
     - Avatar initials.
     - Nama UMKM.
     - ID UMKM.
     - Kategori.
     - Total transaksi.
     - Growth percentage.
     - Komisi fee.
     - Status badge, contoh `Aktif` atau `Menunggu Aktivasi`.
     - Link `View Details`.
   - Empty state:
     - `Data tidak ditemukan`
   - Pagination:
     - Summary `Menampilkan x dari y UMKM`
     - Prev/next
     - Page numbers

## Kondisi Sistem Saat Ini

### Yang Sudah Ada

1. Route web dashboard agent sudah ada:

```php
Route::view('/agent/dashboard', 'dashboard.index', [
    'title' => 'Agent Dashboard',
    'headerTitle' => 'Agent Views',
    'userName' => 'Agent Administrator',
    'role' => 'agent',
    'active' => 'dashboard',
])->name('agent.dashboard');
```

2. Route web `agent/finance` sudah ada sebagai placeholder:

```php
Route::view('/agent/finance', 'dashboard.empty', [
    'title' => 'Agent Finance',
    'headerTitle' => 'Agent Views',
    'userName' => 'Agent Administrator',
    'role' => 'agent',
    'active' => 'finance',
])->name('agent.finance');
```

3. Endpoint API dashboard agent sudah ada:

```http
GET /api/agent/dashboard
GET /api/agent/dashboard?period=30_days
GET /api/agent/dashboard?period=90_days
```

Controller:

- `App\Http\Controllers\Api\GetAgentDashboardController`

Service:

- `App\Support\Dashboard\AgentDashboardAggregator`

4. Endpoint API agent seller sudah ada:

```http
GET /api/agent/sellers
GET /api/agent/sellers/{sellerId}
```

Controller:

- `App\Http\Controllers\Api\GetAgentSellerListController`
- `App\Http\Controllers\Api\GetAgentSellerDetailController`

5. Login agent sudah ada di halaman `/`:
   - Submit OTP ke `POST /api/agent/login`.
   - Verify OTP ke `POST /api/users/verify-otp`.
   - Token disimpan di `localStorage`:
     - `kresekin_token`
     - `kresekin_token_type`
     - `kresekin_user_role`
   - Redirect agent ke `route('agent.dashboard')`.

6. Middleware API role agent sudah ada:

```php
Route::middleware(['session.token', 'role:agent'])->prefix('agent')->group(function (): void {
    Route::get('/dashboard', GetAgentDashboardController::class);
    Route::get('/sellers', GetAgentSellerListController::class);
    Route::get('/sellers/{sellerId}', GetAgentSellerDetailController::class);
    Route::get('/profile', GetAgentProfileController::class);
    Route::put('/profile', UpdateAgentProfileController::class);
    Route::get('/commission-withdrawals', GetAgentCommissionWithdrawalListController::class);
    Route::post('/commission-withdrawals', CreateAgentCommissionWithdrawalController::class);
});
```

7. Domain model yang relevan sudah ada:
   - `User`
   - `Tenant`
   - `Transaction`
   - `TransactionItem`
   - `HousingArea`
   - `AgentCommissionWithdrawal`

8. Relasi yang relevan sudah ada:
   - `User::agentTenants()`
   - `User::ownedTenants()`
   - `Tenant::agent()`
   - `Tenant::owner()`
   - `Tenant::housingAreas()`
   - `Tenant::products()`

9. Support class komisi agent sudah ada:
   - `App\Support\AgentCommissionCalculator`
   - Menghitung:
     - completed revenue agent
     - commission rate
     - total commission
     - locked withdrawal amount
     - available commission

10. Komponen Blade dashboard sudah tersedia:
    - `resources/views/components/dashboard/sidebar.blade.php`
    - `resources/views/components/dashboard/header.blade.php`
    - `resources/views/components/dashboard/metric-card.blade.php`
    - `resources/views/components/dashboard/icon-tile.blade.php`
    - `resources/views/components/dashboard/trend-chart-card.blade.php`
    - `resources/views/components/dashboard/period-toggle.blade.php`
    - `resources/views/components/dashboard/data-table.blade.php`
    - `resources/views/components/dashboard/status-badge.blade.php`
    - `resources/views/components/dashboard/avatar-initial.blade.php`
    - `resources/views/components/dashboard/filter-bar.blade.php`
    - `resources/views/components/dashboard/filter-field.blade.php`
    - `resources/views/components/dashboard/pagination.blade.php`
    - `resources/views/components/dashboard/summary-highlight-card.blade.php`

11. Asset brand sudah tersedia:
    - `public/images/kresek-wordmark.svg`
    - `public/images/kresek-full-logo.svg`
    - `public/images/kresekin-bag-mark.svg`

### Yang Belum Sesuai Desain

1. View `/agent/dashboard` saat ini memakai `resources/views/dashboard/index.blade.php`, yang masih generic untuk agent/finance.
   - Metrics saat ini: `Total Pendapatan UMKM`, `Total Pesanan`, `Total UMKM Aktif`.
   - Design baru membutuhkan: `Total Komisi Saya`, `Total Transaksi UMKM Binaan`, `Total UMKM Binaan`, `Total Area Binaan`.
   - Design baru membutuhkan table `Performa UMKM Binaan`, bukan table transaksi terbaru dan top commissions.

2. Header saat ini belum sesuai copy UI design.
   - Existing: `Agent Views`.
   - Target: `Laporan Performa | Agent Panel`.

3. Sidebar agent belum lengkap sesuai UI design.
   - Existing kemungkinan hanya memetakan menu generic dashboard/finance.
   - Target membutuhkan `UMKM Binaan`, `Pencairan Dana`, `Pengaturan`.

4. Chart existing adalah line chart SVG.
   - Target design menunjukkan bar chart.
   - Existing `transaction_trend.points` bisa digunakan sebagai sumber data, tetapi visual perlu dibuat ulang atau komponen chart ditambah mode bar.

5. Period filter existing mendukung `30_days` dan `90_days`.
   - Target design menunjukkan `Monthly` dan `Weekly`.
   - Perlu mapping final:
     - `Monthly` ke range bulan berjalan atau 30 hari terakhir.
     - `Weekly` ke minggu berjalan atau 7 hari terakhir.

6. Date range picker belum ada.
   - Existing hanya period toggle.
   - Target membutuhkan date range display dan popover kalender.

7. Table `Performa UMKM Binaan` belum tersedia.
   - Existing `AgentDashboardAggregator` punya `stores`, tetapi belum lengkap untuk:
     - `category`
     - `growth_percentage`
     - `status`
     - `umkm_code` atau ID display seperti `UMKM-1015`
     - pagination
     - search
     - filter
     - detail URL

8. Endpoint `GET /api/agent/sellers` belum mendukung search/filter.
   - Data berbasis seller, sedangkan UI table berbasis UMKM/tenant.
   - Jika UI ingin per UMKM, lebih tepat menggunakan dataset tenant binaan, bukan seller.

9. Route web agent saat ini tidak memakai server-side auth guard.
   - Proteksi halaman dilakukan client-side via token `localStorage`.
   - Ini konsisten dengan portal existing, tetapi perlu diterima sebagai keputusan MVP atau diperkuat kemudian.

## Komponen yang Bisa Digunakan Ulang

### Reusable Langsung

1. Sidebar:
   - File: `resources/views/components/dashboard/sidebar.blade.php`
   - Dapat dipakai untuk layout kiri.
   - Perlu penyesuaian menu berdasarkan role agent.

2. Sidebar icon:
   - File: `resources/views/components/dashboard/sidebar-icon.blade.php`
   - Dapat dipakai untuk icon dashboard, UMKM, pencairan dana, pengaturan, support, logout.

3. Header:
   - File: `resources/views/components/dashboard/header.blade.php`
   - Dapat dipakai ulang untuk struktur header.
   - Perlu support tambahan untuk:
     - label kecil `Agent Panel`
     - period toggle `Monthly`/`Weekly`
     - user name dinamis dari API profile/dashboard, bukan hardcoded.

4. Metric card:
   - File: `resources/views/components/dashboard/metric-card.blade.php`
   - Bisa digunakan untuk 3 metric kecil di bawah chart.
   - Untuk card besar `TOTAL KOMISI SAYA`, sebaiknya buat variant baru atau component baru karena layout dan tone berbeda.

5. Icon tile:
   - File: `resources/views/components/dashboard/icon-tile.blade.php`
   - Bisa dipakai untuk metric kecil jika tetap menggunakan icon.

6. Data table:
   - File: `resources/views/components/dashboard/data-table.blade.php`
   - Bisa menjadi basis table `Performa UMKM Binaan`.
   - Perlu support:
     - header action berisi search + filter, bukan hanya title + arrow.
     - custom cell renderer dari JavaScript seperti pattern existing.
     - footer pagination.

7. Avatar initials:
   - File: `resources/views/components/dashboard/avatar-initial.blade.php`
   - Bisa dipakai untuk initial UMKM pada table.

8. Status badge:
   - File: `resources/views/components/dashboard/status-badge.blade.php`
   - Bisa dipakai untuk status `Aktif`, `Menunggu Aktivasi`, dan status lain.

9. Filter field:
   - File: `resources/views/components/dashboard/filter-field.blade.php`
   - Bisa dipakai untuk search atau date range, tetapi butuh penyesuaian agar match compact table header pada design.

10. Pagination:
    - File: `resources/views/components/dashboard/pagination.blade.php`
    - Bisa dipakai ulang untuk table footer.
    - Perlu dibuat dinamis via JavaScript berdasarkan `meta` response API.

### Reusable dengan Modifikasi

1. `resources/views/dashboard/index.blade.php`
   - Bisa menjadi referensi fetch token, redirect unauthorized, loading state, dan render dashboard.
   - Sebaiknya tidak terus dipakai sebagai view generic jika desain agent dan finance makin berbeda.
   - Rekomendasi: buat view khusus `resources/views/dashboard/agent.blade.php`.

2. `resources/views/dashboard/finance.blade.php`
   - Bisa menjadi referensi UI table enterprise:
     - filter bar
     - tab table
     - modal
     - pagination
     - fetch API dengan token
   - Jangan copy seluruh finance page tanpa membersihkan naming `finance-*`.

3. `App\Support\Dashboard\AgentDashboardAggregator`
   - Bisa dipakai sebagai basis endpoint summary dan chart.
   - Perlu ubah/extend payload agar sesuai design.

4. `App\Support\Dashboard\DashboardPeriod`
   - Bisa digunakan untuk menghitung range period.
   - Perlu tambah mapping untuk `monthly`/`weekly` atau buat adapter di controller.

5. `App\Support\Dashboard\DashboardFormatter`
   - Bisa dipakai untuk money, number, initials, growth, status label.

### Tidak Disarankan Dipakai Langsung

1. `resources/views/dashboard/empty.blade.php`
   - Hanya placeholder, tidak cukup untuk dashboard agent.

2. Endpoint `GET /api/agent/sellers`
   - Berguna untuk menu `UMKM Binaan` atau detail seller.
   - Untuk dashboard table `Performa UMKM Binaan`, data yang dibutuhkan lebih cocok per tenant/UMKM, bukan per seller.

## Data Mapping UI ke Backend

### Card `TOTAL KOMISI SAYA`

Sumber yang disarankan:

- `AgentCommissionCalculator::summary($agentId)`
- Field:
  - `total_commission`
  - `total_commission_label`
  - `available_commission`
  - `withdrawn_commission`

Payload target:

```json
{
  "total_commission": 12450000,
  "total_commission_label": "Rp 12.450.000",
  "growth_percentage": 14.2,
  "total_managed_umkm": 300
}
```

Catatan:

- Growth komisi belum dihitung oleh `AgentCommissionCalculator`.
- Perlu hitung komisi periode sekarang dibanding periode sebelumnya.
- Jika previous period `0` dan current period `0`, growth `0.0%`.
- Jika previous period `0` dan current period lebih besar dari `0`, perlu keputusan apakah ditampilkan `+100%`, `+0%`, atau label khusus.

### Chart `Pertumbuhan Transaksi`

Sumber existing:

- `AgentDashboardAggregator::transactionTrend()`

Payload existing:

```json
{
  "active_period": "30_days",
  "available_periods": [],
  "points": [
    {
      "date": "2026-06-01",
      "label": "01 Jun",
      "transaction_count": 12,
      "revenue": 1500000
    }
  ]
}
```

Kebutuhan target:

- Bar chart menggunakan `revenue` atau `transaction_count`.
- UI copy menyebut `Pertumbuhan Transaksi`, jadi default yang paling aman adalah `transaction_count`.
- Jika bisnis menginginkan volume transaksi dalam rupiah, gunakan `revenue`.
- Keputusan final perlu dikonfirmasi.

### Metric `TOTAL TRANSAKSI UMKM BINAAN`

Kemungkinan definisi:

1. Total nominal transaksi UMKM binaan dalam periode aktif.
2. Total jumlah transaksi UMKM binaan dalam periode aktif.

Karena desain menampilkan `Rp 41.450.000`, definisi yang paling sesuai adalah total nominal transaksi/revenue UMKM binaan.

Sumber existing:

- `summary.total_umkm_revenue`

Payload target:

```json
{
  "total_managed_umkm_transaction_amount": 41450000,
  "total_managed_umkm_transaction_amount_label": "Rp 41.450.000"
}
```

### Metric `TOTAL UMKM BINAAN`

Sumber existing:

- `tenant_count`
- Atau count `tenants` dengan `agent_user_id = current agent`.

Payload target:

```json
{
  "total_managed_umkm": 24,
  "total_managed_umkm_label": "24 Toko"
}
```

### Metric `TOTAL AREA BINAAN`

Sumber:

- Count distinct housing areas dari tenant binaan agent.
- Relasi tersedia: `Tenant::housingAreas()`.

Query konsep:

```php
HousingArea::query()
    ->whereHas('tenants', fn ($query) => $query->where('agent_user_id', $agentId))
    ->distinct()
    ->count('housing_areas.id');
```

Payload target:

```json
{
  "total_managed_areas": 5,
  "total_managed_areas_label": "5 Area"
}
```

### Table `Performa UMKM Binaan`

Sumber utama:

- `tenants` where `agent_user_id = current agent`.
- Relasi:
  - owner seller
  - product category/category
  - transaction items
  - housing areas

Payload row target:

```json
{
  "id": "uuid",
  "display_id": "UMKM-1015",
  "name": "Sembako Tetangga",
  "initials": "ST",
  "category": "Retail",
  "total_transaction_amount": 0,
  "total_transaction_amount_label": "Rp 0",
  "growth_percentage": 0.0,
  "growth_label": "0.0%",
  "agent_commission": 0,
  "agent_commission_label": "Rp 0",
  "status": "pending_activation",
  "status_label": "Menunggu Aktivasi",
  "detail_url": "/agent/umkm/{id}"
}
```

Status mapping yang disarankan:

- `active`: tenant punya produk aktif atau punya transaksi completed.
- `pending_activation`: tenant belum punya produk aktif dan belum punya transaksi.
- `inactive`: jika nanti ada field status tenant.

Catatan:

- Saat ini model `Tenant` tidak terlihat memiliki field `status`.
- Untuk MVP, status bisa dihitung dari aktivitas, tetapi keputusan ini perlu dikonfirmasi.

## Rekomendasi Arsitektur

### View

Rekomendasi buat view khusus:

- `resources/views/dashboard/agent.blade.php`

Update route:

```php
Route::view('/agent/dashboard', 'dashboard.agent', [
    'title' => 'Dashboard Agent',
    'headerTitle' => 'Laporan Performa',
    'panelLabel' => 'Agent Panel',
    'role' => 'agent',
    'active' => 'dashboard',
])->name('agent.dashboard');
```

Alasan:

- Desain agent berbeda signifikan dari `dashboard.index`.
- `dashboard.index` saat ini juga dipakai finance dashboard.
- View khusus mengurangi risiko regression pada finance.

### API

Ada dua opsi.

#### Opsi A: Extend `GET /api/agent/dashboard`

Tambahkan payload yang dibutuhkan dashboard agent ke endpoint existing.

Kelebihan:

- Single fetch untuk render halaman.
- Lebih sederhana untuk junior engineer.
- Cocok untuk initial dashboard snapshot.

Kekurangan:

- Search/filter/pagination table menjadi kurang ideal jika semua data dimuat sekaligus.

#### Opsi B: Split summary dan table

Gunakan:

```http
GET /api/agent/dashboard?period=monthly
GET /api/agent/managed-umkm?period=monthly&search=&status=&page=1&per_page=10
```

Kelebihan:

- Table bisa search, filter, pagination dengan benar.
- Lebih scalable jika UMKM binaan banyak.

Kekurangan:

- Perlu endpoint baru.
- Implementasi frontend sedikit lebih panjang.

Rekomendasi senior engineer:

- Pakai Opsi B jika target production dan jumlah UMKM bisa besar.
- Pakai Opsi A hanya jika MVP harus cepat dan jumlah data kecil.

Keputusan final perlu dikonfirmasi.

### Service Class

Jika memilih Opsi B, tambahkan service:

- `App\Support\Dashboard\AgentManagedUmkmPerformanceQuery`

Tanggung jawab:

- Query tenant binaan current agent.
- Apply period.
- Apply search.
- Apply filter status/kategori jika diperlukan.
- Return paginator.
- Map row response table.

Jangan menaruh query kompleks langsung di controller.

## Endpoint Requirement

### 1. Dashboard Summary

```http
GET /api/agent/dashboard?period=monthly
Authorization: Bearer {token}
```

Response target:

```json
{
  "message": "Dashboard agent berhasil diambil.",
  "data": {
    "meta": {
      "role": "agent",
      "period": "monthly",
      "generated_at": "2026-06-17T10:00:00+07:00"
    },
    "agent": {
      "id": "uuid",
      "name": "Agent XYZ",
      "email": "agent@example.com",
      "phone": "081234567890"
    },
    "summary": {
      "total_commission": {
        "value": 12450000,
        "formatted": "Rp 12.450.000",
        "growth_percentage": 14.2
      },
      "total_managed_umkm_transaction_amount": {
        "value": 41450000,
        "formatted": "Rp 41.450.000"
      },
      "total_managed_umkm": {
        "value": 24,
        "formatted": "24 Toko"
      },
      "total_managed_areas": {
        "value": 5,
        "formatted": "5 Area"
      }
    },
    "transaction_growth": {
      "active_period": "monthly",
      "date_range_label": "Oct 1 - Oct 31",
      "points": [
        {
          "date": "2026-10-01",
          "label": "MON",
          "transaction_count": 12,
          "revenue": 1500000
        }
      ]
    }
  }
}
```

### 2. Managed UMKM Performance Table

```http
GET /api/agent/managed-umkm?period=monthly&search=kopi&status=active&page=1&per_page=10
Authorization: Bearer {token}
```

Response target:

```json
{
  "message": "Performa UMKM binaan berhasil diambil.",
  "data": [
    {
      "id": "uuid",
      "display_id": "UMKM-0822",
      "name": "Kopi Bersaudara",
      "initials": "KB",
      "category": "Minuman",
      "total_transaction_amount": 42500000,
      "total_transaction_amount_label": "Rp 42.500.000",
      "growth_percentage": 12.5,
      "growth_label": "+12.5%",
      "agent_commission": 1062500,
      "agent_commission_label": "Rp 1.062.500",
      "status": "active",
      "status_label": "Aktif",
      "detail_url": null
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "last_page": 3,
    "total": 24,
    "from": 1,
    "to": 10
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

## Frontend Behavior Requirement

1. On page load:
   - Read `kresekin_token` and `kresekin_token_type` from `localStorage`.
   - If token missing, redirect to `/`.
   - Fetch dashboard summary.
   - Fetch table data.

2. Unauthorized handling:
   - If API returns `401` or `403`:
     - remove `kresekin_token`
     - remove `kresekin_token_type`
     - remove `kresekin_user_role`
     - redirect to `/`

3. Header user chip:
   - Use agent name from API response.
   - Fallback to `Agent`.

4. Period toggle:
   - `Monthly` and `Weekly` should update:
     - summary
     - chart
     - table
   - Active state must be visually clear.
   - Query parameter should be stable, recommended:
     - `period=monthly`
     - `period=weekly`

5. Date range picker:
   - Minimum MVP:
     - Display current date range label from API.
     - Date picker popover can be deferred if owner accepts.
   - Full design:
     - User can select date range.
     - Summary/chart/table refresh based on selected range.
   - This is a product decision.

6. Search:
   - Debounce 300ms or search on Enter.
   - Search by UMKM name and display ID.
   - Empty result shows `Data tidak ditemukan`.

7. Filter:
   - Button exists in UI.
   - Filter options need decision:
     - status
     - category
     - area
   - Minimum MVP can open no modal yet only if owner accepts. Better: include status/category dropdown.

8. Pagination:
   - Uses API `meta` and `links`.
   - Shows `Menampilkan {from}-{to} dari {total} UMKM` or simplified copy per design.
   - Prev disabled on first page.
   - Next disabled on last page.

9. Loading state:
   - Keep skeleton/loading classes pattern from existing dashboard.
   - Do not show stale values as real data while loading.

10. Empty state:
    - Summary values show zero:
      - `Rp 0`
      - `0 Toko`
      - `0 Area`
    - Chart shows zero bars or empty neutral state.
    - Table shows `Data tidak ditemukan`.

11. Error state:
    - If summary fails, show small inline error and keep layout stable.
    - If table fails, show `Gagal memuat data UMKM binaan.`

## Backend Calculation Requirement

### Period

Supported period target:

- `monthly`
- `weekly`

Recommended mapping:

- `monthly`: current calendar month in `Asia/Jakarta`.
- `weekly`: current week in `Asia/Jakarta`, Monday to Sunday.

Need decision:

- UI sample uses `Oct 1 - Oct 31`, suggesting calendar month.
- Existing code uses rolling `30_days`/`90_days`.

### Total Commission

Use completed transactions only:

- `transactions.status = completed`
- Sum `transaction_items.line_total` for tenants where `agent_user_id = current agent`.
- Commission = revenue x `config('api.agent_commission_rate', 0.05)`.

### Total Transaction Amount

Use completed transactions only unless owner says otherwise.

Reason:

- Commission is usually earned only from completed transaction.
- Existing calculator already uses completed transactions.

### Growth

Formula:

```php
$growth = $previous > 0
    ? (($current - $previous) / $previous) * 100
    : ($current > 0 ? 100 : 0);
```

Formatting:

- One decimal place, e.g. `+14.2%`.
- Negative value uses down indicator or red style.
- Zero value uses `+0.0%` or `0.0%`; final copy needs confirmation.

### Total Area Binaan

Count distinct housing areas related to agent tenants.

If tenant has no housing area:

- Do not count it.

### UMKM Status

Temporary derived status:

- `active` if tenant has at least one product or completed transaction.
- `pending_activation` if tenant has no products and no completed transaction.

Need decision:

- If business has an official tenant activation status, add/use that field instead of derived status.

## Development Checklist

### Phase 0 - Confirmation

- [ ] Confirm endpoint strategy: extend dashboard only or add table endpoint.
- [ ] Confirm period definition: calendar `monthly/weekly` or rolling range.
- [ ] Confirm chart metric: transaction count or revenue.
- [ ] Confirm date picker scope: display-only MVP or selectable date range.
- [ ] Confirm filter options: status, category, area, or none for MVP.
- [ ] Confirm UMKM status definition.
- [ ] Confirm whether `View Details` route is in scope.
- [ ] Confirm whether page route can remain client-side token protected for MVP.

### Phase 1 - Backend

- [ ] Add or update period handling for `monthly` and `weekly`.
- [ ] Extend `AgentDashboardAggregator` for target summary payload.
- [ ] Add total managed area calculation.
- [ ] Add commission growth calculation.
- [ ] Add transaction growth payload that supports bar chart.
- [ ] If Opsi B approved, add endpoint `GET /api/agent/managed-umkm`.
- [ ] If Opsi B approved, add controller for managed UMKM performance.
- [ ] If Opsi B approved, add query/service class for table rows.
- [ ] Add search support for UMKM name and display ID.
- [ ] Add filter support based on confirmed filter options.
- [ ] Add pagination metadata.

### Phase 2 - Frontend

- [ ] Create `resources/views/dashboard/agent.blade.php`.
- [ ] Update `/agent/dashboard` route to use `dashboard.agent`.
- [ ] Reuse `x-dashboard.sidebar`.
- [ ] Adjust sidebar menu for agent:
  - `Dashboard`
  - `UMKM Binaan`
  - `Pencairan Dana`
  - `Pengaturan`
- [ ] Reuse or extend `x-dashboard.header`.
- [ ] Build big turquoise commission card.
- [ ] Build bar chart card for `Pertumbuhan Transaksi`.
- [ ] Build 3 small metric cards.
- [ ] Build table header with search and filter button.
- [ ] Build table rows with avatar, status badge, and action link.
- [ ] Build empty state and pagination.
- [ ] Implement fetch logic with token handling.
- [ ] Implement period switching.
- [ ] Implement search and pagination fetch.
- [ ] Implement error handling.
- [ ] Make layout responsive for tablet and mobile.

### Phase 3 - Tests

- [ ] Feature test `GET /api/agent/dashboard` requires token.
- [ ] Feature test non-agent token cannot access dashboard.
- [ ] Feature test summary returns zero values for agent with no UMKM.
- [ ] Feature test summary returns correct commission and transaction amount.
- [ ] Feature test total area count uses distinct housing areas.
- [ ] Feature test table endpoint returns only UMKM owned by current agent.
- [ ] Feature test search filters UMKM by name.
- [ ] Feature test pagination metadata.
- [ ] Feature test unauthorized role cannot access table endpoint.

### Phase 4 - Manual QA

- [ ] Login as agent redirects to `/agent/dashboard`.
- [ ] Missing token redirects to `/`.
- [ ] Invalid token redirects to `/`.
- [ ] Dashboard matches design at desktop width.
- [ ] Empty state matches design.
- [ ] Search unknown UMKM shows `Data tidak ditemukan`.
- [ ] Pagination works.
- [ ] Weekly/monthly toggle refreshes data.
- [ ] Logout clears token and returns to `/`.

## Suggested File Changes

Jika development sudah diapprove, file yang kemungkinan perlu dibuat/diubah:

1. Route:
   - `routes/web.php`
   - `routes/api.php`

2. Backend:
   - `app/Support/Dashboard/AgentDashboardAggregator.php`
   - `app/Support/Dashboard/DashboardPeriod.php`
   - `app/Support/Dashboard/DashboardFormatter.php`
   - `app/Http/Controllers/Api/GetAgentDashboardController.php`
   - `app/Http/Controllers/Api/GetAgentManagedUmkmPerformanceController.php` jika endpoint baru dipilih
   - `app/Support/Dashboard/AgentManagedUmkmPerformanceQuery.php` jika endpoint baru dipilih

3. Frontend:
   - `resources/views/dashboard/agent.blade.php`
   - `resources/views/components/dashboard/sidebar.blade.php`
   - `resources/views/components/dashboard/header.blade.php`
   - `resources/views/components/dashboard/data-table.blade.php` jika perlu slot/action support
   - optional: `resources/views/components/dashboard/agent-commission-card.blade.php`
   - optional: `resources/views/components/dashboard/bar-chart-card.blade.php`

4. Tests:
   - `tests/Feature/AgentDashboardApiTest.php`
   - optional: `tests/Feature/AgentManagedUmkmPerformanceApiTest.php`

## Acceptance Criteria

1. Agent yang sudah login dapat membuka `/agent/dashboard`.
2. User tanpa token diarahkan ke `/`.
3. User non-agent tidak bisa mengambil data API agent dashboard.
4. Header menampilkan `Laporan Performa` dan `Agent Panel`.
5. Sidebar menampilkan menu sesuai desain agent.
6. Card `TOTAL KOMISI SAYA` menampilkan total komisi agent.
7. Card `TOTAL KOMISI SAYA` menampilkan total UMKM binaan.
8. Chart `Pertumbuhan Transaksi` menampilkan data sesuai periode aktif.
9. Metric `TOTAL TRANSAKSI UMKM BINAAN` menampilkan nominal transaksi UMKM binaan.
10. Metric `TOTAL UMKM BINAAN` menampilkan jumlah tenant binaan.
11. Metric `TOTAL AREA BINAAN` menampilkan jumlah area unik.
12. Table `Performa UMKM Binaan` menampilkan UMKM milik agent yang sedang login saja.
13. Search table bekerja.
14. Filter table bekerja sesuai opsi yang dikonfirmasi.
15. Empty state table menampilkan `Data tidak ditemukan`.
16. Pagination table bekerja.
17. Logout menghapus token dan kembali ke halaman login.
18. Tampilan responsif dan tidak overlap pada desktop/tablet/mobile.

## Pertanyaan untuk Keputusan Owner

1. Apakah endpoint table boleh dibuat baru sebagai `GET /api/agent/managed-umkm`, atau semua data harus tetap dari `GET /api/agent/dashboard`?
2. Untuk toggle `Monthly` dan `Weekly`, apakah definisinya calendar month/week berjalan, atau rolling 30 hari/7 hari terakhir?
3. Bar chart `Pertumbuhan Transaksi` harus menampilkan jumlah transaksi atau nominal transaksi?
4. Date range picker pada MVP perlu bisa dipilih user, atau cukup display range sesuai periode aktif?
5. Filter table perlu berisi apa saja: status, kategori, area, atau cukup search untuk MVP?
6. Status UMKM `Aktif` dan `Menunggu Aktivasi` dihitung dari aktivitas produk/transaksi, atau ada status resmi lain yang perlu ditambahkan ke database?
7. Link `View Details` diarahkan ke halaman detail UMKM dalam scope ini, atau cukup nonaktif/null dulu?
8. Apakah proteksi route web berbasis `localStorage` seperti existing portal sudah diterima untuk MVP, atau perlu migrasi ke session-auth web guard?

## Rekomendasi Scope MVP

Rekomendasi scope paling aman untuk tahap pertama:

- Buat view khusus `dashboard.agent`.
- Extend `GET /api/agent/dashboard` untuk summary dan chart.
- Tambah endpoint baru `GET /api/agent/managed-umkm` untuk table, search, filter, dan pagination.
- Period memakai `monthly` dan `weekly` berbasis calendar di timezone `Asia/Jakarta`.
- Chart memakai jumlah transaksi.
- Date range picker display-only dulu.
- Filter MVP: status dan kategori.
- `View Details` tampil tetapi disabled/null sampai halaman detail UMKM diputuskan.

Semua rekomendasi di atas tetap menunggu persetujuan owner sebelum development.
