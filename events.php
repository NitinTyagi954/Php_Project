<?php
require_once '../auth.php';
require_once '../events.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $events = getUpcomingEvents();
    if ($events === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch events']);
    } else {
        echo json_encode($events);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['event_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Event ID is required']);
        exit;
    }

    $additionalInfo = isset($data['additional_info']) ? $data['additional_info'] : '';
    
    if (registerForEvent($_SESSION['user_id'], $data['event_id'], $additionalInfo)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to register for event']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 