<?php
require_once '../includes/config.php';

// Set the admin details
$full_name = "Nitin Tyagi";
$email = "nitintyagi7982@gmail.com";
$password = "123456";
$role = "admin";

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // User already exists, update their role to admin
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin', password = ? WHERE email = ?");
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->execute([$hashed_password, $email]);
        
        echo "Admin user updated successfully!";
    } else {
        // Create a new admin user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->execute([$full_name, $email, $hashed_password, $role]);
        
        echo "Admin user created successfully!";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><br>You can now log in with:<br>";
echo "Email: " . $email . "<br>";
echo "Password: " . $password . "<br>";
echo "Role: " . $role;
?> 