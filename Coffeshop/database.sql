-- Database: db_coffeeshop_new
-- Import this file in phpMyAdmin (http://localhost/phpmyadmin)

CREATE DATABASE IF NOT EXISTS `db_coffeeshop_new` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_coffeeshop_new`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer','staff') NOT NULL DEFAULT 'customer',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `product`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `url_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `orders`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `order_items`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Default Users (Passwords: admin123 and customer123)
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$73Ueq5w71ZYFdV7OlKtpqOS63QTtHYJ8YblP/vcrI3WS7w9qCnrtu', 'admin'),
(2, 'customer', '$2y$10$g4.YMra9ykcS6rVJtGdl2edCnI8R7.2wNvEM6Vt.rs2IEEpTQZtxW', 'customer')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- --------------------------------------------------------
-- Sample Coffee Products
-- --------------------------------------------------------
INSERT INTO `product` (`id`, `name`, `price`, `url_image`) VALUES
(1, 'Espresso', 20000.00, 'https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?w=300'),
(2, 'Cappuccino', 28000.00, 'https://images.unsplash.com/photo-1534778101976-62847782c213?w=300'),
(3, 'Caffe Latte', 30000.00, 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?w=300'),
(4, 'Caramel Macchiato', 35000.00, 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?w=300')
ON DUPLICATE KEY UPDATE `name`=`name`;
