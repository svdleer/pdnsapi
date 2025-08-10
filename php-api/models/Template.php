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
     * Create domain from template - Uses PowerDNS Admin API only (no database writes)
     */
    public function createDomainFromTemplate($template_id, $domain_data) {
        try {
            // Get template from database (read-only operation)
            $template = $this->getTemplate($template_id);
            if (!$template) {
                return ['success' => false, 'message' => 'Template not found'];
            }
            
            if (empty($domain_data['name'])) {
                return ['success' => false, 'message' => 'Domain name is required'];
            }
            
            $domain_name = rtrim($domain_data['name'], '.');
            $canonical_domain_name = $domain_name . '.'; // PowerDNS API requires canonical names
            
            // Initialize API client
            require_once __DIR__ . '/../classes/PDNSAdminClient.php';
            require_once __DIR__ . '/../config/pdns-admin-database.php';
            
            global $pdns_config;
            $pdns_client = new PDNSAdminClient($pdns_config);
            
            // Check if domain already exists via API (not database)
            // Note: This check uses the server API which may have different auth requirements
            $existing_check = $pdns_client->getDomainDetailsByName($canonical_domain_name);
            if ($existing_check && $existing_check['status_code'] === 200) {
                return ['success' => false, 'message' => 'Domain already exists'];
            }
            
            // If we get a 401 on the check, that's OK - domain probably doesn't exist
            // The 401 just means our server API key doesn't have read permissions
            if ($existing_check && $existing_check['status_code'] === 401) {
                error_log("Domain existence check returned 401 - continuing with creation (this is normal if server API key has limited permissions)");
            }
            
            error_log("Creating domain from template: {$template['name']} -> {$canonical_domain_name}");
            
            // Apply template records to domain
            $applied_records = [];
            foreach ($template['records'] as $record) {
                $applied_record = [
                    'name' => $this->applyTemplateVariables($record['name'], $domain_name),
                    'type' => $record['type'],
                    'content' => $this->applyTemplateVariables($record['content'], $domain_name),
                    'ttl' => (int)($record['ttl'] ?? 3600),
                    'priority' => $record['priority'] ?? null,
                    'disabled' => $record['disabled'] ?? false
                ];
                
                $applied_records[] = $applied_record;
            }
            
            // Prepare rrsets from template records for PowerDNS API
            $rrsets = $this->prepareRRSets($applied_records, $canonical_domain_name);
            
            // Create the domain via PowerDNS Admin API with all rrsets
            // Try to create using /pdnsadmin/zones endpoint (this supports rrsets)
            $api_domain_data = [
                'name' => $canonical_domain_name,
                'kind' => $domain_data['kind'] ?? 'Native',
                'rrsets' => $rrsets,
                'nameservers' => $domain_data['nameservers'] ?? []
            ];
            
            error_log("Attempting to create domain via /pdnsadmin/zones with data: " . json_encode($api_domain_data, JSON_PRETTY_PRINT));
            
            $api_result = $pdns_client->createDomain($api_domain_data);
            
            error_log("PowerDNS Admin API result: " . json_encode([
                'status_code' => $api_result['status_code'] ?? 'unknown',
                'has_data' => isset($api_result['data']),
                'raw_response_length' => strlen($api_result['raw_response'] ?? ''),
                'response_preview' => substr($api_result['raw_response'] ?? '', 0, 500)
            ]));
            
            // Handle different response scenarios
            if ($api_result && isset($api_result['status_code'])) {
                if ($api_result['status_code'] === 201) {
                    // Success - domain created with rrsets
                    $pdns_zone_id = null;
                    if (isset($api_result['data'])) {
                        if (is_array($api_result['data'])) {
                            $pdns_zone_id = $api_result['data']['id'] ?? $api_result['data']['name'] ?? $canonical_domain_name;
                        } else {
                            $pdns_zone_id = $canonical_domain_name;
                        }
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'Domain created from template successfully via PowerDNS Admin API',
                        'data' => [
                            'domain_name' => $canonical_domain_name,
                            'template_name' => $template['name'],
                            'template_id' => $template_id,
                            'records_applied' => count($applied_records),
                            'rrsets_created' => count($rrsets),
                            'pdns_zone_id' => $pdns_zone_id,
                            'api_status' => $api_result['status_code']
                        ]
                    ];
                    
                } elseif ($api_result['status_code'] === 409) {
                    // Domain already exists
                    return ['success' => false, 'message' => 'Domain already exists in PowerDNS'];
                    
                } elseif ($api_result['status_code'] === 400) {
                    // Bad request - likely rrsets not supported or malformed
                    error_log("Bad request (400) - trying fallback method without rrsets");
                    
                    // Fallback: Create domain first, then add records
                    $simple_domain_data = [
                        'name' => $canonical_domain_name,
                        'kind' => $domain_data['kind'] ?? 'Native',
                        'nameservers' => $domain_data['nameservers'] ?? []
                    ];
                    
                    error_log("Fallback: Creating domain without rrsets: " . json_encode($simple_domain_data));
                    $fallback_result = $pdns_client->createDomain($simple_domain_data);
                    
                    if ($fallback_result && $fallback_result['status_code'] === 201) {
                        // Domain created, now add records via PowerDNS Server API
                        error_log("Domain created successfully, now adding records via server API");
                        
                        $records_result = $pdns_client->updateDomainRecords($canonical_domain_name, $rrsets);
                        
                        if ($records_result && $records_result['status_code'] >= 200 && $records_result['status_code'] < 300) {
                            return [
                                'success' => true,
                                'message' => 'Domain created from template successfully (fallback method)',
                                'data' => [
                                    'domain_name' => $canonical_domain_name,
                                    'template_name' => $template['name'],
                                    'template_id' => $template_id,
                                    'records_applied' => count($applied_records),
                                    'rrsets_created' => count($rrsets),
                                    'method' => 'fallback_two_step',
                                    'domain_status' => $fallback_result['status_code'],
                                    'records_status' => $records_result['status_code']
                                ]
                            ];
                        } else {
                            $error_msg = 'Domain created but failed to add template records';
                            if (isset($records_result['raw_response'])) {
                                $error_msg .= ': ' . substr($records_result['raw_response'], 0, 200);
                            }
                            return ['success' => false, 'message' => $error_msg];
                        }
                        
                    } else {
                        $error_msg = 'Fallback domain creation also failed';
                        if (isset($fallback_result['raw_response'])) {
                            $error_msg .= ': ' . substr($fallback_result['raw_response'], 0, 200);
                        }
                        return ['success' => false, 'message' => $error_msg];
                    }
                    
                } else {
                    // Other error
                    $error_msg = "PowerDNS Admin API error (status {$api_result['status_code']})";
                    if (isset($api_result['raw_response'])) {
                        $error_msg .= ': ' . substr($api_result['raw_response'], 0, 300);
                    }
                    return ['success' => false, 'message' => $error_msg];
                }
            } else {
                // No valid API response
                $error_msg = 'No valid response from PowerDNS Admin API';
                if (isset($api_result['raw_response'])) {
                    $error_msg .= ': ' . substr($api_result['raw_response'], 0, 200);
                }
                return ['success' => false, 'message' => $error_msg];
            }
            
        } catch (Exception $e) {
            error_log("Exception in createDomainFromTemplate: " . $e->getMessage());
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
                // Fix record name resolution - ensure canonical names with trailing dots
                $record_name = $record['name'];
                
                if ($record_name === '@' || $record_name === $domain_name || $record_name === rtrim($domain_name, '.')) {
                    // @ or domain name should become the canonical domain name
                    $final_name = $domain_name; // domain_name already has trailing dot
                } elseif (strpos($record_name, '.') === false) {
                    // If record name has no dots, it's a subdomain - append domain and ensure trailing dot
                    $final_name = $record_name . '.' . rtrim($domain_name, '.') . '.';
                } elseif (!str_ends_with($record_name, '.')) {
                    // Record name already contains dots but no trailing dot - add it
                    $final_name = $record_name . '.';
                } else {
                    // Record name already has trailing dot
                    $final_name = $record_name;
                }
                
                $grouped_records[$key] = [
                    'name' => $final_name,
                    'type' => $record['type'],
                    'ttl' => (int)$record['ttl'],
                    'records' => []
                ];
            }
            
            // Format record content based on type
            $content = $this->formatRecordContent($record['content'], $record['type']);
            
            // Prepare record content with proper formatting
            $record_content = [
                'content' => $content,
                'disabled' => $record['disabled'] ?? false
            ];
            
            // Add set-ptr for PTR records if applicable
            if ($record['type'] === 'PTR') {
                $record_content['set-ptr'] = false; // Usually false for manual PTR records
            }
            
            $grouped_records[$key]['records'][] = $record_content;
        }
        
        // Convert to rrsets format with validation
        foreach ($grouped_records as $group) {
            // Skip invalid record types or empty content
            if (empty($group['type']) || empty($group['records'])) {
                error_log("Skipping invalid record group: " . json_encode($group));
                continue;
            }
            
            // Validate TTL
            $ttl = max(1, $group['ttl']); // Minimum TTL of 1 second
            
            $rrset = [
                'name' => $group['name'],
                'type' => $group['type'],
                'ttl' => $ttl,
                'changetype' => 'REPLACE',
                'records' => $group['records']
            ];
            
            // Add comments if available (PowerDNS supports comments in rrsets)
            if (isset($group['comments']) && !empty($group['comments'])) {
                $rrset['comments'] = $group['comments'];
            }
            
            $rrsets[] = $rrset;
        }
        
        // Sort rrsets for consistent ordering (SOA first, then NS, then others)
        usort($rrsets, function($a, $b) {
            $type_priority = [
                'SOA' => 1,
                'NS' => 2,
                'MX' => 3,
                'A' => 4,
                'AAAA' => 5,
                'CNAME' => 6,
                'TXT' => 7
            ];
            
            $priority_a = $type_priority[$a['type']] ?? 99;
            $priority_b = $type_priority[$b['type']] ?? 99;
            
            if ($priority_a === $priority_b) {
                return strcmp($a['name'], $b['name']);
            }
            
            return $priority_a - $priority_b;
        });
        
        error_log("Prepared " . count($rrsets) . " rrsets for PowerDNS API: " . json_encode($rrsets, JSON_PRETTY_PRINT));
        
        return $rrsets;
    }

    /**
     * Format record content based on record type for PowerDNS API compatibility
     */
    private function formatRecordContent($content, $type) {
        switch (strtoupper($type)) {
            case 'MX':
                // MX records should be in format: "priority hostname"
                // If content doesn't start with a number, assume priority 10
                if (!preg_match('/^\d+\s+/', $content)) {
                    $content = '10 ' . $content;
                }
                // Ensure hostname has trailing dot if it's not already canonical
                $parts = explode(' ', $content, 2);
                if (count($parts) === 2 && !str_ends_with($parts[1], '.') && strpos($parts[1], '.') !== false) {
                    $content = $parts[0] . ' ' . $parts[1] . '.';
                }
                break;
                
            case 'CNAME':
            case 'NS':
            case 'PTR':
                // These record types should have canonical hostnames (with trailing dot)
                if (!str_ends_with($content, '.') && strpos($content, '.') !== false) {
                    $content .= '.';
                }
                break;
                
            case 'TXT':
                // TXT records should be properly quoted
                if (!str_starts_with($content, '"') || !str_ends_with($content, '"')) {
                    $content = '"' . addslashes($content) . '"';
                }
                break;
                
            case 'SOA':
                // SOA records need proper formatting: "primary email serial refresh retry expire default_ttl"
                // Ensure all parts have trailing dots where needed
                $soa_parts = explode(' ', $content);
                if (count($soa_parts) >= 2) {
                    // Primary server (position 0)
                    if (!str_ends_with($soa_parts[0], '.') && strpos($soa_parts[0], '.') !== false) {
                        $soa_parts[0] .= '.';
                    }
                    // Email (position 1) 
                    if (!str_ends_with($soa_parts[1], '.') && strpos($soa_parts[1], '.') !== false) {
                        $soa_parts[1] .= '.';
                    }
                    $content = implode(' ', $soa_parts);
                }
                break;
        }
        
        return $content;
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
