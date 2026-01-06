-- Updated Database Schema without Location Tracking
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

DROP DATABASE IF EXISTS `waste_management_db`;
CREATE DATABASE IF NOT EXISTS `waste_management_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `waste_management_db`;

-- Users table (consumers and admins)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','consumer') NOT NULL DEFAULT 'consumer',
  `full_name` varchar(255) NOT NULL,
  `mpesa_phone` varchar(15) DEFAULT NULL,
  `bin_id` varchar(20) DEFAULT NULL,
  `subscription_status` enum('pending','active','expired') DEFAULT 'pending',
  `subscription_start_date` date DEFAULT NULL,
  `subscription_end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `bin_id` (`bin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin (password: password)
INSERT INTO `users` (`email`, `password`, `role`, `full_name`, `subscription_status`) VALUES
('admin@waste.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'active');

-- Trash bins (linked to users)
DROP TABLE IF EXISTS `trash_bins`;
CREATE TABLE `trash_bins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bin_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fill_level` int(11) DEFAULT 0,
  `status` enum('normal','needs_collection','collecting') DEFAULT 'normal',
  `last_emptied` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bin_id` (`bin_id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Waste trucks
DROP TABLE IF EXISTS `waste_trucks`;
CREATE TABLE `waste_trucks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `truck_name` varchar(100) NOT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `status` enum('idle','assigned','collecting') DEFAULT 'idle',
  `assigned_bin_id` varchar(20) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assigned_by` (`assigned_by`),
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample trucks
INSERT INTO `waste_trucks` (`truck_name`, `driver_name`, `status`) VALUES
('Truck Alpha', 'John Kamau', 'idle'),
('Truck Beta', 'Mary Wanjiku', 'idle'),
('Truck Gamma', 'Peter Ochieng', 'idle'),
('Truck Delta', 'Grace Akinyi', 'idle');

-- Payments table
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mpesa_receipt` varchar(50) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Complaints table
DROP TABLE IF EXISTS `complaints`;
CREATE TABLE `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','in_progress','resolved','closed') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `response_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `responded_by` (`responded_by`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`responded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Collection history
DROP TABLE IF EXISTS `collection_history`;
CREATE TABLE `collection_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bin_id` varchar(20) NOT NULL,
  `truck_id` int(11) NOT NULL,
  `fill_level_before` int(11) NOT NULL,
  `collected_by` varchar(255) DEFAULT NULL,
  `collection_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `truck_id` (`truck_id`),
  FOREIGN KEY (`truck_id`) REFERENCES `waste_trucks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;