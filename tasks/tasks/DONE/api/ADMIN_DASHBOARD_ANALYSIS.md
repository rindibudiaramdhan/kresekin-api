# Admin Dashboard Data Analysis

Dokumen ini mencatat analisis backend untuk menyajikan data real pada dashboard admin/agent/finance berdasarkan komponen dashboard yang sudah dibuat di frontend.

## Prinsip Utama

Dashboard sebaiknya tetap memakai endpoint agregat:

```http
GET /api/agent/dashboard?period=30_days
GET /api/finance/dashboard?period=30_days
```

Setiap response perlu mengembalikan satu snapshot data yang konsisten untuk render awal dashboard:

```json
{
  "summary": {},
  "transaction_trend": {},
  "umkm_spotlight": [],
  "recent_transactions": [],
  "top_agent_commissions": [],
  "commission_summary": {}
}
```

Data dapat dihitung dari domain yang sudah ada:

- `users`
- `tenants`
- `transactions`
- `transaction_items`
- `agent_commission_withdrawals`
- `finance_transaction_disbursements`

Untuk tahap awal, query langsung masih cukup. Namun logic dashboard sebaiknya segera dipindahkan dari controller ke service/query class agar mudah dites, dicache, dan dioptimasi.

## Metric Cards

Komponen:

- Total Pendapatan UMKM
- Total Pesanan
- Total UMKM Aktif

### Agent

- `total_umkm_revenue`
  - Sum `transaction_items.line_total`
  - Hanya tenant dengan `agent_user_id = current agent`
  - Hanya transaksi `completed`
- `total_orders`
  - Count distinct `transactions.id`
  - Transaksi yang memiliki item dari tenant binaan agent
- `active_umkm`
  - Count tenant milik agent
  - Definisi lebih kuat: tenant yang memiliki produk aktif atau transaksi dalam periode tertentu

### Finance

- `total_umkm_revenue`
  - Sum `transactions.total_amount`
  - Idealnya hanya transaksi valid/completed, tergantung definisi bisnis finance
- `total_orders`
  - Count `transactions`
- `active_umkm`
  - Count tenant yang memiliki produk atau transaksi

### Growth Percentage

Bandingkan periode saat ini dengan periode sebelumnya.

Jika `period=30_days`:

- Current: 30 hari terakhir
- Previous: 30 hari sebelum current period

Formula:

```php
$growth = $previous > 0
    ? (($current - $previous) / $previous) * 100
    : ($current > 0 ? 100 : 0);
```

Helper yang disarankan:

- `dateRangeForPeriod($period)`
- `previousDateRange($period)`
- `growthPercentage($current, $previous)`

## Transaction Trend Chart

Komponen:

- `TrendChartCard`

Response:

