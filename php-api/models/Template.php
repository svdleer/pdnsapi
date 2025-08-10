<?php
/**
 * Template Model - PowerDNS Admin Database Integration
 * Uses PowerDNS Admin as the only source of truth for templates
 */

class Template {
    private $pdns_admin_db;

    public function __construct($database = null) {
        if ($database) {
            $this->pdns_admin_db = $database;
        } else {
            require_once __DIR__ . '/../config/database.php';
            global $pdns_admin_pdo;
            $this->pdns_admin_db = $pdns_admin_pdo;
        }
        
        if (!$this->pdns_admin_db) {
            throw new Exception("PowerDNS Admin database connection not available");
        }
    }

    /**
     * Get all templates from PowerDNS Admin
     */
    public function getAllTemplates($account_id = null, $active_only = true) {
        try {
            $sql = "SELECT * FROM domain_template WHERE 1=1";
            $params = [];
            
            // PowerDNS Admin doesn't have account_id in domain_template, so we'll ignore this filter for now
            
            $sql .= " ORDER BY name ASC";
            
            $stmt = $this->pdns_admin_db->prepare($sql);
            $stmt->execute($params);
            
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get records for each template
            foreach ($templates as &$template) {
                $template['records'] = $this->getTemplateRecords($template['id']);
            }
            
            return $templates;
        } catch (PDOException $e) {
            error_log("Failed to get all templates from PowerDNS Admin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get template by ID from PowerDNS Admin
     */
    public function getTemplate($template_id) {
        try {
            $stmt = $this->pdns_admin_db->prepare("SELECT * FROM domain_template WHERE id = ?");
            $stmt->execute([$template_id]);
            
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($template) {
                $template['records'] = $this->getTemplateRecords($template_id);
            }
            
            return $template;
        } catch (PDOException $e) {
            error_log("Failed to get template from PowerDNS Admin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get template records from PowerDNS Admin
     */
    private function getTemplateRecords($template_id) {
        try {
            $stmt = $this->pdns_admin_db->prepare("
                SELECT name, type, ttl, data, status, comment 
                FROM domain_template_record 
                WHERE template_id = ? 
                ORDER BY name, type
            ");
            $stmt->execute([$template_id]);
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert to our expected format
            $formatted_records = [];
            foreach ($records as $record) {
                $formatted_records[] = [
                    'name' => $record['name'],
                    'type' => $record['type'],
                    'content' => $record['data'], // 'data' is the correct column name in PowerDNS Admin
                    'ttl' => (int)$record['ttl'],
                    'priority' => null, // PowerDNS Admin doesn't separate priority
                    'disabled' => !$record['status'], // status=1 means active, so !status means disabled
                    'comment' => $record['comment'] ?? ''
                ];
            }
            
            return $formatted_records;
        } catch (PDOException $e) {
            error_log("Failed to get template records from PowerDNS Admin: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create new template (not implemented - use PowerDNS Admin web interface)
     */
    public function createTemplate($template_data) {
        // Template creation should be done through PowerDNS Admin web interface
        // This method is kept for API compatibility but not implemented
        error_log("Template creation should be done through PowerDNS Admin web interface");
        return false;
    }

    /**
     * Update template (not implemented - use PowerDNS Admin web interface)
     */
    public function updateTemplate($template_id, $template_data) {
        // Template updates should be done through PowerDNS Admin web interface
        // This method is kept for API compatibility but not implemented
        error_log("Template updates should be done through PowerDNS Admin web interface");
        return false;
    }

    /**
     * Delete template (not implemented - use PowerDNS Admin web interface)
     */
    public function deleteTemplate($template_id) {
        // Template deletion should be done through PowerDNS Admin web interface
        // This method is kept for API compatibility but not implemented
        error_log("Template deletion should be done through PowerDNS Admin web interface");
        return false;
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
            
            // Create domain using PowerDNS Admin API with template records in one pass
            require_once __DIR__ . '/../classes/PDNSAdminClient.php';
            require_once __DIR__ . '/../config/pdns-admin-database.php';
            
            global $pdns_config;
            $pdns_client = new PDNSAdminClient($pdns_config);
            
            // Prepare rrsets from template records
            $rrsets = $this->prepareRRSets($applied_records, $canonical_domain_name);
            
            // Create the domain in PowerDNS Admin with rrsets in one pass
            $api_domain_data = [
                'name' => $canonical_domain_name,
                'kind' => $domain_data['kind'] ?? 'Native',
                'rrsets' => $rrsets, // Include rrsets in domain creation
                'nameservers' => [], // Will use default nameservers
            ];
            
            error_log("Creating domain with template records in one pass: " . json_encode($api_domain_data));
            $api_result = $pdns_client->createDomain($api_domain_data);
            error_log("PowerDNS Admin API create domain with rrsets result: " . json_encode($api_result));
            
            // Check if the API call was successful
            if (!$api_result || 
                (isset($api_result['status_code']) && $api_result['status_code'] !== 201)) {
                
                $error_msg = 'Failed to create domain with template in PowerDNS Admin API';
                if (isset($api_result['raw_response'])) {
                    $error_msg .= ': ' . $api_result['raw_response'];
                }
                return ['success' => false, 'message' => $error_msg];
            }
            
            // Extract the zone ID from the API response
            $pdns_zone_id = $api_result['data']['id'] ?? $api_result['data']['name'] ?? $canonical_domain_name;
            
            // Domain created successfully in PowerDNS Admin
            // Skip local database creation and rely on sync instead
            return [
                'success' => true,
                'message' => 'Domain created from template successfully in PowerDNS Admin',
                'data' => [
                    'domain_name' => $canonical_domain_name,
                    'template' => $template,
                    'applied_records' => $applied_records,
                    'pdns_zone_id' => $pdns_zone_id,
                    'powerdns_result' => $api_result
                ]
            ];
            
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
     * Prepare rrsets for PowerDNS API from template records
     */
    private function prepareRRSets($applied_records, $domain_name) {
        $rrsets = [];
        $grouped_records = [];
        
        // Group records by name and type
        foreach ($applied_records as $record) {
            $key = $record['name'] . '|' . $record['type'];
            if (!isset($grouped_records[$key])) {
                // Fix record name resolution - avoid double domain names
                $record_name = $record['name'];
                if ($record_name === '@') {
                    // @ should become the domain name itself
                    $final_name = rtrim($domain_name, '.');
                } elseif ($record_name === rtrim($domain_name, '.') || $record_name === $domain_name) {
                    // If record name is already the domain name, use as-is
                    $final_name = rtrim($domain_name, '.');
                } elseif (strpos($record_name, '.') === false) {
                    // If record name has no dots, it's a subdomain - append domain
                    $final_name = $record_name . '.' . rtrim($domain_name, '.');
                } else {
                    // Record name already contains dots - use as-is
                    $final_name = $record_name;
                }
                
                $grouped_records[$key] = [
                    'name' => $final_name,
                    'type' => $record['type'],
                    'ttl' => $record['ttl'],
                    'records' => []
                ];
            }
            
            $grouped_records[$key]['records'][] = [
                'content' => $record['content'],
                'disabled' => $record['disabled'] ?? false
            ];
        }
        
        // Convert to rrsets format
        foreach ($grouped_records as $group) {
            $rrsets[] = [
                'name' => $group['name'],
                'type' => $group['type'],
                'ttl' => $group['ttl'],
                'changetype' => 'REPLACE',
                'records' => $group['records']
            ];
        }
        
        return $rrsets;
    }

    /**
     * Search templates by name or description in PowerDNS Admin
     */
    public function searchTemplates($query, $account_id = null) {
        try {
            $sql = "
                SELECT * FROM domain_template 
                WHERE (name LIKE ? OR description LIKE ?)
                ORDER BY name ASC
            ";
            $params = ["%{$query}%", "%{$query}%"];
            
            $stmt = $this->pdns_admin_db->prepare($sql);
            $stmt->execute($params);
            
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get records for each template
            foreach ($templates as &$template) {
                $template['records'] = $this->getTemplateRecords($template['id']);
            }
            
            return $templates;
        } catch (PDOException $e) {
            error_log("Failed to search templates in PowerDNS Admin: " . $e->getMessage());
            return false;
        }
    }
}
?>
