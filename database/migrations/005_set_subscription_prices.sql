-- Set the agreed monthly Choosery prices on databases where migration 004 was already applied.
UPDATE plans SET monthly_price = 4250.00 WHERE code = 'moderate';
UPDATE plans SET monthly_price = 7500.00 WHERE code = 'premium';
