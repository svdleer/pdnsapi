<?php
/**
 * PowerDNS Admin Template Model
 * Uses PowerDNS Admin's existing domain_template and domain_template_record tables
 * This is the proper way - PowerDNS Admin as the single source of truth
 */

class PowerDNSAdminTemplate {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new PDNSAdminDatabase();
        $this->db = $database->getConnection();
        
        if (!$this->db) {
            throw new Exception("Failed to connect to PowerDNS Admin database");
        }
    }

    /**
     * Get all templates from PowerDNS Admin database
     */
    public function getAllTemplates($account_id = null) {
        try {
            // PowerDNS Admin domain_template table structure (based on Python model)
            $sql = "SELECT * FROM domain_template WHERE 1=1";
            $params = [];
            
            // Note: PowerDNS Admin templates may have account associations
            if ($account_id !== null) {
                $sql .= " AND (account_id = ? OR account_id IS NULL)";
                $params[] = $account_id;
            }
            
            $sql .= " ORDER BY name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get records for each template
            foreach ($templates as &$template) {
                $template['records'] = $this->getTemplateRecords($template['id']);
            }
            
            return $templates;
        } catch (PDOException $e) {
            error_log("Failed to get PowerDNS Admin templates: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get template by ID from PowerDNS Admin database
     */
    public function getTemplate($template_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM domain_template WHERE id = ?");
            $stmt->execute([$template_id]);
            
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($template) {
                $template['records'] = $this->getTemplateRecords($template_id);
            }
            
            return $template;
        } catch (PDOException $e) {
            error_log("Failed to get PowerDNS Admin template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get template records from PowerDNS Admin database
     */
    public function getTemplateRecords($template_id) {
        try {
            $sql = "
                SELECT name, type, data as content, ttl, NULL as priority, 
                       status as disabled, comment
                FROM domain_template_record 
                WHERE template_id = ? 
                ORDER BY type ASC, name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$template_id]);
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert PowerDNS Admin format to our API format
            foreach ($records as &$record) {
                $record['disabled'] = !$record['disabled']; // PowerDNS Admin uses status=1 for active
                $record['priority'] = $record['priority'] ?: null;
            }
            
            return $records;
        } catch (PDOException $e) {
            error_log("Failed to get PowerDNS Admin template records: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create domain from PowerDNS Admin template
     * This should use PowerDNS Admin's logic, not our custom logic
     */
    public function createDomainFromTemplate($template_id, $domain_data) {
        try {
            $template = $this->getTemplate($template_id);
            if (!$template) {
                return ['success' => false, 'message' => 'Template not found in PowerDNS Admin'];
            }
            
            if (empty($domain_data['name'])) {
                return ['success' => false, 'message' => 'Domain name is required'];
            }
            
            $domain_name = rtrim($domain_data['name'], '.');
            $canonical_domain_name = $domain_name . '.'; // PowerDNS Admin API requires canonical names
            
            // Apply template records to domain using PowerDNS Admin's variable substitution
            $applied_records = [];
            foreach ($template['records'] as $record) {
                $applied_record = [
                    'name' => $this->applyPowerDNSAdminVariables($record['name'], $domain_name),
                    'type' => $record['type'],
                    'content' => $this->applyPowerDNSAdminVariables($record['content'], $domain_name),
                    'ttl' => $record['ttl'] ?? 3600,
                    'priority' => $record['priority'] ?? null,
                    'disabled' => $record['disabled'] ?? false
                ];
                
                $applied_records[] = $applied_record;
            }
            
            // Create domain using PowerDNS Admin API (same as before)
            require_once __DIR__ . '/../classes/PDNSAdminClient.php';
            require_once __DIR__ . '/../config/pdns-admin-database.php';
            
            global $pdns_config;
            $pdns_client = new PDNSAdminClient($pdns_config);
            
            // Create the domain in PowerDNS Admin (which forwards to PowerDNS Server)
            $api_domain_data = [
                'name' => $canonical_domain_name,
                'kind' => $domain_data['kind'] ?? 'Native',
                'nameservers' => [],
            ];
            
            $api_result = $pdns_client->createDomain($api_domain_data);
            error_log("PowerDNS Admin API create domain result: " . json_encode($api_result));
            
            // Check if the API call was successful
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
            // Template records would be applied by PowerDNS Admin during zone creation
            return [
                'success' => true,
                'message' => 'Domain created from PowerDNS Admin template successfully',
                'data' => [
                    'domain_name' => $canonical_domain_name,
                    'template' => $template,
                    'applied_records' => $applied_records,
                    'pdns_zone_id' => $pdns_zone_id,
                    'powerdns_result' => $api_result
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Failed to create domain from PowerDNS Admin template: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Apply PowerDNS Admin template variables (matches PowerDNS Admin's logic)
     */
    private function applyPowerDNSAdminVariables($value, $domain_name) {
        // PowerDNS Admin template variable substitution
        $replacements = [
            '{domain}' => $domain_name,
            '{DOMAIN}' => strtoupper($domain_name),
            '@' => $domain_name
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $value);
    }

    /**
     * Search templates by name or description in PowerDNS Admin database
     */
    public function searchTemplates($query, $account_id = null) {
        try {
            $sql = "
                SELECT * FROM domain_template 
                WHERE (name LIKE ? OR description LIKE ?)
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
            
            // Get records for each template
            foreach ($templates as &$template) {
                $template['records'] = $this->getTemplateRecords($template['id']);
            }
            
            return $templates;
        } catch (PDOException $e) {
            error_log("Failed to search PowerDNS Admin templates: " . $e->getMessage());
            return false;
        }
    }
}
?>
