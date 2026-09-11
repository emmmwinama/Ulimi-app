-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Sep 11, 2026 at 03:07 PM
-- Server version: 11.4.9-MariaDB
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agrivault`
--
CREATE DATABASE IF NOT EXISTS `agrivault` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agrivault`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_inputs`
--

DROP TABLE IF EXISTS `activity_inputs`;
CREATE TABLE IF NOT EXISTS `activity_inputs` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `activity_id` varchar(40) NOT NULL,
  `input_name` varchar(160) NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'Other',
  `quantity` decimal(12,3) NOT NULL DEFAULT 0.000,
  `unit` varchar(20) NOT NULL DEFAULT '',
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `acquisition_unit_cost` decimal(12,2) DEFAULT NULL,
  `time_value_cost` decimal(12,2) DEFAULT NULL,
  `inventory_item_id` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ai_activity` (`activity_id`),
  KEY `idx_ai_farm` (`farm_id`),
  KEY `idx_ai_inventory` (`inventory_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_inputs`
--

INSERT INTO `activity_inputs` (`id`, `farm_id`, `activity_id`, `input_name`, `category`, `quantity`, `unit`, `unit_cost`, `total_cost`, `acquisition_unit_cost`, `time_value_cost`, `inventory_item_id`) VALUES
('01M25NP4ZGR57HRAT4DBSFAGFG', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25NP4ZFBMPMPFR467T81XRB', 'Roundup', 'Chemical', 2.000, 'L', 9500.00, 19000.00, NULL, NULL, NULL),
('1f07faa2-3826-4b18-b64a-1a3af6895790', 'cmpjsq4b20001qxws79jyscoy', '5f01b04a-1f17-4cc4-8a90-534408b16e34', 'SEED', 'Fertilizer', 50.000, 'kg', 20000.00, 1000000.00, NULL, NULL, NULL),
('cmpjxko12000bqxkko6n33fic', 'cmpjsq4b20001qxws79jyscoy', 'cmpjxko120008qxkk71q7fnxv', 'Seed', 'Seed', 6.000, 'Bag', 72750.00, 436500.00, NULL, NULL, NULL),
('cmpjzjptu001lqxkky72m9wwd', 'cmpjsq4b20001qxws79jyscoy', 'cmpjzjptt001iqxkkycztgv0y', 'Seed', 'Seed', 75.000, 'kg', 11482.00, 861150.00, NULL, NULL, NULL),
('cmpk00lqz001rqxkk703zaw9h', 'cmpjsq4b20001qxws79jyscoy', 'cmpk00lqy001oqxkkv3olyi39', 'Extra Seed', 'Seed', 1.000, 'Bag', 49000.00, 49000.00, NULL, NULL, NULL),
('cmpk00lqz001sqxkkyafc1fia', 'cmpjsq4b20001qxws79jyscoy', 'cmpk00lqy001oqxkkv3olyi39', 'NPK', 'Fertilizer', 6.000, 'Bag', 150000.00, 900000.00, NULL, NULL, NULL),
('cmpk00lqz001tqxkkjcm59ltf', 'cmpjsq4b20001qxws79jyscoy', 'cmpk00lqy001oqxkkv3olyi39', 'CAN', 'Fertilizer', 1.000, 'Bag', 150000.00, 150000.00, NULL, NULL, NULL),
('cmpk0akii001zqxkk7q4bc1a3', 'cmpjsq4b20001qxws79jyscoy', 'cmpk0akii001wqxkkj1dgrrad', 'UREA', 'Seed', 7.000, 'Bag', 165000.00, 1155000.00, NULL, NULL, NULL),
('cmpk0akii0020qxkk96vnxoxc', 'cmpjsq4b20001qxws79jyscoy', 'cmpk0akii001wqxkkj1dgrrad', 'Confidor', 'Chemical / Pesticide', 5.000, 'Pack', 5000.00, 25000.00, NULL, NULL, NULL),
('cmrpzr85x0005qxrcpm99xgoa', 'cmpjsq4b20001qxws79jyscoy', 'cmrpzr85x0003qxrcpgqlv9r4', 'seed', 'Seed', 20.000, 'kg', 15000.00, 300000.00, NULL, NULL, NULL),
('cmrrjp5l4000dqx1ojyif00oj', 'cmpjsq4b20001qxws79jyscoy', 'cmrrjp5l40009qx1ogqsazkcn', 'Prep', 'Seed', 5000.000, 'kg', 20000.00, 100000000.00, NULL, NULL, NULL),
('cmrrjvtys000mqx1o8ch8w2zn', 'cmpjsq4b20001qxws79jyscoy', 'cmrrjvtys000kqx1o6ofksg2z', 'Seed', 'Seed', 50.000, 'kg', 15000.00, 750000.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `activity_labour`
--

DROP TABLE IF EXISTS `activity_labour`;
CREATE TABLE IF NOT EXISTS `activity_labour` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `activity_id` varchar(40) NOT NULL,
  `employee_id` varchar(40) DEFAULT NULL,
  `worker_name` varchar(120) DEFAULT NULL,
  `hours_worked` decimal(8,2) NOT NULL DEFAULT 0.00,
  `days_worked` decimal(8,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_al_activity` (`activity_id`),
  KEY `idx_al_farm` (`farm_id`),
  KEY `fk_al_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_labour`
--

INSERT INTO `activity_labour` (`id`, `farm_id`, `activity_id`, `employee_id`, `worker_name`, `hours_worked`, `days_worked`, `total_cost`) VALUES
('01M25NP4ZGR57HRAT4DBSFAGFF', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25NP4ZFBMPMPFR467T81XRB', '01M25NP437SVM0YER9V4EF9FR3', NULL, 0.00, 3.00, 12000.00),
('cmpjxko12000aqxkk4zs6m3xc', 'cmpjsq4b20001qxws79jyscoy', 'cmpjxko120008qxkk71q7fnxv', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 75000.00),
('cmpjxujjk000mqxkks5rvy56d', 'cmpjsq4b20001qxws79jyscoy', 'cmpjxujjk000kqxkkhqdvjwtf', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 67000.00),
('cmpjz69h30018qxkkzp3x2cx8', 'cmpjsq4b20001qxws79jyscoy', 'cmpjz69h20016qxkklrl1gu24', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 169250.00),
('cmpjz8wog001cqxkkb6xq7rre', 'cmpjsq4b20001qxws79jyscoy', 'cmpjz8wog001aqxkk2r4300qq', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 112833.00),
('cmpjzb28p001gqxkkedd60x2r', 'cmpjsq4b20001qxws79jyscoy', 'cmpjzb28p001eqxkk9dl6y1f8', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 394917.00),
('cmpjzjptu001kqxkkaehwovgf', 'cmpjsq4b20001qxws79jyscoy', 'cmpjzjptt001iqxkkycztgv0y', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 135815.00),
('cmpk00lqy001qqxkkbxbisfpj', 'cmpjsq4b20001qxws79jyscoy', 'cmpk00lqy001oqxkkv3olyi39', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 196000.00),
('cmpk0akii001yqxkkk79k5wy7', 'cmpjsq4b20001qxws79jyscoy', 'cmpk0akii001wqxkkj1dgrrad', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 160000.00),
('cmpk0nhxm0026qxkk7p3uach2', 'cmpjsq4b20001qxws79jyscoy', 'cmpk0nhxl0024qxkkj8da10fr', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 181000.00),
('cmpk15631002aqxkkwhpf7bzw', 'cmpjsq4b20001qxws79jyscoy', 'cmpk156310028qxkka6f3x767', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 280000.00),
('cmpk19g3g002mqxkk3k3bnqrx', 'cmpjsq4b20001qxws79jyscoy', 'cmpk19g3g002kqxkky593tmie', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 54000.00),
('cmpm3kqb70003qx1skuxqpvj9', 'cmpjsq4b20001qxws79jyscoy', 'cmpm3kqb70001qx1s16lc2gyz', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 58000.00),
('cmpm3ndvb0007qx1shidew91l', 'cmpjsq4b20001qxws79jyscoy', 'cmpm3ndvb0005qx1shhmfd2mz', 'cmpjunah8000bqxbg85vrzz9q', NULL, 0.00, 1.00, 75000.00),
('cmrrjp5l4000bqx1owbn5apmf', 'cmpjsq4b20001qxws79jyscoy', 'cmrrjp5l40009qx1ogqsazkcn', 'cmpjumlfs0009qxbgg79rxrdb', NULL, 0.00, 20.00, 100000.00);

-- --------------------------------------------------------

--
-- Table structure for table `activity_other_costs`
--

DROP TABLE IF EXISTS `activity_other_costs`;
CREATE TABLE IF NOT EXISTS `activity_other_costs` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `activity_id` varchar(40) NOT NULL,
  `description` varchar(200) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_aoc_activity` (`activity_id`),
  KEY `idx_aoc_farm` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_other_costs`
--

INSERT INTO `activity_other_costs` (`id`, `farm_id`, `activity_id`, `description`, `amount`) VALUES
('01M25NP4ZH639WP95R2QFK0CFQ', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25NP4ZFBMPMPFR467T81XRB', 'Knapsack hire', 1500.00),
('cmpjuq1k6000eqxbgyn80h744', 'cmpjsq4b20001qxws79jyscoy', 'cmpjuq1k6000dqxbghvqwnc61', 'Labour', 73423.00),
('cmpjxh9bw0006qxkkez8wpp9t', 'cmpjsq4b20001qxws79jyscoy', 'cmpjxh9bv0005qxkk0ne6km8b', 'Transport', 66222.00),
('cmpjxko12000cqxkkfuancat6', 'cmpjsq4b20001qxws79jyscoy', 'cmpjxko120008qxkk71q7fnxv', 'Transport', 15000.00),
('cmpjxmuk1000fqxkk811t9r8v', 'cmpjsq4b20001qxws79jyscoy', 'cmpjxmuk1000eqxkkzswbwoud', 'Transport', 55000.00),
('cmpjxrke7000iqxkk97h36mhu', 'cmpjsq4b20001qxws79jyscoy', 'cmpjxrke7000hqxkkb3fjpoax', 'Transport and other costs', 231778.00),
('cmpjxx4ax000pqxkk1ncjx63j', 'cmpjsq4b20001qxws79jyscoy', 'cmpjxx4ax000oqxkktjsooty8', 'Transport', 11111.00),
('cmpjxz1s9000sqxkk21f8vwjq', 'cmpjsq4b20001qxws79jyscoy', 'cmpjxz1s9000rqxkkh6ag75wr', 'Transport', 38889.00),
('cmpjy4zpt000vqxkkl4z2a2kj', 'cmpjsq4b20001qxws79jyscoy', 'cmpjy4zpt000uqxkkxvmo1y8o', 'Winnowing', 12000.00),
('cmpjyagg9000yqxkk9hpu9hv2', 'cmpjsq4b20001qxws79jyscoy', 'cmpjyagg9000xqxkkogb5yum8', 'Transport', 8000.00),
('cmpjyc2am0011qxkk86ps7vzn', 'cmpjsq4b20001qxws79jyscoy', 'cmpjyc2am0010qxkkkip38bu8', 'Transport', 12000.00),
('cmpjydcbr0014qxkkxpe5o56g', 'cmpjsq4b20001qxws79jyscoy', 'cmpjydcbr0013qxkkqs1pjcpa', 'Transport', 28000.00),
('cmpjzjptu001mqxkkwspcsjct', 'cmpjsq4b20001qxws79jyscoy', 'cmpjzjptt001iqxkkycztgv0y', 'Transport', 85000.00),
('cmpk00lqz001uqxkkas4qw27y', 'cmpjsq4b20001qxws79jyscoy', 'cmpk00lqy001oqxkkv3olyi39', 'Transport', 80000.00),
('cmpk0akii0021qxkkv0iw9w3s', 'cmpjsq4b20001qxws79jyscoy', 'cmpk0akii001wqxkkj1dgrrad', 'Transport', 69000.00),
('cmpk0akii0022qxkkaqiixxfs', 'cmpjsq4b20001qxws79jyscoy', 'cmpk0akii001wqxkkj1dgrrad', 'Weeding balance', 29998.00),
('cmpk15631002bqxkk8wz5m054', 'cmpjsq4b20001qxws79jyscoy', 'cmpk156310028qxkka6f3x767', 'Transport ', 68000.00),
('cmpk15631002cqxkk1naht6fk', 'cmpjsq4b20001qxws79jyscoy', 'cmpk156310028qxkka6f3x767', 'Food', 30000.00),
('cmpk15631002dqxkkio98059u', 'cmpjsq4b20001qxws79jyscoy', 'cmpk156310028qxkka6f3x767', 'Security', 30000.00),
('cmpk15631002eqxkkeh8ax7i4', 'cmpjsq4b20001qxws79jyscoy', 'cmpk156310028qxkka6f3x767', 'Other costs', 17000.00),
('cmpk15631002fqxkkbh804g01', 'cmpjsq4b20001qxws79jyscoy', 'cmpk156310028qxkka6f3x767', 'Sacks 100', 110000.00),
('cmpk15631002gqxkk0p1k95e6', 'cmpjsq4b20001qxws79jyscoy', 'cmpk156310028qxkka6f3x767', 'Shelling', 126000.00),
('cmpk15631002hqxkkihboq6ak', 'cmpjsq4b20001qxws79jyscoy', 'cmpk156310028qxkka6f3x767', 'Bags loading into house', 33000.00),
('cmpk15631002iqxkkxwodq89l', 'cmpjsq4b20001qxws79jyscoy', 'cmpk156310028qxkka6f3x767', 'Shelling labour', 66000.00),
('cmpk19g3g002nqxkku7gpp1qd', 'cmpjsq4b20001qxws79jyscoy', 'cmpk19g3g002kqxkky593tmie', 'Transport hire', 713000.00),
('cmrrjp5l4000eqx1otjs6zj0q', 'cmpjsq4b20001qxws79jyscoy', 'cmrrjp5l40009qx1ogqsazkcn', 'Tractor Hire', 200000.00);

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` varchar(40) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `email`, `password`, `name`, `is_super_admin`, `last_login_at`, `created_at`) VALUES
('01M27QMCYETHMXFQTJZ1SR5CTR', 'admin@agrivault.local', '$argon2id$v=19$m=65536,t=4,p=2$Tm5Hdkk2WlhjUVMyUVo2cg$20qGlK79rxfVz176jB1azaCXTyHiYlkJCN/+uaOm+cM', 'Site Admin', 1, '2026-09-11 08:54:18', '2026-09-11 07:59:05');

-- --------------------------------------------------------

--
-- Table structure for table `ai_insights_cache`
--

DROP TABLE IF EXISTS `ai_insights_cache`;
CREATE TABLE IF NOT EXISTS `ai_insights_cache` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `kind` varchar(40) NOT NULL,
  `content` text NOT NULL,
  `model` varchar(80) NOT NULL,
  `cached_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ai_farm_kind` (`farm_id`,`kind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `animals`
--

DROP TABLE IF EXISTS `animals`;
CREATE TABLE IF NOT EXISTS `animals` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `livestock_type_id` varchar(40) NOT NULL,
  `tag` varchar(60) DEFAULT NULL,
  `name` varchar(80) DEFAULT NULL,
  `animal_group` varchar(80) DEFAULT NULL,
  `sex` varchar(10) NOT NULL DEFAULT 'Unknown',
  `birth_date` date DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `acquisition_type` varchar(40) NOT NULL DEFAULT 'Born on farm',
  `acquisition_cost` decimal(12,2) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `breed` varchar(80) DEFAULT NULL,
  `colour` varchar(60) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `parent_id` varchar(40) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_animals_farm` (`farm_id`,`status`),
  KEY `idx_animals_type` (`livestock_type_id`),
  KEY `idx_animals_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `animals`
--

INSERT INTO `animals` (`id`, `farm_id`, `livestock_type_id`, `tag`, `name`, `animal_group`, `sex`, `birth_date`, `acquisition_date`, `acquisition_type`, `acquisition_cost`, `status`, `breed`, `colour`, `weight`, `notes`, `parent_id`, `created_at`, `updated_at`) VALUES
('01M25PFT2QXT25W0NRYSH9CHR9', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25PFSCTWMPV5YSMJ42HVX25', 'C-001', 'Daisy', NULL, 'Female', NULL, '2025-06-01', 'Purchased', 250000.00, 'Sold', 'Friesian', NULL, 420.00, NULL, NULL, '2026-09-10 13:00:37', '2026-09-10 13:00:38'),
('cmptxwl9a0009qxt099h5d482', 'cmpjsq4b20001qxws79jyscoy', 'ed18d4b7-05e2-4b34-8ed8-bf36c879a484', 'Pen001', NULL, 'Pen A', 'Male', '2026-05-28', '2026-05-28', 'Purchased', 100000.00, 'Active', 'Friesian', 'Light Pink', 10.00, NULL, NULL, '2026-09-10 13:05:43', '2026-09-10 13:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `animal_expenses`
--

DROP TABLE IF EXISTS `animal_expenses`;
CREATE TABLE IF NOT EXISTS `animal_expenses` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `animal_id` varchar(40) DEFAULT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'Other',
  `description` varchar(200) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ae_farm_date` (`farm_id`,`date`),
  KEY `idx_ae_animal` (`animal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `animal_expenses`
--

INSERT INTO `animal_expenses` (`id`, `farm_id`, `animal_id`, `category`, `description`, `amount`, `date`, `notes`, `created_at`) VALUES
('cmptxwl9g000bqxt058e4hpsp', 'cmpjsq4b20001qxws79jyscoy', 'cmptxwl9a0009qxt099h5d482', 'Purchase', 'Purchased Pigs #Pen001', 100000.00, '2026-05-28', NULL, '2026-09-10 13:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `animal_health`
--

DROP TABLE IF EXISTS `animal_health`;
CREATE TABLE IF NOT EXISTS `animal_health` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `animal_id` varchar(40) NOT NULL,
  `type` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `veterinarian` varchar(120) DEFAULT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ah_animal` (`animal_id`),
  KEY `idx_ah_farm` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `animal_production`
--

DROP TABLE IF EXISTS `animal_production`;
CREATE TABLE IF NOT EXISTS `animal_production` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `animal_id` varchar(40) DEFAULT NULL,
  `type` varchar(60) NOT NULL,
  `quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit` varchar(20) NOT NULL DEFAULT '',
  `date` date NOT NULL,
  `price_per_unit` decimal(12,2) DEFAULT NULL,
  `total_value` decimal(14,2) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ap_animal` (`animal_id`),
  KEY `idx_ap_farm_date` (`farm_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `animal_production`
--

INSERT INTO `animal_production` (`id`, `farm_id`, `animal_id`, `type`, `quantity`, `unit`, `date`, `price_per_unit`, `total_value`, `notes`, `created_at`) VALUES
('01M25PFV06V0PC8Z5S1HJER9T2', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25PFT2QXT25W0NRYSH9CHR9', 'Milk', 12.000, 'litres', '2026-02-02', 800.00, 9600.00, NULL, '2026-09-10 13:00:38');

-- --------------------------------------------------------

--
-- Table structure for table `animal_sales`
--

DROP TABLE IF EXISTS `animal_sales`;
CREATE TABLE IF NOT EXISTS `animal_sales` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `animal_id` varchar(40) NOT NULL,
  `transaction_id` varchar(40) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `weight_at_sale` decimal(10,2) DEFAULT NULL,
  `price_per_kg` decimal(12,2) DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `buyer` varchar(160) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_as_farm` (`farm_id`),
  KEY `idx_as_animal` (`animal_id`),
  KEY `fk_as_tx` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `animal_sales`
--

INSERT INTO `animal_sales` (`id`, `farm_id`, `animal_id`, `transaction_id`, `sale_date`, `quantity`, `weight_at_sale`, `price_per_kg`, `total_amount`, `buyer`, `notes`, `created_at`) VALUES
('01M25PFV6GCKN7JY6SQSQEJ2YB', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25PFT2QXT25W0NRYSH9CHR9', '01M25PFV6GCKN7JY6SQSQEJ2YA', '2026-06-15', 1, 430.00, NULL, 380000.00, 'Local butcher', NULL, '2026-09-10 13:00:38');

-- --------------------------------------------------------

--
-- Table structure for table `animal_weight`
--

DROP TABLE IF EXISTS `animal_weight`;
CREATE TABLE IF NOT EXISTS `animal_weight` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `animal_id` varchar(40) NOT NULL,
  `weight` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(10) NOT NULL DEFAULT 'kg',
  `date` date NOT NULL,
  `notes` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aw_animal` (`animal_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `animal_weight`
--

INSERT INTO `animal_weight` (`id`, `farm_id`, `animal_id`, `weight`, `unit`, `date`, `notes`) VALUES
('01M25PFTRZ53N8RFDK5ZB6N084', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25PFT2QXT25W0NRYSH9CHR9', 420.00, 'kg', '2026-02-01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `api_refresh_tokens`
--

DROP TABLE IF EXISTS `api_refresh_tokens`;
CREATE TABLE IF NOT EXISTS `api_refresh_tokens` (
  `id` varchar(40) NOT NULL,
  `user_id` varchar(40) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `device_label` varchar(120) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_api_rt_hash` (`token_hash`),
  KEY `idx_api_rt_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `api_refresh_tokens`
--

INSERT INTO `api_refresh_tokens` (`id`, `user_id`, `token_hash`, `device_label`, `expires_at`, `revoked_at`, `created_at`) VALUES
('01M27RAGV3KVNVBZ7T2E5RFPCY', '01M25MGE6GRWSYBM4G8V9FMJP0', 'edcf84744bd9d69d023f6e057e115a46548fb2aa6d86c23ee722057eb1ddd6ff', NULL, '2026-10-11 10:11:10', NULL, '2026-09-11 08:11:10'),
('01M27RAW6J64HWR0R1G6G6CW1E', '01M25MGE6GRWSYBM4G8V9FMJP0', 'd6c1a2a6b5e1652162458a78544967b4ef90235c06eb01fe4271ff2b7e6b1588', NULL, '2026-10-11 10:11:22', NULL, '2026-09-11 08:11:22'),
('01M27RB9800JPXCJNHJVB501EY', '01M25MGE6GRWSYBM4G8V9FMJP0', '23ff91945f2266b5d8f93baa42f4886b833ccf7413191ffcdca7c3ebc966ad8f', NULL, '2026-10-11 10:11:35', '2026-09-11 08:11:38', '2026-09-11 08:11:35'),
('01M27RBBX831JA2H2RYMEKDWQX', '01M25MGE6GRWSYBM4G8V9FMJP0', '4c2c2ca8cf2ad9ffacb2edce784dd2a8902572b953355fe89af51b3bdbcc4869', NULL, '2026-10-11 10:11:38', NULL, '2026-09-11 08:11:38'),
('01M27RBQVMHH6X7F4VZ2YXPPKS', '01M25MGE6GRWSYBM4G8V9FMJP0', '9fe8ea92bd912e09c4d606c697b03221abcbc1d3b1dd3d382bf8fe670d8f0b0e', NULL, '2026-10-11 10:11:50', '2026-09-11 08:11:52', '2026-09-11 08:11:50'),
('01M27RDHKXTPFKDYYTSNHH11TX', '01M25MGE6GRWSYBM4G8V9FMJP0', '25c662a6d6742be3404703b0c98015bc2170224956e7d7f2bc8df6aaf38f159e', NULL, '2026-10-11 10:12:49', NULL, '2026-09-11 08:12:49'),
('01M27RJWW1Q92DQEQK1QRF7BMJ', '01M25MGE6GRWSYBM4G8V9FMJP0', '520a6a550e607ee8a92eb1a121d057a8104c13d10fb8e87af0d06cdec4df7f22', NULL, '2026-10-11 10:15:45', NULL, '2026-09-11 08:15:45');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` varchar(40) NOT NULL,
  `actor_type` enum('user','admin','system') NOT NULL DEFAULT 'system',
  `actor_id` varchar(40) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `target_type` varchar(60) DEFAULT NULL,
  `target_id` varchar(40) DEFAULT NULL,
  `farm_id` varchar(40) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_farm` (`farm_id`),
  KEY `idx_audit_actor` (`actor_id`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `actor_type`, `actor_id`, `action`, `target_type`, `target_id`, `farm_id`, `ip`, `meta`, `created_at`) VALUES
('01M25MGEBD4YRCQ9Q9T3SHR7RK', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.register', NULL, NULL, NULL, '127.0.0.1', '{\"tier\":\"Trial\"}', '2026-09-10 12:26:01'),
('01M25MGY2RSQAFBFQY13TQ9TYS', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.activated', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 12:26:17'),
('01M25MGYYY58QBVT5WZ02FGF5C', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'farm.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"farm_id\":\"01M25MGYXN4226KGXM4C0B6Y2A\"}', '2026-09-10 12:26:18'),
('01M25MHJ0JHRQXH2JNQ8Z08QXD', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.logout', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 12:26:37'),
('01M25MHK6KDEWA0NE09A63WX72', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 12:26:39'),
('01M25MJ4W2PG3711S1S3XXQDP9', 'system', NULL, 'auth.login_failed', 'user', NULL, NULL, '127.0.0.1', '{\"email\":\"owner@example.com\"}', '2026-09-10 12:26:57'),
('01M25MJJGJG5BE5FP3CE5RWAQS', 'system', NULL, 'auth.login_failed', 'user', NULL, NULL, '127.0.0.1', '{\"email\":\"nobody@example.com\"}', '2026-09-10 12:27:11'),
('01M25MKMZMZV8Q6MN6H09G54WJ', 'system', NULL, 'auth.login_failed', 'user', NULL, NULL, '127.0.0.1', '{\"email\":\"nobody@example.com\"}', '2026-09-10 12:27:46'),
('01M25N7Q7ZK11VSJSAFYTGGJWK', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 12:38:44'),
('01M25N7R7F2RTGM5TP5PXVMAXC', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'field.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"field_id\":\"01M25N7R637E9MPQ1AYST8DXTS\"}', '2026-09-10 12:38:45'),
('01M25N7RZE6E0T0HVYAP0MJ3NA', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'crop.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"crop_field_id\":\"01M25N7RY7WFNC9Q67HWAXNTNS\"}', '2026-09-10 12:38:45'),
('01M25NP2V50XRNPXGQWMR4QJ60', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 12:46:34'),
('01M25NP451YNE07T9RSMNGF4NJ', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'employee.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"employee_id\":\"01M25NP437SVM0YER9V4EF9FR3\"}', '2026-09-10 12:46:36'),
('01M25NP51WG0BDYGKCNWKAR48P', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'activity.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"activity_id\":\"01M25NP4ZFBMPMPFR467T81XRB\"}', '2026-09-10 12:46:37'),
('01M25NP601BADBDJ7Z3HB6VF6Y', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'yield.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"yield_id\":\"01M25NP5YX57WHNC0MK3BZ0FPY\"}', '2026-09-10 12:46:38'),
('01M25P514BAR15HWNVA002F1P9', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 12:54:44'),
('01M25P527YNMPR0WPZP6KSDJPR', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'transaction.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"tx_id\":\"01M25P5264EWVGQ0XNW8VZ0W9Y\",\"amount\":250000}', '2026-09-10 12:54:45'),
('01M25P52K7B18NE4TR1ZXXYKVP', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'overhead.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"overhead_id\":\"01M25P52J7ET315EYGJV3J432N\"}', '2026-09-10 12:54:46'),
('01M25P52Y9TJRJMX2S4W5V2Q6F', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'inventory.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"item_id\":\"01M25P52Y3T01BE9XDFHMF0FRE\"}', '2026-09-10 12:54:46'),
('01M25P53F08GM1Q92YGNB8N2TG', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'inventory.sold', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"item_id\":\"01M25P52Y3T01BE9XDFHMF0FRE\",\"qty\":40,\"total\":2600000}', '2026-09-10 12:54:47'),
('01M25PFRYCAGKCCSRSDV483Z0P', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 13:00:36'),
('01M25PFSDR7K1DPKZBFB11D833', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'livestock.type_created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"type_id\":\"01M25PFSCTWMPV5YSMJ42HVX25\"}', '2026-09-10 13:00:37'),
('01M25PFT3RR15NW87PHN9DQNYS', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'livestock.animal_created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"animal_id\":\"01M25PFT2QXT25W0NRYSH9CHR9\"}', '2026-09-10 13:00:37'),
('01M25PFTV2MA0W793A358N07GX', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'livestock.event_added', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"animal_id\":\"01M25PFT2QXT25W0NRYSH9CHR9\",\"kind\":\"weight\"}', '2026-09-10 13:00:38'),
('01M25PFV1KTNHV6JWQBNF600FE', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'livestock.event_added', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"animal_id\":\"01M25PFT2QXT25W0NRYSH9CHR9\",\"kind\":\"production\"}', '2026-09-10 13:00:38'),
('01M25PFV7GS9190Z0FDRA60KX7', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'livestock.sold', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"animal_id\":\"01M25PFT2QXT25W0NRYSH9CHR9\",\"total\":380000}', '2026-09-10 13:00:39'),
('01M25YDAYR5PTNAHWXT7T6VZ7J', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 15:19:05'),
('01M25YDD29C9K3BMB3GZA5VQYX', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'gis.boundary_saved', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"field_id\":\"01M25N7R637E9MPQ1AYST8DXTS\"}', '2026-09-10 15:19:07'),
('01M25YFD72Y050BDC8GQYVT0ZZ', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 15:20:13'),
('01M25YFEM80AA6E127KJ531JQ6', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'gis.marker_added', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', NULL, '2026-09-10 15:20:14'),
('01M25YKERFGH9JR5BZ0JDXMPPF', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-10 15:22:25'),
('01M27PJQGBFC7GPFE1XVHK7GPG', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 07:40:42'),
('01M27PJZRKNB3N4Q50TF9DESTV', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'report.pack_viewed', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"type\":\"loan\"}', '2026-09-11 07:40:50'),
('01M27PK03K01367XTW4303V7TT', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'report.pack_viewed', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"type\":\"buyer\"}', '2026-09-11 07:40:51'),
('01M27PK0JF580H66WK1ASRNNH3', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'report.pack_viewed', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"type\":\"audit\"}', '2026-09-11 07:40:51'),
('01M27PK0XT5F84Y7E644H7ZBEZ', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'report.pack_viewed', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"type\":\"insurance\"}', '2026-09-11 07:40:52'),
('01M27PM8K76CHKHVRWJ1EXK8KS', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'report.csv_export', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"section\":\"fields\"}', '2026-09-11 07:41:32'),
('01M27PM8Z2TMGRSGFVY0G4443S', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'report.csv_export', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"section\":\"fields\"}', '2026-09-11 07:41:33'),
('01M27PM9C1SZN09GCT5H9QFYXK', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'report.pack_viewed', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"type\":\"loan\"}', '2026-09-11 07:41:33'),
('01M27Q3PRV3V6846BBZP6MJN80', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'document.uploaded', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"document_id\":\"01M27Q3PR0VWX1J50HPVN9B4CT\"}', '2026-09-11 07:49:58'),
('01M27Q51M4KWJ2QADNS4KCJ2RS', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'document.downloaded', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"document_id\":\"01M27Q3PR0VWX1J50HPVN9B4CT\"}', '2026-09-11 07:50:42'),
('01M27Q5MRB1BFXKNP14TYZ7Z7G', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'document.deleted', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"document_id\":\"01M27Q3PR0VWX1J50HPVN9B4CT\"}', '2026-09-11 07:51:02'),
('01M27Q695FPYZRNBYZMWQR3MNZ', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'farm.settings_updated', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', NULL, '2026-09-11 07:51:23'),
('01M27QMW7XV2JE3KS0CXET2E65', 'admin', '01M27QMCYETHMXFQTJZ1SR5CTR', 'admin.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 07:59:21'),
('01M27QNB91NC38NX8PGVJZHSA0', 'admin', '01M27QMCYETHMXFQTJZ1SR5CTR', 'admin.payment_recorded', 'subscription', '01M25MGE7E0WJWAP6NJJT4HHFX', NULL, '127.0.0.1', '{\"amount\":\"9500\"}', '2026-09-11 07:59:36'),
('01M27QNT0P6AY6V6FC0TT8JGHV', 'admin', '01M27QMCYETHMXFQTJZ1SR5CTR', 'admin.market_price_created', 'market_price', '01M27QNSZMQWWWA1WHQ3TFK0X5', NULL, '127.0.0.1', NULL, '2026-09-11 07:59:51'),
('01M27QNVXXK09M7RXVF9P2V7QT', 'admin', '01M27QMCYETHMXFQTJZ1SR5CTR', 'admin.cms_content_updated', 'site_content', 'hero_title', NULL, '127.0.0.1', NULL, '2026-09-11 07:59:53'),
('01M27QSEEBYZJXR57GES2XP9DT', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:01:51'),
('01M27QSFABRXNEMYD1258H1AMQ', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'field.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"field_id\":\"01M27QSF8YYVYHQ82SN8AQGK0T\"}', '2026-09-11 08:01:51'),
('01M27QVJP1RAZ3ACAKQG1TX0M2', 'user', '01M27QVJGWN3ZRVSBHYEBTT9WB', 'auth.register', NULL, NULL, NULL, '127.0.0.1', '{\"tier\":\"Regular\"}', '2026-09-11 08:03:00'),
('01M27R15452JVF65CFWKWRY716', 'system', NULL, 'public.contact_submitted', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:06:03'),
('01M27R57EAAT4HRZEWKRAE0K4F', 'user', '01M27QVJGWN3ZRVSBHYEBTT9WB', 'auth.login_inactive', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:08:17'),
('01M27RAGQEC7H9KFVWGK612GG7', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:11:10'),
('01M27RAW4NB96ZSDNSS9Y1A23W', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:11:22'),
('01M27RB96GBS9BBW2W7HDAZSVZ', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:11:35'),
('01M27RBAAYNDBF01Z7E5DW2NJC', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.field.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"field_id\":\"01M27RBA85E22N5X65K9HF72QV\"}', '2026-09-11 08:11:36'),
('01M27RBATJ6JCQX2RG5E01PTV9', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.activity.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"activity_id\":\"01M27RBASAEFPP6GC2XXTR0D3E\"}', '2026-09-11 08:11:37'),
('01M27RBBCN9RSANJFFYJ2YG630', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.transaction.created', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"tx_id\":\"01M27RBB9VHGFX5XTJ4B04Y3FN\"}', '2026-09-11 08:11:37'),
('01M27RBQS8TSVHPP33FDPKDDJW', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:11:50'),
('01M27RBS0BFW1T687XGBSSGW7K', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.sync', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"activities\":0,\"transactions\":0}', '2026-09-11 08:11:51'),
('01M27RDHFH10WMTJGAJEB28B27', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:12:49'),
('01M27RDJSQ98E6RW610F0T8G3Y', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.sync', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '127.0.0.1', '{\"activities\":2,\"transactions\":1}', '2026-09-11 08:12:50'),
('01M27RHYSS1RVNJ4Q9MS6QASR8', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:15:14'),
('01M27RJWSRN3CA6PK27X5VPSAC', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'api.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:15:44'),
('01M27T6P24SMMZ62K4ZX7JW4QE', 'system', NULL, 'auth.login_failed', 'user', NULL, NULL, '127.0.0.1', '{\"email\":\"admin@agrivault.local\"}', '2026-09-11 08:44:01'),
('01M27T8BDDF293HWXMFWBGTDQJ', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:44:56'),
('01M27TA1MC7AWK77ZYRYC1M7ZD', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:45:52'),
('01M27TQ6MRGAVENJ9D10X0BA0C', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:53:03'),
('01M27TSGH4N246V6W7YM3MGZCS', 'admin', '01M27QMCYETHMXFQTJZ1SR5CTR', 'admin.login', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:54:18'),
('01M27V2H51QPHFJ2AH3NN721A3', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.logout', NULL, NULL, NULL, '127.0.0.1', NULL, '2026-09-11 08:59:14'),
('01M27YBGEP4RB19M3D249QAZJQ', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '::1', NULL, '2026-09-11 09:56:34'),
('01M284YG8NC6E8DR9SKST97AW1', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.logout', NULL, NULL, NULL, '::1', NULL, '2026-09-11 11:51:48'),
('01M28557XRGGWFBJV820T7C84M', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'auth.login', NULL, NULL, NULL, '::1', NULL, '2026-09-11 11:55:29'),
('01M28B8T9H34TCKBF6BVKGB5VK', 'user', '01M25MGE6GRWSYBM4G8V9FMJP0', 'document.uploaded', NULL, NULL, '01M25MGYXN4226KGXM4C0B6Y2A', '::1', '{\"document_id\":\"01M28B8T3F6QW8GK855M67CKSK\"}', '2026-09-11 13:42:17');

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

DROP TABLE IF EXISTS `auth_tokens`;
CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `id` varchar(40) NOT NULL,
  `user_id` varchar(40) NOT NULL,
  `type` enum('activation','reset') NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_auth_tokens_hash` (`token_hash`),
  KEY `idx_auth_tokens_user_type` (`user_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auth_tokens`
--

INSERT INTO `auth_tokens` (`id`, `user_id`, `type`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
('01M25MGE8XKT0S4TW80BD8GBMY', '01M25MGE6GRWSYBM4G8V9FMJP0', 'activation', '03471d7e1e64863cd4f69ac1d2ede4e735d8e4c55091c85589ac44720b6d6d5a', '2026-09-11 14:26:01', '2026-09-10 12:26:17', '2026-09-10 12:26:01'),
('01M27QVJKVBE19WRMZ45RAE3R2', '01M27QVJGWN3ZRVSBHYEBTT9WB', 'activation', '0a22cf06da344929855884c711bc6772ccbe192dd4db665f6955edbd0290b59f', '2026-09-12 10:03:00', NULL, '2026-09-11 08:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `cms_features`
--

DROP TABLE IF EXISTS `cms_features`;
CREATE TABLE IF NOT EXISTS `cms_features` (
  `id` varchar(40) NOT NULL,
  `icon` varchar(40) NOT NULL DEFAULT 'leaf',
  `title` varchar(160) NOT NULL,
  `description` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_features`
--

INSERT INTO `cms_features` (`id`, `icon`, `title`, `description`, `sort_order`, `is_active`) VALUES
('feat_credit', 'gauge', 'Credit readiness', 'A transparent score from your own data — cashflow, activity cost and revenue factors.', 5, 1),
('feat_economics', 'bar-chart', 'Season economics', 'Cashflow, crop and field profitability, cost per hectare and per kilogram.', 4, 1),
('feat_map', 'map', 'Field mapping', 'Draw field boundaries and management zones; mark boreholes, sheds, gates and roads.', 6, 1),
('feat_proof', 'shield', 'Built to be proven', 'Buyer packs, loan-readiness files, audit and insurance evidence generated from your real records.', 2, 1),
('feat_records', 'leaf', 'Every record in one vault', 'Fields, crops, activities, inputs, labour, finance, inventory and livestock — structured, searchable, and linked.', 1, 1),
('feat_team', 'users', 'Team access, done right', 'Owner, manager, agronomist, accountant and field-worker roles with per-resource permissions.', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `cms_media`
--

DROP TABLE IF EXISTS `cms_media`;
CREATE TABLE IF NOT EXISTS `cms_media` (
  `id` varchar(40) NOT NULL,
  `key` varchar(80) NOT NULL,
  `url` varchar(500) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'image',
  `label` varchar(160) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cms_media_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_pages`
--

DROP TABLE IF EXISTS `cms_pages`;
CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id` varchar(40) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `title` varchar(160) NOT NULL,
  `content` longtext NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cms_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cms_pages`
--

INSERT INTO `cms_pages` (`id`, `slug`, `title`, `content`, `is_public`, `updated_at`) VALUES
('page_about', 'about', 'About AgriVault', '<p>AgriVault is a farm-record vault built for field teams, managers, accountants, lenders, buyers, auditors and insurers who need real, provable farm data.</p>', 1, '2026-09-11 07:59:05'),
('page_privacy', 'privacy', 'Privacy Policy', '<p>AgriVault stores your farm records to provide the service you sign up for. We do not sell your data. Contact support to request an export or deletion of your account.</p>', 1, '2026-09-11 07:59:05'),
('page_security', 'security', 'Security', '<p>Passwords are hashed with Argon2id/bcrypt. All state-changing requests are CSRF-protected. Every farm-scoped query is restricted to your account\'s farm membership. Report a security concern to the support email on the Contact page.</p>', 1, '2026-09-11 07:59:05'),
('page_terms', 'terms', 'Terms of Service', '<p>By using AgriVault you agree to use the service for lawful farm record-keeping. Accounts may be suspended for abuse or non-payment. See your subscription plan for feature and storage limits.</p>', 1, '2026-09-11 07:59:05');

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

DROP TABLE IF EXISTS `contact_submissions`;
CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `email` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `replied_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contact_status` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_submissions`
--

INSERT INTO `contact_submissions` (`id`, `name`, `email`, `message`, `status`, `notes`, `created_at`, `replied_at`) VALUES
('01M27R15264SH7CMBXVNY0EK55', 'Jane Farmer', 'jane@example.com', 'I would like to learn more about AgriVault', 'new', NULL, '2026-09-11 08:06:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `crop_fields`
--

DROP TABLE IF EXISTS `crop_fields`;
CREATE TABLE IF NOT EXISTS `crop_fields` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `field_id` varchar(40) NOT NULL,
  `crop_type_id` varchar(40) NOT NULL,
  `variety` varchar(120) NOT NULL DEFAULT '',
  `area_planted` decimal(12,3) NOT NULL DEFAULT 0.000,
  `season` varchar(60) NOT NULL DEFAULT '',
  `planting_date` date NOT NULL,
  `expected_harvest_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `archived_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cf_farm` (`farm_id`),
  KEY `idx_cf_field` (`field_id`),
  KEY `idx_cf_crop_type` (`crop_type_id`),
  KEY `idx_cf_season` (`farm_id`,`season`),
  KEY `idx_cf_active` (`farm_id`,`is_archived`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crop_fields`
--

INSERT INTO `crop_fields` (`id`, `farm_id`, `field_id`, `crop_type_id`, `variety`, `area_planted`, `season`, `planting_date`, `expected_harvest_date`, `status`, `is_archived`, `archived_at`, `archived_reason`, `created_at`, `updated_at`) VALUES
('01M25N7RY7WFNC9Q67HWAXNTNS', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25N7R637E9MPQ1AYST8DXTS', 'ct_maize', 'SC627', 4.000, '2025/26 Rain Season', '2025-12-01', '2026-04-20', 'Active', 0, NULL, NULL, '2026-09-10 12:38:45', '2026-09-10 12:38:45'),
('24bcf1ac-b0a9-4d49-9e18-0b39182aeb73', 'cmpjsq4b20001qxws79jyscoy', '33f6f6af-3569-47ff-be09-57e2ea20ed4d', 'ct_maize', 'sc123', 2.000, '2025/26', '2026-08-16', '2032-12-13', 'Active', 0, NULL, NULL, '2026-08-16 19:35:48', '2026-09-10 13:05:42'),
('cmpjtue8h0003qxbgmfo8am8t', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'ct_cassava', 'A40', 1.210, '2026 Calendar Year', '2026-02-14', '2026-11-25', 'Active', 0, NULL, NULL, '2026-05-24 13:42:32', '2026-09-10 13:05:42'),
('cmpjtzmot0005qxbgxwv0tpa0', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'ct_maize', 'Kanyani, Fumba and ETG', 2.830, '2025/26 Rain Season', '2025-11-15', '2026-05-02', 'Archived', 1, NULL, NULL, '2026-05-24 13:46:37', '2026-09-10 13:05:42'),
('cmpju1phv0007qxbgl996xww5', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'ct_sunflower', 'Local', 0.810, '2025/26 Rain Season', '2025-11-15', '2026-05-02', 'Archived', 1, NULL, NULL, '2026-05-24 13:48:14', '2026-09-10 13:05:42'),
('cmrrjmf6b0005qx1ov88ns90y', 'cmpjsq4b20001qxws79jyscoy', 'cmrrjierv0001qx1opr3d19o4', 'ct_banana', 'Var', 200.000, 'Cool Dry Season 2026', '2026-07-19', '2027-11-19', 'Active', 0, NULL, NULL, '2026-07-19 08:37:58', '2026-09-10 13:05:42'),
('cmrrju1ig000gqx1o0zw0zktq', 'cmpjsq4b20001qxws79jyscoy', 'cmrrjierv0001qx1opr3d19o4', 'ct_maize', 'variety', 20.000, 'Cool Dry Season 2026', '2026-07-19', '2026-12-19', 'Active', 0, NULL, NULL, '2026-07-19 08:43:54', '2026-09-10 13:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `crop_types`
--

DROP TABLE IF EXISTS `crop_types`;
CREATE TABLE IF NOT EXISTS `crop_types` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_crop_types_scope_name` (`farm_id`,`name`),
  KEY `idx_crop_types_farm` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crop_types`
--

INSERT INTO `crop_types` (`id`, `farm_id`, `name`, `is_custom`, `created_at`) VALUES
('01M25PS38WMA083HET671A5NCP', 'cmpjsq4b20001qxws79jyscoy', 'Paprika', 1, '2026-09-10 13:05:42'),
('01M25PS3AM7ZJVWRRYXXB9C2SN', 'cmpjsq4b20001qxws79jyscoy', 'Chilli', 1, '2026-09-10 13:05:42'),
('01M25PS3C3K144ZVJKVE9KW9R4', 'cmpjsq4b20001qxws79jyscoy', 'Wheat', 1, '2026-09-10 13:05:42'),
('01M25PS3EK6S6Q9E6ZKYPNJ4VK', 'cmpjsq4b20001qxws79jyscoy', 'Barley', 1, '2026-09-10 13:05:42'),
('01M25PS3JVBZEPKXDZJQ0A6YJY', 'cmpjsq4b20001qxws79jyscoy', 'Sesame', 1, '2026-09-10 13:05:42'),
('ct_banana', NULL, 'Banana', 0, '2026-09-10 12:37:56'),
('ct_beans', NULL, 'Beans', 0, '2026-09-10 12:37:56'),
('ct_cabbage', NULL, 'Cabbage', 0, '2026-09-10 12:37:56'),
('ct_cassava', NULL, 'Cassava', 0, '2026-09-10 12:37:56'),
('ct_cotton', NULL, 'Cotton', 0, '2026-09-10 12:37:56'),
('ct_cowpea', NULL, 'Cowpea', 0, '2026-09-10 12:37:56'),
('ct_groundnut', NULL, 'Groundnut', 0, '2026-09-10 12:37:56'),
('ct_irishpotato', NULL, 'Irish Potato', 0, '2026-09-10 12:37:56'),
('ct_maize', NULL, 'Maize', 0, '2026-09-10 12:37:56'),
('ct_millet', NULL, 'Millet', 0, '2026-09-10 12:37:56'),
('ct_onion', NULL, 'Onion', 0, '2026-09-10 12:37:56'),
('ct_pigeonpea', NULL, 'Pigeon Pea', 0, '2026-09-10 12:37:56'),
('ct_rice', NULL, 'Rice', 0, '2026-09-10 12:37:56'),
('ct_sorghum', NULL, 'Sorghum', 0, '2026-09-10 12:37:56'),
('ct_soybean', NULL, 'Soybean', 0, '2026-09-10 12:37:56'),
('ct_sugarcane', NULL, 'Sugarcane', 0, '2026-09-10 12:37:56'),
('ct_sunflower', NULL, 'Sunflower', 0, '2026-09-10 12:37:56'),
('ct_sweetpotato', NULL, 'Sweet Potato', 0, '2026-09-10 12:37:56'),
('ct_tobacco', NULL, 'Tobacco', 0, '2026-09-10 12:37:56'),
('ct_tomato', NULL, 'Tomato', 0, '2026-09-10 12:37:56');

-- --------------------------------------------------------

--
-- Table structure for table `demo_bookings`
--

DROP TABLE IF EXISTS `demo_bookings`;
CREATE TABLE IF NOT EXISTS `demo_bookings` (
  `id` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `email` varchar(190) NOT NULL,
  `farm` varchar(160) NOT NULL DEFAULT '',
  `message` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `booked_for` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_demo_status` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `name` varchar(120) NOT NULL,
  `role` varchar(80) NOT NULL DEFAULT '',
  `pay_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pay_rate_unit` varchar(20) NOT NULL DEFAULT 'day',
  `phone` varchar(40) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employees_farm` (`farm_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `farm_id`, `name`, `role`, `pay_rate`, `pay_rate_unit`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
('01M25NP437SVM0YER9V4EF9FR3', '01M25MGYXN4226KGXM4C0B6Y2A', 'John Phiri', 'Foreman', 4000.00, 'day', NULL, 1, '2026-09-10 12:46:36', '2026-09-10 12:46:36'),
('cmpjumlfs0009qxbgg79rxrdb', 'cmpjsq4b20001qxws79jyscoy', 'Blessings Chibwe', 'Supervisor', 5000.00, 'Per Day', '+265888401762', 1, '2026-09-10 13:05:42', '2026-09-10 13:05:42'),
('cmpjunah8000bqxbg85vrzz9q', 'cmpjsq4b20001qxws79jyscoy', 'Group', 'Field Worker', 5000.00, 'Per Day', '', 1, '2026-09-10 13:05:42', '2026-09-10 13:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `farms`
--

DROP TABLE IF EXISTS `farms`;
CREATE TABLE IF NOT EXISTS `farms` (
  `id` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `location` varchar(160) NOT NULL DEFAULT '',
  `location_lat` double DEFAULT NULL,
  `location_lng` double DEFAULT NULL,
  `owner_name` varchar(120) DEFAULT NULL,
  `user_id` varchar(40) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_farms_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farms`
--

INSERT INTO `farms` (`id`, `name`, `location`, `location_lat`, `location_lng`, `owner_name`, `user_id`, `created_at`, `updated_at`) VALUES
('01M25MGYXN4226KGXM4C0B6Y2A', 'Kanyani Test Farm', 'Mchinji District', NULL, NULL, NULL, '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-10 12:26:18', '2026-09-11 07:51:23'),
('cmpjsq4b20001qxws79jyscoy', 'Emmanuel Mwinama\'s Farm', 'Mchinji', NULL, NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 13:11:13', '2026-09-10 13:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `farm_activities`
--

DROP TABLE IF EXISTS `farm_activities`;
CREATE TABLE IF NOT EXISTS `farm_activities` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `field_id` varchar(40) NOT NULL,
  `crop_field_id` varchar(40) DEFAULT NULL,
  `activity_type` varchar(80) NOT NULL,
  `date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `responsible_person_name` varchar(120) DEFAULT NULL,
  `responsible_employee_id` varchar(40) DEFAULT NULL,
  `created_by_id` varchar(40) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fa_farm_date` (`farm_id`,`date`),
  KEY `idx_fa_field` (`field_id`),
  KEY `idx_fa_crop_field` (`crop_field_id`),
  KEY `idx_fa_type` (`farm_id`,`activity_type`),
  KEY `fk_fa_employee` (`responsible_employee_id`),
  KEY `fk_fa_creator` (`created_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farm_activities`
--

INSERT INTO `farm_activities` (`id`, `farm_id`, `field_id`, `crop_field_id`, `activity_type`, `date`, `notes`, `responsible_person_name`, `responsible_employee_id`, `created_by_id`, `created_at`, `updated_at`) VALUES
('01M25NP4ZFBMPMPFR467T81XRB', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25N7R637E9MPQ1AYST8DXTS', '01M25N7RY7WFNC9Q67HWAXNTNS', 'Weeding', '2026-01-15', 'First weeding pass', NULL, '01M25NP437SVM0YER9V4EF9FR3', '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-10 12:46:37', '2026-09-10 12:46:37'),
('01M27RBASAEFPP6GC2XXTR0D3E', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25N7R637E9MPQ1AYST8DXTS', NULL, 'Weeding', '2026-09-01', 'API test', NULL, NULL, '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-11 08:11:37', '2026-09-11 08:11:37'),
('01M27RDJMM74W7B1Y4N7P4J3P0', '01M25MGYXN4226KGXM4C0B6Y2A', '01M27RBA85E22N5X65K9HF72QV', NULL, 'Harvesting', '2026-09-10', 'Sync test', NULL, NULL, '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-11 08:12:50', '2026-09-11 08:12:50'),
('5f01b04a-1f17-4cc4-8a90-534408b16e34', 'cmpjsq4b20001qxws79jyscoy', '33f6f6af-3569-47ff-be09-57e2ea20ed4d', '24bcf1ac-b0a9-4d49-9e18-0b39182aeb73', 'Planting', '2026-08-16', NULL, NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-08-16 19:37:47', '2026-09-10 13:05:42'),
('cmpjuq1k6000dqxbghvqwnc61', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtue8h0003qxbgmfo8am8t', 'Soil Preparation', '2026-01-19', 'Clearing land for cassava', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 14:07:09', '2026-09-10 13:05:42'),
('cmpjxh9bv0005qxkk0ne6km8b', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpju1phv0007qxbgl996xww5', 'Other', '2025-12-13', '', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:24:18', '2026-09-10 13:05:42'),
('cmpjxko120008qxkk71q7fnxv', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtue8h0003qxbgmfo8am8t', 'Planting', '2026-01-30', 'Planting cassava', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:26:57', '2026-09-10 13:05:42'),
('cmpjxmuk1000eqxkkzswbwoud', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtue8h0003qxbgmfo8am8t', 'Other', '2026-01-24', 'Sourcing seed', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:28:39', '2026-09-10 13:05:42'),
('cmpjxrke7000hqxkkb3fjpoax', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Other', '2025-12-13', 'Supervision visit', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:32:19', '2026-09-10 13:05:42'),
('cmpjxujjk000kqxkkhqdvjwtf', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpju1phv0007qxbgl996xww5', 'Planting', '2025-12-14', 'Planting sunflower', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:34:38', '2026-09-10 13:05:42'),
('cmpjxx4ax000oqxkktjsooty8', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpju1phv0007qxbgl996xww5', 'Other', '2025-12-30', 'Supervision', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:36:38', '2026-09-10 13:05:42'),
('cmpjxz1s9000rqxkkh6ag75wr', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Other', '2025-12-30', 'Supervisory visit', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:38:08', '2026-09-10 13:05:42'),
('cmpjy4zpt000uqxkkxvmo1y8o', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpju1phv0007qxbgl996xww5', 'Other', '2026-05-10', 'Winnowing harvest', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:42:45', '2026-09-10 13:05:42'),
('cmpjyagg9000xqxkkogb5yum8', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpju1phv0007qxbgl996xww5', 'Other', '2025-10-18', 'Supervisory visit', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:47:00', '2026-09-10 13:05:42'),
('cmpjyc2am0010qxkkkip38bu8', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtue8h0003qxbgmfo8am8t', 'Other', '2025-10-18', 'Supervisory visit', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:48:15', '2026-09-10 13:05:42'),
('cmpjydcbr0013qxkkqs1pjcpa', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Other', '2025-10-18', 'Supervisory visit', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 15:49:15', '2026-09-10 13:05:42'),
('cmpjz69h20016qxkklrl1gu24', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtue8h0003qxbgmfo8am8t', 'Soil Preparation', '2025-11-22', 'Land preparation', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 16:11:44', '2026-09-10 13:05:42'),
('cmpjz8wog001aqxkk2r4300qq', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpju1phv0007qxbgl996xww5', 'Soil Preparation', '2025-11-22', 'Land preparation', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 16:13:48', '2026-09-10 13:05:42'),
('cmpjzb28p001eqxkk9dl6y1f8', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Soil Preparation', '2025-11-22', 'Land preparation', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 16:15:28', '2026-09-10 13:05:42'),
('cmpjzjptt001iqxkkycztgv0y', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Planting', '2025-11-24', 'Planting maize', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 16:22:12', '2026-09-10 13:05:42'),
('cmpk00lqy001oqxkkv3olyi39', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Fertilizing', '2025-12-15', 'Fertilizer application', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 16:35:20', '2026-09-10 13:05:42'),
('cmpk0akii001wqxkkj1dgrrad', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Fertilizing', '2026-01-10', 'Fertilizer application', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 16:43:05', '2026-09-10 13:05:42'),
('cmpk0nhxl0024qxkkj8da10fr', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Other', '2026-04-24', 'stalks slashing and heaping', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 16:53:08', '2026-09-10 13:05:42'),
('cmpk156310028qxkka6f3x767', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Harvesting', '2026-05-02', 'Harvesting', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 17:06:52', '2026-09-10 13:05:42'),
('cmpk19g3g002kqxkky593tmie', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'Other', '2026-05-10', 'Transporting maize', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-24 17:10:12', '2026-09-10 13:05:42'),
('cmpm3kqb70001qx1s16lc2gyz', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpju1phv0007qxbgl996xww5', 'Harvesting', '2026-04-29', 'Harvesting of sunflower', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-26 03:50:30', '2026-09-10 13:05:42'),
('cmpm3ndvb0005qx1shhmfd2mz', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtue8h0003qxbgmfo8am8t', 'Weeding', '2026-04-15', 'Weed management for the cassava field', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-05-26 03:52:34', '2026-09-10 13:05:42'),
('cmrpzr85x0003qxrcpgqlv9r4', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtue8h0003qxbgmfo8am8t', 'Land preparation and ridging', '2026-07-18', '', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-07-18 06:34:04', '2026-09-10 13:05:42'),
('cmrrjp5l40009qx1ogqsazkcn', 'cmpjsq4b20001qxws79jyscoy', 'cmrrjierv0001qx1opr3d19o4', 'cmrrjmf6b0005qx1ov88ns90y', 'Land preparation and pits', '2026-07-19', '', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-07-19 08:40:06', '2026-09-10 13:05:42'),
('cmrrjvtys000kqx1o6ofksg2z', 'cmpjsq4b20001qxws79jyscoy', 'cmrrjierv0001qx1opr3d19o4', 'cmrrju1ig000gqx1o0zw0zktq', 'Planting', '2026-07-19', '', NULL, NULL, '01M25PS2SC6RETVVB12PY97YMX', '2026-07-19 08:45:17', '2026-09-10 13:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `farm_credit_scores`
--

DROP TABLE IF EXISTS `farm_credit_scores`;
CREATE TABLE IF NOT EXISTS `farm_credit_scores` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `user_id` varchar(40) NOT NULL,
  `score` int(11) NOT NULL,
  `grade` varchar(4) NOT NULL,
  `factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`factors`)),
  `generated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fcs_farm` (`farm_id`,`generated_at`),
  KEY `fk_fcs_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farm_credit_scores`
--

INSERT INTO `farm_credit_scores` (`id`, `farm_id`, `user_id`, `score`, `grade`, `factors`, `generated_at`) VALUES
('01M27PMJJF5VW40A2225DMNJKT', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25MGE6GRWSYBM4G8V9FMJP0', 90, 'A', '[{\"key\":\"record_completeness\",\"label\":\"Record completeness\",\"weight\":25,\"earned\":19,\"detail\":\"1 fields, 1 plantings, 1 activities, 3 transactions\"},{\"key\":\"cashflow\",\"label\":\"12-month cashflow\",\"weight\":25,\"earned\":25,\"detail\":\"Net 2,550,000 over the last year\"},{\"key\":\"revenue_evidence\",\"label\":\"Revenue evidence\",\"weight\":20,\"earned\":20,\"detail\":\"100% of sales linked to a yield or stock record\"},{\"key\":\"activity_discipline\",\"label\":\"Activity discipline\",\"weight\":15,\"earned\":15,\"detail\":\"100% of activities carry a cost breakdown\"},{\"key\":\"data_recency\",\"label\":\"Data recency\",\"weight\":15,\"earned\":11,\"detail\":\"Most recent record 88 days ago\"}]', '2026-09-11 07:41:42');

-- --------------------------------------------------------

--
-- Table structure for table `farm_documents`
--

DROP TABLE IF EXISTS `farm_documents`;
CREATE TABLE IF NOT EXISTS `farm_documents` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'other',
  `asset_id` varchar(64) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `size` int(11) DEFAULT NULL,
  `linked_to` varchar(40) DEFAULT NULL,
  `linked_type` varchar(40) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(40) NOT NULL,
  `uploaded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_farm` (`farm_id`),
  KEY `fk_doc_user` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farm_documents`
--

INSERT INTO `farm_documents` (`id`, `farm_id`, `name`, `type`, `asset_id`, `mime_type`, `size`, `linked_to`, `linked_type`, `notes`, `uploaded_by`, `uploaded_at`) VALUES
('01M28B8T3F6QW8GK855M67CKSK', '01M25MGYXN4226KGXM4C0B6Y2A', 'ewge', 'deed', '40/408f91934be3b409cf17ab6e4d4d4385.pdf', 'application/pdf', 384305, NULL, NULL, 'bevb', '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-11 13:42:17');

-- --------------------------------------------------------

--
-- Table structure for table `farm_markers`
--

DROP TABLE IF EXISTS `farm_markers`;
CREATE TABLE IF NOT EXISTS `farm_markers` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `field_id` varchar(40) DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'other',
  `label` varchar(120) NOT NULL,
  `lat` double NOT NULL,
  `lng` double NOT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `icon` varchar(40) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_marker_farm` (`farm_id`),
  KEY `fk_marker_field` (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farm_markers`
--

INSERT INTO `farm_markers` (`id`, `farm_id`, `field_id`, `type`, `label`, `lat`, `lng`, `notes`, `icon`, `created_at`) VALUES
('01M25YFEHNYVDJJ2Z6W7YP2RBB', '01M25MGYXN4226KGXM4C0B6Y2A', NULL, 'borehole', 'North borehole', -13.803, 33.604, NULL, NULL, '2026-09-10 15:20:14');

-- --------------------------------------------------------

--
-- Table structure for table `farm_members`
--

DROP TABLE IF EXISTS `farm_members`;
CREATE TABLE IF NOT EXISTS `farm_members` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `user_id` varchar(40) DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `invite_email` varchar(190) DEFAULT NULL,
  `invite_token` char(64) DEFAULT NULL,
  `invite_expires_at` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `invited_by` varchar(40) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_member_farm_user` (`farm_id`,`user_id`),
  UNIQUE KEY `uniq_member_invite_token` (`invite_token`),
  KEY `idx_member_user` (`user_id`),
  KEY `idx_member_farm` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farm_members`
--

INSERT INTO `farm_members` (`id`, `farm_id`, `user_id`, `role`, `permissions`, `invite_email`, `invite_token`, `invite_expires_at`, `status`, `invited_by`, `created_at`) VALUES
('01M25MGYXPZ3GJR6PBW823T815', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25MGE6GRWSYBM4G8V9FMJP0', 'owner', '{}', NULL, NULL, NULL, 'active', NULL, '2026-09-10 12:26:18'),
('01M25PS356CV7RA8JDAKV0JW4Q', 'cmpjsq4b20001qxws79jyscoy', '01M25PS2SC6RETVVB12PY97YMX', 'owner', '{}', NULL, NULL, NULL, 'active', NULL, '2026-09-10 13:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `fields`
--

DROP TABLE IF EXISTS `fields`;
CREATE TABLE IF NOT EXISTS `fields` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `total_area` decimal(12,3) NOT NULL DEFAULT 0.000,
  `cultivatable_area` decimal(12,3) NOT NULL DEFAULT 0.000,
  `soil_type` varchar(80) NOT NULL DEFAULT '',
  `location_lat` double DEFAULT NULL,
  `location_lng` double DEFAULT NULL,
  `boundary_points` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`boundary_points`)),
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fields_farm` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fields`
--

INSERT INTO `fields` (`id`, `farm_id`, `name`, `total_area`, `cultivatable_area`, `soil_type`, `location_lat`, `location_lng`, `boundary_points`, `notes`, `created_at`, `updated_at`) VALUES
('01M25N7R637E9MPQ1AYST8DXTS', '01M25MGYXN4226KGXM4C0B6Y2A', 'Kanyani', 5.000, 4.500, 'Sandy loam', NULL, NULL, NULL, 'Near the river', '2026-09-10 12:38:45', '2026-09-10 12:38:45'),
('01M27RBA85E22N5X65K9HF72QV', '01M25MGYXN4226KGXM4C0B6Y2A', 'API Test Field', 3.000, 2.500, 'Clay', NULL, NULL, NULL, NULL, '2026-09-11 08:11:36', '2026-09-11 08:11:36'),
('33f6f6af-3569-47ff-be09-57e2ea20ed4d', 'cmpjsq4b20001qxws79jyscoy', 'KALEKENI', 2.500, 2.000, 'Loam', NULL, NULL, NULL, NULL, '2026-08-16 19:34:26', '2026-09-10 13:05:42'),
('cmpjtrgr00001qxbgdt3s58ro', 'cmpjsq4b20001qxws79jyscoy', 'Kalekeni', 8.559, 8.559, 'Mixed', NULL, NULL, NULL, 'Main field', '2026-05-24 13:40:16', '2026-09-10 13:05:42'),
('cmrrjierv0001qx1opr3d19o4', 'cmpjsq4b20001qxws79jyscoy', 'North Field', 222.683, 222.683, 'Loam', NULL, NULL, NULL, '', '2026-07-19 08:34:51', '2026-09-10 13:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `field_boundaries`
--

DROP TABLE IF EXISTS `field_boundaries`;
CREATE TABLE IF NOT EXISTS `field_boundaries` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `field_id` varchar(40) NOT NULL,
  `geo_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`geo_json`)),
  `area_ha` decimal(12,4) DEFAULT NULL,
  `centroid_lat` double DEFAULT NULL,
  `centroid_lng` double DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_boundary_field` (`field_id`),
  KEY `idx_boundary_farm` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `field_boundaries`
--

INSERT INTO `field_boundaries` (`id`, `farm_id`, `field_id`, `geo_json`, `area_ha`, `centroid_lat`, `centroid_lng`, `created_at`, `updated_at`) VALUES
('01M25YDCYEBH506JV4QK15RWMY', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25N7R637E9MPQ1AYST8DXTS', '{\"type\":\"Polygon\",\"coordinates\":[[[33.6,-13.8],[33.61,-13.8],[33.61,-13.81],[33.6,-13.81],[33.6,-13.8]]]}', 120.3412, -13.804, 33.604, '2026-09-10 15:19:07', '2026-09-10 15:19:07'),
('cmptx8gck0001qxt0b2y3ui2r', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtrgr00001qxbgdt3s58ro', '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[33.109025,-13.764239],[33.108167,-13.764885],[33.10911,-13.765866],[33.1097,-13.766762],[33.110419,-13.767836],[33.11118,-13.768639],[33.112349,-13.76939],[33.113787,-13.770454],[33.114216,-13.76988],[33.113755,-13.769672],[33.113283,-13.769515],[33.112694,-13.769005],[33.111943,-13.768129],[33.111385,-13.767357],[33.110827,-13.766555],[33.110098,-13.765647],[33.110397,-13.765221],[33.109025,-13.764239]]]},\"properties\":{}}', 8.5589, -13.767405055556, 33.111186666667, '2026-09-10 13:05:42', '2026-09-10 13:05:42'),
('cmrrjjktk0003qx1ofjoukpfi', 'cmpjsq4b20001qxws79jyscoy', 'cmrrjierv0001qx1opr3d19o4', '{\"type\":\"Polygon\",\"coordinates\":[[[33.769606,-13.956939],[33.762184,-13.964439],[33.757509,-13.971273],[33.757122,-13.973523],[33.761455,-13.973356],[33.76656,-13.972523],[33.77115,-13.971773],[33.77411,-13.972064],[33.775225,-13.970231],[33.775397,-13.967898],[33.775611,-13.965064],[33.774925,-13.962939],[33.773252,-13.960439],[33.771622,-13.957605],[33.769606,-13.956939],[33.769606,-13.956939]]]}', 222.6827, -13.9658715, 33.76905875, '2026-09-10 13:05:42', '2026-09-10 13:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `field_zones`
--

DROP TABLE IF EXISTS `field_zones`;
CREATE TABLE IF NOT EXISTS `field_zones` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `boundary_id` varchar(40) NOT NULL,
  `field_id` varchar(40) NOT NULL,
  `crop_field_id` varchar(40) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'management',
  `geo_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`geo_json`)),
  `area_ha` decimal(12,4) DEFAULT NULL,
  `colour` varchar(20) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_zone_boundary` (`boundary_id`),
  KEY `idx_zone_field` (`field_id`),
  KEY `idx_zone_farm` (`farm_id`),
  KEY `fk_zone_crop_field` (`crop_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `harvest_yields`
--

DROP TABLE IF EXISTS `harvest_yields`;
CREATE TABLE IF NOT EXISTS `harvest_yields` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `crop_field_id` varchar(40) NOT NULL,
  `harvest_date` date NOT NULL,
  `quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit` varchar(20) NOT NULL DEFAULT 'kg',
  `unit_weight` decimal(10,3) DEFAULT NULL,
  `quantity_kg` decimal(14,3) NOT NULL DEFAULT 0.000,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hy_farm` (`farm_id`),
  KEY `idx_hy_crop_field` (`crop_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `harvest_yields`
--

INSERT INTO `harvest_yields` (`id`, `farm_id`, `crop_field_id`, `harvest_date`, `quantity`, `unit`, `unit_weight`, `quantity_kg`, `notes`, `created_at`, `updated_at`) VALUES
('01M25NP5YX57WHNC0MK3BZ0FPY', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25N7RY7WFNC9Q67HWAXNTNS', '2026-04-25', 120.000, 'bags', 50.000, 6000.000, NULL, '2026-09-10 12:46:38', '2026-09-10 12:46:38'),
('cmpm3w89r000bqx1si2ooej0j', 'cmpjsq4b20001qxws79jyscoy', 'cmpjtzmot0005qxbgxwv0tpa0', '2026-05-02', 135.000, 'bags', 50.000, 6750.000, '', '2026-05-26 03:59:26', '2026-09-10 13:05:42'),
('cmptnkv7r0001qxyszoh3rflw', 'cmpjsq4b20001qxws79jyscoy', 'cmpju1phv0007qxbgl996xww5', '2026-05-02', 5.000, 'bags', 50.000, 250.000, '', '2026-05-31 10:44:52', '2026-09-10 13:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'other',
  `unit` varchar(20) NOT NULL DEFAULT 'kg',
  `quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `acquisition_unit_cost` decimal(12,2) DEFAULT NULL,
  `acquired_at` date DEFAULT NULL,
  `unit_weight` decimal(10,3) DEFAULT NULL,
  `season` varchar(60) DEFAULT NULL,
  `crop_field_id` varchar(40) DEFAULT NULL,
  `harvest_yield_id` varchar(40) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inv_farm` (`farm_id`,`category`),
  KEY `idx_inv_crop_field` (`crop_field_id`),
  KEY `fk_inv_yield` (`harvest_yield_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `farm_id`, `name`, `category`, `unit`, `quantity`, `acquisition_unit_cost`, `acquired_at`, `unit_weight`, `season`, `crop_field_id`, `harvest_yield_id`, `notes`, `created_at`, `updated_at`) VALUES
('01M25P52Y3T01BE9XDFHMF0FRE', '01M25MGYXN4226KGXM4C0B6Y2A', 'Maize grain', 'crop_harvest', 'bags', 80.000, 0.00, NULL, NULL, '2025/26 Rain Season', NULL, NULL, NULL, '2026-09-10 12:54:46', '2026-09-10 12:54:46'),
('cmpm3w8a6000dqx1smoqnqmgy', 'cmpjsq4b20001qxws79jyscoy', 'Maize — Kanyani, Fumba and ETG', 'crop_harvest', 'bags', 0.000, NULL, NULL, 50.000, '2025/26 Rain Season', 'cmpjtzmot0005qxbgxwv0tpa0', 'cmpm3w89r000bqx1si2ooej0j', 'Auto-added from harvest on 02/05/2026. Field: Kalekeni.', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmptnkv890003qxys5c0wre42', 'cmpjsq4b20001qxws79jyscoy', 'Sunflower — Local', 'crop_harvest', 'bags', 0.000, NULL, NULL, 50.000, '2025/26 Rain Season', 'cmpju1phv0007qxbgl996xww5', 'cmptnkv7r0001qxyszoh3rflw', 'Auto-added from harvest on 02/05/2026. Field: Kalekeni.', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmrpzpd2i0001qxrck81ye8wt', 'cmpjsq4b20001qxws79jyscoy', 'seed', 'seed', 'kg', 130.000, 15000.00, '2026-07-18', NULL, NULL, NULL, NULL, 'Consumed 20 kg by Land preparation and ridging on 2026-07-18.\nConsumed 50 kg by Planting on 2026-07-19.', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmrrkulon000oqx1olaev4wg6', 'cmpjsq4b20001qxws79jyscoy', 'Tractor', 'equipment', 'unit', 3.000, NULL, NULL, NULL, NULL, NULL, NULL, '', '2026-09-10 13:05:43', '2026-09-10 13:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_sales`
--

DROP TABLE IF EXISTS `inventory_sales`;
CREATE TABLE IF NOT EXISTS `inventory_sales` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `inventory_item_id` varchar(40) NOT NULL,
  `transaction_id` varchar(40) DEFAULT NULL,
  `quantity_sold` decimal(14,3) NOT NULL DEFAULT 0.000,
  `unit` varchar(20) NOT NULL DEFAULT '',
  `price_per_unit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `buyer_name` varchar(160) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invs_farm` (`farm_id`),
  KEY `idx_invs_item` (`inventory_item_id`),
  KEY `fk_invs_tx` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_sales`
--

INSERT INTO `inventory_sales` (`id`, `farm_id`, `inventory_item_id`, `transaction_id`, `quantity_sold`, `unit`, `price_per_unit`, `total_amount`, `buyer_name`, `sale_date`, `notes`, `created_at`) VALUES
('01M25P53E84H3PXXXNHP2DKWF7', '01M25MGYXN4226KGXM4C0B6Y2A', '01M25P52Y3T01BE9XDFHMF0FRE', '01M25P53E7SYNQZTZ5V70Y2M3N', 40.000, 'bags', 65000.00, 2600000.00, 'ETG', '2026-05-01', NULL, '2026-09-10 12:54:46'),
('cmrpwfhu40003qx6g88tdf87b', 'cmpjsq4b20001qxws79jyscoy', 'cmpm3w8a6000dqx1smoqnqmgy', NULL, 120.000, 'bags', 65000.00, 7800000.00, 'local trader', '2026-07-18', '', '2026-09-10 13:05:43'),
('cmrpwiz6u0008qx6gqb22ut5w', 'cmpjsq4b20001qxws79jyscoy', 'cmptnkv890003qxys5c0wre42', NULL, 5.000, 'bags', 70000.00, 350000.00, 'local trader', '2026-07-18', '', '2026-09-10 13:05:43'),
('cmrpwk0zj000cqx6g97nsm0fu', 'cmpjsq4b20001qxws79jyscoy', 'cmpm3w8a6000dqx1smoqnqmgy', NULL, 15.000, 'bags', 70000.00, 1050000.00, 'local trader', '2026-07-18', '', '2026-09-10 13:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `livestock_types`
--

DROP TABLE IF EXISTS `livestock_types`;
CREATE TABLE IF NOT EXISTS `livestock_types` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT '',
  `icon` varchar(40) NOT NULL DEFAULT 'cow',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lt_farm_name` (`farm_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `livestock_types`
--

INSERT INTO `livestock_types` (`id`, `farm_id`, `name`, `category`, `icon`, `created_at`) VALUES
('01M25PFSCTWMPV5YSMJ42HVX25', '01M25MGYXN4226KGXM4C0B6Y2A', 'Cattle', 'Ruminant', 'cow', '2026-09-10 13:00:37'),
('55b9efe2-5a1a-4868-9ecf-95657eedabdc', 'cmpjsq4b20001qxws79jyscoy', 'Sheep', 'Sheep', 'Sheep', '2026-09-10 13:05:43'),
('993aa52d-e2ba-4c44-83dd-73cfe7f4f9f0', 'cmpjsq4b20001qxws79jyscoy', 'Chickens', 'Poultry', 'Chickens', '2026-09-10 13:05:43'),
('b2f51bbc-ee0d-42ee-979e-219f7d8e323e', 'cmpjsq4b20001qxws79jyscoy', 'Cattle', 'Cattle', 'Cattle', '2026-09-10 13:05:43'),
('cmptxqxdv0005qxt0ek5pscje', 'cmpjsq4b20001qxws79jyscoy', 'Fish', 'Aquaculture', 'ðŸŸ', '2026-09-10 13:05:43'),
('ed18d4b7-05e2-4b34-8ed8-bf36c879a484', 'cmpjsq4b20001qxws79jyscoy', 'Pigs', 'Pigs', 'Pigs', '2026-09-10 13:05:43'),
('ee6dbdee-6ec9-48fa-b3bc-d188c77bf114', 'cmpjsq4b20001qxws79jyscoy', 'Goats', 'Goats', 'Goats', '2026-09-10 13:05:43'),
('fc066dcc-d51b-437f-867b-bf6f68baa5cc', 'cmpjsq4b20001qxws79jyscoy', 'Ducks', 'Poultry', 'Ducks', '2026-09-10 13:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `mail_queue`
--

DROP TABLE IF EXISTS `mail_queue`;
CREATE TABLE IF NOT EXISTS `mail_queue` (
  `id` varchar(40) NOT NULL,
  `to_email` varchar(190) NOT NULL,
  `to_name` varchar(120) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `html_body` mediumtext NOT NULL,
  `text_body` mediumtext NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `last_error` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mail_queue_status` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mail_queue`
--

INSERT INTO `mail_queue` (`id`, `to_email`, `to_name`, `subject`, `html_body`, `text_body`, `status`, `attempts`, `last_error`, `created_at`, `sent_at`) VALUES
('01M25MGE9Z4X83HC4QCNP40YTK', 'owner@example.com', 'Emmanuel Mwinama', 'Activate your AgriVault account', '<!doctype html>\n<html lang=\"en\">\n<head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width\"></head>\n<body style=\"margin:0;background:#F1F5F9;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1E293B\">\n    <div style=\"max-width:520px;margin:0 auto;padding:28px 16px\">\n        <div style=\"font-weight:800;font-size:18px;color:#0F172A;margin-bottom:18px\">AgriVault</div>\n        <div style=\"background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:26px\">\n            <h1 style=\"font-size:18px;margin:0 0 12px;color:#0F172A\">Activate your account</h1>\n<p style=\"margin:0 0 16px;font-size:15px;line-height:1.6\">\n    Hi Emmanuel Mwinama, thanks for signing up. Confirm your email address to activate\n    your AgriVault account:\n</p>\n<p style=\"margin:0 0 20px\">\n    <a href=\"http://localhost:8750/activate?token=SSV0bfkslYnVD9RsPxhnwTgiR-o4XR0lurwvqKT9D4k\"\n       style=\"display:inline-block;background:#0284C7;color:#fff;text-decoration:none;font-weight:700;padding:12px 20px;border-radius:8px\">\n       Activate my account\n    </a>\n</p>\n<p style=\"margin:0;font-size:13px;color:#64748B;line-height:1.6\">\n    Or paste this link into your browser:<br>\n    <span style=\"word-break:break-all\">http://localhost:8750/activate?token=SSV0bfkslYnVD9RsPxhnwTgiR-o4XR0lurwvqKT9D4k</span>\n</p>\n<p style=\"margin:16px 0 0;font-size:13px;color:#64748B\">This link expires in 24 hours.</p>\n        </div>\n        <p style=\"color:#94A3B8;font-size:12px;margin-top:18px\">\n            You’re receiving this because someone used this address on AgriVault.\n            If it wasn’t you, you can safely ignore this email.\n        </p>\n    </div>\n</body>\n</html>\n', 'AgriVault\n\n Activate your account\n\n Hi Emmanuel Mwinama, thanks for signing up. Confirm your email address to activate\n your AgriVault account:\n\n Activate my account\n\n Or paste this link into your browser:\n\n http://localhost:8750/activate?token=SSV0bfkslYnVD9RsPxhnwTgiR-o4XR0lurwvqKT9D4k\n\nThis link expires in 24 hours.\n\n You’re receiving this because someone used this address on AgriVault.\n If it wasn’t you, you can safely ignore this email.', 'sent', 0, NULL, '2026-09-10 12:26:01', '2026-09-10 12:26:01'),
('01M27QVJNAHPBCBBV847KRFMTP', 'emmmdev@gmail.com', 'Emmanuel', 'Activate your AgriVault account', '<!doctype html>\n<html lang=\"en\">\n<head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width\"></head>\n<body style=\"margin:0;background:#F1F5F9;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1E293B\">\n    <div style=\"max-width:520px;margin:0 auto;padding:28px 16px\">\n        <div style=\"font-weight:800;font-size:18px;color:#0F172A;margin-bottom:18px\">AgriVault</div>\n        <div style=\"background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:26px\">\n            <h1 style=\"font-size:18px;margin:0 0 12px;color:#0F172A\">Activate your account</h1>\n<p style=\"margin:0 0 16px;font-size:15px;line-height:1.6\">\n    Hi Emmanuel, thanks for signing up. Confirm your email address to activate\n    your AgriVault account:\n</p>\n<p style=\"margin:0 0 20px\">\n    <a href=\"http://localhost:8750/activate?token=PqfynDYfX3KFuEmI2GCNQdAXBa-q-GLv-WwstLH21Sg\"\n       style=\"display:inline-block;background:#0284C7;color:#fff;text-decoration:none;font-weight:700;padding:12px 20px;border-radius:8px\">\n       Activate my account\n    </a>\n</p>\n<p style=\"margin:0;font-size:13px;color:#64748B;line-height:1.6\">\n    Or paste this link into your browser:<br>\n    <span style=\"word-break:break-all\">http://localhost:8750/activate?token=PqfynDYfX3KFuEmI2GCNQdAXBa-q-GLv-WwstLH21Sg</span>\n</p>\n<p style=\"margin:16px 0 0;font-size:13px;color:#64748B\">This link expires in 24 hours.</p>\n        </div>\n        <p style=\"color:#94A3B8;font-size:12px;margin-top:18px\">\n            You’re receiving this because someone used this address on AgriVault.\n            If it wasn’t you, you can safely ignore this email.\n        </p>\n    </div>\n</body>\n</html>\n', 'AgriVault\n\n Activate your account\n\n Hi Emmanuel, thanks for signing up. Confirm your email address to activate\n your AgriVault account:\n\n Activate my account\n\n Or paste this link into your browser:\n\n http://localhost:8750/activate?token=PqfynDYfX3KFuEmI2GCNQdAXBa-q-GLv-WwstLH21Sg\n\nThis link expires in 24 hours.\n\n You’re receiving this because someone used this address on AgriVault.\n If it wasn’t you, you can safely ignore this email.', 'sent', 0, NULL, '2026-09-11 08:03:00', '2026-09-11 08:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `market_prices`
--

DROP TABLE IF EXISTS `market_prices`;
CREATE TABLE IF NOT EXISTS `market_prices` (
  `id` varchar(40) NOT NULL,
  `crop_name` varchar(120) NOT NULL,
  `variety` varchar(120) DEFAULT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'kg',
  `price_min` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_max` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_avg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `market` varchar(120) NOT NULL DEFAULT '',
  `region` varchar(120) NOT NULL DEFAULT '',
  `currency` varchar(3) NOT NULL DEFAULT 'MWK',
  `season` varchar(60) DEFAULT NULL,
  `recorded_at` datetime NOT NULL,
  `source` varchar(60) NOT NULL DEFAULT 'ADMARC',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_mp_crop` (`crop_name`,`is_active`),
  KEY `idx_mp_recorded` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `market_prices`
--

INSERT INTO `market_prices` (`id`, `crop_name`, `variety`, `unit`, `price_min`, `price_max`, `price_avg`, `market`, `region`, `currency`, `season`, `recorded_at`, `source`, `is_active`) VALUES
('mp_beans_mzu', 'Beans', NULL, 'kg', 900.00, 1200.00, 1050.00, 'Mzuzu Market', 'Northern', 'MWK', NULL, '2026-09-11 08:24:32', 'ADMARC', 1),
('mp_groundnut_lil', 'Groundnut', NULL, 'kg', 1100.00, 1400.00, 1250.00, 'Lilongwe ADMARC', 'Central', 'MWK', NULL, '2026-09-11 08:24:32', 'ADMARC', 1),
('mp_maize_lil', 'Maize', NULL, 'kg', 550.00, 700.00, 620.00, 'Lilongwe ADMARC', 'Central', 'MWK', NULL, '2026-09-11 08:24:32', 'ADMARC', 1),
('mp_maize_mch', 'Maize', NULL, 'kg', 500.00, 650.00, 580.00, 'Mchinji Market', 'Central', 'MWK', NULL, '2026-09-11 08:24:32', 'ADMARC', 1),
('mp_rice_kar', 'Rice', NULL, 'kg', 700.00, 900.00, 800.00, 'Karonga Market', 'Northern', 'MWK', NULL, '2026-09-11 08:24:32', 'ADMARC', 1),
('mp_soybean_lil', 'Soybean', NULL, 'kg', 950.00, 1150.00, 1050.00, 'Lilongwe ADMARC', 'Central', 'MWK', NULL, '2026-09-11 08:24:32', 'ADMARC', 1),
('mp_sunflower_lil', 'Sunflower', NULL, 'kg', 750.00, 950.00, 850.00, 'Lilongwe ADMARC', 'Central', 'MWK', NULL, '2026-09-11 08:24:32', 'ADMARC', 1),
('mp_tobacco_auc', 'Tobacco', NULL, 'kg', 1800.00, 2600.00, 2200.00, 'Auction Floors', 'National', 'MWK', NULL, '2026-09-11 08:24:32', 'TCC', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` varchar(40) NOT NULL,
  `user_id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `type` varchar(40) NOT NULL,
  `dedupe_key` varchar(160) NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` varchar(500) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notif_dedupe` (`user_id`,`dedupe_key`),
  KEY `idx_notif_user` (`user_id`,`is_read`,`created_at`),
  KEY `fk_notif_farm` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `farm_id`, `type`, `dedupe_key`, `title`, `message`, `is_read`, `link`, `created_at`) VALUES
('01M27Q2W6ZT57VR8R9EPD80KPC', '01M25MGE6GRWSYBM4G8V9FMJP0', '01M25MGYXN4226KGXM4C0B6Y2A', 'no_activity', 'no_activity:01M25N7RY7WFNC9Q67HWAXNTNS:2026-W37', 'No recent activity', 'No activity logged for Maize on Kanyani in the last 3 weeks.', 1, '/crops/01M25N7RY7WFNC9Q67HWAXNTNS', '2026-09-11 07:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `overhead_expenses`
--

DROP TABLE IF EXISTS `overhead_expenses`;
CREATE TABLE IF NOT EXISTS `overhead_expenses` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `description` varchar(200) NOT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'Other',
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `recurring` tinyint(1) NOT NULL DEFAULT 0,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oh_farm_date` (`farm_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `overhead_expenses`
--

INSERT INTO `overhead_expenses` (`id`, `farm_id`, `description`, `category`, `amount`, `date`, `recurring`, `notes`, `created_at`, `updated_at`) VALUES
('01M25P52J7ET315EYGJV3J432N', '01M25MGYXN4226KGXM4C0B6Y2A', 'Farm manager salary', 'Salary', 180000.00, '2026-01-31', 1, NULL, '2026-09-10 12:54:46', '2026-09-10 12:54:46'),
('cmpk1mbuv002pqxkklqs0cd5u', 'cmpjsq4b20001qxws79jyscoy', 'Blessings Chibwe Salary', 'Employee Salary', 90000.00, '2025-11-24', 1, 'Salary', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmpk1ofmu002rqxkkow2uhe1e', 'cmpjsq4b20001qxws79jyscoy', 'Blessings Chibwe Salary', 'Employee Salary', 90000.00, '2025-12-24', 1, 'Salary', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmpk1p82h002tqxkklg8w7477', 'cmpjsq4b20001qxws79jyscoy', 'Blessings Chibwe Salary', 'Employee Salary', 90000.00, '2026-01-24', 1, 'Salary', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmpk1pxho002vqxkkjnzs8ofq', 'cmpjsq4b20001qxws79jyscoy', 'Blessings Chibwe Salary', 'Employee Salary', 90000.00, '2026-02-24', 1, 'Salary', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmpk1qo4d002xqxkko2w2y6ze', 'cmpjsq4b20001qxws79jyscoy', 'Blessings Chibwe Salary', 'Employee Salary', 90000.00, '2026-03-24', 1, 'Salary', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmpk1rnpf002zqxkkr80fbm8k', 'cmpjsq4b20001qxws79jyscoy', 'Blessings Chibwe Salary', 'Employee Salary', 90000.00, '2026-04-24', 1, 'Blessings Chibwe Salary', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmpm3u02b0009qx1shyek36fh', 'cmpjsq4b20001qxws79jyscoy', 'Blessings Chibwe Salary', 'Other overhead', 90000.00, '2026-05-26', 1, '', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmqjkwoyw0001qxw0bng63gt9', 'cmpjsq4b20001qxws79jyscoy', 'Balance on 7 months salary', 'Other overhead', 311996.00, '2026-04-25', 0, '', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmrrkv2bt000qqx1oiwllxe9d', 'cmpjsq4b20001qxws79jyscoy', 'Fuel for tractors', 'Fuel', 400000.00, '2026-07-19', 0, '', '2026-09-10 13:05:43', '2026-09-10 13:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` varchar(40) NOT NULL,
  `subscription_id` varchar(40) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'MWK',
  `status` varchar(20) NOT NULL,
  `method` varchar(30) NOT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `provider_transaction_id` varchar(120) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by_admin_id` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_payments_provider_txn` (`provider_transaction_id`),
  KEY `idx_payments_subscription` (`subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `subscription_id`, `amount`, `currency`, `status`, `method`, `reference`, `provider_transaction_id`, `notes`, `paid_at`, `created_at`, `created_by_admin_id`) VALUES
('01M27QNB7DP0J646FHQM3K0Y36', '01M25MGE7E0WJWAP6NJJT4HHFX', 9500.00, 'MWK', 'paid', 'mobile_money', 'TXN123', NULL, NULL, NULL, '2026-09-11 07:59:36', '01M27QMCYETHMXFQTJZ1SR5CTR');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `bucket` varchar(64) NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`bucket`),
  KEY `idx_rate_limits_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_reports`
--

DROP TABLE IF EXISTS `saved_reports`;
CREATE TABLE IF NOT EXISTS `saved_reports` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `user_id` varchar(40) NOT NULL,
  `name` varchar(120) NOT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sr_farm` (`farm_id`),
  KEY `fk_sr_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(64) NOT NULL,
  `user_id` varchar(40) DEFAULT NULL,
  `admin_id` varchar(40) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `payload` mediumtext NOT NULL,
  `last_activity` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_last_activity` (`last_activity`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `admin_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('06342b55d7ddc046e5cfeffb0ecebc29c63effa17f8f0bda', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Electron/44.2.0 Safari/537.36 MSIX', '_created_at|i:1789135214;_last_seen|i:1789135214;_fp|s:64:\"efb384b30fb23c336e61fca9fa3e70d52b2c55d69f45f1329fc83ed9ab7e8016\";_rotated_at|i:1789135214;_flash_now|a:0:{}_flash|a:0:{}', 1789135214),
('06f6ba95cc7adb9fd5a06c7bc9623e037b492a0fa5655e0f', '01M25MGE6GRWSYBM4G8V9FMJP0', NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789112441;_last_seen|i:1789113698;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113698;_flash_now|a:1:{s:8:\"messages\";a:1:{i:0;a:2:{s:5:\"level\";s:7:\"success\";s:4:\"text\";s:20:\"Farm settings saved.\";}}}_flash|a:0:{}_csrf|s:64:\"642c129623712cca77cf9982fd3419a8b04125ffe3ebc728cd115fc10e718642\";auth|a:2:{s:7:\"user_id\";s:26:\"01M25MGE6GRWSYBM4G8V9FMJP0\";s:8:\"login_at\";i:1789112442;}farm_id|s:26:\"01M25MGYXN4226KGXM4C0B6Y2A\";', 1789113698),
('071a53590f37ae961f45dede47ddb1df39c78a0b3ef806e3', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113019;_last_seen|i:1789113019;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113019;_flash_now|a:0:{}_flash|a:0:{}', 1789113019),
('086d91cc35c174dc995c1a03807f94e3ac611bbc54ed45db', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789120660;_last_seen|i:1789120660;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789120660;_flash_now|a:0:{}_flash|a:0:{}', 1789120660),
('0b2ca35165036c99a040eeabf256e8098ca0999b79648999', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789135292;_last_seen|i:1789135292;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789135292;_flash_now|a:0:{}_flash|a:0:{}', 1789135292),
('0c63a380f767256b496da8745c3e41166d538205f1fd0bf3', '01M25MGE6GRWSYBM4G8V9FMJP0', NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113710;_last_seen|i:1789113734;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113710;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"26d1f80b36f0823def7af5ecc1fd37ac03c33374392273b47857c1c94c1fbdbf\";auth|a:2:{s:7:\"user_id\";s:26:\"01M25MGE6GRWSYBM4G8V9FMJP0\";s:8:\"login_at\";i:1789113710;}farm_id|s:26:\"01M25MGYXN4226KGXM4C0B6Y2A\";', 1789113734),
('0de00510449da25f3d253e7af6b644fe2a273ba8a523f8f4', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789117128;_last_seen|i:1789117128;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789117128;_flash_now|a:0:{}_flash|a:0:{}', 1789117128),
('0e7e573909606909415d827d5069cd66743a1fb9b6355006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789127802;_last_seen|i:1789127802;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789127802;_flash_now|a:0:{}_flash|a:0:{}', 1789127802),
('0f7f587d67fdf4980c5890d5918136f09fdf7a15013f650e', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136520;_last_seen|i:1789136520;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136520;_flash_now|a:0:{}_flash|a:0:{}', 1789136520),
('108b0d20ff00a4ba3bbe4ac8b1d5a76bad2eaa114edf67dc', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789137100;_last_seen|i:1789137100;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789137100;_flash_now|a:0:{}_flash|a:0:{}', 1789137100),
('112fc4f4c46b019c1e5c4cb9a82fd1cf38dd02b6258b6cba', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113947;_last_seen|i:1789113947;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113947;_flash_now|a:0:{}_flash|a:0:{}', 1789113947),
('1152192e31ff83d73bc5b11106d8f8190b6d3c0670a772ce', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789117090;_last_seen|i:1789117090;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789117090;_flash_now|a:0:{}_flash|a:0:{}', 1789117090),
('157170d9d646c3c708adff3f1a252127666182aeaa7e9f98', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116840;_last_seen|i:1789116840;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789116840;_flash_now|a:0:{}_flash|a:0:{}', 1789116840),
('15d4eba63facc11ae157768549326d015530d4696ae39241', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789120597;_last_seen|i:1789120597;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789120597;_flash_now|a:0:{}_flash|a:0:{}', 1789120597),
('1699e03fdfeade393d115a5cdd860a9a21d85348452ce2d8', '01M25MGE6GRWSYBM4G8V9FMJP0', NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114513;_last_seen|i:1789114517;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114514;_flash_now|a:1:{s:8:\"messages\";a:1:{i:0;a:2:{s:5:\"level\";s:5:\"error\";s:4:\"text\";s:19:\"Activity not found.\";}}}_flash|a:1:{s:8:\"messages\";a:1:{i:0;a:2:{s:5:\"level\";s:5:\"error\";s:4:\"text\";s:24:\"Crop planting not found.\";}}}_csrf|s:64:\"45e2b414869d4691e48597d6828600c0844c2d2b7613a975d0d9f8e298760f33\";auth|a:2:{s:7:\"user_id\";s:26:\"01M25MGE6GRWSYBM4G8V9FMJP0\";s:8:\"login_at\";i:1789114514;}farm_id|s:26:\"01M25MGYXN4226KGXM4C0B6Y2A\";', 1789114517),
('16b5d08665634607b5a0d7682573022b9e94b9ce20d36b2f', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136577;_last_seen|i:1789136577;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136577;_flash_now|a:0:{}_flash|a:0:{}', 1789136577),
('177c436f74a760f50051fdbd6e7d25d9a769fcd31f321453', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789120821;_last_seen|i:1789120821;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789120821;_flash_now|a:0:{}_flash|a:0:{}', 1789120821),
('1826caae58e058fd9ce9150f294200bdb189e064b8829277', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136972;_last_seen|i:1789136972;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136972;_flash_now|a:0:{}_flash|a:0:{}', 1789136972),
('1cbfb461adfa56eed42a08c2949d16032fd2b82f77d412c6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136572;_last_seen|i:1789136572;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136572;_flash_now|a:0:{}_flash|a:0:{}', 1789136572),
('1dd09d73e146d88b6facd2af4ab7d2090b6d583b6f916c52', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789117059;_last_seen|i:1789117059;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789117059;_flash_now|a:0:{}_flash|a:0:{}', 1789117059),
('1ffadc8a835c211838e6adaf700e836d2eb29a029dbc7d5f', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789135530;_last_seen|i:1789135530;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789135530;_flash_now|a:0:{}_flash|a:0:{}', 1789135530),
('21086f2f586edce079f9ccb050d86eb4ed8a68a0b9064842', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789135214;_last_seen|i:1789135214;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789135214;_flash_now|a:0:{}_flash|a:0:{}', 1789135214),
('23c02df4e69c56bbe53131580938fbe86ea1c4ba09fa1043', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789120474;_last_seen|i:1789120474;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789120474;_flash_now|a:0:{}_flash|a:0:{}', 1789120474),
('24b609c0da9b6ce4f70ecaf8512c20deb0ceaa60541d0926', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789127508;_last_seen|i:1789127508;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789127508;_flash_now|a:0:{}_flash|a:0:{}', 1789127508),
('29a2bf91c392f6f384707e93699eae3ff92806aa4b114204', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789130245;_last_seen|i:1789130245;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789130245;_flash_now|a:0:{}_flash|a:0:{}', 1789130245),
('2c7b28bfa706beed1602d01b234c2c4e6176217322326c19', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789134031;_last_seen|i:1789134031;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789134031;_flash_now|a:0:{}_flash|a:0:{}', 1789134031),
('2e8ca8dd064142ba109760c5e87adf0487431a7cff0e1ba4', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789120797;_last_seen|i:1789120797;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789120797;_flash_now|a:0:{}_flash|a:0:{}', 1789120797),
('33ecba48a88f73268368ef78b749a8d5c00f18574892b2ab', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116826;_last_seen|i:1789116826;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789116826;_flash_now|a:0:{}_flash|a:0:{}', 1789116826),
('35aa53e04be42b6b9f5f3359f1b16963ffbd81bbe000216d', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789131636;_last_seen|i:1789131636;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789131636;_flash_now|a:0:{}_flash|a:0:{}', 1789131636),
('35b4c6bd5bc15336612c909390a6416360b1a7203fcd290e', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116832;_last_seen|i:1789116832;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789116832;_flash_now|a:0:{}_flash|a:0:{}', 1789116832),
('35d50f721d55fd4cd7525d21cc985023225bd9d77834ed15', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789137110;_last_seen|i:1789137110;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789137110;_flash_now|a:0:{}_flash|a:0:{}', 1789137110),
('3728d96ba6e4fb55df1bcec8c58af8d8bc4144710d4a15a6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789127846;_last_seen|i:1789127846;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789127846;_flash_now|a:0:{}_flash|a:0:{}', 1789127846),
('37d14150946c86a4573630c0652782844cfb380b8cf45b99', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789117165;_last_seen|i:1789117165;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789117165;_flash_now|a:0:{}_flash|a:0:{}', 1789117165),
('38295be8548da4b5d2d9538242b1f89c4d8643fd404f4b72', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789132160;_last_seen|i:1789132160;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789132160;_flash_now|a:0:{}_flash|a:0:{}', 1789132160),
('39b78df80ea3180127e9e353df6ccae48c08aa9feb2e1fcd', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114311;_last_seen|i:1789114311;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114311;_flash_now|a:0:{}_flash|a:0:{}', 1789114311),
('39e83a270f440897519e895086062db5a3aa63549f81a342', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789134254;_last_seen|i:1789134254;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789134254;_flash_now|a:0:{}_flash|a:0:{}', 1789134254),
('3cc2ca4146d864f5afac667317aba0479a5d66ad55abf9f2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789120561;_last_seen|i:1789120561;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789120561;_flash_now|a:0:{}_flash|a:0:{}', 1789120561),
('3d41583ee0da5033367e9edd03533e939f49e8fe01cefd2c', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789137592;_last_seen|i:1789137592;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789137592;_flash_now|a:0:{}_flash|a:0:{}', 1789137592),
('415d83d1b2a066417c11b7605b3de7056189d811b1345416', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789137583;_last_seen|i:1789137583;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789137583;_flash_now|a:0:{}_flash|a:0:{}', 1789137583),
('41b18dc8e84b009eeb32fc5332c8df4c6c822d7bd0caebe2', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116860;_last_seen|i:1789116860;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789116860;_flash_now|a:0:{}_flash|a:0:{}', 1789116860),
('435b0ca6e2124c7fd726665f0d9da85dd88f1d3af2869b5f', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113948;_last_seen|i:1789113948;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113948;_flash_now|a:0:{}_flash|a:0:{}', 1789113948),
('4490a985b5b839c5fff3554b3f52bc7dfa54979f9e0a34cf', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789135519;_last_seen|i:1789135519;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789135519;_flash_now|a:0:{}_flash|a:0:{}', 1789135519),
('44de5fb9309badfff4b78407a08e3ca9d7b8eb174a8fb20f', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114283;_last_seen|i:1789114283;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114283;_flash_now|a:0:{}_flash|a:0:{}', 1789114283),
('467d65b057a8ac550099cdfa9af8c30df71a83dae473381d', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789117048;_last_seen|i:1789117048;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789117048;_flash_now|a:0:{}_flash|a:0:{}', 1789117048),
('4864d8f33fee565838e5a2fb4f8f489da72ce415d53f7403', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113552;_last_seen|i:1789113552;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113552;_flash_now|a:0:{}_flash|a:0:{}', 1789113552),
('498421ca58a5c327b03961575ca0bde6ca20b81dc1217c6e', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114298;_last_seen|i:1789114298;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114298;_flash_now|a:0:{}_flash|a:0:{}', 1789114298),
('49af5cac2bde7a8c85b1115b47752818b93a0db26b67b47f', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789115087;_last_seen|i:1789115087;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789115087;_flash_now|a:0:{}_flash|a:0:{}', 1789115087),
('4a31b72fba23565c1667471706649a3b6d7a4163a41a12c2', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789112415;_last_seen|i:1789112415;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789112415;_flash_now|a:0:{}_flash|a:0:{}', 1789112415),
('4b05a1e04d8751ea6d5b2b1e6598c1a505362d9c2d353560', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789131376;_last_seen|i:1789131376;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789131376;_flash_now|a:0:{}_flash|a:0:{}', 1789131376),
('4c552a07879c333a37e9b2c83448d1ec48d3e07170e3c730', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789115088;_last_seen|i:1789115088;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789115088;_flash_now|a:0:{}_flash|a:1:{s:8:\"messages\";a:1:{i:0;a:2:{s:5:\"level\";s:4:\"info\";s:4:\"text\";s:27:\"Please sign in to continue.\";}}}_intended|s:10:\"/dashboard\";', 1789115088),
('4d7021f805f72a04b240066151034001a223b4676dc8caf8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789137794;_last_seen|i:1789137794;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789137794;_flash_now|a:0:{}_flash|a:0:{}', 1789137794),
('4dd8cb4d5d55218176a12a820739921335266c672078de6b', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114297;_last_seen|i:1789114297;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114297;_flash_now|a:0:{}_flash|a:0:{}', 1789114297),
('507eea06715b873719d35e6255d7eaffafc77c7e2deac932', NULL, '01M27QMCYETHMXFQTJZ1SR5CTR', '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113560;_last_seen|i:1789113971;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113561;_flash_now|a:1:{s:8:\"messages\";a:1:{i:0;a:2:{s:5:\"level\";s:7:\"success\";s:4:\"text\";s:6:\"Saved.\";}}}_flash|a:0:{}_csrf|s:64:\"3191be20c68b8953702bc345679f106c39214e839381ad8e3bdc3b4e49467ab9\";admin|a:2:{s:8:\"admin_id\";s:26:\"01M27QMCYETHMXFQTJZ1SR5CTR\";s:8:\"login_at\";i:1789113561;}', 1789113971),
('51354b9655ffa7fb3beeaf2344f1deba34ac193db3898dbf', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114355;_last_seen|i:1789114355;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114355;_flash_now|a:0:{}_flash|a:0:{}', 1789114355),
('529d9d57d7dfdd682dc7bd71c76856aa98826b421605e199', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789125779;_last_seen|i:1789125779;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789125779;_flash_now|a:0:{}_flash|a:0:{}', 1789125779),
('53305ea02dcf0e80e4a6f103ace095bca71acd80889caada', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113962;_last_seen|i:1789113962;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113962;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"08fbf030c4e910ca9ced91c993359e11a9c36f066c083f50ea8dfdc5d02cc167\";', 1789113962),
('56636ca767ccbaff92c5a9d56c88e7dd8bed55346d939fe6', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114298;_last_seen|i:1789114298;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114298;_flash_now|a:0:{}_flash|a:0:{}', 1789114298),
('56cf6ff235d9242ce4104be1e9a25ca3e414dc265873582b', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789127501;_last_seen|i:1789127501;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789127501;_flash_now|a:0:{}_flash|a:0:{}', 1789127501),
('594fa4d2e9f97df29927bc3db7b6648943f640f01b731ac9', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789117071;_last_seen|i:1789117071;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789117071;_flash_now|a:0:{}_flash|a:0:{}', 1789117071),
('59a7b9b46e8d04923e8df0c0c3649b5bddcbda7d0e54d76c', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114544;_last_seen|i:1789114544;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114544;_flash_now|a:0:{}_flash|a:0:{}', 1789114545),
('5d0c2fedbeea7910da424079f0f8f8454fbada2651a4dd20', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113698;_last_seen|i:1789113698;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113698;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"df439baa61afcdd563b3d2f91623aa702fbba4845fc4accdbdc0a4cda09364e0\";', 1789113698),
('5f0d73163fcf1722882ae3d71663ff49ac8a76a8d2e78de0', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136510;_last_seen|i:1789136510;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136510;_flash_now|a:0:{}_flash|a:0:{}', 1789136510),
('5fef0e3a5a54cfa6660c224dda1a433135172247b4fd4c7c', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789128058;_last_seen|i:1789128058;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789128058;_flash_now|a:0:{}_flash|a:0:{}', 1789128058),
('64b19bce8e461c60032184ca75534f09bafa987df01b6c1f', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114284;_last_seen|i:1789114284;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114284;_flash_now|a:0:{}_flash|a:0:{}', 1789114284),
('658e94804166bf15f1ef0a3f21a4f9a2d80be295e8f4e6ee', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789117155;_last_seen|i:1789117155;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789117155;_flash_now|a:0:{}_flash|a:0:{}', 1789117155),
('65a9d3fd3b64b9b67da85aeccfeccc2276c552649e89c6a2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789134554;_last_seen|i:1789134554;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789134554;_flash_now|a:0:{}_flash|a:0:{}', 1789134554),
('65cefecf93b07a91997c552f9bd5271f050600f4f91a3c0a', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789120627;_last_seen|i:1789120627;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789120627;_flash_now|a:0:{}_flash|a:0:{}', 1789120627),
('66f891055030afe78c491271facdd94a1aa9bf8efc943500', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136540;_last_seen|i:1789136540;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136540;_flash_now|a:0:{}_flash|a:0:{}', 1789136540),
('6761eb8b0623a53ebe5f425fe03257fd3098b1448cf0ea2a', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114295;_last_seen|i:1789114295;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114295;_flash_now|a:0:{}_flash|a:0:{}', 1789114295),
('694d3f444bb5e99f9c9e9f414c78b946a643201ea7c15cdb', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136584;_last_seen|i:1789136584;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136584;_flash_now|a:0:{}_flash|a:0:{}', 1789136584),
('6afad1978e6558d5c11e8693f4fd8eef9e9378156cef7590', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789135525;_last_seen|i:1789135525;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789135525;_flash_now|a:0:{}_flash|a:0:{}', 1789135525),
('6bc621db13089da7f2ff3270128bb10536dd1ca6354ca596', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116760;_last_seen|i:1789116760;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789116760;_flash_now|a:0:{}_flash|a:0:{}', 1789116760),
('6ea6bafff41c627f905f9fc0689f9b5f05340252aca15733', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789137698;_last_seen|i:1789137698;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789137698;_flash_now|a:0:{}_flash|a:0:{}', 1789137698),
('6f1ae83968a3668182fec88647311b9144165e824f84941f', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789117084;_last_seen|i:1789117084;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789117084;_flash_now|a:0:{}_flash|a:0:{}', 1789117084),
('72b68dde5d1034affec5d68dd8f9cab81b540629b74c3774', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116812;_last_seen|i:1789116812;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789116812;_flash_now|a:0:{}_flash|a:0:{}', 1789116812),
('72e467230c5261fe567f87b7c662e08e2e96d23641cfc637', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789131906;_last_seen|i:1789131906;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789131906;_flash_now|a:0:{}_flash|a:0:{}', 1789131906),
('75070271d51d76f15e02c7284157ad8781e5f2f50e0bf44f', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114297;_last_seen|i:1789114297;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114297;_flash_now|a:0:{}_flash|a:0:{}', 1789114297),
('76ec39b11146deb03a2ca1b94dec3a0f0496d3c1189f7d1f', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136962;_last_seen|i:1789136962;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136962;_flash_now|a:0:{}_flash|a:0:{}', 1789136962),
('76f7e284c52e5b120a738d462ac5f95ae621c611554d7c14', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789132275;_last_seen|i:1789132275;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789132275;_flash_now|a:0:{}_flash|a:0:{}', 1789132275),
('78fa8de7cf2451ee04e0550a4d3e631bf0e8d13a49e9e53b', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.9168', '_created_at|i:1789120678;_last_seen|i:1789120678;_fp|s:64:\"c47c0c437f5a7163aa03888f51e10abfa3e7310f4b70e7e2225f419d0d72de3b\";_rotated_at|i:1789120678;_flash_now|a:0:{}_flash|a:0:{}_admin_intended|s:6:\"/admin\";_csrf|s:64:\"8f394928021d4579695876d9e0b6a50b9854436d590a22df961768706312c5fe\";', 1789120678),
('7df1aba8f4b52265aba1b8a89b0efe054ebc88dcc085567e', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.9168', '_created_at|i:1789127496;_last_seen|i:1789127496;_fp|s:64:\"c47c0c437f5a7163aa03888f51e10abfa3e7310f4b70e7e2225f419d0d72de3b\";_rotated_at|i:1789127496;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"827a7a6d30be0084c5234ec6f240c89870bac87f0e6f9dff5535d21c24b45d85\";', 1789127496),
('7f24cdcdd2769dff3a793214f3cb9dca0993c75902b48e11', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136777;_last_seen|i:1789136777;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136777;_flash_now|a:0:{}_flash|a:0:{}', 1789136777),
('817dd1c377b40c701cc477c93078cde512c4d98564ea8635', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114284;_last_seen|i:1789114284;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114284;_flash_now|a:0:{}_flash|a:0:{}', 1789114284),
('82e19736be3b6b23e44308404be7d61da724a4b25a65bd80', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.9168', '_created_at|i:1789120436;_last_seen|i:1789120436;_fp|s:64:\"c47c0c437f5a7163aa03888f51e10abfa3e7310f4b70e7e2225f419d0d72de3b\";_rotated_at|i:1789120436;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"8d8c2c91b63a06ff3a73423e79a320cbae87205140bc9b7666f5cc253e51834e\";', 1789120436),
('83cd181b7206f3e5692e85e584e2deeddb6e2c5f99fc8a71', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113949;_last_seen|i:1789113949;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113949;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"9b00f55567fd112376c7be0188e9979296a9d9dda4b57ae29b4324b6dd18b55f\";', 1789113949),
('86adeebc9f0f9e01cf8b5a6b71b23f55fedac309c0a4dc26', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789131828;_last_seen|i:1789131828;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789131828;_flash_now|a:0:{}_flash|a:0:{}', 1789131828),
('881d7e46618cfbb358e31816be041f475bb4393cbf6573a5', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114283;_last_seen|i:1789114283;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114283;_flash_now|a:0:{}_flash|a:0:{}', 1789114283),
('8c30ec89de2704585b54a5728bc4bdee31d5c820d785313d', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789120496;_last_seen|i:1789120496;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789120496;_flash_now|a:0:{}_flash|a:0:{}', 1789120496),
('8cbb6bcec6637cc9ecbd5fd9ca220f1b3336e70e31fbc0d7', '01M25MGE6GRWSYBM4G8V9FMJP0', '01M27QMCYETHMXFQTJZ1SR5CTR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116460;_last_seen|i:1789137786;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789137583;_flash_now|a:0:{}_flash|a:0:{}farm_id|s:26:\"01M25MGYXN4226KGXM4C0B6Y2A\";admin|a:2:{s:8:\"admin_id\";s:26:\"01M27QMCYETHMXFQTJZ1SR5CTR\";s:8:\"login_at\";i:1789116858;}_csrf|s:64:\"f7edbbdfaacb9357ef434dd35867bb6a6f5a54e2fb42ea861afa772920bc0b19\";auth|a:2:{s:7:\"user_id\";s:26:\"01M25MGE6GRWSYBM4G8V9FMJP0\";s:8:\"login_at\";i:1789127729;}', 1789137786),
('8cd75c17f25b8e55177fe6a4604c607efd57366820b4f292', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789125622;_last_seen|i:1789125622;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789125622;_flash_now|a:0:{}_flash|a:0:{}', 1789125622),
('902402a2de075c2d381d6b9765d646e120c1bd89ad960f22', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789134789;_last_seen|i:1789134789;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789134789;_flash_now|a:0:{}_flash|a:0:{}', 1789134789),
('911e178879b032ad364c07cc5803ad4a1cc945477f05582b', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789117104;_last_seen|i:1789117104;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789117104;_flash_now|a:0:{}_flash|a:0:{}', 1789117104),
('91a417ba3169b73d0a9fc8683909c0470ae913681e3a1815', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114545;_last_seen|i:1789114545;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114545;_flash_now|a:0:{}_flash|a:0:{}', 1789114545),
('94aace0ce70716109511078e33c1a2ee987f1235d5b875b0', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789131990;_last_seen|i:1789131990;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789131990;_flash_now|a:0:{}_flash|a:0:{}', 1789131990),
('96b8d80e612ea9f9cca2a4bb135cd6bdbddf396256d6eec4', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789133822;_last_seen|i:1789133822;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789133822;_flash_now|a:0:{}_flash|a:0:{}', 1789133822),
('98f8a8c73b6e25f58c61d5e0c2d48d89bc14bca04a16ab6a', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136515;_last_seen|i:1789136515;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136515;_flash_now|a:0:{}_flash|a:0:{}', 1789136515),
('9992607c5f251887b76862ba82ba670d0815109b748ba1a5', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789116455;_last_seen|i:1789116455;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789116455;_flash_now|a:0:{}_flash|a:0:{}', 1789116455),
('9f39f8a844a75b872c9e6df734fb24071dc41f6e3704fbad', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789135313;_last_seen|i:1789135313;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789135313;_flash_now|a:0:{}_flash|a:0:{}', 1789135313),
('a1cd7acd289b0dc2d910b4b286845d1560ee3d7c9bd8527e', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789134105;_last_seen|i:1789134105;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789134105;_flash_now|a:0:{}_flash|a:0:{}', 1789134105),
('a4fa12041164640b043c747f4fb90f1bef86379e789ed578', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789132051;_last_seen|i:1789132051;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789132051;_flash_now|a:0:{}_flash|a:0:{}', 1789132051),
('a5c7d4b50cd5dd9f710be74f1c232d7adbe939abdd13d827', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789137787;_last_seen|i:1789137787;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789137787;_flash_now|a:0:{}_flash|a:0:{}', 1789137787),
('a7ffbede219a8ee18e96daedb9faddd426387028652ede2a', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114346;_last_seen|i:1789114346;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114346;_flash_now|a:0:{}_flash|a:0:{}', 1789114346),
('a83efedc7fd297199f4b640d8549791d884f1de00981399d', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114370;_last_seen|i:1789114370;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114370;_flash_now|a:0:{}_flash|a:0:{}', 1789114370),
('a871076571786b045fee4669ea30b48565a941ba9e0d7271', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789134469;_last_seen|i:1789134469;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789134469;_flash_now|a:0:{}_flash|a:0:{}', 1789134469),
('a8ef9efdcf1f0e0fb6e47a03108dc03c86fc7468171fa8d2', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114296;_last_seen|i:1789114296;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114296;_flash_now|a:0:{}_flash|a:0:{}', 1789114296),
('a943d65e5da7e9e956158215b6df7b34152445a99e672721', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789127715;_last_seen|i:1789127715;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789127715;_flash_now|a:0:{}_flash|a:0:{}', 1789127715),
('a9bd65f69af7b2032804f94cf266844f86c91642df7f00e1', '01M25MGE6GRWSYBM4G8V9FMJP0', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '_created_at|i:1789112679;_last_seen|i:1789138914;_fp|s:64:\"ce4aef3086eb9ab481c7ccea7bd64cd4b73bf493c341cb1369495e16692eba91\";_rotated_at|i:1789138891;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"d9bb96a976fb1694210229d8c13d045a21d162ebbbb0a9f01f3abd544314cd19\";auth|a:2:{s:7:\"user_id\";s:26:\"01M25MGE6GRWSYBM4G8V9FMJP0\";s:8:\"login_at\";i:1789116296;}farm_id|s:26:\"01M25MGYXN4226KGXM4C0B6Y2A\";', 1789138914),
('b1e4f404d2c8ec6ca1bc2e6f4044ff4b256f1629908d2201', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113962;_last_seen|i:1789113964;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113962;_flash_now|a:0:{}_flash|a:1:{s:8:\"messages\";a:1:{i:0;a:2:{s:5:\"level\";s:7:\"success\";s:4:\"text\";s:36:\"Thanks — we’ll be in touch soon.\";}}}_csrf|s:64:\"8c7c58738d4036abee7ef2197b37e1667020342b1ef75a7f0387a80c99b3a140\";', 1789113964),
('b499ac6cca2ae8f2885839dd9d6a3e64b1951c31ff47c9e5', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.9168', '_created_at|i:1789120461;_last_seen|i:1789120461;_fp|s:64:\"c47c0c437f5a7163aa03888f51e10abfa3e7310f4b70e7e2225f419d0d72de3b\";_rotated_at|i:1789120461;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"b9eca72a75496c376afb7f48cc9dfe9da56c684533389c90572515f065dfe2da\";', 1789120461),
('b4dd1362fc5a1486446433a0dea8ebddcd63d059ca59ce6a', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789130172;_last_seen|i:1789130172;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789130172;_flash_now|a:0:{}_flash|a:0:{}', 1789130172),
('b4f12b1db77ae3d6016dd5db2b7d5e86510c82b2b6f4961e', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116784;_last_seen|i:1789116784;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789116784;_flash_now|a:0:{}_flash|a:0:{}', 1789116784),
('b635af60e2c917fc1f2c04603063e2c6c3619302d924fa12', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116797;_last_seen|i:1789116797;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789116797;_flash_now|a:0:{}_flash|a:0:{}', 1789116797),
('b7df2fe8209b51585326652ed63da11a9ac10784c0a8a0b9', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789115088;_last_seen|i:1789115088;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789115088;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"3e903575444de6d9a27cd82d699b5f07733c77f658bf101f5859523e191e2963\";', 1789115088),
('b7f46e2c8f375c19abd9b15c68d4bc02d349366e4dc082fa', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789127729;_last_seen|i:1789127729;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789127729;_flash_now|a:0:{}_flash|a:0:{}', 1789127729),
('bb3ebd11b89b8d6272a201584e009dd3ea3d44a0a748b85e', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789128574;_last_seen|i:1789128574;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789128574;_flash_now|a:0:{}_flash|a:0:{}', 1789128574),
('bf0575f94e5f8a3582c849dab8582be6fff6bce6cb783796', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114369;_last_seen|i:1789114369;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114369;_flash_now|a:0:{}_flash|a:0:{}', 1789114369),
('c3ff9491d9f59706247c2abf438b8d93519de3fd74323c0d', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789130400;_last_seen|i:1789130400;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789130400;_flash_now|a:0:{}_flash|a:0:{}', 1789130400),
('c42ed564db26c1fdedca042d44f4fe386fb6ce20763f49bc', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136526;_last_seen|i:1789136526;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136526;_flash_now|a:0:{}_flash|a:0:{}', 1789136526),
('c5adfa32ad2ca6c7d18bca1f23edfd4f24c6112e29aa6075', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789128269;_last_seen|i:1789128269;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789128269;_flash_now|a:0:{}_flash|a:0:{}', 1789128269),
('c71f5f292b00bd58bd7a1653811d13df8c1143086653e1d4', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113946;_last_seen|i:1789113946;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113946;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"12b46524c85a89179b7f40b627f836a2a6e3ab54962b07bf4262d8b0a7a494fa\";', 1789113947),
('c7b8d8b2fac1825d3770adcd24441f3720bc690ab15af512', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114370;_last_seen|i:1789114370;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114370;_flash_now|a:0:{}_flash|a:0:{}', 1789114370),
('cc8bf090a19849250a0d45f14797627f0df244b792270d8a', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113947;_last_seen|i:1789113947;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113947;_flash_now|a:0:{}_flash|a:0:{}', 1789113947),
('ccfb0fa720b6650ccb773bd7da8a60381012b8a74e5fab46', '01M25MGE6GRWSYBM4G8V9FMJP0', NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789116351;_last_seen|i:1789116352;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789116352;_flash_now|a:1:{s:8:\"messages\";a:1:{i:0;a:2:{s:5:\"level\";s:7:\"success\";s:4:\"text\";s:13:\"Welcome back.\";}}}_flash|a:0:{}_csrf|s:64:\"778d27b079a5b59609c9b56d3d6ee06e405f62e8a480c5af01f40acb29a16b8f\";auth|a:2:{s:7:\"user_id\";s:26:\"01M25MGE6GRWSYBM4G8V9FMJP0\";s:8:\"login_at\";i:1789116352;}farm_id|s:26:\"01M25MGYXN4226KGXM4C0B6Y2A\";', 1789116352),
('cd2b553fac8892e4420e4a29b1a1394f74efc86baf18b147', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136596;_last_seen|i:1789136596;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136596;_flash_now|a:0:{}_flash|a:0:{}', 1789136596);
INSERT INTO `sessions` (`id`, `user_id`, `admin_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('cf2c01cec46fa0bdf58981e6ee8c13d3bd637ce5aecb1ddd', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789127863;_last_seen|i:1789127863;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789127863;_flash_now|a:0:{}_flash|a:0:{}', 1789127863),
('d0e8cd61154f4ae25f424e011b91468973608b539546992d', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789132609;_last_seen|i:1789132609;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789132609;_flash_now|a:0:{}_flash|a:0:{}', 1789132609),
('d16d9731ee7483b453882c1b4976dab84ce235f07e07eefa', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114347;_last_seen|i:1789114347;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114347;_flash_now|a:0:{}_flash|a:0:{}', 1789114347),
('d283d66b34ab66015c3433ad4e8775d57a8222b9e68ed241', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789135319;_last_seen|i:1789135319;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789135319;_flash_now|a:0:{}_flash|a:0:{}', 1789135319),
('d34bf7ae4c9325a96db86f7fc8261366e1362e0f94d83f31', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789120615;_last_seen|i:1789120615;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789120615;_flash_now|a:0:{}_flash|a:0:{}', 1789120615),
('d4037e0f40b5800e27347e6c13a4d101f816252647bbfa0b', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789116461;_last_seen|i:1789116461;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789116461;_flash_now|a:0:{}_flash|a:0:{}', 1789116461),
('d4c62e28c7c411ea2a027bfb95d989caf8a15c0bc7aa4400', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114281;_last_seen|i:1789114281;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114281;_flash_now|a:0:{}_flash|a:0:{}', 1789114282),
('d72aa7dcde766c6410e780e20c76e1eab629fc371d50a9b1', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789116350;_last_seen|i:1789116350;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789116350;_flash_now|a:0:{}_flash|a:0:{}', 1789116350),
('d884b0684ca8bf279c2187ddaee2238d71d2f79708c0ba42', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '_created_at|i:1789112624;_last_seen|i:1789112659;_fp|s:64:\"ce4aef3086eb9ab481c7ccea7bd64cd4b73bf493c341cb1369495e16692eba91\";_rotated_at|i:1789112624;_flash_now|a:0:{}_flash|a:0:{}', 1789112659),
('d961c92aa0dbe0746f6f29356e540f293b1f6a0c83b420c0', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114312;_last_seen|i:1789114312;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114312;_flash_now|a:0:{}_flash|a:0:{}', 1789114312),
('dd179993e0874de626a60f985d687931eca8113194fecc5c', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789134546;_last_seen|i:1789134546;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789134546;_flash_now|a:0:{}_flash|a:0:{}', 1789134546),
('e0d5e299da5d89ca5b95d0a675b79c2856a50fd529a4eb5f', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789131746;_last_seen|i:1789131746;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789131746;_flash_now|a:0:{}_flash|a:0:{}', 1789131746),
('e25ec609522dbd04ff3f278670796da83e961fbc927ac264', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113948;_last_seen|i:1789113948;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113948;_flash_now|a:0:{}_flash|a:0:{}', 1789113948),
('e3a42006e56760a38aee395bf46a646ac4149353fe098549', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789131519;_last_seen|i:1789131519;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789131519;_flash_now|a:0:{}_flash|a:0:{}', 1789131519),
('e485af7639958ef79aa5632a1578008d93c84b0ca660d0dc', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114347;_last_seen|i:1789114347;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114347;_flash_now|a:0:{}_flash|a:0:{}', 1789114347),
('e4872ef020a0ad400c64f893f9563c3de2b98a9f95ac7392', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114310;_last_seen|i:1789114310;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114310;_flash_now|a:0:{}_flash|a:0:{}', 1789114310),
('e808f3f78132efd334a93d209ec77279857cf7c88233abc3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789132384;_last_seen|i:1789132384;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789132384;_flash_now|a:0:{}_flash|a:0:{}', 1789132384),
('eb6e3a872f321fdd340b5ff4e7f86e9edd6efd7a55a461df', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136954;_last_seen|i:1789136954;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136954;_flash_now|a:0:{}_flash|a:0:{}', 1789136954),
('ebb0ae5298375ebeb3ce4a59f0896a326b9b4236bd4539d2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136533;_last_seen|i:1789136533;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136533;_flash_now|a:0:{}_flash|a:0:{}', 1789136533),
('ed9bd956ab602bdb9be7c6bc610d510b1d70e8c1e3493dac', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789113948;_last_seen|i:1789113948;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789113948;_flash_now|a:0:{}_flash|a:0:{}', 1789113948),
('f09a2a6b2e3d6e6cdf89c00bfb0675d210003c45ba74af13', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.9168', '_created_at|i:1789130096;_last_seen|i:1789130096;_fp|s:64:\"c47c0c437f5a7163aa03888f51e10abfa3e7310f4b70e7e2225f419d0d72de3b\";_rotated_at|i:1789130096;_flash_now|a:0:{}_flash|a:0:{}_csrf|s:64:\"f5c2f99b8ac4c03503fc26bbd3ee6de5ecc61dd3d51910b4b93c9c4f27e06632\";', 1789130096),
('f5e1c1cd027de9c84f24c6a054a08778e5f03b240f7ebb34', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114270;_last_seen|i:1789114270;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114270;_flash_now|a:0:{}_flash|a:0:{}', 1789114270),
('f78f94d65547f5b2bf8ee7fda409cc5cf22e47a9831b80a8', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114311;_last_seen|i:1789114311;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114311;_flash_now|a:0:{}_flash|a:0:{}', 1789114311),
('f7c9f4e0fb45d9bb50ffc43c9c801e0b74711bf2a8a10393', NULL, NULL, '127.0.0.1', 'curl/8.21.0', '_created_at|i:1789114296;_last_seen|i:1789114296;_fp|s:64:\"81be21ab3ed215ab3416ba75b66bf08cb3347df22d86ec0709628647646559f6\";_rotated_at|i:1789114296;_flash_now|a:0:{}_flash|a:0:{}', 1789114296),
('f81db784711ad557435f643a710276a8821219e1af96d30d', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136545;_last_seen|i:1789136545;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136545;_flash_now|a:0:{}_flash|a:0:{}', 1789136545),
('f8b928e50b4d42220455519b1ec9227f92c2f9cfbcb43870', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789136566;_last_seen|i:1789136566;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789136566;_flash_now|a:0:{}_flash|a:0:{}', 1789136566),
('fce51b8e3563d1c9e7f05a252d6d1378424b3e5f24c9c1f7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789128500;_last_seen|i:1789128500;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789128500;_flash_now|a:0:{}_flash|a:0:{}', 1789128500),
('fe46e2efc220088182bb1649a2b70b782e9897f316c7a84b', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.52386.0 Chrome/152.0.7977.76 Safari/537.36 MSIX', '_created_at|i:1789130598;_last_seen|i:1789130598;_fp|s:64:\"392ab2957b6c280c50aa4f4cc905ee262432a55d1d6a8be51c907bb7fd2c514a\";_rotated_at|i:1789130598;_flash_now|a:0:{}_flash|a:0:{}', 1789130598);

-- --------------------------------------------------------

--
-- Table structure for table `site_content`
--

DROP TABLE IF EXISTS `site_content`;
CREATE TABLE IF NOT EXISTS `site_content` (
  `key` varchar(80) NOT NULL,
  `value` text NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `group` varchar(40) NOT NULL DEFAULT 'general',
  `label` varchar(160) DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_content`
--

INSERT INTO `site_content` (`key`, `value`, `type`, `group`, `label`, `updated_at`, `updated_by`) VALUES
('contact_email', 'support@agrivault.local', 'text', 'contact', 'Support email', '2026-09-11 07:59:05', NULL),
('hero_eyebrow', 'Farm records & evidence', 'text', 'hero', 'Hero eyebrow', '2026-09-11 07:59:05', NULL),
('hero_subtitle', 'Capture fields, crops, activities, labour, finance and livestock in one place — then hand a buyer, lender, auditor or insurer exactly the evidence they ask for.', 'text', 'hero', 'Hero subtitle', '2026-09-11 07:59:05', NULL),
('hero_title', 'The record vault your farm can prove.', 'text', 'hero', 'Hero title', '2026-09-11 07:59:53', '01M27QMCYETHMXFQTJZ1SR5CTR');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` varchar(40) NOT NULL,
  `user_id` varchar(40) NOT NULL,
  `tier_id` varchar(40) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `billing_cycle` varchar(20) NOT NULL DEFAULT 'monthly',
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `provider` varchar(20) DEFAULT NULL,
  `provider_subscription_id` varchar(120) DEFAULT NULL,
  `activation_token` varchar(80) DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_subscriptions_user` (`user_id`),
  UNIQUE KEY `uniq_subscriptions_provider` (`provider_subscription_id`),
  UNIQUE KEY `uniq_subscriptions_acttoken` (`activation_token`),
  KEY `idx_subscriptions_tier` (`tier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `tier_id`, `status`, `billing_cycle`, `start_date`, `end_date`, `trial_ends_at`, `provider`, `provider_subscription_id`, `activation_token`, `activated_at`, `notes`, `created_at`, `updated_at`) VALUES
('01M25MGE7E0WJWAP6NJJT4HHFX', '01M25MGE6GRWSYBM4G8V9FMJP0', 'tier_trial', 'trial', 'monthly', '2026-09-10 12:26:01', NULL, '2026-09-17 14:26:01', NULL, NULL, NULL, NULL, NULL, '2026-09-10 12:26:01', '2026-09-10 12:26:01'),
('01M25PS317A0B1DRBXXYMPT0SW', '01M25PS2SC6RETVVB12PY97YMX', 'tier_regular', 'active', 'monthly', '2026-09-10 13:05:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 13:05:41', '2026-09-10 13:05:41'),
('01M27QVJGYHE6ND8AXY4D630C7', '01M27QVJGWN3ZRVSBHYEBTT9WB', 'tier_regular', 'trial', 'monthly', '2026-09-11 08:03:00', NULL, '2026-09-18 10:03:00', NULL, NULL, NULL, NULL, NULL, '2026-09-11 08:03:00', '2026-09-11 08:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_tiers`
--

DROP TABLE IF EXISTS `subscription_tiers`;
CREATE TABLE IF NOT EXISTS `subscription_tiers` (
  `id` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `currency` varchar(3) NOT NULL DEFAULT 'MWK',
  `price_monthly` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_annual` decimal(12,2) DEFAULT NULL,
  `price_lifetime` decimal(12,2) DEFAULT NULL,
  `audience` varchar(120) DEFAULT NULL,
  `cta_label` varchar(80) DEFAULT NULL,
  `cta_href` varchar(255) DEFAULT NULL,
  `offer_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`offer_items`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `max_fields` int(11) NOT NULL DEFAULT 1,
  `max_crops` int(11) NOT NULL DEFAULT 1,
  `max_activities` int(11) NOT NULL DEFAULT 10,
  `max_transactions` int(11) NOT NULL DEFAULT 5,
  `max_employees` int(11) NOT NULL DEFAULT 1,
  `max_farms` int(11) NOT NULL DEFAULT 1,
  `max_team_members` int(11) NOT NULL DEFAULT 1,
  `season_analytics` tinyint(1) NOT NULL DEFAULT 0,
  `yield_suggestions` tinyint(1) NOT NULL DEFAULT 0,
  `cost_per_hectare` tinyint(1) NOT NULL DEFAULT 0,
  `payroll_tracking` tinyint(1) NOT NULL DEFAULT 0,
  `multiple_farms` tinyint(1) NOT NULL DEFAULT 0,
  `team_accounts` tinyint(1) NOT NULL DEFAULT 0,
  `custom_reports` tinyint(1) NOT NULL DEFAULT 0,
  `api_access` tinyint(1) NOT NULL DEFAULT 0,
  `data_retention_lifetime` tinyint(1) NOT NULL DEFAULT 1,
  `sync_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tiers_visible` (`is_active`,`is_public`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_tiers`
--

INSERT INTO `subscription_tiers` (`id`, `name`, `description`, `currency`, `price_monthly`, `price_annual`, `price_lifetime`, `audience`, `cta_label`, `cta_href`, `offer_items`, `is_active`, `is_public`, `is_featured`, `sort_order`, `max_fields`, `max_crops`, `max_activities`, `max_transactions`, `max_employees`, `max_farms`, `max_team_members`, `season_analytics`, `yield_suggestions`, `cost_per_hectare`, `payroll_tracking`, `multiple_farms`, `team_accounts`, `custom_reports`, `api_access`, `data_retention_lifetime`, `sync_enabled`, `created_at`) VALUES
('tier_enterprise', 'Enterprise', 'Multiple farms and a full team.', 'MWK', 29000.00, 290000.00, NULL, 'Commercial operations', NULL, NULL, NULL, 1, 1, 0, 2, -1, -1, -1, -1, -1, 5, 15, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-09-10 12:12:26'),
('tier_large', 'Large Enterprise', 'Estates, cooperatives and aggregators.', 'MWK', 75000.00, 750000.00, NULL, 'Estates & cooperatives', NULL, NULL, NULL, 1, 0, 0, 3, -1, -1, -1, -1, -1, -1, -1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-09-10 12:12:26'),
('tier_regular', 'Regular', 'For a single working farm.', 'MWK', 9500.00, 95000.00, NULL, 'Smallholder & family farms', NULL, NULL, NULL, 1, 1, 1, 1, 10, 25, -1, -1, 15, 1, 4, 1, 1, 1, 1, 0, 1, 0, 0, 1, 1, '2026-09-10 12:12:26'),
('tier_trial', 'Trial', '7-day full access, then 14 days view-only.', 'MWK', 0.00, 0.00, NULL, 'New farms', NULL, NULL, NULL, 1, 1, 0, 0, 3, 3, 40, 30, 3, 1, 2, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, '2026-09-10 12:12:26');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` varchar(40) NOT NULL,
  `quote` text NOT NULL,
  `name` varchar(120) NOT NULL,
  `role` varchar(120) NOT NULL DEFAULT '',
  `initials` varchar(4) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `quote`, `name`, `role`, `initials`, `is_active`, `sort_order`, `created_at`) VALUES
('test_1', 'For the first time I can hand a bank officer a season\'s worth of records instead of a shoebox of receipts.', 'Grace Banda', 'Smallholder farmer, Mchinji', 'GB', 1, 1, '2026-09-11 07:59:05'),
('test_2', 'Our field team logs activities from their phones; I see the season\'s cost picture without chasing anyone.', 'Kondwani Phiri', 'Farm manager, Kasungu', 'KP', 1, 2, '2026-09-11 07:59:05');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `type` varchar(10) NOT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'Other',
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `season` varchar(60) DEFAULT NULL,
  `field_id` varchar(40) DEFAULT NULL,
  `crop_field_id` varchar(40) DEFAULT NULL,
  `harvest_yield_id` varchar(40) DEFAULT NULL,
  `inventory_item_id` varchar(40) DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'manual',
  `created_by_id` varchar(40) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tx_farm_date` (`farm_id`,`date`),
  KEY `idx_tx_farm_type` (`farm_id`,`type`),
  KEY `idx_tx_season` (`farm_id`,`season`),
  KEY `idx_tx_crop_field` (`crop_field_id`),
  KEY `fk_tx_field` (`field_id`),
  KEY `fk_tx_yield` (`harvest_yield_id`),
  KEY `fk_tx_creator` (`created_by_id`),
  KEY `fk_tx_inventory` (`inventory_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `farm_id`, `type`, `category`, `amount`, `date`, `description`, `season`, `field_id`, `crop_field_id`, `harvest_yield_id`, `inventory_item_id`, `source`, `created_by_id`, `created_at`, `updated_at`) VALUES
('01M25P5264EWVGQ0XNW8VZ0W9Y', '01M25MGYXN4226KGXM4C0B6Y2A', 'Expense', 'Fertiliser', 250000.00, '2026-01-10', 'Urea 10 bags', '2025/26 Rain Season', NULL, NULL, NULL, NULL, 'manual', '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-10 12:54:45', '2026-09-10 12:54:45'),
('01M25P53E7SYNQZTZ5V70Y2M3N', '01M25MGYXN4226KGXM4C0B6Y2A', 'Income', 'Crop sales', 2600000.00, '2026-05-01', 'Sale: Maize grain — 4 bags to ETG', '2025/26 Rain Season', NULL, NULL, NULL, '01M25P52Y3T01BE9XDFHMF0FRE', 'inventory_sale', '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-10 12:54:46', '2026-09-10 12:54:46'),
('01M25PFV6GCKN7JY6SQSQEJ2YA', '01M25MGYXN4226KGXM4C0B6Y2A', 'Income', 'Livestock sales', 380000.00, '2026-06-15', 'Livestock sale: C-001 Cattle to Local butcher', NULL, NULL, NULL, NULL, NULL, 'inventory_sale', '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-10 13:00:38', '2026-09-10 13:00:38'),
('01M27RBB9VHGFX5XTJ4B04Y3FN', '01M25MGYXN4226KGXM4C0B6Y2A', 'Expense', 'Fuel', 15000.00, '2026-09-01', 'API test expense', NULL, NULL, NULL, NULL, NULL, 'manual', '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-11 08:11:37', '2026-09-11 08:11:37'),
('01M27RDJQH7K7YNHKRMA5G0DAF', '01M25MGYXN4226KGXM4C0B6Y2A', 'Income', 'Crop sales', 5000.00, '2026-09-10', 'Sync test sale', NULL, NULL, NULL, NULL, NULL, 'manual', '01M25MGE6GRWSYBM4G8V9FMJP0', '2026-09-11 08:12:50', '2026-09-11 08:12:50'),
('cmrpwfhty0001qx6gbo134v6c', 'cmpjsq4b20001qxws79jyscoy', 'Income', 'Crop sales', 7800000.00, '2026-07-18', 'Sale: Maize — Kanyani, Fumba and ETG (Maize) — 120 bags', '2025/26 Rain Season', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'cmpm3w89r000bqx1si2ooej0j', NULL, 'manual', '01M25PS2SC6RETVVB12PY97YMX', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmrpwiz6o0006qx6g4onirvmi', 'cmpjsq4b20001qxws79jyscoy', 'Income', 'Crop sales', 350000.00, '2026-07-18', 'Sale: Sunflower — Local (Sunflower) — 5 bags', '2025/26 Rain Season', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpju1phv0007qxbgl996xww5', 'cmptnkv7r0001qxyszoh3rflw', NULL, 'manual', '01M25PS2SC6RETVVB12PY97YMX', '2026-09-10 13:05:43', '2026-09-10 13:05:43'),
('cmrpwk0zf000aqx6gyg1k3iaf', 'cmpjsq4b20001qxws79jyscoy', 'Income', 'Crop sales', 1050000.00, '2026-07-18', 'Sale: Maize — Kanyani, Fumba and ETG (Maize) — 15 bags', '2025/26 Rain Season', 'cmpjtrgr00001qxbgdt3s58ro', 'cmpjtzmot0005qxbgxwv0tpa0', 'cmpm3w89r000bqx1si2ooej0j', NULL, 'manual', '01M25PS2SC6RETVVB12PY97YMX', '2026-09-10 13:05:43', '2026-09-10 13:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(40) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
('01M25MGE6GRWSYBM4G8V9FMJP0', 'Emmanuel Mwinama', 'owner@example.com', '$argon2id$v=19$m=65536,t=4,p=2$RjZHRUxxQnNqNkprMlFxMg$rZcWRQ48ATLNFTYFM2SHRt7svj0ZsAGFTc/uIoBfuOo', 1, '2026-09-11 11:55:29', '2026-09-10 12:26:01', '2026-09-10 12:26:17'),
('01M25PS2SC6RETVVB12PY97YMX', 'Emmanuel Mwinama', 'emmmwinama@gmail.com', '$argon2id$v=19$m=65536,t=4,p=2$YzlxblZnWm5ZWnY1VWZ6ZA$z5mj4rDoGFjVnzX5OWpD/uyzaHMS5+bXnwCqFvFQsDE', 1, NULL, '2026-09-10 13:05:41', '2026-09-10 13:05:41'),
('01M27QVJGWN3ZRVSBHYEBTT9WB', 'Emmanuel', 'emmmdev@gmail.com', '$argon2id$v=19$m=65536,t=4,p=2$amguNlk1Zi5kMkZjZkdiLg$IYMzDIhsPKdkCELyNoVcAHw8TKBayfU1Hvj504tQlAA', 0, NULL, '2026-09-11 08:03:00', '2026-09-11 08:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `weather_cache`
--

DROP TABLE IF EXISTS `weather_cache`;
CREATE TABLE IF NOT EXISTS `weather_cache` (
  `id` varchar(40) NOT NULL,
  `farm_id` varchar(40) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `cached_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_weather_farm` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `_migrations`
--

DROP TABLE IF EXISTS `_migrations`;
CREATE TABLE IF NOT EXISTS `_migrations` (
  `filename` varchar(191) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `_migrations`
--

INSERT INTO `_migrations` (`filename`, `applied_at`) VALUES
('001_core.sql', '2026-09-10 12:12:23'),
('002_identity.sql', '2026-09-10 12:12:26'),
('003_land.sql', '2026-09-10 12:37:54'),
('004_crops.sql', '2026-09-10 12:37:55'),
('005_activities.sql', '2026-09-10 12:46:07'),
('006_finance.sql', '2026-09-10 12:54:17'),
('007_inventory.sql', '2026-09-10 12:54:20'),
('008_livestock.sql', '2026-09-10 13:00:11'),
('009_gis.sql', '2026-09-10 13:04:43'),
('010_reporting.sql', '2026-09-11 07:36:34'),
('011_support.sql', '2026-09-11 07:49:05'),
('012_cms.sql', '2026-09-11 07:59:04'),
('013_mobile.sql', '2026-09-11 08:10:59'),
('014_ai.sql', '2026-09-11 13:50:44');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_inputs`
--
ALTER TABLE `activity_inputs`
  ADD CONSTRAINT `fk_ai_activity` FOREIGN KEY (`activity_id`) REFERENCES `farm_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ai_inventory` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `activity_labour`
--
ALTER TABLE `activity_labour`
  ADD CONSTRAINT `fk_al_activity` FOREIGN KEY (`activity_id`) REFERENCES `farm_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_al_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `activity_other_costs`
--
ALTER TABLE `activity_other_costs`
  ADD CONSTRAINT `fk_aoc_activity` FOREIGN KEY (`activity_id`) REFERENCES `farm_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_insights_cache`
--
ALTER TABLE `ai_insights_cache`
  ADD CONSTRAINT `fk_ai_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `animals`
--
ALTER TABLE `animals`
  ADD CONSTRAINT `fk_animals_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_animals_parent` FOREIGN KEY (`parent_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_animals_type` FOREIGN KEY (`livestock_type_id`) REFERENCES `livestock_types` (`id`);

--
-- Constraints for table `animal_expenses`
--
ALTER TABLE `animal_expenses`
  ADD CONSTRAINT `fk_ae_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ae_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `animal_health`
--
ALTER TABLE `animal_health`
  ADD CONSTRAINT `fk_ah_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `animal_production`
--
ALTER TABLE `animal_production`
  ADD CONSTRAINT `fk_ap_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ap_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `animal_sales`
--
ALTER TABLE `animal_sales`
  ADD CONSTRAINT `fk_as_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_as_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_as_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `animal_weight`
--
ALTER TABLE `animal_weight`
  ADD CONSTRAINT `fk_aw_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `api_refresh_tokens`
--
ALTER TABLE `api_refresh_tokens`
  ADD CONSTRAINT `fk_api_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `fk_auth_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `crop_fields`
--
ALTER TABLE `crop_fields`
  ADD CONSTRAINT `fk_cf_crop_type` FOREIGN KEY (`crop_type_id`) REFERENCES `crop_types` (`id`),
  ADD CONSTRAINT `fk_cf_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cf_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `crop_types`
--
ALTER TABLE `crop_types`
  ADD CONSTRAINT `fk_crop_types_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farms`
--
ALTER TABLE `farms`
  ADD CONSTRAINT `fk_farms_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `farm_activities`
--
ALTER TABLE `farm_activities`
  ADD CONSTRAINT `fk_fa_creator` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_fa_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fa_employee` FOREIGN KEY (`responsible_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fa_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fa_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farm_credit_scores`
--
ALTER TABLE `farm_credit_scores`
  ADD CONSTRAINT `fk_fcs_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fcs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farm_documents`
--
ALTER TABLE `farm_documents`
  ADD CONSTRAINT `fk_doc_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_doc_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `farm_markers`
--
ALTER TABLE `farm_markers`
  ADD CONSTRAINT `fk_marker_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_marker_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `farm_members`
--
ALTER TABLE `farm_members`
  ADD CONSTRAINT `fk_member_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fields`
--
ALTER TABLE `fields`
  ADD CONSTRAINT `fk_fields_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `field_boundaries`
--
ALTER TABLE `field_boundaries`
  ADD CONSTRAINT `fk_boundary_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_boundary_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `field_zones`
--
ALTER TABLE `field_zones`
  ADD CONSTRAINT `fk_zone_boundary` FOREIGN KEY (`boundary_id`) REFERENCES `field_boundaries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_zone_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_zone_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `harvest_yields`
--
ALTER TABLE `harvest_yields`
  ADD CONSTRAINT `fk_hy_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hy_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `fk_inv_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inv_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_yield` FOREIGN KEY (`harvest_yield_id`) REFERENCES `harvest_yields` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_sales`
--
ALTER TABLE `inventory_sales`
  ADD CONSTRAINT `fk_invs_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invs_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invs_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `livestock_types`
--
ALTER TABLE `livestock_types`
  ADD CONSTRAINT `fk_lt_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `overhead_expenses`
--
ALTER TABLE `overhead_expenses`
  ADD CONSTRAINT `fk_oh_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Constraints for table `saved_reports`
--
ALTER TABLE `saved_reports`
  ADD CONSTRAINT `fk_sr_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `fk_subscriptions_tier` FOREIGN KEY (`tier_id`) REFERENCES `subscription_tiers` (`id`),
  ADD CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_creator` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_tx_crop_field` FOREIGN KEY (`crop_field_id`) REFERENCES `crop_fields` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tx_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_inventory` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_yield` FOREIGN KEY (`harvest_yield_id`) REFERENCES `harvest_yields` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `weather_cache`
--
ALTER TABLE `weather_cache`
  ADD CONSTRAINT `fk_weather_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
