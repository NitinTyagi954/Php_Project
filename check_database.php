<?php
header('Content-Type: application/json');

// Include config without halting on errors
try {
    require_once 'config.php';
    $configIncluded = true;
} catch (Exception $e) {
    $configIncluded = false;
    $configError = $e->getMessage();
}

// Function to check MySQL server connection
function checkMySQLServer($host, $username, $password) {
    try {
        $conn = @new PDO("mysql:host=$host", $username, $password);
        return ['status' => true, 'message' => 'MySQL server connection successful'];
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'MySQL server connection failed: ' . $e->getMessage()];
    }
}

// Function to check specific database connection
function checkDatabase($host, $dbname, $username, $password) {
    try {
        $conn = @new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        return ['status' => true, 'message' => 'Database connection successful'];
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
    }
}

// Function to check if required tables exist
function checkTables($pdo, $tables) {
    $results = [];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            $results[$table] = ['status' => true, 'message' => 'Table exists and is accessible'];
        } catch (PDOException $e) {
            $results[$table] = ['status' => false, 'message' => 'Table issue: ' . $e->getMessage()];
        }
    }
    return $results;
}

// Function to check XAMPP status
function checkXAMPP() {
    // Check if Apache is running by trying to access localhost
    $apache = @file_get_contents('http://localhost/') !== false;
    
    // Check if we can connect to MySQL on localhost
    try {
        $mysql = @new PDO("mysql:host=localhost", 'root', '');
        $mysqlRunning = true;
    } catch (Exception $e) {
        $mysqlRunning = false;
    }
    
    return [
        'apache' => $apache,
        'mysql' => $mysqlRunning
    ];
}

// Main check process
$host = 'localhost';
$dbname = 'volunteer_portal2';
$username = 'root';
$password = '';

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'xampp' => checkXAMPP(),
    'config_included' => $configIncluded
];

if (!$configIncluded) {
    $results['config_error'] = $configError;
} else {
    $results['mysql_server'] = checkMySQLServer($host, $username, $password);
    $results['database'] = checkDatabase($host, $dbname, $username, $password);
    
    // If database connection is successful, check tables
    if ($results['database']['status']) {
        $requiredTables = ['users', 'events', 'event_registrations', 'certificates'];
        $results['tables'] = checkTables($pdo, $requiredTables);
        
        // Get user count as a sanity check
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $results['user_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {
            $results['user_count_error'] = $e->getMessage();
        }
    }
}

// System information
$results['system'] = [
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
];

// Display the results
echo json_encode($results, JSON_PRETTY_PRINT);
?> 