<?php
session_start();
require_once 'db.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity <= 0) {
        $quantity = 1; // Ensure minimum quantity
    }
    
    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Check if product is already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        // If product is not in cart, add it
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => $product['image']
            ];
        }
        
        $_SESSION['message'] = showAlert("Product added to cart!");
    }
    
    $stmt->close();
    
    // Redirect back to previous page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Handle update cart
if (isset($_POST['update_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($quantity <= 0) {
        // Remove the item from the cart if quantity is zero or negative
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $product_id) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index the array
        $_SESSION['message'] = showAlert("Product removed from cart!");
    } else {
        // Update the quantity
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $product_id) {
                $item['quantity'] = $quantity;
                $_SESSION['message'] = showAlert("Cart updated!");
                break;
            }
        }
    }
    header("Location: cart.php");
    exit;
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);

    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $product_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index the array
    $_SESSION['message'] = showAlert("Product removed from cart!");
    header("Location: cart.php");
    exit;
}

// Handle clear cart
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    $_SESSION['message'] = showAlert("Cart cleared!");
    header("Location: cart.php");
    exit;
}

// Handle checkout
if (isset($_POST['checkout'])) {
    // In a real application, you would process the order here
    // For this demo, we'll just clear the cart and show a success message
    
    // Check if there are items in the cart
    if (count($_SESSION['cart']) > 0) {
        // Update product quantities in database
        foreach ($_SESSION['cart'] as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            
            $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
            $stmt->bind_param("iii", $quantity, $product_id, $quantity);
            $stmt->execute();
            $stmt->close();
        }
        
        $_SESSION['cart'] = [];
        $_SESSION['message'] = showAlert("Checkout successful! Thank you for your purchase.");
    } else {
        $_SESSION['message'] = showAlert("Your cart is empty.", "warning");
    }
    
    header("Location: cart.php");
    exit;
}

// Calculate cart total
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingagi ERP - Shopping Cart</title>
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
                        <a class="nav-link active" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="badge bg-danger cart-count"><?php echo count($_SESSION['cart']); ?></span>
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <button id="theme-toggle" class="btn btn-outline-light me-2">
                        <i class="fas fa-moon"></i>
                    </button>
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

        <div class="card animate__animated animate__fadeIn">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Shopping Cart</h5>
                <?php if (count($_SESSION['cart']) > 0): ?>
                <a href="cart.php?clear=1" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash"></i> Clear Cart
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($_SESSION['cart']) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['image']) && file_exists($item['image'])): ?>
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="product-thumbnail">
                                    <?php else: ?>
                                    <img src="assets/images/no-image.jpg" alt="No Image" class="product-thumbnail">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['name']; ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <form action="cart.php" method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control form-control-sm" style="width: 70px;">
                                        <button type="submit" name="update_cart" class="btn btn-sm btn-outline-primary ms-2">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td>
                                    <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total:</td>
                                <td class="fw-bold">$<?php echo number_format($cart_total, 2); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <a href="index.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <form action="cart.php" method="POST">
                        <button type="submit" name="checkout" class="btn btn-success">
                            <i class="fas fa-check"></i> Checkout
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h4>Your cart is empty</h4>
                    <p>Add some products to your cart and they will appear here.</p>
                    <a href="index.php" class="btn btn-primary mt-3">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
                <?php endif; ?>
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
