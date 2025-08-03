-- Migration script to add klant_id to users table
USE pdns_api_db;

-- Add klant_id column to users table
ALTER TABLE users ADD COLUMN klant_id INT AFTER pdns_user_id;
ALTER TABLE users ADD INDEX idx_klant_id (klant_id);
