<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get the event ID from the request
$data = json_decode(file_get_contents('php://input'), true);
$eventId = $data['event_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$eventId) {
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // First, check if the registration exists at all
    $checkExistenceStmt = $pdo->prepare("SELECT id, status FROM event_registrations WHERE user_id = ? AND event_id = ?");
    $checkExistenceStmt->execute([$userId, $eventId]);
    $registration = $checkExistenceStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        throw new Exception("No registration found for this event");
    }

    if ($registration['status'] !== 'registered') {
        throw new Exception("Registration is already in status: " . $registration['status']);
    }

    // Update the registration status to 'cancelled'
    $updateStmt = $pdo->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE user_id = ? AND event_id = ?");
    $updateStmt->execute([$userId, $eventId]);

    // Decrease the current_volunteers count in the events table
    $decreaseStmt = $pdo->prepare("UPDATE events SET current_volunteers = current_volunteers - 1 WHERE id = ?");
    $decreaseStmt->execute([$eventId]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Event registration cancelled successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error in cancel_event.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 