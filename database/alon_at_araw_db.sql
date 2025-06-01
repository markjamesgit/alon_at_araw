-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2025 at 09:12 AM
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
-- Database: `alon_at_araw_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `addon_id` int(11) NOT NULL,
  `addon_name` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addons`
--

INSERT INTO `addons` (`addon_id`, `addon_name`, `quantity`, `created_at`, `price`) VALUES
(5, 'Whipped Cream', 19, '2025-05-27 14:04:42', 10.00),
(6, 'Extra Espresso Shot', 29, '2025-05-27 14:04:56', 10.00),
(7, 'Chocolate Drizzle', 32, '2025-05-27 14:05:04', 15.00),
(8, 'Caramel Drizzle', 50, '2025-05-27 14:05:13', 10.00),
(9, 'Ice Cream Scoop', 50, '2025-05-27 14:05:23', 20.00),
(10, 'Oat Milk Substitute', 43, '2025-05-27 14:05:33', 15.00),
(11, 'Soy Milk Substitute', 50, '2025-05-27 14:05:43', 10.00),
(12, 'Sugar-Free Syrup', 50, '2025-05-27 14:05:52', 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `unit_price` decimal(10,2) NOT NULL,
  `selected_addons` text DEFAULT NULL,
  `selected_flavors` text DEFAULT NULL,
  `selected_cup_size` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `created_at`) VALUES
(80, 'Hot Beverages', 'Classic hot coffee drinks', '6837326ed996c_6.png', '2025-05-27 12:19:54'),
(81, 'Cold Beverages', 'Iced and chilled coffee options', '6837326944242_5.png', '2025-05-27 12:20:11'),
(82, 'Frappes', 'Blended iced coffee with flavors', '68373260e462f_4.png', '2025-05-27 12:20:27'),
(83, 'Non-Coffee Drinks', 'Chocolate, tea, and other non-coffee drinks', '68373258eb5a7_3.png', '2025-05-27 12:20:43'),
(84, 'Signature Drinks', 'House specials or premium recipes', '6837324f371d0_2.png', '2025-05-27 12:21:08'),
(87, 'Custom Drinks', 'Create your own drink with customizable options', '683875749710d_NO IMAGE AVAILABLE.png', '2025-05-29 14:55:48');

-- --------------------------------------------------------

--
-- Table structure for table `cup_sizes`
--

