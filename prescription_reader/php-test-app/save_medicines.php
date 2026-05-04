<?php
// This is a mock backend script to test the integration.
// In CareSync, this is where you would connect to MySQL and run an INSERT query.

header('Content-Type: text/html; charset=utf-8');

// Get the raw POST data (since Javascript Fetch sends JSON payload, not standard form-data)
$jsonPayload = file_get_contents('php://input');

if (!$jsonPayload) {
    http_response_code(400);
    echo "No data received.";
    exit;
}

// Decode the JSON into a PHP Associative Array
$data = json_decode($jsonPayload, true);

if (isset($data['medicines']) && is_array($data['medicines'])) {
    
    $medicines = $data['medicines'];
    $count = count($medicines);
    
    // Simulate database save delay
    sleep(1);
    
    // Output success message simulating DB save
    echo "<strong>Success!</strong> Successfully saved <strong>$count</strong> medicines to the PHP server.<br><br>";
    echo "<em>Data received by PHP:</em><br>";
    
    // Display the received array to prove PHP got it correctly
    echo "<pre style='background: rgba(0,0,0,0.3); padding: 10px; border-radius: 6px; color: #fff; margin-top: 10px; font-size: 0.9em; text-align: left; overflow-x: auto;'>";
    print_r($medicines);
    echo "</pre>";

} else {
    http_response_code(400);
    echo "Invalid data format received.";
}
?>
