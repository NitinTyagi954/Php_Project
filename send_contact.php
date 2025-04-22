<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    $required_fields = ['name', 'email', 'message'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("$field is required");
        }
    }

    // In a real application, you would:
    // 1. Store the message in a database
    // 2. Send an email notification
    // 3. Log the contact request
    
    // For now, we'll just return a success response
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully!'
    ]);

} catch (Exception $e) {
    error_log('Error in send_contact.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 