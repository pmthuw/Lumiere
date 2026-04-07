-- Complete Database Setup for Perfume Store
-- Run this file once to set up the entire database
-- Use this in phpMyAdmin or any MySQL client

-- Create database if it doesn't exist

-- Drop tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `products`;

-- Create products table
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(60) NOT NULL,
  `price` INT UNSIGNED NOT NULL,
  `brand` VARCHAR(120) NOT NULL,
  `badge` VARCHAR(60) DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `concentration` VARCHAR(80) DEFAULT NULL,
  `size` VARCHAR(60) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `initial_stock` INT UNSIGNED NOT NULL DEFAULT 30,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table (for customers)
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `district` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active', 'locked', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin_users table (separate from regular users for security)
CREATE TABLE `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'super_admin') DEFAULT 'admin',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create orders table
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_email` VARCHAR(255) NOT NULL,
  `customer_phone` VARCHAR(20) DEFAULT NULL,
  `shipping_address` TEXT NOT NULL,
  `ward` VARCHAR(100) DEFAULT NULL,
  `district` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `total_amount` INT UNSIGNED NOT NULL,
  `status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
  `payment_method` VARCHAR(50) DEFAULT 'cod',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_order_number` (`order_number`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_ward` (`ward`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create order_items table
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `product_brand` VARCHAR(120) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `unit_price` INT UNSIGNED NOT NULL,
  `total_price` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert products data
INSERT INTO `products` (`id`, `name`, `category`, `price`, `brand`, `badge`, `notes`, `concentration`, `size`, `image`, `initial_stock`) VALUES
  (1, 'Chanel No.5', 'Nữ', 7200000, 'Chanel', 'Bestseller', 'Hoa cỏ aldehyde', 'Eau de Parfum', '100ml', 'images/hinh1.jpg', 30),
  (2, 'Dior Sauvage', 'Nam', 8800000, 'Dior', 'Hot', 'Fougère Woody', 'Eau de Toilette', '100ml', 'images/hinh3.jpg', 30),
  (3, 'Tom Ford Black Orchid', 'Unisex', 6600000, 'Tom Ford', '', 'Oriental Floral', 'Eau de Parfum', '50ml', 'images/hinh4.jpg', 30),
  (4, 'YSL Black Opium', 'Nữ', 8400000, 'YSL', 'New', 'Oriental Floral', 'Eau de Parfum', '90ml', 'images/hinh5.jpg', 30),
  (5, 'Creed Aventus', 'Nam', 14500000, 'Creed', 'Luxury', 'Fruity Chypre', 'Eau de Parfum', '100ml', 'images/hinh6.jpg', 30),
  (6, 'Jo Malone Peony', 'Unisex', 8200000, 'Jo Malone', '', 'Floral Fruity', 'Cologne', '100ml', 'images/hinh7.jpg', 30),
  (7, 'Versace Eros', 'Nam', 10800000, 'Versace', '', 'Oriental Fougère', 'Eau de Toilette', '100ml', 'images/hinh8.jpg', 30),
  (8, 'Gucci Bloom', 'Nữ', 9800000, 'Gucci', '', 'Floral', 'Eau de Parfum', '100ml', 'images/hinh9.jpg', 30),
  (9, 'Maison Margiela Replica', 'Unisex', 8900000, 'Maison Margiela', 'Limited', 'Woody Floral Musk', 'Eau de Toilette', '100ml', 'images/hinh10.jpg', 30),
  (10, 'Hermès Terre', 'Nam', 9200000, 'Hermès', '', 'Woody Citrus', 'Eau de Toilette', '75ml', 'images/hinh11.jpg', 30),
  (11, 'Lancôme La Vie Est Belle', 'Nữ', 11100000, 'Lancôme', '', 'Oriental Floral', 'Eau de Parfum', '75ml', 'images/hinh12.jpg', 30),
  (12, 'Kilian Angel Share', 'Unisex', 9800000, 'Kilian', 'Limited', 'Oriental Woody', 'Eau de Parfum', '50ml', 'images/hinh13.jpg', 30),
  (13, 'Million Elixir', 'Limited', 9800000, 'Milion', 'Limited', 'Amber Oud', 'Extrait de Parfum', '50ml', 'images/hinh14.jpg', 30),
  (14, 'Attrape-Rêves', 'Limited', 13350000, 'Attrape', 'Limited', 'Floral Fruity Gourmand', 'Eau de Parfum', '100ml', 'images/hinh15.jpg', 30);

-- Insert default admin user (password: admin123)
-- Password hash for 'admin123' using bcrypt
INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `full_name`, `role`) VALUES
  ('admin', 'admin@lumier.com', '$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj8nJ9VH6lK', 'Administrator', 'super_admin'),
  ('quanli1', 'quanli1@lumier.com', '$2y$10$Jm3mN4B7M7uVbQYw4QwL1uQnXvJQ5Xv8D8Gk7bP0nZC4v5K1bG1bS', 'Quản lý 1', 'admin');

-- Insert sample customer users (optional - for testing)
-- Password for all sample users: password123
INSERT INTO `users` (`full_name`, `username`, `email`, `password_hash`, `role`, `phone`, `address`, `district`, `city`) VALUES
  ('Khách hàng 1', 'khachhang1', 'khachhang1@example.com', '$2y$10$a9OfGT2/S5esDwrImHwjyuyi0ctBHu78IFQRKkue4D15QHmcAupSe', 'customer', '0912345678', '789 Đường KHA1', 'Quận 3', 'TP.HCM');


-- Create indexes for better performance
CREATE INDEX `idx_products_category` ON `products` (`category`);
CREATE INDEX `idx_products_brand` ON `products` (`brand`);
CREATE INDEX `idx_products_price` ON `products` (`price`);
CREATE INDEX `idx_orders_customer_email` ON `orders` (`customer_email`);
CREATE INDEX `idx_order_items_total` ON `order_items` (`total_price`);

-- Show completion message
SELECT 'Database setup completed successfully!' as status;