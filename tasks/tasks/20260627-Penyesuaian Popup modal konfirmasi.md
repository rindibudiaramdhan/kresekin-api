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
4. Menjaga isi modal tetap sama secara fungsional.
5. Tidak mengubah endpoint API, payload, atau logic transaksi.

## Rekomendasi Ukuran Target

Ukuran desktop yang disarankan:

| Elemen | Target |
| --- | ---: |
| Lebar panel | `520px-600px` |
| Padding panel | `24px-32px` |
| Border radius | `12px-16px` |
| Judul | `24px-30px` |
| Description | `15px-18px` |
| Detail transaksi | `15px-18px` |
| Note | `14px-16px` |
| Tombol | tinggi `44px-52px`, font `15px-17px` |

Catatan:

1. Jika ID transaksi panjang masih sering muncul, lebar `600px` dapat dipilih sebagai kompromi.
2. Jika ingin mengikuti confirmation modal umum secara lebih ketat, gunakan kisaran `520px`.
3. Hindari kembali memakai font body di atas `20px` untuk dashboard desktop.

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
2. Jika perubahan global pada komponen terlalu memengaruhi modal approval lain, pertimbangkan variant/prop ukuran, misalnya:
   - default compact untuk confirmation,
   - variant larger untuk modal yang benar-benar membutuhkan ruang lebih.
3. Pastikan style responsive tetap ada untuk `max-width: 680px`.
4. Tambahkan handling text wrapping untuk ID transaksi panjang bila diperlukan:
   - `overflow-wrap: anywhere;`
   - atau pendekatan lain yang menjaga layout tidak pecah.
5. Jangan mengubah selector JavaScript yang sudah dipakai:
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

## Out of Scope

1. Redesign total halaman Finance.
2. Perubahan API finance.
3. Perubahan copywriting modal.
4. Penambahan animasi modal.
5. Perubahan flow approval, rejection, atau confirmation.

