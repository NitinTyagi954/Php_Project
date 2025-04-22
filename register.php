<?php
ob_start(); // Start output buffering
header('Content-Type: application/json');
require_once 'config.php';

// Disable error display in production
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Log received data for debugging
error_log("Received registration data: " . print_r($data, true));

// Validate required fields
if (empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Full name, email, and password are required']);
    exit;
}

// Validate email format
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate phone number (if provided)
if (!empty($data['phone']) && !preg_match('/^\+?[\d\s-]{7,15}$/', $data['phone'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

// Sanitize full_name
$full_name = htmlspecialchars(trim($data['full_name']), ENT_QUOTES, 'UTF-8');

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }

    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([
        $full_name,
        $data['email'],
        $data['phone'] ?: null,
        $hashed_password
    ]);

    if ($result) {
        // Optional: Auto-login after registration (uncomment to enable)
        /*
        session_start();
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['last_activity'] = time();
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'redirect' => 'dashboard.html'
        ]);
        */
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        throw new Exception("Failed to insert user into database");
    }
} catch (PDOException $e) {
    error_log("Database error during registration: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error during registration: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

ob_end_flush(); // End output buffering and send the response
?>