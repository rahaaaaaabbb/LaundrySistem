-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Bulan Mei 2026 pada 11.47
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_laundry_modern`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `antrean`
--

CREATE TABLE `antrean` (
  `id_antrean` int(11) NOT NULL,
  `id_transaksi` int(11) NOT NULL,
  `prioritas` int(11) NOT NULL COMMENT '1 = Express (High Priority), 2 = Reguler (Standard FIFO)',
  `tahapan_sekarang` enum('menunggu','pencucian','pengeringan','penyetrikaan','siap_diambil','selesai') DEFAULT 'menunggu',
  `waktu_masuk` timestamp NOT NULL DEFAULT current_timestamp(),
  `waktu_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `antrean`
--

INSERT INTO `antrean` (`id_antrean`, `id_transaksi`, `prioritas`, `tahapan_sekarang`, `waktu_masuk`, `waktu_update`) VALUES
(1, 1, 2, 'selesai', '2026-05-08 09:23:49', '2026-05-08 09:31:58'),
(2, 2, 2, 'selesai', '2026-05-08 09:28:27', '2026-05-08 09:35:40'),
(6, 3, 2, 'selesai', '2026-05-09 08:01:10', '2026-05-09 08:14:48'),
(7, 4, 1, 'selesai', '2026-05-09 08:10:14', '2026-05-09 08:14:46'),
(8, 5, 1, 'selesai', '2026-05-09 08:18:42', '2026-05-11 07:54:13'),
(9, 6, 2, 'menunggu', '2026-05-11 07:54:23', '2026-05-11 07:54:23'),
(10, 7, 1, 'selesai', '2026-05-11 08:20:46', '2026-05-11 08:34:15'),
(11, 8, 1, 'pencucian', '2026-05-11 08:38:12', '2026-05-11 08:39:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `layanan`
--

CREATE TABLE `layanan` (
  `id_layanan` int(11) NOT NULL,
  `nama_layanan` varchar(100) NOT NULL,
  `jenis_layanan` enum('reguler','express') NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `estimasi_waktu` int(11) NOT NULL COMMENT 'Estimasi selesai dalam hitungan jam',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `layanan`
--

INSERT INTO `layanan` (`id_layanan`, `nama_layanan`, `jenis_layanan`, `harga`, `estimasi_waktu`, `created_at`) VALUES
(1, 'Cuci Komplit Reguler', 'reguler', 6000.00, 72, '2026-05-08 07:02:48'),
(2, 'Cuci Komplit Express', 'express', 12000.00, 24, '2026-05-08 07:02:48'),
(3, 'Cuci Satuan Selimut', 'reguler', 15000.00, 72, '2026-05-08 07:02:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_telp` varchar(15) NOT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pelanggan`
--

INSERT INTO `pelanggan` (`id_pelanggan`, `id_user`, `nama_lengkap`, `no_telp`, `alamat`, `created_at`) VALUES
(1, 2, 'Ahmad', '087728967883', 'Jl Sukareja tasikmalaya', '2026-05-08 07:08:47'),
(2, 7, 'Dadang', '082130614046', 'Jl.Sukamulya, Ciamis ', '2026-05-08 09:08:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_layanan` int(11) NOT NULL,
  `no_invoice` varchar(50) NOT NULL,
  `berat` decimal(5,2) NOT NULL COMMENT 'Total berat dalam Kg',
  `total_harga` decimal(12,2) NOT NULL,
  `status_pembayaran` enum('pending','settlement','expire','cancel') DEFAULT 'pending' COMMENT 'Status respon dari Payment Gateway',
  `url_pembayaran` varchar(255) DEFAULT NULL COMMENT 'Tautan invoice pembayaran online',
  `tanggal_transaksi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_pelanggan`, `id_layanan`, `no_invoice`, `berat`, `total_harga`, `status_pembayaran`, `url_pembayaran`, `tanggal_transaksi`) VALUES
(1, 2, 3, 'INV-260508112349', 6.50, 97500.00, 'settlement', NULL, '2026-05-08 09:23:49'),
(2, 1, 1, 'INV-260508112827', 6.00, 36000.00, 'settlement', NULL, '2026-05-08 09:28:27'),
(3, 1, 3, 'INV-260509100110', 5.00, 75000.00, 'settlement', NULL, '2026-05-09 08:01:10'),
(4, 1, 2, 'INV-260509101014', 6.20, 74400.00, 'settlement', NULL, '2026-05-09 08:10:14'),
(5, 1, 2, 'INV-260509101842', 4.00, 48000.00, 'settlement', NULL, '2026-05-09 08:18:42'),
(6, 2, 1, 'INV-260511095423', 3.00, 18000.00, 'pending', NULL, '2026-05-11 07:54:23'),
(7, 1, 2, 'INV-260511102046', 5.00, 60000.00, 'settlement', NULL, '2026-05-11 08:20:46'),
(8, 1, 2, 'INV-260511103812', 9.00, 108000.00, 'settlement', NULL, '2026-05-11 08:38:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kasir','pelanggan') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Aulia', '$2y$10$PEz3Iw5sZUnZf5cXs3E46eJZMNv4mdmZ3OJZU7Tr/x5sqQTvOCC/6', 'admin', '2026-05-08 07:04:56'),
(2, 'ahmad90', '$2y$10$YT13G5nJgsGkuDDtwx4MxOqRYnKlkJ0QQffR9nvYgkETK9aOdfaK6', 'pelanggan', '2026-05-08 07:08:47'),
(7, 'dadang61', '$2y$10$5NPwCSBBYEG4aiawbMAIvuoAukSuccUwsyEvedBGmtShpploPCUai', 'pelanggan', '2026-05-08 09:08:57');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `antrean`
--
ALTER TABLE `antrean`
  ADD PRIMARY KEY (`id_antrean`),
  ADD KEY `id_transaksi` (`id_transaksi`);

--
-- Indeks untuk tabel `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id_layanan`);

--
-- Indeks untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD UNIQUE KEY `no_invoice` (`no_invoice`),
  ADD KEY `id_pelanggan` (`id_pelanggan`),
  ADD KEY `id_layanan` (`id_layanan`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `antrean`
--
ALTER TABLE `antrean`
  MODIFY `id_antrean` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id_layanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `antrean`
--
ALTER TABLE `antrean`
  ADD CONSTRAINT `antrean_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD CONSTRAINT `pelanggan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_layanan`) REFERENCES `layanan` (`id_layanan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
