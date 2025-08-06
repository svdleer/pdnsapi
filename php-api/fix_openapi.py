#!/usr/bin/env python3
import yaml
import json
import sys

def fix_openapi_conversion():
    try:
        print("Reading OpenAPI YAML...")
        with open('openapi.yaml', 'r', encoding='utf-8') as yaml_file:
            data = yaml.safe_load(yaml_file)
        
        print("✅ YAML loaded successfully")
        
        # Ensure all schema references are complete
        if 'components' in data and 'schemas' in data['components']:
            schemas = data['components']['schemas']
            
            # Fix any missing type definitions in arrays
            for schema_name, schema_def in schemas.items():
                if isinstance(schema_def, dict) and 'properties' in schema_def:
                    for prop_name, prop_def in schema_def['properties'].items():
                        if isinstance(prop_def, dict) and prop_def.get('type') == 'array':
                            if 'items' in prop_def and isinstance(prop_def['items'], dict):
                                if 'type' not in prop_def['items'] and '$ref' not in prop_def['items']:
                                    print(f"⚠️  Fixing missing type in {schema_name}.{prop_name}.items")
                                    prop_def['items']['type'] = 'string'
            
            print(f"✅ Validated {len(schemas)} schemas")
        
        # Write JSON with proper formatting
        with open('openapi.json', 'w', encoding='utf-8') as json_file:
            json.dump(data, json_file, indent=2, separators=(',', ': '), 
                     ensure_ascii=False, sort_keys=False)
        
        print("✅ JSON written successfully")
        
        # Validate the JSON
        with open('openapi.json', 'r', encoding='utf-8') as json_file:
            verify_data = json.load(json_file)
        
        print("✅ JSON validation successful")
        print(f"✅ OpenAPI version: {verify_data.get('openapi', 'unknown')}")
        print(f"✅ API title: {verify_data.get('info', {}).get('title', 'unknown')}")
        
        # Check specific schemas
        if 'components' in verify_data and 'schemas' in verify_data['components']:
            schemas = verify_data['components']['schemas']
            print(f"✅ Found {len(schemas)} schemas")
            
            for schema_name in ['Account', 'AccountCreate', 'AccountUpdate', 'Domain']:
                if schema_name in schemas:
                    print(f"✅ Schema '{schema_name}' is present")
                else:
                    print(f"❌ Schema '{schema_name}' is missing")
        
        return True
        
    except Exception as e:
        print(f"❌ Error: {e}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == '__main__':
    success = fix_openapi_conversion()
    sys.exit(0 if success else 1)
