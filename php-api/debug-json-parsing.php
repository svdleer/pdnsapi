<?php
// Simple debug script to test JSON parsing
$input = file_get_contents("php://input");
error_log("Raw input: " . var_export($input, true));

if (!empty($input)) {
    $json_data = json_decode($input, true);
    error_log("Parsed JSON: " . var_export($json_data, true));
    error_log("JSON error: " . json_last_error_msg());
    
    if ($json_data && isset($json_data['id'])) {
        error_log("Found ID in JSON: " . $json_data['id']);
    } else {
        error_log("No ID found in JSON data");
    }
} else {
    error_log("Input is empty");
}

// Test response
header('Content-Type: application/json');
echo json_encode([
    'debug' => 'JSON parsing test',
    'raw_input' => $input,
    'parsed_json' => $json_data ?? null,
    'json_error' => json_last_error_msg(),
    'has_id' => isset($json_data['id']) ? 'yes' : 'no'
]);
?>
