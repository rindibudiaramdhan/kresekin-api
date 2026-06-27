# Penyesuaian Ukuran Popup Modal Konfirmasi

Tanggal: 2026-06-27

## Latar Belakang

Terdapat feedback bahwa popup modal konfirmasi pada halaman Finance terlalu besar, terutama modal `Konfirmasi Pembayaran`. Modal saat ini memakai komponen `approval-confirmation-modal` yang juga digunakan untuk flow approval pencairan dana.

Kondisi saat ini di `resources/views/components/dashboard/approval-confirmation-modal.blade.php`:

| Elemen | Ukuran Saat Ini |
| --- | ---: |
| Lebar panel | `min(760px, 100%)` |
| Padding panel | `38px 40px 46px` |
| Border radius | `22px` |
| Judul | `clamp(32px, 4vw, 46px)` |
| Description | `28px` |
| Detail transaksi | `28px`, `font-weight: 900` |
| Note | `27px` |
| Tombol | `width: min(290px, 100%)`, `min-height: 88px`, `font-size: 28px` |

Ukuran ini lebih dekat ke modal besar atau layar aksi utama. Untuk confirmation modal dashboard, ukuran umum lebih ringkas:

| Referensi | Ukuran Umum |
| --- | ---: |
| Ant Design `Modal.confirm` | `416px` |
| Ant Design Modal default | `520px` |
| Bootstrap modal default | `500px` |
| Bootstrap large modal | `800px` |

## Tujuan

Menyesuaikan ukuran popup modal konfirmasi agar lebih proporsional untuk dashboard operasional, tetap terbaca, dan tidak mendominasi layar.

## Scope

1. Menyesuaikan ukuran modal konfirmasi yang menggunakan komponen `approval-confirmation-modal`.
2. Prioritas utama: modal `Konfirmasi Pembayaran` pada halaman Finance.
3. Evaluasi dampak perubahan terhadap modal lain yang memakai komponen yang sama:
   - `Approve Pencairan Dana`
   - `Selesaikan Pencairan Dana`
   - `Konfirmasi Pembayaran`
4. Menyamakan skala visual modal rejection agar konsisten dengan modal Finance lainnya:
   - `Tolak Pencairan Dana` di `rejection-confirmation-modal`
   - `Detail Penolakan` di `rejection-detail-modal`
5. Menjaga isi modal tetap sama secara fungsional.
6. Tidak mengubah endpoint API, payload, atau logic transaksi.

## Keputusan Implementasi

1. Jadikan `approval-confirmation-modal` sebagai compact confirmation modal secara default.
2. Tidak perlu menambah variant ukuran besar untuk tahap ini karena semua pemakai `approval-confirmation-modal` di halaman Finance adalah dialog konfirmasi operasional.
3. Terapkan skala compact yang konsisten pada `rejection-confirmation-modal` dan `rejection-detail-modal`, dengan tombol destructive tetap merah dan berbeda jelas dari tombol primer.
4. Perubahan harus sebatas struktur visual/CSS Blade modal. JavaScript selector, event handler, endpoint, payload, dan copywriting tidak berubah.
5. Jika di masa depan ada modal yang benar-benar membutuhkan ruang lebih besar, baru tambahkan prop/variant ukuran eksplisit, misalnya `size="large"`.

## Rekomendasi Ukuran Target

Ukuran desktop yang disarankan:

| Elemen | Target |
| --- | ---: |
| Lebar panel | `min(580px, 100%)` |
| Padding panel | `28px 30px 32px` |
| Border radius | `14px` |
| Judul | `clamp(24px, 2.4vw, 30px)` |
| Description | `16px` |
| Detail transaksi | `16px` |
| Note | `15px` |
| Tombol | tinggi `48px`, font `16px` |

Catatan:

1. Lebar `580px` dipilih sebagai kompromi antara confirmation modal umum dan kebutuhan menampilkan ID transaksi yang panjang.
2. Jika hasil visual masih terlalu lebar setelah implementasi, turunkan ke `520px-560px`.
3. Hindari kembali memakai font body di atas `20px` untuk dashboard desktop.
4. Untuk viewport di bawah `680px`, gunakan panel yang tetap memenuhi lebar aman viewport, tombol full width, font sekitar `15px-16px`, dan `max-height` dengan scroll internal bila konten melebihi tinggi layar.

## UX Requirement

1. Modal harus terasa sebagai dialog konfirmasi, bukan layar utama.
2. Informasi penting tetap jelas:
   - ID Transaksi
   - Nama Buyer atau Agent
   - Nominal
   - Bank Tujuan
   - Konsekuensi aksi
