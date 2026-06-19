# Dashboard and Reporting Kresekin API

Dokumen ini mendefinisikan requirement dashboard dan reporting operasional untuk seller, agent, dan finance.

## Overview

Dashboard Kresekin harus menyajikan snapshot data yang cukup lengkap untuk render awal. Agregasi dilakukan server-side melalui support/query class, bukan dihitung client.

Komponen data utama:

1. Summary metric.
2. Transaction trend.
3. UMKM spotlight.
4. Recent transactions.
5. Commission summary.
6. Finance transaction/disbursement status.

## Dashboard Period

Requirement:

1. Period filter harus divalidasi.
2. Period awal minimal mendukung `30_days` dan `90_days`.
3. Current range dan previous range harus dihitung konsisten.
4. Growth percentage harus memakai formula yang sama lintas dashboard.
5. Response harus menyertakan active period dan available periods bila client membutuhkannya.

## Seller Dashboard

Endpoint aktif berada di `/api/seller/dashboard` dan endpoint granular turunannya.

Requirement:

1. Seller hanya melihat data tenant miliknya.
2. Revenue hari ini dihitung dari transaksi valid sesuai definisi status.
3. Count order hari ini harus dikelompokkan per status.
4. Preview order baru tidak boleh memuat order tenant lain.
5. Top product dihitung dari item transaksi tenant seller.
6. Dashboard tidak boleh melakukan N+1 query saat memetakan tenant, produk, dan transaksi.

## Agent Dashboard

Requirement:

1. Agent hanya melihat tenant dengan `agent_user_id` dirinya.
2. `total_umkm_revenue` dihitung dari `transaction_items.line_total` untuk tenant binaan dan transaksi completed.
3. `total_orders` menghitung distinct transaksi yang memiliki item dari tenant binaan.
4. `active_umkm` menghitung tenant binaan yang memenuhi definisi aktif.
5. Commission summary harus memakai `AgentCommissionCalculator`.
6. Recent transactions agent harus jelas memakai subtotal tenant binaan, bukan selalu total transaksi penuh.

## Finance Dashboard

Requirement:

1. Finance dashboard dapat melihat agregat global transaksi, revenue, UMKM, disbursement, dan withdrawal.
2. Revenue finance idealnya memakai `transactions.total_amount` untuk transaksi valid.
3. Finance harus dapat memfilter transaksi berdasarkan status pembayaran/disbursement bila endpoint list mendukungnya.
4. Dashboard finance tidak boleh mengekspos OTP, token, atau dokumen identitas.
5. Query finance harus disiapkan untuk pagination dan filter karena dataset global akan lebih besar.

## Reporting Data Shape

Response dashboard sebaiknya stabil dalam bentuk:

1. `summary`
2. `transaction_trend`
3. `umkm_spotlight`
4. `recent_transactions`
5. `commission_summary`
6. `finance_summary` bila khusus finance

Nominal uang dikirim integer dan boleh ditambah label display terpisah.

## Accuracy Rules

1. Data uang dihitung dari database, bukan input client.
2. Transaksi canceled tidak masuk revenue completed.
3. Tanggal trend kosong harus diisi nol.
4. Perhitungan growth saat previous value nol harus punya aturan eksplisit.
5. Semua query dashboard role-specific harus punya test scope data.

## Audit Requirements

Dashboard read biasa tidak wajib diaudit satu per satu. Namun export report, akses data sensitif, atau download CSV finance harus diaudit bila fitur ditambahkan.

## Open Questions

1. Apakah dashboard perlu near-real-time atau cukup request-time aggregation?
2. Apakah finance membutuhkan export CSV di MVP?
3. Definisi final UMKM aktif: tenant punya produk aktif, transaksi dalam periode, atau status eksplisit?
