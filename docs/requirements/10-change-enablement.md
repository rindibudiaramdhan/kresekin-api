# Operational Change and Review Kresekin API

Dokumen ini mendefinisikan requirement perubahan operasional sensitif: perubahan status transaksi, withdrawal, disbursement, agent review, master data finance, dan perubahan kontrak API/schema.

## Change Types

Perubahan sensitif dalam Kresekin API:

1. Agent verification status change.
2. Order/transaction status change.
3. Commission withdrawal approve/reject/paid.
4. Buyer payment confirmation.
5. Seller disbursement.
6. Cancellation reason CRUD.
7. Schema migration.
8. API contract change.
9. Formula komisi atau dashboard metric change.

## State Transition Requirements

1. Setiap perubahan status harus memvalidasi current state.
2. Transisi invalid harus gagal dengan `422` atau status error semantik.
3. Actor dan timestamp harus disimpan untuk workflow finance/agent review.
4. Perubahan multi-table harus memakai transaksi database.
5. Perubahan status transaksi harus menulis status history.
6. Aksi yang sudah final seperti paid/disbursed tidak boleh bisa diulang tanpa idempotency policy.

## Approval and Review

Requirement:

1. Agent review final owner harus ditentukan sebelum fitur approval lengkap.
2. Withdrawal review dilakukan finance.
3. Reject withdrawal wajib memiliki alasan valid.
4. Mark as paid idealnya membutuhkan bukti atau reference payout bila provider belum ada.
5. Aksi review harus masuk audit trail.

## API Contract Change

Requirement:

1. Field response existing tidak boleh dihapus/ganti tipe tanpa migration/deprecation.
2. Endpoint baru harus didokumentasikan di `API_DOCUMENTATION.md`.
3. Breaking change harus memakai versioning strategy atau route baru.
4. Status code sukses/error tidak boleh berubah diam-diam.
5. Perubahan pagination harus ditest dan didokumentasikan.

## Schema Change

Requirement:

1. Migration baru harus forward-only.
2. Jangan mengubah migration lama yang sudah dipakai bersama.
3. Migration production harus kompatibel dengan data existing.
4. Backfill data harus punya plan bila data production besar.
5. Perubahan schema harus diikuti update model, request, controller, test, dan docs.

## Rollback and Release Safety

1. Release yang mengubah schema dan code harus memperhatikan urutan deploy.
2. Kolom baru sebaiknya nullable/default dulu bila client lama masih berjalan.
3. Job atau endpoint lama tidak boleh gagal saat field baru belum terisi.
4. Rollback code tidak boleh membuat data baru tidak terbaca total.

## Audit Requirements

Event minimum:

1. Semua state transition finance.
2. Semua state transition order.
3. Agent verification status changed.
4. Formula commission/rate changed bila dibuat configurable.
5. Export atau perubahan master finance.

## Open Questions

1. Apakah perlu dashboard internal untuk agent approval?
2. Apakah perubahan rate komisi perlu effective date?
3. Apakah finance action membutuhkan maker-checker approval atau cukup satu role finance?
