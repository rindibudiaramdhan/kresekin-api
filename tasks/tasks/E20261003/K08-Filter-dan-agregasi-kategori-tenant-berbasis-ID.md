# E20261003-K08 — Filter dan agregasi kategori tenant berbasis ID

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Backend API / Query; nama personel ditetapkan pada K01
- Target baseline: M4
- Estimasi: 3–4 hari kerja effort, bukan janji durasi kalender
- Dependensi: K06, K07
- Tautan dependensi: [K06](K06-Eksekusi-dan-rekonsiliasi-backfill.md), [K07](K07-Reader-dan-response-produk-berbasis-master.md)

## Tujuan

Filter dan kategori tenant berasal dari produk tanpa mengubah visibility atau bentuk agregat.

## Cakupan

1. Resolusi parameter category pada produk dan product_category pada tenant buyer melalui slug master kemudian ID.
2. Ubah GET tenants/{tenantId}/product-categories ke master unik melalui relasi produk.
3. Pertahankan product_categories dan product_category_slugs dalam bentuk existing; deduplikasi identitas master.
4. Pertahankan agregasi kategori/count dari produk yang sudah difilter dan scope visibility/soft-delete existing.

## Acceptance criteria dan verifikasi

- [ ] Tenant dengan produk A/B muncul pada filter A dan B terlepas dari kategori tenant lama.
- [ ] Tenant tanpa produk muncul pada daftar tanpa filter, agregat kosong, dan tidak muncul pada filter kategori tertentu.
- [ ] Endpoint per-tenant unik dan mengikuti visibility existing; rename nama tidak memutus filter ID.
- [ ] Test membandingkan count, scope dan bentuk response sebelum/sesudah; slug invalid mengikuti validasi existing.
- [ ] Eager loading/index dan query list yang terdampak diverifikasi tanpa N+1.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
