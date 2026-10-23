CREATE DATABASE IF NOT EXISTS printing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE printing_system;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS print_rates (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  paper_size ENUM('A4','Letter','Legal') NOT NULL,
  print_color ENUM('bw','color') NOT NULL,
  price_per_page DECIMAL(10,2) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  updated_by BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_rate (paper_size,print_color),
  CONSTRAINT fk_rate_admin FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT ck_rate_price CHECK (price_per_page > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS print_orders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_no VARCHAR(30) NOT NULL UNIQUE,
  submission_token CHAR(32) NOT NULL UNIQUE,
  customer_id BIGINT UNSIGNED NOT NULL,
  rate_id BIGINT UNSIGNED NOT NULL,
  paper_size ENUM('A4','Letter','Legal') NOT NULL,
  print_color ENUM('bw','color') NOT NULL,
  sides ENUM('single','double') NOT NULL DEFAULT 'single',
  pages_per_copy INT UNSIGNED NOT NULL,
  copies INT UNSIGNED NOT NULL,
  unit_price_snapshot DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  status ENUM('pending','approved','printing','ready','completed','rejected','canceled') NOT NULL DEFAULT 'pending',
  admin_note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at TIMESTAMP NULL DEFAULT NULL,
  completed_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES users(id),
  CONSTRAINT fk_order_rate FOREIGN KEY (rate_id) REFERENCES print_rates(id),
  CONSTRAINT ck_order_pages CHECK (pages_per_copy >= 1),
  CONSTRAINT ck_order_copies CHECK (copies BETWEEN 1 AND 100),
  KEY idx_customer_date (customer_id,created_at),
  KEY idx_status_date (status,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_files (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL UNIQUE,
  original_name VARCHAR(255) NOT NULL,
  stored_name CHAR(48) NOT NULL UNIQUE,
  file_size BIGINT UNSIGNED NOT NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_file_order FOREIGN KEY (order_id) REFERENCES print_orders(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL UNIQUE,
  amount DECIMAL(10,2) NOT NULL,
  method ENUM('cash') NOT NULL DEFAULT 'cash',
  status ENUM('paid') NOT NULL DEFAULT 'paid',
  recorded_by BIGINT UNSIGNED NOT NULL,
  paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES print_orders(id),
  CONSTRAINT fk_payment_admin FOREIGN KEY (recorded_by) REFERENCES users(id),
  CONSTRAINT ck_payment_amount CHECK (amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_status_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  actor_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(20) NULL,
  new_status VARCHAR(20) NOT NULL,
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_order FOREIGN KEY (order_id) REFERENCES print_orders(id),
  CONSTRAINT fk_history_actor FOREIGN KEY (actor_id) REFERENCES users(id),
  KEY idx_history_order (order_id,id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rate_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  rate_id BIGINT UNSIGNED NOT NULL,
  actor_id BIGINT UNSIGNED NOT NULL,
  old_price DECIMAL(10,2) NOT NULL,
  new_price DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rate_id) REFERENCES print_rates(id),
  FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB;
