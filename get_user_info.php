<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Debug logging
error_log('Session data: ' . print_r($_SESSION, true));

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        error_log('No user_id in session');
        throw new Exception('User not logged in');
    }

    $user_id = $_SESSION['user_id'];
    error_log('Attempting to fetch user with ID: ' . $user_id);

    // Get user information
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, phone 
        FROM users 
        WHERE id = ?
    ");
    
    if (!$stmt) {
        error_log('PDO prepare error: ' . print_r($pdo->errorInfo(), true));
        throw new Exception('Failed to prepare statement: ' . $pdo->errorInfo()[2]);
    }

    $result = $stmt->execute([$user_id]);
    if (!$result) {
        error_log('PDO execute error: ' . print_r($stmt->errorInfo(), true));
        throw new Exception('Failed to execute statement: ' . $stmt->errorInfo()[2]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log('User data fetched: ' . print_r($user, true));

    if (!$user) {
        error_log('No user found for ID: ' . $user_id);
        throw new Exception('User not found in database');
    }

    echo json_encode([
        'success' => true,
        'user' => $user
    ]);

} catch (Exception $e) {
    error_log('Error in get_user_info.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 