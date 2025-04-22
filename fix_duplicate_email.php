<?php
header('Content-Type: text/html');
echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Duplicate Email Error</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #0066cc; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 5px; background-color: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        button, .btn {
            background-color: #0066cc;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px 0;
        }
        button:hover, .btn:hover {
            background-color: #0055aa;
        }
    </style>
</head>
<body>
    <h1>Fix Duplicate Email Error</h1>";

$host = 'localhost';
$dbname = 'volunteer_portal2';
$username = 'root';
$password = '';

// Check if we should perform the fix
$action = $_GET['action'] ?? '';

if ($action == 'update') {
    // Option 1: Update the existing user
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Update the existing user to be an admin with the correct password
        $password_hash = password_hash('123456', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin', password = ? WHERE email = 'nitintyagi7982@gmail.com'");
        $stmt->execute([$password_hash]);
        
        if ($stmt->rowCount() > 0) {
            echo "<div class='box'>";
            echo "<h2 class='success'>Success!</h2>";
            echo "<p>The user with email 'nitintyagi7982@gmail.com' has been updated with:</p>";
            echo "<ul>
                <li>Role: <span class='success'>admin</span></li>
                <li>Password: <span class='success'>123456</span></li>
            </ul>";
            echo "<p>You can now <a href='index.html'>login</a> with these credentials.</p>";
            echo "</div>";
        } else {
            echo "<div class='box'>";
            echo "<p class='warning'>No user found with email 'nitintyagi7982@gmail.com'. Try the recreate option instead.</p>";
            echo "</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='box'>";
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else if ($action == 'recreate') {
    // Option 2: Force drop and recreate the database
    try {
        // Connect to the MySQL server
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Drop the database
        $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
        
        echo "<div class='box'>";
        echo "<h2 class='success'>Database Dropped Successfully</h2>";
        echo "<p>The database '$dbname' has been completely removed.</p>";
        echo "<p>Now you can <a href='recreate_database.php' class='btn'>Recreate the Database</a> without duplicate email errors.</p>";
        echo "</div>";
    } catch (PDOException $e) {
        echo "<div class='box'>";
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    // Show options
    echo "<div class='box'>";
    echo "<h2>Error Explanation</h2>";
    echo "<p class='error'>Error: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'nitintyagi7982@gmail.com' for key 'email'</p>";
    echo "<p>This error means you already have a user with the email address 'nitintyagi7982@gmail.com' in your database.</p>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>Fix Options</h2>";
    echo "<p>You have two options to fix this:</p>";
    
    echo "<div style='margin-bottom: 20px;'>";
    echo "<h3>Option 1: Update Existing User</h3>";
    echo "<p>This will update the existing user with admin privileges and password '123456'.</p>";
    echo "<a href='?action=update' class='btn'>Update Existing User</a>";
    echo "</div>";
    
    echo "<div>";
    echo "<h3>Option 2: Complete Database Reset</h3>";
    echo "<p>This will drop the entire database and you can recreate it from scratch. <strong>Warning: All existing data will be lost!</strong></p>";
    echo "<a href='?action=recreate' class='btn' style='background-color: #cc3300;'>Drop Database</a>";
    echo "</div>";
    
    echo "</div>";
}

echo "</body>
</html>";
?> 