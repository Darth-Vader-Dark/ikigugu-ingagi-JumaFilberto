<?php
session_start();
require_once 'db.php';

// Check if user is logged in (if authentication is enabled)
$auth_enabled = true; // Set to false if you want to disable authentication temporarily
if ($auth_enabled && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get total products count
$sql = "SELECT COUNT(*) as total FROM products";
$result = $conn->query($sql);
$total_products = $result->num_rows > 0 ? $result->fetch_assoc()['total'] : 0;

// Get total stock value
$sql = "SELECT SUM(price * quantity) as total_value FROM products";
$result = $conn->query($sql);
$total_value = $result->num_rows > 0 ? $result->fetch_assoc()['total_value'] : 0;

// Get low stock products (less than 10 items)
$sql = "SELECT COUNT(*) as low_stock FROM products WHERE quantity < 10";
$result = $conn->query($sql);
$low_stock = $result->num_rows > 0 ? $result->fetch_assoc()['low_stock'] : 0;

// Handle search and sorting
$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($conn, $_GET['sort']) : 'name';
$order = isset($_GET['order']) ? sanitize($conn, $_GET['order']) : 'ASC';

// Build the query
$query = "SELECT * FROM products";
if (!empty($search)) {
    $query .= " WHERE name LIKE '%$search%' OR category LIKE '%$search%'";
}
$query .= " ORDER BY $sort $order";

// Execute the query
$result = $conn->query($query);

// Get user role (if authentication is enabled)
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingagi ERP - Dashboard</title>
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-box"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="badge bg-danger cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
                        </a>
                    </li>
                    <?php if (in_array($user_role, ['admin', 'manager'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="loyalty.php">
                            <i class="fas fa-medal"></i> Loyalty
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($auth_enabled): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog"></i> Management
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <?php if (in_array($user_role, ['admin', 'manager'])): ?>
                            <li><a class="dropdown-item" href="users.php"><i class="fas fa-users me-2"></i>Users</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="categories.php"><i class="fas fa-tags me-2"></i>Categories</a></li>
                            <?php if (in_array($user_role, ['admin', 'manager'])): ?>
                            <li><a class="dropdown-item" href="suppliers.php"><i class="fas fa-truck me-2"></i>Suppliers</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex">
                    <button id="theme-toggle" class="btn btn-outline-light me-2">
                        <i class="fas fa-moon"></i>
                    </button>
                    <?php if ($auth_enabled): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i> <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest'; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
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

        <!-- Statistics Cards -->
        <div class="row animate__animated animate__fadeIn">
            <div class="col-md-4 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Total Products</h6>
                                <h1><?php echo $total_products; ?></h1>
                            </div>
                            <i class="fas fa-box-open fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Total Stock Value</h6>
                                <h1>$<?php echo number_format($total_value, 2); ?></h1>
                            </div>
                            <i class="fas fa-dollar-sign fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Low Stock Items</h6>
                                <h1><?php echo $low_stock; ?></h1>
                            </div>
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Add Product -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form action="" method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo $search; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> Add Product
                </button>
                <a href="export.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-file-export"></i> Export
                </a>
                <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#scannerModal">
                    <i class="fas fa-qrcode"></i> Scan
                </button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card animate__animated animate__fadeIn">
            <div class="card-header bg-light">
                <h5 class="mb-0">Products List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>
                                    <a href="?sort=name&order=<?php echo $sort == 'name' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo $search; ?>">
                                        Name
                                        <?php if ($sort == 'name'): ?>
                                            <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=price&order=<?php echo $sort == 'price' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo $search; ?>">
                                        Price
                                        <?php if ($sort == 'price'): ?>
                                            <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=quantity&order=<?php echo $sort == 'quantity' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo $search; ?>">
                                        Quantity
                                        <?php if ($sort == 'quantity'): ?>
                                            <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort=category&order=<?php echo $sort == 'category' && $order == 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo $search; ?>">
                                        Category
                                        <?php if ($sort == 'category'): ?>
                                            <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>";
                                    if (!empty($row['image']) && file_exists($row['image'])) {
                                        echo "<img src='" . $row['image'] . "' alt='" . $row['name'] . "' class='product-thumbnail'>";
                                    } else {
                                        echo "<img src='assets/images/no-image.jpg' alt='No Image' class='product-thumbnail'>";
                                    }
                                    echo "</td>";
                                    echo "<td>" . $row['name'] . "</td>";
                                    echo "<td>$" . number_format($row['price'], 2) . "</td>";
                                    echo "<td>" . $row['quantity'] . "</td>";
                                    echo "<td>" . $row['category'] . "</td>";
                                    echo "<td>
                                            <button class='btn btn-sm btn-primary edit-btn' data-id='" . $row['id'] . "'>
                                                <i class='fas fa-edit'></i>
                                            </button>
                                            <button class='btn btn-sm btn-danger delete-btn' data-id='" . $row['id'] . "' data-name='" . htmlspecialchars($row['name'], ENT_QUOTES) . "'>
                                                <i class='fas fa-trash'></i>
                                            </button>
                                            <button class='btn btn-sm btn-success add-to-cart' data-id='" . $row['id'] . "' data-name='" . htmlspecialchars($row['name'], ENT_QUOTES) . "' data-price='" . $row['price'] . "'>
                                                <i class='fas fa-cart-plus'></i>
                                            </button>
                                            <button class='btn btn-sm btn-info qr-btn' data-id='" . $row['id'] . "' data-name='" . htmlspecialchars($row['name'], ENT_QUOTES) . "'>
                                                <i class='fas fa-qrcode'></i>
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No products found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_product.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <?php
                                $cat_query = "SELECT name FROM categories ORDER BY name";
                                $cat_result = $conn->query($cat_query);
                                if ($cat_result && $cat_result->num_rows > 0) {
                                    while ($cat = $cat_result->fetch_assoc()) {
                                        echo "<option value='" . $cat['name'] . "'>" . $cat['name'] . "</option>";
                                    }
                                } else {
                                    echo "<option value='Other'>Other</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reorder_level" class="form-label">Reorder Level</label>
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" value="10">
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="edit_product.php" method="POST" enctype="multipart/form-data" id="editProductForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price" class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">Category</label>
                            <select class="form-select" id="edit_category" name="category" required>
                                <?php
                                if ($cat_result && $cat_result->num_rows > 0) {
                                    $cat_result->data_seek(0); // Reset the result pointer
                                    while ($cat = $cat_result->fetch_assoc()) {
                                        echo "<option value='" . $cat['name'] . "'>" . $cat['name'] . "</option>";
                                    }
                                } else {
                                    echo "<option value='Other'>Other</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_reorder_level" class="form-label">Reorder Level</label>
                            <input type="number" class="form-control" id="edit_reorder_level" name="reorder_level" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                            <div id="current_image_container" class="mt-2"></div>
                            <input type="hidden" name="current_image" id="current_image">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <span id="delete_product_name"></span>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirm_delete" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code for <span id="qr_product_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="qr-code mb-3">
                        <img id="qr_image" src="/placeholder.svg" alt="QR Code" class="img-fluid">
                    </div>
                    <p class="text-muted">Scan this code to quickly access product information</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="download_qr" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scanner Modal -->
    <div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Scan QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="scanner-container" style="width: 100%; height: 300px;"></div>
                    <div class="text-center mt-3">
                        <button id="start-scan" class="btn btn-primary">
                            <i class="fas fa-camera"></i> Start Scanner
                        </button>
                        <button id="stop-scan" class="btn btn-secondary" style="display: none;">
                            <i class="fas fa-stop"></i> Stop Scanner
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11"></div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- HTML5-QRCode -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <!-- Custom JavaScript -->
    <script>
        // Set user ID for JavaScript
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
    </script>
    <script src="assets/js/scripts.js"></script>
    
    <script>
        // Initialize edit product functionality
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    
                    // Fetch product details
                    fetch(`get_product.php?id=${productId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const product = data.product;
                                
                                // Populate form fields
                                document.getElementById('edit_id').value = product.id;
                                document.getElementById('edit_name').value = product.name;
                                document.getElementById('edit_price').value = product.price;
                                document.getElementById('edit_quantity').value = product.quantity;
                                document.getElementById('edit_category').value = product.category;
                                document.getElementById('edit_reorder_level').value = product.reorder_level || 10;
                                document.getElementById('current_image').value = product.image || '';
                                
                                // Show current image if exists
                                const imageContainer = document.getElementById('current_image_container');
                                if (product.image) {
                                    imageContainer.innerHTML = `<img src="${product.image}" alt="${product.name}" class="img-thumbnail" style="max-height: 100px;">`;
                                } else {
                                    imageContainer.innerHTML = '';
                                }
                                
                                // Show modal
                                const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                                editModal.show();
                            } else {
                                showToast('Error loading product details', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching product details:', error);
                            showToast('Error loading product details', 'error');
                        });
                });
            });
            
            // Download QR code
            const downloadQrBtn = document.getElementById('download_qr');
            if (downloadQrBtn) {
                downloadQrBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const qrImage = document.getElementById('qr_image');
                    const productName = document.getElementById('qr_product_name').textContent;
                    
                    // Create a temporary link
                    const link = document.createElement('a');
                    link.href = qrImage.src;
                    link.download = `qr-${productName.replace(/\s+/g, '-').toLowerCase()}.png`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            }
        });
    </script>
</body>
</html>
