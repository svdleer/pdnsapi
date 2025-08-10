<?php
/**
 * Translation strings for OpenAPI documentation
 * Supports: en (English - default), nl (Dutch)
 */

return [
    'en' => [
        // API Info
        'api.title' => 'PDNSAdmin PHP API',
        'api.description' => 'PHP API wrapper for PowerDNS Admin with local database storage.

## Authentication
Uses X-API-Key header authentication with global IP allowlist.
Include `X-API-Key: <your-api-key>` header in requests.

**Admin API Key Required** - Single API key provides full administrative access.
All API keys and configuration are loaded from environment variables (.env).

## Environment Configuration
All sensitive configuration (API keys, database credentials, hosts) is stored in `.env`:
- `AVANT_API_KEY` - Admin API key for authentication
- `PDNS_API_KEY` - PowerDNS Admin API key
- `PDNS_SERVER_KEY` - PowerDNS Server API key
- `API_DB_HOST`, `API_DB_NAME`, `API_DB_USER`, `API_DB_PASS` - Local business logic database
- `PDNS_ADMIN_DB_HOST`, `PDNS_ADMIN_DB_NAME`, `PDNS_ADMIN_DB_USER`, `PDNS_ADMIN_DB_PASS` - PowerDNS Admin database
- IP allowlist and other security settings

## Features
- Domain management with PowerDNS Admin integration
- User account and domain assignment management
- Template-based domain creation
- IP allowlist security management

## Base URL
All endpoints are relative to: `https://your-domain.com/api/`',

        // Tag Descriptions
        'tag.accounts' => 'Account management operations',
        'tag.domains' => 'Domain management operations',
        'tag.templates' => 'Domain template management operations',
        'tag.domain_account' => 'Domain-account relationship management',
        'tag.status' => 'API status, health checks, and synchronization',
        'tag.security' => 'Security-related operations including IP allowlist management and access control',
        'tag.testing' => 'Testing and validation endpoints',

        // Accounts endpoints
        'accounts.list.summary' => 'List all accounts',
        'accounts.list.description' => 'Retrieve all user accounts with their details and associated domains.',
        'accounts.create.summary' => 'Create a new account',
        'accounts.create.description' => 'Create a new user account in the system.',
        'accounts.get.summary' => 'Get account details',
        'accounts.get.description' => 'Retrieve details for a specific account by ID.',
        'accounts.update.summary' => 'Update an account',
        'accounts.update.description' => 'Update an existing account\'s information.',
        'accounts.delete.summary' => 'Delete an account',
        'accounts.delete.description' => 'Delete an account from the system.',

        // Domains endpoints
        'domains.list.summary' => 'List all domains',
        'domains.list.description' => 'Retrieve all domains with their details and record counts.',
        'domains.create.summary' => 'Create a new domain',
        'domains.create.description' => 'Create a new domain in PowerDNS Admin.',
        'domains.get.summary' => 'Get domain details',
        'domains.get.description' => 'Retrieve details for a specific domain.',
        'domains.update.summary' => 'Update a domain',
        'domains.update.description' => 'Update domain settings and configuration.',
        'domains.delete.summary' => 'Delete a domain',
        'domains.delete.description' => 'Delete a domain from PowerDNS Admin.',

        // Templates endpoints
        'templates.list.summary' => 'List all templates',
        'templates.list.description' => 'Retrieve all domain templates available in the system.',
        'templates.create.summary' => 'Create a new template',
        'templates.create.description' => 'Create a new domain template for reusable domain configuration.',
        'templates.get.summary' => 'Get template details',
        'templates.get.description' => 'Retrieve details for a specific template.',
        'templates.update.summary' => 'Update a template',
        'templates.update.description' => 'Update an existing domain template.',
        'templates.delete.summary' => 'Delete a template',
        'templates.delete.description' => 'Delete a domain template from the system.',

        // Security/IP Allowlist endpoints
        'security.list.summary' => 'List IP allowlist entries',
        'security.list.description' => 'Retrieve all IP addresses in the global allowlist.',
        'security.create.summary' => 'Add IP to allowlist',
        'security.create.description' => 'Add a new IP address or CIDR range to the global allowlist.',
        'security.update.summary' => 'Update IP allowlist entry',
        'security.update.description' => 'Update an existing IP allowlist entry.',
        'security.delete.summary' => 'Remove IP from allowlist',
        'security.delete.description' => 'Remove an IP address from the global allowlist.',
        'security.test.summary' => 'Test IP allowlist access',
        'security.test.description' => 'Test whether a specific IP address would be allowed by the current allowlist.',

        // Status endpoints
        'status.health.summary' => 'Health check',
        'status.health.description' => 'Check the health status of the API and its dependencies.',
        'status.sync.summary' => 'Synchronize data',
        'status.sync.description' => 'Synchronize data between local database and PowerDNS Admin.',

        // Common responses
        'response.success' => 'Operation completed successfully',
        'response.created' => 'Resource created successfully',
        'response.notfound' => 'Resource not found',
        'response.badrequest' => 'Bad request - invalid parameters',
        'response.unauthorized' => 'Unauthorized - invalid or missing API key',
        'response.forbidden' => 'Forbidden - IP not in allowlist or insufficient permissions',
        'response.error' => 'Internal server error',
    ],

    'nl' => [
        // API Info
        'api.title' => 'PDNSAdmin PHP API',
        'api.description' => 'PHP API wrapper voor PowerDNS Admin met lokale database opslag.

## Authenticatie
Gebruikt X-API-Key header authenticatie met globale IP whitelist.
Voeg `X-API-Key: <uw-api-key>` header toe aan verzoeken.

**Admin API Key Vereist** - Enkele API key biedt volledige beheerderstoegang.
Alle API keys en configuratie worden geladen vanuit omgevingsvariabelen (.env).

## Omgeving Configuratie
Alle gevoelige configuratie (API keys, database credentials, hosts) wordt opgeslagen in `.env`:
- `AVANT_API_KEY` - Admin API key voor authenticatie
- `PDNS_API_KEY` - PowerDNS Admin API key
- `PDNS_SERVER_KEY` - PowerDNS Server API key
- `API_DB_HOST`, `API_DB_NAME`, `API_DB_USER`, `API_DB_PASS` - Lokale bedrijfslogica database
- `PDNS_ADMIN_DB_HOST`, `PDNS_ADMIN_DB_NAME`, `PDNS_ADMIN_DB_USER`, `PDNS_ADMIN_DB_PASS` - PowerDNS Admin database
- IP whitelist en andere beveiligingsinstellingen

## Functies
- Domeinbeheer met PowerDNS Admin integratie
- Gebruikersaccount en domeintoewijzing beheer
- Template-gebaseerde domein creatie
- IP whitelist beveiligingsbeheer

## Basis URL
Alle endpoints zijn relatief ten opzichte van: `https://uw-domein.com/api/`',

        // Tag Descriptions
        'tag.accounts' => 'Account beheer operaties',
        'tag.domains' => 'Domein beheer operaties',
        'tag.templates' => 'Domein template beheer operaties',
        'tag.domain_account' => 'Domein-account relatie beheer',
        'tag.status' => 'API status, health checks, en synchronisatie',
        'tag.security' => 'Beveiligingsgerelateerde operaties inclusief IP whitelist beheer en toegangscontrole',
        'tag.testing' => 'Test en validatie endpoints',

        // Accounts endpoints
        'accounts.list.summary' => 'Lijst alle accounts',
        'accounts.list.description' => 'Haal alle gebruikersaccounts op met hun details en geassocieerde domeinen.',
        'accounts.create.summary' => 'Maak een nieuw account',
        'accounts.create.description' => 'Maak een nieuw gebruikersaccount aan in het systeem.',
        'accounts.get.summary' => 'Krijg account details',
        'accounts.get.description' => 'Haal details op voor een specifiek account op basis van ID.',
        'accounts.update.summary' => 'Update een account',
        'accounts.update.description' => 'Update de informatie van een bestaand account.',
        'accounts.delete.summary' => 'Verwijder een account',
        'accounts.delete.description' => 'Verwijder een account uit het systeem.',

        // Domains endpoints
        'domains.list.summary' => 'Lijst alle domeinen',
        'domains.list.description' => 'Haal alle domeinen op met hun details en record aantallen.',
        'domains.create.summary' => 'Maak een nieuw domein',
        'domains.create.description' => 'Maak een nieuw domein aan in PowerDNS Admin.',
        'domains.get.summary' => 'Krijg domein details',
        'domains.get.description' => 'Haal details op voor een specifiek domein.',
        'domains.update.summary' => 'Update een domein',
        'domains.update.description' => 'Update domein instellingen en configuratie.',
        'domains.delete.summary' => 'Verwijder een domein',
        'domains.delete.description' => 'Verwijder een domein uit PowerDNS Admin.',

        // Templates endpoints
        'templates.list.summary' => 'Lijst alle templates',
        'templates.list.description' => 'Haal alle domein templates op die beschikbaar zijn in het systeem.',
        'templates.create.summary' => 'Maak een nieuwe template',
        'templates.create.description' => 'Maak een nieuwe domein template voor herbruikbare domein configuratie.',
        'templates.get.summary' => 'Krijg template details',
        'templates.get.description' => 'Haal details op voor een specifieke template.',
        'templates.update.summary' => 'Update een template',
        'templates.update.description' => 'Update een bestaande domein template.',
        'templates.delete.summary' => 'Verwijder een template',
        'templates.delete.description' => 'Verwijder een domein template uit het systeem.',

        // Security/IP Allowlist endpoints
        'security.list.summary' => 'Lijst IP whitelist items',
        'security.list.description' => 'Haal alle IP adressen op in de globale whitelist.',
        'security.create.summary' => 'Voeg IP toe aan whitelist',
        'security.create.description' => 'Voeg een nieuw IP adres of CIDR bereik toe aan de globale whitelist.',
        'security.update.summary' => 'Update IP whitelist item',
        'security.update.description' => 'Update een bestaand IP whitelist item.',
        'security.delete.summary' => 'Verwijder IP uit whitelist',
        'security.delete.description' => 'Verwijder een IP adres uit de globale whitelist.',
        'security.test.summary' => 'Test IP whitelist toegang',
        'security.test.description' => 'Test of een specifiek IP adres zou worden toegestaan door de huidige whitelist.',

        // Status endpoints
        'status.health.summary' => 'Health check',
        'status.health.description' => 'Controleer de health status van de API en zijn afhankelijkheden.',
        'status.sync.summary' => 'Synchroniseer data',
        'status.sync.description' => 'Synchroniseer data tussen lokale database en PowerDNS Admin.',

        // Common responses
        'response.success' => 'Operatie succesvol voltooid',
        'response.created' => 'Resource succesvol aangemaakt',
        'response.notfound' => 'Resource niet gevonden',
        'response.badrequest' => 'Slecht verzoek - ongeldige parameters',
        'response.unauthorized' => 'Niet geautoriseerd - ongeldige of ontbrekende API key',
        'response.forbidden' => 'Verboden - IP niet in whitelist of onvoldoende rechten',
        'response.error' => 'Interne server fout',
    ]
];
