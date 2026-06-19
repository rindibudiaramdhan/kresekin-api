# Glossary Kresekin API

## Domain

| Istilah | Definisi |
| --- | --- |
| Buyer | User yang membeli produk dan melakukan checkout. |
| Seller | User pemilik UMKM/tenant yang mengelola produk dan order. |
| Agent | User yang membina UMKM dan menerima komisi dari performa tenant binaan. |
| Finance | User internal yang mengelola pembayaran, withdrawal, dan disbursement. |
| Tenant / UMKM | Toko/usaha yang menjual produk di Kresek.in. |
| Housing Area | Area perumahan/cakupan layanan. |
| Product | Item yang dijual oleh tenant. |
| Product Category | Kategori produk, misalnya sayur, buah, sembako. |
| Product Unit | Satuan produk, misalnya pcs, kg, pack. |
| Cart | Keranjang buyer sebelum checkout. |
| Checkout | Proses mengubah cart menjadi transaksi. |
| Transaction | Order buyer yang menyimpan snapshot pembayaran, pengiriman, promo, dan item. |
| Transaction Item | Snapshot item produk dalam transaksi. |
| Disbursement | Pencairan dana transaksi ke seller. |
| Commission | Komisi agent dari revenue UMKM binaan. |
| Withdrawal | Permintaan pencairan komisi agent. |
| Promo Code | Kode diskon yang divalidasi server-side. |
| Cancellation Reason | Alasan pembatalan transaksi/order. |

## Status

| Status | Definisi |
| --- | --- |
| `pending_payment` | Transaksi menunggu pembayaran. |
| `accepted_by_store` | Order diterima toko. |
| `processing` | Order sedang diproses seller. |
| `on_the_way` | Order dalam pengiriman/perjalanan. |
| `completed` | Order selesai dan dapat dihitung sebagai revenue completed. |
| `canceled` | Order dibatalkan. |
| `pending_review` | Agent menunggu review data registrasi. |
| `approved` | Agent/withdrawal disetujui sesuai konteks. |
| `rejected` | Agent/withdrawal ditolak sesuai konteks. |
| `requested` | Withdrawal baru diajukan. |
| `paid` | Withdrawal sudah dibayar. |
| `pending_buyer_payment` | Finance menunggu pembayaran buyer. |
| `buyer_payment_confirmed` | Finance sudah mengkonfirmasi pembayaran buyer. |
| `disbursed_to_seller` | Dana sudah dicairkan ke seller. |

## Technical

| Istilah | Definisi |
| --- | --- |
| FormRequest | Class Laravel untuk validasi dan normalisasi request. |
| Middleware `session.token` | Middleware bearer token untuk API authenticated. |
| Middleware `role:*` | Middleware pembatas role API. |
| Hashed token | Token session yang disimpan server dalam bentuk hash, bukan plain token. |
| Private disk | Storage yang tidak dapat diakses publik langsung. |
| Idempotent | Operasi yang aman bila dipanggil ulang tanpa menghasilkan efek ganda. |
| N+1 query | Pola query tidak efisien karena mengambil relation berulang dalam loop. |
| Snapshot | Salinan data saat transaksi dibuat agar histori tidak berubah saat master berubah. |
| Soft delete | Data ditandai terhapus tanpa dihapus fisik dari database. |

## Metrics

| Istilah | Definisi |
| --- | --- |
| Total UMKM revenue | Total revenue dari transaksi valid/completed. |
| Total orders | Jumlah transaksi/order pada scope tertentu. |
| Active UMKM | Tenant aktif sesuai definisi produk/transaksi/status yang disepakati. |
| Growth percentage | Perbandingan current period terhadap previous period. |
| Available commission | Komisi agent yang belum terkunci oleh withdrawal requested/approved/paid. |
| Withdrawn commission | Komisi yang sudah requested/approved/paid. |
| p95 latency | 95% request selesai di bawah durasi tertentu. |
| RTO | Recovery Time Objective, batas waktu pemulihan layanan. |
| RPO | Recovery Point Objective, batas kehilangan data yang dapat diterima. |
