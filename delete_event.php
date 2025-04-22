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

// Get the event ID from POST
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

if (!$event_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Event ID is required.'
    ]);
    exit;
}

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if there are any registrations for this event
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = :event_id");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();
    $registrationsCount = $stmt->fetchColumn();
    
    // Delete any registrations for this event
    if ($registrationsCount > 0) {
        $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = :event_id");
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // Delete any certificates for this event
    $stmt = $pdo->prepare("DELETE FROM certificates WHERE event_id = :event_id");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Delete the event
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = :event_id");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Check if the event was deleted
    if ($stmt->rowCount() === 0) {
        throw new Exception('Event not found or already deleted.');
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Event deleted successfully.'
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