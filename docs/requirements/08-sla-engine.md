# Reliability and Metrics Engine Kresekin API

Dokumen ini mengadaptasi konsep SLA engine menjadi requirement reliability, performa, dan kalkulasi metrik untuk Kresekin API.

## API Reliability Targets

Target awal:

| Area | Target |
| --- | --- |
| Healthcheck | p95 kurang dari 300 ms |
| Auth OTP verify | p95 kurang dari 1 detik tanpa bottleneck provider |
| List/detail umum | p95 kurang dari 1 detik pada dataset normal |
| Dashboard | p95 kurang dari 2 detik untuk MVP |
| Checkout/status finance | Integrity lebih penting daripada latency, p95 target kurang dari 2 detik |
| Availability MVP | 99.5% bulanan |

Target harus divalidasi ulang setelah traffic production tersedia.

## Business Metrics

Metrik utama:

1. Total revenue UMKM.
2. Total order.
3. UMKM aktif.
4. Growth percentage current vs previous period.
5. Trend transaksi harian/mingguan.
6. Komisi total, withdrawn, dan available.
7. Status withdrawal.
8. Status buyer payment dan seller disbursement.

## Metric Calculation Rules

1. Revenue completed harus mengecualikan transaksi canceled.
2. Agent revenue dihitung dari item tenant binaan, bukan total transaksi penuh bila transaksi multi-tenant.
3. Finance revenue dapat memakai total transaksi global sesuai definisi status valid.
4. Growth percentage harus konsisten lintas dashboard.
5. Nominal uang harus integer.
6. Semua metrik harus bisa diuji dengan data fixture kecil.

## Dashboard Engine Implementation

Requirement:

1. Logic agregasi dashboard ditempatkan di `app/Support/Dashboard`.
2. Controller hanya mengorkestrasi request, period, user, dan response.
3. Query harus menghindari N+1.
4. Filter periode harus dinormalisasi oleh class seperti `DashboardPeriod`.
5. Formatter dashboard harus menghasilkan label yang konsisten.
6. Query agent dan finance harus dipisahkan agar scoping tidak tercampur.

## Warning and Breach Analogy

Tidak ada SLA tiket di Kresekin saat ini. Namun workflow finance memiliki risiko aging.

Candidate alert untuk fase berikutnya:

1. Withdrawal requested lebih dari batas hari tanpa approval.
2. Withdrawal approved lebih dari batas hari belum paid.
3. Buyer payment pending terlalu lama.
4. Disbursement belum dilakukan setelah payment confirmed.
5. OTP resend/verify failure rate tinggi.

## Observability

Requirement:

1. Healthcheck `/api/vershealthcheck` harus tetap ringan dan tidak bergantung dependency eksternal berat.
2. Error 5xx, latency endpoint, queue failure, dan provider failure harus bisa diinvestigasi dari log.
3. Log tidak boleh memuat OTP, token, credential bank lengkap, atau path dokumen identitas sensitif.
4. Endpoint mahal seperti dashboard dan upload perlu metric/monitoring saat production.

## Audit Requirements

1. Perubahan target metrik finance atau formula komisi harus didokumentasikan.
2. Export/report yang bersifat finance harus diaudit bila ditambahkan.
3. Alert yang mengubah status atau mengirim notifikasi harus menyimpan event aman.

## Open Questions

1. Berapa batas aging finance untuk approval withdrawal dan disbursement?
2. Apakah production akan memakai queue worker untuk notifikasi/alert?
3. Apakah metrik perlu disimpan sebagai snapshot periodik atau dihitung request-time?
