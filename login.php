<?php
session_start();
require_once 'db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_regenerate_id();
                
                // Store user data in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login time
                $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Log the login action
                logAction($conn, $user['id'], 'login', 'users', $user['id'], null, null);
                
                // Load user preferences
                loadUserPreferences($conn, $user['id']);
                
                // Redirect to dashboard
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
        
        $stmt->close();
    }
}

// Function to log user actions
function logAction($conn, $user_id, $action, $table, $record_id, $old_values, $new_values) {
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'];
    $old_values_json = $old_values ? json_encode($old_values) : null;
    $new_values_json = $new_values ? json_encode($new_values) : null;
    
    $stmt->bind_param("issssss", $user_id, $action, $table, $record_id, $old_values_json, $new_values_json, $ip);
    $stmt->execute();
    $stmt->close();
}

// Function to load user preferences
function loadUserPreferences($conn, $user_id) {
    $stmt = $conn->prepare("SELECT theme, dashboard_widgets FROM user_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $prefs = $result->fetch_assoc();
        $_SESSION['theme'] = $prefs['theme'];
        $_SESSION['dashboard_widgets'] = $prefs['dashboard_widgets'];
    } else {
        // Create default preferences
        $theme = 'light';
        $widgets = json_encode(['sales', 'inventory', 'recent_orders', 'low_stock']);
        
        $stmt = $conn->prepare("INSERT INTO user_preferences (user_id, theme, dashboard_widgets) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $theme, $widgets);
        $stmt->execute();
        
        $_SESSION['theme'] = $theme;
        $_SESSION['dashboard_widgets'] = $widgets;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingagi ERP - Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow-lg" style="max-width: 400px; width: 100%;">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="fas fa-cash-register fa-3x text-primary mb-3"></i>
                    <h2 class="fw-bold">Ingagi ERP</h2>
                    <p class="text-muted">Sign in to your account</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3 bg-light">
                <p class="mb-0">Default credentials: admin / admin123</p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
