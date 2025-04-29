<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $id = intval($_POST['id']);
    $name = sanitize($conn, $_POST['name']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $category = sanitize($conn, $_POST['category']);
    $current_image = isset($_POST['current_image']) ? $_POST['current_image'] : null;
    
    // Handle file upload
    $image_path = $current_image;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "assets/images/products/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (limit to 5MB)
            if ($_FILES["image"]["size"] > 5000000) {
                $_SESSION['message'] = showAlert("Sorry, your file is too large.", "danger");
                header("Location: index.php");
                exit;
            }
            
            // Allow certain file formats
            if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif") {
                $_SESSION['message'] = showAlert("Sorry, only JPG, JPEG, PNG & GIF files are allowed.", "danger");
                header("Location: index.php");
                exit;
            }
            
            // Upload file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Delete old image if exists
                if (!empty($current_image) && file_exists($current_image)) {
                    unlink($current_image);
                }
                $image_path = $target_file;
            } else {
                $_SESSION['message'] = showAlert("Sorry, there was an error uploading your file.", "danger");
                header("Location: index.php");
                exit;
            }
        } else {
            $_SESSION['message'] = showAlert("File is not an image.", "danger");
            header("Location: index.php");
            exit;
        }
    }
    
    // Update product in database
    $stmt = $conn->prepare("UPDATE products SET name=?, price=?, quantity=?, category=?, image=? WHERE id=?");
    $stmt->bind_param("sdissi", $name, $price, $quantity, $category, $image_path, $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = showAlert("Product updated successfully!");
    } else {
        $_SESSION['message'] = showAlert("Error: " . $stmt->error, "danger");
    }
    
    $stmt->close();
    
    // Redirect back to index page
    header("Location: index.php");
    exit;
} else {
    // Handle AJAX request to get product details
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            echo json_encode($product);
        } else {
            echo json_encode(['error' => 'Product not found']);
        }
        
        $stmt->close();
        exit;
    }
}
?>