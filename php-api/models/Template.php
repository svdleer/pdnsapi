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
            
            // Create domain in local database (template is only used for creation)
            require_once __DIR__ . '/Domain.php';
            $domainModel = new Domain($this->db);
            
            $domain_result = $domainModel->createDomain([
                'name' => $domain_name,
                'type' => $domain_data['type'] ?? 'Zone',
                'kind' => $domain_data['kind'] ?? 'Master',
                'account_id' => $domain_data['account_id'] ?? $template['account_id'],
                'records' => $applied_records  // Template records applied during creation only
            ]);
            
            if ($domain_result) {
                return [
                    'success' => true,
                    'message' => 'Domain created from template successfully',
                    'data' => [
                        'domain' => $domain_result,
                        'template' => $template,
                        'applied_records' => $applied_records
                    ]
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create domain from template'];
            
        } catch (Exception $e) {
            error_log("Failed to create domain from template: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
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
