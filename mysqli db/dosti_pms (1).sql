-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2026 at 11:44 AM
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
-- Database: `dosti_pms`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sheet_logs`
--

CREATE TABLE `attendance_sheet_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_snapshot` varchar(50) NOT NULL,
  `department_id_snapshot` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `sign_in_at` datetime NOT NULL,
  `sign_out_at` datetime DEFAULT NULL,
  `sign_out_method` enum('Manual','Automatic') NOT NULL DEFAULT 'Manual',
  `activity_report` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_sheet_logs`
--

INSERT INTO `attendance_sheet_logs` (`id`, `user_id`, `role_snapshot`, `department_id_snapshot`, `attendance_date`, `sign_in_at`, `sign_out_at`, `sign_out_method`, `activity_report`, `created_at`) VALUES
(1, 38, 'Super Admin', 0, '2026-04-18', '2026-04-18 12:21:43', '2026-04-18 12:21:49', 'Manual', 'sfsdsaf', '2026-04-18 10:21:43'),
(7, 68, 'Team Member', 0, '2026-04-23', '2026-04-23 08:15:40', '2026-04-23 16:30:51', 'Manual', 'radiator-services-london page completed with mobile tablet responsiveness\r\nBathroom wala page pora set kiya with responsiveness', '2026-04-23 08:15:40'),
(8, 67, 'Team Member', 0, '2026-04-23', '2026-04-23 08:15:46', '2026-04-23 19:29:26', 'Manual', 'for revision I watch a playlist about integration of rest ai in flutter project. \r\nthan I integrate local Hive database in DiaStep app for storing history of scan result . \r\nand there were some crashes in app I removed that.', '2026-04-23 08:15:46'),
(9, 73, 'Team Member', 0, '2026-04-23', '2026-04-23 09:08:36', '2026-04-23 23:30:00', 'Automatic', 'Session auto-closed by system at 11:30 PM due to missed sign-out.', '2026-04-23 09:08:36'),
(10, 71, 'Team Member', 0, '2026-04-23', '2026-04-23 09:12:45', '2026-04-23 23:30:00', 'Automatic', 'Session auto-closed by system at 11:30 PM due to missed sign-out.', '2026-04-23 09:12:45'),
(11, 76, 'Team Member', 0, '2026-04-23', '2026-04-23 09:25:34', '2026-04-23 23:30:00', 'Automatic', 'Session auto-closed by system at 11:30 PM due to missed sign-out.', '2026-04-23 09:25:34'),
(12, 38, 'Team Member', 27, '2026-04-23', '2026-04-23 09:34:49', '2026-04-23 20:32:30', 'Manual', 'Updated the user booking process Step 1 with improved category design and made it dynamic from the admin panel.\r\nAdded a yearly calendar in the hall partner dashboard for better planning and tracking.\r\nProfile editing features for both super admin and hall users.', '2026-04-23 09:34:49'),
(13, 77, 'Team Member', 27, '2026-04-23', '2026-04-23 09:43:02', '2026-04-23 20:34:45', 'Manual', 'docbrand completed', '2026-04-23 09:43:02'),
(15, 78, 'Team Member', 27, '2026-04-23', '2026-04-23 15:11:08', '2026-04-23 23:30:00', 'Automatic', 'Session auto-closed by system at 11:30 PM due to missed sign-out.', '2026-04-23 10:11:08'),
(16, 68, 'Team Lead', 27, '2026-04-24', '2026-04-24 09:26:38', '2026-04-24 16:31:04', 'Manual', 'Block Drainage page half done, kuch sections and responsiveness rehti hy will be completed by tomorrow', '2026-04-24 04:26:38'),
(17, 79, 'Team Member', 28, '2026-04-24', '2026-04-24 09:29:01', '2026-04-24 16:32:26', 'Manual', 'i have completed today assigned task.', '2026-04-24 04:29:01'),
(18, 83, 'Team Member', 26, '2026-04-24', '2026-04-24 09:34:32', '2026-04-24 17:03:51', 'Manual', 'Gig images is completed i will submit it later', '2026-04-24 04:34:32'),
(19, 71, 'Team Member', 27, '2026-04-24', '2026-04-24 09:42:35', '2026-04-24 17:27:05', 'Manual', 'worked in wordpress website and also worked in assigned task', '2026-04-24 04:42:35'),
(20, 77, 'Team Member', 27, '2026-04-24', '2026-04-24 09:51:17', '2026-04-24 20:56:35', 'Manual', 'work in dewgi on Meta Tags and meta descriptions seo. and learn about meta boxes in jet engine', '2026-04-24 04:51:17'),
(21, 73, 'Team Member', 27, '2026-04-24', '2026-04-24 10:48:28', '2026-04-24 17:23:49', 'Manual', 'No task assigned today.', '2026-04-24 05:48:28'),
(22, 67, 'Team Member', 29, '2026-04-24', '2026-04-24 11:23:55', '2026-04-24 19:25:31', 'Manual', 'today app report integration of local datase complete for history or last scan , show the last summary scan on home screen last scan  summary card. \r\nApi integration real products appear in shop screen. \r\nInshallah tomorrow the product details screen and products order on whatsApp will be cover.\r\nand sir wo whatsapp  number bhi phir share kardain .. ju app main integrate hona hain\r\nand I Upload the updated file please do update the app all the changes will appear in app.', '2026-04-24 06:23:55'),
(23, 80, 'Team Member', 26, '2026-04-24', '2026-04-24 13:05:31', '2026-04-24 16:32:12', 'Manual', 'Today I continued with the Adobe Photoshop playlist focusing on the clone mask tool\r\nI also improved my skills with the lasso, marquee selection, and healing spot brush tools', '2026-04-24 08:05:31'),
(24, 69, 'Team Member', 27, '2026-04-24', '2026-04-24 13:05:49', '2026-04-24 23:30:00', 'Automatic', 'Session auto-closed by system at 11:30 PM due to missed sign-out.', '2026-04-24 08:05:49'),
(25, 38, 'Team Member', 27, '2026-04-24', '2026-04-24 13:38:00', '2026-04-24 16:41:33', 'Manual', 'Hall Booking:\r\nNew Feature added,\r\nbugs solved,\r\nmobile layout solved.\r\nproject complete my side.', '2026-04-24 08:38:00'),
(26, 78, 'Team Member', 27, '2026-04-24', '2026-04-24 14:38:26', '2026-04-24 17:17:01', 'Manual', 'hostinger websites upgrade the theme and plugins and watched for how to create my account page in wordpress', '2026-04-24 09:38:26'),
(27, 70, 'Team Member', 26, '2026-04-24', '2026-04-24 17:19:53', '2026-04-24 17:20:12', 'Manual', 'I was assigned with 5 pages of / Elite Home renovators for today and I have completed the task.', '2026-04-24 12:19:53'),
(28, 74, 'Team Member', 26, '2026-04-24', '2026-04-24 19:13:23', '2026-04-24 20:57:49', 'Manual', 'today i learn photoshop and make SEO gigs and i sign in on 11:00 am but on discord', '2026-04-24 14:13:23'),
(29, 71, 'Team Member', 27, '2026-04-25', '2026-04-25 08:59:57', NULL, 'Manual', NULL, '2026-04-25 03:59:57'),
(30, 68, 'Team Lead', 27, '2026-04-25', '2026-04-25 09:06:38', NULL, 'Manual', NULL, '2026-04-25 04:06:38'),
(31, 69, 'Team Member', 27, '2026-04-25', '2026-04-25 09:19:11', NULL, 'Manual', NULL, '2026-04-25 04:19:11'),
(32, 83, 'Team Member', 26, '2026-04-25', '2026-04-25 09:31:23', NULL, 'Manual', NULL, '2026-04-25 04:31:23'),
(33, 73, 'Team Member', 27, '2026-04-25', '2026-04-25 09:31:59', NULL, 'Manual', NULL, '2026-04-25 04:31:59'),
(34, 76, 'Team Member', 27, '2026-04-25', '2026-04-25 09:50:33', NULL, 'Manual', NULL, '2026-04-25 04:50:33'),
(35, 80, 'Team Member', 26, '2026-04-25', '2026-04-25 10:03:45', NULL, 'Manual', NULL, '2026-04-25 05:03:45'),
(36, 77, 'Team Member', 27, '2026-04-25', '2026-04-25 10:58:42', NULL, 'Manual', NULL, '2026-04-25 05:58:42'),
(37, 74, 'Team Member', 26, '2026-04-25', '2026-04-25 11:32:24', NULL, 'Manual', NULL, '2026-04-25 06:32:24'),
(38, 38, 'Team Member', 27, '2026-04-25', '2026-04-25 11:33:15', NULL, 'Manual', NULL, '2026-04-25 06:33:15'),
(39, 78, 'Team Member', 27, '2026-04-25', '2026-04-25 11:53:01', NULL, 'Manual', NULL, '2026-04-25 06:53:01'),
(40, 67, 'Team Member', 29, '2026-04-25', '2026-04-25 12:02:09', NULL, 'Manual', NULL, '2026-04-25 07:02:09');