3. Tombol primer dan batal tetap mudah diklik.
4. Tombol destructive pada modal rejection tetap berbeda secara visual dari tombol primer.
5. ID transaksi panjang tidak boleh keluar dari panel modal.
6. Modal harus tetap usable pada viewport desktop kecil dan mobile.

## Design Constraint

1. Mengikuti prinsip di `docs/requirements/14-design-language.md`:
   - Dashboard harus operasional dan cepat dipindai.
   - Ukuran besar dipakai untuk metrik utama dan heading page.
   - Teks di komponen dashboard tidak boleh terlalu besar.
2. Tidak membuat modal terlihat seperti landing page atau hero section.
3. Tidak mengubah brand color kecuali diperlukan untuk state tombol.
4. Tidak menambahkan ilustrasi, card tambahan, atau dekorasi visual baru.

## Technical Requirement

1. Perubahan utama kemungkinan berada di:
   - `resources/views/components/dashboard/approval-confirmation-modal.blade.php`
2. Perubahan konsistensi visual modal rejection berada di:
   - `resources/views/components/dashboard/rejection-confirmation-modal.blade.php`
   - `resources/views/components/dashboard/rejection-detail-modal.blade.php`
3. Jangan menambah variant/prop ukuran pada tahap ini kecuali implementasi compact default terbukti merusak kebutuhan modal yang sudah ada.
4. Pastikan style responsive tetap ada untuk `max-width: 680px`.
5. Tambahkan handling text wrapping untuk ID transaksi panjang bila diperlukan:
   - `overflow-wrap: anywhere;`
   - atau pendekatan lain yang menjaga layout tidak pecah.
6. Detail/value modal perlu punya `min-width: 0` agar wrapping bekerja di grid/flex layout.
7. Pertimbangkan `max-height: calc(100vh - 40px); overflow-y: auto;` pada panel modal agar usable di layar pendek.
8. Jangan mengubah selector JavaScript yang sudah dipakai:
   - `data-finance-modal`
   - `data-finance-modal-close`
   - `data-finance-modal-confirm`
   - `data-finance-modal-field`

## Acceptance Criteria

1. Modal `Konfirmasi Pembayaran` tidak lagi menggunakan skala visual besar seperti saat ini.
2. Lebar modal desktop berada di kisaran `520px-600px`.
3. Judul modal maksimal sekitar `30px` pada desktop normal.
4. Body/detail/note berada di kisaran `14px-18px`.
5. Tombol tidak lagi setinggi `88px`; target tinggi `44px-52px`.
6. ID transaksi panjang tetap terbaca dan tidak overflow keluar panel.
7. Modal tetap centered dan overlay tetap bekerja.
8. Tombol `Batal`, `Ya`, dan close icon tetap bisa digunakan.
9. Tidak ada perubahan behavior submit/confirm/reject.
10. Tampilan mobile tetap rapi tanpa horizontal scroll.
11. Modal `Approve Pencairan Dana` dan `Selesaikan Pencairan Dana` tetap terbaca setelah skala compact diterapkan.
12. Modal `Tolak Pencairan Dana` dan `Detail Penolakan` menggunakan skala visual yang konsisten dengan modal konfirmasi lain.
13. Tombol destructive pada modal rejection tetap merah dan tidak tertukar dengan tombol primer.

## Verification Checklist

1. Buka halaman Finance.
2. Klik `Pembayaran Masuk`.
3. Pastikan modal `Konfirmasi Pembayaran` terlihat compact dan proporsional.
4. Cek row dengan ID transaksi panjang.
5. Cek tombol `Batal`.
6. Cek tombol `Ya`.
7. Cek close icon.
8. Cek viewport desktop sekitar `1280px`.
9. Cek viewport tablet/mobile atau responsive mode di bawah `680px`.
10. Pastikan modal approval lain yang memakai komponen sama tidak rusak.
11. Cek modal `Approve Pencairan Dana`.
12. Cek modal `Selesaikan Pencairan Dana`.
13. Cek modal `Tolak Pencairan Dana`, termasuk pilihan alasan dan error validasi alasan.
14. Cek modal `Detail Penolakan`.
15. Pastikan tidak ada horizontal scroll pada modal dengan ID transaksi panjang.

## Out of Scope

1. Redesign total halaman Finance.
2. Perubahan API finance.
3. Perubahan copywriting modal.
4. Penambahan animasi modal.
5. Perubahan flow approval, rejection, atau confirmation.
