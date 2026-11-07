# Design Language Kresekin API

Dokumen ini mendefinisikan prinsip desain untuk halaman web dan dashboard yang ada di repository: registration agent, seller web area, agent dashboard, dan finance dashboard.

## Acuan dan sumber token

Acuan visual adalah tiga screenshot dari UI Designer yang diberikan pengguna: Admin Dashboard, Finance Management (termasuk modal approve/reject), dan Web Admin Login (normal/error). Acuan ini menggantikan deskripsi warna awal yang bertentangan di dokumen ini.

Token runtime terpusat di `public/css/design-system.css`, dimuat langsung oleh halaman login, registrasi/OTP, dan dashboard tanpa ketergantungan build Vite. Komponen dashboard menggunakan token yang sama. Nilai warna dan ukuran merupakan pendekatan implementasi dari screenshot, bukan hasil ekstraksi properti Figma. Font Inter adalah fallback implementasi yang sudah ada; font final belum dikonfirmasi designer. Seller Bootstrap belum dicakup oleh referensi ini.

| Token | Nilai | Penggunaan |
| --- | --- | --- |
| `--ds-brand` | `#00bdcc` | Tombol utama, link, navigasi/tab aktif, garis chart |
| `--ds-highlight` | `#009fa8` | Kartu spotlight dan ringkasan komisi |
| `--ds-canvas` | `#f7f8fa` | Background halaman |
| `--ds-surface` | `#ffffff` | Kartu dan modal |
| `--ds-text` | `#1c2021` | Teks utama |
| `--ds-text-muted` | `#52617f` | Label dan teks sekunder |
| `--ds-radius-control` | `8px` | Kontrol dan tombol |
| `--ds-radius-card` | `12px` | Kartu |
| `--ds-radius-pill` | `999px` | Badge transaksi dan identitas pengguna |

Spacing dasar: 4, 8, 12, 16, 24, 28, dan 32px, tersedia sebagai `--ds-space-*`. Gunakan shadow kartu dan modal yang terpisah. Ukuran layar pada screenshot bukan ukuran CSS wajib; pertahankan layout responsive yang sudah ada.

## Principles

1. UI harus operasional, cepat dipindai, dan tidak terasa seperti landing page marketing.
2. Dashboard harus menonjolkan metrik, status, filter, dan tindakan berikutnya.
3. Form harus jelas, ringkas, dan aman untuk data sensitif.
4. Empty state harus membantu user memahami data kosong tanpa menjelaskan fitur secara berlebihan.
5. Visual brand Kresek.in harus konsisten dengan logo dan asset di `public/images`.

## Brand and Identity

Asset brand aktif:

1. `public/images/kresek-full-logo.svg`
2. `public/images/kresek-wordmark.svg`
3. `public/images/kresek-bag-icon.svg`
4. `public/images/kresekin-bag-mark.svg`

Requirement:

1. Logo digunakan sebagai identitas utama di auth/register dan dashboard shell.
2. Jangan mengubah SVG brand tanpa keputusan desain.
3. Icon kategori produk memakai asset `ic_*_category.svg` atau metadata kategori dari model tenant.

## Color System

Palet harus mendukung domain grocery/UMKM yang segar dan ramah, tetapi dashboard operasional tetap harus netral dan mudah dibaca.

Token awal:

| Token | Fungsi |
| --- | --- |
| Brand cyan/toska | Primary action, link, navigasi/tab aktif, icon brand |
| Neutral slate/gray | Text, border, dashboard surface |
| Soft green | Success/completed state |
| Amber | Warning/processing |
| Red | Error/rejected/canceled |
| Blue | Pengajuan/pending dan informasi |

Warna status harus konsisten di badge, chart legend, dan summary card.

## Status Colors

| Status | Warna intent |
| --- | --- |
| Pending payment/requested/pending review | Blue |
| Processing/in progress | Amber |
| Completed/paid/disbursed/approved final | Green |
| Rejected/canceled/failed | Red |
| Inactive/empty/disabled | Gray |

## Typography

1. Gunakan ukuran besar hanya untuk angka metrik utama dan heading page.
2. Label tabel, filter, dan badge harus ringkas.
3. Jangan gunakan teks terlalu besar di card dashboard yang padat.
4. Hindari letter spacing negatif.
5. Pastikan angka uang panjang tetap muat di mobile.

## Layout and Components

Komponen dashboard yang sudah ada:

1. Sidebar.
2. Header.
3. Filter bar.
4. Metric card.
5. Summary highlight card.
6. Trend chart card.
7. Spotlight card.
8. Data table.
9. Status badge.
10. Pagination.
11. Approval/rejection modal.

Requirement:

1. Sidebar dan header harus konsisten antara agent dan finance.
2. Filter periode harus mudah ditemukan dan tidak menggeser layout.
3. Table harus tetap terbaca di layar kecil, dengan prioritas kolom jelas.
4. Modal approval/rejection harus menyebut konsekuensi aksi dan validasi alasan bila perlu.
5. Card tidak boleh bersarang dalam card lain.

## Variasi komponen dan autentikasi

1. Dashboard memakai wordmark; halaman autentikasi memakai logo dengan ikon tas, sesuai screenshot.
2. Metric card memiliki varian vertikal (overview) dan horizontal dengan ikon di kanan (finance).
3. Badge transaksi berbentuk pill; badge ringkas pada tabel komisi boleh memakai kotak membulat. Warna tetap mengikuti arti status, bukan bentuk badge.
4. Tab aktif memakai teks cyan dan garis bawah. Pagination aktif memakai background cyan.
5. Tombol utama cyan, aksi penolakan merah, tombol batal netral. Tombol batal tetap interaktif, bukan disabled.
6. Modal memuat judul, detail transaksi, konsekuensi aksi, dan konfirmasi. Modal penolakan memuat pilihan alasan.
7. Login desktop memakai form di kiri dan panel promosi di kanan. Pertahankan pilihan role/metode autentikasi yang sudah berfungsi.
8. Input memakai label di atas, ikon opsional, background netral, serta kontrol tampilkan password. Error harus tampil dekat field terkait, tidak di atas panel promosi.
9. Focus harus terlihat; state loading, disabled, dan mobile adalah perilaku implementasi yang belum ditentukan lengkap oleh screenshot.

## Forms

Agent registration form harus:

1. Memakai grouping field yang logis: identitas, kontak, area/alamat, payout, dokumen, consent.
2. Menampilkan error validasi dekat field.
3. Tidak menampilkan OTP/token/dokumen sensitif setelah submit.
4. Menjaga consent terms/privacy terlihat jelas.
5. Menjelaskan status pending review setelah pendaftaran tanpa membuka data sensitif.

## Accessibility

1. Semua input harus punya label.
2. Contrast text dan badge harus cukup.
3. Button destructive harus jelas berbeda dari primary action.
4. Focus state harus terlihat.
5. Icon-only button harus punya accessible label/title.

## Empty States

Empty state diperlukan untuk:

1. Agent belum punya UMKM binaan.
2. Tidak ada transaksi pada periode dipilih.
3. Belum ada withdrawal.
4. Finance belum punya item pending.
5. Seller belum punya produk.

Empty state harus memberi aksi berikutnya bila ada, misalnya buat produk atau ubah filter.

## Open Questions

1. Apakah design system akan dipisah menjadi package/component library?
2. Apakah dashboard mobile wajib full responsive atau cukup tablet/desktop untuk internal?
3. Apakah nilai HEX, font, ukuran, dan varian badge dari screenshot sudah dikonfirmasi melalui spesifikasi Figma?
