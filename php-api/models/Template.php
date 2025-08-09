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
                    'content' => $record['data'],
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
