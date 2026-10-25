-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jan 26, 2026 at 04:04 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_intim`
--

-- --------------------------------------------------------

--
-- Table structure for table `berita_galeri`
--

CREATE TABLE `berita_galeri` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `berita_galeri`
--

INSERT INTO `berita_galeri` (`id`, `judul`, `deskripsi`, `gambar`, `kategori`, `tanggal`, `created_at`) VALUES
(1, 'Pameran Teknologi 2026', 'cccccccccc', '../uploads/galeri/galeri_696873d5319ac.webp', 'Kegiatan Perusahaan', '2026-01-15', '2026-01-15 04:57:57'),
(2, 'Pameran Teknologi 2026', 'mmm', '../uploads/galeri/galeri_69689d501f9c6.jpg', 'Produksi', '2026-01-15', '2026-01-15 07:54:56'),
(3, 'Pameran Teknologi 2026', 'jjn', '../uploads/galeri/galeri_69707ec722fa8.jpg', 'Produksi', '2026-01-21', '2026-01-21 07:22:47');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `created_at`) VALUES
(5, 'FIKA updated', 'taufika@gmail.com', '085867393603', 'sssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 'sssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssss', '2026-01-21 07:26:17');

-- --------------------------------------------------------

--
-- Table structure for table `karir`
--

CREATE TABLE `karir` (
  `id` int(11) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `deadline` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `karir`
--

INSERT INTO `karir` (`id`, `position`, `department`, `location`, `type`, `description`, `requirements`, `deadline`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 'zzzzzzzzzzzz', 'Produksi', 'Klaten, Jawa Tengah', 'Contract', 'sssss', 'sssssss', '2026-02-20', 0, '2026-01-21 01:22:04', '2026-01-21 02:01:21'),
(4, 'zzzzzzzzzzzzss', 'Produksi', 'Klaten, Jawa Tengah', 'Internship', 'sssss', 'ssssssssssssssssssss', '2026-02-20', 1, '2026-01-21 01:22:26', '2026-01-21 07:47:14');

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan`
--

CREATE TABLE `kegiatan` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal` date NOT NULL,
  `waktu` varchar(50) DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `lamaran`
--

CREATE TABLE `lamaran` (
  `id` int(11) NOT NULL,
  `karir_id` int(11) NOT NULL,
  `nama_pelamar` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telepon` varchar(20) NOT NULL,
  `pendidikan` varchar(100) NOT NULL,
  `pengalaman` text NOT NULL,
  `motivasi` text NOT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `foto_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','review','interview','accepted','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `lamaran`
--

INSERT INTO `lamaran` (`id`, `karir_id`, `nama_pelamar`, `email`, `telepon`, `pendidikan`, `pengalaman`, `motivasi`, `cv_file`, `foto_file`, `status`, `created_at`, `updated_at`) VALUES
(3, 4, 'Taufika Retno Wulanx', 'taufikaa94@gmail.com', '085867393603', 'D1/D2/D3', 'nnnnnnnnnnnnnnnnnnn', 'nnnnnnnnnnnnn', NULL, NULL, 'review', '2026-01-21 14:47:37', '2026-01-21 14:47:37');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `kode_inna` varchar(50) NOT NULL,
  `gambar` text DEFAULT NULL,
  `motif_kain` varchar(100) DEFAULT NULL,
  `tipe_kain` varchar(100) DEFAULT NULL,
  `warna_kain` text DEFAULT NULL,
  `screen` varchar(50) DEFAULT NULL,
  `deskripsi` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`kode_inna`, `gambar`, `motif_kain`, `tipe_kain`, `warna_kain`, `screen`, `deskripsi`, `created_at`) VALUES
('INNA-001', 'uploads/1768441193_MAIN_INNA-001.jpg', 'Batik Soloom', 'Katunm', 'Merah:#FF0000:0,Biru:#0000FF:0', '120', '', '2026-01-14 02:10:12'),
('INNA-001mm', '../uploads/1768463772_MAIN_INNA-001mm.jpg,../uploads/1768463772_VAR_0_INNA-001mm.jpeg', 'Batik Solo', 'Katun', 'Warna #800000:#800000:1,nk:#df9595:0', '120', '', '2026-01-15 07:56:12'),
('INNA-001ssm', '../uploads/1768894609_MAIN_INNA-001ssm.jpeg', 'Batik Solo', 'Katun', 'Warna #493232:#493232:0', '120', ',,,,,,,,,,,,,,,,p', '2026-01-20 07:36:49'),
('INNA-001ssx', '../uploads/1768952291_MAIN_INNA-001ssx.jpeg,../uploads/1768952291_VAR_0_INNA-001ssx.webp', 'Batik Solo', 'Katun', 'Abu-abu:#808080:1', '120', 'xxxxxxxxxxx', '2026-01-20 23:38:11'),
('INNA-001ssxm', '../uploads/1768980141_MAIN_INNA-001ssxm.webp,../uploads/1768980141_VAR_1_INNA-001ssxm.jpg', 'Batik Solo', 'Katun', 'Orange:#ffa500:0,Warna 2:#235753:1', '120', 'jjjjjjjjjjj', '2026-01-21 07:22:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
(4, 'admin', '$2y$10$H9MwcTGi9raEpLzgUfDTbuuS3utMDZNKFnF4AB1pw6OAusZheT2sG', 'admin', '2026-01-14 02:22:39', '2026-01-21 02:12:40'),
(8, 'userA', '$2y$10$dBJtZvGiSxiLbVsncIYd1.z9c25szp2.IA9DHcHe/YDGdG4zH4jGW', 'user', '2026-01-21 02:14:03', '2026-01-21 02:14:03'),
(9, 'user', '$2y$10$QSKYNVaIDYrMzfTVKjqGdu3355py6ZPT8wmrrZZanLP8nB9EUjI3i', 'user', '2026-01-21 03:27:51', '2026-01-21 03:27:51'),
(10, 'f', '$2y$10$4DSskPPd/YaNnZM87MHmsegBVBwaqzYkvyrMgj7bOAVznBvVfA4zy', 'admin', '2026-01-21 03:32:48', '2026-01-21 07:28:11'),
(11, 'fika', '$2y$10$P8qZiyG/T3HcU0OstgvNSeuXuq.M.ueUmvoS.tc6gu2Cme3DENTwO', 'user', '2026-01-21 03:33:24', '2026-01-21 03:33:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `berita_galeri`
--
ALTER TABLE `berita_galeri`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `karir`
--
ALTER TABLE `karir`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lamaran`
--
ALTER TABLE `lamaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_karir` (`karir_id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`kode_inna`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `berita_galeri`
--
ALTER TABLE `berita_galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `karir`
--
ALTER TABLE `karir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kegiatan`
--
ALTER TABLE `kegiatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lamaran`
--
ALTER TABLE `lamaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lamaran`
--
ALTER TABLE `lamaran`
  ADD CONSTRAINT `fk_karir` FOREIGN KEY (`karir_id`) REFERENCES `karir` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
