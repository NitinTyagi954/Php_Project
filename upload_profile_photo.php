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

    // Check if file was uploaded without errors
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    $file = $_FILES['profile_photo'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and GIF images are allowed.');
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds the maximum limit (5MB).');
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/profile_photos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = $user_id . '_' . time() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Failed to save uploaded file.');
    }
    
    // Get previous profile photo if exists
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $prev_photo = $stmt->fetchColumn();
    
    // Update profile photo in database
    $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
    if (!$stmt->execute([$filename, $user_id])) {
        // Delete uploaded file if database update fails
        unlink($target_path);
        throw new Exception('Failed to update profile photo in database.');
    }
    
    // Delete previous profile photo if exists
    if ($prev_photo && file_exists($upload_dir . $prev_photo)) {
        unlink($upload_dir . $prev_photo);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile photo updated successfully',
        'photo_url' => $target_path
    ]);
    
} catch (Exception $e) {
    error_log('Error in upload_profile_photo.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 