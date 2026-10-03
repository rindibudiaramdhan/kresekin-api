# E20261003-K07 — Reader dan response produk berbasis master

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Backend API; nama personel ditetapkan pada K01
- Target baseline: M3–M4
- Estimasi: 3–4 hari kerja effort, bukan janji durasi kalender
- Dependensi: K03, K06; pengembangan dapat dimulai setelah K03
- Tautan dependensi: [K03](K03-Standardisasi-writer-produk-API.md), [K06](K06-Eksekusi-dan-rekonsiliasi-backfill.md)

## Tujuan

Semua reader produk membaca ID/master dengan bentuk response konsisten.

## Cakupan

1. Audit list/detail dan nested product responses; sediakan product_category_id, product_category id/name/slug dan category turunan.
2. Pindahkan metadata ikon/warna ke pengelola kategori produk berbasis slug master dengan fallback netral.
3. Eager load master; jangan membaca nama tersimpan sebagai sumber utama; fallback record lama hanya selama backfill belum tuntas.

## Acceptance criteria dan verifikasi

- [ ] Create/edit/list/detail konsisten; rename nama master langsung tampil, slug berasal dari master.
- [ ] Kategori baru memiliki metadata netral dan tidak membutuhkan Tenant::CATEGORIES.
- [ ] Setelah rekonsiliasi seluruh reader produk memakai ID; tidak ada dependency aktif ke teks produk untuk pembacaan kategori.
- [ ] Test rename, master baru, soft-delete/visibility dan nested response lulus; pemeriksaan query membuktikan tidak ada N+1 master.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
