<?php
session_start();

// Log the logout action if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'db.php';
    
    // Log the logout action
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, ip_address) VALUES (?, 'logout', 'users', ?)");
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("is", $_SESSION['user_id'], $ip);
    $stmt->execute();
    $stmt->close();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>
