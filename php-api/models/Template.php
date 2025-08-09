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
            $canonical_domain_name = $domain_name . '.'; // PowerDNS Admin API requires canonical names
            
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
            
            // Create the domain in PowerDNS Admin (which forwards to PowerDNS Server)
            // PowerDNS Admin expects the same format as PowerDNS Server API
            $api_domain_data = [
                'name' => $canonical_domain_name, // Use canonical name for API
                'kind' => $domain_data['kind'] ?? 'Native', // Native is correct for PowerDNS Admin
                'nameservers' => [], // Will use default nameservers
            ];
            
            error_log("Sending to PowerDNS Admin API: " . json_encode($api_domain_data));
            $api_result = $pdns_client->createDomain($api_domain_data);
            error_log("PowerDNS Admin API create domain result: " . json_encode($api_result));
            
            // Check if the API call was successful (PowerDNS Admin returns 201 for successful creation)
            if (!$api_result || 
                (isset($api_result['status_code']) && $api_result['status_code'] !== 201)) {
                
                $error_msg = 'Failed to create domain in PowerDNS Admin API';
                if (isset($api_result['raw_response'])) {
                    $error_msg .= ': ' . $api_result['raw_response'];
                }
                return ['success' => false, 'message' => $error_msg];
            }
            
            // Extract the zone ID from the API response
            $pdns_zone_id = $api_result['id'] ?? $api_result['data']['id'] ?? null;
            
            // Domain created successfully in PowerDNS Admin
            // Now trigger silent sync to pull the domain into our local database
            try {
                require_once __DIR__ . '/../api/domains.php';
                require_once __DIR__ . '/../models/Domain.php';
                
                // Create domain and client instances for sync
                $domain_obj = new Domain($this->db);
                
                // Call silent sync to pull the newly created domain
                $sync_result = $this->triggerSilentSync($domain_obj, $pdns_client);
                
                return [
                    'success' => true,
                    'message' => 'Domain created from PowerDNS Admin template successfully',
                    'data' => [
                        'domain_name' => $canonical_domain_name,
                        'template' => $template,
                        'applied_records' => $applied_records,
                        'pdns_zone_id' => $pdns_zone_id,
                        'powerdns_result' => $api_result,
                        'sync_result' => $sync_result
                    ]
                ];
            } catch (Exception $sync_error) {
                // Domain was created successfully, sync just failed
                error_log("Domain created but sync failed: " . $sync_error->getMessage());
                
                return [
                    'success' => true,
                    'message' => 'Domain created from PowerDNS Admin template successfully (sync pending)',
                    'data' => [
                        'domain_name' => $canonical_domain_name,
                        'template' => $template,
                        'applied_records' => $applied_records,
                        'pdns_zone_id' => $pdns_zone_id,
                        'powerdns_result' => $api_result,
                        'sync_note' => 'Sync will occur on next API call'
                    ]
                ];
            }
            
        } catch (Exception $e) {
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
     * Trigger silent sync to pull newly created domain into local database
     */
    private function triggerSilentSync($domain_obj, $pdns_client) {
        try {
            // Get all domains from PowerDNS Admin  
            $pdns_response = $pdns_client->getAllDomainsWithAccounts();
            
            if($pdns_response['status_code'] != 200) {
                throw new Exception("Failed to fetch domains for sync: " . ($pdns_response['raw_response'] ?? 'Unknown error'));
            }
            
            $pdns_domains = $pdns_response['data'];
            $synced_count = 0;
            
            // Start a transaction
            $this->db->beginTransaction();
            
            foreach($pdns_domains as $pdns_domain) {
                $domain_name = $pdns_domain['name'] ?? '';
                $pdns_zone_id = $pdns_domain['id'] ?? null;
                $account_id = $pdns_domain['account_id'] ?? null;
                
                if (empty($domain_name)) {
                    continue;
                }
                
                // Check if domain exists in local database
                $stmt = $this->db->prepare("SELECT id FROM domains WHERE name = ?");
                $stmt->execute([$domain_name]);
                $local_domain = $stmt->fetch();
                
                if (!$local_domain) {
                    // Domain doesn't exist locally, add it
                    $default_account_id = $account_id ?? 1; // Default to admin account if no account
                    
                    $insert_stmt = $this->db->prepare("
                        INSERT INTO domains (name, powerdns_zone_id, account_id, created_at, updated_at) 
                        VALUES (?, ?, ?, NOW(), NOW())
                    ");
                    
                    if ($insert_stmt->execute([$domain_name, $pdns_zone_id, $default_account_id])) {
                        $synced_count++;
                    }
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'synced_domains' => $synced_count,
                'message' => $synced_count > 0 ? "Synced $synced_count new domains" : "No new domains to sync"
            ];
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            error_log("Silent sync failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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
