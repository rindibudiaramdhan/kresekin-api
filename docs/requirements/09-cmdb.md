# Data Model and Ownership Kresekin API

Dokumen ini mengadaptasi konsep CMDB menjadi peta entity, relasi, ownership, dan data quality untuk Kresekin API.

## Core Entity Model

Entity utama:

| Entity | Peran |
| --- | --- |
| `users` | Akun buyer, seller, agent, finance, OTP, profil, payout, status agent |
| `user_session_tokens` | Session bearer token hashed dan expiry |
| `user_devices` | Device token notifikasi |
| `housing_areas` | Area layanan |
| `tenants` | UMKM/toko, owner seller, agent pembina |
| `products` | Produk tenant |
| `product_categories` | Kategori produk |
| `product_units` | Satuan produk |
| `carts`, `cart_items` | Cart buyer |
| `transactions` | Order/checkout buyer |
| `transaction_items` | Snapshot item transaksi |
| `transaction_status_histories` | Histori status transaksi |
| `promo_codes` | Promo dan diskon |
| `agent_commission_withdrawals` | Withdrawal komisi agent |
| `finance_transaction_disbursements` | Workflow finance per tenant/transaksi |
| `cancellation_reason_categories` | Master alasan pembatalan |

## Ownership Rules

1. `users.role` menentukan capability umum.
2. `transactions.user_id` menentukan buyer owner.
3. `tenants.owner_user_id` menentukan seller owner.
4. `tenants.agent_user_id` menentukan agent pembina.
5. `products.tenant_id` menentukan ownership turunan dari tenant.
6. `transaction_items.tenant_id` menjadi basis scoping seller/agent.
7. `agent_commission_withdrawals.agent_user_id` menentukan owner withdrawal.
8. Finance disbursement terkait transaksi, tenant, dan seller.

## Relationship Requirements

1. Relasi Eloquent harus didefinisikan untuk ownership dan query umum.
2. Query role-specific harus memakai relasi ownership, bukan filter lepas yang mudah salah.
3. Deleting/soft deleting master data tidak boleh memutus histori transaksi.
4. Transaction item harus menyimpan snapshot data produk yang dibutuhkan untuk histori.
5. Perubahan tenant/product setelah checkout tidak boleh mengubah histori order lama.

## Data Quality

Requirement:

1. UUID digunakan untuk entity aplikasi.
2. Uang dan quantity disimpan sebagai integer.
3. Timestamp penting dicast sebagai datetime.
4. Status domain berasal dari constants model.
5. Seeder reference data harus idempotent.
6. Index perlu ditambahkan untuk foreign key dan filter dashboard/list yang sering dipakai.
7. Secret dan data berisiko tinggi seperti OTP, session token, password, credential bank, dan path dokumen identitas harus disembunyikan dari serialization default maupun response manual.
8. Data pribadi operasional hanya boleh dipetakan secara eksplisit pada response bila diperlukan untuk use case, dibatasi ke role dan ownership yang berwenang, serta tidak boleh berasal dari serialization model mentah. Contohnya, alamat dan koordinat Buyer dapat diberikan kepada Seller yang memiliki item pada order tersebut untuk kebutuhan fulfillment.

## Import and External Reference

Saat ini tidak ada import massal formal. Jika data tenant/product/master diimpor:

1. Import harus validasi duplicate dan ownership.
2. Import harus bisa diulang aman atau memiliki idempotency key.
3. Error import harus bisa ditelusuri tanpa menyimpan data sensitif di log.
4. Import finance tidak boleh mengubah nominal historis tanpa audit.

## Verification and Audit Cycle

Candidate audit data quality:

1. Tenant tanpa owner seller.
2. Tenant agent mismatch.
3. Produk tanpa tenant valid.
4. Transaction item tanpa snapshot penting.
5. Withdrawal amount melebihi available commission.
6. Disbursement status tidak sesuai transaksi.
7. User agent tanpa consent timestamp.

## Open Questions

1. Apakah perlu soft delete standar untuk tenant dan produk?
2. Apakah transaksi multi-tenant memang didukung penuh?
3. Apakah agent dapat berpindah area atau tenant binaan dipindahkan antar agent?
4. Apakah diperlukan audit table formal terpisah untuk semua entity high-risk?
