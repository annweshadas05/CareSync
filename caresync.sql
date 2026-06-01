-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2026 at 07:57 AM
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
-- Database: `caresync`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `user` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `activity`, `user`, `created_at`) VALUES
(1, 'New Patient Registered: PAT-2026-001', 'System', '2026-05-14 18:44:14'),
(2, 'New Doctor Added', 'Admin', '2026-05-14 18:45:49'),
(3, 'New Attendee Registered: ATTN-2026-003', 'Admin', '2026-05-14 19:35:37'),
(4, 'New Attendee Registered: ATTN-2026-004', 'Admin', '2026-05-14 19:37:53');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_code` varchar(20) NOT NULL COMMENT 'References patients.patient_code',
  `doctor_code` varchar(20) NOT NULL COMMENT 'References doctors.doctor_code',
  `slot_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'confirmed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendees`
--

CREATE TABLE `attendees` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `hospital_branch` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `attendee_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendees`
--

INSERT INTO `attendees` (`id`, `full_name`, `email`, `mobile`, `hospital_branch`, `password`, `attendee_code`, `created_at`) VALUES
(2, 'Manish Sharma', 'mca.24mmce34@silicon.ac.in', '8340778990', 'Bhubaneswar', 'mani123', 'ATTN-2026-002', '2026-04-16 07:28:07'),
(4, 'Ankit Sao', 'ankitsao7852@gmail.com', '7894561230', 'Bhubaneswar', '$2y$10$5NOvYHuNQBCgU3O7ZHdss.yP4a54hH.Za3ZSi4VoY5Fyk2teWAGfy', 'ATTN-2026-004', '2026-05-14 19:37:53');

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`name`, `email`, `message`) VALUES
('Manish Sharma', 'SHARMAMANISH5846579@GMAIL.COM', 'bfhghfgh'),
('Manish Sharma', 'SHARMAMANISH5846579@GMAIL.COM', 'ko'),
('Manish Sharma', 'SHARMAMANISH5846579@GMAIL.COM', 'bdgbgdbdg');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `doctor_code` varchar(20) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT 0,
  `contact` varchar(15) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `doctor_code`, `full_name`, `department`, `specialization`, `experience`, `contact`, `email`, `password`, `created_at`) VALUES
(1, 'DOC-2026-001', 'Rahul Kumar', 'Cardiology', 'Surgeon', 5, '7894561230', 'manishsharma081999@gmail.com', '$2y$10$KRhwIKJbxtAlNZSQ8FKv4OvDAVTv2kLCyxAgZRGGRZNZFYIU6LX.e', '2026-05-14 18:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'References users.id',
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 3, 'New appointment scheduled with patient #PAT-2026-001 for May 31, 2026 1:16 AM', 'appointment', 0, '2026-05-14 18:47:45'),
(2, 3, 'Your appointment on Sunday, May 31, 2026 has been cancelled.', 'appointment', 0, '2026-05-14 18:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `patient_code` varchar(20) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mobile` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `aadhar` varchar(12) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_code`, `full_name`, `email`, `mobile`, `dob`, `gender`, `aadhar`, `blood_group`, `city`, `address`, `password`, `created_at`) VALUES
(1, 'PAT-2026-001', 'Manish Sharma', 'sharmamanish5846579@gmail.com', '8340778990', '2026-05-04', 'male', '565623232232', 'O+', 'Barajamda', 'Near Reliance Tower Football Ground Barajamda', '$2y$10$5T4rJHg65XFAf09e4vxFbeeKalauV1o5jYmf.XHxgf85QUlPo1LSG', '2026-05-14 18:44:14');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_medicines`
--

CREATE TABLE `prescription_medicines` (
  `id` int(11) NOT NULL,
  `medicine` varchar(100) DEFAULT NULL,
  `form` varchar(50) DEFAULT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `patient_code` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_medicines`
--

INSERT INTO `prescription_medicines` (`id`, `medicine`, `form`, `dosage`, `frequency`, `duration`, `patient_code`, `notes`, `created_at`) VALUES
(1, 'Calpol', 'Syrup', '4ml', 'Three times daily', '3 days', 'PAT-2026-001', 'Please Take Medicine on time', '2026-05-18 02:10:32'),
(2, 'Delcon', 'Syrup', '8ml', 'Three times daily', '3 days', 'PAT-2026-001', 'Please Take Medicine on time', '2026-05-18 02:10:32');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `doctor_code` int(11) NOT NULL COMMENT 'References users.id where role=doctor',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'available',
  `location` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT 1,
  `booked_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `doctor_code`, `start_time`, `end_time`, `status`, `location`, `capacity`, `booked_count`) VALUES
(1, 3, '2026-05-31 01:16:00', '2026-05-31 02:17:00', 'available', 'CareSync Clinic Room A', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `patient_code` varchar(20) DEFAULT NULL,
  `doctor_code` varchar(20) DEFAULT NULL,
  `attendee_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `reset_token`, `token_expiry`, `patient_code`, `doctor_code`, `attendee_code`) VALUES
