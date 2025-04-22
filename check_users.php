<?php
header('Content-Type: text/html');
session_start();

// Include database configuration
require_once '../includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Check Users Table</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .card { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        button { background-color: #4CAF50; color: white; border: none; padding: 10px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Check Users Table</h1>";

echo "<div class='card'>";
echo "<h2>Users in Database</h2>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all users directly from the database
    $stmt = $pdo->query("SELECT id, full_name, email, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<p>Found <span class='success'>" . count($users) . " users</span> in the database.</p>";
        echo "<table>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>
                <td>{$user['id']}</td>
                <td>{$user['full_name']}</td>
                <td>{$user['email']}</td>
                <td>{$user['role']}</td>
            </tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='warning'>No users found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='card'>";
echo "<h2>Test get_users.php API</h2>";
echo "<div id='apiResult'>Loading API results...</div>";

echo "<script>
    // Test what get_users.php returns
    fetch('get_users.php')
        .then(response => response.json())
        .then(data => {
            const resultElement = document.getElementById('apiResult');
            
            if (data.success) {
                resultElement.innerHTML = '<p class=\"success\">API returned successfully with ' + data.users.length + ' users</p>';
                
                // Add table with user data
                if (data.users.length > 0) {
                    let table = '<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>';
                    
                    data.users.forEach(user => {
                        table += '<tr>' +
                            '<td>' + user.id + '</td>' +
                            '<td>' + user.full_name + '</td>' +
                            '<td>' + user.email + '</td>' +
                            '<td>' + user.role + '</td>' +
                            '</tr>';
                    });
                    
                    table += '</table>';
                    resultElement.innerHTML += table;
                } else {
                    resultElement.innerHTML += '<p class=\"warning\">No users returned from API.</p>';
                }
            } else {
                resultElement.innerHTML = '<p class=\"error\">API Error: ' + (data.message || 'Unknown error') + '</p>';
            }
        })
        .catch(error => {
            document.getElementById('apiResult').innerHTML = 
                '<p class=\"error\">Error testing get_users.php: ' + error.message + '</p>';
        });
</script>";
echo "</div>";

echo "<div class='card'>";
echo "<h2>Session Information</h2>";

if (isset($_SESSION) && !empty($_SESSION)) {
    echo "<table>
        <tr><th>Key</th><th>Value</th></tr>";
    
    foreach ($_SESSION as $key => $value) {
        if ($key == 'password' || strpos($key, 'pass') !== false) {
            $displayValue = '[HIDDEN]';
        } else {
            $displayValue = is_array($value) ? json_encode($value) : htmlspecialchars($value);
        }
        
        echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . $displayValue . "</td></tr>";
    }
    
    echo "</table>";
} else {
    echo "<p class='warning'>No session information available. You may not be logged in.</p>";
}

echo "</div>";

echo "<div class='card'>";
echo "<h2>Actions</h2>";
echo "<p>If users are not showing up in the admin dashboard:</p>";
echo "<ol>
    <li>Make sure you're logged in as an admin user</li>
    <li>Check if there are users in the database (see table above)</li>
    <li>Check if the get_users.php API is returning users (see API test above)</li>
    <li><a href='admin-dashboard.html'>Return to admin dashboard</a> and try refreshing the page</li>
</ol>";
echo "</div>";

echo "</body></html>";
?> 