<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $user_id = $_SESSION['user_id'];

    // Get user certificates
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.certificate_name,
            c.issue_date,
            c.hours_earned,
            e.title as event_title
        FROM certificates c
        LEFT JOIN events e ON c.event_id = e.id
        WHERE c.user_id = ?
        ORDER BY c.issue_date DESC
    ");
    
    $stmt->execute([$user_id]);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'certificates' => $certificates
    ]);

} catch (Exception $e) {
    error_log('Error in get_certificates.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 