CREATE TABLE `cup_sizes` (
  `cup_size_id` int(11) NOT NULL,
  `size_name` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cup_sizes`
--

INSERT INTO `cup_sizes` (`cup_size_id`, `size_name`, `quantity`, `created_at`, `price`) VALUES
(4, 'Small', 11, '2025-05-27 14:06:34', 10.00),
(5, 'Medium', 38, '2025-05-27 14:06:39', 15.00),
(6, 'Large', 42, '2025-05-27 14:06:49', 20.00),
(7, 'Extra Large', 27, '2025-05-27 14:06:56', 25.00);

-- --------------------------------------------------------

--
-- Table structure for table `flavors`
--

CREATE TABLE `flavors` (
  `flavor_id` int(11) NOT NULL,
  `flavor_name` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flavors`
--

INSERT INTO `flavors` (`flavor_id`, `flavor_name`, `quantity`, `created_at`, `price`) VALUES
(10, 'Vanilla', 40, '2025-05-27 12:51:35', 10.00),
(11, 'Caramel', 40, '2025-05-27 12:51:44', 10.00),
(12, 'Hazelnut', 50, '2025-05-27 12:51:58', 10.00),
(13, 'Mocha', 38, '2025-05-27 12:52:07', 20.00),
(14, 'Matcha', 38, '2025-05-27 12:52:17', 20.00),
(15, 'Brown Sugar', 41, '2025-05-27 12:52:27', 5.00),
(16, 'Chocolate Syrup', 50, '2025-05-27 12:52:35', 15.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','gcash','bdo') NOT NULL,
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','preparing','ready_for_pickup','completed','cancelled') NOT NULL DEFAULT 'pending',
  `delivery_method` enum('pickup','delivery') NOT NULL DEFAULT 'pickup',
  `delivery_address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_number`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `delivery_method`, `delivery_address`, `contact_number`, `special_instructions`, `created_at`, `updated_at`) VALUES
(1, 11, 'ORD-20250531-4319', 755.00, 'gcash', 'pending', 'pending', 'delivery', 'kkjk', '09924524412', '', '2025-05-31 12:09:42', '2025-05-31 12:09:42'),
(2, 11, 'ORD-20250531-7677', 360.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-05-31 12:17:21', '2025-05-31 12:17:21'),
(6, 11, 'ORD-20250531-8698', 615.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09123456789', '', '2025-05-31 12:23:56', '2025-05-31 12:23:56'),
(7, 11, 'ORD-20250531-7330', 550.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09451824631', '', '2025-05-31 12:25:12', '2025-05-31 12:25:12'),
(8, 11, 'ORD-20250531-7628', 320.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09451824631', '', '2025-05-31 12:26:19', '2025-05-31 12:26:19'),
(9, 11, 'ORD-20250531-0520', 440.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09123456789', '', '2025-05-31 13:28:18', '2025-05-31 13:28:18'),
(10, 11, 'ORD-20250531-3232', 180.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09123456789', '', '2025-05-31 13:48:29', '2025-05-31 13:48:29'),
(11, 11, 'ORD-20250531-2481', 1200.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09123456789', '', '2025-05-31 17:35:26', '2025-05-31 17:35:26'),
(12, 11, 'ORD-20250601-0339', 485.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 03:33:04', '2025-06-01 03:33:04'),
(13, 11, 'ORD-20250601-7314', 155.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 03:38:06', '2025-06-01 03:38:06'),
(14, 11, 'ORD-20250601-0980', 160.00, 'cash', 'pending', 'pending', 'delivery', 'dgdfgdfg', '09924524412', '', '2025-06-01 03:39:44', '2025-06-01 03:39:44'),
(15, 11, 'ORD-20250601-0981', 185.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 03:42:00', '2025-06-01 03:42:00'),
(21, 11, 'ORD-20250601-8050', 120.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:06:33', '2025-06-01 04:06:33'),
(22, 11, 'ORD-20250601-8196', 185.00, 'gcash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:07:46', '2025-06-01 04:07:46'),
(23, 11, 'ORD-20250601-6439', 150.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:08:09', '2025-06-01 04:08:09'),
(24, 11, 'ORD-20250601-0403', 220.00, 'cash', 'pending', 'pending', 'delivery', 'dsfdgdfgd', '09924524412', '', '2025-06-01 04:09:09', '2025-06-01 04:09:09'),
(25, 11, 'ORD-20250601-5462', 130.00, 'cash', 'pending', 'pending', 'delivery', 'dfsdfsdfds', '09924524412', '', '2025-06-01 04:11:43', '2025-06-01 04:11:43'),
(26, 11, 'ORD-20250601-2107', 175.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:14:17', '2025-06-01 04:14:17'),
(27, 11, 'ORD-20250601-7895', 180.00, 'cash', 'pending', 'pending', 'delivery', 'fdddhfdf', '09924524412', '', '2025-06-01 04:14:34', '2025-06-01 04:14:34'),
(28, 11, 'ORD-20250601-9163', 1350.00, 'gcash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:47:24', '2025-06-01 04:47:24'),
(29, 11, 'ORD-20250601-8417', 200.00, '', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:48:16', '2025-06-01 04:48:16'),
(30, 11, 'ORD-20250601-6922', 180.00, 'gcash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:49:27', '2025-06-01 04:49:27'),
(31, 11, 'ORD-20250601-5031', 175.00, '', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:50:01', '2025-06-01 04:50:01'),
(32, 11, 'ORD-20250601-2893', 210.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:52:08', '2025-06-01 04:52:08'),
(33, 11, 'ORD-20250601-8894', 180.00, 'bdo', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:54:47', '2025-06-01 04:54:47'),
(34, 11, 'ORD-20250601-8583', 190.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 04:55:15', '2025-06-01 04:55:15'),
(35, 11, 'ORD-20250601-0672', 140.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 06:56:07', '2025-06-01 06:56:07'),
(36, 11, 'ORD-20250601-0330', 175.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 07:00:06', '2025-06-01 07:00:06'),
(37, 11, 'ORD-20250601-2147', 210.00, 'bdo', 'pending', 'pending', 'delivery', 'sadafdss', '09924524412', '', '2025-06-01 07:02:40', '2025-06-01 07:02:40'),
(38, 11, 'ORD-20250601-4877', 185.00, 'gcash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 07:03:19', '2025-06-01 07:03:19'),
(39, 11, 'ORD-20250601-2926', 200.00, 'cash', 'pending', 'pending', 'pickup', NULL, '09924524412', '', '2025-06-01 07:05:26', '2025-06-01 07:05:26'),
(40, 11, 'ORD-20250601-1883', 130.00, 'cash', 'pending', 'pending', 'delivery', 'dsgfdg', '09924524412', '', '2025-06-01 07:05:57', '2025-06-01 07:05:57');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `selected_cup_size` int(11) DEFAULT NULL,
  `selected_addons` text DEFAULT NULL,
  `selected_flavors` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `selected_cup_size`, `selected_addons`, `selected_flavors`, `created_at`) VALUES
(1, 1, 75, 1, 205.00, 205.00, 7, '{\"5\":\"1\",\"6\":\"1\"}', '{\"15\":\"2\"}', '2025-05-31 12:09:42'),
(2, 1, 70, 2, 185.00, 370.00, 7, '{\"5\":\"1\"}', '{\"11\":\"3\"}', '2025-05-31 12:09:42'),
(3, 1, 74, 1, 180.00, 180.00, 5, '{\"10\":\"1\"}', '{\"14\":\"1\"}', '2025-05-31 12:09:42'),
(4, 2, 73, 1, 215.00, 215.00, 5, '{\"7\":\"2\"}', '{\"13\":\"2\"}', '2025-05-31 12:17:21'),
(5, 2, 71, 1, 145.00, 145.00, 7, '{\"6\":\"2\"}', '[]', '2025-05-31 12:17:21'),
(6, 7, 73, 2, 275.00, 550.00, 6, '{\"7\":\"3\"}', '{\"13\":\"4\"}', '2025-05-31 12:25:12'),
(7, 8, 74, 1, 320.00, 320.00, 7, '{\"10\":\"3\"}', '{\"14\":\"6\"}', '2025-05-31 12:26:19'),
(8, 9, 72, 1, 190.00, 190.00, 5, '{\"5\":\"1\",\"7\":\"1\"}', '{\"10\":\"1\"}', '2025-05-31 13:28:18'),
(9, 9, 72, 1, 250.00, 250.00, 7, '{\"5\":\"1\",\"7\":\"3\"}', '{\"10\":\"3\"}', '2025-05-31 13:28:18'),
(10, 10, 70, 1, 180.00, 180.00, 4, '{\"5\":\"2\"}', '{\"11\":\"3\"}', '2025-05-31 13:48:29'),
(11, 11, 75, 6, 200.00, 1200.00, 7, '{\"5\":\"1\",\"6\":\"1\"}', '{\"15\":\"1\"}', '2025-05-31 17:35:26'),
(12, 12, 70, 1, 160.00, 160.00, 6, '{\"5\":\"1\"}', '{\"11\":\"1\"}', '2025-06-01 03:33:04'),
(13, 12, 71, 1, 125.00, 125.00, 5, '{\"6\":\"1\"}', '[]', '2025-06-01 03:33:04'),
(14, 12, 75, 1, 200.00, 200.00, 7, '{\"5\":\"1\",\"6\":\"1\"}', '{\"15\":\"1\"}', '2025-06-01 03:33:04'),
(15, 13, 70, 1, 165.00, 165.00, 7, '{\"5\":\"1\"}', '{\"11\":\"1\"}', '2025-06-01 03:38:06'),
(16, 13, 70, 1, 155.00, 155.00, 5, '{\"5\":\"1\"}', '{\"11\":\"1\"}', '2025-06-01 03:38:06'),
(17, 14, 70, 1, 155.00, 155.00, 5, '{\"5\":\"1\"}', '{\"11\":\"1\"}', '2025-06-01 03:39:44'),
(18, 14, 70, 1, 160.00, 160.00, 6, '{\"5\":\"1\"}', '{\"11\":\"1\"}', '2025-06-01 03:39:44'),
(19, 15, 75, 1, 195.00, 195.00, 6, '{\"5\":\"1\",\"6\":\"1\"}', '{\"15\":\"1\"}', '2025-06-01 03:42:00'),
(20, 15, 75, 1, 185.00, 185.00, 4, '{\"5\":\"1\",\"6\":\"1\"}', '{\"15\":\"1\"}', '2025-06-01 03:42:00'),
(21, 21, 71, 1, 125.00, 125.00, 5, '{\"6\":\"1\"}', '[]', '2025-06-01 04:06:33'),
(22, 21, 71, 1, 120.00, 120.00, 4, '{\"6\":\"1\"}', '[]', '2025-06-01 04:06:33'),
(23, 21, 70, 1, 160.00, 160.00, 6, '{\"5\":\"1\"}', '{\"11\":\"1\"}', '2025-06-01 04:06:33'),
(24, 21, 71, 1, 120.00, 120.00, 4, '{\"6\":\"1\"}', '[]', '2025-06-01 04:06:33'),
(25, 22, 72, 1, 185.00, 185.00, 4, '{\"5\":\"1\",\"7\":\"1\"}', '{\"10\":\"1\"}', '2025-06-01 04:07:46'),
(26, 23, 70, 1, 155.00, 155.00, 5, '{\"5\":\"1\"}', '{\"11\":\"1\"}', '2025-06-01 04:08:09'),
(27, 24, 72, 1, 220.00, 220.00, 7, '{\"5\":\"1\",\"7\":\"1\"}', '{\"10\":\"3\"}', '2025-06-01 04:09:09'),
(28, 25, 71, 1, 125.00, 125.00, 5, '{\"6\":\"1\"}', '[]', '2025-06-01 04:11:43'),
(29, 25, 71, 1, 130.00, 130.00, 6, '{\"6\":\"1\"}', '[]', '2025-06-01 04:11:43'),
(30, 26, 74, 1, 175.00, 175.00, 4, '{\"10\":\"1\"}', '{\"14\":\"1\"}', '2025-06-01 04:14:17'),
(31, 27, 74, 1, 180.00, 180.00, 5, '{\"10\":\"1\"}', '{\"14\":\"1\"}', '2025-06-01 04:14:34'),
(32, 29, 74, 1, 200.00, 200.00, 5, '{\"10\":\"1\"}', '{\"14\":\"2\"}', '2025-06-01 04:48:16'),
(33, 30, 73, 1, 180.00, 180.00, 5, '{\"7\":\"1\"}', '{\"13\":\"1\"}', '2025-06-01 04:49:27'),
(34, 31, 73, 1, 175.00, 175.00, 4, '{\"7\":\"1\"}', '{\"13\":\"1\"}', '2025-06-01 04:50:01'),
(35, 35, 71, 1, 140.00, 140.00, 6, '{\"6\":\"2\"}', '[]', '2025-06-01 06:56:07'),
(36, 37, 72, 1, 210.00, 210.00, 7, '{\"5\":\"1\",\"7\":\"1\"}', '{\"10\":\"2\"}', '2025-06-01 07:02:40'),
(37, 38, 72, 1, 185.00, 185.00, 4, '{\"5\":\"2\",\"7\":\"1\"}', '[]', '2025-06-01 07:03:19'),
(38, 39, 74, 1, 200.00, 200.00, 5, '{\"10\":\"1\"}', '{\"14\":\"2\"}', '2025-06-01 07:05:26'),
(39, 40, 71, 1, 130.00, 130.00, 4, '{\"6\":\"2\"}', '[]', '2025-06-01 07:05:57');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_best_seller` tinyint(1) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `description`, `price`, `product_image`, `category_id`, `is_best_seller`, `is_available`, `created_at`, `updated_at`) VALUES
(70, 'Iced Caramel Macchiato', 'Espresso, milk & caramel syrup over ice', 120.00, '683722db5306c_6.png', 81, 1, 1, '2025-05-27 12:48:33', '2025-05-28 14:51:07'),
(71, 'Hot Americano', 'Simple and bold black coffee', 100.00, '683722cf713f8_3.png', 80, 0, 1, '2025-05-27 12:49:02', '2025-05-31 10:54:40'),
(72, 'Vanilla Frappe', 'Blended ice with vanilla and cream', 140.00, '683722c62c265_1.png', 82, 1, 1, '2025-05-27 12:49:32', '2025-05-31 11:15:14'),
(73, 'Mocha Latte', 'Espresso with chocolate and steamed milk', 130.00, '683722edd22e2_2.png', 80, 0, 1, '2025-05-27 12:49:58', '2025-05-28 14:51:25'),
(74, 'Matcha Green Tea Latte', 'Green tea matcha with milk', 130.00, '683722ad151af_4.png', 83, 1, 1, '2025-05-27 12:50:51', '2025-05-31 10:54:38'),
(75, 'Signature Brown Sugar Latte', 'Sweet brown sugar with creamy espresso', 150.00, '683722f873725_5.png', 84, 1, 1, '2025-05-27 12:51:12', '2025-05-31 11:15:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_components`
--

CREATE TABLE `product_components` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `component_type` enum('cup_sizes','flavors','addons') DEFAULT NULL,
  `component_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_components`
--

INSERT INTO `product_components` (`id`, `product_id`, `component_type`, `component_id`, `quantity`, `active`) VALUES
(7, 70, 'cup_sizes', 4, 10, 1),
(8, 70, 'cup_sizes', 5, 7, 1),
(9, 70, 'cup_sizes', 6, 7, 1),
(10, 70, 'cup_sizes', 7, 9, 1),
(11, 70, 'flavors', 11, 40, 1),
(12, 70, 'addons', 5, 1, 1),
(13, 71, 'cup_sizes', 4, 7, 1),
(14, 71, 'cup_sizes', 5, 7, 1),
(15, 71, 'cup_sizes', 6, 8, 1),
(16, 71, 'cup_sizes', 7, 10, 1),
(17, 71, 'addons', 6, 0, 1),
(18, 72, 'cup_sizes', 4, 8, 1),
(19, 72, 'cup_sizes', 5, 9, 1),
(20, 72, 'cup_sizes', 6, 10, 1),
(21, 72, 'cup_sizes', 7, 7, 1),
(22, 72, 'flavors', 10, 0, 1),
(23, 72, 'addons', 5, 3, 1),
(24, 72, 'addons', 7, 2, 1),
(25, 73, 'cup_sizes', 4, 9, 1),
(26, 73, 'cup_sizes', 5, 9, 1),
(27, 73, 'cup_sizes', 6, 8, 1),
(28, 73, 'cup_sizes', 7, 10, 1),
(29, 73, 'flavors', 13, 38, 1),
(30, 73, 'addons', 7, 8, 1),
(31, 74, 'cup_sizes', 4, 9, 1),
(32, 74, 'cup_sizes', 5, 7, 1),
(33, 74, 'cup_sizes', 6, 10, 1),
(34, 74, 'cup_sizes', 7, 9, 1),
(35, 74, 'flavors', 14, 38, 1),
(36, 74, 'addons', 10, 3, 1),
(37, 75, 'cup_sizes', 4, 9, 1),
(38, 75, 'cup_sizes', 5, 10, 1),
(39, 75, 'cup_sizes', 6, 9, 1),
(40, 75, 'cup_sizes', 7, 3, 1),
(41, 75, 'flavors', 15, 1, 1),
(42, 75, 'addons', 5, 1, 1),
(43, 75, 'addons', 6, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `account_type` enum('admin','customer') DEFAULT 'customer',
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_code` varchar(6) DEFAULT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `is_blocked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `account_type`, `email_verified`, `verification_code`, `reset_code`, `created_at`, `full_name`, `address`, `age`, `birthday`, `profile_image`, `failed_attempts`, `is_blocked`) VALUES
(11, 'markjames', 'markjames.villagonzalo06@gmail.com', '$2y$10$NuA2V3F5X.dy1GWMtunIZemYHWHbr1GPGXyuvaHqaitt3t9ucRnBm', 'customer', 1, NULL, NULL, '2025-05-22 11:20:53', NULL, NULL, NULL, NULL, NULL, 0, 0),
(12, 'Admin', 'alonatarawcoffeeshop@gmail.com', '$2y$10$e9flSnYv1nYecIDTNf9zquZspUT12hdKweta23sjBBoDGKJ48jDDm', 'admin', 1, NULL, NULL, '2025-05-22 11:44:38', NULL, NULL, NULL, NULL, NULL, 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`addon_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `selected_cup_size` (`selected_cup_size`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cup_sizes`
--
ALTER TABLE `cup_sizes`
  ADD PRIMARY KEY (`cup_size_id`);

--
-- Indexes for table `flavors`
--
ALTER TABLE `flavors`
  ADD PRIMARY KEY (`flavor_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `selected_cup_size` (`selected_cup_size`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_components`
--
ALTER TABLE `product_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

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
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `addon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=337;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `cup_sizes`
--
ALTER TABLE `cup_sizes`
  MODIFY `cup_size_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `flavors`
--
ALTER TABLE `flavors`
  MODIFY `flavor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `product_components`
--
ALTER TABLE `product_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`selected_cup_size`) REFERENCES `cup_sizes` (`cup_size_id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`selected_cup_size`) REFERENCES `cup_sizes` (`cup_size_id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_components`
--
ALTER TABLE `product_components`
  ADD CONSTRAINT `product_components_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
