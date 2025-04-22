<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    // Get user details
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, phone, created_at, profile_photo
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Add photo URL if profile photo exists
    if ($user['profile_photo']) {
        $user['profile_photo_url'] = 'uploads/profile_photos/' . $user['profile_photo'];
    } else {
        $user['profile_photo_url'] = null;
    }

    // Get user statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT er.event_id) as total_events,
            SUM(TIMESTAMPDIFF(HOUR, e.start_time, e.end_time)) as total_hours
        FROM event_registrations er
        JOIN events e ON er.event_id = e.id
        WHERE er.user_id = ? AND er.status = 'registered'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get user certificates
    $stmt = $pdo->prepare("
        SELECT c.id, e.title as event_title, c.issue_date
        FROM certificates c
        JOIN events e ON c.event_id = e.id
        WHERE c.user_id = ?
        ORDER BY c.issue_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'user' => $user,
        'stats' => [
            'total_events' => $stats['total_events'] ?? 0,
            'total_hours' => $stats['total_hours'] ?? 0
        ],
        'certificates' => $certificates
    ]);

} catch (Exception $e) {
    error_log("Error in get_profile.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load profile data'
    ]);
} 