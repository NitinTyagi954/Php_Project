<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);
$registrationId = $data['registration_id'] ?? 0;

if (!$registrationId) {
    echo json_encode([
        'success' => false,
        'message' => 'Registration ID is required'
    ]);
    exit;
}

try {
    // Update registration status to rejected
    $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$registrationId]);
    
    // Send notification email (to be implemented)
    // sendRejectionEmail($registrationId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration rejected successfully'
    ]);
} catch (PDOException $e) {
    error_log("Rejection error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} 