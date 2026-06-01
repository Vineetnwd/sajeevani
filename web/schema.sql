-- Praanveda Ayurshakti Database Schema

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

-- --------------------------------------------------------

-- Table structure for `users` (Admin, Doctors, Executives, MR, Distributor, Stockist)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Doctor','Executive','Stockist','MR','Distributor') NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Basic Admin Account (Password: 123456)
INSERT INTO `users` (`name`, `phone`, `password`, `role`, `status`) VALUES
('Super Admin', 'admin', 'e10adc3949ba59abbe56e057f20f883e', 'Admin', 'Active'); 
-- MD5 for '123456' for legacy simplicity, consider bcrypt (`password_hash`) in future

-- --------------------------------------------------------

-- Table structure for `patients` (Leads)
CREATE TABLE IF NOT EXISTS `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `executive_id` int(11) NOT NULL, -- Created by which executive
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `age` int(3) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` text,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for `vitals`
CREATE TABLE IF NOT EXISTS `vitals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `bp` varchar(20) DEFAULT NULL,       -- e.g. 120/80
  `sugar` varchar(20) DEFAULT NULL,    -- Random, Fasting etc
  `weight` decimal(5,2) DEFAULT NULL,  -- In kg
  `pulse` int(5) DEFAULT NULL,         -- bpm
  `symptoms_notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for `consultations`
CREATE TABLE IF NOT EXISTS `consultations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,     -- Assigned / calling doctor
  `status` enum('Pending','In-Call','Prescribed','Rejected') DEFAULT 'Pending',
  `prescription_notes` text,
  `medicine_required` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for `products` (Medicines/Inventory)
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for `orders`
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consultation_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `stockist_id` int(11) DEFAULT NULL,   -- Assigned stockist
  `delivery_boy_id` int(11) DEFAULT NULL, -- Assigned delivery personnel
  `status` enum('Created','Packing','Dispatched','Delivered','Cancelled') DEFAULT 'Created',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
