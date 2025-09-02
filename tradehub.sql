-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2025 at 05:08 AM
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
-- Database: `tradehub`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `created_at`) VALUES
(1, 'Music & Instruments', 'music', 'Musical instruments, equipment, and lessons', 'music', '2025-07-22 13:16:07'),
(2, 'Technology', 'technology', 'Computers, software, and tech services', 'laptop', '2025-07-22 13:16:07'),
(3, 'Health & Beauty', 'health-beauty', 'Health products, beauty services, and wellness', 'heart', '2025-07-22 13:16:07'),
(4, 'Education', 'education', 'Tutoring, courses, and educational materials', 'graduation-cap', '2025-07-22 13:16:07'),
(5, 'Design', 'design', 'Graphic design, web design, and creative services', 'paint-brush', '2025-07-22 13:16:07'),
(6, 'Electronics', 'electronics', 'Electronic devices and repair services', 'mobile-alt', '2025-07-22 13:16:07'),
(7, 'Home & Garden', 'home-garden', 'Home improvement and gardening services', 'home', '2025-07-22 13:16:07'),
(8, 'Sports & Fitness', 'sports-fitness', 'Sports equipment and fitness services', 'dumbbell', '2025-07-22 13:16:07'),
(9, 'Arts & Crafts', 'arts-crafts', 'Art supplies and handmade items', 'palette', '2025-07-22 13:16:07'),
(10, 'Automotive', 'automotive', 'Car services and automotive parts', 'car', '2025-07-22 13:16:07');

-- --------------------------------------------------------

--
-- Table structure for table `credits`
--

CREATE TABLE `credits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `type` enum('earned','spent','bonus') NOT NULL,
  `description` varchar(255) NOT NULL,
  `trade_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `credits`
--

INSERT INTO `credits` (`id`, `user_id`, `amount`, `type`, `description`, `trade_id`, `created_at`) VALUES
(1, 1, 50, 'bonus', 'Welcome bonus for new users', NULL, '2025-07-15 13:16:07'),
(2, 1, 25, 'earned', 'Completed trade bonus', NULL, '2025-07-17 13:16:07'),
(3, 1, 10, 'earned', 'Profile completion bonus', NULL, '2025-07-19 13:16:07'),
(4, 4, 50, 'bonus', 'Welcome bonus for new users', NULL, '2025-07-17 12:19:30'),
(5, 4, 25, 'earned', 'Completed trade bonus', 2, '2025-07-23 12:19:30'),
(6, 5, 50, 'bonus', 'Welcome bonus for new users', NULL, '2025-07-18 12:19:30'),
(7, 5, 25, 'earned', 'Completed trade bonus', 7, '2025-07-24 12:19:30'),
(8, 5, 10, 'spent', 'Boosted listing visibility', NULL, '2025-07-25 12:19:30'),
(9, 6, 50, 'bonus', 'Welcome bonus for new users', NULL, '2025-07-19 12:19:30'),
(10, 6, 30, 'earned', 'Completed trade bonus', 4, '2025-07-25 12:19:30'),
(11, 7, 50, 'bonus', 'Welcome bonus for new users', NULL, '2025-07-20 12:19:30'),
(12, 7, 15, 'spent', 'Unlocked premium feature', NULL, '2025-07-24 12:19:30'),
(13, 8, 50, 'bonus', 'Welcome bonus for new users', NULL, '2025-07-21 12:19:30'),
(14, 8, 10, 'earned', 'Profile completion bonus', NULL, '2025-07-22 12:19:30'),
(15, 1, 25, 'earned', 'Completed trade bonus', 7, '2025-07-24 12:19:30');

-- --------------------------------------------------------

--
-- Table structure for table `email_confirmations`
--

CREATE TABLE `email_confirmations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_confirmations`
--

INSERT INTO `email_confirmations` (`id`, `user_id`, `token`, `created_at`, `expires_at`) VALUES
(1, 9, 'cf6458d5bacb7c06886e8205fc6dae3428dc3c4532d06b86c5daa47c2e476225', '2025-08-02 03:34:22', '2025-08-02 01:34:22'),
(2, 10, '802aef433f95754a78b121ec97626f08c37e467a84ee1f8e052d167c3224ddbf', '2025-08-02 03:35:06', '2025-08-02 01:35:06'),
(4, 12, '46cc572d2f4c21564874c9ea8c8ad909d23f87e511496930edf7606b267a04fc', '2025-08-18 17:28:00', '2025-08-18 18:28:00'),
(6, 19, '5f2b79e63311567b85ce2fa4d03f4a52d2e7ae1cf973d8095b0571fb724e6520', '2025-08-23 05:35:16', '2025-08-23 06:35:16'),
(7, 20, '77829b9fdebe429e66d551546b40387ddd78287be96fc3dd1968c5c0d7c37fd1', '2025-08-23 18:41:12', '2025-08-23 19:41:12'),
(8, 21, 'c0138cee217ff4edc9c6d8c2d34209bd2c638f64dd9921e348c63a52158d036e', '2025-08-23 18:42:49', '2025-08-23 19:42:49');

-- --------------------------------------------------------

--
-- Table structure for table `listings`
--

