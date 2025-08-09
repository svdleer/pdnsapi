<?php
/**
 * Template Model - Local Database Implementation
 * Handles domain templates since PowerDNS Admin API doesn't support them
 */

class Template {
    private $db;

    public function __construct($database = null) {
        if ($database) {
            $this->db = $database;
        } else {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $this->db = $database->getConnection();
        }
        
        // Ensure templates table exists
        $this->createTemplatesTable();
    }

    /**
     * Create templates table if it doesn't exist
     */
    private function createTemplatesTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS templates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL UNIQUE,
                    description TEXT,
                    records JSON NOT NULL,
                    account_id INT DEFAULT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_account_id (account_id),
                    INDEX idx_name (name),
                    INDEX idx_is_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Failed to create templates table: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all templates
     */
    public function getAllTemplates($account_id = null, $active_only = true) {
        try {
            $sql = "SELECT * FROM templates WHERE 1=1";
            $params = [];
            
            if ($account_id !== null) {
                $sql .= " AND (account_id = ? OR account_id IS NULL)";
                $params[] = $account_id;
            }
            
            if ($active_only) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON records
            foreach ($templates as &$template) {
                $template['records'] = json_decode($template['records'], true);
            }
            
            return $templates;
        } catch (PDOException $e) {
            error_log("Failed to get all templates: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get template by ID
     */
    public function getTemplate($template_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM templates WHERE id = ? AND is_active = 1");
            $stmt->execute([$template_id]);
            
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($template) {
                $template['records'] = json_decode($template['records'], true);
            }
            
            return $template;
        } catch (PDOException $e) {
            error_log("Failed to get template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new template
     */
    public function createTemplate($template_data) {
        try {
            // Validate required fields
            if (empty($template_data['name']) || empty($template_data['records'])) {
                throw new InvalidArgumentException("Template name and records are required");
            }
            
            // Prepare records JSON
            $records_json = is_string($template_data['records']) 
                ? $template_data['records'] 
                : json_encode($template_data['records']);
            
            $sql = "
                INSERT INTO templates (name, description, records, account_id, is_active) 
                VALUES (?, ?, ?, ?, ?)
            ";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $template_data['name'],
                $template_data['description'] ?? '',
                $records_json,
                $template_data['account_id'] ?? null,
                $template_data['is_active'] ?? true
            ]);
            
            if ($success) {
                $template_id = $this->db->lastInsertId();
                return $this->getTemplate($template_id);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Failed to create template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update template
     */
    public function updateTemplate($template_id, $template_data) {
        try {
            // Get current template
            $current = $this->getTemplate($template_id);
            if (!$current) {
                return false;
            }
            
            // Prepare records JSON if provided
            $records_json = null;
            if (isset($template_data['records'])) {
                $records_json = is_string($template_data['records']) 
                    ? $template_data['records'] 
                    : json_encode($template_data['records']);
            }
            
            $sql = "
                UPDATE templates 
                SET name = COALESCE(?, name),
                    description = COALESCE(?, description),
                    records = COALESCE(?, records),
                    account_id = COALESCE(?, account_id),
                    is_active = COALESCE(?, is_active),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $template_data['name'] ?? null,
                $template_data['description'] ?? null,
                $records_json,
                $template_data['account_id'] ?? null,
                $template_data['is_active'] ?? null,
                $template_id
            ]);
            
            if ($success && $stmt->rowCount() > 0) {
                return $this->getTemplate($template_id);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Failed to update template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete template (soft delete by setting is_active = false)
     */
    public function deleteTemplate($template_id) {
        try {
            $stmt = $this->db->prepare("UPDATE templates SET is_active = 0 WHERE id = ?");
            return $stmt->execute([$template_id]) && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Failed to delete template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create domain from template
     */
    public function createDomainFromTemplate($template_id, $domain_data) {
        try {
            $template = $this->getTemplate($template_id);
            if (!$template) {
                return ['success' => false, 'message' => 'Template not found'];
            }
            
            if (empty($domain_data['name'])) {
                return ['success' => false, 'message' => 'Domain name is required'];
            }
            
            $domain_name = rtrim($domain_data['name'], '.');
            
            // Apply template records to domain
            $applied_records = [];
            foreach ($template['records'] as $record) {
                $applied_record = [
                    'name' => $this->applyTemplateVariables($record['name'], $domain_name),
                    'type' => $record['type'],
                    'content' => $this->applyTemplateVariables($record['content'], $domain_name),
                    'ttl' => $record['ttl'] ?? 3600,
                    'priority' => $record['priority'] ?? null,
                    'disabled' => $record['disabled'] ?? false
                ];
                
                $applied_records[] = $applied_record;
            }
            
            // Create domain using PowerDNS Admin API first
            require_once __DIR__ . '/../classes/PDNSAdminClient.php';
            require_once __DIR__ . '/../config/pdns-admin-database.php';
            
            global $pdns_config;
            $pdns_client = new PDNSAdminClient($pdns_config);
            
            // Create the domain in PowerDNS Admin
            $api_domain_data = [
                'name' => $domain_name,
                'kind' => $domain_data['kind'] ?? 'Native',
                'nameservers' => [], // Will use default nameservers
            ];
            
            $api_result = $pdns_client->createDomain($api_domain_data);
            error_log("PowerDNS Admin API create domain result: " . json_encode($api_result));
            
            // Check if the API call was successful (PowerDNS Admin returns different response formats)
            if (!$api_result || 
                (isset($api_result['status_code']) && $api_result['status_code'] !== 200 && $api_result['status_code'] !== 201) ||
                (!isset($api_result['id']) && !isset($api_result['data']['id']))) {
                
                $error_msg = 'Failed to create domain in PowerDNS Admin API';
                if (isset($api_result['raw_response'])) {
                    $error_msg .= ': ' . $api_result['raw_response'];
                }
                return ['success' => false, 'message' => $error_msg];
            }
            
            // Extract the zone ID from the API response
            $pdns_zone_id = $api_result['id'] ?? $api_result['data']['id'] ?? null;
            
            // Now create domain in local database with PowerDNS Admin details
            require_once __DIR__ . '/Domain.php';
            $domainModel = new Domain($this->db);
            
            $domain_result = $domainModel->createDomain([
                'name' => $domain_name,
                'type' => $domain_data['type'] ?? 'Native',
                'kind' => $domain_data['kind'] ?? 'Native', 
                'pdns_user_id' => $domain_data['account_id'] ?? $template['account_id'], // Use account_id as pdns_user_id
                'pdns_zone_id' => $pdns_zone_id, // PowerDNS Admin zone ID
                'account' => '', // Will be set by sync
                'dnssec' => 0,
                'masters' => '',
            ]);
            
            if ($domain_result) {
                // Apply template records to the domain using PowerDNS Admin API
                if (!empty($applied_records)) {
                    $rrsets = [];
                    foreach ($applied_records as $record) {
                        $rrsets[] = [
                            'name' => $record['name'],
                            'type' => $record['type'],
                            'changetype' => 'REPLACE',
                            'records' => [
                                [
                                    'content' => $record['content'],
                                    'disabled' => $record['disabled'] ?? false
                                ]
                            ]
                        ];
                    }
                    
                    // Apply records via PowerDNS Admin API
                    $records_result = $pdns_client->updateDomainRecords($domain_name, $rrsets);
                    error_log("Applied template records to domain {$domain_name}: " . json_encode($records_result));
                }
                
                return [
                    'success' => true,
                    'message' => 'Domain created from template successfully',
                    'data' => [
                        'domain' => $domain_result,
                        'template' => $template,
                        'applied_records' => $applied_records,
                        'pdns_zone_id' => $pdns_zone_id
                    ]
                ];
            }

            error_log("Domain creation failed for: " . $domain_name . " with account_id: " . ($domain_data['account_id'] ?? $template['account_id']));
            return ['success' => false, 'message' => 'Failed to create domain from template - domain creation returned false'];        } catch (Exception $e) {
            error_log("Failed to create domain from template: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Apply template variables to record names and content
     */
    private function applyTemplateVariables($value, $domain_name) {
        // Replace template variables
        $replacements = [
            '{domain}' => $domain_name,
            '{DOMAIN}' => strtoupper($domain_name),
            '@' => $domain_name
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $value);
    }

    /**
     * Search templates by name or description
     */
    public function searchTemplates($query, $account_id = null) {
        try {
            $sql = "
                SELECT * FROM templates 
                WHERE is_active = 1 
                AND (name LIKE ? OR description LIKE ?)
            ";
            $params = ["%{$query}%", "%{$query}%"];
            
            if ($account_id !== null) {
                $sql .= " AND (account_id = ? OR account_id IS NULL)";
                $params[] = $account_id;
            }
            
            $sql .= " ORDER BY name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON records
            foreach ($templates as &$template) {
                $template['records'] = json_decode($template['records'], true);
            }
            
            return $templates;
        } catch (PDOException $e) {
            error_log("Failed to search templates: " . $e->getMessage());
            return false;
        }
    }
}
?>
