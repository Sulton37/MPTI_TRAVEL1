-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 31, 2025 at 07:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paket_travel`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `avatar`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin MPTI Travel', 'admin@mptitravel.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 1, NULL, '2025-05-30 12:23:16', '2025-05-30 12:23:16'),
(2, 'test', 'testadmin@gmail.com', '2025-05-16 12:51:05', 'testadmin', 'admin', NULL, 1, NULL, '2025-05-30 12:51:43', '2025-05-30 12:51:43'),
(3, 'Admin', 'admin@vacationland.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 1, NULL, '2025-05-30 12:56:24', '2025-05-30 12:56:24');

-- --------------------------------------------------------

--
-- Table structure for table `package_gallery`
--

CREATE TABLE `package_gallery` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `photo_filename` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `photo_order` int(11) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_gallery`
--

INSERT INTO `package_gallery` (`id`, `package_id`, `photo_filename`, `caption`, `photo_order`, `uploaded_at`) VALUES
(1, 15, 'gallery_15_1748667617_683a8ce10f8c8.png', 'fyfyy', 1, '2025-05-31 05:00:17');

-- --------------------------------------------------------

--
-- Table structure for table `paket`
--

CREATE TABLE `paket` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `fotos` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `itinerary` text DEFAULT NULL,
  `highlights` text DEFAULT NULL,
  `inclusions` text DEFAULT NULL,
  `exclusions` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration` varchar(50) DEFAULT '2D1N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `paket`
--

INSERT INTO `paket` (`id`, `nama`, `deskripsi`, `fotos`, `created_at`, `updated_at`, `itinerary`, `highlights`, `inclusions`, `exclusions`, `price`, `duration`) VALUES
(15, 'bla bla bla', 'Jelajahi warisan budaya dan sejarah Yogyakarta dalam paket wisata 2 hari 1 malam. Kunjungi Candi Borobudur, Prambanan, Keraton, dan nikmati kuliner khas Jogja dengan pemandu berpengalaman.', '[\"1748618583_6839cd57671f8_1.jpg\",\"1748618583_6839cd57674aa_2.jpg\",\"1748618583_6839cd5767685_3.jpg\"]', '2025-05-30 15:23:03', '2025-05-30 15:23:03', 'Hari 1: Siang - jalan santau | Sore - nonton | Malam - turu', 'Candi Borobudur - Warisan Dunia UNESCO | Keraton Yogyakarta - Istana Sultan | Candi Prambanan - Kemegahan Hindu | Malioboro Street - Jantung Kota Jogja | Taman Sari - Istana Air Bersejarah | Kuliner Khas Gudeg Jogja', 'Hotel bintang 3 dengan AC dan breakfast | Transportasi AC selama tour | Guide profesional berbahasa Indonesia | Tiket masuk semua objek wisata | Makan siang 2x | Air mineral selama perjalanan | Parkir dan toll', 'Tiket pesawat/kereta api | Makan malam | Pengeluaran pribadi | Tips guide dan driver | Asuransi perjalanan | Aktivitas tambahan di luar itinerary', 0.00, '1D');

-- --------------------------------------------------------

--
-- Table structure for table `paket_backup`
--

CREATE TABLE `paket_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nama` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `fotos` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `paket_backup`
--

INSERT INTO `paket_backup` (`id`, `nama`, `deskripsi`, `fotos`, `created_at`, `updated_at`) VALUES
(7, 'JALAN JALAN KE BANDUNG', 'dsdds', NULL, '2025-05-30 13:24:36', '2025-05-30 13:24:36'),
(8, 'JALAN JALAN KE BANDUNG', 'hjsvhjdvjsbd', '[\"1748612156_6839b43ce5c52_1.png\",\"1748612156_6839b43ce5d7d_2.png\",\"1748612156_6839b43ce5ea3_3.jpg\"]', '2025-05-30 13:35:56', '2025-05-30 13:35:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `admins_email_unique` (`email`);

--
-- Indexes for table `package_gallery`
--
ALTER TABLE `package_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `photo_order` (`photo_order`);

--
-- Indexes for table `paket`
--
ALTER TABLE `paket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id` (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `package_gallery`
--
ALTER TABLE `package_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `paket`
--
ALTER TABLE `paket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
