<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Debug logging
error_log('Session data in get_user_events: ' . print_r($_SESSION, true));

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $user_id = $_SESSION['user_id'];
    error_log('Fetching events for user ID: ' . $user_id);

    // Get user's registered events with event details
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.title,
            e.description,
            e.event_date,
            e.start_time,
            e.end_time,
            e.location,
            er.status,
            er.registration_date
        FROM event_registrations er
        JOIN events e ON er.event_id = e.id
        WHERE er.user_id = ?
        ORDER BY e.event_date ASC
    ");

    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $pdo->errorInfo()[2]);
    }

    $result = $stmt->execute([$user_id]);
    if (!$result) {
        throw new Exception('Failed to execute statement: ' . $stmt->errorInfo()[2]);
    }

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log('Fetched events: ' . print_r($events, true));

    // Calculate total hours and days
    $total_hours = 0;
    $total_days = 0;
    $total_events = 0;
    
    foreach ($events as $event) {
        if ($event['status'] === 'registered' || $event['status'] === 'attended') {
            $start = strtotime($event['start_time']);
            $end = strtotime($event['end_time']);
            $hours = ($end - $start) / 3600;
            $total_hours += $hours;
            $total_days += 1;
            $total_events += 1;
        }
    }

    echo json_encode([
        'success' => true,
        'events' => $events,
        'stats' => [
            'total_events' => $total_events,
            'total_hours' => round($total_hours, 1),
            'total_days' => $total_days
        ]
    ]);

} catch (Exception $e) {
    error_log('Error in get_user_events.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 