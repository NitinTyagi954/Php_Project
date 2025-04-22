<?php
header('Content-Type: text/html');
echo "<!DOCTYPE html>
<html>
<head>
    <title>XAMPP MySQL Connection Check</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        h2 { color: #0066cc; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 5px; background-color: #f9f9f9; }
        pre { background-color: #f1f1f1; padding: 10px; border-radius: 5px; overflow-x: auto; }
        ol li, ul li { margin-bottom: 10px; }
        .steps { background-color: #f0f8ff; padding: 15px; border-left: 4px solid #0066cc; }
    </style>
</head>
<body>
    <h1>XAMPP MySQL Connection Check</h1>";

$host = 'localhost';
$username = 'root';
$password = '';

echo "<div class='box'>";
echo "<h2>Step 1: Checking XAMPP Services</h2>";

// Check if we can connect to localhost at all
$apache_running = @file_get_contents('http://localhost/') !== false;
echo "<p>Apache service: " . ($apache_running ? "<span class='success'>RUNNING</span>" : "<span class='error'>NOT RUNNING</span>") . "</p>";

// Try to connect to MySQL server
try {
    $start_time = microtime(true);
    $pdo = @new PDO("mysql:host=$host", $username, $password);
    $end_time = microtime(true);
    $connection_time = round(($end_time - $start_time) * 1000, 2);
    
    echo "<p>MySQL service: <span class='success'>RUNNING</span> (connected in {$connection_time}ms)</p>";
    $mysql_running = true;
    
    // Try to get MySQL version
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch(PDO::FETCH_ASSOC)['version'];
    echo "<p>MySQL version: <span class='success'>{$version}</span></p>";
    
} catch (PDOException $e) {
    echo "<p>MySQL service: <span class='error'>NOT RUNNING OR CONNECTION FAILED</span></p>";
    echo "<p>Error message: <span class='error'>" . $e->getMessage() . "</span></p>";
    $mysql_running = false;
}
echo "</div>";

// If MySQL is not running, show troubleshooting steps
if (!$mysql_running) {
    echo "<div class='box steps'>";
    echo "<h2>MySQL Connection Troubleshooting</h2>";
    echo "<ol>
        <li><strong>Check XAMPP Control Panel</strong>: Make sure MySQL service is started
            <ul>
                <li>Open XAMPP Control Panel</li>
                <li>If MySQL is not running (no green light), click the 'Start' button next to MySQL</li>
                <li>If it fails to start, check the log by clicking on the 'Logs' button</li>
            </ul>
        </li>
        <li><strong>Check if MySQL port is in use</strong>:
            <ul>
                <li>Open Command Prompt as Administrator</li>
                <li>Run: <pre>netstat -ano | findstr :3306</pre></li>
                <li>If another process is using port 3306, you may need to stop that process or change MySQL port</li>
            </ul>
        </li>
        <li><strong>Check MySQL error logs</strong>:
            <ul>
                <li>Check the file: <code>C:\\xampp\\mysql\\data\\mysql_error.log</code> or <code>C:\\xampp\\mysql\\data\\[your-computer-name].err</code></li>
            </ul>
        </li>
        <li><strong>Try restarting XAMPP completely</strong>:
            <ul>
                <li>Stop all services in XAMPP Control Panel</li>
                <li>Close the XAMPP Control Panel</li>
                <li>Reopen XAMPP Control Panel as Administrator</li>
                <li>Start Apache, then MySQL</li>
            </ul>
        </li>
        <li><strong>Check MySQL configuration</strong>:
            <ul>
                <li>Open <code>C:\\xampp\\mysql\\bin\\my.ini</code></li>
                <li>Make sure port settings are correct</li>
            </ul>
        </li>
    </ol>";
    echo "</div>";
} else {
    // If MySQL is running, try to check the volunteer database
    echo "<div class='box'>";
    echo "<h2>Step 2: Checking 'volunteer_portal2' Database</h2>";
    
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `volunteer_portal2`");
        echo "<p>Database 'volunteer_portal2': <span class='success'>AVAILABLE</span> (created if it didn't exist)</p>";
        
        $pdo = new PDO("mysql:host=$host;dbname=volunteer_portal2", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check for tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p>Tables found: <span class='success'>" . implode(", ", $tables) . "</span></p>";
        } else {
            echo "<p>Tables: <span class='warning'>NONE FOUND</span> - You should run the recreate_database.php script</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p>Database 'volunteer_portal2': <span class='error'>ERROR</span></p>";
        echo "<p>Error message: <span class='error'>" . $e->getMessage() . "</span></p>";
    }
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>Next Steps</h2>";
    echo "<p>Your MySQL server is running correctly. Here's what you can do next:</p>";
    echo "<ul>
        <li><a href='recreate_database.php'>Run the Database Recreation Script</a> to set up all tables and sample data</li>
        <li>After recreating the database, <a href='index.html'>go to the login page</a> and try logging in with one of the sample accounts</li>
    </ul>";
    echo "</div>";
}

echo "</body></html>";
?> 