<?php
/**
 * Dynamic OpenAPI/Swagger Generator with i18n Support
 * Generates localized OpenAPI YAML for the PDNSAdmin API
 * 
 * Usage: openapi-i18n.php?lang=nl (defaults to 'en')
 */

require_once 'includes/autoloader.php';

// Get language parameter, default to English
$lang = $_GET['lang'] ?? 'en';

// Load translations
$translations = require 'translations.php';

// Validate language
if (!isset($translations[$lang])) {
    $lang = 'en'; // fallback to English
}

$t = $translations[$lang];

// Function to get translation
function trans($key, $lang_array) {
    return $lang_array[$key] ?? $key;
}

// Set appropriate headers
header('Content-Type: application/x-yaml; charset=utf-8');
header('Content-Disposition: inline; filename="openapi-' . $lang . '.yaml"');

// Generate the OpenAPI YAML
echo "openapi: 3.0.3
info:
  title: " . trans('api.title', $t) . "
  description: |
    " . str_replace("\n", "\n    ", trans('api.description', $t)) . "
  version: \"1.0.0\"
  contact:
    name: API Support
    email: admin@example.com
  license:
    name: MIT
    url: https://opensource.org/licenses/MIT

servers:
  - url: https://api.example.com
    description: Production server
  - url: http://localhost/api
    description: Development server

paths:
  /accounts:
    get:
      summary: " . trans('accounts.list.summary', $t) . "
      description: " . trans('accounts.list.description', $t) . "
      tags:
        - Accounts
      security:
        - ApiKeyAuth: []
      responses:
        '200':
          description: " . trans('response.success', $t) . "
          content:
            application/json:
              schema:
                type: array
                items:
                  \$ref: '#/components/schemas/Account'
        '401':
          \$ref: '#/components/responses/Unauthorized'
        '403':
          \$ref: '#/components/responses/Forbidden'
        '500':
          \$ref: '#/components/responses/InternalError'
          
    post:
      summary: " . trans('accounts.create.summary', $t) . "
      description: " . trans('accounts.create.description', $t) . "
      tags:
        - Accounts
      security:
        - ApiKeyAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              \$ref: '#/components/schemas/AccountCreate'
      responses:
        '201':
          description: " . trans('response.created', $t) . "
          content:
            application/json:
              schema:
                \$ref: '#/components/schemas/Account'
        '400':
          \$ref: '#/components/responses/BadRequest'
        '401':
          \$ref: '#/components/responses/Unauthorized'
        '403':
          \$ref: '#/components/responses/Forbidden'
        '500':
          \$ref: '#/components/responses/InternalError'

  /accounts/{id}:
    get:
      summary: " . trans('accounts.get.summary', $t) . "
      description: " . trans('accounts.get.description', $t) . "
      tags:
        - Accounts
      security:
        - ApiKeyAuth: []
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
          description: Account ID
      responses:
        '200':
          description: " . trans('response.success', $t) . "
          content:
            application/json:
              schema:
                \$ref: '#/components/schemas/Account'
        '404':
          \$ref: '#/components/responses/NotFound'
        '401':
          \$ref: '#/components/responses/Unauthorized'
        '403':
          \$ref: '#/components/responses/Forbidden'
        '500':
          \$ref: '#/components/responses/InternalError'

  /domains:
    get:
      summary: " . trans('domains.list.summary', $t) . "
      description: " . trans('domains.list.description', $t) . "
      tags:
        - Domains
      security:
        - ApiKeyAuth: []
      responses:
        '200':
          description: " . trans('response.success', $t) . "
          content:
            application/json:
              schema:
                type: array
                items:
                  \$ref: '#/components/schemas/Domain'
        '401':
          \$ref: '#/components/responses/Unauthorized'
        '403':
          \$ref: '#/components/responses/Forbidden'
        '500':
          \$ref: '#/components/responses/InternalError'

  /ip-allowlist:
    get:
      summary: " . trans('security.list.summary', $t) . "
      description: " . trans('security.list.description', $t) . "
      tags:
        - Security
      security:
        - ApiKeyAuth: []
      responses:
        '200':
          description: " . trans('response.success', $t) . "
          content:
            application/json:
              schema:
                type: array
                items:
                  \$ref: '#/components/schemas/IPAllowlistEntry'
        '401':
          \$ref: '#/components/responses/Unauthorized'
        '403':
          \$ref: '#/components/responses/Forbidden'
        '500':
          \$ref: '#/components/responses/InternalError'
          
    post:
      summary: " . trans('security.create.summary', $t) . "
      description: " . trans('security.create.description', $t) . "
      tags:
        - Security
      security:
        - ApiKeyAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              \$ref: '#/components/schemas/IPAllowlistCreate'
      responses:
        '201':
          description: " . trans('response.created', $t) . "
          content:
            application/json:
              schema:
                \$ref: '#/components/schemas/IPAllowlistEntry'
        '400':
          \$ref: '#/components/responses/BadRequest'
        '401':
          \$ref: '#/components/responses/Unauthorized'
        '403':
          \$ref: '#/components/responses/Forbidden'
        '500':
          \$ref: '#/components/responses/InternalError'

