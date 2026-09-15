-- ============================================================
-- ASIF SNOOKER CLUB - Initial Schema
-- MySQL / MariaDB
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Users (staff & admin accounts)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    phone         VARCHAR(20)  DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('owner','admin','eco','counter','staff','auditor') NOT NULL DEFAULT 'staff',
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    remember_token VARCHAR(100) DEFAULT NULL,
    last_login_at DATETIME DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tables (snooker tables)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tables (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    number            VARCHAR(10) NOT NULL,
    name              VARCHAR(120) NOT NULL,
    type              VARCHAR(60)  DEFAULT 'Standard' COMMENT 'Standard, VIP, Tournament...',
    hourly_rate       DECIMAL(10,2) NOT NULL DEFAULT 300.00,
    frame_rate        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    peak_rate         DECIMAL(10,2) DEFAULT NULL,
    off_peak_rate     DECIMAL(10,2) DEFAULT NULL,
    vip_rate          DECIMAL(10,2) DEFAULT NULL,
    night_rate        DECIMAL(10,2) DEFAULT NULL,
    min_charge        DECIMAL(10,2) NOT NULL DEFAULT 100.00,
    status            ENUM('available','occupied','reserved','maintenance','blocked','offline') NOT NULL DEFAULT 'available',
    location          VARCHAR(120) DEFAULT NULL,
    camera_id         VARCHAR(60)  DEFAULT NULL,
    camera_url        TEXT DEFAULT NULL,
    maintenance_notes TEXT DEFAULT NULL,
    last_maintenance  DATE DEFAULT NULL,
    is_active         TINYINT(1) NOT NULL DEFAULT 1,
    sort_order        INT NOT NULL DEFAULT 0,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status (status),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Customers
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name                 VARCHAR(160) NOT NULL,
    phone                VARCHAR(20)  DEFAULT NULL,
    whatsapp             VARCHAR(20)  DEFAULT NULL,
    email                VARCHAR(190) DEFAULT NULL,
    category             ENUM('regular','vip','member','tournament','inactive') NOT NULL DEFAULT 'regular',
    notes                TEXT DEFAULT NULL,
    total_visits         INT UNSIGNED NOT NULL DEFAULT 0,
    total_hours          DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_spent          DECIMAL(12,2) NOT NULL DEFAULT 0,
    outstanding_balance  DECIMAL(12,2) NOT NULL DEFAULT 0,
    last_visit_at        DATETIME DEFAULT NULL,
    status               ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_phone (phone),
    KEY idx_status (status),
    KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Sessions (table usage)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    table_id         INT UNSIGNED NOT NULL,
    customer_id      INT UNSIGNED DEFAULT NULL,
    players_count    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    start_time       DATETIME NOT NULL,
    end_time         DATETIME DEFAULT NULL,
    paused_at        DATETIME DEFAULT NULL,
    paused_total_sec INT UNSIGNED NOT NULL DEFAULT 0,
    rate_type        ENUM('hourly','frame','peak','off_peak','vip','night','custom') NOT NULL DEFAULT 'hourly',
    rate             DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount         DECIMAL(10,2) NOT NULL DEFAULT 0,
    extra_charges    DECIMAL(10,2) NOT NULL DEFAULT 0,
    amount           DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status   ENUM('unpaid','partial','paid','refunded') NOT NULL DEFAULT 'unpaid',
    payment_method   VARCHAR(20) DEFAULT NULL,
    notes            TEXT DEFAULT NULL,
    status           ENUM('active','paused','completed','cancelled') NOT NULL DEFAULT 'active',
    staff_id         INT UNSIGNED DEFAULT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_table (table_id),
    KEY idx_customer (customer_id),
    KEY idx_status (status),
    KEY idx_start (start_time),
    CONSTRAINT fk_sess_table FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE,
    CONSTRAINT fk_sess_cust FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_sess_staff FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Bookings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    table_id        INT UNSIGNED NOT NULL,
    customer_id     INT UNSIGNED DEFAULT NULL,
    customer_name   VARCHAR(160) DEFAULT NULL,
    customer_phone  VARCHAR(20)  DEFAULT NULL,
    booking_date    DATE NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    players_count   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status          ENUM('requested','confirmed','arrived','active','completed','cancelled','no_show','expired','paid') NOT NULL DEFAULT 'requested',
    notes           TEXT DEFAULT NULL,
    created_by      INT UNSIGNED DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_table (table_id),
    KEY idx_customer (customer_id),
    KEY idx_date (booking_date),
    KEY idx_status (status),
    CONSTRAINT fk_book_table FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE,
    CONSTRAINT fk_book_cust FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Payments
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id        BIGINT UNSIGNED DEFAULT NULL,
    booking_id        INT UNSIGNED DEFAULT NULL,
    customer_id       INT UNSIGNED DEFAULT NULL,
    amount            DECIMAL(12,2) NOT NULL,
    method            ENUM('cash','jazzcash','bank_transfer','card','other') NOT NULL DEFAULT 'cash',
    status            ENUM('pending','paid','failed','cancelled','refunded','partial') NOT NULL DEFAULT 'paid',
    transaction_ref   VARCHAR(120) DEFAULT NULL,
    notes             TEXT DEFAULT NULL,
    paid_at           DATETIME DEFAULT NULL,
    accepted_by       INT UNSIGNED DEFAULT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_session (session_id),
    KEY idx_customer (customer_id),
    KEY idx_method (method),
    KEY idx_paid_at (paid_at),
    CONSTRAINT fk_pay_sess FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL,
    CONSTRAINT fk_pay_cust FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Expenses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS expenses (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category      VARCHAR(60) NOT NULL COMMENT 'Electricity, Labour, Rent, Maintenance, Cleaning, Supplies, Internet, Security, Camera, Other',
    amount        DECIMAL(12,2) NOT NULL,
    expense_date  DATE NOT NULL,
    paid_by       VARCHAR(120) DEFAULT NULL,
    vendor        VARCHAR(160) DEFAULT NULL,
    description   TEXT DEFAULT NULL,
    receipt_path  VARCHAR(255) DEFAULT NULL,
    status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    approved_by   INT UNSIGNED DEFAULT NULL,
    notes         TEXT DEFAULT NULL,
    created_by    INT UNSIGNED DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_category (category),
    KEY idx_date (expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Audit Log
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,
    entity      VARCHAR(60)  DEFAULT NULL,
    record_id   VARCHAR(30)  DEFAULT NULL,
    old_value   TEXT DEFAULT NULL,
    new_value   TEXT DEFAULT NULL,
    ip_address  VARCHAR(45) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_entity (entity, record_id),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Settings (key-value store for configurable options)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`      VARCHAR(120) NOT NULL UNIQUE,
    value      TEXT DEFAULT NULL,
    `group`    VARCHAR(60) DEFAULT 'general',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_group (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Permissions (RBAC)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS permissions (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(200) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role          VARCHAR(30) NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role, permission_id),
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;