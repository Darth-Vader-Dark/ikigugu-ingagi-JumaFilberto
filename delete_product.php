<?php
session_start();
require_once 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get product image path before deleting
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $image_path = $product['image'];
        
        // Delete the product from database
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            // Delete the image file if it exists
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }
            
            $_SESSION['message'] = showAlert("Product deleted successfully!");
        } else {
            $_SESSION['message'] = showAlert("Error deleting product: " . $delete_stmt->error, "danger");
        }
        
        $delete_stmt->close();
    } else {
        $_SESSION['message'] = showAlert("Product not found.", "warning");
    }
    
    $stmt->close();
    
    // Redirect back to index page
    header("Location: index.php");
    exit;
} else {
    $_SESSION['message'] = showAlert("Invalid request.", "danger");
    header("Location: index.php");
    exit;
}
?>
