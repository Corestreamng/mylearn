<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /index.php');
        exit();
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /index.php');
        exit();
    }
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'full_name' => $_SESSION['full_name'],
        'user_type' => $_SESSION['user_type']
    ];
}

// Log activity
function logActivity($user_id, $action, $description = '') {
    $conn = getDBConnection();
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
    header('Location: /index.php');
    exit();
}
?>
