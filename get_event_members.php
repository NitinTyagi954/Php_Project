<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get event ID from GET parameter
    $event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
    if (!$event_id) {
        throw new Exception('Invalid event ID');
    }

    // Get registered members for the event
    $stmt = $pdo->prepare("
        SELECT 
            u.full_name,
            u.email,
            er.registration_date,
            er.status
        FROM event_registrations er
        JOIN users u ON er.user_id = u.id
        WHERE er.event_id = ? 
        AND er.status != 'cancelled'
        ORDER BY er.registration_date ASC
    ");

    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $pdo->errorInfo()[2]);
    }

    $result = $stmt->execute([$event_id]);
    if (!$result) {
        throw new Exception('Failed to execute statement: ' . $stmt->errorInfo()[2]);
    }

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter out cancelled registrations
    $active_members = array_filter($members, function($member) {
        return $member['status'] === 'registered';
    });

    echo json_encode([
        'success' => true,
        'members' => array_values($active_members),
        'total_members' => count($active_members)
    ]);

} catch (Exception $e) {
    error_log('Error in get_event_members.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 