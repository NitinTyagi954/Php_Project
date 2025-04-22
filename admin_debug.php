<?php
header('Content-Type: text/html');
session_start();

// Include database configuration
require_once '../includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Debug Tool</title>
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
    </style>
</head>
<body>
    <h1>Admin Session Debug Tool</h1>";

// Display current session information
echo "<div class='card'>
    <h2>Current Session Information</h2>";

if (isset($_SESSION) && !empty($_SESSION)) {
    echo "<table>
        <tr><th>Key</th><th>Value</th></tr>";
    
    foreach ($_SESSION as $key => $value) {
        // Don't show password hashes or sensitive data
        if ($key == 'password' || strpos($key, 'pass') !== false) {
            $displayValue = '[HIDDEN]';
        } else {
            $displayValue = is_array($value) ? json_encode($value) : htmlspecialchars($value);
        }
        
        echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . $displayValue . "</td></tr>";
    }
    
    echo "</table>";
    
    // Check specific admin-related session variables
    if (isset($_SESSION['user_id'])) {
        echo "<p class='success'>✅ User ID found in session: " . $_SESSION['user_id'] . "</p>";
    } else {
        echo "<p class='error'>❌ No user_id in session. You are not logged in.</p>";
    }
    
    // Check both role fields
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            echo "<p class='success'>✅ Role (role) is set to 'admin' in session</p>";
        } else {
            echo "<p class='warning'>⚠️ Role (role) in session is '" . $_SESSION['role'] . "', not 'admin'</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ No 'role' variable in session</p>";
    }

    if (isset($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] === 'admin') {
            echo "<p class='success'>✅ Role (user_role) is set to 'admin' in session</p>";
        } else {
            echo "<p class='warning'>⚠️ Role (user_role) in session is '" . $_SESSION['user_role'] . "', not 'admin'</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ No 'user_role' variable in session</p>";
    }
} else {
    echo "<p class='error'>No session data found. You may not be logged in.</p>";
}

echo "</div>";

// Check the user in the database
echo "<div class='card'>
    <h2>Database User Check</h2>";

if (isset($_SESSION['user_id']) || isset($_SESSION['email'])) {
    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get user information
        $userId = $_SESSION['user_id'] ?? null;
        $userEmail = $_SESSION['email'] ?? null;
        
        if ($userId) {
            $stmt = $pdo->prepare("SELECT id, full_name, email, role FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
        } else if ($userEmail) {
            $stmt = $pdo->prepare("SELECT id, full_name, email, role FROM users WHERE email = :email");
            $stmt->bindParam(':email', $userEmail);
        }
        
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<table>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>
                <tr>
                    <td>{$user['id']}</td>
                    <td>{$user['full_name']}</td>
                    <td>{$user['email']}</td>
                    <td>{$user['role']}</td>
                </tr>
            </table>";
            
            if ($user['role'] === 'admin') {
                echo "<p class='success'>✅ User has 'admin' role in the database</p>";
                
                // Check if session role matches database role
                if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
                    echo "<p class='error'>❌ Session role ('{$_SESSION['role']}') does not match database role ('admin')</p>";
                    echo "<p>This explains why you're being redirected to the user dashboard.</p>";
                }
            } else {
                echo "<p class='warning'>⚠️ User does not have 'admin' role in the database. Current role: '{$user['role']}'</p>";
                echo "<p>This is why you're being redirected to the user dashboard.</p>";
                
                echo "<h3>Fix your role:</h3>";
                echo "<p>To fix this, click the button below to update your role to 'admin':</p>";
                echo "<form method='post' action=''>
                    <input type='hidden' name='update_role' value='1'>
                    <input type='submit' value='Update My Role to Admin'>
                </form>";
            }
        } else {
            echo "<p class='error'>User not found in database.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>No user ID or email in session. Cannot check database.</p>";
}

echo "</div>";

// Process role update if requested
if (isset($_POST['update_role']) && isset($_SESSION['user_id'])) {
    echo "<div class='card'>";
    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Update user role
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Also update the session
            $_SESSION['role'] = 'admin';
            
            echo "<h2 class='success'>Role Updated Successfully!</h2>";
            echo "<p>Your role has been updated to 'admin' in the database and session.</p>";
            echo "<p>Try accessing the <a href='admin-dashboard.html'>admin dashboard</a> now.</p>";
        } else {
            echo "<h2 class='warning'>No Changes Made</h2>";
            echo "<p>Your user record was not updated. It may already be set as admin or there might be an issue with your user ID.</p>";
        }
    } catch (PDOException $e) {
        echo "<h2 class='error'>Error Updating Role</h2>";
        echo "<p>Database error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

// Check PHP session configuration
echo "<div class='card'>
    <h2>PHP Session Configuration</h2>
    <p>Session save path: " . session_save_path() . "</p>
    <p>Session name: " . session_name() . "</p>
    <p>Session ID: " . session_id() . "</p>
</div>";

// Show check_session.php test
echo "<div class='card'>
    <h2>check_session.php Test</h2>
    <div id='sessionCheckResult'>Loading...</div>
    <script>
        // Test what check_session.php returns
        fetch('../includes/check_session.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('sessionCheckResult').innerHTML = 
                    '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                document.getElementById('sessionCheckResult').innerHTML = 
                    '<p class=\"error\">Error testing check_session.php: ' + error.message + '</p>';
            });
    </script>
</div>";

echo "<div class='card'>
    <h2>Next Steps</h2>
    <ul>
        <li>If your role is not 'admin' in the database, use the button above to update it</li>
        <li>If your role is 'admin' in the database but not in the session, try logging out and back in</li>
        <li>If check_session.php doesn't show your correct role, there may be an issue with that script</li>
        <li>After fixing your role, try the <a href='admin-dashboard.html'>admin dashboard</a> again</li>
    </ul>
</div>";

echo "</body></html>";
?> 
?> 