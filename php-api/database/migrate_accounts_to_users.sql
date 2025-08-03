-- Migration script to rename accounts table to users and update schema
USE pdns_api_db;

-- Rename accounts table to users (only if accounts table exists)
RENAME TABLE accounts TO users;

-- Add pdns_user_id column
ALTER TABLE users ADD COLUMN pdns_user_id INT AFTER mail;
ALTER TABLE users ADD INDEX idx_pdns_user_id (pdns_user_id);
