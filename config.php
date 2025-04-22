<?php
$host = 'localhost';
$dbname = 'volunteer_portal2';
$username = 'root'; // TODO: Replace with secure username
$password = ''; // TODO: Replace with secure password

try {
    // Check if MySQL is accessible (XAMPP is running)
    $testConnection = @new PDO("mysql:host=$host", $username, $password);
    
    // If we can connect to the server but the database doesn't exist, create it
    $testConnection->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $testConnection = null; // Close the test connection
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Log successful connection
    error_log("Database connected successfully: $host / $dbname");
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    error_log("Database connection error: " . $errorMessage);
    
    // Provide more helpful error message
    if (strpos($errorMessage, "Unknown database") !== false) {
        $errorMessage = "Database '$dbname' does not exist. Please run the database.sql script first.";
    } elseif (strpos($errorMessage, "Access denied") !== false) {
        $errorMessage = "MySQL access denied. Check your username and password.";
    } elseif (strpos($errorMessage, "Connection refused") !== false || 
              strpos($errorMessage, "Could not find driver") !== false) {
        $errorMessage = "Could not connect to MySQL. Make sure XAMPP is running and MySQL service is started.";
    }
    
    // Only output the error details if this is not an included file
    if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    } else {
        // For included files, just pass the error upstream
        throw new PDOException($errorMessage, $e->getCode());
    }
    exit;
}
?>