-- --------------------------------------------------------

--
-- Table structure for table `chat_groups`
--

CREATE TABLE `chat_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `picture_url` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_groups`
--

INSERT INTO `chat_groups` (`id`, `name`, `created_by`, `created_at`, `picture_url`, `updated_at`) VALUES
(1, 'test group 2', 38, '2026-03-03 07:23:13', 'uploads/groups/group_69ac7c98e7bac.png', '2026-03-07 19:29:28'),
(2, 'hamza', 38, '2026-03-04 05:56:24', NULL, NULL),
(3, 'Design Team', 38, '2026-03-04 06:08:18', NULL, NULL),
(4, 'Global – All Members', 38, '2026-03-04 06:26:31', 'uploads/groups/group_69ac7e1e35993.webp', '2026-03-07 19:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `chat_group_members`
--

CREATE TABLE `chat_group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('Admin','Member') NOT NULL DEFAULT 'Member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `attachment_url` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `head_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `head_id`, `created_at`) VALUES
(26, 'Graphic Design', NULL, '2026-04-22 11:34:20'),
(27, 'Wordpress Developer', NULL, '2026-04-22 11:34:25'),
(28, 'Ai-Department', NULL, '2026-04-23 09:35:13'),
(29, 'App-Developer', NULL, '2026-04-23 09:35:21'),
(30, 'SEO', NULL, '2026-04-23 09:58:26');

-- --------------------------------------------------------

--
-- Table structure for table `major_project_departments`
--

