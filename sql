-- Create database
CREATE DATABASE IF NOT EXISTS `cba2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cba2`;

-- Drop tables if they already exist (safe to import multiple times)
DROP TABLE IF EXISTS `finance_logs`;
DROP TABLE IF EXISTS `user_finance_entry`;
DROP TABLE IF EXISTS `office_request`;
DROP TABLE IF EXISTS `office_logout`;
DROP TABLE IF EXISTS `office_log`;
DROP TABLE IF EXISTS `office_list`;
DROP TABLE IF EXISTS `log_time`;
DROP TABLE IF EXISTS `user_list`;

-- Users table
CREATE TABLE `user_list` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(64) DEFAULT NULL, -- application-level unique id (uniqid)
  `fullname` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `country_code` VARCHAR(16) DEFAULT NULL,
  `mobile` VARCHAR(50) DEFAULT NULL,
  `address` TEXT,
  `dob` DATE DEFAULT NULL,
  `gender` VARCHAR(20) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `policy_agreed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`),
  UNIQUE KEY `uq_user_userid` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Office (business) list
CREATE TABLE `office_list` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `business_name` VARCHAR(255) NOT NULL,
  `business_logo` VARCHAR(255) DEFAULT NULL,
  `business_email` VARCHAR(255) NOT NULL,
  `office_address` TEXT,
  `country_code` VARCHAR(16) DEFAULT NULL,
  `contact_number` VARCHAR(50) DEFAULT NULL,
  `creation_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `purchase_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `founder_email` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_office_email` (`business_email`),
  KEY `idx_founder_email` (`founder_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Office join/requests (membership & permissions)
CREATE TABLE `office_request` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_email` VARCHAR(255) NOT NULL,
  `office_email` VARCHAR(255) NOT NULL,
  `status` ENUM('Yes','No','waiting','ban') NOT NULL DEFAULT 'waiting',
  `position` VARCHAR(50) NOT NULL DEFAULT 'member', -- founder, manager, finance_manager, member etc.
  `full_name` VARCHAR(255) DEFAULT NULL,
  `mobile_number` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_office` (`user_email`,`office_email`),
  KEY `idx_office_email` (`office_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Finance entries (journal/ledger entries)
CREATE TABLE `user_finance_entry` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `entry_type` VARCHAR(50) DEFAULT NULL,         -- e.g., journal, cash, bank
  `entry_date` DATE DEFAULT NULL,                -- used with YEAR(entry_date) queries
  `debit_account` VARCHAR(255) DEFAULT NULL,
  `credit_account` VARCHAR(255) DEFAULT NULL,
  `transaction_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) DEFAULT 'USD',
  `reference` VARCHAR(255) DEFAULT NULL,
  `comments` TEXT,
  `document_path` VARCHAR(255) DEFAULT NULL,     -- uploaded document path
  `office_email` VARCHAR(255) DEFAULT NULL,
  `office_id` INT(11) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,             -- links to user_list.id
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(50) DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_office_email` (`office_email`),
  KEY `idx_office_id` (`office_id`),
  KEY `idx_entry_date` (`entry_date`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs for finance actions (deletes/updates)
CREATE TABLE `finance_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `entry_id` INT(11) NOT NULL,
  `office_email` VARCHAR(255) DEFAULT NULL,
  `action` VARCHAR(100) DEFAULT NULL,  -- e.g., 'deleted', 'created', 'updated'
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entry_id` (`entry_id`),
  KEY `idx_office_email` (`office_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Office join log (log when a user joins office)
CREATE TABLE `office_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_email` VARCHAR(255) NOT NULL,
  `office_email` VARCHAR(255) NOT NULL,
  `join_time` VARCHAR(50) NOT NULL, -- stored as mm/dd/yy per your code
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_office_email` (`office_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Office logout log (when user logs out of an office)
CREATE TABLE `office_logout` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_email` VARCHAR(255) NOT NULL,
  `office_email` VARCHAR(255) NOT NULL,
  `logout_time` VARCHAR(50) NOT NULL, -- stored as mm/dd/yy per your code
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_office_email` (`office_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login times for users
CREATE TABLE `log_time` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL, -- stores user_list.id
  `email` VARCHAR(255) DEFAULT NULL,
  `login_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: add foreign key constraints if desired (commented out)
-- ALTER TABLE `office_list`
--   ADD CONSTRAINT `fk_office_founder_email` FOREIGN KEY (`founder_email`) REFERENCES `user_list` (`email`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Commit / finished