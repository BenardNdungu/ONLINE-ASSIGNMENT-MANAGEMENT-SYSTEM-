-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2025 at 09:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `oams`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('admin','teacher') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `target_user` enum('student','teacher','all') DEFAULT 'student',
  `created_at` datetime DEFAULT current_timestamp(),
  `expiry_date` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `sender_id`, `sender_role`, `title`, `content`, `target_user`, `created_at`, `expiry_date`, `updated_at`) VALUES
(1, 2, 'admin', 'System update', 'There will be a system update on the 9th November 2025', 'student', '2025-11-07 00:16:15', '2025-11-10 00:00:00', '2025-11-07 00:40:51'),
(2, 3, 'teacher', 'Do work', 'please ensure you complete the assignment', 'student', '2025-11-07 00:18:09', '0000-00-00 00:00:00', '2025-11-07 00:41:05'),
(3, 3, 'teacher', NULL, 'please ensure you complete the assignment', 'student', '2025-11-07 00:18:14', '0000-00-00 00:00:00', '2025-11-07 00:18:14'),
(4, 2, 'admin', 'New Feature', 'Anew feature has been added to the system', 'student', '2025-11-07 01:05:56', NULL, '2025-11-07 01:05:56');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'open',
  `max_score` int(11) DEFAULT 100,
  `allow_late_submission` tinyint(1) DEFAULT 1,
  `reopened_at` datetime DEFAULT NULL,
  `reopened_by` int(11) DEFAULT NULL,
  `reopen_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `teacher_id`, `title`, `description`, `file`, `created_at`, `due_date`, `status`, `max_score`, `allow_late_submission`, `reopened_at`, `reopened_by`, `reopen_reason`) VALUES
(6, 3, 'IBP', 'DO RESEARCH', 'assets/uploads/res_68ecb39a9af608.18974938.docx', '2025-10-19 08:08:58', '2025-10-31 00:00:00', 'open', 100, 1, '2025-10-25 00:59:49', 0, 'add more time for research '),
(7, 3, 'Operating system', 'Answer the following questions', 'assets/uploads/res_68fdf44d10d426.65767144.pdf', '2025-10-26 10:13:33', '2025-10-31 08:00:00', 'open', 50, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('admin','teacher') NOT NULL,
  `target_user` enum('student','teacher','all') DEFAULT 'student',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `sender_id`, `sender_type`, `target_user`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'admin', 'all', 'New Announcement', 'There will be a system update on the 9th November 2025', 0, '2025-11-07 00:16:15'),
(2, 3, 'teacher', 'student', 'New Announcement', 'please ensure you complete the assignment', 0, '2025-11-07 00:18:09'),
(3, 3, 'teacher', 'student', 'New Announcement', 'please ensure you complete the assignment', 0, '2025-11-07 00:18:14'),
(4, 2, 'admin', 'student', 'New Feature', 'Anew feature has been added to the system', 0, '2025-11-07 01:05:56');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `system_name` varchar(255) DEFAULT NULL,
  `institution_name` varchar(255) DEFAULT NULL,
  `system_logo` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `min_password_length` int(11) DEFAULT NULL,
  `account_lockout_limit` int(11) DEFAULT NULL,
  `session_timeout` int(11) DEFAULT NULL,
  `two_factor_auth` tinyint(1) DEFAULT NULL,
  `maintenance_mode` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `system_name`, `institution_name`, `system_logo`, `contact_email`, `min_password_length`, `account_lockout_limit`, `session_timeout`, `two_factor_auth`, `maintenance_mode`) VALUES
(1, 'TASKNEST', 'st charles lwanga secondary school', '', 'stcharleslwanga@gmail.com', 8, 5, 30, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `score` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('submitted','graded','late') DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `assignment_id`, `student_id`, `file`, `submitted_at`, `score`, `feedback`, `status`) VALUES
(10, 6, 1, 'uploads/submissions/1760345805_PROJECT PROPOSAL.docx', '2025-10-13 08:56:45', 90, 'Great work', 'graded'),
(11, 6, 10, 'uploads/submissions/1761473015_FULL DOCUMENT.docx', '2025-10-26 10:03:35', 85, 'Great work', 'graded');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `profile_pic` varchar(255) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `name`, `email`, `password`, `phone`, `department`, `created_at`, `status`, `profile_pic`, `otp_code`, `otp_expires`) VALUES
(1, 'student', 'Nancy', 'mumbi@gmail.com', '$2y$10$QRhN734n60vmQYQ9X56ll.oDJ.32EFNpOnIaZCRcRz8A/UQEEfOt.', '0799887767', 'Computer Science', '2025-09-15 21:52:08', 'active', 'uploads/1.jpg', NULL, NULL),
(2, 'admin', 'Benard', 'benardadmin@gmail.com', '$2y$10$WOPAd1Ts1d0pr4ytAPXD1O1o0L1nZNWLnOqXDnJSnGAPovvguMu2y', '0711222333', NULL, '2025-09-15 22:31:17', 'active', NULL, NULL, NULL),
(3, 'teacher', 'Mr john', 'johnteacher@gmail.com', '$2y$10$6F/XHdtno0.uz2DlzS418emk6XieIAcO3nmRGrzhVv1D3qdEVf/La', '0711444555', 'Computer science ', '2025-09-15 22:33:01', 'active', 'uploads/teacher_3_1762501698.jpg', NULL, NULL),
(4, 'student', 'Lizz', 'lizb@gmail.com', '$2y$10$FDfyqIqZmbl0EX.fJAwdnu8sKiSBp5JWSepcAcV34kOz269t6KivS', '0788888888', 'Business\r\n', '2025-09-24 17:54:32', 'active', NULL, NULL, NULL),
(6, 'student', 'Susan', 'susan123@gmail.com', '$2y$10$nMMnOVcZ1pqTuun/5bqzEOSkAWXa3Vfcz7n9oxW9qV6bywfWtPvCe', '0744555666', 'Journalism ', '2025-09-26 21:48:26', 'active', NULL, NULL, NULL),
(8, 'student', 'perpetual', 'perp@gmail.com', '$2y$10$OHQY3zNkZX/DVwIGCxus7OaNBO0ZwCf5TANz4PVfIOS5zJHW3iHhW', '', 'Business', '2025-10-01 23:33:47', 'active', NULL, NULL, NULL),
(9, 'teacher', 'Miss Mundia', 'mundia123@gmail.com', '$2y$10$3T6nJHmdGk08PoW7Yapi5OoDU8O6AWChYLbvJvlyoSRhsSS7.0LyO', '0745454545', 'Business', '2025-10-13 15:30:32', 'active', '/../uploads/teacher_9_1762936474.jpeg', NULL, NULL),
(10, 'student', 'Maxwell kamau', 'maxwellk123@gmail.com', '$2y$10$7QePo453QZK7ba1qNpnU9OBujyGOuOYLHXhU87530BJ6QortMbWGu', '0789562314', 'Hospitality', '2025-10-26 09:53:48', 'active', 'uploads/100.jpg', NULL, NULL),
(11, 'teacher', 'Alice', 'alice.teacher@gmail.com', '$2y$10$byyLbSv/pknjyjexa4GGbeojwAJsMvNt.dqOSklYjn/ZZOsiHEzvW', NULL, NULL, '2025-10-26 10:17:39', 'active', NULL, NULL, NULL),
(15, 'teacher', 'Mr Mark', 'mark.teacher@gmail.com', '$2y$10$86ZLVA6ktM57Cis0sdM7uuaSHZ48LcqZta1FK9LkLkLrQ9lHAYG62', NULL, NULL, '2025-11-12 08:06:30', 'active', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

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
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
