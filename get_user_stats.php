<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Get user statistics
    $stmt = $pdo->prepare("
        SELECT 
            total_hours,
            impact_score,
            (SELECT COUNT(*) FROM certificates WHERE user_id = ?) as certificates
        FROM users 
        WHERE id = ?
    ");
    
    $stmt->execute([$userId, $userId]);
    $stats = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_hours' => (int)($stats['total_hours'] ?? 0),
            'impact_score' => (int)($stats['impact_score'] ?? 0),
            'certificates' => (int)($stats['certificates'] ?? 0)
        ]
    ]);
} catch (PDOException $e) {
    error_log("Error getting user stats: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>