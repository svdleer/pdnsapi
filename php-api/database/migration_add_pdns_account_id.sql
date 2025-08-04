-- Migration to add pdns_account_id column to domains table
ALTER TABLE domains 
ADD COLUMN pdns_account_id INT AFTER pdns_zone_id,
ADD INDEX idx_pdns_account_id (pdns_account_id);
