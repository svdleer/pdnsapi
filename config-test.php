<?php
/**
 * Configuration and Dependency Test for Accounts API
 * Verifies that all required files and dependencies are properly configured
 */

class ConfigurationTester {
    private $base_path;
    private $test_results = [];
    
    public function __construct($base_path = null) {
        $this->base_path = $base_path ?: realpath(__DIR__ . '/php-api');
        echo "=== Configuration and Dependency Test ===\n";
        echo "Base path: {$this->base_path}\n";
        echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    public function runAllTests() {
        $this->testFileStructure();
        $this->testConfigFiles();
        $this->testDatabaseConfig();
        $this->testModelFiles();
        $this->testAPIEndpoints();
        $this->testPermissions();
        
        $this->printSummary();
    }
    
    private function testFileStructure() {
        echo "Testing file structure...\n";
        
        $required_files = [
            'index.php',
            'config/config.php',
            'config/database.php',
            'models/Account.php',
            'classes/PDNSAdminClient.php',
            'api/accounts.php',
            'includes/database-compat.php'
        ];
        
        foreach ($required_files as $file) {
            $file_path = $this->base_path . '/' . $file;
            $exists = file_exists($file_path);
            $readable = $exists ? is_readable($file_path) : false;
            
            if ($exists && $readable) {
                $this->recordTest("File {$file}", true, "File exists and is readable");
            } elseif ($exists) {
                $this->recordTest("File {$file}", false, "File exists but is not readable");
            } else {
                $this->recordTest("File {$file}", false, "File does not exist");
            }
        }
        
        // Test directory structure
        $required_dirs = [
            'config',
            'models',
            'classes',
            'api',
            'includes'
        ];
        
        foreach ($required_dirs as $dir) {
            $dir_path = $this->base_path . '/' . $dir;
            $exists = is_dir($dir_path);
            $readable = $exists ? is_readable($dir_path) : false;
            
            if ($exists && $readable) {
                $this->recordTest("Directory {$dir}", true, "Directory exists and is readable");
            } elseif ($exists) {
                $this->recordTest("Directory {$dir}", false, "Directory exists but is not readable");
            } else {
                $this->recordTest("Directory {$dir}", false, "Directory does not exist");
            }
        }
    }
    
    private function testConfigFiles() {
        echo "Testing configuration files...\n";
        
        // Test config.php
        $config_file = $this->base_path . '/config/config.php';
        if (file_exists($config_file)) {
            ob_start();
            $error_before = error_get_last();
            include $config_file;
            $error_after = error_get_last();
            $output = ob_get_clean();
            
            $has_syntax_error = $error_after && $error_after !== $error_before;
            
            if (!$has_syntax_error) {
                $this->recordTest("Config syntax", true, "config.php has valid PHP syntax");
                
                // Check for required variables
                $required_vars = ['pdns_config', 'api_settings'];
                foreach ($required_vars as $var) {
                    $exists = isset($GLOBALS[$var]) || isset($$var);
                    $this->recordTest("Config variable {$var}", $exists, $exists ? "Variable defined" : "Variable not defined");
                }
                
                // Check for required functions
                $required_functions = ['sendResponse', 'sendError', 'enforceHTTPS', 'requireApiKey'];
                foreach ($required_functions as $func) {
                    $exists = function_exists($func);
                    $this->recordTest("Config function {$func}", $exists, $exists ? "Function defined" : "Function not defined");
                }
            } else {
                $this->recordTest("Config syntax", false, "config.php has syntax errors");
            }
        } else {
            $this->recordTest("Config file", false, "config.php not found");
        }
    }
    
    private function testDatabaseConfig() {
        echo "Testing database configuration...\n";
        
        // Test database.php
        $db_config_file = $this->base_path . '/config/database.php';
        if (file_exists($db_config_file)) {
            ob_start();
            $error_before = error_get_last();
            include $db_config_file;
            $error_after = error_get_last();
            $output = ob_get_clean();
            
            $has_syntax_error = $error_after && $error_after !== $error_before;
            
            if (!$has_syntax_error) {
                $this->recordTest("Database config syntax", true, "database.php has valid PHP syntax");
            } else {
                $this->recordTest("Database config syntax", false, "database.php has syntax errors");
            }
        } else {
            $this->recordTest("Database config file", false, "database.php not found");
        }
        
        // Test database compatibility layer
        $db_compat_file = $this->base_path . '/includes/database-compat.php';
        if (file_exists($db_compat_file)) {
            ob_start();
            $error_before = error_get_last();
            include $db_compat_file;
            $error_after = error_get_last();
            $output = ob_get_clean();
            
            $has_syntax_error = $error_after && $error_after !== $error_before;
            
            if (!$has_syntax_error) {
                $this->recordTest("Database compatibility", true, "database-compat.php has valid PHP syntax");
            } else {
                $this->recordTest("Database compatibility", false, "database-compat.php has syntax errors");
            }
        }
    }
    
    private function testModelFiles() {
        echo "Testing model files...\n";
        
        $model_file = $this->base_path . '/models/Account.php';
        if (file_exists($model_file)) {
            ob_start();
            $error_before = error_get_last();
            include $model_file;
            $error_after = error_get_last();
            $output = ob_get_clean();
            
            $has_syntax_error = $error_after && $error_after !== $error_before;
            
            if (!$has_syntax_error) {
                $this->recordTest("Account model syntax", true, "Account.php has valid PHP syntax");
                
                // Check if Account class exists (might not be loaded due to dependencies)
                $class_defined = class_exists('Account', false);
                if ($class_defined) {
                    $this->recordTest("Account class", true, "Account class is defined");
                } else {
                    $this->recordTest("Account class", false, "Account class not loaded (may need database connection)");
                }
            } else {
                $this->recordTest("Account model syntax", false, "Account.php has syntax errors");
            }
        } else {
            $this->recordTest("Account model file", false, "Account.php not found");
        }
    }
    
    private function testAPIEndpoints() {
        echo "Testing API endpoint files...\n";
        
        $api_files = [
            'accounts.php',
            'domains.php',
            'status.php',
            'users.php'
        ];
        
        foreach ($api_files as $file) {
            $file_path = $this->base_path . '/api/' . $file;
            if (file_exists($file_path)) {
                // Check syntax without executing (to avoid side effects)
                $syntax_check = shell_exec("php -l " . escapeshellarg($file_path) . " 2>&1");
                $has_syntax_error = strpos($syntax_check, 'Parse error') !== false || strpos($syntax_check, 'Fatal error') !== false;
                
                if (!$has_syntax_error) {
                    $this->recordTest("API {$file} syntax", true, "Valid PHP syntax");
                } else {
                    $this->recordTest("API {$file} syntax", false, "Syntax errors detected");
                }
            } else {
                $this->recordTest("API {$file} file", false, "File not found");
            }
        }
    }
    
    private function testPermissions() {
        echo "Testing file permissions...\n";
        
        // Test read permissions on critical files
        $files_to_check = [
            'index.php',
            'config/config.php',
            'config/database.php',
            'api/accounts.php'
        ];
        
        foreach ($files_to_check as $file) {
            $file_path = $this->base_path . '/' . $file;
            if (file_exists($file_path)) {
                $readable = is_readable($file_path);
                $this->recordTest("Read permission {$file}", $readable, $readable ? "File is readable" : "File is not readable");
            }
        }
        
        // Test write permissions on log directories (if they exist)
        $write_dirs = ['logs', 'tmp', 'cache'];
        foreach ($write_dirs as $dir) {
            $dir_path = $this->base_path . '/' . $dir;
            if (is_dir($dir_path)) {
                $writable = is_writable($dir_path);
                $this->recordTest("Write permission {$dir}", $writable, $writable ? "Directory is writable" : "Directory is not writable");
            }
        }
    }
    
    private function recordTest($test_name, $success, $message) {
        $this->test_results[] = [
            'test' => $test_name,
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $status = $success ? "✓ PASS" : "✗ FAIL";
        echo "  {$status}: {$test_name} - {$message}\n";
    }
    
    private function printSummary() {
        echo "\n=== Configuration Test Summary ===\n";
        
        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($test) {
            return $test['success'];
        }));
        $failed_tests = $total_tests - $passed_tests;
        
        echo "Total Tests: {$total_tests}\n";
        echo "Passed: {$passed_tests}\n";
        echo "Failed: {$failed_tests}\n";
        echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 2) . "%\n\n";
        
        if ($failed_tests > 0) {
            echo "Failed Tests:\n";
            foreach ($this->test_results as $test) {
                if (!$test['success']) {
                    echo "- {$test['test']}: {$test['message']}\n";
                }
            }
            echo "\nRecommendations:\n";
            echo "- Ensure all required files are present\n";
            echo "- Check file permissions (644 for files, 755 for directories)\n";
            echo "- Verify PHP syntax in all files\n";
            echo "- Ensure database configuration is correct\n";
        }
        
        echo "\nConfiguration test completed at: " . date('Y-m-d H:i:s') . "\n";
    }
}

// Run the configuration tests
$base_path = isset($argv[1]) ? $argv[1] : null;
$tester = new ConfigurationTester($base_path);
$tester->runAllTests();
?>
