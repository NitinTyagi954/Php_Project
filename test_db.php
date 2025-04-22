<?php
require_once 'config.php';

header('Content-Type: text/plain');

try {
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection successful\n";

    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "Users table exists\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE users");
        echo "Users table structure:\n";
        while ($row = $stmt->fetch()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } else {
        echo "Users table does not exist\n";
    }

    // Check if events table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'events'");
    if ($stmt->rowCount() === 0) {
        echo "Events table does not exist. Creating it...\n";
        
        // Create events table
        $pdo->exec("
            CREATE TABLE events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                event_date DATE NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                location VARCHAR(255) NOT NULL,
                max_volunteers INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "Events table created successfully.\n";
    } else {
        echo "Events table exists.\n";
    }

    // Check if event_registrations table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'event_registrations'");
    if ($stmt->rowCount() === 0) {
        echo "Event registrations table does not exist. Creating it...\n";
        
        // Create event_registrations table
        $pdo->exec("
            CREATE TABLE event_registrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_id INT NOT NULL,
                user_id INT NOT NULL,
                status ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
                registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES events(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        echo "Event registrations table created successfully.\n";
    } else {
        echo "Event registrations table exists.\n";
        
        // Check if registration_date column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM event_registrations LIKE 'registration_date'");
        if ($stmt->rowCount() === 0) {
            echo "Adding registration_date column...\n";
            $pdo->exec("ALTER TABLE event_registrations ADD COLUMN registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            echo "registration_date column added successfully.\n";
        } else {
            echo "registration_date column already exists.\n";
        }
    }

    // Show table structures
    echo "\nEvents table structure:\n";
    $stmt = $pdo->query("DESCRIBE events");
    while ($row = $stmt->fetch()) {
        echo implode("\t", $row) . "\n";
    }

    echo "\nEvent registrations table structure:\n";
    $stmt = $pdo->query("DESCRIBE event_registrations");
    while ($row = $stmt->fetch()) {
        echo implode("\t", $row) . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 