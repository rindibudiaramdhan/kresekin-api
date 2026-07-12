# Fitur Detail Transaksi Web Finance

Tanggal: 2026-07-12

## Ringkasan

Web finance membutuhkan fitur untuk melihat detail setiap transaksi yang masuk dari tabel Finance Management. Trigger fitur ini berasal dari button ikon `eye` pada kolom action di setiap row tabel transaksi.

Dokumen ini berisi task dan requirement awal sebelum implementasi. Belum ada perubahan kode aplikasi, database, test, atau dokumentasi API.

## Acuan Dokumen

Task ini mengikuti aturan pada:

1. `docs/requirements/01-architecture-nfr.md`
2. `docs/requirements/02-roles-permissions.md`
3. `docs/requirements/06-knowledge-mgmt.md`
4. `docs/requirements/07-problem-mgmt.md`
5. `docs/requirements/13-engineering-standards.md`
6. `docs/requirements/14-design-language.md`
7. `docs/adr/004-role-based-access-with-ownership-scoping.md`
8. `docs/adr/006-finance-workflow-manual-first.md`
9. `docs/adr/007-snapshot-transaction-data.md`
10. `docs/adr/008-server-side-financial-calculation.md`

Aturan penting yang perlu dijaga:

1. Endpoint finance wajib memakai middleware `session.token`.
2. Endpoint finance wajib memakai middleware `role:finance`.
3. Response API harus memakai allowlist field, bukan model mentah.
4. Nominal uang harus berupa integer dan boleh ditambah label display terpisah.
5. Data uang, disbursement, dan komisi harus berasal dari database/server-side calculation.
6. Detail transaksi finance tidak boleh mengekspos OTP, token, credential, dokumen identitas, atau data sensitif yang tidak dibutuhkan use case.
7. Nomor rekening hanya boleh ditampilkan masked kecuali ada keputusan produk dan kontrol akses eksplisit.
8. Setiap perubahan behavior harus memiliki regression test.
9. Jika detail dipakai untuk aksi finance berikutnya, state transition tetap harus divalidasi server-side pada endpoint aksi, bukan dipercaya dari payload detail.

## Kondisi Saat Ini

1. Route finance sudah berada di prefix `/api/finance` dengan middleware `session.token` dan `role:finance`.
2. Endpoint list transaksi finance sudah tersedia:
   - `GET /api/finance/transactions`
3. Endpoint detail transaksi finance sudah tersedia:
   - `GET /api/finance/transactions/{id}`
4. List finance saat ini berbasis `finance_transaction_disbursements` dan memuat ringkasan buyer, store, seller, bank masked, status disbursement, dan ringkasan transaksi.
5. Detail finance saat ini mengambil data dari `transactions`, melakukan sinkronisasi disbursement, dan memuat buyer, item, nominal, status, serta daftar disbursement.
6. Dokumentasi `API_DOCUMENTATION.md` masih perlu diselaraskan bila shape response final berbeda dari implementasi aktual.
7. Trigger UI yang diinginkan adalah button ikon `eye` di row tabel web finance.

## Tujuan

Menyediakan detail transaksi finance yang dapat dibuka dari tabel web finance agar user finance dapat memeriksa informasi buyer, seller, toko, item pesanan, nominal, status transaksi, status pembayaran buyer, dan status pencairan seller sebelum melakukan aksi lanjutan.

## Keputusan yang Perlu Dikonfirmasi

1. Detail ditampilkan sebagai modal, drawer, atau halaman detail terpisah.
2. Parameter detail memakai `transaction.id` atau `finance_transaction_disbursement.id` dari row tabel.
3. Bila satu transaksi memiliki beberapa disbursement tenant, apakah klik eye pada satu row menampilkan:
   - detail transaksi penuh beserta semua disbursement, atau
   - detail khusus disbursement pada row tersebut.
4. Field rekening seller yang boleh tampil:
   - selalu masked, atau
   - full account number hanya untuk role finance tertentu.
5. Apakah detail membutuhkan bukti pembayaran buyer atau bukti transfer seller bila fitur upload dokumen ditambahkan.
6. Apakah akses detail transaksi finance perlu dicatat sebagai audit event.
7. Apakah detail harus memuat status history transaksi lengkap atau cukup status saat ini.

## Rekomendasi MVP

1. Klik ikon `eye` membuka modal atau drawer detail tanpa meninggalkan halaman tabel.
2. Frontend memakai `transaction.id` dari payload list dan memanggil `GET /api/finance/transactions/{id}`.
3. Detail menampilkan transaksi penuh, termasuk semua disbursement seller yang terkait transaksi tersebut.
4. Rekening seller ditampilkan masked pada response detail.
5. Detail read-only; aksi konfirmasi pembayaran dan pencairan tetap memakai endpoint aksi yang sudah ada.
6. Payload list tetap ringan; data lengkap hanya dimuat saat user menekan ikon `eye`.
7. Jika row tabel merepresentasikan satu disbursement, detail tetap menandai disbursement yang dipilih melalui `selected_disbursement_id` di state frontend, bukan mengubah kontrak endpoint transaksi.

