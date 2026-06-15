# Endpoint Finance Dashboard

Dokumen ini merangkum endpoint yang dibutuhkan untuk halaman Finance Management di Management Portal, khususnya menu Finance dengan tab `Transaksi Agent` dan alur pencairan komisi agent.

## Komponen Halaman

1. Summary cards:
   - Total Dana Tersalurkan
   - Total Dana Tertunda
   - Jumlah Pencairan Komisi

2. Filter bar:
   - Search nama atau ID agent
   - Status
   - Date range

3. Tabs:
   - Transaksi Seller
   - Transaksi Agent

4. Table Transaksi Agent:
   - ID Transaksi
   - Nama Agent
   - Bank Tujuan
   - Nominal
   - Tanggal Pengajuan
   - Status
   - Actions

5. Action flows:
   - `Pengajuan` -> Approve / Reject
   - Approve -> status `Diproses`
   - `Diproses` -> Selesai
   - Selesai -> status `Berhasil`
   - Reject -> wajib pilih alasan -> status `Ditolak`
   - `Ditolak` -> lihat detail penolakan

## Endpoint Yang Dibutuhkan

### 1. Summary Pencairan Komisi Agent

`GET /api/finance/commission-withdrawals/summary`

Dipakai untuk summary cards.

Contoh response:

```json
{
  "data": {
    "total_disbursed": 45000000,
    "total_disbursed_label": "Rp 45.000.000",
    "total_pending": 45000000,
    "total_pending_label": "Rp 45.000.000",
    "total_withdrawals": 249
  }
}
```

### 2. List Pencairan Komisi Agent

`GET /api/finance/commission-withdrawals`

Dipakai untuk table `Transaksi Agent`, filter, search, dan pagination.

Query yang direkomendasikan:

```text
?page=1
&per_page=10
&search=Santi
&status=requested|approved|paid|rejected
&date_from=2026-10-01
&date_to=2026-10-31
```

Contoh response:

```json
{
  "data": [
    {
      "id": "WD-20230914-0042",
      "agent": {
        "id": "uuid",
        "name": "Santi"
      },
      "bank": {
        "name": "Mandiri",
        "account_number_masked": "1240098xxx",
        "account_holder": "Santi"
      },
      "amount": 2450999,
      "amount_label": "Rp 2.450.999",
      "requested_at": "2026-02-15T10:00:00+07:00",
      "requested_at_label": "15 Feb 2026",
      "status": "requested",
      "status_label": "Pengajuan",
      "rejection": null,
      "processed_by": null,
      "processed_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "last_page": 5,
    "total": 49,
    "from": 1,
    "to": 10
  }
}
```

Status mapping untuk frontend:

| Backend | Frontend |
| --- | --- |
| `requested` | `Pengajuan` |
| `approved` | `Diproses` |
| `paid` | `Berhasil` |
| `rejected` | `Ditolak` |

### 3. Approve Pengajuan

`PATCH /api/finance/commission-withdrawals/{id}/approve`

Dipakai saat user klik `Ya, Approve`.

Behavior:

- Valid hanya dari status `requested`.
- Ubah status menjadi `approved`.
- Simpan `processed_by_user_id`.
- Simpan `processed_at`, atau lebih baik `approved_at` jika field audit dipisah.

Contoh response:

```json
{
  "message": "Pengajuan pencairan dana berhasil disetujui.",
  "data": {
    "id": "WD-20230914-0042",
    "status": "approved",
    "status_label": "Diproses"
  }
}
```

### 4. Reject Pengajuan

`PATCH /api/finance/commission-withdrawals/{id}/reject`

Dipakai saat user submit modal `Tolak Pengajuan`.

Contoh request:

```json
{
  "reason": "invalid_account"
}
```

Validasi:

- `reason` required.
- `reason` harus salah satu dari enum alasan.
- Valid hanya dari status `requested`.

Contoh response:

```json
{
  "message": "Pengajuan pencairan dana berhasil ditolak.",
  "data": {
    "id": "WD-20230914-0042",
    "status": "rejected",
    "status_label": "Ditolak",
    "rejection": {
      "reason": "invalid_account",
      "reason_label": "Data rekening tidak valid",
      "rejected_at": "2026-06-15T19:30:00+07:00",
      "rejected_at_label": "15 Jun 2026, 19:30",
      "rejected_by": {
        "id": "uuid",
        "name": "Finance Administrator"
      }
    }
  }
}
```

### 5. Mark As Paid / Selesaikan Pencairan

`PATCH /api/finance/commission-withdrawals/{id}/mark-as-paid`

Dipakai saat user klik `Selesai` lalu confirm.

Behavior:

- Valid hanya dari status `approved`.
- Ubah status menjadi `paid`.
- Simpan `paid_at`.
- Simpan `paid_by_user_id`.

Contoh response:

```json
{
  "message": "Pencairan dana berhasil diselesaikan.",
  "data": {
    "id": "WD-20230914-0042",
    "status": "paid",
    "status_label": "Berhasil",
    "paid_at": "2026-06-15T19:35:00+07:00"
  }
}
```

### 6. Detail Pencairan Komisi Agent

`GET /api/finance/commission-withdrawals/{id}`

