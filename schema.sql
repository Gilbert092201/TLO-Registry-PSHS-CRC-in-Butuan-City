-- ============================================================
-- Philippine Science High School - Caraga Region Campus
-- Technology Licensing Office (TLO) Registry
-- Database Schema for MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS tlo_registry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tlo_registry;

-- ------------------------------------------------------------
-- Table: users  (login / signup accounts)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: ip_entries  (IP / Technology Transfer registry rows)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ip_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(50) NOT NULL,
    entry_month TINYINT NOT NULL,              -- 1-12
    entry_day TINYINT NOT NULL,                -- 1-31
    entry_year SMALLINT NOT NULL,              -- e.g. 2026
    ip_name VARCHAR(150) NOT NULL,
    application_number VARCHAR(100) NOT NULL,
    application_code VARCHAR(100) NOT NULL,
    status_of_application ENUM('Filled','Registered','Formality examination','Substantive examination') NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ip_type ENUM('Trademark','Copyright','Industrial Design','Utility Model','Patent') NOT NULL,
    mode_of_transfer ENUM('Commercialization','Deployment','Extension','No Transfer') NOT NULL,
    title_of_ip VARCHAR(255) NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Upgrading an existing database created from an earlier version
-- of this schema? Run these two statements instead of the
-- CREATE TABLE above:
--
-- ALTER TABLE ip_entries ADD COLUMN application_code VARCHAR(100) NOT NULL AFTER application_number;
-- ALTER TABLE ip_entries MODIFY COLUMN status_of_application
--     ENUM('Filled','Registered','Formality examination','Substantive examination') NOT NULL;
-- ------------------------------------------------------------

-- Helpful indexes for filtering
CREATE INDEX idx_ip_type ON ip_entries (ip_type);
CREATE INDEX idx_mode_transfer ON ip_entries (mode_of_transfer);
CREATE INDEX idx_status ON ip_entries (status_of_application);
CREATE INDEX idx_year_month ON ip_entries (entry_year, entry_month);
