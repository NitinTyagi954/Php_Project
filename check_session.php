<?php
session_start();
header('Content-Type: application/json');

// Debug logging
error_log('Checking session status');
error_log('Session data: ' . print_r($_SESSION, true));

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? 'user'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'logged_in' => false,
        'message' => 'No active session found'
    ]);
}