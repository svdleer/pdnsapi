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
            'tag_documentation' => 'Documentation',
            'tag_ip_allowlist' => 'IP Allowlist',
            
            // Tag descriptions
            'tag_system_description' => 'System status and health checks',
            'tag_accounts_description' => 'Account management operations',
            'tag_domains_description' => 'Domain management operations',
            'tag_assignments_description' => 'Domain-account assignment operations',
            'tag_templates_description' => 'Template management operations',
            'tag_documentation_description' => 'API documentation and help',
            'tag_ip_allowlist_description' => 'IP address allowlist management',
            
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
            'domains_delete_summary' => 'Delete Domain',
            'domains_delete_description' => 'Permanently delete a domain (irreversible)',
            
            // Endpoints - Templates
            'templates_list_summary' => 'List Templates',
            'templates_list_description' => 'Retrieve a list of all DNS templates',
            'templates_create_summary' => 'Create Template',
            'templates_create_description' => 'Create a new DNS template',
            'templates_get_summary' => 'Get Template',
            'templates_get_description' => 'Retrieve a specific DNS template by ID',
            'templates_update_summary' => 'Update Template',
            'templates_update_description' => 'Update an existing DNS template',
            'templates_delete_summary' => 'Delete Template',
            'templates_delete_description' => 'Permanently delete a DNS template',
            'templates_create_domain_summary' => 'Create Domain from Template',
            'templates_create_domain_description' => 'Create a new domain based on a DNS template',
            
            // Endpoints - IP Allowlist
            'ip_allowlist_list_summary' => 'List IP Allowlist',
            'ip_allowlist_list_description' => 'Retrieve all allowed IP addresses and ranges',
            'ip_allowlist_create_summary' => 'Add IP Address',
            'ip_allowlist_create_description' => 'Add a new IP address or range to the allowlist',
            'ip_allowlist_get_summary' => 'Get IP Address',
            'ip_allowlist_get_description' => 'Retrieve a specific IP address from the allowlist',
            'ip_allowlist_update_summary' => 'Update IP Address',
            'ip_allowlist_update_description' => 'Update an existing IP address in the allowlist',
            'ip_allowlist_delete_summary' => 'Delete IP Address',
            'ip_allowlist_delete_description' => 'Permanently remove an IP address from the allowlist',
            'ip_allowlist_test_summary' => 'Test IP Access',
            'ip_allowlist_test_description' => 'Test if an IP address has access to the API',
            
            // Documentation endpoint
            'documentation_summary' => 'API Documentation',
            'documentation_description' => 'Returns API documentation and available endpoints',
            
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
            
            // Schema Fields - Template
            'template_id' => 'Unique template identifier',
            'template_name' => 'Template name',
            'template_description' => 'Template description',
            'template_records' => 'DNS records in the template',
            
            // Schema Fields - IP Allowlist
            'ip_id' => 'Unique IP allowlist entry identifier',
            'ip_address' => 'IP address or CIDR range',
            'ip_description' => 'Description of the IP entry',
            'ip_enabled' => 'Whether the IP entry is active',
            
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
            'api_description' => 'PHP API wrapper voor PowerDNS Admin met lokale database opslag.

## Authenticatie
Gebruikt Basic Authentication met base64 gecodeerde credentials.
Neem `Authorization: Basic <base64-credentials>` header op in verzoeken.

**Admin API Key Vereist** - Enkele API key biedt volledige administratieve toegang.
Alle API keys en configuratie worden geladen vanuit omgevingsvariabelen (.env).

## Omgeving Configuratie
Alle gevoelige configuratie (API keys, database credentials, hosts) wordt opgeslagen in `.env`:
- `AVANT_API_KEY` - Admin API key voor authenticatie
- `PDNS_API_KEY` - PowerDNS Admin API key
- `PDNS_SERVER_KEY` - PowerDNS Server API key
- `API_DB_HOST`, `API_DB_NAME`, `API_DB_USER`, `API_DB_PASS` - Lokale bedrijfslogica database
- `PDNS_ADMIN_DB_HOST`, `PDNS_ADMIN_DB_NAME`, `PDNS_ADMIN_DB_USER`, `PDNS_ADMIN_DB_PASS` - PowerDNS Admin database
- IP allowlist en andere beveiligingsinstellingen

