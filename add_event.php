<?php
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug session
error_log("SESSION DATA IN ADD_EVENT: " . print_r($_SESSION, true));

// Check if user is logged in and is an admin (check both role variables)
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || 
            (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!isset($_SESSION['user_id']) || !$is_admin) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin privileges required.',
        'session_data' => $_SESSION // Include session data for debugging
    ]);
    exit;
}

// Include the database configuration
require_once 'config.php';

// Validate and sanitize inputs
$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
$event_date = filter_input(INPUT_POST, 'event_date', FILTER_SANITIZE_STRING);
$start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
$end_time = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
$location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
$category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
$max_volunteers = filter_input(INPUT_POST, 'max_volunteers', FILTER_VALIDATE_INT);
$requires_approval = filter_input(INPUT_POST, 'requires_approval', FILTER_VALIDATE_INT) ? 1 : 0;

// Check if all required fields are provided
if (!$title || !$description || !$event_date || !$start_time || !$end_time || !$location || !$category || !$max_volunteers) {
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required.'
    ]);
    exit;
}

// Validate date and time format
$date_pattern = '/^\d{4}-\d{2}-\d{2}$/'; // YYYY-MM-DD
$time_pattern = '/^\d{2}:\d{2}$/'; // HH:MM

if (!preg_match($date_pattern, $event_date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid date format. Use YYYY-MM-DD.'
    ]);
    exit;
}

if (!preg_match($time_pattern, $start_time) || !preg_match($time_pattern, $end_time)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid time format. Use HH:MM.'
    ]);
    exit;
}

// Validate max_volunteers
if ($max_volunteers <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Maximum participants must be greater than 0.'
    ]);
    exit;
}

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insert event into the database
    $stmt = $pdo->prepare("
        INSERT INTO events (
            title, description, event_date, start_time, end_time, 
            location, category, max_volunteers, requires_approval
        ) VALUES (
            :title, :description, :event_date, :start_time, :end_time, 
            :location, :category, :max_volunteers, :requires_approval
        )
    ");
    
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':event_date', $event_date);
    $stmt->bindParam(':start_time', $start_time);
    $stmt->bindParam(':end_time', $end_time);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':max_volunteers', $max_volunteers, PDO::PARAM_INT);
    $stmt->bindParam(':requires_approval', $requires_approval, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $eventId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Event created successfully.',
        'event_id' => $eventId
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 