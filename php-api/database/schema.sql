-- Database schema for PHP API
CREATE DATABASE IF NOT EXISTS pdns_api_db;
USE pdns_api_db;

-- Accounts table
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    contact VARCHAR(255),
    mail VARCHAR(255),
    ip_addresses TEXT, -- JSON field to store IPv4/IPv6 addresses
    pdns_account_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_pdns_id (pdns_account_id)
);

-- Domains table
CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    type VARCHAR(50) DEFAULT 'Zone',
    account_id INT,
    pdns_zone_id VARCHAR(255),
    kind ENUM('Native', 'Master', 'Slave') DEFAULT 'Master',
    masters TEXT,
    dnssec BOOLEAN DEFAULT FALSE,
    account VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_account_id (account_id),
    INDEX idx_pdns_zone_id (pdns_zone_id)
);

-- API logs table for tracking external API calls
CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(500),
    method VARCHAR(10),
    request_data TEXT,
    response_data TEXT,
    status_code INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at)
);

-- Domain synchronization tracking
CREATE TABLE IF NOT EXISTS domain_sync (
    id INT AUTO_INCREMENT PRIMARY KEY,
    last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_domains INT DEFAULT 0,
    sync_status ENUM('success', 'failed', 'in_progress') DEFAULT 'success',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default sync record
INSERT INTO domain_sync (total_domains, sync_status) VALUES (0, 'success') 
ON DUPLICATE KEY UPDATE total_domains=total_domains;

-- IP Allowlist table for security
CREATE TABLE IF NOT EXISTS ip_allowlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL, -- IPv4 (15 chars) or IPv6 (39 chars) + CIDR
    description VARCHAR(255) DEFAULT '',
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ip_address (ip_address),
    INDEX idx_enabled (enabled),
    UNIQUE KEY unique_ip (ip_address)
);

-- Insert default IP allowlist entries
INSERT INTO ip_allowlist (ip_address, description) VALUES 
    ('127.0.0.1', 'localhost IPv4'),
    ('::1', 'localhost IPv6'),
    ('149.210.167.40', 'server primary IP'),
    ('149.210.166.5', 'server secondary IP'), 
    ('2a01:7c8:aab3:5d8:149:210:166:5', 'server IPv6')
ON DUPLICATE KEY UPDATE description=VALUES(description);
