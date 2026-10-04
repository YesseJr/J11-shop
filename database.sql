-- J11 Online Shopping - Complete Database Schema
-- Compatible with the existing Admin dashboard queries

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `j11_shopping` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `j11_shopping`;

-- --------------------------------------------------------
-- Admin users (for backend login)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tbl_user`;
CREATE TABLE `tbl_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'Admin',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Multi-level Categories
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tbl_top_category`;
CREATE TABLE `tbl_top_category` (
  `tcat_id` int(11) NOT NULL AUTO_INCREMENT,
  `tcat_name` varchar(255) NOT NULL,
  `show_on_menu` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`tcat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tbl_mid_category`;
CREATE TABLE `tbl_mid_category` (
  `mcat_id` int(11) NOT NULL AUTO_INCREMENT,
  `mcat_name` varchar(255) NOT NULL,
  `tcat_id` int(11) NOT NULL,
  PRIMARY KEY (`mcat_id`),
  KEY `tcat_id` (`tcat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tbl_end_category`;
CREATE TABLE `tbl_end_category` (
  `ecat_id` int(11) NOT NULL AUTO_INCREMENT,
  `ecat_name` varchar(255) NOT NULL,
  `mcat_id` int(11) NOT NULL,
  PRIMARY KEY (`ecat_id`),
  KEY `mcat_id` (`mcat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Products
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tbl_product`;
CREATE TABLE `tbl_product` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_name` varchar(255) NOT NULL,
  `p_old_price` decimal(10,2) DEFAULT NULL,
  `p_current_price` decimal(10,2) NOT NULL,
  `p_qty` int(11) NOT NULL DEFAULT 0,
  `p_featured_photo` varchar(255) DEFAULT NULL,
  `p_description` text,
  `p_short_description` text,
  `p_feature` text,
  `p_condition` text,
  `p_return_policy` text,
  `p_total_view` int(11) NOT NULL DEFAULT 0,
  `p_is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `p_is_active` tinyint(1) NOT NULL DEFAULT 1,
  `ecat_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`p_id`),
  KEY `ecat_id` (`ecat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Product photos (gallery)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tbl_product_photo`;
CREATE TABLE `tbl_product_photo` (
  `pp_id` int(11) NOT NULL AUTO_INCREMENT,
  `photo` varchar(255) NOT NULL,
  `p_id` int(11) NOT NULL,
  PRIMARY KEY (`pp_id`),
  KEY `p_id` (`p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Customers
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tbl_customer`;
CREATE TABLE `tbl_customer` (
  `cust_id` int(11) NOT NULL AUTO_INCREMENT,
  `cust_name` varchar(100) NOT NULL,
  `cust_cname` varchar(100) DEFAULT '',
  `cust_email` varchar(100) NOT NULL UNIQUE,
  `cust_phone` varchar(50) DEFAULT '',
  `cust_country` int(11) DEFAULT 0,
  `cust_address` text,
  `cust_city` varchar(100) DEFAULT '',
  `cust_state` varchar(100) DEFAULT '',
  `cust_zip` varchar(30) DEFAULT '',
  `cust_b_name` varchar(100) DEFAULT '',
  `cust_b_cname` varchar(100) DEFAULT '',
  `cust_b_phone` varchar(50) DEFAULT '',
  `cust_b_country` int(11) DEFAULT 0,
  `cust_b_address` text,
  `cust_b_city` varchar(100) DEFAULT '',
  `cust_b_state` varchar(100) DEFAULT '',
  `cust_b_zip` varchar(30) DEFAULT '',
  `cust_s_name` varchar(100) DEFAULT '',
  `cust_s_cname` varchar(100) DEFAULT '',
  `cust_s_phone` varchar(50) DEFAULT '',
  `cust_s_country` int(11) DEFAULT 0,
  `cust_s_address` text,
  `cust_s_city` varchar(100) DEFAULT '',
  `cust_s_state` varchar(100) DEFAULT '',
  `cust_s_zip` varchar(30) DEFAULT '',
  `cust_password` varchar(255) NOT NULL,
  `cust_token` varchar(255) DEFAULT '',
  `cust_datetime` varchar(100) DEFAULT '',
  `cust_timestamp` varchar(100) DEFAULT '',
  `cust_status` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`cust_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Orders / Payments
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tbl_payment`;
CREATE TABLE `tbl_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `payment_date` varchar(50) NOT NULL,
  `txnid` varchar(255) DEFAULT '',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `card_number` varchar(50) DEFAULT '',
  `card_cvv` varchar(10) DEFAULT '',
  `card_month` varchar(10) DEFAULT '',
  `card_year` varchar(10) DEFAULT '',
  `bank_transaction_info` text,
  `payment_method` varchar(50) DEFAULT 'PayPal',
  `payment_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `shipping_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `payment_id` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tbl_order`;
CREATE TABLE `tbl_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `size` varchar(50) DEFAULT '',
  `color` varchar(50) DEFAULT '',
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `payment_id` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Shipping, subscribers, settings
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tbl_shipping_cost`;
CREATE TABLE `tbl_shipping_cost` (
  `shipping_cost_id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`shipping_cost_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tbl_subscriber`;
CREATE TABLE `tbl_subscriber` (
  `subs_id` int(11) NOT NULL AUTO_INCREMENT,
  `subs_email` varchar(100) NOT NULL UNIQUE,
  `subs_date` varchar(100) DEFAULT '',
  `subs_date_time` varchar(100) DEFAULT '',
  `subs_hash` varchar(255) DEFAULT '',
  `subs_active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`subs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tbl_settings`;
CREATE TABLE `tbl_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `footer_about` text,
  `footer_copyright` text,
  `contact_address` text,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_map_iframe` text,
  `meta_title_home` varchar(255) DEFAULT NULL,
  `meta_keyword_home` text,
  `meta_description_home` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tbl_color`;
CREATE TABLE `tbl_color` (
  `color_id` int(11) NOT NULL AUTO_INCREMENT,
  `color_name` varchar(100) NOT NULL,
  PRIMARY KEY (`color_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tbl_size`;
CREATE TABLE `tbl_size` (
  `size_id` int(11) NOT NULL AUTO_INCREMENT,
  `size_name` varchar(50) NOT NULL,
  PRIMARY KEY (`size_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tbl_product_size`;
CREATE TABLE `tbl_product_size` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `size_id` int(11) NOT NULL,
  `p_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tbl_product_color`;
CREATE TABLE `tbl_product_color` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `color_id` int(11) NOT NULL,
  `p_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Sample data
-- --------------------------------------------------------

INSERT INTO `tbl_top_category` (`tcat_id`, `tcat_name`, `show_on_menu`) VALUES
(1, 'Men', 1),
(2, 'Women', 1),
(3, 'Kids', 1),
(4, 'Electronics', 1);

INSERT INTO `tbl_mid_category` (`mcat_id`, `mcat_name`, `tcat_id`) VALUES
(1, 'Clothing', 1),
(2, 'Shoes', 1),
(3, 'Accessories', 1),
(4, 'Clothing', 2),
(5, 'Shoes', 2),
(6, 'Bags', 2),
(7, 'Boys', 3),
(8, 'Girls', 3),
(9, 'Mobile Phones', 4),
(10, 'Laptops', 4);

INSERT INTO `tbl_end_category` (`ecat_id`, `ecat_name`, `mcat_id`) VALUES
(1, 'T-Shirts', 1),
(2, 'Jeans', 1),
(3, 'Sneakers', 2),
(4, 'Formal Shoes', 2),
(5, 'Watches', 3),
(6, 'Dresses', 4),
(7, 'Tops', 4),
(8, 'Heels', 5),
(9, 'Handbags', 6),
(10, 'Boys T-Shirts', 7),
(11, 'Girls Dresses', 8),
(12, 'Smartphones', 9),
(13, 'Gaming Laptops', 10);

INSERT INTO `tbl_product` (`p_id`, `p_name`, `p_old_price`, `p_current_price`, `p_qty`, `p_featured_photo`, `p_description`, `p_short_description`, `p_is_featured`, `p_is_active`, `ecat_id`) VALUES
(1, 'Classic Cotton T-Shirt', 25.00, 18.99, 50, 'product1.jpg', 'Premium quality cotton t-shirt. Soft, comfortable and durable. Perfect for everyday wear.', 'Soft cotton everyday tee', 1, 1, 1),
(2, 'Slim Fit Jeans', 60.00, 45.00, 30, 'product2.jpg', 'Modern slim fit jeans with stretch denim. Comfortable all-day wear.', 'Stretch slim fit denim', 1, 1, 2),
(3, 'Running Sneakers', 90.00, 69.99, 40, 'product3.jpg', 'Lightweight running sneakers with excellent cushioning and grip.', 'Lightweight performance sneakers', 1, 1, 3),
(4, 'Leather Formal Shoes', 120.00, 89.99, 20, 'product4.jpg', 'Genuine leather formal shoes. Perfect for office and special occasions.', 'Genuine leather formal', 0, 1, 4),
(5, 'Smart Watch Pro', 199.00, 149.00, 25, 'product5.jpg', 'Feature-rich smartwatch with heart rate monitor, GPS and long battery life.', 'GPS + Heart rate smartwatch', 1, 1, 5),
(6, 'Summer Floral Dress', 55.00, 39.99, 35, 'product6.jpg', 'Beautiful floral print summer dress. Light and breezy fabric.', 'Floral summer dress', 1, 1, 6),
(7, 'Casual Blouse', 35.00, 24.99, 45, 'product7.jpg', 'Elegant casual blouse suitable for work or weekend.', 'Elegant casual blouse', 0, 1, 7),
(8, 'High Heel Sandals', 75.00, 54.99, 28, 'product8.jpg', 'Stylish high heel sandals. Comfortable insole.', 'Stylish high heels', 0, 1, 8),
(9, 'Leather Handbag', 110.00, 79.99, 15, 'product9.jpg', 'Premium leather handbag with multiple compartments.', 'Premium leather handbag', 1, 1, 9),
(10, 'Kids Graphic Tee', 18.00, 12.99, 60, 'product10.jpg', 'Fun graphic t-shirt for boys. 100% cotton.', 'Fun kids graphic tee', 0, 1, 10),
(11, 'Princess Party Dress', 40.00, 29.99, 22, 'product11.jpg', 'Adorable party dress for little girls.', 'Cute party dress', 1, 1, 11),
(12, 'Flagship Smartphone X', 899.00, 749.00, 18, 'product12.jpg', 'Latest flagship smartphone with 108MP camera, 5G and AMOLED display.', '5G Flagship phone', 1, 1, 12),
(13, 'Gaming Laptop Ultra', 1499.00, 1299.00, 10, 'product13.jpg', 'High-performance gaming laptop with RTX graphics and high refresh rate display.', 'RTX Gaming laptop', 1, 1, 13);

INSERT INTO `tbl_color` (`color_name`) VALUES
('Red'),('Black'),('Blue'),('White'),('Green'),('Yellow'),('Gray'),('Pink'),('Brown'),('Navy');

INSERT INTO `tbl_size` (`size_name`) VALUES
('XS'),('S'),('M'),('L'),('XL'),('XXL'),('28'),('30'),('32'),('34'),('36'),('38'),('40'),('42');

INSERT INTO `tbl_settings` (`id`, `logo`, `footer_about`, `footer_copyright`, `contact_email`, `contact_phone`, `meta_title_home`) VALUES
(1, 'logo.png', 'J11 Online Shopping is your one-stop shop for fashion, electronics and more. Quality products at affordable prices.', '© 2026 J11 Online Shopping. All Rights Reserved.', 'support@j11shop.com', '+1 234 567 8900', 'J11 Online Shopping - Best Deals Online');

INSERT INTO `tbl_shipping_cost` (`country_id`, `amount`) VALUES
(230, 10.00),  -- USA
(229, 15.00),  -- UK
(14, 12.00);   -- Australia

-- Note: Admin user is created via setup.php (password will be hashed)

-- Hero / site images (added for image management)
ALTER TABLE tbl_settings 
  ADD COLUMN IF NOT EXISTS hero_image VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS hero_title VARCHAR(255) DEFAULT 'Shop smarter. Live better.',
  ADD COLUMN IF NOT EXISTS hero_subtitle TEXT;
