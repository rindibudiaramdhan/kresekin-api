# E20261003-K02 — Ekspansi schema dan relasi produk

- Tanggal: 2026-10-03
- Status: Planned — belum diimplementasikan
- Epic: [E20261003](../../epic/E20261003-Pemisahan%20Kategori%20Tenant%20dan%20Standardisasi%20Kategori%20Produk.md)
- Timeline: [timeline](../../timeline.md)
- PIC peran: Backend / Database; nama personel ditetapkan pada K01
- Target baseline: M1–M2
- Estimasi: 2–3 hari kerja effort, bukan janji durasi kalender
- Dependensi: K01
- Tautan dependensi: [K01](K01-Audit-kontrak-dan-keputusan-transisi.md)

## Tujuan

Menyediakan schema kompatibel untuk writer baru tanpa menghapus kolom lama.

## Cakupan

1. Migration baru: tenants.category nullable; products.product_category_id UUID nullable, indeks dan FK pembatasan penghapusan.
2. Tambahkan relasi Product → ProductCategory; audit relasi/delete path master, termasuk soft-deleted products.
3. Audit seeder kategori agar idempotent dan tidak mengganti UUID yang sudah direferensikan; client selalu memperoleh ID dari master environment terkait.

## Acceptance criteria dan verifikasi

- [ ] Upgrade database berisi produk/tenant lama berhasil tanpa kehilangan nilai kategori.
- [ ] FK menolak orphan dan penghapusan master yang dipakai produk aktif maupun soft-deleted; tidak ada cascade/pengosongan otomatis.
- [ ] Seeder dapat diulang tanpa merusak relasi master; migration historis tidak diubah.
- [ ] Uji migration pada PostgreSQL sesuai produksi tersedia; ekspansi dideploy sebelum writer ID atau tenant tanpa kategori.

## Batasan dan Definition of Done

Keputusan domain dan kontrak epic berlaku. Tidak mengubah aturan harga, stok, transaksi, ownership, autentikasi atau visibility; tidak menambah pengelolaan master atau endpoint edit tenant.

Untuk perubahan PHP, jalankan Pint dan test relevan; perubahan behavior mempunyai regression test pada tiket ini. Untuk migration gunakan juga PostgreSQL sesuai produksi; SQLite test saja tidak cukup membuktikan upgrade. Perubahan asset memerlukan build. Catat hasil verifikasi dan update dokumentasi yang terdampak.

## Bukti penyelesaian

- PR/commit: belum tersedia.
- Hasil test/verifikasi: belum tersedia.
- Bukti deployment/operasi/client jika relevan: belum tersedia.
- PIC personel dan keputusan gate: belum ditetapkan.
