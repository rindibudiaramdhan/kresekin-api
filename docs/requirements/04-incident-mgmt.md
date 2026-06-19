# Transaction and Order Management Kresekin API

Dokumen ini mendefinisikan requirement cart, checkout, transaksi, status order, histori, dan pembatalan.

## Transaction Entity

Transaksi berada di tabel `transactions` dan memiliki item di `transaction_items`.

Field penting:

1. `user_id`
2. `order_number`
3. `status`
4. `transaction_at`
5. `subtotal_amount`
6. `delivery_fee`
7. `discount_amount`
8. `total_amount`
9. Snapshot metode pengiriman, waktu ambil/kirim, metode pembayaran, dan promo
10. Data pembatalan

Status transaksi yang tersedia:

1. `pending_payment`
2. `accepted_by_store`
3. `processing`
4. `on_the_way`
5. `completed`
6. `canceled`

## Cart

Requirement:

1. Buyer memiliki satu cart aktif.
2. Cart item harus terkait ke produk valid.
3. Quantity harus divalidasi server-side.
4. Update quantity nol atau delete harus menghasilkan state cart yang konsisten.
5. Cart harus menghitung ulang subtotal dari harga produk server-side.
6. Delivery method cart dapat diubah sebelum checkout.
7. Buyer tidak boleh mengakses cart user lain.

## Checkout

Requirement:

1. Checkout hanya boleh dilakukan buyer authenticated.
2. Cart kosong tidak boleh checkout.
3. Server harus menghitung ulang subtotal, delivery fee, discount, dan total.
4. Server harus membuat transaksi, item transaksi, status history awal, dan disbursement finance bila diperlukan dalam satu transaksi database.
5. Data produk, tenant, harga, metode pembayaran, metode pengiriman, dan promo harus disnapshot.
6. Checkout harus menghasilkan `order_number` unik.
7. Jika promo invalid pada saat checkout, request harus gagal eksplisit.
8. Setelah checkout sukses, cart harus dibersihkan atau ditandai selesai sesuai desain persistence.

## Order Lifecycle

Transisi status harus mengikuti aturan domain, bukan menerima target status bebas dari client.

Baseline transisi:

| Dari | Ke | Aktor |
| --- | --- | --- |
| `pending_payment` | `accepted_by_store` | Seller atau finance setelah pembayaran valid |
| `accepted_by_store` | `processing` | Seller |
| `processing` | `on_the_way` | Seller |
| `on_the_way` | `completed` | Seller/system sesuai keputusan produk |
| Status aktif | `canceled` | Seller/buyer/finance sesuai policy |

Requirement:

1. Update status order seller wajib memeriksa ownership tenant.
2. Setiap perubahan status harus menulis `transaction_status_histories`.
3. Status history harus menyimpan urutan, status, timestamp, dan actor bila tersedia.
4. Transaksi completed menjadi dasar perhitungan revenue dan komisi agent.
5. Transaksi canceled tidak boleh dihitung sebagai revenue completed.

## Buyer Transaction History

Requirement:

1. Buyer hanya melihat transaksi sendiri.
2. List transaksi harus paginated.
3. Detail transaksi harus memuat item, tenant/store, status, pembayaran, pengiriman, promo, dan histori status yang diperlukan client.
4. Response harus memakai status code dan label yang konsisten.

## Seller Order Management

Requirement:

1. Seller hanya melihat order yang mengandung item dari tenant miliknya.
2. Jika satu transaksi berisi item dari beberapa tenant, seller hanya boleh melihat porsi item miliknya.
3. Total yang ditampilkan ke seller harus jelas apakah total transaksi penuh atau subtotal tenant seller.
4. Seller dapat mengubah status order sesuai transisi yang diizinkan.
5. Seller dapat melihat alasan pembatalan aktif.

## Cancellation

Requirement:

1. Pembatalan harus memiliki status `canceled`.
2. Kategori alasan pembatalan harus berasal dari master aktif bila dikirim.
3. Teks alasan tambahan boleh disimpan sesuai validasi.
4. Pembatalan harus menulis status history.
5. Pembatalan pada transaksi yang sudah finance/disbursement lanjut membutuhkan policy khusus.

## Audit Requirements

Event minimum:

1. Checkout created.
2. Payment confirmed.
3. Order status changed.
4. Transaction canceled.
5. Promo applied atau rejected pada checkout.
6. Perubahan disbursement yang berasal dari transaksi.

## Open Questions

1. Siapa yang final menandai transaksi `completed`: seller, buyer, system, atau finance?
2. Apakah transaksi multi-tenant boleh terjadi dalam satu checkout?
3. Bagaimana aturan refund/cancel setelah buyer payment confirmed?
4. Apakah stok harus dikunci saat cart, checkout, atau saat seller accept?