## Kernfuncties
- Account en gebruikersbeheer met PowerDNS Admin synchronisatie
- Domeinbeheer met intelligente ID/naam detectie
- DNS record beheer via PowerDNS Server API
- Template-gebaseerd domein aanmaken
- Lokale database caching en metadata opslag
- Veilige omgeving-gebaseerde configuratie (geen hardgecodeerde credentials)',
            'server_description' => 'Productie API Server',
            
            // Tags
            'tag_system' => 'Systeem',
            'tag_accounts' => 'Accounts',
            'tag_domains' => 'Domeinen',
            'tag_assignments' => 'Domein Toewijzingen',
            'tag_templates' => 'Sjablonen',
            'tag_documentation' => 'Documentatie',
            'tag_ip_allowlist' => 'IP Allowlist',
            
            // Tag descriptions
            'tag_system_description' => 'Systeem status en gezondheidscontroles',
            'tag_accounts_description' => 'Account beheer operaties',
            'tag_domains_description' => 'Domein beheer operaties',
            'tag_assignments_description' => 'Domein-account toewijzing operaties',
            'tag_templates_description' => 'Sjabloon beheer operaties',
            'tag_documentation_description' => 'API documentatie en hulp',
            'tag_ip_allowlist_description' => 'IP adres allowlist beheer',
            
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
            'domains_delete_summary' => 'Domein Verwijderen',
            'domains_delete_description' => 'Een domein permanent verwijderen (onomkeerbaar)',
            
            // Endpoints - Templates
            'templates_list_summary' => 'Sjablonen Weergeven',
            'templates_list_description' => 'Een lijst van alle DNS sjablonen ophalen',
            'templates_create_summary' => 'Sjabloon Aanmaken',
            'templates_create_description' => 'Een nieuwe DNS sjabloon aanmaken',
            'templates_get_summary' => 'Sjabloon Ophalen',
            'templates_get_description' => 'Een specifieke DNS sjabloon ophalen op ID',
            'templates_update_summary' => 'Sjabloon Bijwerken',
            'templates_update_description' => 'Een bestaande DNS sjabloon bijwerken',
            'templates_delete_summary' => 'Sjabloon Verwijderen',
            'templates_delete_description' => 'Een DNS sjabloon permanent verwijderen',
            'templates_create_domain_summary' => 'Domein van Sjabloon Aanmaken',
            'templates_create_domain_description' => 'Een nieuw domein aanmaken gebaseerd op een DNS sjabloon',
            
            // Endpoints - IP Allowlist
            'ip_allowlist_list_summary' => 'IP Allowlist Weergeven',
            'ip_allowlist_list_description' => 'Alle toegestane IP adressen en bereiken weergeven',
            'ip_allowlist_create_summary' => 'IP Adres Toevoegen',
            'ip_allowlist_create_description' => 'Een nieuw IP adres of bereik toevoegen aan de allowlist',
            'ip_allowlist_get_summary' => 'IP Adres Ophalen',
            'ip_allowlist_get_description' => 'Een specifiek IP adres uit de allowlist ophalen',
            'ip_allowlist_update_summary' => 'IP Adres Bijwerken',
            'ip_allowlist_update_description' => 'Een bestaand IP adres in de allowlist bijwerken',
            'ip_allowlist_delete_summary' => 'IP Adres Verwijderen',
            'ip_allowlist_delete_description' => 'Een IP adres permanent uit de allowlist verwijderen',
            'ip_allowlist_test_summary' => 'IP Toegang Testen',
            'ip_allowlist_test_description' => 'Testen of een IP adres toegang heeft tot de API',
            
            // Documentation endpoint
            'documentation_summary' => 'API Documentatie',
            'documentation_description' => 'API documentatie en beschikbare endpoints weergeven',
            
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
            
            // Schema Fields - Template
            'template_id' => 'Unieke sjabloon identificatie',
            'template_name' => 'Sjabloon naam',
            'template_description' => 'Sjabloon beschrijving',
            'template_records' => 'DNS records in de sjabloon',
            
            // Schema Fields - IP Allowlist
            'ip_id' => 'Unieke IP allowlist entry identificatie',
            'ip_address' => 'IP adres of CIDR bereik',
            'ip_description' => 'Beschrijving van het IP entry',
            'ip_enabled' => 'Of het IP entry actief is',
            
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
            'api_key_description' => 'API sleutel voor authenticatie. Neem op in X-API-Key header.',
            
            // Key Features & Important Notes - Dutch
            'authentication_note' => '**ADMIN API KEY + GLOBALE IP ALLOWLIST AUTHENTICATIE**

