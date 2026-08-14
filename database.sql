-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 12, 2026 at 04:19 AM
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
-- Database: `asafhairsalon`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_duration` int(11) NOT NULL,
  `service_price` decimal(10,2) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('confirmed','cancelled','completed','no_show') DEFAULT 'confirmed',
  `cancel_token` varchar(64) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `slot_key` varchar(32) GENERATED ALWAYS AS (concat(`appointment_date`,'_',`start_time`)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `service_id`, `service_name`, `service_duration`, `service_price`, `customer_name`, `customer_phone`, `customer_email`, `appointment_date`, `start_time`, `end_time`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 9, 'צבע מלא', 90, 400.00, 'בני מימון', '0528529448', 'maimonov@gmail.com', '2026-05-08', '09:00:00', '10:30:00', 'confirmed', NULL, '2026-05-07 23:02:35', '2026-05-07 23:02:35'),
(2, 1, 'תספורת נשים', 60, 120.00, 'benny', '065434567', 'maimonov@gmail.com', '2026-05-08', '11:00:00', '12:00:00', 'confirmed', NULL, '2026-05-07 23:14:06', '2026-05-07 23:14:06'),
(3, 3, 'תספורת פן בלבד', 30, 70.00, 'benny', '065345667', 'maimonov@gmail.com', '2026-05-13', '09:00:00', '09:30:00', 'confirmed', NULL, '2026-05-07 23:19:46', '2026-05-07 23:19:46'),
(4, 1, 'תספורת נשים', 60, 120.00, 'benny', '052345687', 'maimonov@gmail.com', '2026-05-21', '09:00:00', '10:00:00', 'confirmed', NULL, '2026-05-07 23:22:56', '2026-05-07 23:22:56'),
(5, 13, 'החלקה יפנית', 150, 1050.00, 'Benny', '0528529448', 'maimonov@gmail.com', '2026-05-11', '09:00:00', '11:30:00', 'confirmed', NULL, '2026-05-11 16:39:22', '2026-05-11 16:39:22'),
(6, 1, 'תספורת נשים', 60, 120.00, 'benny', '0525852589', 'maimonov@gmail.com', '2026-05-11', '12:30:00', '13:30:00', 'confirmed', NULL, '2026-05-11 17:20:17', '2026-05-11 17:20:17');

-- --------------------------------------------------------

--
-- Table structure for table `before_after`
--

CREATE TABLE `before_after` (
  `id` int(11) NOT NULL,
  `title_he` varchar(255) NOT NULL,
  `before_image` varchar(500) NOT NULL,
  `after_image` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `before_after`
--

INSERT INTO `before_after` (`id`, `title_he`, `before_image`, `after_image`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'החלקה יפנית', 'uploads/before-after/before_1786096894_98afc7ba.webp', 'uploads/before-after/after_1786096894_58493aab.webp', NULL, 0, 1, '2026-08-07 10:01:34', '2026-08-07 10:01:34'),
(2, 'בחלקה נורווגית', 'uploads/before-after/before_1786096922_dd141cdd.webp', 'uploads/before-after/after_1786096922_c1b91f6e.webp', NULL, 0, 1, '2026-08-07 10:02:02', '2026-08-07 10:02:02');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name_he` varchar(100) NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#c8a97e',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name_he`, `name_en`, `icon`, `color`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'תספורות ועיצוב שיער', 'Haircuts & Styling', '✂️', '#c8a97e', 1, 1, '2025-12-03 23:11:38'),
(2, 'צבע שיער', 'Hair Coloring', '🎨', '#8b7355', 2, 1, '2025-12-03 23:11:38'),
(3, 'החלקות וטיפולים', 'Straightening & Treatments', '🔆', '#a8926f', 3, 1, '2025-12-03 23:11:38'),
(4, 'תוספות שיער', 'Hair Extensions', '💇‍♀️', '#c8a97e', 4, 1, '2025-12-03 23:11:38'),
(5, 'חבילות ואירועים', 'Packages & Events', '🎁', '#8b7355', 5, 1, '2025-12-03 23:11:38');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(3, 'maimonov@gmail.com', '8c1877feb5f85fc6eb4663062e67730dea329b41887c39aea48630cb73183383cd75fafbf9be04feb6edca136344c3e86e2c', '2026-08-07 12:58:26', '2026-08-07 09:58:26');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `duration` int(11) NOT NULL COMMENT 'משך בדקות',
  `base_price` decimal(10,2) NOT NULL,
  `materials_fee` decimal(10,2) DEFAULT 0.00,
  `popular` tinyint(1) DEFAULT 0,
  `featured` tinyint(1) DEFAULT 0,
  `requires_consultation` tinyint(1) DEFAULT 0,
  `min_preparation_time` int(11) DEFAULT 0,
  `max_clients_per_slot` int(11) DEFAULT 1,
  `image_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category_id`, `title`, `description`, `short_description`, `duration`, `base_price`, `materials_fee`, `popular`, `featured`, `requires_consultation`, `min_preparation_time`, `max_clients_per_slot`, `image_url`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'תספורת נשים', 'תספורת מקצועית כולל ייעוץ, גזירה, שטיפה, ייבוש ועיצוב סופי', 'תספורת מלאה עם עיצוב', 60, 120.00, 0.00, 1, 1, 0, 0, 1, 'uploads/services/svc_1786497255_6df373c6.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:14:15'),
