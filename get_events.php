<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Debug logging
error_log('Session data in get_events: ' . print_r($_SESSION, true));

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please log in to view events');
    }

    // Get all events with registration count and check if user is registered
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            COUNT(DISTINCT er.id) as current_volunteers,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND user_id = ?) as is_registered
        FROM events e 
        LEFT JOIN event_registrations er ON e.id = er.event_id 
        GROUP BY e.id 
        ORDER BY e.event_date ASC
    ");

    if (!$stmt) {
        error_log('PDO prepare error: ' . print_r($pdo->errorInfo(), true));
        throw new Exception('Failed to prepare statement');
    }

    $result = $stmt->execute([$_SESSION['user_id']]);
    if (!$result) {
        error_log('PDO execute error: ' . print_r($stmt->errorInfo(), true));
        throw new Exception('Failed to execute statement');
    }

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate available spots and format dates
    foreach ($events as &$event) {
        $event['available_spots'] = max(0, $event['max_volunteers'] - $event['current_volunteers']);
        $event['is_full'] = $event['available_spots'] === 0;
        $event['event_date'] = date('F j, Y', strtotime($event['event_date']));
        $event['is_registered'] = (bool)$event['is_registered'];
    }

    error_log('Events fetched: ' . print_r($events, true));

    echo json_encode([
        'success' => true,
        'events' => $events
    ]);

} catch (Exception $e) {
    error_log('Error in get_events.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 