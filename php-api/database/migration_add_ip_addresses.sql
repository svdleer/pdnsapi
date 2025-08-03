-- Migration script to add ip_addresses column to accounts table
-- Run this if you have an existing installation

USE pdns_api_db;

-- Add ip_addresses column to accounts table
ALTER TABLE accounts 
ADD COLUMN ip_addresses TEXT COMMENT 'JSON field to store IPv4/IPv6 addresses' 
AFTER mail;

-- Update the schema version (optional tracking)
INSERT INTO api_logs (endpoint, method, request_data, response_data, status_code) 
VALUES ('migration', 'ADD_COLUMN', 'ip_addresses added to accounts table', 'SUCCESS', 200);
