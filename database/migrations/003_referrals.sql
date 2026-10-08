ALTER TABLE users
  ADD COLUMN referral_code VARCHAR(20) NULL UNIQUE,
  ADD COLUMN referred_by_user_id INT UNSIGNED NULL,
  ADD INDEX idx_users_referred_by (referred_by_user_id),
  ADD CONSTRAINT fk_users_referred_by FOREIGN KEY (referred_by_user_id) REFERENCES users(id) ON DELETE SET NULL;
