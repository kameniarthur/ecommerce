<?php
// Simple DB connection test script
// Usage (CLI): php test_db_connection.php
// Usage (web): open /test_db_connection.php in browser

// Load project bootstrap/config
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';

// Helper to print for web or CLI
function out($msg) {
    if (php_sapi_name() === 'cli') {
        echo $msg . PHP_EOL;
    } else {
        echo htmlspecialchars($msg) . "<br>\n";
    }
}

if (php_sapi_name() !== 'cli') {
    echo "<pre>";
}

out("Testing database connection...");

try {
    // Acquire singleton Database instance
    $db = Database::getInstance();

    // Use underlying PDO connection for a lightweight test
    $conn = $db->getConnection();

    // Simple query
    $stmt = $conn->query('SELECT 1');
    $result = $stmt->fetchColumn();

    out("Connection successful. SELECT 1 returned: " . var_export($result, true));
    $exitCode = 0;
} catch (Throwable $e) {
    out("Connection failed: " . $e->getMessage());
    $exitCode = 1;
}

if (php_sapi_name() !== 'cli') {
    echo "</pre>";
}

exit($exitCode);
