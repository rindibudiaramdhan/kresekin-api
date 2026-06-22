# Agent and Seller Operations Kresekin API

Dokumen ini mendefinisikan requirement operasional agent dan seller: registrasi agent, UMKM binaan, seller tenant, produk, dan order.

## Agent Registration

Agent memiliki flow web khusus di `/agent/register`.

Requirement:

1. Registrasi agent harus mengumpulkan identitas, kontak, housing area, alamat, koordinat bila ada, data bank, dokumen identitas, dan consent.
2. Request harus divalidasi melalui FormRequest.
3. Upload dokumen identitas harus disimpan di disk private.
4. Agent baru mendapat `agent_code` unik dan status awal `pending_review`.
5. `terms_accepted_at`, `terms_version`, dan `privacy_accepted_at` harus tercatat.
6. OTP dikirim untuk verifikasi email/phone sesuai flow yang berjalan.
7. Secret dan data verifikasi berisiko tinggi seperti OTP, credential bank, dan path dokumen identitas tidak boleh muncul di log atau response. Data profil yang memang boleh dibaca Agent sendiri harus dipetakan eksplisit dan dibatasi oleh ownership.

## Agent Profile

Requirement:

1. Agent dapat membaca profil sendiri melalui `/api/agent/profile`.
2. Agent dapat memperbarui data payout/profile yang diizinkan.
3. Update data bank harus divalidasi dan diaudit.
4. Response profil tidak boleh mengekspos path dokumen identitas mentah bila tidak diperlukan.
5. Status review agent harus tersedia agar client bisa membatasi fitur.

## Managed UMKM

Requirement:

1. Agent dapat melihat daftar seller/tenant binaan melalui `/api/agent/sellers`.
2. Detail seller hanya boleh dibuka bila tenant memiliki `agent_user_id` sesuai agent.
3. Endpoint `/api/agent/managed-umkm` menyediakan performa UMKM binaan.
4. Revenue agent dihitung dari transaksi completed yang itemnya berasal dari tenant binaan.
5. List harus mendukung pagination/filter bila data bertambah.

## Agent Dashboard

Requirement:

1. Dashboard agent menampilkan ringkasan revenue UMKM binaan, jumlah order, UMKM aktif, tren transaksi, spotlight UMKM, transaksi terbaru, dan komisi.
2. Periode minimal mendukung `30_days` dan `90_days` bila mengikuti dashboard frontend.
3. Semua metrik harus dihitung server-side.
4. Tanggal kosong pada trend chart harus diisi nilai nol agar chart stabil.
5. Response harus stabil untuk web dashboard dan mobile bila digunakan ulang.

## Seller Tenant Management

Requirement:

1. Seller dapat melihat dan membuat tenant melalui prefix `/api/seller/tenants`.
2. Tenant baru wajib terkait ke seller aktif sebagai owner.
3. Seller tidak boleh mengubah tenant seller lain.
4. Data tenant yang berdampak ke katalog buyer harus divalidasi ketat.

## Seller Product Management

Requirement:

1. Seller dapat melihat, membuat, mengubah, mengaktif/nonaktifkan, menghapus, dan mengupload gambar produk.
2. Product image upload harus validasi tipe, ukuran, dan ownership.
3. Update produk via `PUT` dan alias `POST` multipart harus menghasilkan behavior yang sama.
4. Produk yang dihapus tidak boleh merusak histori transaction item.
5. Ringkasan produk seller harus menghitung status dan jumlah dari data milik seller saja.

## Seller Order Operations

Requirement:

1. Seller dashboard menyediakan revenue hari ini, perubahan revenue, transaksi hari ini, count order hari ini, preview order baru, dan top product.
2. Seller order list/detail harus scoped ke tenant seller.
3. Update status order harus mengikuti lifecycle transaksi.
4. Seller tidak boleh memproses transaksi yang tidak mengandung item dari tenant miliknya.

## Audit Requirements

Event minimum:

1. Agent registration submitted.
2. Agent profile/payout updated.
3. Tenant created/updated.
4. Product created/updated/status changed/deleted.
5. Product image uploaded/replaced.
6. Seller order status changed.

## Open Questions

1. Apakah agent boleh membuat tenant/seller baru atau hanya melihat yang sudah ditugaskan?
2. Apa batas fitur agent saat status `pending_review`?
3. Apakah seller web auth akan disatukan dengan session token API atau tetap memakai Laravel session?