components:
  securitySchemes:
    ApiKeyAuth:
      type: apiKey
      in: header
      name: X-API-Key
      description: Admin API Key for authentication

  responses:
    Unauthorized:
      description: " . trans('response.unauthorized', $t) . "
      content:
        application/json:
          schema:
            \$ref: '#/components/schemas/Error'
    Forbidden:
      description: " . trans('response.forbidden', $t) . "
      content:
        application/json:
          schema:
            \$ref: '#/components/schemas/Error'
    NotFound:
      description: " . trans('response.notfound', $t) . "
      content:
        application/json:
          schema:
            \$ref: '#/components/schemas/Error'
    BadRequest:
      description: " . trans('response.badrequest', $t) . "
      content:
        application/json:
          schema:
            \$ref: '#/components/schemas/Error'
    InternalError:
      description: " . trans('response.error', $t) . "
      content:
        application/json:
          schema:
            \$ref: '#/components/schemas/Error'

  schemas:
    Account:
      type: object
      properties:
        id:
          type: integer
          example: 1
        name:
          type: string
          example: \"john.doe\"
        description:
          type: string
          example: \"John Doe Account\"
        contact:
          type: string
          example: \"john@example.com\"
        domains:
          type: array
          items:
            type: string
          example: [\"example.com\", \"test.com\"]
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time

    AccountCreate:
      type: object
      required:
        - name
      properties:
        name:
          type: string
          example: \"jane.doe\"
        description:
          type: string
          example: \"Jane Doe Account\"
        contact:
          type: string
          example: \"jane@example.com\"

    Domain:
      type: object
      properties:
        id:
          type: integer
          example: 1
        name:
          type: string
          example: \"example.com\"
        type:
          type: string
          example: \"NATIVE\"
        account:
          type: string
          example: \"john.doe\"
        records:
          type: integer
          example: 5
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time

    IPAllowlistEntry:
      type: object
      properties:
        id:
          type: integer
          example: 1
        ip_address:
          type: string
          example: \"192.168.1.100\"
        description:
          type: string
          example: \"Office workstation\"
        enabled:
          type: boolean
          example: true
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time

    IPAllowlistCreate:
      type: object
      required:
        - ip_address
      properties:
        ip_address:
          type: string
          example: \"192.168.1.100\"
        description:
          type: string
          example: \"Office workstation\"
        enabled:
          type: boolean
          example: true
          default: true

    Error:
      type: object
      properties:
        error:
          type: string
          example: \"Error message\"
        message:
          type: string
          example: \"Detailed error description\"
        code:
          type: integer
          example: 400

security:
  - ApiKeyAuth: []

tags:
  - name: Accounts
    description: " . trans('tag.accounts', $t) . "
  - name: Domains
    description: " . trans('tag.domains', $t) . "
  - name: Templates
    description: " . trans('tag.templates', $t) . "
  - name: Domain-Account
    description: " . trans('tag.domain_account', $t) . "
  - name: Status
    description: " . trans('tag.status', $t) . "
  - name: Security
    description: " . trans('tag.security', $t) . "
  - name: Testing
    description: " . trans('tag.testing', $t) . "
";
?>
