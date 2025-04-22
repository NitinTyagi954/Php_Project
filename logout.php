<?php
ob_start(); // Start output buffering
header('Content-Type: application/json');
session_start();
require_once 'config.php';

// Clear remember token in database if it exists
if (isset($_COOKIE['remember_token'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE remember_token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
    } catch (PDOException $e) {
        error_log("Error clearing remember token: " . $e->getMessage());
    }
}

// Clear session
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

echo json_encode(['success' => true]);

ob_end_flush(); // End output buffering and send the response
?>