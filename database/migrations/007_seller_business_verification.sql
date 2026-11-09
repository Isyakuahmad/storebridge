-- Seller business profile and admin-reviewed verification status.
ALTER TABLE stores
  ADD COLUMN business_country VARCHAR(100) NOT NULL DEFAULT 'Nigeria' AFTER delivery_note,
  ADD COLUMN business_state VARCHAR(100) NULL AFTER business_country,
  ADD COLUMN business_city VARCHAR(100) NULL AFTER business_state,
  ADD COLUMN business_area VARCHAR(120) NULL AFTER business_city,
  ADD COLUMN business_address VARCHAR(255) NULL AFTER business_area,
  ADD COLUMN verification_status ENUM('unsubmitted','pending','verified','rejected') NOT NULL DEFAULT 'unsubmitted' AFTER business_address,
  ADD COLUMN verification_submitted_at DATETIME NULL AFTER verification_status,
  ADD COLUMN verification_reviewed_by INT UNSIGNED NULL AFTER verification_submitted_at,
  ADD COLUMN verification_reviewed_at DATETIME NULL AFTER verification_reviewed_by,
  ADD COLUMN verification_note VARCHAR(500) NULL AFTER verification_reviewed_at,
  ADD INDEX idx_stores_location (business_country,business_state,business_city),
  ADD INDEX idx_stores_verification (verification_status),
  ADD CONSTRAINT fk_stores_verification_reviewer FOREIGN KEY (verification_reviewed_by) REFERENCES users(id) ON DELETE SET NULL;
