# Catalog and Master Data Kresekin API

Dokumen ini mendefinisikan requirement katalog produk, tenant/UMKM, dan master data yang dipakai buyer, seller, agent, dan finance.

## Overview

Katalog Kresekin terdiri dari dua lapisan:

1. **Tenant/UMKM** sebagai toko atau usaha yang menjual produk.
2. **Produk** sebagai item yang dapat dilihat buyer dan dikelola seller.

Master data pendukung meliputi kategori produk, satuan produk, area perumahan, metode pengiriman, opsi waktu pesanan, metode pembayaran, promo code, dan alasan pembatalan.

## Tenant / UMKM

Tenant menyimpan identitas usaha, owner seller, agent pembina, kategori, lokasi, rating, jam operasional, dan relasi area perumahan.

Requirement:

1. Tenant harus memiliki owner seller melalui `owner_user_id`.
2. Tenant dapat dikaitkan ke agent melalui `agent_user_id`.
3. Tenant dapat dikaitkan ke banyak housing area.
4. Kategori tenant harus berasal dari daftar yang stabil atau product category yang valid.
5. Jam operasional harus dapat ditampilkan sebagai label dan dipakai untuk status buka/tutup.
6. Endpoint seller hanya boleh membuat/mengubah tenant milik seller aktif.
7. Endpoint buyer hanya menampilkan tenant yang relevan untuk katalog buyer.

## Product

Produk adalah item jual yang terkait ke tenant, kategori produk, satuan, harga, stok/status, dan gambar.

Requirement:

1. Produk wajib terkait ke tenant milik seller.
2. Harga dan stok/jumlah harus berupa integer.
3. Produk buyer-facing hanya boleh menampilkan produk aktif dan tersedia sesuai aturan domain.
4. Seller dapat create, update, update status, upload gambar, dan soft delete produk miliknya.
5. Product image harus disimpan dengan validasi file dan path yang tidak mudah ditebak.
6. Response produk harus memetakan field secara eksplisit, bukan mengembalikan model mentah.
7. List produk harus mendukung filter dan pagination sesuai kebutuhan client.

## Product Category and Unit

Kategori dan satuan produk adalah reference data.

Requirement:

1. Endpoint `/api/product-categories` dan `/api/product-units` tersedia untuk user authenticated.
2. Seeder harus idempotent.
3. Kode/nama kategori dan satuan tidak boleh berubah sembarangan karena dipakai client untuk display dan filter.
4. Perubahan ikon atau warna kategori harus dijaga kompatibel dengan asset di `public/images`.

## Housing Area

Housing area membatasi atau mengelompokkan coverage layanan.

Requirement:

1. Housing area tersedia untuk user authenticated.
2. Tenant dapat berada di banyak housing area melalui pivot.
3. Query buyer dan seller harus mempertimbangkan area bila requirement coverage sudah final.
4. Data area harus konsisten dengan pilihan saat registrasi agent.

## Delivery, Order Time, and Payment Method

Master ini dipakai saat checkout.

Requirement:

1. Buyer dapat membaca metode pengiriman, opsi waktu pesanan, dan metode pembayaran aktif.
2. Payment method dapat memiliki option/detail tambahan.
3. Saat checkout, nama dan kode metode harus disnapshot ke transaksi agar histori tidak berubah saat master data berubah.
4. Master data yang nonaktif tidak boleh dipilih untuk transaksi baru.

## Promo Code

Promo code dipakai oleh buyer sebelum checkout.

Requirement:

1. Validasi promo dilakukan server-side melalui endpoint `/api/promo-codes/validate`.
2. Server menghitung diskon, bukan client.
3. Promo harus memiliki aturan aktif, periode, tipe diskon, nilai diskon, dan constraint penggunaan sesuai model yang tersedia.
4. Checkout harus menyimpan snapshot kode, nama, tipe, nilai, dan nominal diskon.
5. Promo yang sudah dipakai dalam transaksi harus tetap bisa ditelusuri walaupun master promo berubah.

## Cancellation Reason Category

Kategori alasan pembatalan dipakai buyer/seller dan dikelola finance.

Requirement:

1. Buyer dan seller dapat membaca kategori alasan aktif.
2. Finance dapat create, update, dan delete/nonaktifkan kategori alasan.
3. Transaksi yang dibatalkan harus menyimpan reference kategori dan teks alasan bila ada.
4. Penghapusan kategori tidak boleh merusak histori transaksi.

## Lifecycle and Governance

1. Master data production harus diubah melalui endpoint internal yang tepat, seeder terkendali, atau migration/data patch yang direview.
2. Field yang sudah dikonsumsi client tidak boleh diganti tanpa migrasi kontrak.
3. Master data yang mempengaruhi transaksi harus disnapshot ke transaksi.
4. Audit diperlukan untuk perubahan master data finance dan perubahan produk/tenant penting.

## Open Questions

1. Apakah tenant bisa dimiliki lebih dari satu seller?
2. Apakah buyer catalog harus difilter berdasarkan housing area user?
3. Apakah stok produk wajib dikurangi saat checkout atau status order tertentu?
4. Apakah promo akan mendukung campaign agent, tenant tertentu, atau kategori tertentu?
