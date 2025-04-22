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
require_once 'config.php';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch all pending registrations with user and event details
    $stmt = $pdo->query("
        SELECT er.id, er.registration_date, u.full_name as user_name, e.title as event_title, e.id as event_id
        FROM event_registrations er
        JOIN users u ON er.user_id = u.id
        JOIN events e ON er.event_id = e.id
        WHERE er.status = 'pending'
        ORDER BY er.registration_date ASC
    ");
    
    $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'approvals' => $approvals
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 