**DUBBELE AUTHENTICATIE - EENVOUDIG & VEILIG:**
1. **Geldige Admin API Key** - Enkele sleutel voor alle administratieve toegang
2. **Globale IP Allowlist** - Zelfde allowlist geldt uniform voor ALLE endpoints

**📋 AUTHENTICATIE VEREISTEN:**
- **Swagger UI toegang** - Geen authenticatie vereist (voor eenvoudige ontwikkeling)
- **OpenAPI documentatie** - Geen authenticatie vereist (voor eenvoudige toegang)
- **ALLE API endpoints** - Volledige authenticatie vereist (API key + IP allowlist)
- **Health check** - Geen authenticatie vereist (monitoring)',

            'security_warnings' => '**🛡️ GLOBALE IP ALLOWLIST - DATABASE-GEDREVEN & DYNAMISCH:**
- Enkele allowlist geldt uniform voor ALLE API endpoints
- **Opgeslagen in MySQL database** voor persistentie en eenvoudig beheer
- Geen per-endpoint complexiteit - ofwel bent u toegestaan of niet
- Zowel IPv4 als IPv6 adressen ondersteund met CIDR notatie
- **Schakel IPs in/uit** zonder verwijdering voor tijdelijke toegangscontrole
- Eenvoudig te beheren via CLI tool en auditeerbaar via database logs',

            'core_features_title' => 'Kernfuncties',
            'core_features' => '- Account en gebruikersbeheer met PowerDNS Admin synchronisatie
- Domeinbeheer met intelligente ID/naam detectie  
- DNS record beheer via PowerDNS Server API
- Template-gebaseerd domein aanmaken
- Lokale database caching en metadata opslag
- Veilige omgeving-gebaseerde configuratie (geen hardgecodeerde credentials)',

            'smart_query_note' => '**Slimme Query met AND Filters:**
- `q` parameter: Auto-detecteert zoektype (ID/naam/patroon/bevat)
- Extra filters: Gecombineerd als AND voorwaarden
- `?q=voorbeeld.com&account_id=5` vindt "voorbeeld.com" EN behoort tot account 5',

            'security_impact_title' => '🛡️ BEVEILIGINGSIMPACT:',
            'security_requirements_title' => '🔐 BEVEILIGINGSVEREISTEN:',
            'critical_warnings_title' => '⚠️ KRITIEKE BEVEILIGINGSWAARSCHUWINGEN:',
            'destructive_operation_title' => '🚨 DESTRUCTIEVE OPERATIE',
            
            'domain_ownership_warning' => '- **Domein Eigendom:** Alleen domeinen eigendom van geauthenticeerd account kunnen worden gewijzigd',
            'dns_validation_warning' => '- **DNS Record Validatie:** Alle DNS records worden gevalideerd voor toepassing',
            'account_transfer_warning' => '- **Account Overdracht:** Wijzigen van account_id vereist eigendom verificatie',
            'zone_type_warning' => '- **Zone Type Wijzigingen:** Wijzigen van kind/masters beïnvloedt DNS resolutie - gebruik voorzichtig',
            
            'irreversible_warning' => '- **Onomkeerbare Actie:** Domein verwijdering kan niet ongedaan gemaakt worden',
            'ownership_verification_warning' => '- **Eigendom Verificatie:** Alleen domeinen eigendom van geauthenticeerd account kunnen worden verwijderd',
            'dns_resolution_warning' => '- **DNS Resolutie Impact:** Domein zal onmiddellijk stoppen met resolven',
            'cascade_effects_warning' => '- **Cascade Effecten:** Alle DNS records zullen permanent verloren gaan',
            
            'validation_enforced_title' => '🛡️ VALIDATIE AFGEDWONGEN:',
            'safety_measures_title' => '🛡️ VEILIGHEIDSMAATREGELEN:',
            
            'dns_content_validation' => '- DNS record inhoud gevalideerd (A/AAAA/CNAME/MX formaten)',
            'master_ip_validation' => '- Master server IPs gevalideerd voor Slave zones',
            'ownership_verified' => '- Account eigendom geverifieerd voor overdrachten',
            'dnssec_validation' => '- DNSSEC wijzigingen vereisen extra validatie',
            
            'audit_logging' => '- Audit logging met administrator details',
            'automatic_sync' => '- PowerDNS Admin en lokale database automatisch gesynchroniseerd',
            'domain_ownership_verified' => '- Domein eigendom geverifieerd voor verwijdering',
            
            'ip_security_note' => '- Verleent API toegang aan gespecificeerd IP/bereik