```json
{
  "active_period": "30_days",
  "available_periods": [
    { "label": "30 Days", "value": "30_days" },
    { "label": "90 Days", "value": "90_days" }
  ],
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

### Agent

- Group transaksi per tanggal.
- Filter transaksi yang memiliki `transaction_items` dari tenant binaan agent.
- Revenue = sum `transaction_items.line_total` untuk tenant binaan agent.
- Transaction count = count distinct `transactions.id`.

### Finance

- Group langsung dari `transactions`.
- Revenue = sum `transactions.total_amount`.
- Transaction count = count `transactions.id`.

### Catatan

Backend harus mengisi tanggal kosong dengan nilai `0`, agar chart frontend stabil.

Rekomendasi grouping:

- `30_days`: daily points
- `90_days`: weekly points

## UMKM Spotlight

Komponen:

- `SpotlightCard`

Tujuan: menampilkan UMKM dengan pertumbuhan terbaik.

Response:

```json
{
  "id": "uuid",
  "name": "Tokoma",
  "initials": "T",
  "category": "Sayur",
  "growth_percentage": 240,
  "detail_url": null
}
```

Cara hitung:

1. Ambil revenue per tenant untuk current period.
2. Ambil revenue per tenant untuk previous period.
3. Hitung growth percentage per tenant.
4. Sort descending by growth.
5. Ambil top 2 atau top 5.

Filter:

- Agent: hanya tenant dengan `agent_user_id = current agent`
- Finance: semua tenant

Edge case:

- Tenant dengan previous revenue `0` dan current revenue besar bisa mendominasi ranking.
- Gunakan threshold seperti:
  - minimal current revenue `>= 100000`, atau
  - minimal transaction count `>= 3`, atau
  - cap growth saat previous revenue `0`

## Recent Transactions

Komponen:

- `DataTable`
- `StatusBadge`
- `AvatarInitial`

### Agent

- Ambil transaksi terbaru yang memiliki item dari tenant binaan agent.
- Total yang ditampilkan sebaiknya `agent_subtotal_amount`, bukan full transaction total.
- Jika transaksi berisi banyak tenant:
  - tampilkan tenant pertama + count tambahan, atau
  - kirim array `store_names`.

### Finance

- Ambil transaksi terbaru global.
- Total = `transactions.total_amount`.
- UMKM = store names dari item transaksi.

Response minimal:

```json
{
  "id": "uuid",
  "order_number": "#26032301CATSYR",
  "transaction_at": "2026-06-15T10:00:00+07:00",
  "transaction_date_label": "15 Jun 2026",
  "umkm": {
    "id": "uuid",
    "name": "Kopi Nusantara",
    "initials": "KN"
  },
  "total_amount": 1240,
  "total_amount_formatted": "Rp 1.240",
  "status": "pending",
  "status_label": "Pending",
  "raw_status": "sedang diproses",
  "status_code": "processing"
}
```

Status mapping UI:

- `success`: `completed`
- `pending`: `pending_payment`, `accepted_by_store`, `processing`, `on_the_way`
- `failed`: `canceled`

## Top Agent Commissions

Komponen:

- `TopAgentCommissionsTable`

Komponen ini paling relevan untuk finance/admin. Untuk agent pribadi, data ini kurang relevan.

### Finance

Cara hitung:

1. Group tenant revenue by `agent_user_id`.
2. Join `users` sebagai agent.
3. Count managed tenants.
4. Sum completed revenue.
5. Commission = revenue * commission rate.
6. Sort by commission descending.
7. Limit 3.

Response:

```json
{
  "agent": {
    "id": "uuid",
    "name": "Agent XYZ"
  },
  "managed_umkm_count": 42,
  "managed_umkm_label": "42 Toko",
  "store_revenue": 285000,
  "store_revenue_formatted": "Rp 285.000",
  "agent_commission": 142500,
  "agent_commission_formatted": "Rp 142.500",
  "status": "approved",
  "status_label": "BERHASIL"
}
```

### Agent

Rekomendasi:

1. Sembunyikan komponen ini dari dashboard agent, atau
2. Ubah menjadi `Top UMKM Commissions`:
   - group by tenant milik agent
   - tampilkan UMKM, revenue, estimated commission

Status komisi:

- Jika berbasis withdrawal: `approved`, `processing`, `rejected`
- Jika hanya estimasi: gunakan status `estimated`, bukan approval status

## Commission Summary

Komponen:

- `SummaryHighlightCard`

### Agent

Gunakan existing `AgentCommissionCalculator`:

- `total_commission`
- `withdrawn_commission`
- `available_commission`

### Finance

Cara hitung:

- Total komisi semua agent:
  - sum completed revenue tenant yang memiliki `agent_user_id`
  - commission = revenue * rate
- Total agent:
  - count users dengan role `agent`, atau
  - count active agents yang punya tenant

Response:

```json
{
  "total_agent_commission": 482500000,
  "total_agent_commission_formatted": "Rp 482.500.000",
  "total_agents": 842,
  "total_agents_formatted": "842"
}
```

## Arsitektur Backend Yang Disarankan

Jangan menaruh semua query dashboard di controller.

Struktur yang disarankan:

```text
app/Support/Dashboard/
  DashboardPeriod.php
  DashboardFormatter.php
  AgentDashboardAggregator.php
  FinanceDashboardAggregator.php
```

Controller menjadi tipis:

```php
public function __invoke(Request $request, AgentDashboardAggregator $dashboard): JsonResponse
{
    return response()->json([
        'message' => 'Dashboard agent berhasil diambil.',
        'data' => $dashboard->forUser(
            $request->user(),
            $request->query('period', '30_days')
        ),
    ]);
}
```

`AgentDashboardAggregator`:

- `summary()`
- `transactionTrend()`
- `umkmSpotlight()`
- `recentTransactions()`
- `commissionSummary()`

`FinanceDashboardAggregator`:

- `summary()`
- `transactionTrend()`
- `umkmSpotlight()`
- `recentTransactions()`
- `topAgentCommissions()`
- `commissionSummary()`

## Query Performance

Area yang rawan mahal:

- trend chart group by date
- UMKM spotlight growth calculation
- top agent commissions

Index yang perlu dipastikan:

- `transactions.transaction_at`
- `transactions.status`
- `transaction_items.transaction_id`
- `transaction_items.tenant_id`
- `tenants.agent_user_id`
- `tenants.owner_user_id`
- `agent_commission_withdrawals.agent_user_id`
- `agent_commission_withdrawals.status`

Cache yang disarankan:

```php
Cache::remember("dashboard:agent:{$agentId}:{$period}", now()->addMinutes(5), fn () => ...)
```

```php
Cache::remember("dashboard:finance:{$period}", now()->addMinutes(5), fn () => ...)
```

## Prioritas Implementasi Real Data

1. Rapikan response aggregator tanpa dummy untuk:
   - summary
   - recent transactions
   - commission summary

2. Implement trend chart real:
   - current period daily/weekly grouping
   - fill missing dates

3. Implement UMKM spotlight real:
   - revenue current vs previous
   - growth calculation
   - minimum threshold

4. Implement top agent commissions real untuk finance.

5. Sesuaikan frontend:
   - agent dashboard jangan memaksa tampilkan top agent commissions jika tidak relevan

## Kesimpulan

Struktur endpoint saat ini sudah bisa dikembangkan. Langkah terbaik adalah memindahkan logika dashboard dari controller ke aggregator service, lalu mengganti dummy satu per satu dengan query real yang terukur.