CREATE TABLE `major_project_departments` (
  `major_project_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `major_project_members`
--

CREATE TABLE `major_project_members` (
  `major_project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `major_project_members`
--

INSERT INTO `major_project_members` (`major_project_id`, `user_id`) VALUES
(61, 68),
(61, 73),
(61, 76),
(62, 38),
(63, 76),
(63, 77),
(64, 70),
(64, 74),
(64, 80),
(64, 83),
(65, 67),
(66, 74),
(66, 80),
(66, 83);

-- --------------------------------------------------------

--
-- Table structure for table `mention_notifications`
--

CREATE TABLE `mention_notifications` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `mentioned_user_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_logs`
--

CREATE TABLE `performance_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `score_change` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `performance_logs`
--

INSERT INTO `performance_logs` (`id`, `user_id`, `task_id`, `score_change`, `reason`, `timestamp`) VALUES
(4, 1, NULL, -10, 'Task Missed: Finalize Blueprints', '2026-02-18 06:42:38'),
(5, 1, NULL, 0, 'assigned task \'aap ajj kay din 20 boxes laga du\' to user ID 40 (Project ID: 13)', '2026-02-18 07:15:10'),
(6, 40, NULL, 2, 'On-Time Completion', '2026-02-18 07:24:01'),
(7, 40, NULL, 2, 'On-Time Completion', '2026-02-18 07:31:22'),
(8, 40, NULL, 2, 'On-Time Completion', '2026-02-18 07:59:21'),
(9, 40, NULL, -10, 'Task Missed: aap ajj kay din 20 boxes laga du', '2026-02-23 04:52:29'),
(10, 1, NULL, 0, 'assigned task \'create a draf document\' to user ID 41 (Project ID: 23)', '2026-02-23 04:57:59'),
(11, 1, NULL, 0, 'assigned task \'Tomarrow hom 20 school banye gi\' to user ID 44 (Project ID: 32)', '2026-02-23 07:08:45'),
(12, 1, NULL, 0, 'assigned task \'landikotal mein schol oopen karu\' to user ID 44 (Project ID: 32)', '2026-02-23 07:15:44'),
(13, 44, NULL, 2, 'On-Time Completion', '2026-02-23 07:16:12'),
(14, 44, NULL, 5, 'Early Completion', '2026-02-23 07:26:02'),
(15, 44, NULL, 5, 'Early Completion', '2026-02-23 07:26:07'),
(16, 44, NULL, 5, 'Early Completion', '2026-02-23 07:26:23'),
(17, 44, NULL, 2, 'On-Time Completion', '2026-02-23 07:39:22'),
(18, 1, NULL, 0, 'assigned task \'Test Task by Dept Head\' to user ID 38 (Project ID: 39)', '2026-02-24 06:44:27'),
(19, 1, NULL, 0, 'assigned task \'jsdfskj\' to user ID 44 (Project ID: 41)', '2026-02-24 06:58:39'),
(20, 44, NULL, 2, 'On-Time Completion', '2026-02-24 06:59:48'),
(21, 1, NULL, 0, 'assigned task \'safd\' to user ID 44 (Project ID: 41)', '2026-02-24 09:06:34'),
(22, 38, NULL, 0, 'assigned task \'dfgsdgdf\' to user ID 43 (Direct Task)', '2026-03-03 06:19:04'),
(23, 38, NULL, 0, 'assigned task \'dfgsdgdf\' to user ID 44 (Direct Task)', '2026-03-03 06:19:04'),
(24, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 43', '2026-03-03 06:32:07'),
(25, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 44', '2026-03-03 06:32:07'),
(26, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 43', '2026-03-03 06:32:07'),
(27, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 44', '2026-03-03 06:32:07'),
(28, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 43', '2026-03-03 06:32:07'),
(29, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 44', '2026-03-03 06:32:07'),
(30, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 43', '2026-03-03 06:32:07'),
(31, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 44', '2026-03-03 06:32:07'),
(32, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 43', '2026-03-03 06:32:08'),
(33, 38, NULL, 0, 'assigned Direct Task \'fsdsf\' to user ID 44', '2026-03-03 06:32:08'),
(34, 38, NULL, 0, 'assigned Direct Task \'zzz\' to user ID 44', '2026-03-03 06:32:49'),
(35, 38, NULL, 0, 'assigned Direct Task \'zzz\' to user ID 45', '2026-03-03 06:32:49'),
(36, 38, NULL, 0, 'assigned task \'sdfsf\' to user ID  (Project ID: 41)', '2026-03-03 06:33:57'),
(37, 38, NULL, 0, 'assigned Direct Task \'fsdaf\' to user ID 43', '2026-03-03 06:40:42'),
(38, 38, NULL, 0, 'assigned Direct Task \'234234\' to user ID 45', '2026-03-03 06:45:10'),
(39, 44, NULL, -10, 'Task Missed: jsdfskj', '2026-03-03 06:58:12'),
(40, 44, NULL, -10, 'Task Missed: safd', '2026-03-03 06:58:12'),
(41, 38, NULL, 0, 'assigned task \'sdfsd\' to user ID 44 (Project ID: 45)', '2026-03-04 06:43:11'),
(42, 38, NULL, 0, 'assigned task \'sfasd\' to user ID 44 (Project ID: 45)', '2026-03-04 06:49:27'),
(43, 38, NULL, 0, 'assigned Direct Task \'dsfsaf\' to user ID 44', '2026-03-04 06:57:45'),
(44, 38, NULL, 0, 'assigned Direct Task \'dsad\' to user ID 44', '2026-03-04 07:27:31'),
(45, 44, NULL, -5, 'Delayed Completion', '2026-03-04 07:54:19'),
(46, 38, NULL, 0, 'assigned task \'jawad sub projec task\' to user ID 44 (Project ID: 41)', '2026-03-04 08:45:10'),
(47, 38, NULL, 0, 'assigned task \'jawad sub project task\' to user ID 44 (Project ID: 41)', '2026-03-04 08:45:57'),
(48, 38, NULL, 0, 'assigned Direct Task \'jawd direct task\' to user ID 44', '2026-03-04 08:46:17'),
(49, 38, NULL, 0, 'assigned Direct Task \'qqqwww\' to user ID 44', '2026-03-04 08:47:02'),
(50, 38, NULL, 0, 'assigned Direct Task \'dsdfdf\' to user ID 43', '2026-03-04 08:58:53'),
(51, 38, NULL, 0, 'assigned Direct Task \'dsdfdf\' to user ID 44', '2026-03-04 08:58:53'),
(52, 38, NULL, 0, 'assigned task \'sdfd\' to user ID 50 (Project ID: 45)', '2026-03-04 08:59:13'),
(53, 38, NULL, 0, 'assigned Direct Task \'sdfadf\' to user ID 43', '2026-03-04 09:12:49'),
(54, 38, NULL, 0, 'assigned Direct Task \'assf\' to user ID 43', '2026-03-04 09:49:09'),
(55, 38, NULL, 0, 'assigned Direct Task \'cvc\' to user ID 53', '2026-03-04 10:11:05'),
(56, 44, NULL, -5, 'Delayed Completion', '2026-03-05 04:36:47'),
(57, 44, NULL, -5, 'Delayed Completion', '2026-03-05 04:39:27'),
(58, 44, NULL, -5, 'Delayed Completion', '2026-03-05 04:39:31'),
(59, 43, NULL, 0, 'assigned Direct Task \'fasdsdf\' to user ID 44', '2026-03-05 05:23:19'),
(60, 38, NULL, 0, 'assigned Direct Task \'sdfasd\' to user ID 44', '2026-03-05 06:16:24'),
(61, 44, NULL, -5, 'Delayed Completion', '2026-03-05 06:21:41'),
(62, 38, NULL, 0, 'assigned task \'ffasd\' to user ID 52 (Project ID: 39)', '2026-03-05 06:22:18'),
(63, 52, NULL, -5, 'Delayed Completion', '2026-03-05 06:22:22'),
(64, 38, NULL, 0, 'assigned Direct Task \'dsgdg\' to user ID 43', '2026-03-05 06:30:30'),
(65, 38, NULL, 0, 'assigned Direct Task \'new direct task\' to user ID 43', '2026-03-05 06:31:21'),
(66, 38, NULL, 0, 'assigned Direct Task \'amra task\' to user ID 53', '2026-03-05 06:32:31'),
(67, 38, NULL, 0, 'assigned task \'sdfd\' to user ID 53 (Project ID: 47)', '2026-03-05 06:47:21'),
(68, 53, NULL, -5, 'Delayed Completion', '2026-03-05 06:47:24'),
(69, 43, NULL, 0, 'assigned Direct Task \'sdafd\' to user ID 44', '2026-03-06 17:20:22'),
(70, 43, NULL, 0, 'assigned Direct Task \'departent task create\' to user ID 44', '2026-03-06 17:21:16'),
(71, 43, NULL, 0, 'assigned task \'fklasjkl\' to user ID 38 (Project ID: 48)', '2026-03-06 17:41:46'),
(72, 38, NULL, -5, 'Delayed Completion', '2026-03-06 18:04:45'),
(73, 38, NULL, 0, 'assigned task \'first school in karkhano\' to user ID 44 (Project ID: 50)', '2026-03-06 18:10:50'),
(74, 44, NULL, 5, 'Early Completion', '2026-03-06 18:11:48'),
(75, 43, NULL, 0, 'assigned Direct Task \'new task check\' to user ID 44', '2026-03-06 18:19:58'),
(76, 38, NULL, 0, 'assigned task \'projeect taskkdk\' to user ID 43 (Project ID: 47)', '2026-03-07 02:10:21'),
(77, 43, NULL, 0, 'assigned Direct Task \'hamza create a own team task\' to user ID 44', '2026-03-07 02:16:35'),
(78, 43, NULL, 0, 'assigned Direct Task \'other task\' to user ID 53', '2026-03-07 02:55:19'),
(79, 52, NULL, 0, 'assigned Direct Task \'create own team task\' to user ID 53', '2026-03-07 03:10:52'),
(80, 43, NULL, 0, 'assigned Direct Task \'jawad task check\' to user ID 44', '2026-03-07 05:37:07'),
(81, 43, NULL, 0, 'assigned Direct Task \'mamu task\' to user ID 44', '2026-03-07 05:58:56'),
(82, 43, NULL, 0, 'assigned Direct Task \'expied tasjk\' to user ID 44', '2026-03-07 18:57:07'),
(83, 38, NULL, 0, 'assigned task \'new clendar task show\' to user ID 44 (Project ID: 47)', '2026-03-08 06:26:18'),
(84, 52, NULL, 0, 'assigned Direct Task \'arif task assign to hamza\' to user ID 43', '2026-03-08 17:59:26'),
(85, 52, NULL, 0, 'assigned Direct Task \'arif task assign to hamza\' to user ID 44', '2026-03-08 17:59:26'),
(86, 44, NULL, -10, 'Task Missed: new clendar task show', '2026-03-09 08:55:54'),
(87, 38, NULL, 0, 'assigned task \'ready to front page\' to user ID 43 (Project ID: 54)', '2026-03-09 10:45:32'),
(88, 43, NULL, 0, 'assigned Direct Task \'amar task\' to user ID 53', '2026-03-09 10:47:26'),
(89, 43, NULL, 0, 'assigned Direct Task \'hgggh\' to user ID 44', '2026-03-10 05:06:35'),
(90, 38, NULL, 0, 'assigned task \'aetr\' to user ID 56 (Project ID: 56)', '2026-04-17 08:20:27'),
(91, 38, NULL, 0, 'assigned Direct Task \'afsdds\' to user ID 56', '2026-04-17 08:21:04'),
(92, 38, NULL, 0, 'assigned task \'afsdf\' to user ID 57 (Project ID: 57)', '2026-04-17 09:12:55'),
(93, 38, NULL, 0, 'assigned Direct Task \'asff\' to user ID 57', '2026-04-17 09:13:45'),
(94, 38, NULL, 0, 'assigned task \'notficaiton project task\' to user ID 57 (Project ID: 57)', '2026-04-18 12:44:46'),
(95, 38, NULL, 0, 'assigned task \'new notificaoint systm gask\' to user ID 57 (Project ID: 57)', '2026-04-18 12:55:19'),
(96, 38, NULL, 0, 'assigned task \'asfds\' to user ID 57 (Project ID: 57)', '2026-04-18 12:55:49'),
(97, 38, NULL, 0, 'assigned task \'asdfff\' to user ID 58 (Project ID: 57)', '2026-04-18 12:56:20'),
(98, 38, NULL, 0, 'assigned task \'asdfs\' to user ID 57 (Project ID: 57)', '2026-04-18 12:58:56'),
(99, 38, NULL, 0, 'assigned task \'ADS\' to user ID 57 (Project ID: 57)', '2026-04-18 13:03:51'),
(100, 57, NULL, 0, 'assigned Direct Task \'afsf\' to user ID 58', '2026-04-18 13:04:26'),
(101, 38, NULL, 0, 'assigned task \'q\' to user ID 57 (Project ID: 57)', '2026-04-18 13:11:44'),
(102, 57, NULL, 0, 'assigned Direct Task \'FASASD\' to user ID 57', '2026-04-18 13:14:17'),
(103, 38, NULL, 0, 'assigned Direct Task \'Complete Home page  by today\' to user ID 60', '2026-04-20 09:42:15'),
(104, 38, NULL, 0, 'assigned Direct Task \'teste  direct task\' to user ID 60', '2026-04-20 10:08:11'),
(105, 38, NULL, 0, 'assigned Direct Task \'asfdffsda\' to user ID 61', '2026-04-20 10:09:13'),
(106, 38, NULL, 0, 'assigned Direct Task \'asffsdf\' to user ID 60', '2026-04-20 10:10:17'),
(107, 38, NULL, 0, 'assigned Direct Task \'asfdf\' to user ID 60', '2026-04-20 10:16:12'),
(108, 38, NULL, 0, 'assigned Direct Task \'fdadfsd\' to user ID 60', '2026-04-20 10:17:01'),
(109, 38, NULL, 0, 'assigned Direct Task \'dg\' to user ID 60', '2026-04-20 10:20:54'),
(110, 38, NULL, 0, 'assigned task \'dFS\' to user ID 60 (Project ID: 58)', '2026-04-20 10:22:14'),
(111, 38, NULL, 0, 'assigned task \'KLMKLMK\' to user ID 60 (Project ID: 58)', '2026-04-20 10:23:51'),
(112, 38, NULL, 0, 'assigned Direct Task \'DASF\' to user ID 61', '2026-04-20 10:24:54'),
(113, 38, NULL, 0, 'assigned Direct Task \'DASF\' to user ID 60', '2026-04-20 10:24:54'),
(114, 38, NULL, 0, 'assigned Direct Task \'DSFSD\' to user ID 60', '2026-04-20 10:26:06'),
(115, 38, NULL, 0, 'assigned Direct Task \'UHGYFDZSFHG\' to user ID 60', '2026-04-20 10:30:25'),
(116, 38, NULL, 0, 'assigned Direct Task \'bababba\' to user ID 60', '2026-04-20 10:32:24'),
(117, 38, NULL, 0, 'assigned Direct Task \'dfdsfd\' to user ID 61', '2026-04-20 10:34:19'),
(118, 38, NULL, 0, 'assigned Direct Task \'dfdsfd\' to user ID 60', '2026-04-20 10:34:19'),
(119, 38, NULL, 0, 'assigned Direct Task \'dfsfd\' to user ID 60', '2026-04-20 10:35:56'),
(120, 38, NULL, 0, 'assigned Direct Task \'asdfds\' to user ID 60', '2026-04-20 10:42:12'),
(121, 38, NULL, 0, 'assigned Direct Task \'asfsa\' to user ID 60', '2026-04-20 10:46:41'),
(122, 38, NULL, 0, 'assigned task \'sgdfgg\' to user ID 60 (Project ID: 59)', '2026-04-20 10:47:53'),
(123, 60, NULL, -5, 'Delayed Completion', '2026-04-20 10:48:15'),
(124, 38, NULL, 0, 'assigned Direct Task \'fdasf\' to user ID 61', '2026-04-20 10:49:51'),
(125, 38, NULL, 0, 'assigned Direct Task \'both\' to user ID 61', '2026-04-20 10:50:05'),
(126, 38, NULL, 0, 'assigned Direct Task \'both\' to user ID 60', '2026-04-20 10:50:05'),
(127, 38, NULL, 0, 'assigned task \'asfd\' to user ID 60 (Project ID: 59)', '2026-04-20 10:50:26'),
(128, 38, NULL, 0, 'assigned task \'asfd\' to user ID 61 (Project ID: 59)', '2026-04-20 10:50:26'),
(129, 38, NULL, 0, 'assigned Direct Task \'both task\' to user ID 61', '2026-04-20 10:54:48'),
(130, 38, NULL, 0, 'assigned Direct Task \'both task\' to user ID 60', '2026-04-20 10:54:48'),
(131, 38, NULL, 0, 'assigned Direct Task \'zllll\' to user ID 61', '2026-04-20 10:55:20'),
(132, 38, NULL, 0, 'assigned Direct Task \'zllll\' to user ID 60', '2026-04-20 10:55:20'),
(133, 38, NULL, 0, 'assigned Direct Task \'both task\' to user ID 61', '2026-04-20 11:04:38'),
(134, 38, NULL, 0, 'assigned Direct Task \'both task\' to user ID 60', '2026-04-20 11:04:38'),
(135, 38, NULL, 0, 'assigned Direct Task \'asad task\' to user ID 61', '2026-04-20 11:04:56'),
(136, 38, NULL, 0, 'assigned Direct Task \'ASF\' to user ID 60', '2026-04-20 11:35:27'),
(137, 38, NULL, 0, 'assigned Direct Task \'blal testte\' to user ID 61', '2026-04-20 11:46:57'),
(138, 38, NULL, 0, 'assigned Direct Task \'blal testte\' to user ID 60', '2026-04-20 11:46:57'),
(139, 38, NULL, 0, 'assigned task \'project task\' to user ID 60 (Project ID: 59)', '2026-04-20 11:47:31'),
(140, 38, NULL, 0, 'assigned task \'project task\' to user ID 61 (Project ID: 59)', '2026-04-20 11:47:31'),
(141, 38, NULL, 0, 'assigned task \'new project task\' to user ID 60 (Project ID: 59)', '2026-04-20 11:54:26'),
(142, 38, NULL, 0, 'assigned task \'new project task\' to user ID 61 (Project ID: 59)', '2026-04-20 11:54:26'),
(143, 38, NULL, 0, 'assigned task \'AADASDSDDS\' to user ID 60 (Project ID: 59)', '2026-04-20 12:08:25'),
(144, 38, NULL, 0, 'assigned task \'AADASDSDDS\' to user ID 61 (Project ID: 59)', '2026-04-20 12:08:25'),
(145, 38, NULL, 0, 'assigned task \'FDKJSDLJK\' to user ID 60 (Project ID: 59)', '2026-04-20 12:08:51'),
(146, 38, 140, 0, 'assigned Direct Task \'DIRECT TASK\' to user ID 61', '2026-04-20 12:11:59'),
(147, 38, 141, 0, 'assigned Direct Task \'asfs\' to user ID 60', '2026-04-20 12:54:40'),
(148, 38, NULL, 0, 'assigned task \'Home page complete\' to user ID 63 (Project ID: 60)', '2026-04-22 11:41:10'),
(149, 38, NULL, 0, 'assigned task \'Home page complete\' to user ID 62 (Project ID: 60)', '2026-04-22 11:41:10'),
(150, 63, NULL, 5, 'Early Completion', '2026-04-22 11:43:52'),
(151, 63, NULL, 5, 'Early Completion', '2026-04-22 11:45:31'),
(152, 38, 143, 0, 'assigned Direct Task \'20 pages create\' to user ID 63', '2026-04-22 11:46:20'),
(153, 63, NULL, 5, 'Early Completion', '2026-04-22 11:49:57'),
(154, 38, 144, 0, 'assigned Direct Task \'new task\' to user ID 63', '2026-04-22 11:51:03'),
(155, 38, NULL, 0, 'assigned task \'pproject tesskks\' to user ID 76 (Project ID: 60)', '2026-04-22 12:24:51'),
(156, 76, NULL, 5, 'Early Completion', '2026-04-22 12:25:15'),
(157, 38, NULL, 0, 'assigned task \'qwrwer\' to user ID 76 (Project ID: 60)', '2026-04-22 12:32:29'),
(158, 38, NULL, 0, 'assigned Direct Task \'direct taskkksdaksf\' to user ID 76', '2026-04-22 12:33:56'),
(159, 38, NULL, 0, 'assigned Direct Task \'safsff\' to user ID 68', '2026-04-22 12:37:44'),
(160, 66, NULL, 0, 'assigned task \'Central Heating Page\' to user ID 76 (Project ID: 61)', '2026-04-23 08:09:23'),
(161, 66, 150, 0, 'assigned task \'Central Heating Page\' to user ID 76 (Project ID: 61)', '2026-04-23 08:10:19'),
(162, 66, 151, 0, 'assigned task \'BLOCK Drain Page\' to user ID 68 (Project ID: 61)', '2026-04-23 10:37:25'),
(163, 66, 152, 0, 'assigned task \'UX/UI Redesign\' to user ID 38 (Project ID: 62)', '2026-04-23 10:42:50'),
(164, 66, NULL, 0, 'assigned Direct Task \'COmplete the branded Doc\' to user ID 77', '2026-04-23 10:59:53'),
(165, 66, 154, 0, 'assigned Direct Task \'Complete Branded Doc\' to user ID 77', '2026-04-23 11:26:30'),
(166, 66, 155, 0, 'assigned task \'Add Meta title\' to user ID 77 (Project ID: 63)', '2026-04-23 13:36:10'),
(167, 76, 150, -10, 'Task Missed: Central Heating Page', '2026-04-23 22:41:22'),
(168, 68, 151, -10, 'Task Missed: BLOCK Drain Page', '2026-04-23 22:41:22'),
(169, 77, 155, -10, 'Task Missed: Add Meta title', '2026-04-23 22:41:22'),
(170, 66, 156, 0, 'assigned Direct Task \'IR  - Replicate\' to user ID 81', '2026-04-24 07:13:38'),
(171, 66, 157, 0, 'assigned Direct Task \'Crown - Replciate\' to user ID 69', '2026-04-24 07:14:26'),
(172, 66, 158, 0, 'assigned Direct Task \'Thames - Replica\' to user ID 71', '2026-04-24 07:15:18'),
(173, 66, 159, 0, 'assigned Direct Task \'Relaible - Replica\' to user ID 72', '2026-04-24 07:15:46'),
(174, 77, 155, -5, 'Delayed Completion', '2026-04-24 07:26:30'),
(175, 77, 155, -5, 'Delayed Completion', '2026-04-24 07:26:56'),
(176, 66, 160, 0, 'assigned task \'Guttering Page\' to user ID 70 (Project ID: 64)', '2026-04-24 07:41:19'),
(177, 66, 161, 0, 'assigned task \'Radiator Page\' to user ID 70 (Project ID: 64)', '2026-04-24 07:41:47'),
(178, 66, 162, 0, 'assigned task \'Bathroom Page\' to user ID 70 (Project ID: 64)', '2026-04-24 07:42:20'),
(179, 66, 163, 0, 'assigned task \'Toilet Repair page\' to user ID 70 (Project ID: 64)', '2026-04-24 07:43:11'),
(180, 66, 164, 0, 'assigned task \'Shower Installation Page\' to user ID 70 (Project ID: 64)', '2026-04-24 07:43:38'),
(181, 66, 165, 0, 'assigned task \'Wordpress Gig\' to user ID 83 (Project ID: 66)', '2026-04-24 07:49:13'),
(182, 66, 166, 0, 'assigned task \'SEO Gig\' to user ID 74 (Project ID: 66)', '2026-04-24 07:49:33'),
(183, 75, 167, 0, 'assigned task \'Integrate local Database\' to user ID 67 (Project ID: 65)', '2026-04-24 07:49:58'),
(184, 66, 168, 0, 'assigned task \'Fix Gig\' to user ID 83 (Project ID: 66)', '2026-04-24 07:51:20'),
(185, 75, 169, 0, 'assigned Direct Task \'Carpet Demo Website\' to user ID 78', '2026-04-24 07:52:06'),
(186, 76, 150, -5, 'Delayed Completion', '2026-04-24 14:55:32'),
(187, 74, 166, 2, 'On-Time Completion', '2026-04-24 14:56:07'),
(188, 70, 164, 2, 'On-Time Completion', '2026-04-24 14:56:19'),
(189, 70, 163, 2, 'On-Time Completion', '2026-04-24 14:56:21'),
(190, 70, 162, 2, 'On-Time Completion', '2026-04-24 14:56:24'),
(191, 70, 161, 2, 'On-Time Completion', '2026-04-24 14:56:26'),
(192, 70, 160, 2, 'On-Time Completion', '2026-04-24 14:56:32'),
(193, 38, 152, -10, 'Task Missed: UX/UI Redesign', '2026-04-25 03:58:33'),
(194, 83, 165, -10, 'Task Missed: Wordpress Gig', '2026-04-25 03:58:33'),
(195, 74, 166, -10, 'Task Missed: SEO Gig', '2026-04-25 03:58:33'),
(196, 83, 168, -10, 'Task Missed: Fix Gig', '2026-04-25 03:58:33'),
(197, 83, 168, -5, 'Delayed Completion', '2026-04-25 06:36:33'),
(198, 74, 166, -5, 'Delayed Completion', '2026-04-25 06:36:35'),
(199, 83, 165, -5, 'Delayed Completion', '2026-04-25 06:36:37'),
(200, 38, 152, -5, 'Delayed Completion', '2026-04-25 06:36:47'),
(201, 67, 167, 5, 'Early Completion', '2026-04-25 06:37:36'),
(202, 38, NULL, 0, 'assigned task \'new testing task for aqib\' to user ID 76 (Project ID: 61)', '2026-04-25 07:45:26'),
(203, 76, NULL, -5, 'Delayed Completion', '2026-04-25 07:47:27'),
(204, 38, NULL, 0, 'assigned task \'new review notificaiotn\' to user ID 76 (Project ID: 61)', '2026-04-25 07:47:58'),
(205, 38, NULL, 0, 'assigned task \'new task review testing notifaionoi\' to user ID 76 (Project ID: 61)', '2026-04-25 07:52:08'),
(206, 38, NULL, 0, 'assigned Direct Task \'testing notifiaont\' to user ID 76', '2026-04-25 07:54:00'),
(207, 38, NULL, 0, 'assigned task \'zulkif new review task\' to user ID 76 (Project ID: 61)', '2026-04-25 08:00:49'),
(208, 38, NULL, 0, 'assigned Direct Task \'new zulkif testin  diect rak\' to user ID 76', '2026-04-25 08:01:18'),
(209, 76, NULL, 5, 'Early Completion', '2026-04-25 08:04:53'),
(210, 76, NULL, 5, 'Early Completion', '2026-04-25 08:04:56'),
(211, 38, NULL, 0, 'assigned task \'asfs testing sound\' to user ID 76 (Project ID: 61)', '2026-04-25 08:05:50'),
(212, 38, NULL, 0, 'assigned Direct Task \'testst sound\' to user ID 76', '2026-04-25 08:06:25'),
(213, 38, NULL, 0, 'assigned task \'nwe sounddds\' to user ID 76 (Project ID: 61)', '2026-04-25 08:13:44'),
(214, 38, NULL, 0, 'assigned Direct Task \'review testing\' to user ID 76', '2026-04-25 08:25:11'),
(215, 38, NULL, 0, 'assigned task \'testse\' to user ID 76 (Project ID: 61)', '2026-04-25 08:25:50'),
(216, 38, NULL, 0, 'assigned Direct Task \'teste arasass\' to user ID 83', '2026-04-25 09:14:53'),
(217, 83, NULL, 5, 'Early Completion', '2026-04-25 09:15:20'),
(218, 38, NULL, 0, 'assigned Direct Task \'arassas\' to user ID 83', '2026-04-25 09:19:56'),
(219, 83, NULL, 5, 'Early Completion', '2026-04-25 09:20:18');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `project_head_id` int(11) DEFAULT NULL,
  `parent_project_id` int(11) DEFAULT NULL,
  `project_type` enum('Major','Sub') NOT NULL DEFAULT 'Major',
  `progress_percentage` int(11) NOT NULL DEFAULT 0,
  `status` enum('Planned','In Progress','Completed','On Hold') NOT NULL DEFAULT 'Planned',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `kpi_target` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `budget` decimal(15,2) DEFAULT 0.00,
  `spent` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `department_id`, `project_head_id`, `parent_project_id`, `project_type`, `progress_percentage`, `status`, `start_date`, `end_date`, `kpi_target`, `created_at`, `budget`, `spent`) VALUES
(61, 'Elite Home Renovators', NULL, NULL, NULL, 'Major', 0, 'Planned', '2026-04-10', '2026-05-22', 'Elite Home Renovators', '2026-04-23 08:08:52', 0.00, 0.00),
(62, 'Event Managment System', NULL, NULL, NULL, 'Major', 0, 'Planned', '2026-04-23', '2026-04-30', 'Event Managment System', '2026-04-23 10:42:18', 0.00, 0.00),
(63, 'Dewiji Cosultant', NULL, NULL, NULL, 'Major', 0, 'Planned', '2026-04-23', '2026-05-30', 'Dewiji Cosultant Website', '2026-04-23 13:35:28', 0.00, 0.00),
(64, 'Elite Home Graphics X UX/UI', NULL, NULL, NULL, 'Major', 0, 'Planned', '2026-04-24', '2026-07-24', 'Elite Home Graphics X UX/UI', '2026-04-24 07:39:12', 0.00, 0.00),
(65, 'Dia Step', NULL, NULL, NULL, 'Major', 0, 'In Progress', '2026-04-10', '2026-05-09', 'Foot size & Wound Detection App', '2026-04-24 07:46:21', 0.00, 0.00),
(66, 'Haris Graphics', NULL, NULL, NULL, 'Major', 0, 'Planned', '2026-04-24', '2026-07-31', 'Haris Graphics', '2026-04-24 07:46:29', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sub_project_departments`
--

CREATE TABLE `sub_project_departments` (
  `sub_project_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `status` enum('Pending','Pending Approval','In Progress','Completed','Review','Missed') NOT NULL DEFAULT 'Pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `specific_time` time DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `review_status` enum('Pending','Under Review','Approved','Changes Requested') NOT NULL DEFAULT 'Pending',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `project_id`, `assigned_to`, `priority`, `status`, `start_date`, `end_date`, `specific_time`, `due_date`, `completed_at`, `created_at`, `review_status`, `updated_at`) VALUES
(140, 'DIRECT TASK', '', NULL, NULL, 'Medium', 'Pending', '2026-04-22', NULL, NULL, NULL, NULL, '2026-04-20 12:11:59', 'Pending', '2026-04-20 12:11:59'),
(141, 'asfs', '', NULL, NULL, 'Medium', 'Pending', '2026-05-08', NULL, NULL, NULL, NULL, '2026-04-20 12:54:40', 'Pending', '2026-04-20 12:54:40'),
(143, '20 pages create', '', NULL, NULL, 'Medium', 'Completed', '2026-04-23', '2026-04-29', NULL, NULL, '2026-04-22 04:49:24', '2026-04-22 11:46:20', 'Pending', '2026-04-22 11:49:24'),
(144, 'new task', '', NULL, NULL, 'Medium', 'Pending', '2026-04-20', '2026-05-28', NULL, NULL, NULL, '2026-04-22 11:51:03', 'Pending', '2026-04-22 11:51:03'),
(150, 'Central Heating Page', 'Create Central Heating Page', 61, 76, 'High', 'Completed', NULL, NULL, NULL, '2026-04-24 00:00:00', '2026-04-24 19:55:32', '2026-04-23 08:10:19', 'Pending', '2026-04-24 14:55:32'),
(151, 'BLOCK Drain Page', 'BLOCK Drain Page', 61, 68, 'High', 'Missed', NULL, NULL, NULL, '2026-04-24 00:00:00', NULL, '2026-04-23 10:37:25', 'Pending', '2026-04-24 14:55:56'),
(152, 'UX/UI Redesign', 'UX/UI Redesign', 62, 38, 'High', 'Completed', NULL, NULL, NULL, '2026-04-25 00:00:00', '2026-04-25 11:36:47', '2026-04-23 10:42:50', 'Pending', '2026-04-25 06:36:47'),
(154, 'Complete Branded Doc', 'Complete Branded Doc', NULL, 77, 'High', 'Completed', '2026-04-23', '2026-04-23', '19:31:00', '2026-04-23 00:00:00', '2026-04-23 19:57:52', '2026-04-23 11:26:30', 'Pending', '2026-04-25 09:13:39'),
(155, 'Add Meta title', 'Add Meta title Meta description to Pages', 63, 77, 'High', 'Completed', NULL, NULL, NULL, '2026-04-24 00:00:00', '2026-04-24 12:26:56', '2026-04-23 13:36:10', 'Pending', '2026-04-24 07:26:56'),
(156, 'IR  - Replicate', 'IR  - Replicate', NULL, NULL, 'High', 'Pending', '2026-04-24', '2026-04-27', NULL, '2026-04-27 00:00:00', NULL, '2026-04-24 07:13:38', 'Pending', '2026-04-24 07:13:38'),
(157, 'Crown - Replciate', 'Crown - Replciate', NULL, NULL, 'Medium', 'Pending', '2026-04-24', '2026-04-27', NULL, '2026-04-27 00:00:00', NULL, '2026-04-24 07:14:26', 'Pending', '2026-04-24 07:14:26'),
(158, 'Thames - Replica', 'Thames - Replica', NULL, NULL, 'High', 'Pending', '2026-04-24', '2026-04-27', NULL, '2026-04-27 00:00:00', NULL, '2026-04-24 07:15:18', 'Pending', '2026-04-24 07:15:18'),
(159, 'Relaible - Replica', 'Relaible - Replica', NULL, 72, 'High', 'Pending', '2026-04-24', '2026-04-27', '14:15:00', '2026-04-27 00:00:00', NULL, '2026-04-24 07:15:46', 'Pending', '2026-04-25 09:13:49'),
(160, 'Guttering Page', 'Guttering Services London', 64, 70, 'High', 'Completed', NULL, NULL, NULL, '2026-04-25 00:00:00', '2026-04-24 19:56:32', '2026-04-24 07:41:19', 'Pending', '2026-04-24 14:56:32'),
(161, 'Radiator Page', 'Radiator Services London', 64, 70, 'High', 'Completed', NULL, NULL, NULL, '2026-04-25 00:00:00', '2026-04-24 19:56:26', '2026-04-24 07:41:47', 'Pending', '2026-04-24 14:56:26'),
(162, 'Bathroom Page', 'Bathroom Installation London', 64, 70, 'High', 'Completed', NULL, NULL, NULL, '2026-04-25 00:00:00', '2026-04-24 19:56:24', '2026-04-24 07:42:20', 'Pending', '2026-04-24 14:56:24'),
(163, 'Toilet Repair page', 'Toilet Repair Page', 64, 70, 'High', 'Completed', NULL, NULL, NULL, '2026-04-25 00:00:00', '2026-04-24 19:56:21', '2026-04-24 07:43:11', 'Pending', '2026-04-24 14:56:21'),
(164, 'Shower Installation Page', 'Shower Installation London', 64, 70, 'High', 'Completed', NULL, NULL, NULL, '2026-04-25 00:00:00', '2026-04-24 19:56:19', '2026-04-24 07:43:38', 'Pending', '2026-04-24 14:56:19'),
(165, 'Wordpress Gig', 'Wordpress Gig', 66, 83, 'High', 'Completed', NULL, NULL, NULL, '2026-04-25 00:00:00', '2026-04-25 11:36:37', '2026-04-24 07:49:13', 'Pending', '2026-04-25 06:36:37'),
(166, 'SEO Gig', 'SEO Gig', 66, 74, 'Medium', 'Completed', NULL, NULL, NULL, '2026-04-25 00:00:00', '2026-04-25 11:36:35', '2026-04-24 07:49:33', 'Pending', '2026-04-25 06:36:35'),
(167, 'Integrate local Database', 'Integrate local database for history and than further work on apis and gradually try to get accurate result ,', 65, 67, 'Medium', 'Completed', NULL, NULL, NULL, '2026-05-01 00:00:00', '2026-04-25 11:37:36', '2026-04-24 07:49:58', 'Pending', '2026-04-25 06:37:36'),
(168, 'Fix Gig', 'Fix Gig', 66, 83, 'High', 'Completed', NULL, NULL, NULL, '2026-04-25 00:00:00', '2026-04-25 11:36:33', '2026-04-24 07:51:20', 'Pending', '2026-04-25 06:36:33'),
(169, 'Carpet Demo Website', '', NULL, 78, 'Low', 'Pending', '2026-04-20', '2026-04-25', NULL, '2026-04-25 00:00:00', NULL, '2026-04-24 07:52:06', 'Pending', '2026-04-25 09:13:29');

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_assignments`
--

INSERT INTO `task_assignments` (`id`, `task_id`, `user_id`) VALUES
(120, 150, 76),
(121, 151, 68),
(122, 152, 38),
(152, 154, 77),
(125, 155, 77),
(126, 156, 81),
(127, 157, 69),
(128, 158, 71),
(153, 159, 72),
(130, 160, 70),
(131, 161, 70),
(132, 162, 70),
(133, 163, 70),
(134, 164, 70),
(135, 165, 83),
(136, 166, 74),
(137, 167, 67),
(138, 168, 83),
(151, 169, 78);

-- --------------------------------------------------------

--
-- Table structure for table `task_assignment_notifications`
--

CREATE TABLE `task_assignment_notifications` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `sender_user_id` int(11) NOT NULL,
  `notification_type` enum('direct_task','project_task') NOT NULL DEFAULT 'direct_task',
  `title_snapshot` varchar(255) NOT NULL DEFAULT '',
  `message` varchar(500) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_popup_shown` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_assignment_notifications`
--

INSERT INTO `task_assignment_notifications` (`id`, `task_id`, `recipient_user_id`, `sender_user_id`, `notification_type`, `title_snapshot`, `message`, `is_read`, `created_at`, `is_popup_shown`) VALUES
(66, 140, 61, 38, 'direct_task', 'DIRECT TASK', 'New Direct Task assigned: DIRECT TASK', 1, '2026-04-20 12:11:59', 0),
(67, 141, 60, 38, 'direct_task', 'asfs', 'New Direct Task assigned: asfs', 1, '2026-04-20 12:54:40', 0),
(70, 143, 63, 38, 'direct_task', '20 pages create', 'New Direct Task assigned: 20 pages create', 1, '2026-04-22 11:46:20', 0),
(71, 144, 63, 38, 'direct_task', 'new task', 'New Direct Task assigned: new task', 0, '2026-04-22 11:51:03', 0),
(77, 150, 76, 66, 'project_task', 'Central Heating Page', 'New Project Task assigned: Central Heating Page', 1, '2026-04-23 08:10:19', 0),
(78, 151, 68, 66, 'project_task', 'BLOCK Drain Page', 'New Project Task assigned: BLOCK Drain Page', 1, '2026-04-23 10:37:25', 0),
(79, 152, 38, 66, 'project_task', 'UX/UI Redesign', 'New Project Task assigned: UX/UI Redesign', 1, '2026-04-23 10:42:50', 0),
(81, 154, 77, 66, 'direct_task', 'Complete Branded Doc', 'New Direct Task assigned: Complete Branded Doc', 1, '2026-04-23 11:26:30', 0),
(82, 155, 77, 66, 'project_task', 'Add Meta title', 'New Project Task assigned: Add Meta title', 1, '2026-04-23 13:36:10', 0),
(83, 156, 81, 66, 'direct_task', 'IR  - Replicate', 'New Direct Task assigned: IR  - Replicate', 0, '2026-04-24 07:13:38', 0),
(84, 157, 69, 66, 'direct_task', 'Crown - Replciate', 'New Direct Task assigned: Crown - Replciate', 1, '2026-04-24 07:14:26', 0),
(85, 158, 71, 66, 'direct_task', 'Thames - Replica', 'New Direct Task assigned: Thames - Replica', 1, '2026-04-24 07:15:18', 0),
(86, 159, 72, 66, 'direct_task', 'Relaible - Replica', 'New Direct Task assigned: Relaible - Replica', 0, '2026-04-24 07:15:46', 0),
(87, 160, 70, 66, 'project_task', 'Guttering Page', 'New Project Task assigned: Guttering Page', 1, '2026-04-24 07:41:19', 0),
(88, 161, 70, 66, 'project_task', 'Radiator Page', 'New Project Task assigned: Radiator Page', 1, '2026-04-24 07:41:47', 0),
(89, 162, 70, 66, 'project_task', 'Bathroom Page', 'New Project Task assigned: Bathroom Page', 1, '2026-04-24 07:42:20', 0),
(90, 163, 70, 66, 'project_task', 'Toilet Repair page', 'New Project Task assigned: Toilet Repair page', 1, '2026-04-24 07:43:11', 0),
(91, 164, 70, 66, 'project_task', 'Shower Installation Page', 'New Project Task assigned: Shower Installation Page', 1, '2026-04-24 07:43:38', 0),
(92, 165, 83, 66, 'project_task', 'Wordpress Gig', 'New Project Task assigned: Wordpress Gig', 1, '2026-04-24 07:49:13', 0),
(93, 166, 74, 66, 'project_task', 'SEO Gig', 'New Project Task assigned: SEO Gig', 1, '2026-04-24 07:49:33', 0),
(94, 167, 67, 75, 'project_task', 'Integrate local Database', 'New Project Task assigned: Integrate local Database', 1, '2026-04-24 07:49:58', 0),
(95, 168, 83, 66, 'project_task', 'Fix Gig', 'New Project Task assigned: Fix Gig', 1, '2026-04-24 07:51:20', 0),
(96, 169, 78, 75, 'direct_task', 'Carpet Demo Website', 'New Direct Task assigned: Carpet Demo Website', 1, '2026-04-24 07:52:06', 0);

-- --------------------------------------------------------

--
-- Table structure for table `task_assignment_pending`
--

CREATE TABLE `task_assignment_pending` (
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_chat_read`
--

CREATE TABLE `task_chat_read` (
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_chat_read`
--

INSERT INTO `task_chat_read` (`user_id`, `task_id`, `last_read_at`) VALUES
(43, 67, '2026-03-07 02:16:39'),
(43, 73, '2026-03-07 02:17:49'),
(43, 76, '2026-03-07 02:32:43'),
(43, 77, '2026-03-07 03:00:57'),
(43, 84, '2026-03-09 10:45:45'),
(44, 73, '2026-03-07 02:17:23'),
(44, 84, '2026-03-09 10:46:10'),
(52, 76, '2026-03-07 03:00:36'),
(52, 77, '2026-03-07 02:58:37'),
(53, 64, '2026-03-07 03:04:48'),
(53, 74, '2026-03-07 02:20:22'),
(53, 77, '2026-03-07 03:05:02');

-- --------------------------------------------------------

--
-- Table structure for table `task_messages`
--

CREATE TABLE `task_messages` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `attachment_url` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'Member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_plain` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'Team Member',
  `department_id` int(11) DEFAULT NULL,
  `performance_score` int(11) NOT NULL DEFAULT 100,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone_number`, `password_hash`, `password_plain`, `role`, `department_id`, `performance_score`, `avatar_url`, `created_at`, `updated_at`) VALUES
(38, 'Zulkif', 'muhammadzulkif2001@gmail.com', '', '$2y$10$BQNfOpc1eeSN2hQpIErBP.sIGgthi1F1IYtquCKO/OX.gWFzHfFsK', 'zulkif123@', 'Team Member', 27, 80, NULL, '2026-02-18 06:22:05', '2026-04-25 07:16:56'),
(66, 'Haris Sattar', 'hariskh1835@gmail.com', '', '$2y$10$abbOsQL6cgihJYA2iqwEq.xKW9lr68F8LVijpBGY.s38/fixzvtaq', 'Sog654321$', 'Super Admin', NULL, 100, NULL, '2026-04-22 12:08:56', '2026-04-22 12:08:56'),
(67, 'Obaid', 'obaidkhan42342@gmail.com', '', '$2y$10$RfABSmx2HcIRvIrjFjmCE.OjoqK7O7fM8d1kgyhNcD8afmRgGvlWG', 'zouetech123', 'Team Member', 29, 105, 'uploads/avatars/avatar_69ea2cfdd8751.jpg', '2026-04-22 12:10:20', '2026-04-25 06:37:36'),
(68, 'Alishah', 'alishasyed2000@gmail.com', '', '$2y$10$0D7VVPD5AmOp6526t13Uw.B0cvRts8xxZOC5/WugpeJLgK0Ob0ow.', 'zouetech123', 'Team Lead', 27, 90, NULL, '2026-04-22 12:11:04', '2026-04-23 22:41:22'),
(69, 'Shahzad Habib', 'Shahzad.habib1961@gmail.com', '', '$2y$10$weE6rofZvxHk0D0wpjRhXutpuwzbSQhgcYHggnzb91UzTEVnBA5PC', 'zouetech123', 'Team Member', 27, 100, NULL, '2026-04-22 12:11:27', '2026-04-23 09:34:40'),
(70, 'Asad Marwat', 'marwatasad904@gmail.com', '', '$2y$10$VafNm7Ft65i0SVRlBhFGsOcI/2WlpyU/KL64FuH2zkMgr65YeapoS', 'zouetech123', 'Team Member', 26, 110, NULL, '2026-04-22 12:11:48', '2026-04-24 14:56:32'),
(71, 'Khalil Ahmad', 'khalilahmad9338@gmail.com', '', '$2y$10$K5P2AZfRmxDnWz5pQ.2C2.t9v3tqkCPR0wzvCi3xhL8m4h7qrvpnS', 'zouetech123', 'Team Member', 27, 100, NULL, '2026-04-22 12:12:16', '2026-04-23 09:34:29'),
(72, 'Wisal Hussain', 'wisalhussain147@gmail.com', '', '$2y$10$JnfYYk6Zkl2ti4yES1yqK.2MMhYafdI.nb6xDQ2e6BKFIYgNf/rea', 'zouetech123', 'Team Member', 27, 100, NULL, '2026-04-22 12:12:32', '2026-04-23 09:34:25'),
(73, 'Nahal Imran', 'nahalimran2001@gmail.com', '', '$2y$10$g8LptGf9PMiqYpMC4nVyseznliH7hDgK1tCCfA7Ob6V0tLeWXHyh6', 'zouetech123', 'Team Member', 27, 100, NULL, '2026-04-22 12:12:48', '2026-04-23 09:40:59'),
(74, 'Usman khan', 'umar18naveed@gmail.com', '', '$2y$10$TfNQMcZpFLgoefIZCYLa7usiKXHyPF/3yjHmqUIzcPIPksDZ21.G2', 'zouetech123', 'Team Member', 26, 87, 'uploads/avatars/avatar_69eb7fce06922.jpg', '2026-04-22 12:13:07', '2026-04-25 06:36:35'),
(75, 'Umar Naveed', '13805@cityuniversity.edu.pk', '', '$2y$10$MbbwGx17zCWYh93U9m6iRekqPcA5l1blBPuJuiGEp8LV0JpUlbKRe', 'Ramu6789$$', 'Super Admin', NULL, 100, NULL, '2026-04-22 12:13:22', '2026-04-23 09:33:57'),
(76, 'Aqib', 'aqibofficial276@gmail.com', '', '$2y$10$dYM9Su7h09UGYMAayKXJVOGb4chU0ZbA5oDQBj357mIr6p3qk1dyO', 'zouetech123', 'Team Member', 27, 95, NULL, '2026-04-22 12:13:40', '2026-04-25 08:04:56'),
(77, 'Muhammad Daniyal', 'intoxicateddaniyal123@gmail.com', '', '$2y$10$qLdToOo2XvenglagEgmsl.XXzUaZlZlxfKblI2ILuBf.xCbVMBcqq', 'daniyal4420', 'Team Member', 27, 80, 'uploads/avatars/avatar_69eb39755a651.png', '2026-04-23 09:38:20', '2026-04-24 09:37:11'),
(78, 'Sallu Bhai', 'asad@gmail.com', '', '$2y$10$23pl4edKnNDP4HoEjc969eHRpen7X5GYIdVBRKVHllTjCKPUQVlFy', 'zouetech123', 'Team Member', 27, 100, NULL, '2026-04-23 09:39:18', '2026-04-23 09:39:18'),
(79, 'Sidra Akhter', 'Sidra@zouetech.com', '', '$2y$10$pTuvfuZyNEOqDw1ajfRYk.L/Y7fE6b3/irQTuiEWr/WGJIxtj3d9S', 'zouetech123', 'Team Member', 28, 100, NULL, '2026-04-23 09:54:05', '2026-04-24 06:07:39'),
(80, 'Linta Shah', 'Linta@zouetech.com', '', '$2y$10$uXmCUMGhNewGWXS92LzqSeVRnlRuWoAoBaeyVyeyx5cHykwTu6I3e', 'zouetech123', 'Team Member', 26, 100, NULL, '2026-04-23 09:55:36', '2026-04-23 09:55:36'),
(81, 'Abdullah Khalil', 'Abdullah@zouetech.com', '', '$2y$10$i6LmAv6nxsXf9XCShwydwOJxBDaF8NfWNWCj.FOPLsZKJGLUIUtla', 'zouetech123', 'Team Member', 27, 100, NULL, '2026-04-23 09:56:11', '2026-04-23 09:59:01'),
(82, 'Sohaib', 'Sohaib@zouetech.com', '', '$2y$10$c79osfA0aweFYz8XlXsNzOfXXfN1orvPSLQ8almLOPdZySlZM3b06', 'zouetech123', 'Team Member', 30, 100, NULL, '2026-04-23 09:58:17', '2026-04-23 09:58:39'),
(83, 'Arsalan Afridi', 'Arsalan@zouetech.com', '', '$2y$10$rLQyyQEJKorsuaVZfTNV4.heYSA5gSWYZVqQCNBIGFzn9z4L2vOi.', 'zouetech123', 'Team Member', 26, 80, NULL, '2026-04-23 09:59:43', '2026-04-25 09:20:18'),
(84, 'Hamza Abdul Sattar', 'CEO@zouetech.com', '', '$2y$10$SfWaPvOhsmXYD4i97kQ9HeNFodoi6J7mFzzVKZThQrnBnT5UqzL.i', 'Sog654321$', 'Super Admin', NULL, 100, NULL, '2026-04-23 10:01:43', '2026-04-23 10:01:43'),
(86, 'ZULKIF sa', 'zulkif@gmail.com', '', '$2y$10$5LrPHM/ZeajmZv9RFgnrdOJWO4esyrqlGUpDF.gXIKFn714NtsdLa', 'zouetech123', 'Super Admin', NULL, 100, NULL, '2026-04-25 06:37:23', '2026-04-25 06:37:23');

-- --------------------------------------------------------

--
-- Table structure for table `user_attendance_logs`
--

CREATE TABLE `user_attendance_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_snapshot` varchar(50) NOT NULL,
  `department_id_snapshot` int(11) DEFAULT NULL,
  `login_at` datetime NOT NULL,
  `logout_at` datetime DEFAULT NULL,
  `daily_summary` text DEFAULT NULL,
  `login_ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_attendance_logs`
--

INSERT INTO `user_attendance_logs` (`id`, `user_id`, `role_snapshot`, `department_id_snapshot`, `login_at`, `logout_at`, `daily_summary`, `login_ip`, `user_agent`, `created_at`) VALUES
(2, 38, 'Super Admin', NULL, '2026-04-18 11:55:52', '2026-04-18 11:59:20', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 09:55:52'),
(4, 38, 'Super Admin', NULL, '2026-04-18 12:13:49', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-18 10:13:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_sheet_logs`
--
ALTER TABLE `attendance_sheet_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_day` (`user_id`,`attendance_date`),
  ADD KEY `idx_att_sheet_user` (`user_id`),
  ADD KEY `idx_att_sheet_date` (`attendance_date`);

--
-- Indexes for table `chat_groups`
--
ALTER TABLE `chat_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `chat_group_members`
--
ALTER TABLE `chat_group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_user` (`group_id`,`user_id`),
  ADD KEY `fk_cgm_user` (`user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `head_id` (`head_id`);

--
-- Indexes for table `major_project_departments`
--
ALTER TABLE `major_project_departments`
  ADD PRIMARY KEY (`major_project_id`,`department_id`),
  ADD KEY `fk_mpd_department` (`department_id`);

--
-- Indexes for table `major_project_members`
--
ALTER TABLE `major_project_members`
  ADD PRIMARY KEY (`major_project_id`,`user_id`),
  ADD KEY `fk_mpm_user` (`user_id`);

--
-- Indexes for table `mention_notifications`
--
ALTER TABLE `mention_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `mentioned_user_id` (`mentioned_user_id`),
  ADD KEY `message_id` (`message_id`);

--
-- Indexes for table `performance_logs`
--
ALTER TABLE `performance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `project_head_id` (`project_head_id`),
  ADD KEY `parent_project_id` (`parent_project_id`);

--
-- Indexes for table `sub_project_departments`
--
ALTER TABLE `sub_project_departments`
  ADD PRIMARY KEY (`sub_project_id`,`department_id`),
  ADD KEY `fk_spd_department` (`department_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_user` (`task_id`,`user_id`),
  ADD KEY `fk_ta_user` (`user_id`);

--
-- Indexes for table `task_assignment_notifications`
--
ALTER TABLE `task_assignment_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tan_task_recipient_type` (`task_id`,`recipient_user_id`,`notification_type`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `idx_tan_recipient` (`recipient_user_id`,`is_read`,`created_at`),
  ADD KEY `idx_tan_task` (`task_id`);

--
-- Indexes for table `task_assignment_pending`
--
ALTER TABLE `task_assignment_pending`
  ADD PRIMARY KEY (`task_id`,`user_id`);

--
-- Indexes for table `task_chat_read`
--
ALTER TABLE `task_chat_read`
  ADD PRIMARY KEY (`user_id`,`task_id`);

--
-- Indexes for table `task_messages`
--
ALTER TABLE `task_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `team_user` (`team_id`,`user_id`),
  ADD KEY `fk_tm_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `user_attendance_logs`
--
ALTER TABLE `user_attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_att_user` (`user_id`),
  ADD KEY `idx_att_login` (`login_at`),
  ADD KEY `idx_att_logout` (`logout_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_sheet_logs`
--
ALTER TABLE `attendance_sheet_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `chat_groups`
--
ALTER TABLE `chat_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chat_group_members`
--
ALTER TABLE `chat_group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `mention_notifications`
--
ALTER TABLE `mention_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `performance_logs`
--
ALTER TABLE `performance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=183;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `task_assignment_notifications`
--
ALTER TABLE `task_assignment_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `task_messages`
--
ALTER TABLE `task_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `user_attendance_logs`
--
ALTER TABLE `user_attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_sheet_logs`
--
ALTER TABLE `attendance_sheet_logs`
  ADD CONSTRAINT `fk_att_sheet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_groups`
--
ALTER TABLE `chat_groups`
  ADD CONSTRAINT `fk_group_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_group_members`
--
ALTER TABLE `chat_group_members`
  ADD CONSTRAINT `fk_cgm_group` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cgm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_msg_group` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_dept_head` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `major_project_departments`
--
ALTER TABLE `major_project_departments`
  ADD CONSTRAINT `fk_mpd_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mpd_project` FOREIGN KEY (`major_project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `major_project_members`
--
ALTER TABLE `major_project_members`
  ADD CONSTRAINT `fk_mpm_project` FOREIGN KEY (`major_project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mpm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mention_notifications`
--
ALTER TABLE `mention_notifications`
  ADD CONSTRAINT `mention_notifications_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mention_notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mention_notifications_ibfk_3` FOREIGN KEY (`mentioned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mention_notifications_ibfk_4` FOREIGN KEY (`message_id`) REFERENCES `task_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_logs`
--
ALTER TABLE `performance_logs`
  ADD CONSTRAINT `fk_log_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_proj_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_proj_head` FOREIGN KEY (`project_head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_proj_parent` FOREIGN KEY (`parent_project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_project_departments`
--
ALTER TABLE `sub_project_departments`
  ADD CONSTRAINT `fk_spd_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_spd_project` FOREIGN KEY (`sub_project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_task_proj` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_task_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `fk_ta_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ta_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_assignment_notifications`
--
ALTER TABLE `task_assignment_notifications`
  ADD CONSTRAINT `fk_tan_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_messages`
--
ALTER TABLE `task_messages`
  ADD CONSTRAINT `fk_msg_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_team_proj` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `fk_tm_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_attendance_logs`
--
ALTER TABLE `user_attendance_logs`
  ADD CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
