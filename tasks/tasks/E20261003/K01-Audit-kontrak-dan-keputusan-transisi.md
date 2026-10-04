# E20261003-K01 — Audit kontrak dan keputusan transisi

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Engineering Lead; nama personel ditetapkan pada K01
- Target baseline: M1
- Estimasi: 2–3 hari kerja effort, bukan janji durasi kalender
- Dependensi: —
- Tautan dependensi: —

## Tujuan

Inventaris final endpoint, writer, reader, client, dan keputusan rollout sebelum implementasi.

## Cakupan

1. Audit seluruh referensi kategori tenant, Tenant::CATEGORIES, metadata, pencocokan nama, route aktif/tidak aktif, dokumentasi, seeder dan fixture.
2. Buat matriks endpoint × payload/response × client × PIC; pisahkan client aktif, versi lama, dan kebijakan upgrade.
3. Konfirmasi engine/version PostgreSQL operasional, volume produk termasuk soft-deleted, ritme rilis client, serta kemampuan observasi kontrak lama.
4. Catat keputusan kompatibilitas tenant, ambang observasi, PIC mapping, dan release owner.

## Acceptance criteria dan verifikasi

- [x] Matriks audit memiliki lokasi kode, jalur kontrak, pemilik, serta status tindakan; tidak hanya mengulang peta awal epic.
- [ ] Setiap client aktif mempunyai PIC; identitas PIC dan versi produksi terverifikasi, bukan asumsi.
- [ ] Bentuk response tenant sementara serta penanganan nilai kosong disepakati dan dibuktikan dapat diterima client sebelum aktivasi tenant tanpa kategori.
- [x] Lokasi dokumentasi API dan rencana observasi fallback/endpoint/field lama ditetapkan; perubahan observabilitas yang diperlukan dialokasikan pada K03/K10.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: audit statis backend tersedia pada [matriks audit](K01-Matriks-audit-kontrak.md); `git diff --check` lolos pada 2026-10-04. Tidak ada perubahan behavior/PHP, test aplikasi tidak dijalankan.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.

## Catatan grooming — 2026-10-04

- Konfirmasi pengguna: satu-satunya client API aktif adalah mobile seller Android/iOS. Route buyer/agent/finance yang tersedia di backend tetap masuk audit kode; keberadaan route tidak membuktikan adanya client produksi aktif.
- PIC pembaruan dan pengujian mobile Android/iOS: Rindi (pengguna). PIC mapping data dan release owner belum dikonfirmasi.
- Aplikasi sudah digunakan pengguna produksi. Kebijakan upgrade wajib; menurut pengguna sudah didiskusikan dengan tim mobile engineer dan perubahan sudah dikembangkan. Nomor versi/build, bukti pengujian, rilis, dan penerapan upgrade wajib belum tersedia.
- Keputusan bisnis dikonfirmasi: tenant tidak lagi membutuhkan kategori. Penerimaan client terhadap penghapusan field response atau bentuk kosong sementara masih perlu bukti pengujian; keputusan bisnis saja belum membuktikan kompatibilitas client.
- Klarifikasi pengguna: kode mobile berada di repository terpisah dan tidak tersedia. Pengerjaan dilanjutkan berdasarkan API contract; audit kode/pengujian mobile tidak diklaim telah dilakukan.
- Pengguna sepakat menghilangkan pengisian kategori tenant/merchant saat buat/ubah. Create mengabaikan input kategori lama; endpoint edit tenant belum tersedia dan tidak ditambahkan pada epic ini.
- Lokasi dokumentasi API: `API_DOCUMENTATION.md`. [Matriks audit dan keputusan transisi](K01-Matriks-audit-kontrak.md) mencatat kontrak, caller, lokasi kode, pemilik peran, alokasi K02–K13, dan rencana observasi K03/K10.
- Versi PostgreSQL operasional dan jumlah produk termasuk soft-deleted belum tersedia; perlu hasil query read-only dari database produksi.

