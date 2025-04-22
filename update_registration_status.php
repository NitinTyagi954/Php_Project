<?php
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin (check both role variables)
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || 
            (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!isset($_SESSION['user_id']) || !$is_admin) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin privileges required.'
    ]);
    exit;
}

// Include the database configuration
require_once 'config.php';

// Get POST parameters
$registration_id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

// Validate inputs
if (!$registration_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Registration ID is required.'
    ]);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'registered', 'attended', 'cancelled', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status. Valid options are: ' . implode(', ', $valid_statuses)
    ]);
    exit;
}

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get the event ID for the registration
    $stmt = $pdo->prepare("SELECT event_id FROM event_registrations WHERE id = :registration_id");
    $stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        throw new Exception('Registration not found.');
    }
    
    $event_id = $result['event_id'];
    
    // Update registration status
    $stmt = $pdo->prepare("UPDATE event_registrations SET status = :status WHERE id = :registration_id");
    $stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    
    // If status is changing to or from 'registered', update event's current_volunteers count
    $stmt = $pdo->prepare("
        SELECT status FROM event_registrations WHERE id = :registration_id
    ");
    $stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt->execute();
    $old_status = $stmt->fetchColumn();
    
    // Increment or decrement current_volunteers based on status change
    if ($old_status !== 'registered' && $status === 'registered') {
        // New registration, increment count
        $stmt = $pdo->prepare("
            UPDATE events 
            SET current_volunteers = current_volunteers + 1 
            WHERE id = :event_id
        ");
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->execute();
    } else if ($old_status === 'registered' && $status !== 'registered') {
        // Registration cancelled/rejected, decrement count
        $stmt = $pdo->prepare("
            UPDATE events 
            SET current_volunteers = GREATEST(0, current_volunteers - 1) 
            WHERE id = :event_id
        ");
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration status updated successfully.',
        'event_id' => $event_id
    ]);
} catch (Exception $e) {
    // Roll back transaction on error
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 