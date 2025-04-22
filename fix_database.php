<?php
header('Content-Type: text/html');
require_once 'config.php';

echo "<h2>Database Fix Script</h2>";
echo "<p>Checking and fixing database structure...</p>";

try {
    // Check if 'role' column exists in users table
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'role'");
    $stmt->execute();
    $roleColumnExists = $stmt->rowCount() > 0;
    
    if (!$roleColumnExists) {
        echo "<p>The 'role' column is missing from the users table. Adding it now...</p>";
        
        // Add the role column
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user'");
        
        echo "<p style='color: green;'>Success! The 'role' column has been added to the users table.</p>";
        
        // Set the first user as admin for testing
        $pdo->exec("UPDATE users SET role = 'admin' WHERE id = 1");
        echo "<p>The first user (ID 1) has been set as an admin for testing.</p>";
        
        // Set the user with email nitintyagi7982@gmail.com as admin if exists
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE email = 'nitintyagi7982@gmail.com'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo "<p>User with email 'nitintyagi7982@gmail.com' has been set as admin.</p>";
        }
    } else {
        echo "<p style='color: blue;'>The 'role' column already exists in the users table.</p>";
    }
    
    // Check for other required columns and tables based on our schema
    echo "<h3>Checking other database elements:</h3>";
    
    // Check if events table has requires_approval column
    $stmt = $pdo->prepare("SHOW COLUMNS FROM events LIKE 'requires_approval'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        echo "<p>Adding 'requires_approval' column to events table...</p>";
        $pdo->exec("ALTER TABLE events ADD COLUMN requires_approval BOOLEAN DEFAULT FALSE");
        echo "<p style='color: green;'>Added 'requires_approval' column to events table.</p>";
    } else {
        echo "<p>The 'requires_approval' column already exists in the events table.</p>";
    }
    
    // Check if event_registrations has all the required status values
    $stmt = $pdo->prepare("SHOW COLUMNS FROM event_registrations LIKE 'status'");
    $stmt->execute();
    $statusColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($statusColumn) {
        $type = $statusColumn['Type'];
        
        // Check if the enum has all required values
        if (strpos($type, 'pending') === false || strpos($type, 'rejected') === false) {
            echo "<p>Updating 'status' column in event_registrations table to include 'pending' and 'rejected'...</p>";
            $pdo->exec("ALTER TABLE event_registrations MODIFY COLUMN status ENUM('pending', 'registered', 'attended', 'cancelled', 'rejected') DEFAULT 'registered'");
            echo "<p style='color: green;'>Updated 'status' column in event_registrations table.</p>";
        } else {
            echo "<p>The 'status' column in event_registrations already has all required values.</p>";
        }
    }
    
    echo "<h3>Database check complete!</h3>";
    echo "<p>Your database should now be compatible with the application.</p>";
    echo "<p><a href='index.html'>Return to login page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please ensure that XAMPP is running and MySQL service is started.</p>";
    echo "<pre>" . print_r($e, true) . "</pre>";
}
?> 