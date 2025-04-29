<?php
require_once 'db.php';

// Create users table for role-based access control
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'manager', 'cashier', 'inventory') NOT NULL DEFAULT 'cashier',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating users table: " . $conn->error);
}

// Create default admin user
$admin_username = "admin";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$admin_email = "admin@ingagi.com";
$admin_role = "admin";

$stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $admin_username, $admin_password, $admin_email, $admin_role);
$stmt->execute();
$stmt->close();

// Create locations table for multi-location inventory
$sql = "CREATE TABLE IF NOT EXISTS locations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating locations table: " . $conn->error);
}

// Add default location
$stmt = $conn->prepare("INSERT IGNORE INTO locations (id, name) VALUES (1, 'Main Store')");
$stmt->execute();
$stmt->close();

// Add location_id to products table
$sql = "SHOW COLUMNS FROM products LIKE 'location_id'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE products ADD COLUMN location_id INT(11) DEFAULT 1";
    if ($conn->query($sql) !== TRUE) {
        die("Error adding location_id to products table: " . $conn->error);
    }
    
    $sql = "ALTER TABLE products ADD CONSTRAINT fk_product_location FOREIGN KEY (location_id) REFERENCES locations(id)";
    $conn->query($sql);
}

// Create customers table for loyalty program
$sql = "CREATE TABLE IF NOT EXISTS customers (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    loyalty_points INT DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating customers table: " . $conn->error);
}

// Create orders table for tracking sales
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    customer_id INT(11) NULL,
    user_id INT(11) NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT 'cash',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating orders table: " . $conn->error);
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating order_items table: " . $conn->error);
}

// Create audit_log table
$sql = "CREATE TABLE IF NOT EXISTS audit_log (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT(11) NULL,
    old_values TEXT NULL,
    new_values TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating audit_log table: " . $conn->error);
}

// Create user_preferences table for dashboard widgets and theme settings
$sql = "CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT(11) PRIMARY KEY,
    theme VARCHAR(20) DEFAULT 'light',
    dashboard_widgets TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating user_preferences table: " . $conn->error);
}

// Create suppliers table
$sql = "CREATE TABLE IF NOT EXISTS suppliers (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating suppliers table: " . $conn->error);
}

// Create purchase_orders table
$sql = "CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT(11) NOT NULL,
    user_id INT(11) NULL,
    status ENUM('draft', 'sent', 'received', 'cancelled') DEFAULT 'draft',
    total_amount DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating purchase_orders table: " . $conn->error);
}

// Create purchase_order_items table
$sql = "CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating purchase_order_items table: " . $conn->error);
}

// Add reorder_level and preferred_supplier to products table
$sql = "SHOW COLUMNS FROM products LIKE 'reorder_level'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE products 
            ADD COLUMN reorder_level INT(11) DEFAULT 10,
            ADD COLUMN preferred_supplier_id INT(11) NULL,
            ADD CONSTRAINT fk_product_supplier FOREIGN KEY (preferred_supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL";
    if ($conn->query($sql) !== TRUE) {
        die("Error updating products table: " . $conn->error);
    }
}

echo "Database updated successfully!";
?>
