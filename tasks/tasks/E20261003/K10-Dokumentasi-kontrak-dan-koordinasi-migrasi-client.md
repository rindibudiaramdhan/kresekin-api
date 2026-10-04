# E20261003-K10 — Dokumentasi kontrak dan koordinasi migrasi client

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Engineering Lead + Client PIC + Product; nama personel ditetapkan pada K01
- Target baseline: M1–M7
- Estimasi: 3–5 internal; effort client dihitung oleh PIC hari kerja effort, bukan janji durasi kalender
- Dependensi: K01; finalisasi mengikuti K03–K09
- Tautan dependensi: [K01](../../DONE/E20261003/K01-Audit-kontrak-dan-keputusan-transisi.md), [K03](K03-Standardisasi-writer-produk-API.md), [K09](K09-Tenant-tanpa-kategori-dan-dashboard-terdampak.md)

## Tujuan

Dokumentasi dan bukti kesiapan semua client tersedia sebelum kontrak lama dihentikan.

## Cakupan

1. Dokumentasikan payload/response, fallback edit, deprecation, error dan endpoint master pengganti; selaraskan ADR-012 untuk master database kategori produk.
2. Buat/link pekerjaan tiap client eksternal dengan PIC, versi, jadwal dan acceptance: tenant tanpa kategori, produk ID, subtitle tanpa kategori tunggal, endpoint master.
3. Mulai jendela kompatibilitas dua siklus rilis client; panjang siklus aktual belum terverifikasi saat penutupan audit API K01, sehingga dicatat pada K10 sebelum menghitung jendela rilis; revisi timeline bila berbeda.
4. Pertahankan slug selama rollout; ID diperoleh dari master environment terkait; alias slug di luar epic.
5. Sediakan bukti observasi fallback, endpoint lama dan konsumsi field response lama; field read perlu evidence client karena log server tidak membuktikan apakah field dibaca.

## Acceptance criteria dan verifikasi

- [ ] Dokumentasi transisi serta target tersedia dan contoh kontrak konsisten dengan implementasi.
- [ ] Semua client aktif memiliki PIC, tiket/PR/release evidence, versi target dan kebijakan upgrade untuk versi lama.
- [ ] Uji client membuktikan tidak membaca field tunggal tenant, tidak memakai fallback teks dan tidak memakai GET tenants/categories.
- [ ] Dua siklus rilis terpenuhi; semua versi aktif siap dan observasi kontrak lama nol selama 14 hari berturut-turut sebelum removal.
- [ ] Status kesiapan dan tanggal cutover dicatat; kegagalan readiness menggeser cleanup, bukan memaksa removal.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
