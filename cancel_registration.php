<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get event ID from POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $event_id = $data['event_id'] ?? null;
    if (!$event_id) {
        throw new Exception('Event ID is required');
    }

    $user_id = $_SESSION['user_id'];

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Check if registration exists and get event details
        $stmt = $pdo->prepare("
            SELECT er.status, e.start_time, e.end_time 
            FROM event_registrations er
            JOIN events e ON er.event_id = e.id
            WHERE er.user_id = ? AND er.event_id = ?
        ");
        $stmt->execute([$user_id, $event_id]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registration) {
            throw new Exception('Registration not found');
        }

        if ($registration['status'] === 'cancelled') {
            throw new Exception('Registration is already cancelled');
        }
        
        // Only deduct hours if status was registered (not pending)
        if ($registration['status'] === 'registered') {
            // Calculate hours for this event
            $start_time = strtotime($registration['start_time']);
            $end_time = strtotime($registration['end_time']);
            $hours_for_event = ($end_time - $start_time) / 3600;
            
            // Update user's statistics to deduct hours and impact score
            $stmt = $pdo->prepare("
                UPDATE users 
                SET total_hours = GREATEST(0, total_hours - ?),
                    impact_score = GREATEST(0, impact_score - ROUND(? * 10))
                WHERE id = ?
            ");
            $stmt->execute([$hours_for_event, $hours_for_event, $user_id]);
            
            // Log the updated statistics
            error_log("Updated user $user_id statistics: deducted $hours_for_event hours and impact score.");
            
            // Decrement current_volunteers in events
            $stmt = $pdo->prepare("
                UPDATE events 
                SET current_volunteers = GREATEST(0, current_volunteers - 1)
                WHERE id = ?
            ");
            $stmt->execute([$event_id]);
        }

        // Update registration status
        $stmt = $pdo->prepare("
            UPDATE event_registrations 
            SET status = 'cancelled' 
            WHERE user_id = ? AND event_id = ?
        ");
        $stmt->execute([$user_id, $event_id]);

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Registration cancelled successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error in cancel_registration.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 