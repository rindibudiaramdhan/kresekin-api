# Vision and Scope Kresekin API

Dokumen ini mendefinisikan batas produk, tujuan bisnis, aktor utama, risiko, dan pertanyaan terbuka untuk pengembangan Kresekin API. Dokumen ini menjadi acuan awal sebelum requirement detail per fitur, desain API, dan implementasi teknis.

## Vision

Kresekin API menjadi fondasi backend untuk platform Kresek.in yang membantu UMKM mengelola transaksi, pertumbuhan usaha, dan hubungan operasional dengan agent serta finance secara aman, terukur, dan mudah dikembangkan.

Platform ini harus mendukung:

1. Registrasi dan autentikasi user berbasis role.
2. Pengelolaan UMKM oleh agent.
3. Monitoring performa transaksi dan komisi.
4. Alur finance untuk pemantauan dan pencairan dana.
5. Kontrak API yang stabil untuk web, mobile, dan integrasi internal.

## Problem Statements

1. UMKM membutuhkan sistem yang dapat mencatat dan memantau transaksi secara konsisten agar performa bisnis bisa diukur.
2. Agent membutuhkan portal untuk melihat UMKM binaan, performa transaksi, dan estimasi komisi tanpa proses manual.
3. Finance membutuhkan akses ke data yang cukup akurat untuk memantau transaksi, komisi, dan pencairan dana.
4. Sistem membutuhkan kontrak API yang stabil agar pengembangan web, mobile, dan dashboard tidak saling mematahkan integrasi.
5. Registrasi agent membutuhkan proses yang lebih lengkap daripada registrasi user umum karena melibatkan identitas, kontak, consent, dan review manual.
6. Data sensitif seperti OTP, token, dokumen identitas, dan data keuangan harus diproses dengan kontrol keamanan yang jelas.

## Goals

1. Menyediakan API Laravel yang stabil untuk domain utama Kresek.in: user, auth, agent, seller/UMKM, transaksi, dashboard, komisi, dan finance.
2. Memastikan setiap endpoint role-specific dilindungi middleware autentikasi dan role yang sesuai.
3. Menyediakan flow agent yang end-to-end untuk registrasi, verifikasi OTP, login, dashboard, daftar UMKM binaan, detail UMKM, profil, dan pencairan komisi.
4. Menyediakan struktur response API yang konsisten sehingga mudah digunakan oleh client.
5. Menjaga backward compatibility endpoint yang sudah dipakai client.
6. Menyediakan dokumentasi requirement dan engineering standard agar pengembangan berikutnya bisa dilakukan secara konsisten.
7. Mengutamakan data integrity untuk transaksi, komisi, withdrawal, status pembayaran, dan perubahan data penting.
8. Menyediakan test coverage untuk perubahan behavior yang berdampak pada kontrak API atau alur bisnis.

## Non-Goals (Out of Scope)

1. Membangun semua fitur frontend production-ready di dalam scope API awal.
2. Mengganti kontrak endpoint auth/register existing tanpa strategi migrasi.
3. Menambahkan login password untuk agent pada MVP registrasi agent.
4. Mengotomasi approval dokumen identitas agent tanpa proses review yang disepakati.
5. Membangun sistem accounting penuh, general ledger, atau rekonsiliasi bank lengkap.
6. Menangani seluruh variasi payout provider eksternal sebelum kebutuhan integrasi final.
7. Membuat multi-tenant enterprise configuration yang kompleks di luar kebutuhan UMKM dan agent saat ini.
8. Menyimpan credential, token, OTP, atau dokumen sensitif di repository.

## Personas & Process Owners

| Persona / Owner | Peran | Kebutuhan Utama | Area Kepemilikan |
| --- | --- | --- | --- |
| Agent | Pengelola UMKM binaan | Registrasi, login OTP, melihat performa UMKM, memantau komisi, mengajukan pencairan | Agent portal, agent dashboard, agent profile, commission withdrawal |
| Seller / UMKM | Pelaku usaha yang menggunakan platform | Mengelola transaksi, produk, order, dan aktivitas usaha | Seller API, tenant, transaksi, produk |
| Finance | Tim internal pengelola keuangan | Memantau transaksi, komisi, pencairan, dan status pembayaran | Finance dashboard, payout/withdrawal review, financial reporting |
| Admin / Operations | Tim operasional internal | Review agent, menjaga kualitas data, membantu support | Agent review, user operations, support workflow |
| Buyer / Customer | Pengguna yang melakukan transaksi | Transaksi berjalan benar dan data pesanan tercatat | Buyer flow, checkout/order flow |
| Engineering | Tim pengembang | Kontrak API jelas, testable, aman, dan mudah dirawat | API design, database, security, deployment, observability |
| Product Owner | Pemilik prioritas produk | Scope jelas, keputusan terdokumentasi, risiko terlihat | Roadmap, acceptance criteria, release decision |

## Scope Summary

### In Scope

1. Backend REST API menggunakan Laravel 13++ dan PostgreSQL++.
2. Role utama: buyer, seller, agent, finance.
3. OTP-based authentication dan session token.
4. Agent registration web flow dengan upload dokumen identitas, consent, dan status awal review.
5. Agent dashboard API dan web dashboard yang menampilkan performa komisi serta UMKM binaan.
6. Endpoint agent untuk daftar UMKM binaan, detail UMKM, profil, dan commission withdrawals.
7. Finance-facing API/dashboard sesuai requirement yang sudah atau akan didefinisikan.
8. Validasi request melalui FormRequest untuk input kompleks.
9. Dokumentasi requirement, API, dan standar engineering.
10. Test untuk endpoint, role guard, validasi, dan business logic penting.