(2, 1, 'תספורת ילדות', 'תספורת לילדות עד גיל 12 עם גישה מיוחדת לילדים וכיסא מיוחד', 'תספורת ילדות עד גיל 12', 45, 90.00, 0.00, 1, 0, 0, 0, 1, 'uploads/services/svc_1786497249_b01c1862.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:14:09'),
(3, 1, 'תספורת פן בלבד', 'עיצוב ושיוף פנים בלבד ללא שטיפה', 'עיצוב פנים בלבד', 30, 70.00, 0.00, 0, 0, 0, 0, 1, 'uploads/services/svc_1786497240_6bada8b4.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:14:00'),
(4, 1, 'תספורת שיער קצר', 'תספורת ועיצוב מקצועי לשיער קצר מאוד עם תשומת לב לפרטים', 'תספורת לשיער קצר', 45, 100.00, 0.00, 1, 0, 0, 0, 1, 'uploads/services/svc_1786497230_e12aeaae.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:13:50'),
(5, 1, 'תסרוקת אירוע', 'עיצוב תסרוקת מיוחדת לאירועים, חתונות ואירועים מיוחדים', 'תסרוקת לאירועים מיוחדים', 90, 250.00, 50.00, 0, 1, 0, 0, 1, 'uploads/services/svc_1786497223_72920e74.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:13:43'),
(6, 1, 'עיצוב תלתולים', 'טיפול ועיצוב לשיער מתולתל - סלסול או יישור', 'עיצוב לשיער מתולתל', 60, 150.00, 20.00, 1, 0, 0, 0, 1, 'uploads/services/svc_1786497217_06e852d2.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:13:37'),
(7, 1, 'תספורת גברית', 'תספורת גברים כולל מכונה, מספריים וגילוח קצוות', 'תספורת גברים', 45, 80.00, 0.00, 1, 0, 0, 0, 1, 'uploads/services/svc_1786497207_345ef1fc.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:13:27'),
(8, 2, 'צבע שורשים', 'צביעת שורשים בלבד עם צבע איכותי נטול אמוניה', 'צבע שורשים בלבד', 60, 150.00, 80.00, 1, 1, 1, 0, 1, 'uploads/services/svc_1786497202_b9d72987.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:13:22'),
(9, 2, 'צבע מלא', 'צביעת שיער מלאה כולל שורשים ואורך עם צבע איכותי', 'צבע מלא לשיער', 90, 250.00, 150.00, 1, 1, 1, 0, 1, 'uploads/services/svc_1786497196_25fb51a1.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:13:16'),
(10, 2, 'הילייט מלא', 'הבהרה מקצועית מלאה בשיטת foil עם הבהרה מקצועית', 'הבהרה מלאה', 120, 350.00, 200.00, 1, 1, 1, 0, 1, 'uploads/services/svc_1786497185_74bf2935.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:13:05'),
(11, 2, 'הילייט חלקי', 'הבהרה בחלק העליון של הראש בלבד', 'הבהרה חלקית', 90, 220.00, 120.00, 1, 0, 1, 0, 1, 'uploads/services/svc_1786497181_0ba3ca0f.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:13:01'),
(12, 3, 'החלקה ברזילאית', 'החלקה מלאה עם מרכיבים טבעיים מברזיל', 'החלקה מלאה ברזילאית', 180, 600.00, 300.00, 1, 1, 1, 0, 1, 'uploads/services/svc_1786497174_7719e02a.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:12:54'),
(13, 3, 'החלקה יפנית', 'החלקה אורגנית ועדינה במיוחד בשיטה היפנית', 'החלקה יפנית אורגנית', 150, 700.00, 350.00, 0, 1, 1, 0, 1, 'uploads/services/svc_1786497165_299a05b4.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:12:45'),
(14, 3, 'החלקת קראטין', 'טיפול קראטין לחיזוק והחלקת השיער', 'טיפול קראטין להחלקה', 120, 500.00, 250.00, 1, 0, 1, 0, 1, 'uploads/services/svc_1786497160_cd353d1c.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:12:40'),
(15, 3, 'טיפול החלקה חודשי', 'תחזוקה והחלקה להחלקה קיימת', 'תחזוקה להחלקה', 60, 150.00, 80.00, 1, 0, 0, 0, 1, 'uploads/services/svc_1786497134_a10fb79b.webp', NULL, 1, '2025-12-03 23:11:38', '2026-08-12 01:12:14'),
(16, 4, 'הרחבת שיער בקליפים', 'הרחבה בשיטת קליפים (ניתן להסיר לבד) - לא קבועה', 'הרחבה בקליפים', 120, 400.00, 600.00, 1, 1, 1, 0, 1, 'uploads/services/svc_1786497126_644a4957.webp', 'לא קבוע - להסרה עצמית', 1, '2025-12-03 23:11:38', '2026-08-12 01:12:06'),
(17, 4, 'הרחבת שיער במיקרו-רינג', 'הרחבה בשיטת מיקרו-רינג קבועה ואיכותית', 'הרחבה במיקרו-רינג', 180, 800.00, 1200.00, 1, 1, 1, 0, 1, 'uploads/services/svc_1786497117_aeeffaf8.webp', 'מחזיקה 3-4 חודשים', 1, '2025-12-03 23:11:38', '2026-08-12 01:11:57'),
(18, 4, 'הרחבת שיער בקרניבן', 'הרחבה בשיטת הקרניבן האיטלקית המתקדמת', 'הרחבה בקרניבן', 200, 900.00, 1400.00, 0, 1, 1, 0, 1, 'uploads/services/svc_1786497111_6f2b69a5.webp', 'מתאים לשיער עבה', 1, '2025-12-03 23:11:38', '2026-08-12 01:11:51'),
(19, 4, 'הרחבת שיער בקשר', 'הרחבה בשיטת הקשר הקוריאני העדינה', 'הרחבה בקשר', 160, 750.00, 1100.00, 1, 0, 1, 0, 1, 'uploads/services/svc_1786497106_6bf91f3c.webp', 'שיטה עדינה לשיער דק', 1, '2025-12-03 23:11:38', '2026-08-12 01:11:46'),
(20, 5, 'חבילת כלה מלאה', 'תספורת, איפור כלה, מניקור, פדיקור והכנה מלאה לחתונה', 'חבילת כלה שלמה', 240, 900.00, 200.00, 1, 1, 1, 0, 1, 'uploads/services/svc_1786497096_ffdcaade.webp', 'כולל ניסוי מקדים חינם', 1, '2025-12-03 23:11:38', '2026-08-12 01:11:36'),
(21, 5, 'חבילת פינוק מלאה', 'תספורת, צבע מלא וטיפול פנים מפנק', 'חבילת פינוק', 210, 750.00, 150.00, 1, 0, 0, 0, 1, 'uploads/services/svc_1786497089_919d61ee.webp', 'מתנה מושלמת ליום הולדת', 1, '2025-12-03 23:11:38', '2026-08-12 01:11:29'),
(22, 5, 'חבילת כלה בסיסית', 'תספורת ואיפור כלה בלבד ללא ציפורניים', 'חבילת כלה בסיסית', 150, 600.00, 150.00, 1, 0, 1, 0, 1, 'uploads/services/svc_1786497078_42adf679.webp', 'ללא טיפולי ציפורניים', 1, '2025-12-03 23:11:38', '2026-08-12 01:11:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','editor','viewer') DEFAULT 'viewer',
  `avatar_color` varchar(7) DEFAULT '#c8a97e',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `role`, `avatar_color`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$GHfrzSGxaHAxwwsyn.33rO213MWwwJSRyYkEW6havmgM9l6Mfqd.a', 'מנהל ראשי', 'maimonov@gmail.com', 'admin', '#c8a97e', 1, '2025-12-03 23:11:38', '2026-08-07 09:59:38');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot_key` (`slot_key`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_customer_phone` (`customer_phone`);

--
-- Indexes for table `before_after`
--
ALTER TABLE `before_after`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sort` (`sort_order`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_popular` (`popular`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_price` (`base_price`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `before_after`
--
ALTER TABLE `before_after`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
