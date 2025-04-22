<?php
header('Content-Type: text/html');
echo "<h2>Database Recreation Script</h2>";
echo "<p>This script will drop the existing database and create a new one with all required tables.</p>";

// Database configuration
$host = 'localhost';
$dbname = 'volunteer_portal2';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server without specifying a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop existing database if it exists
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    echo "<p style='color: blue;'>Dropped existing database '$dbname' if it existed.</p>";
    
    // Create new database
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>Created new database '$dbname'.</p>";
    
    // Connect to the new database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Creating tables...</h3>";
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            role ENUM('admin', 'user') DEFAULT 'user',
            remember_token VARCHAR(100),
            token_expiry DATETIME,
            total_hours INT DEFAULT 0,
            impact_score INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p>Created 'users' table.</p>";
    
    // Create events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            location VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            max_volunteers INT NOT NULL,
            current_volunteers INT DEFAULT 0,
            requires_approval BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p>Created 'events' table.</p>";
    
    // Create event_registrations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'registered', 'attended', 'cancelled', 'rejected') DEFAULT 'registered',
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (event_id) REFERENCES events(id),
            UNIQUE KEY unique_registration (user_id, event_id)
        )
    ");
    echo "<p>Created 'event_registrations' table.</p>";
    
    // Create certificates table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            event_id INT,
            certificate_name VARCHAR(255),
            issue_date DATE,
            hours_earned INT,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (event_id) REFERENCES events(id)
        )
    ");
    echo "<p>Created 'certificates' table.</p>";
    
    // Insert sample data
    echo "<h3>Inserting sample data...</h3>";
    
    // Insert sample users (including admin user)
    $adminPassword = password_hash('123456', PASSWORD_BCRYPT);
    $userPassword = password_hash('password', PASSWORD_BCRYPT);
    
    $pdo->exec("
        INSERT INTO users (full_name, email, password, role) VALUES 
        ('Admin User', 'nitintyagi7982@gmail.com', '$adminPassword', 'admin'),
        ('Test Volunteer', 'volunteer@example.com', '$userPassword', 'user')
    ");
    echo "<p>Added sample users including admin (nitintyagi7982@gmail.com, password: 123456)</p>";
    
    // Insert sample events
    $pdo->exec("
        INSERT INTO events (title, description, category, location, event_date, start_time, end_time, max_volunteers, requires_approval) VALUES
        ('Community Cleanup Day', 'Join us for a day of cleaning and beautifying our local parks and streets.', 'Environment', 'Riverside Park', '2025-04-15', '09:00:00', '12:00:00', 20, false),
        ('Food Drive for Families', 'Help us collect and distribute food to families in need in our community.', 'Food Security', 'Community Center', '2025-04-20', '10:00:00', '14:00:00', 15, false),
        ('Literacy Workshop', 'Assist in teaching children to read and write at our community literacy program.', 'Education', 'Local Library', '2025-04-25', '13:00:00', '16:00:00', 10, true),
        ('Senior Care Program', 'Spend time with elderly community members, assist with activities, and provide companionship.', 'Community Care', 'Senior Center', '2025-04-30', '10:00:00', '15:00:00', 12, true),
        ('Animal Shelter Support', 'Help care for animals at our local shelter.', 'Animal Welfare', 'City Animal Shelter', '2025-05-05', '09:00:00', '13:00:00', 8, false)
    ");
    echo "<p>Added sample events.</p>";
    
    echo "<h3>Success! Database has been recreated successfully.</h3>";
    echo "<p>You can now <a href='index.html'>return to the login page</a> and log in with:</p>";
    echo "<ul>
        <li><strong>Admin account:</strong> Email: nitintyagi7982@gmail.com, Password: 123456</li>
        <li><strong>User account:</strong> Email: volunteer@example.com, Password: password</li>
    </ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please ensure that XAMPP is running and MySQL service is started.</p>";
}
?> 