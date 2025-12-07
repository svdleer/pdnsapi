-- Add API Keys table for account-scoped access control
-- This allows creating API keys that are restricted to specific accounts and their domains

CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(128) NOT NULL UNIQUE,
    key_hash VARCHAR(255) NOT NULL,
    account_id INT NULL,
    description VARCHAR(500) DEFAULT '',
    permissions JSON DEFAULT NULL COMMENT 'JSON object defining permissions: {"domains": "rw", "create_domains": true, "delete_domains": false}',
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    created_by INT NULL COMMENT 'Admin user who created this key',
    INDEX idx_key_hash (key_hash),
    INDEX idx_account_id (account_id),
    INDEX idx_enabled (enabled),
    INDEX idx_expires_at (expires_at),
    UNIQUE KEY unique_api_key (api_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to clarify usage
ALTER TABLE api_keys COMMENT = 'API keys for account-scoped access control. account_id=NULL means admin key (full access)';

-- Sample data structure for permissions JSON:
-- {
--   "domains": "rw",           // read-write access to domains
--   "create_domains": true,    // can create new domains
--   "delete_domains": false,   // cannot delete domains
--   "scope": "account"         // scope limited to account's domains only
-- }

-- Example: Create an account-scoped API key for account ID 5
-- INSERT INTO api_keys (api_key, key_hash, account_id, description, permissions) VALUES 
-- (
--     'test_account_key_abc123def456',
--     SHA2('test_account_key_abc123def456', 256),
--     5,
--     'API key for customer account 5',
--     '{"domains": "rw", "create_domains": true, "delete_domains": false, "scope": "account"}'
-- );