CREATE TABLE `listings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` enum('product','service') NOT NULL,
  `category` varchar(100) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `looking_for` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`looking_for`)),
  `status` enum('active','inactive','deleted') DEFAULT 'active',
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`id`, `user_id`, `title`, `description`, `type`, `category`, `image_url`, `looking_for`, `status`, `views`, `created_at`, `updated_at`) VALUES
(1, 1, 'Guitar Lessons', 'Professional guitar lessons for beginners and intermediate players. 10+ years experience.', 'service', 'music', NULL, '[\"Photography services\", \"Web design\", \"Cooking lessons\"]', 'active', 0, '2025-07-17 13:16:07', '2025-07-22 13:16:07'),
(2, 1, 'MacBook Pro 2019', 'Excellent condition MacBook Pro, perfect for students or professionals.', 'product', 'technology', NULL, '[\"Camera equipment\", \"Musical instruments\", \"Design services\"]', 'active', 0, '2025-07-19 13:16:07', '2025-07-22 13:16:07'),
(3, 1, 'Web Development Services', 'Full-stack web development using modern technologies. Portfolio available.', 'service', 'technology', NULL, '[\"Graphic design\", \"Content writing\", \"Marketing services\"]', 'active', 0, '2025-07-21 13:16:07', '2025-07-22 13:16:07'),
(4, 2, 'ASD', 'asd', 'product', 'music', '', '[\"Nothing\"]', 'active', 1, '2025-07-22 13:29:02', '2025-08-17 00:22:29'),
(5, 3, 'asd', 'asd', 'service', 'health-beauty', '', '[\"asd\"]', 'inactive', 0, '2025-07-22 13:32:01', '2025-08-04 11:27:02'),
(6, 4, 'iPhone 12 Pro', 'Like-new iPhone 12 Pro, 256GB, Pacific Blue.', 'product', 'electronics', NULL, '[\"Laptop\", \"Camera\", \"Fitness equipment\"]', 'active', 120, '2025-07-21 12:19:30', '2025-07-27 12:22:38'),
(7, 4, 'Web Design Services', 'Custom website design with responsive layouts.', 'service', 'design', NULL, '[\"Photography\", \"Marketing services\", \"Musical instruments\"]', 'active', 80, '2025-07-22 12:19:30', '2025-07-27 12:19:30'),
(8, 5, 'Acoustic Guitar', 'Yamaha acoustic guitar, great condition.', 'product', 'music', NULL, '[\"Electronics\", \"Fitness classes\", \"Art supplies\"]', 'active', 150, '2025-07-20 12:19:30', '2025-07-27 12:22:36'),
(9, 5, 'Piano Lessons', 'Beginner to advanced piano lessons, 1-hour sessions.', 'service', 'music', NULL, '[\"Tech gadgets\", \"Graphic design\", \"Home improvement\"]', 'active', 61, '2025-07-23 12:19:30', '2025-08-17 00:03:25'),
(10, 6, 'Personal Training Sessions', 'Customized fitness plans and 1:1 training.', 'service', 'sports-fitness', NULL, '[\"Electronics\", \"Art supplies\", \"Music lessons\"]', 'active', 91, '2025-07-21 12:19:30', '2025-08-16 23:56:19'),
(11, 6, 'Yoga Mat', 'High-quality yoga mat, barely used.', 'product', 'sports-fitness', NULL, '[\"Tech services\", \"Books\", \"Home decor\"]', 'active', 70, '2025-07-24 12:19:30', '2025-08-20 13:57:53'),
(12, 7, 'Car Repair Services', 'Expert car repair and maintenance services.', 'service', 'automotive', NULL, '[\"Electronics\", \"Fitness equipment\", \"Music instruments\"]', 'active', 71, '2025-07-22 12:19:30', '2025-08-19 20:51:41'),
(13, 7, 'Tool Set', 'Complete tool set for automotive and home use.', 'product', 'automotive', NULL, '[\"Tech gadgets\", \"Sports equipment\", \"Design services\"]', 'active', 100, '2025-07-23 12:19:30', '2025-07-27 12:22:42'),
(14, 8, 'Handmade Jewelry', 'Unique handmade necklaces and earrings.', 'product', 'arts-crafts', NULL, '[\"Electronics\", \"Fitness services\", \"Music lessons\"]', 'active', 110, '2025-07-22 12:19:30', '2025-07-27 12:22:43'),
(15, 8, 'Logo Design', 'Professional logo design for businesses.', 'service', 'design', NULL, '[\"Tech gadgets\", \"Fitness equipment\", \"Books\"]', 'active', 89, '2025-07-24 12:19:30', '2025-08-20 13:34:59'),
(16, 2, 'Iphone', 'No Desc', 'product', 'electronics', 'uploads/listings/68861dbec4ff7_photo-1726574686436-5ef90358e032.jpeg', '[\"Anything\"]', 'inactive', 0, '2025-07-27 12:38:22', '2025-08-01 18:33:04'),
(17, 2, 'df', 'vbcrth', 'product', 'electronics', '', '[\"g\"]', 'inactive', 0, '2025-08-01 19:18:51', '2025-08-01 19:19:19'),
(18, 3, 'asf', 'adsf', 'service', 'health-beauty', '', '[\"adf\"]', 'inactive', 0, '2025-08-01 19:27:00', '2025-08-04 11:17:24'),
(19, 11, 'qwe', 'sa', 'product', 'technology', '', '[\"asd\"]', 'inactive', 0, '2025-08-01 23:07:22', '2025-08-04 11:17:24'),
(20, 11, 'fg', 'gvbn', 'product', 'health-beauty', '', '[\"Anything\"]', 'inactive', 0, '2025-08-04 11:19:04', '2025-08-04 11:27:02'),
(21, 3, 'dfg', 'fgh', 'product', 'technology', '', '[\"23\"]', 'inactive', 0, '2025-08-04 11:29:11', '2025-08-15 20:48:54'),
(22, 11, 'f', 'gf', 'product', 'technology', '', '[\"Anything\"]', 'inactive', 0, '2025-08-04 11:29:35', '2025-08-15 20:48:54'),
(23, 11, 'fdg', 'fgdf', 'product', 'design', '', '[\"Nothing\"]', 'inactive', 0, '2025-08-04 11:38:23', '2025-08-15 20:48:56'),
(24, 11, 'Last Product', 'Last', 'product', 'music', 'uploads/listings/689f6f031e524_SnowWhiteandtheSevenDwarfsauthorJacobGrimmandWilhelmGrimm-ezgif.com-webp-to-jpg-converter.jpg,uploads/listings/689f6f032969b_Myself-careplan-ezgif.com-webp-to-jpg-converter.jpg', '[\"ABCD\",\"dsa\"]', 'inactive', 0, '2025-08-15 17:31:47', '2025-08-16 17:18:26'),
(25, 3, 'Last product trade', 'NO desc', 'service', 'design', 'uploads/listings/689f9dc46af36_Myself-careplan-ezgif.com-webp-to-jpg-converter.jpg,uploads/listings/689f9dc46b7d5_Self-CareGuideAuthorMindPeace-ezgif.com-webp-to-jpg-converter.jpg', '[\"ASD\"]', 'inactive', 0, '2025-08-15 20:51:16', '2025-08-20 22:10:17'),
(26, 3, 'Airpods', 'No Desc', 'product', 'technology', 'uploads/listings/68a5d381d39a9_images (1).jpeg,uploads/listings/68a5d381de028_images.jpeg', '[\"AirPods\",\"Anything else\"]', 'inactive', 1, '2025-08-20 13:54:09', '2025-08-20 14:21:38'),
(27, 11, 'Something', 'ASD', 'product', 'technology', 'uploads/listings/68a5d42ee5421_images (1).jpeg,uploads/listings/68a5d42ee5c65_images.jpeg', '[\"IDK\"]', 'inactive', 1, '2025-08-20 13:57:02', '2025-08-20 14:21:38'),
(28, 3, 'asd', 'dfs', 'product', 'music', 'uploads/listings/68a5dffc17b32_images (1).jpeg,uploads/listings/68a5dffc17f4e_images.jpeg', '[\"asd\"]', 'inactive', 0, '2025-08-20 14:47:24', '2025-08-20 14:53:39'),
(29, 11, 'adf', 'asdf', 'product', 'music', 'uploads/listings/68a5e00a89bf3_images (1).jpeg,uploads/listings/68a5e00a8a072_images.jpeg', '[\"fdg\"]', 'inactive', 0, '2025-08-20 14:47:38', '2025-08-20 14:53:39'),
(30, 3, 'wer', 'wer', 'product', 'music', 'uploads/listings/68a5e3b714573_images (1).jpeg,uploads/listings/68a5e3b71498f_images.jpeg', '[\"sdf\"]', 'inactive', 0, '2025-08-20 15:03:19', '2025-08-20 15:05:27'),
(31, 11, 'asdf', 'ad', 'product', 'technology', 'uploads/listings/68a5e3c64a400_images (1).jpeg,uploads/listings/68a5e3c64a9b5_images.jpeg', '[\"dfg\"]', 'inactive', 0, '2025-08-20 15:03:34', '2025-08-20 15:05:27'),
(32, 3, 'asd', 'asd', 'product', 'music', 'uploads/listings/68a5e6460a798_images (1).jpeg,uploads/listings/68a5e6460ab9f_images.jpeg', '[\"sdf\"]', 'inactive', 0, '2025-08-20 15:14:14', '2025-08-20 15:17:37'),
(33, 11, 'ssf', 'xc', 'product', 'education', 'uploads/listings/68a5e651837f9_images (1).jpeg,uploads/listings/68a5e65183c56_images.jpeg', '[\"cvb\"]', 'inactive', 0, '2025-08-20 15:14:25', '2025-08-20 15:17:37'),
(34, 3, 'l;', '&#039;l&#039;', 'product', 'music', 'uploads/listings/68a5e7555b942_images (1).jpeg,uploads/listings/68a5e7555bd8a_images.jpeg', '[\"lk;\'\"]', 'inactive', 0, '2025-08-20 15:18:45', '2025-08-20 15:25:47'),
(35, 11, 'l;k&#039;', 'kl;&#039;', 'product', 'health-beauty', 'uploads/listings/68a5e7672d14d_images (1).jpeg,uploads/listings/68a5e7672d5c0_images.jpeg', '[\";l\'\"]', 'inactive', 0, '2025-08-20 15:19:03', '2025-08-20 15:25:47'),
(36, 3, 'dfg', 'fdg', 'product', 'education', 'uploads/listings/68ab449baabe2_images (1).jpeg,uploads/listings/68ab449bab050_images.jpeg', '[\"dg\"]', 'inactive', 0, '2025-08-20 21:58:36', '2025-08-24 16:58:03'),
(37, 11, 'QWERTY', 'asd', 'product', 'music', 'uploads/listings/68a645a32c467_images (1).jpeg,uploads/listings/68a645a32c8a6_images.jpeg,uploads/listings/68a645a32cd41_a_different_vision_on_fashion_photography-BY-peter_lindbergh-ezgif.com-webp-to-jpg-converter.jpg', '[\"hgjghj\"]', 'inactive', 1, '2025-08-20 22:01:07', '2025-08-20 22:04:21'),
(38, 11, 'asd', 'asd', 'product', 'health-beauty', 'uploads/listings/68ab4f615c5f7_images (1).jpeg,uploads/listings/68ab4f615cbf1_images.jpeg', '[\"sdf\"]', 'inactive', 0, '2025-08-24 17:44:01', '2025-08-24 17:52:42'),
(39, 3, 'xcv', 'xcv', 'product', 'technology', 'uploads/listings/68ab4f7417238_images (1).jpeg,uploads/listings/68ab4f741765c_images.jpeg', '[\"sdf\"]', 'inactive', 0, '2025-08-24 17:44:20', '2025-08-24 17:52:42'),
(40, 3, 'Something', 'xcvb', 'product', 'Technology', 'uploads/listings/68ab51ebace69_images (1).jpeg,uploads/listings/68ab51ebad646_images.jpeg', '[\"ANything\",\"something\"]', 'inactive', 1, '2025-08-24 17:54:51', '2025-08-24 18:01:06'),
(41, 11, 'asdsadsda', 'xcv', 'product', 'Education', 'uploads/listings/68ab522468fd6_images (1).jpeg,uploads/listings/68ab52246952e_images.jpeg', '[\"xcvxcv\"]', 'inactive', 0, '2025-08-24 17:55:48', '2025-08-24 18:01:06'),
(42, 3, 'asd', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3c4193b0e_images (1).jpeg,uploads/listings/68ae3c41965b7_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 22:59:13', '2025-08-26 23:22:29'),
(43, 3, 'asd', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3c5092c6b_images (1).jpeg,uploads/listings/68ae3c5093300_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 22:59:28', '2025-08-26 23:06:42'),
(44, 3, 'asd', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3cb04973a_images (1).jpeg,uploads/listings/68ae3cb049d1f_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:01:04', '2025-08-26 23:06:39'),
(45, 3, 'asd', 'xcv', 'product', 'Health &amp; Beauty', 'uploads/listings/68ae3cc9662ec_images (1).jpeg,uploads/listings/68ae3cc966ce5_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:01:29', '2025-08-26 23:06:37'),
(46, 3, 'asd', 'asd', 'product', 'Sports &amp; Fitness', 'uploads/listings/68ae3d31476b6_images (1).jpeg,uploads/listings/68ae3d3147bee_images.jpeg', '[\"sf\"]', 'deleted', 0, '2025-08-26 23:03:13', '2025-08-26 23:06:35'),
(47, 3, 'asd', 'asd', 'product', 'Sports &amp; Fitness', 'uploads/listings/68ae3d39b610c_images (1).jpeg,uploads/listings/68ae3d39b6730_images.jpeg', '[\"sf\"]', 'deleted', 0, '2025-08-26 23:03:21', '2025-08-26 23:06:32'),
(48, 3, 'asd', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3e224c9ad_images (1).jpeg,uploads/listings/68ae3e2251c2e_images.jpeg', '[\"asd\"]', 'deleted', 0, '2025-08-26 23:07:14', '2025-08-26 23:22:26'),
(49, 3, 'asd', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3e9625619_images (1).jpeg,uploads/listings/68ae3e9625c36_images.jpeg', '[\"asd\"]', 'deleted', 0, '2025-08-26 23:09:10', '2025-08-26 23:22:24'),
(50, 3, 'sdf', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3ec3e0810_images (1).jpeg,uploads/listings/68ae3ec3e0ef9_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:09:55', '2025-08-26 23:22:22'),
(51, 3, 'sdf', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3f05eded0_images (1).jpeg,uploads/listings/68ae3f05ee4f4_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:11:01', '2025-08-26 23:22:20'),
(52, 3, 'sdf', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3f0a0e212_images (1).jpeg,uploads/listings/68ae3f0a0e86c_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:11:06', '2025-08-26 23:22:17'),
(53, 3, 'sdf', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3f44c2ffa_images (1).jpeg,uploads/listings/68ae3f44c35b9_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:12:04', '2025-08-26 23:22:15'),
(54, 3, 'asd', 'asd', 'product', 'Education', 'uploads/listings/68ae3f5534174_images (1).jpeg,uploads/listings/68ae3f5535d6d_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:12:21', '2025-08-26 23:22:13'),
(55, 3, 'asd', 'asd', 'product', 'Education', 'uploads/listings/68ae3f5d9a4c7_images (1).jpeg,uploads/listings/68ae3f5d9aaad_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:12:29', '2025-08-26 23:22:10'),
(56, 3, 'asd', 'asd', 'product', 'Health &amp; Beauty', 'uploads/listings/68ae3f812c311_images (1).jpeg,uploads/listings/68ae3f812cdc0_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:13:05', '2025-08-26 23:22:08'),
(57, 3, 'sdf', 'sdf', 'product', 'Technology', 'uploads/listings/68ae3fceb47a4_images (1).jpeg,uploads/listings/68ae3fceb4d4c_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:14:22', '2025-08-26 23:22:04'),
(58, 3, 'sdf', 'fdg', 'product', 'Technology', 'uploads/listings/68ae40b442679_images (1).jpeg,uploads/listings/68ae40b442cc8_images.jpeg', '[\"dfg\"]', 'deleted', 0, '2025-08-26 23:18:12', '2025-08-26 23:22:02'),
(59, 3, 'hgk', 'hjk', 'product', 'Technology', 'uploads/listings/68ae412b380b7_images (1).jpeg,uploads/listings/68ae412b386de_images.jpeg', '[\"l;\"]', 'deleted', 0, '2025-08-26 23:20:11', '2025-08-26 23:21:59'),
(60, 3, 'dsf', 'sdf', 'product', 'Design', 'uploads/listings/68ae4180d8526_images (1).jpeg,uploads/listings/68ae4180d8b53_images.jpeg', '[\"sdf\"]', 'deleted', 0, '2025-08-26 23:21:36', '2025-08-26 23:21:56'),
(61, 3, 'fgf', 'sdf', 'service', 'Education', 'uploads/listings/68ae493feb961_images (1).jpeg,uploads/listings/68ae493febe46_images.jpeg', '[\"fg\"]', 'active', 1, '2025-08-26 23:22:45', '2025-08-26 23:54:39'),
(62, 3, 'asd', 'asd', 'product', 'technology', 'uploads/listings/68ae4a13be2b2_images (1).jpeg,uploads/listings/68ae4a13be7cb_images.jpeg', '[\"dsf\"]', 'active', 0, '2025-08-26 23:58:11', '2025-08-26 23:58:11'),
(63, 3, 'asd', 'asd', 'product', 'technology', 'uploads/listings/68ae4adc35f2e_images (1).jpeg,uploads/listings/68ae4adc36390_images.jpeg', '[\"dsf\"]', 'active', 0, '2025-08-27 00:01:32', '2025-08-27 00:01:32'),
(64, 3, 'asd', 'asd', 'product', 'technology', 'uploads/listings/68ae4b5759b88_images (1).jpeg,uploads/listings/68ae4b575a032_images.jpeg', '[\"dsf\"]', 'active', 0, '2025-08-27 00:03:35', '2025-08-27 00:03:35'),
(65, 3, 'sdf', 'sdf', 'product', 'technology', 'uploads/listings/68ae4b78a0d79_images (1).jpeg,uploads/listings/68ae4b78a11cd_images.jpeg', '[\"dfg\"]', 'active', 0, '2025-08-27 00:04:08', '2025-08-27 00:04:08'),
(66, 3, 'sf', 'sdf', 'product', 'health-beauty', 'uploads/listings/68ae4baf93f45_images (1).jpeg,uploads/listings/68ae4baf943d3_images.jpeg', '[\"sf\"]', 'active', 0, '2025-08-27 00:05:03', '2025-08-27 00:05:03');

-- --------------------------------------------------------

--
-- Table structure for table `listing_views`
--

CREATE TABLE `listing_views` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listing_views`
--

INSERT INTO `listing_views` (`id`, `user_id`, `listing_id`, `viewed_at`) VALUES
(1, 3, 11, '2025-08-16 23:46:33'),
(2, 3, 10, '2025-08-16 23:56:19'),
(3, 3, 9, '2025-08-17 00:03:25'),
(4, 3, 4, '2025-08-17 00:22:29'),
(5, 3, 12, '2025-08-19 20:51:41'),
(6, 3, 15, '2025-08-20 13:34:59'),
(7, 11, 26, '2025-08-20 13:57:26'),
(8, 11, 11, '2025-08-20 13:57:53'),
(9, 3, 27, '2025-08-20 13:58:12'),
(10, 3, 37, '2025-08-20 22:01:19'),
(11, 11, 40, '2025-08-24 17:56:06'),
(12, 11, 61, '2025-08-26 23:51:42');

-- --------------------------------------------------------

--
-- Table structure for table `meetups`
--

CREATE TABLE `meetups` (
  `id` int(11) NOT NULL,
  `trade_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `meetup_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meetups`
--

INSERT INTO `meetups` (`id`, `trade_id`, `user_id`, `location`, `meetup_time`, `created_at`, `updated_at`) VALUES
(1, 18, 3, 'ABC road Karachi', '2025-08-15 19:36:00', '2025-08-04 11:36:41', '2025-08-04 11:36:41'),
(2, 21, 11, 'ASD', '2025-08-04 16:44:00', '2025-08-04 11:40:40', '2025-08-04 11:40:40'),
(3, 22, 3, 'Abc road', '2025-08-15 16:14:00', '2025-08-11 11:10:38', '2025-08-11 11:10:38'),
(4, 24, 11, 'SD ROAD', '2025-08-07 19:21:00', '2025-08-20 14:19:36', '2025-08-20 14:19:36'),
(5, 32, 11, 'hjk', '2025-08-24 13:45:00', '2025-08-24 17:45:19', '2025-08-24 17:45:19'),
(6, 33, 3, 'Somewhere on this planet', '2025-08-24 16:00:00', '2025-08-24 18:00:21', '2025-08-24 18:00:21');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `trade_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','delivered','read') DEFAULT 'sent',
  `call_type` enum('none','call_invitation','missed_call','voice_call') DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `trade_id`, `message`, `is_read`, `created_at`, `status`, `call_type`) VALUES
(1, 2, 3, NULL, 'asdasd', 1, '2025-07-22 13:33:14', 'sent', 'none'),
(2, 3, 2, NULL, 'asdsad', 1, '2025-07-22 13:33:35', 'sent', 'none'),
(3, 2, 3, NULL, 'asd', 1, '2025-07-22 13:33:42', 'sent', 'none'),
(4, 3, 11, 14, 'sfsfvb', 1, '2025-08-01 23:11:53', 'read', 'none'),
(5, 11, 3, NULL, 'adsfasdf', 1, '2025-08-01 23:13:23', 'sent', 'none'),
(6, 11, 3, NULL, 'adsfasdf', 1, '2025-08-01 23:14:24', 'sent', 'none'),
(7, 11, 3, NULL, 'adsfasdf', 1, '2025-08-01 23:14:29', 'sent', 'none'),
(8, 3, 6, 15, 'fghfghfghgf', 0, '2025-08-04 11:17:40', 'sent', 'none'),
(9, 3, 2, 16, 'dfgdfg', 0, '2025-08-04 11:18:16', 'sent', 'none'),
(10, 3, 11, 17, 'ghj', 1, '2025-08-04 11:19:12', 'read', 'none'),
(11, 11, 3, 18, 'df', 1, '2025-08-04 11:29:42', 'sent', 'none'),
(12, 11, 3, 19, 'df', 1, '2025-08-04 11:34:37', 'sent', 'none'),
(13, 3, 11, 18, 'I have accepted your trade request for f in exchange for dfg. Let\'s meet at ABC road Karachi on August 15, 2025, 7:36 PM to complete the trade.', 1, '2025-08-04 11:36:41', 'read', 'none'),
(14, 3, 11, 19, 'I have accepted your trade request for f in exchange for dfg. The trade method is Secure Trade Location, paid via Cash on Delivery. Please bring your item to our secure facility to complete the trade.', 1, '2025-08-04 11:37:32', 'read', 'none'),
(15, 3, 11, 20, 'l;', 1, '2025-08-04 11:38:35', 'read', 'none'),
(16, 11, 3, 20, 'I have accepted your trade request for dfg in exchange for fdg. The trade method is Company Inspection & Delivery, paid via Cash on Delivery. Please prepare your item for inspection and delivery.', 1, '2025-08-04 11:38:55', 'sent', 'none'),
(17, 3, 11, 21, 'sdfsdf', 1, '2025-08-04 11:40:24', 'read', 'none'),
(18, 11, 3, 21, 'I have accepted your trade request for dfg in exchange for fdg. Let\'s meet at ASD on August 4, 2025, 4:44 PM to complete the trade.', 1, '2025-08-04 11:40:40', 'sent', 'none'),
(19, 11, 3, 22, 'bvhgfhfgh', 1, '2025-08-11 11:08:11', 'sent', 'none'),
(20, 3, 11, 22, 'I have accepted your trade request for f in exchange for dfg. Let\'s meet at Abc road on August 15, 2025, 4:14 PM to complete the trade.', 1, '2025-08-11 11:10:38', 'read', 'none'),
(21, 3, 11, NULL, 'ok', 1, '2025-08-12 12:43:43', 'read', 'none'),
(22, 3, 11, NULL, 'ok', 1, '2025-08-12 12:44:00', 'read', 'none'),
(23, 3, 11, 23, 'ghj', 1, '2025-08-15 20:53:24', 'read', 'none'),
(24, 11, 3, 23, 'I have accepted your trade request for Last product trade in exchange for Last Product. The trade method is Company Inspection & Delivery, paid via Cash on Delivery. Please prepare your item for inspection and delivery.', 1, '2025-08-15 20:53:43', 'sent', 'none'),
(25, 11, 3, NULL, 'Hey', 1, '2025-08-15 21:03:13', 'sent', 'none'),
(26, 3, 11, NULL, 'wassup', 1, '2025-08-15 21:03:22', 'read', 'none'),
(27, 11, 3, NULL, 'Hey', 1, '2025-08-15 21:03:38', 'sent', 'none'),
(28, 3, 11, NULL, 'ASD', 1, '2025-08-15 21:06:16', 'read', 'none'),
(29, 11, 3, NULL, 'fghj', 1, '2025-08-15 22:05:35', 'sent', 'none'),
(30, 11, 3, NULL, 'dsf', 1, '2025-08-15 22:13:44', 'sent', 'none'),
(31, 11, 3, NULL, 'sdf', 1, '2025-08-15 22:13:48', 'sent', 'none'),
(32, 3, 11, NULL, 'fds', 1, '2025-08-15 22:14:00', 'sent', 'none'),
(33, 3, 11, NULL, 'f', 1, '2025-08-15 22:14:06', 'sent', 'none'),
(34, 3, 11, NULL, 'l', 1, '2025-08-15 22:14:34', 'sent', 'none'),
(35, 11, 3, NULL, 'a', 1, '2025-08-15 22:22:29', 'sent', 'none'),
(36, 11, 3, NULL, 'asd', 1, '2025-08-15 22:24:48', 'sent', 'none'),
(37, 3, 11, NULL, 'fds', 1, '2025-08-15 22:24:52', 'sent', 'none'),
(38, 11, 3, NULL, 'hjk', 1, '2025-08-15 22:25:16', 'sent', 'none'),
(39, 11, 3, NULL, 'k', 1, '2025-08-15 22:31:08', 'sent', 'none'),
(40, 3, 11, NULL, 'k', 1, '2025-08-15 22:31:11', 'sent', 'none'),
(41, 3, 11, NULL, 'k', 1, '2025-08-15 22:31:23', 'sent', 'none'),
(42, 3, 11, NULL, 'f', 1, '2025-08-15 22:33:46', 'sent', 'none'),
(43, 3, 11, NULL, 'asdasdasdasdasdasdasdasdasd', 1, '2025-08-15 22:33:59', 'sent', 'none'),
(45, 11, 3, NULL, 'ghjghjghjghjghjg', 1, '2025-08-15 22:41:38', 'sent', 'none'),
(46, 11, 3, NULL, 'ghjghjghjghjghjgghjghjghjghjghjg', 1, '2025-08-15 22:41:42', 'sent', 'none'),
(47, 11, 3, NULL, 'ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg', 1, '2025-08-15 22:41:45', 'sent', 'none'),
(48, 3, 11, NULL, 'fdg', 1, '2025-08-15 22:43:12', 'sent', 'none'),
(49, 3, 11, NULL, 'asd', 1, '2025-08-15 22:43:22', 'sent', 'none'),
(50, 3, 11, NULL, 'asd', 1, '2025-08-15 22:43:25', 'sent', 'none'),
(51, 3, 11, NULL, 'ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg  03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg  03:41', 1, '2025-08-15 22:43:30', 'sent', 'none'),
(52, 3, 11, NULL, 'ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg  03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg  03:41', 1, '2025-08-15 22:43:35', 'sent', 'none'),
(53, 3, 11, NULL, 'ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg', 1, '2025-08-15 22:43:47', 'sent', 'none'),
(54, 3, 11, NULL, 'asd asd', 1, '2025-08-15 22:43:52', 'sent', 'none'),
(55, 3, 11, NULL, 'asd asd , asd', 1, '2025-08-15 22:43:56', 'sent', 'none'),
(56, 3, 11, NULL, 'asdasdsa', 1, '2025-08-15 22:44:54', 'sent', 'none'),
(57, 3, 11, NULL, 'dasd', 1, '2025-08-15 22:45:28', 'sent', 'none'),
(58, 3, 11, NULL, 'sdf', 1, '2025-08-15 22:47:49', 'sent', 'none'),
(59, 3, 11, NULL, 'asd', 1, '2025-08-15 22:48:00', 'sent', 'none'),
(60, 3, 11, NULL, 'fdg', 1, '2025-08-15 22:50:24', 'sent', 'none'),
(61, 3, 11, NULL, 'ghj', 1, '2025-08-15 22:50:50', 'sent', 'none'),
(62, 11, 3, NULL, 'hj', 1, '2025-08-15 22:50:54', 'sent', 'none'),
(63, 11, 3, NULL, 'jh', 1, '2025-08-15 22:53:51', 'sent', 'none'),
(64, 11, 3, NULL, 'dfg', 1, '2025-08-15 23:08:38', 'sent', 'none'),
(65, 11, 3, NULL, 'cvb', 1, '2025-08-15 23:08:45', 'sent', 'none'),
(66, 11, 3, NULL, 'hj', 1, '2025-08-15 23:12:54', 'sent', 'none'),
(67, 3, 6, NULL, '😭', 0, '2025-08-17 00:23:12', 'sent', 'none'),
(68, 3, 6, NULL, 'gkskgsgkskgogsggkskjtstistizitzfisigsgisigsgisigsgiskgs', 0, '2025-08-17 00:23:34', 'sent', 'none'),
(69, 3, 6, NULL, 'xhxhxjxjxhfhhhhjcpudyodoydoydypdyodupdpuduppudypdoydpydyodoysoysyspspyspspsypsyosyo', 0, '2025-08-17 00:23:49', 'sent', 'none'),
(73, 3, 11, NULL, 'ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg', 1, '2025-08-17 00:28:53', 'sent', 'none'),
(75, 3, 11, NULL, 'ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg', 1, '2025-08-17 00:32:52', 'sent', 'none'),
(76, 3, 11, NULL, 'ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg 03:41ghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjgghjghjghjghjghjg', 1, '2025-08-17 00:34:29', 'sent', 'none'),
(77, 3, 6, NULL, 'stutysystustustusluRyzkdTkYkOytToToatoatoryaptstpatpstoaptLtyLtyfGlyfKfyLfhzgzlfhzlgzhflzlgzglfhDhFhlyUlfzlfualruatpyratoryayroatoyatouatorylaotToyaoyaotrTlLfLchzchznlczljczjlfgjfzutzrzuuzrrRrr', 0, '2025-08-17 00:36:42', 'sent', 'none'),
(78, 3, 11, NULL, 'sjdj', 1, '2025-08-17 00:42:23', 'sent', 'none'),
(79, 11, 3, NULL, 'fghj', 1, '2025-08-17 00:52:11', 'sent', 'none'),
(80, 11, 3, NULL, 'fghjgfhj', 1, '2025-08-17 00:52:19', 'sent', 'none'),
(81, 11, 3, NULL, 'ghjghj', 1, '2025-08-17 00:52:25', 'sent', 'none'),
(82, 3, 11, NULL, '.', 1, '2025-08-17 00:56:22', 'sent', 'none'),
(83, 3, 11, NULL, '.', 1, '2025-08-17 00:56:28', 'sent', 'none'),
(84, 3, 11, NULL, '.', 1, '2025-08-17 01:01:19', 'sent', 'none'),
(85, 3, 11, NULL, '.', 1, '2025-08-17 01:01:27', 'sent', 'none'),
(86, 3, 11, NULL, '.', 1, '2025-08-17 01:01:33', 'sent', 'none'),
(87, 3, 11, NULL, '.', 1, '2025-08-17 01:01:37', 'sent', 'none'),
(88, 11, 3, NULL, 'gfh', 1, '2025-08-17 01:01:53', 'sent', 'none'),
(89, 11, 3, NULL, 'dfg', 1, '2025-08-17 01:01:57', 'sent', 'none'),
(90, 11, 3, NULL, 'g', 1, '2025-08-17 01:04:17', 'sent', 'none'),
(91, 11, 3, NULL, 'j', 1, '2025-08-17 01:04:20', 'sent', 'none'),
(92, 11, 3, NULL, 'j', 1, '2025-08-17 01:04:21', 'sent', 'none'),
(93, 11, 3, NULL, 'j', 1, '2025-08-17 01:04:21', 'sent', 'none'),
(94, 11, 3, NULL, 'j', 1, '2025-08-17 01:04:22', 'sent', 'none'),
(95, 11, 3, NULL, 'j', 1, '2025-08-17 01:04:22', 'sent', 'none'),
(96, 11, 3, NULL, 'j', 1, '2025-08-17 01:04:23', 'sent', 'none'),
(97, 11, 3, NULL, 'j', 1, '2025-08-17 01:04:25', 'sent', 'none'),
(98, 11, 3, NULL, 'j', 1, '2025-08-17 01:04:26', 'sent', 'none'),
(99, 11, 3, NULL, 'j', 1, '2025-08-17 01:04:26', 'sent', 'none'),
(100, 11, 3, NULL, 'jj', 1, '2025-08-17 01:04:27', 'sent', 'none'),
(101, 3, 11, NULL, '.', 1, '2025-08-17 01:04:30', 'sent', 'none'),
(102, 3, 11, NULL, '.', 1, '2025-08-17 01:04:34', 'sent', 'none'),
(103, 3, 11, NULL, '.', 1, '2025-08-17 01:04:39', 'sent', 'none'),
(104, 3, 11, NULL, '.', 1, '2025-08-17 01:04:44', 'sent', 'none'),
(105, 3, 11, NULL, '.', 1, '2025-08-17 01:04:50', 'sent', 'none'),
(106, 3, 11, NULL, '.', 1, '2025-08-17 01:04:58', 'sent', 'none'),
(107, 3, 11, NULL, '.', 1, '2025-08-17 01:05:02', 'sent', 'none'),
(109, 11, 3, NULL, '.', 1, '2025-08-17 01:05:10', 'sent', 'none'),
(110, 3, 11, NULL, 'uraufafiajfsgk', 1, '2025-08-17 01:05:17', 'sent', 'none'),
(111, 3, 11, NULL, '😭', 1, '2025-08-17 01:05:36', 'sent', 'none'),
(112, 3, 11, NULL, 'Buhahahahahha', 1, '2025-08-17 01:05:40', 'sent', 'none'),
(113, 3, 11, NULL, 'jj', 1, '2025-08-17 01:11:41', 'sent', 'none'),
(114, 3, 11, NULL, '.', 1, '2025-08-17 01:16:30', 'sent', 'none'),
(115, 3, 11, NULL, '.', 1, '2025-08-17 01:16:40', 'sent', 'none'),
(116, 3, 11, NULL, '.', 1, '2025-08-17 01:17:57', 'sent', 'none'),
(117, 3, 11, NULL, '.', 1, '2025-08-17 01:18:06', 'sent', 'none'),
(118, 3, 11, NULL, '😭', 1, '2025-08-17 01:18:14', 'sent', 'none'),
(119, 3, 11, NULL, '😔', 1, '2025-08-17 01:18:25', 'sent', 'none'),
(120, 3, 11, NULL, '.', 1, '2025-08-17 01:20:09', 'sent', 'none'),
(121, 3, 11, NULL, '.', 1, '2025-08-17 01:20:38', 'sent', 'none'),
(122, 3, 11, NULL, '.', 1, '2025-08-17 01:21:41', 'sent', 'none'),
(123, 3, 11, NULL, '😔', 1, '2025-08-17 01:23:03', 'sent', 'none'),
(125, 3, 11, NULL, '.', 1, '2025-08-17 01:25:37', 'sent', 'none'),
(133, 11, 3, NULL, '656+5', 1, '2025-08-17 01:43:58', 'sent', 'none'),
(134, 11, 3, NULL, '32', 1, '2025-08-17 01:43:59', 'sent', 'none'),
(135, 11, 3, NULL, '3232', 1, '2025-08-17 01:44:00', 'sent', 'none'),
(136, 11, 3, NULL, '+9+9', 1, '2025-08-17 01:44:01', 'sent', 'none'),
(137, 11, 3, NULL, '9+9+5', 1, '2025-08-17 01:44:02', 'sent', 'none'),
(138, 11, 3, NULL, '+9+3', 1, '2025-08-17 01:44:04', 'sent', 'none'),
(139, 11, 3, NULL, '23', 1, '2025-08-17 01:44:04', 'sent', 'none'),
(140, 11, 3, NULL, '.0.6', 1, '2025-08-17 01:44:05', 'sent', 'none'),
(141, 11, 3, NULL, '356', 1, '2025-08-17 01:44:06', 'sent', 'none'),
(142, 11, 3, NULL, '230.', 1, '2025-08-17 01:44:06', 'sent', 'none'),
(143, 11, 3, NULL, '065+', 1, '2025-08-17 01:44:07', 'sent', 'none'),
(144, 11, 3, NULL, '065+.0', 1, '2025-08-17 01:44:07', 'sent', 'none'),
(145, 11, 3, NULL, '655+6++9+4+110.4164+54112013', 1, '2025-08-17 01:44:17', 'sent', 'none'),
(147, 3, 11, NULL, '.', 1, '2025-08-17 01:54:30', 'sent', 'none'),
(148, 3, 11, NULL, '.', 1, '2025-08-17 01:54:45', 'sent', 'none'),
(149, 3, 11, NULL, '😭😭😭😭😭', 1, '2025-08-17 01:54:54', 'sent', 'none'),
(150, 3, 11, NULL, 'gi do aid', 1, '2025-08-17 01:55:07', 'sent', 'none'),
(151, 3, 11, NULL, 'svichi', 1, '2025-08-17 01:55:18', 'sent', 'none'),
(152, 3, 11, NULL, 'ghj', 1, '2025-08-17 12:40:23', 'sent', 'none'),
(153, 3, 11, NULL, 'ghjk', 1, '2025-08-17 13:13:47', 'sent', 'none'),
(154, 3, 11, NULL, 'ghjk', 1, '2025-08-17 13:13:49', 'sent', 'none'),
(155, 3, 11, NULL, 'l', 1, '2025-08-17 13:13:57', 'sent', 'none'),
(156, 3, 11, NULL, 'nxv', 1, '2025-08-17 13:16:11', 'sent', 'none'),
(157, 3, 11, NULL, 'hjk', 1, '2025-08-17 13:18:28', 'sent', 'none'),
(158, 11, 3, NULL, 'asd', 1, '2025-08-17 13:20:31', 'sent', 'none'),
(159, 3, 11, NULL, '.', 1, '2025-08-17 13:20:56', 'sent', 'none'),
(160, 3, 11, NULL, '.', 1, '2025-08-17 13:21:13', 'sent', 'none'),
(161, 11, 3, NULL, '.', 1, '2025-08-17 15:04:30', 'sent', 'none'),
(162, 3, 11, NULL, 'Incoming call...', 1, '2025-08-17 16:56:46', 'sent', 'call_invitation'),
(163, 11, 3, NULL, 'Incoming call...', 1, '2025-08-17 16:56:53', 'sent', 'call_invitation'),
(164, 3, 11, NULL, 'Incoming call...', 1, '2025-08-17 16:57:46', 'sent', 'call_invitation'),
(165, 11, 3, NULL, 'Incoming call...', 1, '2025-08-17 16:57:55', 'sent', 'call_invitation'),
(166, 3, 11, NULL, 'bnbnbnbnbnb', 1, '2025-08-17 17:02:56', 'sent', NULL),
(167, 3, 11, NULL, 'bmn,nb,m', 1, '2025-08-17 17:03:05', 'sent', NULL),
(168, 3, 11, NULL, 'bnm,', 1, '2025-08-17 17:03:15', 'sent', NULL),
(169, 3, 11, NULL, 'Incoming call...', 1, '2025-08-17 17:03:41', 'sent', 'call_invitation'),
(170, 11, 3, NULL, '.', 1, '2025-08-17 17:12:19', 'sent', 'none'),
(171, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:12:27', 'sent', 'none'),
(172, 3, 11, NULL, 'Calling', 1, '2025-08-17 17:12:40', 'sent', 'none'),
(173, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:14:52', 'sent', 'none'),
(174, 11, 3, NULL, 'dfg', 1, '2025-08-17 17:16:34', 'sent', 'none'),
(175, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:17:50', 'sent', 'none'),
(176, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:19:32', 'sent', 'none'),
(177, 11, 3, NULL, 'Missed', 1, '2025-08-17 17:19:39', 'sent', 'none'),
(178, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:19:42', 'sent', 'none'),
(179, 11, 3, NULL, 'Missed', 1, '2025-08-17 17:19:46', 'sent', 'none'),
(180, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:20:20', 'sent', 'none'),
(181, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:20:25', 'sent', 'none'),
(182, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:20:28', 'sent', 'none'),
(183, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:20:29', 'sent', 'none'),
(184, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:21:08', 'sent', 'none'),
(185, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:21:13', 'sent', 'none'),
(186, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:21:17', 'sent', 'none'),
(187, 11, 3, NULL, '.', 1, '2025-08-17 17:22:53', 'sent', 'none'),
(188, 3, 11, NULL, 'Calling', 1, '2025-08-17 17:23:04', 'sent', 'none'),
(189, 11, 3, NULL, 'askjgdkfjgjsadgfsadgfsaskjgdkfjgjsadgfsadgfsaskjgdkfjgjsadgfsadgfsaskjgdkfjgjsadgfsadgfsaskjgdkfjgjsadgfsadgfsaskjgdkfjgjsadgfsadgfsaskjgdkfjgjsadgfsadgfs', 1, '2025-08-17 17:23:40', 'sent', 'none'),
(190, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:23:44', 'sent', 'none'),
(191, 3, 11, NULL, 'Calling', 1, '2025-08-17 17:23:52', 'sent', 'none'),
(192, 11, 3, NULL, '&#039;;;;&#039;', 1, '2025-08-17 17:27:06', 'sent', 'none'),
(193, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:27:10', 'sent', 'none'),
(194, 3, 11, NULL, 'Calling', 1, '2025-08-17 17:27:14', 'sent', 'none'),
(195, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:27:18', 'sent', 'none'),
(196, 3, 11, NULL, 'Calling', 1, '2025-08-17 17:27:21', 'sent', 'none'),
(197, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:28:48', 'sent', 'none'),
(198, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:29:41', 'sent', 'none'),
(199, 11, 3, NULL, 'Calling', 1, '2025-08-17 17:29:52', 'sent', 'none'),
(200, 11, 3, NULL, '.', 1, '2025-08-19 00:27:57', 'sent', 'none'),
(201, 3, 11, NULL, 'dfg', 1, '2025-08-19 00:28:03', 'sent', 'none'),
(202, 3, 11, NULL, 'AVCD', 1, '2025-08-19 22:10:34', 'sent', 'none'),
(203, 11, 3, NULL, 'ndsfsd', 1, '2025-08-19 22:10:51', 'sent', 'none'),
(204, 11, 3, NULL, 'Calling', 1, '2025-08-19 22:10:59', 'sent', 'none'),
(205, 11, 3, NULL, 'Calling', 1, '2025-08-19 22:12:56', 'sent', 'none'),
(206, 3, 11, NULL, 'Calling', 1, '2025-08-19 22:12:59', 'sent', 'none'),
(207, 3, 11, 24, 'I would like to trade this', 1, '2025-08-20 13:58:57', 'sent', 'none'),
(208, 11, 3, 24, 'I have accepted your trade request for Airpods in exchange for Something. Let\'s meet at SD ROAD on August 7, 2025, 7:21 PM to complete the trade.', 1, '2025-08-20 14:19:36', 'sent', 'none'),
(209, 11, 3, 25, 'cvbvcb', 1, '2025-08-20 14:47:46', 'sent', 'none'),
(210, 3, 11, 25, 'I have accepted your trade request for adf in exchange for asd. The trade method is Secure Trade Location, paid via Cash on Delivery. Please bring your item to our secure facility to complete the trade.', 1, '2025-08-20 14:49:10', 'sent', 'none'),
(211, 11, 3, 26, 'h', 1, '2025-08-20 15:03:43', 'sent', 'none'),
(212, 3, 11, 26, 'I have accepted your trade request for asdf in exchange for wer. The trade method is Company Inspection & Delivery, paid via Cash on Delivery. Please prepare your item for inspection and delivery.', 1, '2025-08-20 15:04:21', 'sent', 'none'),
(213, 3, 11, 27, 'xcv', 1, '2025-08-20 15:14:37', 'sent', 'none'),
(214, 3, 11, 28, 'm', 1, '2025-08-20 15:15:02', 'sent', 'none'),
(215, 11, 3, 28, 'I have accepted your trade request for asd in exchange for ssf. The trade method is Secure Trade Location, paid via Cash on Delivery. Please bring your item to our secure facility to complete the trade.', 1, '2025-08-20 15:15:48', 'sent', 'none'),
(216, 3, 11, 29, 'l;&#039;', 1, '2025-08-20 15:19:13', 'sent', 'none'),
(217, 3, 11, 30, 'l;&#039;', 1, '2025-08-20 15:19:19', 'sent', 'none'),
(218, 11, 3, 30, 'I have accepted your trade request for l; in exchange for l;k&#039;. The trade method is Company Inspection & Delivery, paid via Cash on Delivery. Please prepare your item for inspection and delivery.', 1, '2025-08-20 15:20:10', 'sent', 'none'),
(219, 3, 11, 31, 'dfg', 1, '2025-08-20 22:03:13', 'sent', 'none'),
(220, 11, 3, 31, 'I have accepted your trade request for dfg in exchange for QWERTY. The trade method is Secure Trade Location, paid via Cash on Delivery. Please bring your item to our secure facility to complete the trade.', 1, '2025-08-20 22:03:38', 'sent', 'none'),
(221, 3, 11, 32, 'gjghj', 1, '2025-08-24 17:45:06', 'sent', 'none'),
(222, 11, 3, 32, 'I have accepted your trade request for xcv in exchange for asd. Let\'s meet at hjk on August 24, 2025, 1:45 PM to complete the trade.', 1, '2025-08-24 17:45:19', 'sent', 'none'),
(223, 3, 11, NULL, 'vbn', 1, '2025-08-24 17:46:26', 'sent', 'none'),
(224, 11, 3, 33, 'Hiiii', 1, '2025-08-24 17:56:43', 'sent', 'none'),
(225, 11, 3, NULL, 'sdfsdfds', 1, '2025-08-24 17:57:13', 'sent', 'none'),
(226, 11, 3, NULL, 'cvnvcnv', 1, '2025-08-24 17:57:16', 'sent', 'none'),
(227, 3, 11, NULL, 'cvbvcbvcbcvb', 1, '2025-08-24 17:57:28', 'sent', 'none'),
(228, 11, 3, NULL, 'Calling', 1, '2025-08-24 17:57:43', 'sent', 'none'),
(229, 11, 3, NULL, 'Calling', 1, '2025-08-24 17:58:41', 'sent', 'none'),
(230, 11, 3, NULL, 'Calling', 1, '2025-08-24 17:58:53', 'sent', 'none'),
(231, 3, 11, NULL, 'Calling', 1, '2025-08-24 17:58:58', 'sent', 'none'),
(232, 3, 11, 33, 'I have accepted your trade request for asdsadsda in exchange for Something. Let\'s meet at Somewhere on this planet on August 24, 2025, 4:00 PM to complete the trade.', 1, '2025-08-24 18:00:22', 'sent', 'none');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `is_read`, `related_id`, `created_at`) VALUES
(1, 1, 'welcome', 'Welcome to TradeCircle!', 'Thanks for joining our community. Start by creating your first listing.', 0, NULL, '2025-07-15 13:16:07'),
(2, 1, 'tip', 'Complete Your Profile', 'Add a profile photo and bio to increase your trading success rate.', 0, NULL, '2025-07-17 13:16:07'),
(3, 1, 'system', 'New Feature: Visual Search', 'Try our new AI-powered visual search to find items by uploading photos.', 0, NULL, '2025-07-20 13:16:07'),
(4, 4, 'welcome', 'Welcome to TradeCircle!', 'Thanks for joining! Create your first listing to start trading.', 1, NULL, '2025-07-17 12:19:30'),
(5, 4, 'trade_accepted', 'Trade Accepted!', 'Your trade offer for Acoustic Guitar has been accepted by Bob Smith.', 0, 2, '2025-07-23 12:19:30'),
(6, 5, 'welcome', 'Welcome to TradeCircle!', 'Thanks for joining! Create your first listing to start trading.', 1, NULL, '2025-07-18 12:19:30'),
(7, 5, 'new_trade', 'New Trade Offer', 'Alice Johnson wants to trade Web Design Services for your Acoustic Guitar.', 0, 2, '2025-07-22 12:19:30'),
(8, 5, 'trade_completed', 'Trade Completed!', 'Your trade with Demo User for Piano Lessons is complete.', 0, 7, '2025-07-24 12:19:30'),
(9, 6, 'welcome', 'Welcome to TradeCircle!', 'Thanks for joining! Create your first listing to start trading.', 1, NULL, '2025-07-19 12:19:30'),
(10, 6, 'trade_completed', 'Trade Completed!', 'Your trade with David Brown for Tool Set is complete.', 0, 4, '2025-07-25 12:19:30'),
(11, 7, 'welcome', 'Welcome to TradeCircle!', 'Thanks for joining! Create your first listing to start trading.', 1, NULL, '2025-07-20 12:19:30'),
(12, 7, 'trade_declined', 'Trade Declined', 'Your trade offer for Handmade Jewelry was declined by Emma Davis.', 0, 5, '2025-07-25 12:19:30'),
(13, 8, 'welcome', 'Welcome to TradeCircle!', 'Thanks for joining! Create your first listing to start trading.', 1, NULL, '2025-07-21 12:19:30'),
(14, 8, 'new_trade', 'New Trade Offer', 'David Brown wants to trade Car Repair Services for your Handmade Jewelry.', 0, 5, '2025-07-24 12:19:30'),
(15, 1, 'trade_completed', 'Trade Completed!', 'Your trade with Bob Smith for Piano Lessons is complete.', 0, 7, '2025-07-24 12:19:30');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 'roaraxyt@gmail.com', '2c84970a06f2efeab480a993e639b32e07e5fdd82ebaa4c980e0782e25a47a9a', '2025-08-02 01:09:28', 0, '2025-08-02 03:09:28'),
(2, 'roaraxyt@gmail.com', 'c95884ec91cc5a21c91696c5797d22b868d49f4cb9d7d3e96d01d07d278e78c5', '2025-08-02 01:12:28', 0, '2025-08-02 03:12:28'),
(3, 'roaraxyt@gmail.com', 'f775e16618dc19e3a2810a63480582d929867ccfb54e8c40140dbe7c85d910d3', '2025-08-02 04:14:19', 0, '2025-08-02 03:14:19'),
(4, 'roaraxyt@gmail.com', '75eabe1c9663a9076d815c28c7643ce8a71741f1b801b6c2fd35d7a4daf577cb', '2025-08-02 04:23:29', 0, '2025-08-02 03:23:29'),
(5, 'roaraxyt@gmail.com', 'c256c946814e8a9638cf4ff0bc891cefa302525aa17a692c7c4300b07af74f4b', '2025-08-23 05:52:01', 1, '2025-08-23 04:52:01'),
(6, 'roaraxyt@gmail.com', '329c3ab1a1b829935669f2790048138daf15ff329366a051b7a13637dde9e51d', '2025-08-23 05:53:54', 1, '2025-08-23 04:53:54'),
(7, 'roaraxyt@gmail.com', 'f17f8c603b2db45b0be7d2ca61ac4725c900f74104399a45d14e8d1c510ee641', '2025-08-23 05:56:18', 0, '2025-08-23 04:56:18'),
(8, 'roaraxyt@gmail.com', 'c1a2e92ad08dd242ec38bc8d8630c7b976bfbaa207e35e31709922153f69445c', '2025-08-23 05:56:52', 0, '2025-08-23 04:56:52'),
(9, 'roaraxyt@gmail.com', '017f77bcb03be63627cc51f8d43b6cf6fcca5cd97ffecce922c44fa45ba421f7', '2025-08-23 05:58:10', 0, '2025-08-23 04:58:10'),
(10, 'roaraxyt@gmail.com', '1ed9e1b3d4687a2c67f201083bf7fe42eb58ad80326620a0092b201f1fe257d4', '2025-08-23 06:03:39', 0, '2025-08-23 05:03:39'),
(11, 'roaraxyt@gmail.com', 'f35feb3906a77447f01277af67b34c6a324f17a562330eb6d23a119b0499c65f', '2025-08-23 06:07:04', 0, '2025-08-23 05:07:04'),
(12, 'roaraxyt@gmail.com', 'f2d0b21062d2685d34a3c91d18cedb96cae7f512f5390173e756c27788dd5666', '2025-08-23 06:07:08', 0, '2025-08-23 05:07:08'),
(13, 'roaraxyt@gmail.com', '777b3582d257cbc17b95557c2ef267ed3ea8c51ff0223a638dcf69d89ac52455', '2025-08-23 06:07:23', 0, '2025-08-23 05:07:23'),
(14, 'roaraxyt@gmail.com', 'e50ce7e42f9fe7c979b4856d6593e5ea43b8281362637cfa06a09164d032ee00', '2025-08-23 06:13:56', 0, '2025-08-23 05:13:56'),
(15, 'roaraxyt@gmail.com', 'a5ff505e737f4abdc1924e40424d006b2f72e35846aea06a95ac207215d85bc0', '2025-08-23 19:43:43', 0, '2025-08-23 18:43:43');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `trade_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('card','cod') NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `trade_id`, `user_id`, `amount`, `method`, `status`, `created_at`, `updated_at`) VALUES
(1, 9, 3, 6.00, 'card', 'completed', '2025-08-01 18:14:12', '2025-08-01 18:14:12'),
(2, 10, 3, 6.00, 'cod', 'completed', '2025-08-01 18:15:44', '2025-08-01 18:15:44'),
(3, 11, 3, 6.00, 'card', 'completed', '2025-08-01 18:27:42', '2025-08-01 18:27:42'),
(4, 12, 3, 6.00, 'cod', 'completed', '2025-08-01 18:33:07', '2025-08-01 18:33:07'),
(5, 19, 3, 10.00, 'cod', 'completed', '2025-08-04 11:37:32', '2025-08-04 11:37:32'),
(6, 20, 11, 6.00, 'cod', 'completed', '2025-08-04 11:38:55', '2025-08-04 11:38:55'),
(7, 23, 11, 6.00, 'cod', 'completed', '2025-08-15 20:53:43', '2025-08-15 20:53:43'),
(8, 25, 3, 10.00, 'cod', 'completed', '2025-08-20 14:49:10', '2025-08-20 14:49:10'),
(9, 26, 3, 6.00, 'cod', 'completed', '2025-08-20 15:04:21', '2025-08-20 15:04:21'),
(10, 28, 11, 10.00, 'cod', 'completed', '2025-08-20 15:15:48', '2025-08-20 15:15:48'),
(11, 30, 11, 6.00, 'cod', 'completed', '2025-08-20 15:20:10', '2025-08-20 15:20:10'),
(12, 31, 11, 10.00, 'cod', 'completed', '2025-08-20 22:03:38', '2025-08-20 22:03:38');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewed_user_id` int(11) NOT NULL,
  `trade_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `reviewer_id`, `reviewed_user_id`, `trade_id`, `rating`, `comment`, `created_at`) VALUES
(1, 4, 5, 2, 5, 'Bob was great to trade with! The guitar is perfect.', '2025-07-23 12:19:30'),
(2, 5, 4, 2, 4, 'Alice delivered a fantastic website design.', '2025-07-23 12:19:30'),
(3, 6, 7, 4, 5, 'David’s tools were exactly what I needed!', '2025-07-25 12:19:30'),
(4, 7, 6, 4, 5, 'Carol’s training sessions were top-notch.', '2025-07-25 12:19:30'),
(5, 1, 5, 7, 5, 'Amazing piano lessons from Bob!', '2025-07-24 12:19:30'),
(6, 5, 1, 7, 4, 'The MacBook is in great condition, thanks!', '2025-07-24 12:19:30'),
(7, 3, 11, 24, 5, 'Good to trade', '2025-08-20 14:21:38'),
(8, 3, 11, 25, 3, 'Good', '2025-08-20 14:53:39'),
(9, 11, 3, 26, 1, 'Not really good', '2025-08-20 15:04:53'),
(10, 3, 11, 26, 3, 'Fine', '2025-08-20 15:05:27'),
(11, 3, 11, 28, 3, '32', '2025-08-20 15:16:15'),
(12, 11, 3, 28, 3, '3', '2025-08-20 15:17:37'),
(13, 11, 3, 30, 2, 'hjk', '2025-08-20 15:25:47'),
(14, 3, 11, 32, 4, 'ghj', '2025-08-24 17:52:42'),
(15, 3, 11, 33, 1, 'From abcddd', '2025-08-24 18:01:06'),
(16, 11, 3, 33, 5, 'From this side', '2025-08-24 18:01:07');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'TradeHub', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(2, 'site_description', 'A community-driven barter marketplace', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(3, 'site_email', 'admin@TradeHub.com', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(4, 'maintenance_mode', '0', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(5, 'user_registration', '1', '2025-08-20 23:53:23', '2025-08-22 22:54:17'),
(6, 'email_verification', '1', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(7, 'max_listings_per_user', '50', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(8, 'max_file_size', '10', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(9, 'allowed_file_types', 'jpg,jpeg,png,gif', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(10, 'smtp_host', 'smtp.gmail.com', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(11, 'smtp_port', '587', '2025-08-20 23:53:23', '2025-08-27 02:56:21'),
(12, 'smtp_username', 'thezulekhacollection@gmail.com', '2025-08-20 23:53:24', '2025-08-27 02:56:21'),
(13, 'smtp_password', 'sany hcbz imdy qafp', '2025-08-20 23:53:24', '2025-08-27 02:56:21'),
(14, 'google_analytics', '', '2025-08-20 23:53:24', '2025-08-27 02:56:21'),
(15, 'facebook_pixel', '', '2025-08-20 23:53:24', '2025-08-27 02:56:21'),
(65, 'maintenance_message', '', '2025-08-22 23:33:19', '2025-08-22 23:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `trades`
--

CREATE TABLE `trades` (
  `id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `offered_item_id` int(11) NOT NULL,
  `requested_item_id` int(11) NOT NULL,
  `status` enum('pending','accepted','declined','completed','cancelled') DEFAULT 'pending',
  `trade_method` enum('company_inspection','meetup','secure_location') DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `requester_completed` tinyint(1) DEFAULT 0,
  `owner_completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trades`
--

INSERT INTO `trades` (`id`, `requester_id`, `owner_id`, `offered_item_id`, `requested_item_id`, `status`, `trade_method`, `message`, `created_at`, `updated_at`, `requester_completed`, `owner_completed`) VALUES
(1, 2, 3, 4, 5, 'completed', NULL, '', '2025-07-22 13:33:05', '2025-07-22 13:34:20', 0, 0),
(2, 4, 5, 7, 8, 'accepted', NULL, 'I can design a website for your music lessons in exchange for the guitar!', '2025-07-22 12:19:30', '2025-07-23 12:19:30', 0, 0),
(3, 5, 6, 9, 11, 'pending', NULL, 'Interested in trading piano lessons for your yoga mat.', '2025-07-23 12:19:30', '2025-07-23 12:19:30', 0, 0),
(4, 6, 7, 10, 13, 'completed', NULL, 'Let’s trade fitness sessions for your tool set!', '2025-07-21 12:19:30', '2025-07-25 12:19:30', 0, 0),
(5, 7, 8, 12, 14, 'declined', NULL, 'Can I fix your car in exchange for some jewelry?', '2025-07-24 12:19:30', '2025-07-25 12:19:30', 0, 0),
(6, 8, 4, 15, 6, 'pending', NULL, 'Would love to design a logo for your iPhone!', '2025-07-25 12:19:30', '2025-07-25 12:19:30', 0, 0),
(7, 1, 5, 2, 9, 'completed', NULL, 'Trading my MacBook for piano lessons.', '2025-07-20 12:19:30', '2025-07-24 12:19:30', 0, 0),
(8, 2, 3, 16, 5, 'completed', NULL, 'I want to trade', '2025-08-01 18:00:55', '2025-08-01 18:12:46', 0, 0),
(9, 2, 3, 16, 5, 'completed', NULL, 'asd', '2025-08-01 18:13:07', '2025-08-01 18:14:39', 0, 0),
(10, 2, 3, 16, 5, 'completed', NULL, 'hggffg', '2025-08-01 18:14:59', '2025-08-01 18:17:42', 0, 0),
(11, 2, 3, 16, 5, 'completed', NULL, 'IDk', '2025-08-01 18:17:16', '2025-08-01 18:27:45', 0, 0),
(12, 2, 3, 16, 5, 'completed', NULL, 'fgh', '2025-08-01 18:32:58', '2025-08-01 18:33:10', 0, 0),
(13, 11, 3, 19, 18, 'cancelled', NULL, 'as', '2025-08-01 23:07:37', '2025-08-15 20:49:09', 0, 0),
(14, 3, 11, 18, 19, 'completed', 'company_inspection', 'sfsfvb', '2025-08-01 23:11:53', '2025-08-04 11:17:24', 0, 0),
(15, 3, 6, 5, 11, 'pending', NULL, 'fghfghfghgf', '2025-08-04 11:17:40', '2025-08-04 11:17:40', 0, 0),
(16, 3, 2, 5, 4, 'pending', NULL, 'dfgdfg', '2025-08-04 11:18:16', '2025-08-04 11:18:16', 0, 0),
(17, 3, 11, 5, 20, 'completed', 'meetup', 'ghj', '2025-08-04 11:19:12', '2025-08-04 11:27:02', 0, 0),
(18, 11, 3, 22, 21, 'completed', 'meetup', 'df', '2025-08-04 11:29:42', '2025-08-15 20:49:04', 0, 0),
(19, 11, 3, 22, 21, 'completed', 'secure_location', 'df', '2025-08-04 11:34:37', '2025-08-15 20:49:01', 0, 0),
(20, 3, 11, 21, 23, 'completed', 'company_inspection', 'l;', '2025-08-04 11:38:35', '2025-08-15 20:48:56', 0, 0),
(21, 3, 11, 21, 23, 'completed', 'meetup', 'sdfsdf', '2025-08-04 11:40:24', '2025-08-15 20:48:59', 0, 0),
(22, 11, 3, 22, 21, 'completed', 'meetup', 'bvhgfhfgh', '2025-08-11 11:08:10', '2025-08-15 20:48:54', 0, 0),
(23, 3, 11, 25, 24, 'completed', 'company_inspection', 'ghj', '2025-08-15 20:53:24', '2025-08-16 17:18:25', 0, 0),
(24, 3, 11, 26, 27, 'completed', 'meetup', 'I would like to trade this', '2025-08-20 13:58:57', '2025-08-20 14:21:38', 0, 0),
(25, 11, 3, 29, 28, 'completed', 'secure_location', 'cvbvcb', '2025-08-20 14:47:46', '2025-08-20 14:53:39', 0, 0),
(26, 11, 3, 31, 30, 'completed', 'company_inspection', 'h', '2025-08-20 15:03:43', '2025-08-20 15:05:27', 0, 0),
(27, 3, 11, 32, 33, 'cancelled', NULL, 'xcv', '2025-08-20 15:14:37', '2025-08-20 21:57:28', 0, 0),
(28, 3, 11, 32, 33, 'completed', 'secure_location', 'm', '2025-08-20 15:15:02', '2025-08-20 15:18:17', 0, 0),
(29, 3, 11, 34, 35, 'cancelled', NULL, 'l;&#039;', '2025-08-20 15:19:13', '2025-08-20 21:57:33', 0, 0),
(30, 3, 11, 34, 35, 'completed', 'company_inspection', 'l;&#039;', '2025-08-20 15:19:19', '2025-08-20 15:25:47', 0, 0),
(31, 3, 11, 36, 37, 'completed', 'secure_location', 'dfg', '2025-08-20 22:03:13', '2025-08-20 22:04:21', 1, 1),
(32, 3, 11, 39, 38, 'completed', 'meetup', 'gjghj', '2025-08-24 17:45:06', '2025-08-24 17:52:42', 0, 0),
(33, 11, 3, 41, 40, 'completed', 'meetup', 'Hiiii', '2025-08-24 17:56:43', '2025-08-24 18:01:06', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `last_seen` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `location`, `bio`, `avatar_url`, `status`, `email_verified`, `created_at`, `updated_at`, `role`, `last_seen`) VALUES
(1, 'Demo User', 'demo@tradecircle.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'San Francisco, CA', 'Welcome to TradeCircle! I love trading and discovering new things.', NULL, 'active', 0, '2025-07-22 13:16:07', '2025-07-22 13:16:07', 'user', NULL),
(2, 'Roarax', 'roarxaxyt@gmail.com', '$2y$10$yQ0EB0R9Puq5g96g8xHgXep2anCQAtkUAm.SNzSukIZZVfU5RCuv.', '123123123', '', '', NULL, 'active', 0, '2025-07-22 13:16:47', '2025-08-01 22:34:12', 'user', NULL),
(3, 'asd', 'asd@gmail.com', '$2y$10$G9MEN2uNV8uSj8.O546ZK.AffMyH2k5f2tRgwjqFO3dfflI3GxUQ2', NULL, NULL, NULL, './uploads/avatars/avatar_3_1756050449.jpeg', 'active', 0, '2025-07-22 13:30:34', '2025-08-27 00:06:34', 'user', '2025-08-27 00:06:34'),
(4, 'Alice Johnson', 'alice.johnson@tradecircle.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0101', 'New York, NY', 'Passionate about tech and trading gadgets!', 'https://example.com/avatars/alice.jpg', 'active', 1, '2025-07-17 12:19:29', '2025-07-27 12:19:29', 'user', NULL),
(5, 'Bob Smith', 'bob.smith@tradecircle.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0102', 'Los Angeles, CA', 'Musician and teacher, love sharing skills.', 'https://example.com/avatars/bob.jpg', 'active', 1, '2025-07-18 12:19:29', '2025-07-27 12:19:29', 'user', NULL),
(6, 'Carol Williams', 'carol.williams@tradecircle.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0103', 'Chicago, IL', 'Fitness enthusiast offering personal training.', 'https://example.com/avatars/carol.jpg', 'active', 0, '2025-07-19 12:19:29', '2025-07-27 12:19:29', 'user', NULL),
(7, 'David Brown', 'david.brown@tradecircle.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0104', 'Austin, TX', 'Car mechanic and DIY lover.', 'https://example.com/avatars/david.jpg', 'active', 1, '2025-07-20 12:19:29', '2025-07-27 12:19:29', 'user', NULL),
(8, 'Emma Davis', 'emma.davis@tradecircle.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0105', 'Seattle, WA', 'Graphic designer with a knack for crafts.', 'https://example.com/avatars/emma.jpg', 'active', 1, '2025-07-21 12:19:29', '2025-07-27 12:19:29', 'user', NULL),
(9, 'Roarax', 'roaraxyst@gmail.com', '$2y$10$.tYFJ22t8LDRMbE0eZpI4.m.IBXlqK2nJjhUIGmMWw5WeNeNC57NG', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-01 22:34:22', '2025-08-01 22:35:03', 'user', NULL),
(10, 'Roarax', 'roaraxyt@gmail.coma', '$2y$10$xOCKrncP9U74PHQ0HkBXWuMlf6A4NkJm8f.BwvE/pLGB1ku3aCcmK', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-01 22:35:06', '2025-08-01 22:36:53', 'user', NULL),
(11, 'Roarax', 'roaraxyt@gmail.com', '$2y$10$EEvxlYSxVUYocGdGqd3gJOLeU1gYOXaAHNV/Jyu2PNoZB6GHo.OJy', NULL, NULL, NULL, './uploads/avatars/avatar_11_1756058488.jpeg', 'active', 1, '2025-08-01 22:36:56', '2025-08-27 02:56:42', 'admin', '2025-08-27 02:56:42'),
(12, 'Roarax', 'roarax@gmail.com', '$2y$10$PP4ZslZ/Qva.PP0Lm8sDkeqVDyUljrvl3qDNzJBfxXm4V6qU1jOE6', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-18 12:28:00', '2025-08-18 12:28:00', 'user', NULL),
(13, 'Roarax', 'roaraxx@gmail.com', '$2y$10$KVu3ls/pkGdK4Pfn926G9.Lvjl78JCGe/C48MaTaiXSOUqMgEN.S6', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-18 12:37:38', '2025-08-18 12:37:38', 'user', NULL),
(14, 'ABCD', 'Abc@gmail.com', '$2y$10$XGQ0MKiPBqSKSVX2smOGfuCu8As9xiQ2ls/tImpvNqY9.DZbZn85u', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-18 12:38:41', '2025-08-18 12:38:41', 'user', NULL),
(15, 'ABCD', 'abcd@gmail.com', '$2y$10$rFJvEakPMFesHBifMUp7W.z6bnH5DkjlGX/VIGFdXFssgz.2V8V4O', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-18 12:40:56', '2025-08-21 13:36:03', 'user', NULL),
(16, 'asd', 'roaraxshorts@gmail.com', '$2y$10$l4LobdvVXgzhbFMEyck7Su97kdH.PQXoeHphqOwn5eLWmdWgkGYuW', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-23 00:12:09', '2025-08-27 01:19:39', 'user', '2025-08-27 01:19:39'),
(17, 'asdasd', 'roaraxxd@gmail.com', '$2y$10$8bjZua97.HACpHFQtE/IXe0zQ86P901Zbqeozn/UWmyeFPmgkGLCO', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-23 00:18:05', '2025-08-23 00:18:05', 'user', NULL),
(18, 'abcd', 'raoraxyt@gmail.com', '$2y$10$mid7Qys7XlmaQLZtOQT3Q.v1wjHsrae4sF9eNtJcm.GHj4pucsXk.', NULL, NULL, NULL, NULL, 'active', 1, '2025-08-23 00:32:38', '2025-08-23 00:32:51', 'user', NULL),
(19, 'ascvb', 'roaraxemma@gmail.com', '$2y$10$bb.mEOCFELvRrpQuBQqG0el7M.hNwc.PPjNjZdwMv5M7W6FCQ4dcC', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-23 00:35:16', '2025-08-23 00:35:16', 'user', NULL),
(20, 'asda', 'thezulekhacollection@gmail.com', '$2y$10$Yxh1h3wQBivqmd160hFBs.vESKtuWDzy93wEPbTy8KgQPoqajavbC', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-23 13:41:11', '2025-08-23 13:41:11', 'user', NULL),
(21, 'zxczxc', 'roarax123@gmail.com', '$2y$10$31C7LxdrdFAFf5hNOyE1WOX2/QsMibOhjagP3MOir.9kAIsoJDuVi', NULL, NULL, NULL, NULL, 'active', 0, '2025-08-23 13:42:49', '2025-08-23 13:42:49', 'user', NULL),
(22, 'Roarax', 'asdd@gmail.com', '$2y$10$kfc/mw6bo/kPdBaxxrX.6eQqtuTuqAxIzL9UpTIKHMgVWbml2Mwnu', '123', NULL, NULL, NULL, 'active', 0, '2025-08-27 01:46:54', '2025-08-27 01:47:19', 'user', NULL),
(23, 'Roarax', 'roarax1234@gmail.com', '$2y$10$.ZNKiX.3moIAMCAMLWG3Tu4bRseb9Uync0jwTBJl7CI9d0T0W6FbW', '12312321', NULL, NULL, NULL, 'active', 0, '2025-08-27 02:21:56', '2025-08-27 02:21:56', 'user', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `credits`
--
ALTER TABLE `credits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trade_id` (`trade_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `email_confirmations`
--
ALTER TABLE `email_confirmations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `listings`
--
ALTER TABLE `listings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);
ALTER TABLE `listings` ADD FULLTEXT KEY `idx_search` (`title`,`description`);

--
-- Indexes for table `listing_views`
--
ALTER TABLE `listing_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_view` (`user_id`,`listing_id`);

--
-- Indexes for table `meetups`
--
ALTER TABLE `meetups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trade_id` (`trade_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_trade` (`trade_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_conversation` (`sender_id`,`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trade_id` (`trade_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`reviewer_id`,`reviewed_user_id`,`trade_id`),
  ADD KEY `trade_id` (`trade_id`),
  ADD KEY `idx_reviewed_user` (`reviewed_user_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `trades`
--
ALTER TABLE `trades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offered_item_id` (`offered_item_id`),
  ADD KEY `requested_item_id` (`requested_item_id`),
  ADD KEY `idx_requester` (`requester_id`),
  ADD KEY `idx_owner` (`owner_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_location` (`location`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `credits`
--
ALTER TABLE `credits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `email_confirmations`
--
ALTER TABLE `email_confirmations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `listings`
--
ALTER TABLE `listings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `listing_views`
--
ALTER TABLE `listing_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `meetups`
--
ALTER TABLE `meetups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=233;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

--
-- AUTO_INCREMENT for table `trades`
--
ALTER TABLE `trades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `credits`
--
ALTER TABLE `credits`
  ADD CONSTRAINT `credits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credits_ibfk_2` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_confirmations`
--
ALTER TABLE `email_confirmations`
  ADD CONSTRAINT `email_confirmations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `listings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meetups`
--
ALTER TABLE `meetups`
  ADD CONSTRAINT `meetups_ibfk_1` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meetups_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewed_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trades`
--
ALTER TABLE `trades`
  ADD CONSTRAINT `trades_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trades_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trades_ibfk_3` FOREIGN KEY (`offered_item_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trades_ibfk_4` FOREIGN KEY (`requested_item_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
