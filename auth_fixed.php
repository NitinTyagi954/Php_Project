<?php
ob_start(); // Start output buffering
header('Content-Type: application/json');
session_start();
require_once 'config.php';

// Disable error display in production
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$remember = $data['remember'] ?? false;
$requestedRole = $data['role'] ?? 'user';

// Log received data for debugging
error_log("Received login data: " . print_r($data, true));

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

try {
    // First check if the email exists
    $stmt = $pdo->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Determine if the user exists by checking if $user is not false
    $emailExists = ($user !== false);

    if ($emailExists && password_verify($password, $user['password'])) {
        // Check if user has the requested role
        if ($requestedRole == 'admin' && $user['role'] != 'admin') {
            echo json_encode(['success' => false, 'message' => 'You do not have admin privileges']);
            exit;
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
            
            // Set secure cookie with proper attributes
            setcookie('remember_token', $token, [
                'expires' => $expires,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        // Determine redirect URL based on role - FIXED PATH FOR ADMIN
        $redirect = ($user['role'] == 'admin') ? 'admin/admin-dashboard.html' : 'dashboard.html';

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $redirect,
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $email,
                'role' => $user['role']
            ]
        ]);
    } else {
        // Custom error messages
        if (!$emailExists) {
            echo json_encode([
                'success' => false, 
                'message' => 'No account found with this email. Please sign up first.',
                'should_register' => true
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid password. Please try again or reset your password.'
            ]);
        }
    }
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Login error: ' . $e->getMessage()
    ]);
}

ob_end_flush(); // End output buffering and send the response

function logout() {
    session_destroy();
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, "/");
    }
}

function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    if (isset($_COOKIE['remember_token'])) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW()");
            $stmt->execute([$_COOKIE['remember_token']]);
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                return true;
            }
        } catch(PDOException $e) {
            error_log("Remember token error: " . $e->getMessage());
        }
    }
    
    return false;
}

function getUserStats($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.total_hours,
                u.impact_score,
                (SELECT COUNT(*) FROM event_registrations WHERE user_id = ? AND status = 'registered') as upcoming_events,
                (SELECT COUNT(*) FROM event_registrations WHERE user_id = ? AND status = 'attended') as completed_events,
                (SELECT COUNT(*) FROM certificates WHERE user_id = ?) as certificates
            FROM users u
            WHERE u.id = ?
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Get user stats error: " . $e->getMessage());
        return false;
    }
}
?>