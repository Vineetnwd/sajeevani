CREATE TABLE `states` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `districts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `state_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`state_id`) REFERENCES `states`(`id`) ON DELETE CASCADE
);

CREATE TABLE `blocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `district_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE CASCADE
);

-- Insert Sample States
INSERT INTO `states` (`id`, `name`) VALUES 
(1, 'Andhra Pradesh'),
(2, 'Maharashtra'),
(3, 'Uttar Pradesh'),
(4, 'Karnataka');

-- Insert Sample Districts
INSERT INTO `districts` (`id`, `state_id`, `name`) VALUES 
(1, 1, 'Visakhapatnam'),
(2, 1, 'Vijayawada'),
(3, 2, 'Pune'),
(4, 2, 'Mumbai Suburban'),
(5, 3, 'Lucknow'),
(6, 3, 'Kanpur'),
(7, 4, 'Bengaluru Urban'),
(8, 4, 'Mysuru');

-- Insert Sample Blocks
INSERT INTO `blocks` (`id`, `district_id`, `name`) VALUES 
(1, 1, 'Bheemunipatnam'),
(2, 1, 'Anandapuram'),
(3, 3, 'Haveli'),
(4, 3, 'Khed'),
(5, 5, 'Malihabad'),
(6, 5, 'Bakshi Ka Talab'),
(7, 7, 'Bengaluru North'),
(8, 7, 'Bengaluru South');
