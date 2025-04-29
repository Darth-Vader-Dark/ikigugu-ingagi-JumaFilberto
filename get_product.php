<?php
session_start();
require_once 'db.php';

// Check if user is logged in (if authentication is enabled)
$auth_enabled = true; // Set to false if you want to disable authentication temporarily
if ($auth_enabled && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = intval($_GET['id']);

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();

// Return product details as JSON
echo json_encode([
    'success' => true,
    'product' => $product
]);
?>