(1, 'Admin', 'admincaresync@gmail.com', '$2y$10$g5neN3iDI.gTx20qWwm72uoFVPpVQ/AzkChky2BA8FOvw.I7jB7J.', 'admin', NULL, NULL, NULL, NULL, NULL),
(2, 'Manish Sharma', 'sharmamanish5846579@gmail.com', '$2y$10$5T4rJHg65XFAf09e4vxFbeeKalauV1o5jYmf.XHxgf85QUlPo1LSG', 'patient', NULL, NULL, 'PAT-2026-001', NULL, NULL),
(3, 'Rahul Kumar', 'manishsharma081999@gmail.com', '$2y$10$KRhwIKJbxtAlNZSQ8FKv4OvDAVTv2kLCyxAgZRGGRZNZFYIU6LX.e', 'doctor', NULL, NULL, NULL, 'DOC-2026-001', NULL),
(4, 'Ankit Sao', 'ankit7852@gmail.com', '$2y$10$Zbb9AxSOUM2yE/OTfJG0qOGhIPTJV.9M9W9SqfOSpDOS0U56qzhCO', 'attendee', NULL, NULL, NULL, NULL, 'ATTN-2026-003'),
(5, 'Ankit Sao', 'ankitsao7852@gmail.com', '$2y$10$5NOvYHuNQBCgU3O7ZHdss.yP4a54hH.Za3ZSi4VoY5Fyk2teWAGfy', 'attendee', NULL, NULL, NULL, NULL, 'ATTN-2026-004');

-- --------------------------------------------------------

--
-- Table structure for table `vitals`
--

CREATE TABLE `vitals` (
  `id` int(11) NOT NULL,
  `patient_code` varchar(20) NOT NULL,
  `attendee_code` varchar(20) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL COMMENT 'e.g. 120/80',
  `heart_rate` int(11) DEFAULT NULL COMMENT 'bpm',
  `temperature` decimal(4,1) DEFAULT NULL COMMENT 'Celsius',
  `respiratory_rate` int(11) DEFAULT NULL COMMENT 'breaths/min',
  `oxygen_saturation` decimal(4,1) DEFAULT NULL COMMENT 'percent',
  `blood_sugar` decimal(6,1) DEFAULT NULL COMMENT 'mg/dL',
  `weight_kg` decimal(5,1) DEFAULT NULL,
  `height_cm` decimal(5,1) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vitals`
--

INSERT INTO `vitals` (`id`, `patient_code`, `attendee_code`, `blood_pressure`, `heart_rate`, `temperature`, `respiratory_rate`, `oxygen_saturation`, `blood_sugar`, `weight_kg`, `height_cm`, `notes`, `recorded_at`) VALUES
(1, 'PAT-2026-001', 'ATTN-2026-004', '100', 40, 32.0, 15, 74.0, 90.0, 70.0, 159.8, 'All Good', '2026-05-14 19:46:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendees`
--
ALTER TABLE `attendees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `attendee_code` (`attendee_code`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `password` (`password`),
  ADD UNIQUE KEY `doctor_code` (`doctor_code`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_code` (`patient_code`),
  ADD UNIQUE KEY `aadhar` (`aadhar`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `password` (`password`);

--
-- Indexes for table `prescription_medicines`
--
ALTER TABLE `prescription_medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_code` (`patient_code`),
  ADD UNIQUE KEY `doctor_code` (`doctor_code`),
  ADD UNIQUE KEY `attendee_code` (`attendee_code`);

--
-- Indexes for table `vitals`
--
ALTER TABLE `vitals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_code` (`patient_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendees`
--
ALTER TABLE `attendees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `prescription_medicines`
--
ALTER TABLE `prescription_medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vitals`
--
ALTER TABLE `vitals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
