<?php
require_once 'config.php';

try {
    // Add profile_photo column to users table if it doesn't exist
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL";
    $pdo->exec($sql);
    echo "Profile photo column added successfully!";
} catch (PDOException $e) {
    echo "Error adding profile_photo column: " . $e->getMessage();
}
?> 