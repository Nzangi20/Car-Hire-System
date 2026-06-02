-- Car Hire System Database Schema (Enhanced Edition)
-- Database: `prestige_wheels`

CREATE DATABASE IF NOT EXISTS `prestige_wheels`;
USE `prestige_wheels`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `phone` varchar(20) DEFAULT NULL,
  `id_number` varchar(50) NOT NULL,
  `driving_license` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `verification_status` enum('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,
  `role` enum('super_admin','manager','staff','customer') NOT NULL DEFAULT 'customer',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0, -- Left for backward compatibility
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `user_documents`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `document_type` enum('license','id_passport') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `user_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `cars`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_number` varchar(50) DEFAULT NULL UNIQUE,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `transmission` enum('manual','automatic') NOT NULL DEFAULT 'automatic',
  `capacity` int(11) NOT NULL DEFAULT 5,
  `charge_per_day` decimal(10,2) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available', -- 'available', 'maintenance'
  `quantity` int(11) NOT NULL DEFAULT 1,
  `category` varchar(50) DEFAULT 'SUV',
  `description` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT -1.28330000,
  `longitude` decimal(11,8) DEFAULT 36.82190000,
  `last_tracked` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `car_images`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `car_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `car_images_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `bookings`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pickup_datetime` datetime NOT NULL,
  `return_datetime` datetime NOT NULL,
  `pickup_location` varchar(100) NOT NULL,
  `return_location` varchar(100) NOT NULL,
  `hire_days` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','awaiting_verification','approved','rejected','paid','active','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `returned` tinyint(1) NOT NULL DEFAULT 0,
  `special_requests` text DEFAULT NULL,
  `hire_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL UNIQUE,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('deposit','full') NOT NULL DEFAULT 'full',
  `payment_status` enum('pending','completed','refunded') NOT NULL DEFAULT 'completed',
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `receipt_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `inspections`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inspections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `inspector_id` int(11) NOT NULL,
  `return_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `inspection_status` enum('clean','damaged') NOT NULL DEFAULT 'clean',
  `damages_description` text DEFAULT NULL,
  `penalties_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `late_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `inspection_photos_path` varchar(255) DEFAULT NULL,
  `report_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `inspections_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inspections_ibfk_2` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `reviews`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `activity_logs`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Dumping seed data for `users`
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `id_number`, `driving_license`, `role`, `is_admin`, `verification_status`) VALUES
(1, 'admin', '$2y$10$fDw3Ybznb7esiRj7fuEFQu4SSTQypC8cHM7jSjAJKZnycWfkTUe8S', 'Prestige Admin', 'admin@prestigewheels.com', '0711223344', 'ADMIN001', 'DL-ADMIN01', 'super_admin', 1, 'verified'),
(2, 'john_doe', '$2y$10$fDw3Ybznb7esiRj7fuEFQu4SSTQypC8cHM7jSjAJKZnycWfkTUe8S', 'John Doe', 'john@example.com', '0722334455', '12345678', 'DL-12345678', 'customer', 0, 'verified')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- --------------------------------------------------------
-- Dumping seed data for `cars`
-- --------------------------------------------------------
INSERT INTO `cars` (`id`, `registration_number`, `brand`, `model`, `year`, `fuel_type`, `transmission`, `capacity`, `charge_per_day`, `photo`, `status`, `quantity`, `category`, `description`) VALUES
(1, 'KAA 123A', 'Toyota', 'Land Cruiser Prado', 2020, 'Diesel', 'automatic', 7, 12000.00, 'uploads/corollatoyota.jpg', 'available', 3, 'SUV', 'A robust and luxurious SUV built for all Kenyan terrains. Excellent choice for family trips and VIP travel.'),
(2, 'KAB 456B', 'Mercedes-Benz', 'C-Class', 2018, 'Petrol', 'automatic', 5, 15000.00, 'uploads/mercedesbenz.jpg', 'available', 2, 'Luxury', 'Sleek design, quiet cabin, and cutting-edge drive assistant features. Perfect for business meetings.'),
(3, 'KAC 789C', 'BMW', '3 Series', 2019, 'Petrol', 'automatic', 5, 14000.00, 'uploads/bmwseries.jpg', 'available', 2, 'Sports Sedan', 'Ultimate driving machine offering sports-tuned handling, modern infotainment, and executive comfort.'),
(4, 'KAD 012D', 'Audi', 'A4', 2017, 'Petrol', 'automatic', 5, 13000.00, 'uploads/audia4.jpg', 'available', 2, 'Executive', 'High interior quality and sophisticated styling, equipped with Quattro all-wheel drive stability.'),
(5, 'KAE 345E', 'Honda', 'Civic', 2020, 'Petrol', 'automatic', 5, 7000.00, 'uploads/hondacivic.jpg', 'available', 4, 'Compact', 'Highly fuel-efficient and reliable compact sedan, ideal for city driving and daily commuting.')
ON DUPLICATE KEY UPDATE `id`=`id`;
