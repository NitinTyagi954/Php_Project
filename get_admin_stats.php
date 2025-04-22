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
    
    // Get total number of users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();
    
    // Get total number of events
    $stmt = $pdo->query("SELECT COUNT(*) FROM events");
    $total_events = $stmt->fetchColumn();
    
    // Get total number of pending approvals
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_registrations WHERE status = 'pending'");
    $pending_approvals = $stmt->fetchColumn();
    
    // Get upcoming events count (events that haven't happened yet)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM events 
        WHERE event_date >= CURDATE()
    ");
    $upcoming_events = $stmt->fetchColumn();
    
    // Get total event registrations
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_registrations");
    $total_registrations = $stmt->fetchColumn();
    
    // Get total hours contributed (if you track this in your system)
    $stmt = $pdo->query("SELECT SUM(total_hours) FROM users");
    $total_hours = $stmt->fetchColumn() ?: 0;
    
    echo json_encode([
        'success' => true,
        'total_users' => $total_users,
        'total_events' => $total_events,
        'pending_approvals' => $pending_approvals,
        'upcoming_events' => $upcoming_events,
        'total_registrations' => $total_registrations,
        'total_hours' => $total_hours
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 