### Out of Scope for Initial Scope

1. Native mobile implementation.
2. Fully automated KYC/identity verification.
3. Real-time analytics dengan streaming pipeline.
4. Advanced BI/report builder.
5. Payment provider integration yang belum dipilih.
6. Multi-currency accounting.
7. Password-based login untuk agent.
8. Public access ke dokumen identitas.

## Constraints & Assumptions

### Constraints

1. Stack backend menggunakan Laravel 13, PHP 8.3, PostgreSQL, dan Laravel Cloud sebagai platform production wajib.
2. Endpoint authenticated harus menggunakan `session.token`.
3. Endpoint role-specific harus menggunakan middleware role yang sesuai.
4. Response API harus menjaga struktur yang sudah ada dan tidak mematahkan client.
5. File sensitif seperti dokumen identitas harus disimpan di storage private, bukan public URL terbuka.
6. OTP, token, password, credential, dan data sensitif tidak boleh muncul di log, response, atau dokumentasi contoh.
7. Perubahan schema harus dibuat melalui migration baru dan menjaga kompatibilitas data production.
8. Test suite menggunakan SQLite in-memory sesuai konfigurasi project.
9. Production runtime harus kompatibel dengan Laravel Cloud, termasuk environment variable, deployment command, queue, scheduler, log, dan storage.
10. File runtime yang perlu persisten tidak boleh bergantung pada local application disk karena production storage harus memakai durable object storage.

### Assumptions

1. Agent dapat login setelah OTP verified meskipun status review dokumen masih `pending_review`.
2. Pembatasan fitur untuk agent `pending_review` akan ditentukan pada requirement lanjutan.
3. Email menjadi channel OTP utama untuk registrasi agent MVP, sedangkan nomor WhatsApp tetap disimpan sebagai kontak.
4. Finance dan operations memiliki proses manual untuk review data yang belum diotomasi.
5. Komisi agent dihitung dari transaksi completed sesuai helper domain yang tersedia.
6. Data dashboard dapat menggunakan agregasi server-side dari tabel transaksi dan relasi tenant.
7. Laravel Cloud akan menyediakan environment variable production untuk koneksi database dan resource platform lain yang terhubung.
8. Client web menyimpan bearer token di `localStorage` sesuai flow yang sudah berjalan.

## Risks

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Perubahan kontrak API register mematahkan client existing | High | Buat flow web agent khusus dan jaga backward compatibility endpoint API existing |
| Dokumen identitas tersimpan atau terekspos secara tidak aman | High | Gunakan private disk, validasi file, dan jangan expose path sensitif di response publik |
| Data komisi tidak konsisten karena race condition atau query agregasi keliru | High | Gunakan transaksi, query teruji, test nilai database, dan hindari kalkulasi client-side |
| Role guard tidak lengkap pada endpoint baru | High | Wajib gunakan `session.token` dan middleware role; tambahkan feature test forbidden/unauthorized |
| OTP/token bocor lewat log atau response | High | Masking data sensitif dan audit response endpoint auth |
| Scope dashboard melebar menjadi BI/reporting kompleks | Medium | Batasi MVP ke metrik utama dan filter periode yang sudah disepakati |
| Agent pending review mendapat akses fitur yang belum seharusnya | Medium | Definisikan policy status agent sebelum fitur sensitif dibuka |
| Integrasi payout belum jelas | Medium | Pisahkan withdrawal request internal dari eksekusi payout provider |
| Requirement finance belum lengkap | Medium | Dokumentasikan open questions dan buat requirement finance terpisah sebelum implementasi besar |
| Test coverage tidak mengikuti perubahan behavior | Medium | Terapkan engineering standard: setiap behavior baru wajib regression test |
| Asumsi runtime tidak cocok dengan Laravel Cloud | Medium | Review dependency, storage, queue, scheduler, dan deploy command sebelum production release |
| File upload hilang karena bergantung pada local disk ephemeral | High | Gunakan object storage/Flysystem untuk file production yang perlu persisten |

## Open Questions

1. Apa definisi final status agent selain `pending_review`, `approved`, dan `rejected`?
2. Fitur apa saja yang boleh diakses agent saat status masih `pending_review`?
3. Siapa process owner final untuk review dokumen identitas agent: Admin, Operations, Finance, atau kombinasi?
4. Apakah OTP agent akan tetap email-only untuk jangka panjang atau perlu WhatsApp fallback?
5. Bagaimana aturan komisi final: rate tetap, tiered, per kategori UMKM, per area, atau campaign-specific?
6. Kapan komisi dianggap available untuk withdrawal: saat transaksi completed, setelah settlement, atau setelah periode hold?
7. Apakah withdrawal agent membutuhkan approval finance manual sebelum pencairan?
8. Payment/payout provider apa yang akan digunakan dan kapan integrasi dimasukkan ke scope?
9. Metrik finance dashboard apa yang wajib untuk MVP?
10. Apakah buyer dan seller akan memiliki web portal sendiri atau hanya API/mobile client?
11. Apakah perlu audit log formal untuk perubahan status agent, withdrawal, transaksi, dan data sensitif?
12. Berapa retention policy untuk dokumen identitas dan data OTP?
13. Apakah ada requirement compliance spesifik terkait data pribadi, consent, dan penghapusan akun?
14. Bagaimana strategi versioning API jika mobile/web client mulai berjalan paralel?
15. Apakah dashboard membutuhkan data real-time atau cukup near-real-time berdasarkan request API?
