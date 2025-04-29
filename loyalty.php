<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user has permission to access this page
if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    $_SESSION['message'] = showAlert("You don't have permission to access this page", "danger");
    header("Location: index.php");
    exit;
}

// Handle search
$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';
$query = "SELECT * FROM customers";
if (!empty($search)) {
    $query .= " WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%'";
}
$query .= " ORDER BY loyalty_points DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingagi ERP - Customer Loyalty</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cash-register me-2"></i>Ingagi ERP
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="badge bg-danger cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="loyalty.php">
                            <i class="fas fa-medal"></i> Loyalty
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <button id="theme-toggle" class="btn btn-outline-light me-2">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i> <?php echo $_SESSION['username']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <?php
        if (isset($_SESSION['message'])) {
            echo $_SESSION['message'];
            unset($_SESSION['message']);
        }
        ?>

        <!-- Loyalty Program Overview -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Loyalty Program Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-medal fa-2x text-warning mb-2"></i>
                                    <h5>Bronze</h5>
                                    <p class="mb-1">100+ points</p>
                                    <p class="mb-0 text-muted">2% discount</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-medal fa-2x text-secondary mb-2"></i>
                                    <h5>Silver</h5>
                                    <p class="mb-1">500+ points</p>
                                    <p class="mb-0 text-muted">5% discount</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-medal fa-2x text-warning mb-2"></i>
                                    <h5>Gold</h5>
                                    <p class="mb-1">1000+ points</p>
                                    <p class="mb-0 text-muted">10% discount</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-chart-line fa-2x text-primary mb-2"></i>
                                    <h5>Points</h5>
                                    <p class="mb-1">$1 = 1 point</p>
                                    <p class="mb-0 text-muted">Never expires</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Add Customer -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form action="" method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search customers..." value="<?php echo $search; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="fas fa-plus"></i> Add Customer
                </button>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="card animate__animated animate__fadeIn">
            <div class="card-header bg-light">
                <h5 class="mb-0">Customers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Loyalty Points</th>
                                <th>Level</th>
                                <th>Total Spent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Determine loyalty level
                                    $level = 'Standard';
                                    $level_class = 'text-muted';
                                    $icon = 'user';
                                    
                                    if ($row['loyalty_points'] >= 1000) {
                                        $level = 'Gold';
                                        $level_class = 'text-warning';
                                        $icon = 'medal';
                                    } elseif ($row['loyalty_points'] >= 500) {
                                        $level = 'Silver';
                                        $level_class = 'text-secondary';
                                        $icon = 'medal';
                                    } elseif ($row['loyalty_points'] >= 100) {
                                        $level = 'Bronze';
                                        $level_class = 'text-warning';
                                        $icon = 'medal';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td>" . $row['id'] . "</td>";
                                    echo "<td>" . $row['name'] . "</td>";
                                    echo "<td>" . $row['email'] . "</td>";
                                    echo "<td>" . $row['phone'] . "</td>";
                                    echo "<td>" . $row['loyalty_points'] . "</td>";
                                    echo "<td><span class='{$level_class}'><i class='fas fa-{$icon} me-1'></i>{$level}</span></td>";
                                    echo "<td>$" . number_format($row['total_spent'], 2) . "</td>";
                                    echo "<td>
                                            <button class='btn btn-sm btn-primary edit-customer' data-id='" . $row['id'] . "'>
                                                <i class='fas fa-edit'></i>
                                            </button>
                                            <button class='btn btn-sm btn-info view-history' data-id='" . $row['id'] . "'>
                                                <i class='fas fa-history'></i>
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No customers found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_customer.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="loyalty_points" class="form-label">Initial Loyalty Points</label>
                            <input type="number" class="form-control" id="loyalty_points" name="loyalty_points" value="0" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11"></div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/scripts.js"></script>
</body>
</html>
