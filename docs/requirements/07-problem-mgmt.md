# Commission and Finance Workflow Kresekin API

Dokumen ini mendefinisikan requirement komisi agent, withdrawal, konfirmasi pembayaran buyer, dan disbursement ke seller.

## Commission Model

Komisi agent dihitung dari revenue completed UMKM binaan.

Baseline saat ini:

1. Revenue agent berasal dari `transaction_items.line_total`.
2. Item difilter berdasarkan tenant dengan `agent_user_id = agent`.
3. Transaksi harus berstatus completed.
4. Rate komisi berasal dari `config('api.agent_commission_rate', 0.05)`.
5. Withdrawal dengan status requested, approved, atau paid mengunci komisi agar tidak bisa ditarik ulang.

## Agent Withdrawal

Status withdrawal:

1. `requested`
2. `approved`
3. `paid`
4. `rejected`

Requirement:

1. Agent hanya boleh membuat withdrawal untuk dirinya sendiri.
2. Amount withdrawal tidak boleh melebihi available commission.
3. Withdrawal harus menyimpan requested timestamp.
4. Agent dapat melihat histori withdrawal miliknya.
5. Withdrawal yang sudah requested/approved/paid harus mengurangi available commission.
6. Rejected withdrawal harus menyimpan alasan yang valid.

## Finance Withdrawal Review

Requirement:

1. Finance dapat melihat summary dan list withdrawal.
2. Finance dapat approve, reject, dan mark as paid.
3. Approve hanya boleh dari status `requested`.
4. Reject hanya boleh dari status `requested` atau sesuai policy final.
5. Mark as paid hanya boleh dari status `approved`.
6. Aksi finance harus menyimpan actor id dan timestamp: `approved_by_user_id`, `rejected_by_user_id`, `paid_by_user_id`, `approved_at`, `rejected_at`, `paid_at`.
7. State transition harus memakai transaksi database dan current-state guard.

## Finance Transaction and Disbursement

Disbursement finance berada di `finance_transaction_disbursements`.

Status:

1. `pending_buyer_payment`
2. `buyer_payment_confirmed`
3. `disbursed_to_seller`

Requirement:

1. Finance dapat melihat list dan detail transaksi finance.
2. Finance dapat mengkonfirmasi pembayaran buyer.
3. Finance dapat menandai disbursement sudah dicairkan ke seller.
4. Konfirmasi pembayaran harus menyimpan `buyer_payment_confirmed_at` dan actor.
5. Disbursement harus menyimpan `disbursed_at` dan actor.
6. Aksi disbursement harus idempotent atau gagal eksplisit bila status tidak valid.
7. Nominal disbursement harus dihitung dari transaksi/item server-side.

## Cancellation Reason Finance

Requirement:

1. Finance dapat mengelola kategori alasan pembatalan.
2. Kategori yang sudah dipakai transaksi tidak boleh dihapus secara destructive bila merusak histori.
3. CRUD kategori harus memiliki validasi nama/status.

## Audit Requirements

Event minimum:

1. Commission withdrawal requested.
2. Withdrawal approved.
3. Withdrawal rejected.
4. Withdrawal marked as paid.
5. Buyer payment confirmed.
6. Disbursement marked as paid to seller.
7. Cancellation reason created/updated/deleted.

## Open Questions

1. Apakah komisi dihitung dari gross revenue, net setelah diskon, atau setelah fee?
2. Apakah ada minimum withdrawal?
3. Apakah withdrawal membutuhkan bukti transfer upload?
4. Apakah payout provider akan otomatis mengeksekusi paid state?
