-- Drop the old users table since PowerDNS Admin is the source of truth
-- This table is redundant and could cause confusion

USE pdns_api_db;

-- Safety check: Show what's in the users table before dropping
SELECT 'Current users table content:' as info;
SELECT id, username, email, created_at FROM users LIMIT 5;

-- Drop the users table
DROP TABLE IF EXISTS users;

-- Verify it's gone
SELECT 'Remaining tables after dropping users:' as info;
SHOW TABLES;
