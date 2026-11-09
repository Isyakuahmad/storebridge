-- Enable Google-linked identities and first-class buyer accounts.
-- Apply once to an existing database after taking a backup.
ALTER TABLE users
  MODIFY COLUMN password_hash VARCHAR(255) NULL,
  MODIFY COLUMN role ENUM('buyer','seller','admin') NOT NULL DEFAULT 'seller',
  ADD COLUMN google_sub VARCHAR(255) NULL AFTER password_hash,
  ADD UNIQUE KEY uq_users_google_sub (google_sub);
