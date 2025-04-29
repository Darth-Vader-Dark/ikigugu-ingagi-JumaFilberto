<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();

}
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ingagi_erp";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create products table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT(11) NOT NULL,
    category VARCHAR(100) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Create categories table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating categories table: " . $conn->error);
}

// Insert default categories if they don't exist
$default_categories = ['Electronics', 'Clothing', 'Food', 'Beverages', 'Household', 'Other'];
foreach ($default_categories as $category) {
    $stmt = $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $stmt->close();
}

// Function to sanitize input data
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Function to display alert messages
function showAlert($message, $type = 'success') {
    return "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
}
?>