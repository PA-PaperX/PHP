ALTER TABLE issues
  ADD COLUMN payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid' AFTER status,
  ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER payment_status;
