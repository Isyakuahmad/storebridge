ALTER TABLE users
  ADD COLUMN role ENUM('seller','admin') NOT NULL DEFAULT 'seller' AFTER password_hash,
  ADD COLUMN account_status ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER role;

ALTER TABLE products
  ADD COLUMN moderation_status ENUM('pending','approved','rejected','flagged') NOT NULL DEFAULT 'pending' AFTER available,
  ADD COLUMN moderation_note TEXT NULL AFTER moderation_status,
  ADD COLUMN moderated_by INT UNSIGNED NULL AFTER moderation_note,
  ADD COLUMN moderated_at TIMESTAMP NULL DEFAULT NULL AFTER moderated_by,
  ADD INDEX idx_products_moderation_status (moderation_status),
  ADD CONSTRAINT fk_products_moderated_by FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL;

UPDATE products SET moderation_status='approved' WHERE moderation_status='pending';

CREATE TABLE IF NOT EXISTS audit_logs(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  target_type VARCHAR(50) NOT NULL,
  target_id BIGINT UNSIGNED NULL,
  details TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(actor_user_id,created_at),
  INDEX(target_type,target_id),
  FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;