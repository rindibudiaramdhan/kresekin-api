# E20261003-K05 — Alat audit dan backfill kategori produk

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Backend / Database; nama personel ditetapkan pada K01
- Target baseline: M2
- Estimasi: 3–4 hari kerja effort, bukan janji durasi kalender
- Dependensi: K02
- Tautan dependensi: [K02](K02-Ekspansi-schema-dan-relasi-produk.md)

## Tujuan

Alat yang dapat direview, diulang dan dihentikan aman untuk memetakan data lama.

## Cakupan

1. Sediakan dry-run inventory teks/master termasuk soft-deleted, laporan cocok/tidak cocok/ambigu dan ID tujuan tanpa data personal.
2. Gunakan resolver konsisten dengan K03; dukung mapping eksplisit yang direview domain owner.
3. Batch, progress, resume dan rerun; ukuran batch ditetapkan dari pengukuran volume/lock pada PostgreSQL.
4. Update bersyarat: ID masih kosong dan teks belum berubah sejak dibaca; record konflik dilewati dan dilaporkan untuk rekonsiliasi.
5. ID valid tidak ditimpa; ID invalid dilaporkan untuk resolusi eksplisit, bukan diperbaiki diam-diam.
6. Laporan/checkpoint persisten di storage operasional durable Laravel Cloud; jangan bergantung pada disk aplikasi ephemeral.

## Acceptance criteria dan verifikasi

- [ ] Dry-run tidak mengubah data; laporan mempunyai jumlah inventory, hasil mapping, konflik dan kandidat ID tujuan.
- [ ] Test mencakup exact/normalized unik, ambigu, tidak cocok, soft-deleted, batch/resume dan rerun.
- [ ] Test concurrent edit membuktikan ID/teks yang berubah tidak ditimpa hasil mapping stale.
- [ ] Rekonsiliasi menyediakan hitungan total/null/orphan dan runbook eksekusi, penghentian serta pemulihan.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