Dipakai untuk detail row, termasuk detail penolakan. Untuk modal `Detail Penolakan`, data dapat diambil dari response list jika payload list sudah memuat detail yang cukup. Endpoint detail tetap disarankan untuk menjaga payload list tetap ringan dan sebagai sumber data final.

Contoh response:

```json
{
  "data": {
    "id": "WD-20230914-0042",
    "agent": {
      "id": "uuid",
      "name": "Denny"
    },
    "bank": {
      "name": "BSI",
      "account_number_masked": "012322xxx"
    },
    "amount_label": "Rp 1.025.873",
    "status": "rejected",
    "status_label": "Ditolak",
    "rejection": {
      "reason": "incomplete_account",
      "reason_label": "Data akun belum lengkap",
      "rejected_at_label": "6 Mar 2026, 14:20",
      "rejected_by": {
        "name": "Finance Administrator"
      }
    }
  }
}
```

## Catatan Existing API

Di repo saat ini sudah ada endpoint finance berikut:

- `GET /api/finance/dashboard`
- `GET /api/finance/transactions`
- `GET /api/finance/transactions/{id}`
- `PATCH /api/finance/transactions/{id}/confirm-buyer-payment`
- `PATCH /api/finance/disbursements/{id}/disburse-to-seller`

Endpoint tersebut lebih cocok untuk tab `Transaksi Seller`, karena model domainnya adalah `FinanceTransactionDisbursement`.

Untuk tab `Transaksi Agent`, model domain yang paling relevan adalah `AgentCommissionWithdrawal`. Saat ini endpoint yang tersedia baru agent-side:

- `GET /api/agent/commission-withdrawals`
- `POST /api/agent/commission-withdrawals`

Finance-side endpoint untuk mengelola withdrawal agent belum tersedia dan perlu dibuat.

## Rekomendasi Tahapan Pengerjaan

### Tahap 1 - Rapikan Domain Contract

- Tetapkan bahwa tab `Transaksi Agent` memakai model `AgentCommissionWithdrawal`.
- Review struktur tabel `agent_commission_withdrawals`.
- Tambahkan field audit yang belum ada jika diperlukan:
  - `rejection_reason`
  - `rejected_by_user_id`
  - `approved_by_user_id`
  - `paid_by_user_id`
  - `approved_at`
  - `paid_at`
- Jika tetap memakai field existing `processed_at`, pastikan definisinya jelas untuk status `approved`, `paid`, dan `rejected`.

### Tahap 2 - Bangun Read API

- Implement `GET /api/finance/commission-withdrawals/summary`.
- Implement `GET /api/finance/commission-withdrawals`.
- Pastikan endpoint list mendukung:
  - pagination
  - filter status
  - search nama agent atau ID withdrawal
  - date range berdasarkan `requested_at`
- Tambahkan transformer/resource agar response stabil dan tidak bergantung pada struktur model mentah.

### Tahap 3 - Integrasi Frontend List dan Summary

- Ganti fetch halaman Finance dari endpoint sementara ke endpoint final:
  - `/api/finance/commission-withdrawals/summary`
  - `/api/finance/commission-withdrawals`
- Normalisasi status mapping frontend:
  - `requested` -> `pending`
  - `approved` -> `processing`
  - `paid` -> `success`
  - `rejected` -> `rejected`
- Pastikan loading skeleton hilang hanya setelah response sukses atau error tertangani.

### Tahap 4 - Bangun Mutation API

- Implement approve endpoint:
  - `PATCH /api/finance/commission-withdrawals/{id}/approve`
  - transition: `requested -> approved`
- Implement reject endpoint:
  - `PATCH /api/finance/commission-withdrawals/{id}/reject`
  - transition: `requested -> rejected`
  - validasi `reason` wajib
- Implement mark-as-paid endpoint:
  - `PATCH /api/finance/commission-withdrawals/{id}/mark-as-paid`
  - transition: `approved -> paid`
- Semua mutation harus:
  - memakai database transaction
  - validasi current status
  - menyimpan audit user finance
  - mengembalikan object withdrawal terbaru

### Tahap 5 - Integrasi Action Modal

- Modal approve memanggil endpoint approve.
- Modal reject memanggil endpoint reject dengan `reason`.
- Modal selesai memanggil endpoint mark-as-paid.
- Setelah mutation sukses:
  - update row lokal dari response, atau
  - refetch list current page.
- Jika mutation gagal:
  - tampilkan error state yang eksplisit.
  - jangan ubah status row secara optimistis kecuali rollback sudah disiapkan.

### Tahap 6 - Detail Penolakan

- Minimal: include rejection detail di response list untuk row berstatus `rejected`.
- Lebih aman: panggil `GET /api/finance/commission-withdrawals/{id}` saat icon eye diklik.
- Modal detail penolakan harus menampilkan:
  - alasan penolakan
  - waktu ditolak
  - user finance yang menolak

### Tahap 7 - Testing

- Feature test list:
  - pagination
  - filter status
  - search
  - date range
- Feature test summary:
  - total disbursed
  - total pending
  - total withdrawals
- Feature test mutation:
  - approve valid
  - approve invalid status
  - reject valid
  - reject tanpa reason
  - reject invalid reason
  - mark-as-paid valid
  - mark-as-paid invalid status
- Authorization test:
  - hanya role `finance` yang bisa read dan mutate finance-side withdrawal.
  - role `agent` tidak bisa approve/reject/paid.

