<?php
header('Content-Type: text/html');
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Tables Check for phpMyAdmin</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 900px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        h2 { color: #0066cc; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 5px; background-color: #f9f9f9; }
        .phpmyadmin-info { background-color: #e6f7ff; padding: 15px; border-left: 4px solid #0066cc; }
        .code { font-family: monospace; background-color: #f1f1f1; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Database Tables for phpMyAdmin</h1>";

$host = 'localhost';
$dbname = 'volunteer_portal2';
$username = 'root';
$password = '';

echo "<div class='phpmyadmin-info'>";
echo "<h2>phpMyAdmin Information</h2>";
echo "<p>To access phpMyAdmin:</p>";
echo "<ol>
    <li>Open <a href='http://localhost/phpmyadmin/' target='_blank'>http://localhost/phpmyadmin/</a></li>
    <li>Login with username: <span class='code'>root</span> and an empty password (unless you've set one)</li>
    <li>Look for the database <span class='code'>volunteer_portal2</span> in the left sidebar</li>
    <li>Click on the database name to expand and see all tables</li>
</ol>";
echo "</div>";

try {
    // Connect to the MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "<div class='box'>";
    echo "<h2>Database Information</h2>";
    echo "<p>Database: <strong>$dbname</strong></p>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get list of tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<p>Number of tables: <span class='success'>" . count($tables) . "</span></p>";
        echo "</div>";
        
        echo "<h2>Tables in $dbname</h2>";
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Records Count</th><th>Columns</th><th>Created</th></tr>";
        
        foreach ($tables as $table) {
            // Get number of records
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $recordCount = $stmt->fetchColumn();
            
            // Get create time and table info
            $stmt = $pdo->query("SHOW TABLE STATUS LIKE '$table'");
            $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            $createTime = $tableInfo['Create_time'] ?? 'Unknown';
            
            // Get columns
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $primaryColumns = array();
            
            // Get primary key columns
            $stmt = $pdo->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $primaryColumns[] = $row['Column_name'];
            }
            
            $columnsStr = implode(", ", array_map(function($col) use ($primaryColumns) {
                return in_array($col, $primaryColumns) ? "<strong>$col (PK)</strong>" : $col;
            }, $columns));
            
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$recordCount</td>";
            echo "<td>$columnsStr</td>";
            echo "<td>$createTime</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show table details
        echo "<h2>Table Structure Details</h2>";
        
        foreach ($tables as $table) {
            echo "<h3>Table: $table</h3>";
            
            // Get columns with details
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>" . ($column['Default'] === null ? "NULL" : $column['Default']) . "</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
        echo "<div class='box'>";
        echo "<h2>Next Steps</h2>";
        echo "<p>You can now access these tables in phpMyAdmin to view, edit, or query the data.</p>";
        echo "<p>If you're having issues, or see no tables here, run the <a href='recreate_database.php'>database recreation script</a> first.</p>";
        echo "<p><a href='index.html'>Return to login page</a></p>";
        echo "</div>";
        
    } else {
        echo "<p>Tables: <span class='warning'>NONE FOUND</span></p>";
        echo "</div>";
        
        echo "<div class='box'>";
        echo "<h2>No Tables Found</h2>";
        echo "<p>Your database exists but has no tables. You should run the database recreation script.</p>";
        echo "<p><a href='recreate_database.php' class='btn'>Run Database Recreation Script</a></p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>Connection Failed</h2>";
    echo "<p>Could not connect to MySQL or the database. Make sure:</p>";
    echo "<ol>
        <li>MySQL service is running in XAMPP</li>
        <li>You've created the 'volunteer_portal2' database</li>
    </ol>";
    echo "<p><a href='mysql_check.php'>Run MySQL Connection Check</a></p>";
    echo "</div>";
}

echo "</body></html>";
?> 