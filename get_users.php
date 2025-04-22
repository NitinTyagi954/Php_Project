<?php
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin (check both role variables)
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || 
            (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!isset($_SESSION['user_id']) || !$is_admin) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin privileges required.'
    ]);
    exit;
}

// Include the database configuration
require_once '../includes/config.php';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch all users with their event participation counts and additional stats
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.full_name,
            u.email,
            u.phone,
            u.role,
            u.total_hours,
            u.impact_score,
            u.created_at,
            (SELECT COUNT(*) FROM event_registrations er WHERE er.user_id = u.id) as events_count,
            (SELECT COUNT(*) FROM event_registrations er WHERE er.user_id = u.id AND er.status = 'attended') as attended_count
        FROM users u
        ORDER BY u.full_name ASC
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log how many users were found (for debugging)
    error_log("Found " . count($users) . " users in get_users.php");
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (PDOException $e) {
    error_log("Error in get_users.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 