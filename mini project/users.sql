-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2025 at 01:14 PM
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
-- Database: `westleys_resto_cafe`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password`, `created_at`) VALUES
(1, 'Daniya Thomas', 'daniya123@gmail.com', '9999245600', '$2y$10$gZcF11bM3l5oMHcq1yNdt.ISv9RXMlMFVdEfkz6nf0MNs7HV0CGHK', '2025-07-09 10:03:25'),
(2, 'gowri nandana', 'sweety23dd@gmail.com', '8714296955', '$2y$10$mjtPWtHsUjHcJIEe7bdPounjY9LSXygzZZC7q6nz7JObVoT0d101K', '2025-07-11 11:02:56'),
(3, 'Reema S', 'rema07@gmail.com', '6743215687', '$2y$10$Y3fe7h7XKecm5vZWHqUGK.gd./0r0uWCgn5GklIZy/TB8fUFXsWCe', '2025-07-16 03:56:04'),
(4, 'Delna Thomas', 'daniyathomas439@gmail.com', '8921951595', '$2y$10$FEO33bABCSodVDk7kAjlw.u8jUBFopCaCFBtKS4Qy7UBQYG0TeCxe', '2025-07-21 05:09:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
