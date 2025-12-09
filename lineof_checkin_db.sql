-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 09, 2025 at 02:34 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lineof_checkin_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `checkin_time` datetime DEFAULT current_timestamp(),
  `status` enum('present','late') NOT NULL,
  `session_token` varchar(100) DEFAULT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `classroom_id`, `checkin_time`, `status`, `session_token`, `location_lat`, `location_lng`) VALUES
(19, 17, 23, '2025-12-02 23:09:29', 'present', 'SESS_692f0f225d504', 16.74564234, 100.20125963),
(20, 16, 23, '2025-12-02 23:10:13', 'present', 'SESS_692f0f225d504', 16.74557830, 100.20124830),
(21, 20, 23, '2025-12-03 10:04:59', 'present', 'SESS_692fa8cba40b8', 16.74877110, 100.18942870),
(22, 17, 23, '2025-12-03 10:05:45', 'present', 'SESS_692fa8cba40b8', 16.74873733, 100.18937279),
(23, 20, 26, '2025-12-03 14:35:34', 'present', 'SESS_692fe81108c75', 16.74876530, 100.18944010),
(24, 21, 26, '2025-12-08 09:25:19', 'present', 'SESS_6936368750ef0', 16.74884058, 100.18952627),
(25, 21, 26, '2025-12-08 11:17:45', 'present', 'SESS_69365091a423f', 16.74875817, 100.18942998);

-- --------------------------------------------------------

--
-- Table structure for table `classrooms`
--

CREATE TABLE `classrooms` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `room_color` varchar(20) DEFAULT '#FFFFFF',
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `qr_code_data` text DEFAULT NULL,
  `checkin_limit_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `student_limit` int(11) DEFAULT 40,
  `qr_token` varchar(100) DEFAULT NULL,
  `current_session_id` varchar(100) DEFAULT NULL,
  `zoom_link` text DEFAULT NULL,
  `teams_link` text DEFAULT NULL,
  `is_online_session` tinyint(1) DEFAULT 0,
  `session_link` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `classrooms`
--

INSERT INTO `classrooms` (`id`, `teacher_id`, `subject_name`, `course_code`, `class_code`, `room_color`, `lat`, `lng`, `qr_code_data`, `checkin_limit_time`, `created_at`, `student_limit`, `qr_token`, `current_session_id`, `zoom_link`, `teams_link`, `is_online_session`, `session_link`) VALUES
(23, 18, 'ตั้งใจเรียน', 'GG60', '440595', '#10B981', 16.74872244, 100.18936546, NULL, '10:18:00', '2025-12-02 16:08:19', 40, '7406', 'SESS_692fa8cba40b8', '', '', 0, ''),
(25, 18, 'cak', 'FUNMA', '179203', '#f79191ff', NULL, NULL, NULL, NULL, '2025-12-03 04:48:54', 40, NULL, NULL, NULL, NULL, 0, NULL),
(26, 18, 'cak', 'FUNMA', '772056', '#67f380ff', 16.74892748, 100.19235134, NULL, '15:38:00', '2025-12-03 04:49:02', 40, '6021', 'SESS_69365091a423f', NULL, NULL, 0, ''),
(28, 18, 'ทดสอบการตรวจจับพิกัด', 'TEST', '452466', '#10B981', 16.74832134, 100.19116044, NULL, '12:39:00', '2025-12-08 04:38:39', 40, '8211', 'SESS_693656a8be9b1', '', '', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `classroom_members`
--

CREATE TABLE `classroom_members` (
  `id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enroll_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `classroom_members`
--

INSERT INTO `classroom_members` (`id`, `classroom_id`, `student_id`, `enroll_date`) VALUES
(17, 23, 16, '2025-12-02 16:08:33'),
(18, 23, 17, '2025-12-02 16:08:53'),
(19, 23, 20, '2025-12-03 03:03:21'),
(20, 26, 20, '2025-12-03 04:49:14'),
(21, 25, 20, '2025-12-03 04:49:23'),
(23, 26, 21, '2025-12-04 09:21:17'),
(24, 25, 21, '2025-12-08 04:12:22'),
(25, 28, 20, '2025-12-08 04:39:42');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sender_name` varchar(255) DEFAULT NULL,
  `line_user_id` varchar(255) DEFAULT NULL,
  `topic` varchar(255) DEFAULT 'ทั่วไป',
  `message` text DEFAULT NULL,
  `phone` varchar(10) NOT NULL,
  `status` enum('pending','replied') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `name` varchar(100) NOT NULL,
  `edu_id` varchar(50) DEFAULT NULL,
  `line_user_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `edu_id`, `line_user_id`, `created_at`, `active`, `phone`) VALUES
(8, 'admin', '1234', 'admin', 'admin', '', 'Ub7e74e1847e675152553e08898635861', '2025-11-27 04:05:27', 1, NULL),
(17, 'mam', '1234', 'student', 'mam za', '002', 'U8fa3e266dbbf49569f34b23c13088376', '2025-12-02 16:07:07', 1, NULL),
(18, 't1', '1234', 'teacher', 'teacher', '', 'Ub7e74e1847e675152553e08898635861', '2025-11-27 04:05:27', 1, NULL),
(20, 'ped', '1234', 'student', 'เป็ด ก๊า', '001', 'U68bf2e4e406b45ecec5c0005f7b72b73', '2025-12-03 01:49:09', 1, '0800977084'),
(21, 's1', '1234', 'student', 'student', '', 'Ub7e74e1847e675152553e08898635861', '2025-11-27 04:05:27', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classrooms`
--
ALTER TABLE `classrooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classroom_members`
--
ALTER TABLE `classroom_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`classroom_id`,`student_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `classrooms`
--
ALTER TABLE `classrooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `classroom_members`
--
ALTER TABLE `classroom_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
