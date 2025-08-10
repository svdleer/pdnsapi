<?php
/**
 * Translation system for OpenAPI documentation
 * Supports English (en) and Dutch (nl) translations
 */

function getTranslations($lang = 'en') {
    $translations = [
        'en' => [
            // API Info
            'api_title' => 'PDNSAdmin PHP API',
            'api_description' => 'Comprehensive wrapper for PowerDNS Admin with local database storage and extended functionality for managing DNS accounts and domains.',
            'server_description' => 'Production API Server',
            
            // Tags
            'tag_system' => 'System',
            'tag_accounts' => 'Accounts',
            'tag_domains' => 'Domains',
            'tag_assignments' => 'Domain Assignments',
            'tag_templates' => 'Templates',
            
            // Tag descriptions
            'tag_system_description' => 'System status and health checks',
            'tag_accounts_description' => 'Account management operations',
            'tag_domains_description' => 'Domain management operations',
            'tag_assignments_description' => 'Domain-account assignment operations',
            'tag_templates_description' => 'Template management operations',
            
            // Endpoints - Status
            'status_summary' => 'Get API Status',
            'status_description' => 'Check the API and database connection status',
            'status_field' => 'API status indicator',
            'database_field' => 'Database connection status',
            'timestamp_field' => 'Current server timestamp',
            
            // Endpoints - Accounts
            'accounts_list_summary' => 'List Accounts',
            'accounts_list_description' => 'Retrieve a paginated list of all accounts',
            'accounts_create_summary' => 'Create Account',
            'accounts_create_description' => 'Create a new account with optional IP address restrictions',
            'accounts_get_summary' => 'Get Account',
            'accounts_get_description' => 'Retrieve a specific account by ID',
            'accounts_update_summary' => 'Update Account',
            'accounts_update_description' => 'Update an existing account',
            'accounts_delete_summary' => 'Delete Account',
            'accounts_delete_description' => 'Delete an account and all its domain assignments',
            
            // Endpoints - Domains
            'domains_list_summary' => 'List Domains',
            'domains_list_description' => 'Retrieve a list of all domains',
            'domains_create_summary' => 'Create Domain',
            'domains_create_description' => 'Create a new domain and sync with PDNSAdmin',
            'domains_get_summary' => 'Get Domain',
            'domains_get_description' => 'Retrieve a specific domain by ID',
            'domains_update_summary' => 'Update Domain',
            'domains_update_description' => 'Update an existing domain',
            
            // Endpoints - Domain Assignments
            'assignments_list_summary' => 'List Domain Assignments',
            'assignments_list_description' => 'Retrieve all domain-account assignments',
            'assignments_create_summary' => 'Create Domain Assignment',
            'assignments_create_description' => 'Assign a domain to an account',
            'assignments_delete_summary' => 'Delete Domain Assignment',
            'assignments_delete_description' => 'Remove a domain-account assignment',
            
            // Schema Fields - Account
            'account_id' => 'Unique account identifier',
            'account_name' => 'Account name',
            'account_description' => 'Account description',
            'account_contact' => 'Contact person for this account',
            'account_email' => 'Contact email address',
            'account_ips' => 'Comma-separated list of allowed IP addresses (local storage only)',
            
            // Schema Fields - Domain
            'domain_id' => 'Unique domain identifier',
            'domain_name' => 'Domain name (FQDN)',
            'domain_type' => 'Domain type (Native, Master, Slave)',
            'domain_account_id' => 'Associated account ID',
            
            // Schema Fields - Assignment
            'assignment_id' => 'Unique assignment identifier',
            'assignment_domain_id' => 'Domain ID for this assignment',
            'assignment_account_id' => 'Account ID for this assignment',
            
            // Common Fields
            'created_at' => 'Creation timestamp',
            'updated_at' => 'Last update timestamp',
            
            // Parameters
            'page_param' => 'Page number for pagination',
            'limit_param' => 'Number of items per page',
            
            // Pagination
            'current_page' => 'Current page number',
            'per_page' => 'Items per page',
            'total_items' => 'Total number of items',
            'total_pages' => 'Total number of pages',
            
            // Responses
            'response_success' => 'Success',
            'response_created' => 'Created successfully',
            'response_updated' => 'Updated successfully',
            'response_deleted' => 'Deleted successfully',
            'response_not_found' => 'Resource not found',
            'response_bad_request' => 'Bad request',
            'response_unauthorized' => 'Unauthorized',
            'response_forbidden' => 'Forbidden',
            'response_conflict' => 'Conflict',
            'response_server_error' => 'Internal server error',
            
            // Security
            'api_key_description' => 'API key for authentication. Include in X-API-Key header.'
        ],
        
        'nl' => [
            // API Info
            'api_title' => 'PDNSAdmin PHP API',
            'api_description' => 'Uitgebreide wrapper voor PowerDNS Admin met lokale database opslag en uitgebreide functionaliteit voor het beheren van DNS accounts en domeinen.',
            'server_description' => 'Productie API Server',
            
            // Tags
            'tag_system' => 'Systeem',
            'tag_accounts' => 'Accounts',
            'tag_domains' => 'Domeinen',
            'tag_assignments' => 'Domein Toewijzingen',
            'tag_templates' => 'Sjablonen',
            
            // Tag descriptions
            'tag_system_description' => 'Systeem status en gezondheidscontroles',
            'tag_accounts_description' => 'Account beheer operaties',
            'tag_domains_description' => 'Domein beheer operaties',
            'tag_assignments_description' => 'Domein-account toewijzing operaties',
            'tag_templates_description' => 'Sjabloon beheer operaties',
            
            // Endpoints - Status
            'status_summary' => 'API Status Ophalen',
            'status_description' => 'Controleer de API en database verbinding status',
            'status_field' => 'API status indicator',
            'database_field' => 'Database verbinding status',
            'timestamp_field' => 'Huidige server tijdstempel',
            
            // Endpoints - Accounts
            'accounts_list_summary' => 'Accounts Weergeven',
            'accounts_list_description' => 'Een gepagineerde lijst van alle accounts ophalen',
            'accounts_create_summary' => 'Account Aanmaken',
            'accounts_create_description' => 'Een nieuw account aanmaken met optionele IP adres beperkingen',
            'accounts_get_summary' => 'Account Ophalen',
            'accounts_get_description' => 'Een specifiek account ophalen op ID',
            'accounts_update_summary' => 'Account Bijwerken',
            'accounts_update_description' => 'Een bestaand account bijwerken',
            'accounts_delete_summary' => 'Account Verwijderen',
            'accounts_delete_description' => 'Een account en alle domein toewijzingen verwijderen',
            
            // Endpoints - Domains
            'domains_list_summary' => 'Domeinen Weergeven',
            'domains_list_description' => 'Een lijst van alle domeinen ophalen',
            'domains_create_summary' => 'Domein Aanmaken',
            'domains_create_description' => 'Een nieuw domein aanmaken en synchroniseren met PDNSAdmin',
            'domains_get_summary' => 'Domein Ophalen',
            'domains_get_description' => 'Een specifiek domein ophalen op ID',
            'domains_update_summary' => 'Domein Bijwerken',
            'domains_update_description' => 'Een bestaand domein bijwerken',
            
            // Endpoints - Domain Assignments
            'assignments_list_summary' => 'Domein Toewijzingen Weergeven',
            'assignments_list_description' => 'Alle domein-account toewijzingen ophalen',
            'assignments_create_summary' => 'Domein Toewijzing Aanmaken',
            'assignments_create_description' => 'Een domein toewijzen aan een account',
            'assignments_delete_summary' => 'Domein Toewijzing Verwijderen',
            'assignments_delete_description' => 'Een domein-account toewijzing verwijderen',
            
            // Schema Fields - Account
            'account_id' => 'Unieke account identificatie',
            'account_name' => 'Account naam',
            'account_description' => 'Account beschrijving',
            'account_contact' => 'Contactpersoon voor dit account',
            'account_email' => 'Contact email adres',
            'account_ips' => 'Komma-gescheiden lijst van toegestane IP adressen (alleen lokale opslag)',
            
            // Schema Fields - Domain
            'domain_id' => 'Unieke domein identificatie',
            'domain_name' => 'Domein naam (FQDN)',
            'domain_type' => 'Domein type (Native, Master, Slave)',
            'domain_account_id' => 'Gekoppeld account ID',
            
            // Schema Fields - Assignment
            'assignment_id' => 'Unieke toewijzing identificatie',
            'assignment_domain_id' => 'Domein ID voor deze toewijzing',
            'assignment_account_id' => 'Account ID voor deze toewijzing',
            
            // Common Fields
            'created_at' => 'Aanmaak tijdstempel',
            'updated_at' => 'Laatste update tijdstempel',
            
            // Parameters
            'page_param' => 'Pagina nummer voor paginering',
            'limit_param' => 'Aantal items per pagina',
            
            // Pagination
            'current_page' => 'Huidige pagina nummer',
            'per_page' => 'Items per pagina',
            'total_items' => 'Totaal aantal items',
            'total_pages' => 'Totaal aantal pagina\'s',
            
            // Responses
            'response_success' => 'Succesvol',
            'response_created' => 'Succesvol aangemaakt',
            'response_updated' => 'Succesvol bijgewerkt',
            'response_deleted' => 'Succesvol verwijderd',
            'response_not_found' => 'Resource niet gevonden',
            'response_bad_request' => 'Ongeldig verzoek',
            'response_unauthorized' => 'Niet geautoriseerd',
            'response_forbidden' => 'Verboden',
            'response_conflict' => 'Conflict',
            'response_server_error' => 'Interne server fout',
            
            // Security
            'api_key_description' => 'API sleutel voor authenticatie. Neem op in X-API-Key header.'
        ]
    ];
    
    // Return translations for requested language, fallback to English
    return $translations[$lang] ?? $translations['en'];
}
?>
