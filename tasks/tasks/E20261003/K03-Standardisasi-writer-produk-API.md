# E20261003-K03 — Standardisasi writer produk API

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Backend API; nama personel ditetapkan pada K01
- Target baseline: M2
- Estimasi: 3–4 hari kerja effort, bukan janji durasi kalender
- Dependensi: K02
- Tautan dependensi: [K02](K02-Ekspansi-schema-dan-relasi-produk.md)

## Tujuan

Create/edit menggunakan ID master dengan kompatibilitas edit lama yang terukur.

## Cakupan

1. Ubah request/controller create, PUT update dan POST alias multipart ke product_category_id.
2. Create tetap wajib ID; fallback teks hanya edit ketika key ID tidak dikirim.
3. Resolver: exact name, lalu trim/normalisasi spasi/case jika unik; tanpa fuzzy matching, master otomatis atau default.
4. Dual-write ID dan nama master ke kolom teks selama reader lama masih aktif; response ID, objek id/name/slug, serta category turunan.
5. Catat pemakaian fallback tanpa payload personal; gunakan mekanisme observasi yang disepakati K01.

## Acceptance criteria dan verifikasi

- [ ] ID valid menang atas teks; ID invalid/null/kosong ditolak 422 meskipun teks valid.
- [ ] Edit tanpa ID menerima nama unik; nama tidak cocok/ambigu ditolak 422; create teks-only ditolak.
- [ ] PUT dan POST multipart konsisten; validasi field lain, ownership dan auth tetap sesuai kontrak existing.
- [ ] Writer menyimpan ID serta teks master secara konsisten; response menggunakan allowlist.
- [ ] Regression test mencakup konflik ID/teks, normalisasi ambigu, ownership, dan response; fallback dapat diobservasi.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
