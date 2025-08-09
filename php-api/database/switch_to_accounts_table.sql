-- Switch domains table from users to accounts table
-- This script updates the foreign key constraint and drops the old users table

-- Show current constraint
SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'domains' AND COLUMN_NAME = 'account_id';

-- Drop the existing foreign key constraint
ALTER TABLE domains DROP FOREIGN KEY domains_ibfk_1;

-- Add new foreign key constraint pointing to accounts table
ALTER TABLE domains ADD CONSTRAINT domains_ibfk_1 
FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Verify the new constraint
SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'domains' AND COLUMN_NAME = 'account_id';

-- Show domains that will be affected (those with account_id not in accounts table)
SELECT 'Domains with invalid account_id:' as message;
SELECT d.name, d.account_id 
FROM domains d 
LEFT JOIN accounts a ON d.account_id = a.id 
WHERE d.account_id IS NOT NULL AND a.id IS NULL;

-- Drop the old users table
DROP TABLE users;

-- Confirm tables
SHOW TABLES LIKE '%users%';
SHOW TABLES LIKE '%accounts%';
