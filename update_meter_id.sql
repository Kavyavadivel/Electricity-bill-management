-- Add meter_id column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS meter_id VARCHAR(20) UNIQUE;

-- Update existing users with a default meter ID if they don't have one
UPDATE users 
SET meter_id = CONCAT('METER-', LPAD(user_id, 6, '0'))
WHERE meter_id IS NULL; 