Catatan ini merupakan hasil grooming sementara, bukan bukti seluruh acceptance criteria telah terpenuhi.

## Evaluasi PM — 2026-10-04

Hasil audit backend memadai untuk ditinjau dan menjadi acuan pengerjaan kontrak. Tiket belum dipindahkan ke DONE karena dua acceptance criteria awal serta informasi operasional dalam cakupan belum terpenuhi. Label Planned di header adalah status awal perencanaan; audit backend sudah dilakukan, tetapi penutupan tiket belum disetujui.

| Kriteria | Hasil evaluasi | Kekurangan yang tersisa |
| --- | --- | --- |
| Matriks lokasi kode, kontrak, pemilik dan tindakan | Terpenuhi untuk audit repository | Pemilik teknis berbentuk peran, bukan penetapan personel; PIC mobile dikonfirmasi pengguna |
| PIC dan versi produksi tiap client aktif | Sebagian | PIC Rindi dikonfirmasi; versi/build belum tersedia |
| Response tenant sementara/penanganan kosong diterima client | Belum terbukti | Target tanpa kategori tunggal jelas dari epic; tidak ada bukti uji client atau keputusan lapisan sementara |
| Lokasi dokumentasi dan alokasi observasi | Terpenuhi sebagai rencana | Implementasi dan verifikasi collector merupakan pekerjaan lanjutan K03/K10 |

Arahan pengguna untuk bekerja berdasarkan API contract diterima: repository mobile tidak diperlukan untuk audit backend. Arahan ini belum secara eksplisit memindahkan kewajiban bukti versi/uji client dari AC K01 ke K10 atau menghapus gate produksi. Opsi yang direkomendasikan: tutup K01 sebagai audit backend setelah perubahan kriteria disetujui, dengan kewajiban client dialihkan ke K10/K11 dan tetap menjadi syarat G4/G5. Data PostgreSQL/volume dapat dikonfirmasi pada K02/K05 sebelum rehearsal/penetapan batch; perpindahan kewajiban ini juga belum disetujui.

Konfirmasi yang diperlukan untuk penutupan:

1. Apakah K01 diterima sebagai audit backend/API contract, dengan bukti versi dan kompatibilitas mobile diselesaikan oleh Rindi di K10 sebelum G4/G5, tanpa menyatakan bukti tersebut sudah tersedia?
2. Siapa PIC mapping kategori produk lama, operations/database, dan release owner? Rindi hanya sudah dikonfirmasi sebagai PIC mobile, tidak otomatis memegang ketiga peran tersebut.
3. Apakah versi PostgreSQL/volume produk dikumpulkan sekarang, atau dialihkan ke K02/K05 sebelum verifikasi migration dan perencanaan backfill? Ritme rilis aktual tetap perlu dicatat di K10 sebelum menghitung dua siklus rilis.

Asumsi material evaluasi:

- Repository saat audit adalah baseline backend untuk kontrak; kesesuaian dengan deployment produksi belum dibuktikan.
- Pernyataan pengguna tentang client aktif, PIC mobile, pengembangan mobile dan upgrade wajib diterima sebagai konfirmasi stakeholder; bukan hasil pemeriksaan release/build/enforcement.
- Web/admin/dashboard yang ditemukan tetap masuk audit perubahan kode, meskipun penggunaan produksinya belum dikonfirmasi.
- Response target tanpa field kategori tunggal berasal dari epic; kesepakatan menghapus pengisian kategori tidak dengan sendirinya membuktikan penerimaan response oleh aplikasi lama.
- Tidak menambah endpoint edit tenant; kategori produk tetap mengikuti master.
- Dua siklus rilis dan observasi nol 14 hari mengikuti keputusan epic; tidak diasumsikan sudah berjalan atau terpenuhi.

Verifikasi evaluasi: pembacaan tiket, matriks, dan keputusan grooming; tidak menguji aplikasi atau mengakses produksi. Tidak ada komitmen jadwal/personel baru, pemindahan tiket, atau pelonggaran gate yang dilakukan pada evaluasi ini.
