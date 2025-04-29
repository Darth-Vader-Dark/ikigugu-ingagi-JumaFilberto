<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $name = sanitize($conn, $_POST['name']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $category = sanitize($conn, $_POST['category']);
    
    // Handle file upload
    $image_path = null;
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
    
    // Insert product into database
    $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, category, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdiss", $name, $price, $quantity, $category, $image_path);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = showAlert("Product added successfully!");
    } else {
        $_SESSION['message'] = showAlert("Error: " . $stmt->error, "danger");
    }
    
    $stmt->close();
    
    // Redirect back to index page
    header("Location: index.php");
    exit;
}
?>