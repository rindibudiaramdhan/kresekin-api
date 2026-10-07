# E20261003-K05 — Alat audit dan backfill kategori produk

- Tanggal: 2026-10-03
- Status: Tool lokal teruji — rehearsal PostgreSQL/storage operasional belum terverifikasi
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](timeline.md)
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

- [x] Dry-run tidak mengubah data; laporan mempunyai jumlah inventory, hasil mapping, konflik dan kandidat ID tujuan.
- [x] Test mencakup exact/normalized unik, ambigu, tidak cocok, soft-deleted, batch/resume dan rerun.
- [x] Test concurrent edit membuktikan ID/teks yang berubah tidak ditimpa hasil mapping stale.
- [x] Rekonsiliasi menyediakan hitungan total/null/orphan dan runbook eksekusi, penghentian serta pemulihan.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.

## Implementasi dan bukti lokal — 2026-10-04

`products:backfill-categories` menyediakan dry-run default dan apply eksplisit. Resolver sama dengan K03, termasuk prioritas exact unik dan penolakan ambigu. Inventory dan scan mencakup soft-deleted; ID valid tidak disentuh dan orphan dilaporkan. Mapping eksplisit harus objek JSON nama kategori lama persis ke UUID master environment terkait; seluruh ID mapping divalidasi sebelum write. Persetujuan mapping tetap gate domain owner K06.

Scan memakai cursor UUID dan batas ID awal. Checkpoint menyimpan mode, fingerprint database/master/mapping, master, mapping, jumlah hasil dan rekonsiliasi total/null/orphan/soft-deleted bertimestamp. Resume dengan mode/master/mapping/database berubah ditolak; gunakan run baru setelah review. Plan batch disimpan sebelum write; hasil attempt dan checkpoint disimpan sesudahnya. Laporan berisi product ID/kategori/ID tujuan/status, tanpa nama produk/tenant/owner, harga, token atau payload personal.

Update bersyarat memeriksa ID masih null serta teks sama dengan snapshot; concurrent text/ID edit menghasilkan conflict, bukan overwrite. ID dan teks target master diperbarui bersama. Kegagalan storage menghentikan proses; checkpoint terakhir dapat dipakai resume. Produksi diwajibkan disk S3 dan `--isolated=1`; cache lock harus shared antarrunner/instance.

`ProductCategoryBackfillTest`: 9 test meliputi dry-run, exact/normalized/ambigu/unmatched, soft-deleted, mapping, batch/resume/rerun, orphan, concurrent edit dan laporan conflict, perubahan kontrak resume, storage failure, mapping invalid serta isolation lock. Verifikasi gabungan K02–K05 + SellerApiTest: 58 test lolos, 484 assertion, SQLite in-memory dan fake S3. Kriteria tool dicentang untuk bukti lokal; PostgreSQL, storage S3 aktual, tuning lock/batch dan operasi produksi belum terverifikasi.

### Runbook K05/K06 — belum dieksekusi pada data operasional

1. Rehearse K02 serta tool pada database **test khusus PostgreSQL 18.6** dengan snapshot representatif termasuk soft-deleted/concurrent edit. Catat volume, query time dan lock; batch 100 hanya default awal, bukan hasil pengukuran. Jangan memakai database bersama sebagai database test.
2. Sebelum apply produksi, penuhi G1/G2: migration ekspansi terpasang, semua writer dual-write dirilis/aktif, tidak ada writer/import/worker lama, backup dan pemulihan diverifikasi, Rindi sebagai operations/domain owner mereview dry-run dan mapping. Verifikasi cache lock shared, akses read/write/list bucket S3 privat, retensi durable, serta satu operator aktif.
3. Jalankan dry-run lewat console/job Laravel Cloud memakai UUID run baru (contoh di bawah adalah placeholder, bukan ID master/data nyata). Pilih disk S3 operasional privat yang telah dikonfigurasi. Jangan mencatat kredensial ke command atau tiket.

```bash
php artisan products:backfill-categories --run=<uuid-dry-run> --disk=s3 --batch=100 --max-batches=1 --isolated=1
# Resume run yang sama sampai completed=true:
php artisan products:backfill-categories --run=<uuid-dry-run> --disk=s3 --batch=100 --isolated=1
```

4. Review artifact `product-category-backfill/<run>/checkpoint.json`, `plans/` dan `batches/`. Domain owner menyetujui kategori unmatched/ambiguous secara eksplisit. Upload mapping objek JSON ke disk yang sama dan simpan bukti persetujuan durable terpisah. UUID tujuan berasal dari master environment ini, bukan contoh dokumentasi.
5. Jalankan dry-run **baru** dengan `--mapping=<path-mapping-approved.json>` untuk memverifikasi mapping. Apply memakai run UUID berbeda; jangan mengubah dry-run menjadi apply pada checkpoint yang sama.

```bash
php artisan products:backfill-categories --run=<uuid-apply> --disk=s3 --mapping=<path-mapping-approved.json> --apply --batch=<batch-terukur> --max-batches=1 --isolated=1
# Resume dengan run/mode/mapping/database/master yang sama:
php artisan products:backfill-categories --run=<uuid-apply> --disk=s3 --mapping=<path-mapping-approved.json> --apply --batch=<batch-terukur> --max-batches=1 --isolated=1
```

6. Hentikan aman pada batas batch memakai `--max-batches`; pilih durasi command di bawah TTL isolation lock Laravel satu jam. Jika process dihentikan/error di tengah batch, data yang sudah ditulis tetap tersimpan. Periksa plans/results dan checkpoint durable terakhir, lalu resume. Penulisan DB dan storage bukan satu transaksi: attempt dapat muncul lebih dari sekali setelah crash; ID valid tetap dilewati, jangan menjumlahkan semua attempt sebagai jumlah produk unik. Checkpoint counts adalah hasil scan yang sudah checkpoint, bukan audit akuntansi write tepat satu kali.
7. Concurrent insert dengan UUID di luar batas awal/di belakang cursor tidak dijamin masuk scan. Rekonsiliasi menghitung **seluruh tabel saat pemeriksaan**; gunakan run baru setelah konflik/mapping berubah atau untuk sweep final, bukan menganggap completed berarti null/orphan nol. Master/mapping berubah membutuhkan run baru. Jika null/orphan/conflict masih ada, tahan G3 dan minta resolusi eksplisit; tool tidak memperbaiki orphan otomatis.
8. Pemulihan aplikasi lama dapat mempertahankan schema ekspansi dan teks dual-write. Jangan menghapus ID valid untuk mengulang backfill. Jika diperlukan undo data, review plans/results bersama operations/domain owner, hentikan writer, dan gunakan restore backup atau perubahan bersyarat yang direview; jangan menimpa concurrent edit. Simpan laporan rekonsiliasi baru setelah pemulihan.

Belum ada backfill produksi, persetujuan mapping operasional, uji S3 aktual atau gate G2/G3. K06 tetap belum selesai.
