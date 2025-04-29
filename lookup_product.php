<?php
session_start();
require_once 'db.php';

// Check if code is provided
if (!isset($_GET['code'])) {
    echo json_encode(['success' => false, 'message' => 'QR code is required']);
    exit;
}

$code = $_GET['code'];

// Try to decode the QR code content
try {
    $data = json_decode($code, true);
    
    // If the QR code contains a product ID
    if (isset($data['id'])) {
        $product_id = intval($data['id']);
        
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
        
        // Return product details
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid QR code format']);
    }
} catch (Exception $e) {
    // If the QR code is not in JSON format, try to find product by name or code
    $search = sanitize($conn, $code);
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR id = ? LIMIT 1");
    $search_param = "%$search%";
    $id_param = is_numeric($search) ? intval($search) : 0;
    $stmt->bind_param("si", $search_param, $id_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Return product details
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
}
?>