## Scope

1. Menentukan kontrak detail transaksi finance untuk kebutuhan web finance.
2. Menyelaraskan trigger button `eye` pada tabel dengan endpoint detail.
3. Memastikan response detail berisi field yang cukup untuk UI detail.
4. Memastikan field sensitif tidak bocor.
5. Menyiapkan task frontend untuk loading, empty, error, dan not found state.
6. Menyiapkan test backend untuk auth, role, not found, response shape, dan masking data sensitif.
7. Memperbarui dokumentasi API bila kontrak endpoint berubah.

## Out of Scope Awal

1. Implementasi kode aplikasi.
2. Perubahan database/migration.
3. Perubahan workflow approve/reject/confirm/disburse.
4. Upload bukti pembayaran atau bukti transfer.
5. Export detail transaksi ke PDF/CSV.
6. Audit log read detail, kecuali diputuskan wajib.
7. Pembukaan full nomor rekening seller.
8. Integrasi payment gateway atau payout provider.

## Task Analisis

1. Review payload `GET /api/finance/transactions` untuk memastikan row tabel memiliki `transaction.id` yang dapat dipakai button `eye`.
2. Review implementasi `GET /api/finance/transactions/{id}` dan pastikan parameter `{id}` konsisten sebagai `transaction.id`.
3. Tentukan apakah web finance membutuhkan detail per transaksi atau per disbursement.
4. Petakan field yang tampil di desain web finance:
   - informasi transaksi
   - informasi buyer
   - informasi seller/toko
   - daftar item
   - ringkasan pembayaran
   - ringkasan disbursement
   - status history
5. Tentukan status label yang dipakai UI untuk transaksi dan disbursement agar konsisten dengan tabel.
6. Tentukan apakah tombol aksi lanjutan tetap muncul di detail atau hanya di tabel.
7. Review field sensitif pada model `User`, `Tenant`, `Transaction`, dan `FinanceTransactionDisbursement`.
8. Tentukan apakah dokumentasi `API_DOCUMENTATION.md` perlu diperbaiki agar sama dengan response aktual.

## Task Backend/API

1. Pastikan route berikut tetap berada dalam group middleware `session.token` dan `role:finance`:

```http
GET /api/finance/transactions/{id}
```

2. Pastikan endpoint menerima `{id}` sebagai `transaction.id`.
3. Jika frontend lebih mudah memakai `disbursement.id`, tambahkan endpoint terpisah atau query eksplisit, jangan membuat `{id}` ambigu.
4. Query detail harus eager load relation yang dibutuhkan untuk menghindari N+1:
   - buyer/user
   - items
   - tenant
   - seller/owner
   - finance disbursements
   - status histories jika ditampilkan
5. Jalankan sinkronisasi disbursement sebelum response jika transaksi belum memiliki record disbursement.
6. Response harus memakai allowlist field.
7. Jangan mengembalikan full model `Transaction`, `User`, `Tenant`, atau `FinanceTransactionDisbursement`.
8. Nominal uang harus dikirim sebagai integer dan label terpisah.
9. Nomor rekening seller harus masked.
10. Field buyer dan seller cukup memuat data kontak yang diperlukan finance.
11. Jika transaksi tidak ditemukan, kembalikan `404` dengan message yang jelas.
12. Jika role bukan finance, kembalikan `403` melalui middleware.
13. Jika unauthenticated, kembalikan `401` melalui middleware.
14. Pastikan endpoint detail tidak mengubah status transaksi atau disbursement.
15. Pastikan endpoint detail tidak mempercayai status dari client untuk aksi finance.

## Rekomendasi Response Detail

