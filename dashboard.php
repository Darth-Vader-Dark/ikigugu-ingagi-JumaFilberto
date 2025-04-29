<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get dashboard widgets from user preferences
$widgets = isset($_SESSION['dashboard_widgets']) ? json_decode($_SESSION['dashboard_widgets'], true) : ['sales', 'inventory', 'recent_orders', 'low_stock'];

// Get sales data for the last 7 days
$sales_data = [];
$labels = [];

$query = "SELECT DATE(created_at) as date, SUM(total_amount) as total 
          FROM orders 
          WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          GROUP BY DATE(created_at)
          ORDER BY date";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = date('M d', strtotime($row['date']));
        $sales_data[] = $row['total'];
    }
}

// Get top selling products
$top_products = [];
$query = "SELECT p.name, SUM(oi.quantity) as total_sold
          FROM order_items oi
          JOIN products p ON oi.product_id = p.id
          GROUP BY oi.product_id
          ORDER BY total_sold DESC
          LIMIT 5";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $top_products[] = $row;
    }
}

// Get low stock products
$low_stock = [];
$query = "SELECT * FROM products WHERE quantity <= reorder_level ORDER BY quantity ASC LIMIT 5";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $low_stock[] = $row;
    }
}

// Get recent orders
$recent_orders = [];
$query = "SELECT o.*, c.name as customer_name 
          FROM orders o 
          LEFT JOIN customers c ON o.customer_id = c.id 
          ORDER BY o.created_at DESC 
          LIMIT 5";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingagi ERP - Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Sortable.js -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="badge bg-danger cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
                        </a>
                    </li>
                    <?php if (in_array($_SESSION['role'], ['admin', 'manager'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="loyalty.php">
                            <i class="fas fa-medal"></i> Loyalty
                        </a>
                    </li>
                    <?php endif; ?>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard</h2>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#widgetSettingsModal">
                <i class="fas fa-cog"></i> Customize Dashboard
            </button>
        </div>

        <!-- Dashboard Widgets -->
        <div id="widget-container" class="row">
            <!-- Sales Chart Widget -->
            <?php if (in_array('sales', $widgets)): ?>
            <div class="col-md-6 mb-4" data-widget-id="sales">
                <div class="widget">
                    <div class="widget-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Sales Trend</h5>
                        <button class="btn btn-sm btn-outline-secondary widget-settings" data-widget-id="sales">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div class="chart-container">
                            <canvas id="sales-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Inventory Status Widget -->
            <?php if (in_array('inventory', $widgets)): ?>
            <div class="col-md-6 mb-4" data-widget-id="inventory">
                <div class="widget">
                    <div class="widget-header">
                        <h5><i class="fas fa-boxes me-2"></i>Inventory Status</h5>
                        <button class="btn btn-sm btn-outline-secondary widget-settings" data-widget-id="inventory">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div class="chart-container">
                            <canvas id="categories-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Orders Widget -->
            <?php if (in_array('recent_orders', $widgets)): ?>
            <div class="col-md-6 mb-4" data-widget-id="recent_orders">
                <div class="widget">
                    <div class="widget-header">
                        <h5><i class="fas fa-shopping-bag me-2"></i>Recent Orders</h5>
                        <button class="btn btn-sm btn-outline-secondary widget-settings" data-widget-id="recent_orders">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo $order['customer_name'] ?? 'Guest'; ?></td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $status_class = '';
                                            switch ($order['status']) {
                                                case 'completed':
                                                    $status_class = 'text-success';
                                                    break;
                                                case 'pending':
                                                    $status_class = 'text-warning';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'text-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span>
                                        </td>
                                        <td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No recent orders</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Low Stock Widget -->
            <?php if (in_array('low_stock', $widgets)): ?>
            <div class="col-md-6 mb-4" data-widget-id="low_stock">
                <div class="widget">
                    <div class="widget-header">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert</h5>
                        <button class="btn btn-sm btn-outline-secondary widget-settings" data-widget-id="low_stock">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Current Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock as $product): ?>
                                    <tr>
                                        <td><?php echo $product['name']; ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?php echo $product['quantity']; ?></span>
                                        </td>
                                        <td><?php echo $product['reorder_level']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary reorder-btn" data-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-sync-alt"></i> Reorder
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($low_stock)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No low stock items</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Weather-based Recommendations Widget -->
            <?php if (in_array('weather_recommendations', $widgets)): ?>
            <div class="col-md-6 mb-4" data-widget-id="weather_recommendations">
                <div class="widget">
                    <div class="widget-header">
                        <h5><i class="fas fa-cloud-sun me-2"></i>Weather Recommendations</h5>
                        <button class="btn btn-sm btn-outline-secondary widget-settings" data-widget-id="weather_recommendations">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div id="weather-recommendations">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                <p>Loading weather-based recommendations...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Top Selling Products Widget -->
            <?php if (in_array('top_products', $widgets)): ?>
            <div class="col-md-6 mb-4" data-widget-id="top_products">
                <div class="widget">
                    <div class="widget-header">
                        <h5><i class="fas fa-trophy me-2"></i>Top Selling Products</h5>
                        <button class="btn btn-sm btn-outline-secondary widget-settings" data-widget-id="top_products">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Units Sold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['name']; ?></td>
                                        <td><?php echo $product['total_sold']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($top_products)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No sales data available</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- QR Code Scanner Widget -->
            <?php if (in_array('qr_scanner', $widgets)): ?>
            <div class="col-md-6 mb-4" data-widget-id="qr_scanner">
                <div class="widget">
                    <div class="widget-header">
                        <h5><i class="fas fa-qrcode me-2"></i>QR Code Scanner</h5>
                        <button class="btn btn-sm btn-outline-secondary widget-settings" data-widget-id="qr_scanner">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div class="text-center">
                            <div id="scanner-container" class="mb-3" style="width: 100%; height: 200px;"></div>
                            <div class="btn-group">
                                <button id="start-scan" class="btn btn-primary">
                                    <i class="fas fa-camera"></i> Start Scanner
                                </button>
                                <button id="stop-scan" class="btn btn-secondary" style="display: none;">
                                    <i class="fas fa-stop"></i> Stop Scanner
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Widget Settings Modal -->
    <div class="modal fade" id="widgetSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customize Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="save_preferences.php" method="POST">
                    <div class="modal-body">
                        <p>Select which widgets to display on your dashboard:</p>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="widget_sales" name="widgets[]" value="sales" <?php echo in_array('sales', $widgets) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="widget_sales">Sales Trend</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="widget_inventory" name="widgets[]" value="inventory" <?php echo in_array('inventory', $widgets) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="widget_inventory">Inventory Status</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="widget_recent_orders" name="widgets[]" value="recent_orders" <?php echo in_array('recent_orders', $widgets) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="widget_recent_orders">Recent Orders</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="widget_low_stock" name="widgets[]" value="low_stock" <?php echo in_array('low_stock', $widgets) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="widget_low_stock">Low Stock Alert</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="widget_top_products" name="widgets[]" value="top_products" <?php echo in_array('top_products', $widgets) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="widget_top_products">Top Selling Products</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="widget_weather_recommendations" name="widgets[]" value="weather_recommendations" <?php echo in_array('weather_recommendations', $widgets) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="widget_weather_recommendations">Weather Recommendations</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="widget_qr_scanner" name="widgets[]" value="qr_scanner" <?php echo in_array('qr_scanner', $widgets) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="widget_qr_scanner">QR Code Scanner</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
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
    <script src="assets/js/scripts.js"></script>
    <script>
        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Sales Chart
            const salesChartEl = document.getElementById('sales-chart');
            if (salesChartEl) {
                const ctx = salesChartEl.getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [{
                            label: 'Sales',
                            data: <?php echo json_encode($sales_data); ?>,
                            borderColor: 'rgba(13, 110, 253, 1)',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
            
            // Initialize scanner
            initializeScanner();
            
            // Get weather recommendations
            getWeatherRecommendations();
        });
        
        // Make widgets draggable
        if (typeof Sortable !== 'undefined') {
            const widgetContainer = document.getElementById('widget-container');
            if (widgetContainer) {
                Sortable.create(widgetContainer, {
                    animation: 150,
                    handle: '.widget-header',
                    onEnd: function() {
                        // Save widget order
                        const widgets = document.querySelectorAll('[data-widget-id]');
                        const order = Array.from(widgets).map(widget => widget.getAttribute('data-widget-id'));
                        
                        fetch('save_preferences.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `widget_order=${JSON.stringify(order)}&user_id=<?php echo $_SESSION['user_id']; ?>`
                        });
                    }
                });
            }
        }
    </script>
</body>
</html>