- Wordt onmiddellijk van kracht
- Alle nieuwe IPs zijn standaard ingeschakeld
- Ondersteunt zowel IPv4 als IPv6 met CIDR notatie',

            'permanent_removal_warning' => '**⚠️ BEVEILIGINGSWAARSCHUWING:**
- Permanent verwijdert IP toegang
- Kan niet ongedaan gemaakt worden zonder opnieuw toevoegen
- Kan onmiddellijk actieve verbindingen van dit IP blokkeren
- Gebruik uitschakelen in plaats hiervan voor tijdelijke toegang verwijdering',

            'testing_debugging_title' => '🔍 TESTEN & DEBUGGEN:',
            'testing_features' => '- Valideer IP toegang voordat wijzigingen gemaakt worden
- Test CIDR bereik matching
- Verifieer allowlist configuratie
- Nuttig voor het oplossen van toegangsproblemen',

            'cli_management_title' => '**Dynamisch Beheer via CLI:**',
            'cli_examples' => '```bash
# Alle toegestane IPs weergeven
php manage-ips-clean.php list

# IPs toevoegen met beschrijvingen
php manage-ips-clean.php add "203.0.113.25" "Admin thuis IP"
php manage-ips-clean.php add "192.168.1.0/24" "Kantoor netwerk"
php manage-ips-clean.php add "2001:db8::/32" "IPv6 netwerk"

# Tijdelijk IPs uitschakelen/inschakelen
php manage-ips-clean.php disable "203.0.113.25"
php manage-ips-clean.php enable "203.0.113.25"

# IPs permanent verwijderen
php manage-ips-clean.php remove "203.0.113.25"

# IP toegang testen
php manage-ips-clean.php test "192.168.1.100"
```',

            'database_storage_title' => '**Database Opslag:**',
            'database_example' => '```sql
-- IPs opgeslagen in ip_allowlist tabel
SELECT ip_address, description, enabled 
FROM ip_allowlist 
WHERE enabled = 1;
```',

            'security_benefits_title' => '**Beveiligingsvoordelen:**',
            'security_benefits' => '- **Defense in depth:** Zowel API key ALS IP moet geldig zijn
- **Nul complexiteit:** Zelfde regels gelden overal
- **Dynamisch beheer:** IPs toevoegen/verwijderen zonder herstart
- **Database persistentie:** Wijzigingen overleven server herstarts
- **Tijdelijke controle:** Inschakelen/uitschakelen zonder verwijdering
- **Volledige bescherming:** API ontoegankelijk vanaf niet-toegestane IPs
- **Prestaties:** In-memory caching met database fallback',

            'security_notes_title' => '**Beveiligingsnotities:**',
            'security_notes' => '- **Dubbele authenticatie:** API key EN IP allowlist vereist
- Een API key voor alle administratieve operaties
- Volledige toegang tot accounts, domeinen, templates, en DNS records
- **Database-gedreven allowlist** voorkomt ongeautoriseerde toegang zelfs met geldige key
- Moet regelmatig gerouleerd worden en veilig gehouden worden
- **Update allowlist dynamisch** via CLI tool wanneer netwerk verandert
- **Audit trail:** Alle IP wijzigingen bijgehouden in database met timestamps'
        ]
    ];
    
    // Return translations for requested language, fallback to English
    return $translations[$lang] ?? $translations['en'];
}
?>
