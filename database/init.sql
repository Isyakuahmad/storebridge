CREATE DATABASE IF NOT EXISTS catalogue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; USE catalogue;
CREATE TABLE users(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL UNIQUE,password_hash VARCHAR(255) NOT NULL,role ENUM('seller','admin') NOT NULL DEFAULT 'seller',account_status ENUM('active','suspended') NOT NULL DEFAULT 'active',referral_code VARCHAR(20) NULL UNIQUE,referred_by_user_id INT UNSIGNED NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_users_referred_by(referred_by_user_id),FOREIGN KEY(referred_by_user_id) REFERENCES users(id) ON DELETE SET NULL)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE stores(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT UNSIGNED NOT NULL UNIQUE,name VARCHAR(160) NOT NULL,slug VARCHAR(180) NOT NULL UNIQUE,description TEXT,whatsapp_number VARCHAR(30) NOT NULL,delivery_note TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE products(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,store_id INT UNSIGNED NOT NULL,name VARCHAR(180) NOT NULL,description TEXT,price DECIMAL(12,2) NOT NULL,category VARCHAR(100) NOT NULL,image VARCHAR(255),variations VARCHAR(255),available TINYINT(1) NOT NULL DEFAULT 1,moderation_status ENUM('pending','approved','rejected','flagged') NOT NULL DEFAULT 'pending',moderation_note TEXT,moderated_by INT UNSIGNED NULL,moderated_at TIMESTAMP NULL DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX(store_id,category),INDEX(moderation_status),FOREIGN KEY(store_id) REFERENCES stores(id) ON DELETE CASCADE,FOREIGN KEY(moderated_by) REFERENCES users(id) ON DELETE SET NULL)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE orders(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,store_id INT UNSIGNED NOT NULL,customer_name VARCHAR(160) NOT NULL,customer_phone VARCHAR(40) NOT NULL,customer_address TEXT NOT NULL,notes TEXT,total DECIMAL(12,2) NOT NULL,status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX(store_id,status),FOREIGN KEY(store_id) REFERENCES stores(id) ON DELETE CASCADE)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE order_items(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,order_id BIGINT UNSIGNED NOT NULL,product_id INT UNSIGNED NULL,product_name VARCHAR(180) NOT NULL,unit_price DECIMAL(12,2) NOT NULL,quantity INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(order_id),FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE audit_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,actor_user_id INT UNSIGNED NULL,action VARCHAR(100) NOT NULL,target_type VARCHAR(50) NOT NULL,target_id BIGINT UNSIGNED NULL,details TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(actor_user_id,created_at),INDEX(target_type,target_id),FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE password_reset_tokens(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id INT UNSIGNED NOT NULL,token_hash CHAR(64) NOT NULL UNIQUE,expires_at DATETIME NOT NULL,used_at DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(user_id,expires_at),INDEX(expires_at),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subscription plans, manual bank-transfer payment review, and subscription history.
CREATE TABLE plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  monthly_price DECIMAL(12,2) NULL DEFAULT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO plans (code,name,monthly_price,description) VALUES
('free','Free',0.00,'Basic access to Choosery.'),
('moderate','Moderate',NULL,'For sellers who need more room to grow. Set the monthly price in Admin > Plans.'),
('premium','Premium',NULL,'For sellers who need the full paid experience. Set the monthly price in Admin > Plans.');

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  billing_cycle ENUM('monthly') NOT NULL DEFAULT 'monthly',
  payment_method ENUM('bank_transfer') NOT NULL DEFAULT 'bank_transfer',
  sender_name VARCHAR(160) NOT NULL,
  transfer_reference VARCHAR(190) NOT NULL,
  status ENUM('pending','confirmed','rejected','cancelled') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(500) NULL,
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_payments_user_status (user_id,status),
  INDEX idx_payments_status_created (status,created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES plans(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscriptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  payment_id BIGINT UNSIGNED NOT NULL UNIQUE,
  status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_subscriptions_user_dates (user_id,status,ends_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES plans(id),
  FOREIGN KEY (payment_id) REFERENCES payments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;