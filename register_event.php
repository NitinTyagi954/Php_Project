<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

// Debug logging
error_log('Session data in register_event: ' . print_r($_SESSION, true));

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    error_log('Received registration data: ' . print_r($data, true));

    if (!$data) {
        throw new Exception('Invalid request data');
    }

    $event_id = $data['event_id'] ?? null;
    if (!$event_id) {
        throw new Exception('Event ID is required');
    }

    $user_id = $_SESSION['user_id'];

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Check if event exists and has available spots
        $stmt = $pdo->prepare("
            SELECT e.*, 
                   COUNT(er.id) as registered_count
            FROM events e
            LEFT JOIN event_registrations er ON e.id = er.event_id AND er.status IN ('registered', 'attended')
            WHERE e.id = ?
            GROUP BY e.id
        ");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $pdo->errorInfo()[2]);
        }

        $result = $stmt->execute([$event_id]);
        if (!$result) {
            throw new Exception('Failed to execute statement: ' . $stmt->errorInfo()[2]);
        }

        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            throw new Exception('Event not found');
        }

        $available_spots = $event['max_volunteers'] - $event['registered_count'];
        if ($available_spots <= 0) {
            throw new Exception('No spots available for this event');
        }

        // Check if user is already registered
        $stmt = $pdo->prepare("
            SELECT status 
            FROM event_registrations 
            WHERE user_id = ? AND event_id = ?
        ");
        $stmt->execute([$user_id, $event_id]);
        $existing_registration = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_registration) {
            if ($existing_registration['status'] === 'registered' || $existing_registration['status'] === 'pending') {
                // User is already registered or pending
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'You are already registered for this event'
                ]);
                exit;
            } else if ($existing_registration['status'] === 'rejected') {
                throw new Exception('Your registration for this event was previously rejected');
            } else {
                // Update existing registration based on approval requirement
                $new_status = $event['requires_approval'] ? 'pending' : 'registered';
                $stmt = $pdo->prepare("
                    UPDATE event_registrations 
                    SET status = ?, 
                        registration_date = NOW() 
                    WHERE user_id = ? AND event_id = ?
                ");
                $stmt->execute([$new_status, $user_id, $event_id]);
            }
        } else {
            // Create new registration based on approval requirement
            $new_status = $event['requires_approval'] ? 'pending' : 'registered';
            $stmt = $pdo->prepare("
                INSERT INTO event_registrations (user_id, event_id, status, registration_date) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $event_id, $new_status]);
        }

        // Update current_volunteers count only if not requiring approval
        if (!$event['requires_approval']) {
            $stmt = $pdo->prepare("
                UPDATE events 
                SET current_volunteers = current_volunteers + 1 
                WHERE id = ?
            ");
            $stmt->execute([$event_id]);
            
            // Calculate hours for this event and update user statistics
            $start_time = strtotime($event['start_time']);
            $end_time = strtotime($event['end_time']);
            $hours_for_event = ($end_time - $start_time) / 3600;
            
            // Update user's total hours, impact score, and add 1 day
            $stmt = $pdo->prepare("
                UPDATE users 
                SET total_hours = total_hours + ?,
                    impact_score = impact_score + ROUND(? * 10)
                WHERE id = ?
            ");
            $stmt->execute([$hours_for_event, $hours_for_event, $user_id]);
            
            // Log the updated statistics
            error_log("Updated user $user_id statistics: added $hours_for_event hours and impact score.");
        }

        // Commit transaction
        $pdo->commit();

        if ($event['requires_approval']) {
            echo json_encode([
                'success' => true,
                'message' => 'Your registration is pending approval',
                'requires_approval' => true
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Successfully registered for the event',
                'requires_approval' => false
            ]);
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error in register_event.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>