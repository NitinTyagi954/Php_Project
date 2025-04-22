<?php
header('Content-Type: text/html');
session_start();

// Include database configuration
require_once '../includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin Role</title>
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
        input, button { padding: 8px; margin: 5px 0; }
        button { background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h1>Fix Admin Role</h1>";

// Process email update if submitted
if (isset($_POST['email']) && !empty($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    echo "<div class='card'>";
    
    try {
        // Connect to database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Update user role to admin
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "<h2 class='success'>Role Updated Successfully!</h2>";
            echo "<p>The user with email <strong>$email</strong> has been updated to 'admin' in the database.</p>";
            
            // Also update session if this is the current user
            if (isset($_SESSION['user_email']) && $_SESSION['user_email'] === $email) {
                $_SESSION['user_role'] = 'admin';
                $_SESSION['role'] = 'admin'; // Set both role variables to be safe
                echo "<p class='success'>✅ Your session has also been updated with admin privileges.</p>";
            }
            
            echo "<p>Try accessing the <a href='admin-dashboard.html'>admin dashboard</a> now.</p>";
        } else {
            echo "<h2 class='warning'>No Changes Made</h2>";
            echo "<p>No user was found with the email <strong>$email</strong> or the user is already an admin.</p>";
        }
    } catch (PDOException $e) {
        echo "<h2 class='error'>Error Updating Role</h2>";
        echo "<p>Database error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// Option to make current user admin
echo "<div class='card'>";
echo "<h2>Fix Current User</h2>";

if (isset($_SESSION['user_id'])) {
    $currentEmail = $_SESSION['user_email'] ?? '(email not in session)';
    echo "<p>You are currently logged in as: <strong>$currentEmail</strong></p>";
    
    echo "<form method='post' action=''>
        <input type='hidden' name='email' value='$currentEmail'>
        <button type='submit'>Make Me an Admin</button>
    </form>";
} else {
    echo "<p class='warning'>⚠️ You are not currently logged in. Please log in first.</p>";
}

echo "</div>";

// Option to make any user admin
echo "<div class='card'>";
echo "<h2>Fix Any User</h2>";
echo "<p>Enter the email address of the user you want to make an admin:</p>";

echo "<form method='post' action=''>
    <input type='email' name='email' placeholder='user@example.com' required>
    <button type='submit'>Update to Admin</button>
</form>";
echo "</div>";

// Show all current users
echo "<div class='card'>";
echo "<h2>Current Users</h2>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all users
    $stmt = $pdo->query("SELECT id, full_name, email, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        
        foreach ($users as $user) {
            $roleClass = $user['role'] === 'admin' ? 'success' : '';
            echo "<tr>
                <td>{$user['id']}</td>
                <td>{$user['full_name']}</td>
                <td>{$user['email']}</td>
                <td class='$roleClass'>{$user['role']}</td>
            </tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No users found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Login form if not logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='card'>";
    echo "<h2>Login</h2>";
    echo "<p>You need to be logged in to access the admin dashboard:</p>";
    echo "<p><a href='../index.html'>Go to login page</a></p>";
    echo "</div>";
}

echo "<div class='card'>
    <h2>Next Steps</h2>
    <ul>
        <li>After updating a user to admin, they should <a href='../includes/logout.php'>log out</a> and log back in</li>
        <li>Then try accessing the <a href='admin-dashboard.html'>admin dashboard</a></li>
        <li>If still having issues, check <a href='admin_debug.php'>admin debug tool</a> for more detailed diagnostics</li>
    </ul>
</div>";

echo "</body></html>";
?> 