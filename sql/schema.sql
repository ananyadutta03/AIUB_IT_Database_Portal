-- =====================================================================
-- AIUB IT Database Portal — Database Schema
-- Run this once in phpMyAdmin (SQL tab) to create the database + tables.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `aiub_auth_info`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `aiub_auth_info`;

-- ---------------------------------------------------------------------
-- users : admin login accounts
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(50)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name`     VARCHAR(100) DEFAULT NULL,
  `role`          ENUM('admin') DEFAULT 'admin',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- inventory : unified table for all 25 sheets
-- The `sheet_name` column tells us which Excel sheet a row came from.
-- All other columns are nullable so different sheets can use different fields.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory` (
  `id`                    INT AUTO_INCREMENT PRIMARY KEY,

  -- which sheet / sub-section this row belongs to
  `sheet_name`            VARCHAR(50)  NOT NULL,
  `section`               VARCHAR(100) DEFAULT NULL,
  `serial_no`             VARCHAR(20)  DEFAULT NULL,

  -- person info
  `full_name`             VARCHAR(200) DEFAULT NULL,
  `employee_id`           VARCHAR(50)  DEFAULT NULL,
  `email`                 VARCHAR(150) DEFAULT NULL,
  `username`              VARCHAR(100) DEFAULT NULL,
  `contact_number`        VARCHAR(50)  DEFAULT NULL,
  `designation`           VARCHAR(150) DEFAULT NULL,
  `department`            VARCHAR(100) DEFAULT NULL,

  -- location
  `room`                  VARCHAR(100) DEFAULT NULL,
  `location`              VARCHAR(200) DEFAULT NULL,
  `building`              VARCHAR(50)  DEFAULT NULL,

  -- network
  `ip_address`            VARCHAR(100) DEFAULT NULL,
  `mac_address`           VARCHAR(100) DEFAULT NULL,
  `switch_port`           VARCHAR(100) DEFAULT NULL,
  `ip_phone`              VARCHAR(50)  DEFAULT NULL,
  `extension`             VARCHAR(20)  DEFAULT NULL,

  -- hardware
  `cpu_model`             VARCHAR(200) DEFAULT NULL,
  `processor`             VARCHAR(100) DEFAULT NULL,
  `ram`                   VARCHAR(50)  DEFAULT NULL,
  `monitor`               VARCHAR(100) DEFAULT NULL,
  `hardware_description`  TEXT         DEFAULT NULL,

  -- peripherals
  `printer`               VARCHAR(200) DEFAULT NULL,
  `scanner`               VARCHAR(200) DEFAULT NULL,
  `ups`                   VARCHAR(100) DEFAULT NULL,

  -- device-specific (projector, printer, flap door, face detector, kiosk...)
  `device_model`          VARCHAR(200) DEFAULT NULL,
  `device_serial`         VARCHAR(100) DEFAULT NULL,
  `status`                VARCHAR(50)  DEFAULT NULL,

  -- catch-all for anything that doesn't fit a column above
  `notes`                 TEXT         DEFAULT NULL,

  `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- normal indexes (fast lookups by these columns)
  INDEX `idx_sheet`       (`sheet_name`),
  INDEX `idx_full_name`   (`full_name`),
  INDEX `idx_employee_id` (`employee_id`),
  INDEX `idx_email`       (`email`),
  INDEX `idx_ip`          (`ip_address`),
  INDEX `idx_room`        (`room`),

  -- fulltext index for the dashboard search box
  FULLTEXT KEY `ft_search` (
    `full_name`, `employee_id`, `email`, `username`,
    `contact_number`, `room`, `ip_address`, `mac_address`,
    `hardware_description`, `notes`, `device_model`
  )
) ENGINE=InnoDB;
