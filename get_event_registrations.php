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

// Get the event ID from query parameters
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

if (!$event_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Event ID is required.'
    ]);
    exit;
}

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch registrations for the specified event with user details
    $stmt = $pdo->prepare("
        SELECT er.id, er.registration_date, er.status, u.full_name, u.email, u.phone
        FROM event_registrations er
        JOIN users u ON er.user_id = u.id
        WHERE er.event_id = :event_id
        ORDER BY er.registration_date DESC
    ");
    
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'registrations' => $registrations
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 