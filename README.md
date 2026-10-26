Aplikasi ini adalah Sistem Manajemen Laundry Modern berbasis Web yang dirancang untuk mendigitalisasi operasional bisnis laundry.
Sistem ini menjembatani tiga peran utama (Multi-role):

Administrator / Kasir: Mengelola data pelanggan, mencatat transaksi masuk, mengatur antrean tahapan cucian, melihat rekam jejak omset, serta mencetak laporan keuangan.

Pelanggan: Mempermudah pengguna untuk melihat katalog layanan, melacak status pesanan secara real-time, melakukan pembayaran secara digital menggunakan Payment Gateway (Midtrans), serta mengunduh E-Nota transaksi.

A. Fitur Sisi Pelanggan (Customer Dashboard & Mobile Interface)
Katalog & Beranda Interaktif: Menampilkan informasi layanan cuci (reguler maupun express), estimasi waktu selesai, serta carousel informasi/promo menarik.

Tracking Status Laundry (Real-Time): Memantau progres cucian secara transparan dengan persentase dan ikon visual (mulai dari menunggu, pencucian, pengeringan, penyetrikaan, hingga siap diambil).

Integrasi Pembayaran Digital (Midtrans Snap): Pelanggan dapat langsung membayar tagihan secara online menggunakan virtual account, dompet digital, atau QRIS melalui pop-up Midtrans.

E-Nota (Nota Digital): Menyediakan struk digital interaktif lengkap dengan detail berat, harga, hingga cap stempel PAID/LUNAS yang bisa diunduh atau dicetak.

Manajemen Profil: Memungkinkan pelanggan mengubah kata sandi akun demi keamanan.

B. Fitur Sisi Administrator
Dashboard Workspace (Bento Grid): Menyajikan ringkasan cepat berupa estimasi omset harian, jumlah total pelanggan, transaksi aktif, serta grafik monitor antrean operasional secara real-time.

Manajemen Pelanggan & Akun Otomatis: Admin dapat mendaftarkan pelanggan baru, di mana sistem secara otomatis membuatkan username dan password default bagi pelanggan untuk masuk ke aplikasi.

Kasir & Pembuatan Transaksi: Menginput berat cucian, memilih jenis layanan (reguler/express), serta menerapkan logika diskon loyalitas otomatis (misal diskon khusus pada transaksi kelipatan tertentu).

Monitor Antrean & Prioritas (FIFO): Mengelola antrean dengan sistem prioritas (Layanan Express otomatis menjadi prioritas utama/High Priority, sedangkan Reguler mengikuti sistem First-In, First-Out).

Laporan Keuangan & Cetak PDF: Rekapitulasi seluruh data transaksi dan proyeksi pendapatan kotor yang dapat dicetak langsung menjadi laporan fisik/PDF.

Teknologi & Framework

Aplikasi ini tidak menggunakan full-stack framework besar seperti Laravel atau CodeIgniter, melainkan dibangun menggunakan PHP Native yang terstruktur dengan pendekatan modular, dipadukan dengan berbagai pustaka modern (modern frontend libraries):

Backend:

PHP (Native - Versi 8.2+): Menggunakan fungsi prosedural/standar PHP dengan ekstensi mysqli untuk koneksi dan eksekusi kueri database.

Database MySQL / MariaDB: Struktur database bernama db_laundry_modern yang mencakup tabel: users, pelanggan, layanan, transaksi, dan status_laundry (lengkap dengan foreign key dan on delete cascade).

Payment Gateway API:

Midtrans Snap API (Sandbox Mode): Menggunakan komunikasi cURL dan JavaScript Snap.js untuk memproses pembayaran online secara aman.

Frontend & Desain UI/UX:

HTML5 & CSS3 (Custom Modern UI / CSS Variables): Menggunakan tata letak modern seperti Bento Grid, Flexbox, dan Mobile-First Design untuk tampilan ala aplikasi seluler (App-like experience).

JavaScript & GSAP (GreenSock Animation Platform): Digunakan untuk memberikan animasi transisi masuk (fade/slide) yang halus pada elemen antarmuka.

Lenis Scroll (@studio-freight/lenis): Pustaka JavaScript untuk menciptakan efek smooth scrolling yang nyaman pada halaman admin.

Phosphor Icons: Pustaka ikon modern (@phosphor-icons/web) untuk mempermanis antarmuka ikon tombol dan navigasi.

Flickity: Pustaka touch, responsive flickity carousel untuk komponen slider di beranda pelanggan.
