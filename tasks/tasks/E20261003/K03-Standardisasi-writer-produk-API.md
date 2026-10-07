# E20261003-K03 — Standardisasi writer produk API

- Tanggal: 2026-10-03
- Status: Implementasi lokal teruji — menunggu gate K02/deployment
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](timeline.md)
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

- [x] ID valid menang atas teks; ID invalid/null/kosong ditolak 422 meskipun teks valid.
- [x] Edit tanpa ID menerima nama unik; nama tidak cocok/ambigu ditolak 422; create teks-only ditolak.
- [x] PUT dan POST multipart konsisten; validasi field lain, ownership dan auth tetap sesuai kontrak existing.
- [x] Writer menyimpan ID serta teks master secara konsisten; response menggunakan allowlist.
- [x] Regression test mencakup konflik ID/teks, normalisasi ambigu, ownership, dan response; fallback dapat diobservasi.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.

## Implementasi dan bukti lokal — 2026-10-04

- Create API mempertahankan ID master; create, PUT dan POST alias menyimpan ID + nama master, lalu merespons `product_category_id`, objek `product_category` allowlist id/name/slug, dan `category` turunan. Reader GET mengikuti implementasi lokal K07; aktivasi tetap menunggu K06/G3.
- Edit memeriksa keberadaan key ID sebelum fallback: null/kosong/UUID invalid/orphan ditolak meskipun teks benar. Teks diabaikan jika ID dikirim. Resolver bersama `ProductCategoryResolver` menerima exact unik lebih dahulu, lalu normalisasi trim/spasi/case unik; duplikasi exact atau normalisasi ditolak.
- Event `product_category_legacy_fallback` dicatat dari validasi setiap edit tanpa key ID, accepted/rejected berdasarkan hasil validasi; accepted bukan bukti write berhasil (controller dapat menolak ownership dengan 404). Context hanya outcome, route template, method, environment dan request ID. Tidak mencatat nilai kategori, token atau payload. Collector/retensi produksi tetap tugas K10/K11.
- `ProductCategoryWriterApiTest`: 7 test meliputi create teks-only, konflik ID/teks, null/kosong/invalid, PUT/multipart dengan gambar, fallback unik/ambigu, allowlist, ownership/auth dan validasi harga.
- Verifikasi gabungan K02–K05 + SellerApiTest: **58 test lolos, 484 assertion**, SQLite in-memory. Pint dijalankan pada seluruh PHP perubahan; pemeriksaan format akhir dicatat di timeline.
- API_DOCUMENTATION.md diperbarui pada payload PUT/POST alias dan ketiga contoh response writer. Belum ada commit, deployment atau bukti gate G1; checklist di atas menyatakan hasil lokal, bukan readiness produksi. Tiket tidak dipindahkan ke DONE.