```json
{
  "message": "Detail transaksi finance berhasil diambil.",
  "data": {
    "id": "dddddddd-dddd-4ddd-8ddd-dddddddddddd",
    "order_number": "INV-20260712-0001",
    "status": "pending_payment",
    "status_code": "pending_payment",
    "status_label": "Menunggu Pembayaran",
    "buyer": {
      "id": "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
      "name": "Budi",
      "email": "budi@example.com",
      "phone": "081234567890"
    },
    "amounts": {
      "subtotal_amount": 50000,
      "subtotal_amount_label": "Rp. 50.000",
      "delivery_fee": 5000,
      "delivery_fee_label": "Rp. 5.000",
      "discount_amount": 0,
      "discount_amount_label": "Rp. 0",
      "total_amount": 55000,
      "total_amount_label": "Rp. 55.000"
    },
    "payment": {
      "payment_method": "bank_transfer",
      "payment_method_option_name": "BCA",
      "buyer_payment_status": "pending_buyer_payment",
      "buyer_payment_confirmed_at": null
    },
    "items": [
      {
        "id": "iiiiiiii-iiii-4iii-8iii-iiiiiiiiiiii",
        "tenant_id": "tttttttt-tttt-4ttt-8ttt-tttttttttttt",
        "tenant_name": "Toko Asep",
        "seller_name": "Asep Pemilik",
        "product_name": "Bayam",
        "quantity": 2,
        "unit_price": 10000,
        "unit_price_label": "Rp. 10.000",
        "line_total": 20000,
        "line_total_label": "Rp. 20.000"
      }
    ],
    "disbursements": [
      {
        "id": "ffffffff-ffff-4fff-8fff-ffffffffffff",
        "unique_code": "FIN-INV-20260712-0001-tttttttt",
        "status": "pending_buyer_payment",
        "status_label": "Pengajuan",
        "amount": 20000,
        "amount_label": "Rp. 20.000",
        "store": {
          "id": "tttttttt-tttt-4ttt-8ttt-tttttttttttt",
          "name": "Toko Asep"
        },
        "seller": {
          "id": "ssssssss-ssss-4sss-8sss-ssssssssssss",
          "name": "Asep Pemilik",
          "email": "asep@example.com",
          "phone": "081111111111"
        },
        "bank": {
          "name": "BCA",
          "account_holder": "Asep Pemilik",
          "account_number_masked": "1234567xxx"
        },
        "buyer_payment_confirmed_at": null,
        "disbursed_at": null
      }
    ],
    "transaction_at": "2026-07-12T10:30:00+07:00",
    "transaction_at_label": "12 Jul 2026, 10:30 WIB"
  }
}
```

Response final boleh mempertahankan struktur existing jika frontend sudah siap, tetapi harus memenuhi kebutuhan field UI, masking data sensitif, dan konsistensi dokumentasi.

## Task Frontend Web Finance

1. Tambahkan atau pastikan button ikon `eye` tersedia pada kolom action setiap row tabel transaksi finance.
2. Button `eye` harus memakai `aria-label` atau tooltip yang jelas, misalnya `Lihat detail transaksi`.
3. Saat button diklik, ambil `transaction.id` dari row.
4. Panggil `GET /api/finance/transactions/{id}` memakai token session finance.
5. Tampilkan loading state di modal/drawer detail.
6. Tampilkan error state jika request gagal.
7. Tampilkan not found state jika API mengembalikan `404`.
8. Tampilkan detail buyer, seller/toko, item, nominal, pembayaran, disbursement, dan tanggal transaksi.
9. Jangan tampilkan full nomor rekening jika API hanya mengirim masked account number.
10. Jangan hitung ulang total di client; gunakan nominal dari response.
11. Jika detail memuat tombol aksi, tombol harus mengikuti status dari response tetapi endpoint aksi tetap melakukan validasi state server-side.
12. Setelah aksi finance berhasil dari detail, refresh data detail dan row tabel yang terdampak.

## Acceptance Criteria

1. User finance dapat membuka detail transaksi dengan menekan button ikon `eye` pada row tabel finance.
2. Detail mengambil data dari endpoint finance authenticated.
3. User tanpa token mendapat `401`.
4. User dengan role selain finance mendapat `403`.
5. ID transaksi tidak ditemukan mendapat `404`.
6. Detail menampilkan informasi transaksi, buyer, seller/toko, item, nominal, status pembayaran, dan status disbursement.
7. Nominal uang tersedia dalam integer dan label.
8. Nomor rekening seller tampil dalam bentuk masked.
9. Response tidak memuat OTP, token, password, secret, atau dokumen sensitif.
10. Payload list transaksi tetap ringan dan tidak wajib memuat semua detail.
11. Endpoint detail tidak mengubah status transaksi atau disbursement.
12. Dokumentasi API diperbarui jika response shape berubah.

## Task Testing

1. Test finance berhasil mengambil detail transaksi.
2. Test detail melakukan sync disbursement jika record belum tersedia.
3. Test response memuat item transaksi.
4. Test response memuat disbursement terkait transaksi.
5. Test response memuat rekening seller dalam format masked.
6. Test response tidak memuat full nomor rekening seller.
7. Test response tidak memuat field sensitif user.
8. Test unauthenticated mendapat `401`.
9. Test role non-finance mendapat `403`.
10. Test transaksi tidak ditemukan mendapat `404`.
11. Test endpoint detail tidak mengubah status transaksi.
12. Test query detail tidak menghasilkan N+1 yang jelas pada transaksi multi-item/multi-tenant bila tersedia tooling test query.

## Verification Checklist

1. Jalankan Laravel Pint.
2. Jalankan test feature finance transaction detail.
3. Jalankan seluruh test finance bila kontrak controller finance berubah.
4. Jika frontend web diubah, jalankan build asset yang relevan.
5. Pastikan `API_DOCUMENTATION.md` sesuai dengan kontrak response final.
6. Pastikan tidak ada credential, token, OTP, full nomor rekening, atau data sensitif masuk response/log/dokumentasi contoh.
