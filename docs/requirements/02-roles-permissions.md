# Roles and Permissions Kresekin API

Dokumen ini mendefinisikan model role, batas akses, ownership resource, dan lifecycle user untuk Kresekin API.

## Core Model

Kresekin API memakai role-based access control sederhana dengan ownership check di level query/use case. Role disimpan di `users.role` dan nilai valid berasal dari constant model `User`.

Role aktif:

1. `buyer`
2. `seller`
3. `agent`
4. `finance`

Authorization tidak cukup hanya memastikan token valid. Endpoint harus memastikan role sesuai dan data yang diakses berada dalam scope user tersebut.

## Authentication Boundary

1. Endpoint authenticated wajib memakai middleware `session.token`.
2. Bearer token dicocokkan ke `user_session_tokens` dalam bentuk hash.
3. Logout dan refresh session harus dilakukan lewat endpoint resmi.
4. OTP dan token plain tidak boleh masuk response selain momen verifikasi OTP yang memang mengembalikan token baru.
5. User yang berubah role/status sensitif harus dipertimbangkan untuk invalidasi session.

## Role Catalog

| Role | Kebutuhan utama | Scope data |
| --- | --- | --- |
| Buyer | Melihat katalog, mengelola cart, checkout, melihat transaksi sendiri | Cart dan transaksi milik user |
| Seller | Mengelola tenant, produk, order, dashboard toko | Tenant/product/order milik seller |
| Agent | Melihat UMKM binaan, dashboard performa, profil payout, withdrawal komisi | Tenant dengan `agent_user_id = current user` |
| Finance | Memantau transaksi, disbursement, withdrawal komisi, master alasan pembatalan | Data finance global sesuai endpoint |

## Permission Matrix

| Area | Buyer | Seller | Agent | Finance |
| --- | --- | --- | --- | --- |
| Auth/session sendiri | Yes | Yes | Yes | Yes |
| Master umum authenticated | Read | Read | Read | Read |
| Product catalog buyer | Read | No | No | No |
| Cart dan checkout | Own | No | No | No |
| Transaction history buyer | Own | No | No | Finance read |
| Tenant seller | No | Own CRUD terbatas | Managed read | Finance read sesuai kebutuhan |
| Product seller | No | Own CRUD | Managed read bila diperlukan | Finance read sesuai kebutuhan |
| Order seller | No | Own manage status | Read agregat managed UMKM | Finance read/status finance |
| Agent dashboard/profile | No | No | Own | No |
| Agent withdrawal | No | No | Own create/read | Review/manage |
| Finance dashboard | No | No | No | Yes |
| Cancellation reason finance CRUD | No | No | No | Yes |

## Data Scoping

1. Buyer hanya boleh membaca/mengubah cart dan transaksi dengan `user_id` miliknya.
2. Seller hanya boleh mengelola tenant dengan `owner_user_id` miliknya dan produk/order yang berasal dari tenant tersebut.
3. Agent hanya boleh membaca UMKM/tenant dengan `agent_user_id` miliknya.
4. Finance boleh membaca workflow finance lintas tenant, tetapi tetap tidak boleh mendapat secret seperti OTP, token, atau path dokumen identitas mentah.
5. Endpoint detail wajib mengembalikan `404` untuk resource di luar scope agar tidak membocorkan keberadaan data.
6. Query list wajib menerapkan filter scope sebelum pagination.

## Web Access

Web route yang saat ini ada:

1. Agent registration: `/agent/register` dan `/agent/verify-otp`.
2. Dashboard static/server-rendered untuk agent dan finance.
3. Seller web area di prefix `/seller` dengan middleware `auth` dan `role:seller`.

Web route yang mengubah data tetap harus memakai validasi, CSRF protection, dan authorization yang setara dengan API.

## User Lifecycle

1. User dibuat melalui register role-specific atau web agent registration.
2. OTP dikirim untuk verifikasi login/register.
3. Setelah OTP valid, session token dibuat.
4. Agent web registration mengisi data tambahan: nama, email/phone, area, alamat, data bank, dokumen identitas, consent, dan status `pending_review`.
5. Agent dapat memiliki status review: `pending_review`, `approved`, atau `rejected`.
6. Perubahan role atau status agent harus diaudit dan dapat mempengaruhi akses fitur sensitif.

## Service Accounts

Belum ada service account formal di codebase. Jika integrasi backend-to-backend ditambahkan, token service account harus:

1. Terpisah dari token user.
2. Memiliki scope eksplisit.
3. Bisa dicabut.
4. Diaudit saat digunakan.

## Audit Requirements

Event minimum yang perlu diaudit:

1. Register, login OTP verified, resend OTP, logout, refresh session.
2. Perubahan role/status user.
3. Agent registration dan perubahan status review.
4. Update profil payout agent.
5. Akses ditolak untuk aksi sensitif bila perlu investigasi.

## Open Questions

1. Apakah agent `pending_review` boleh membuat withdrawal atau hanya melihat dashboard?
2. Siapa role final untuk review agent: finance, operations, admin, atau role baru?
3. Apakah perlu permission granular di luar empat role saat jumlah fitur internal bertambah?
