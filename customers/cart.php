<?php
ob_start();
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_GET['action']) && $_GET['action'] == 'add') {
    require_once '../auth/db.php';
    $product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
    
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.php';
    
    if ($product_id > 0) {
        $query = "SELECT p.*, c.category_name FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  WHERE p.product_id = ? AND p.status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if ($product) {
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $product_id) {
                    $item['qty'] += $quantity;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $product_id,
                    'name' => $product['product_name'],
                    'price' => $product['price'],
                    'image' => $product['image_path'],
                    'category' => $product['category_name'],
                    'qty' => $quantity
                ];
            }
            
            $_SESSION['message'] = "Product added to cart!";
            $_SESSION['message_type'] = "success";
        }
    }
    header('Location: ' . $redirect_url);
    ob_end_flush();
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    
    if ($index >= 0 && isset($_SESSION['cart'][$index])) {
        if ($qty < 1) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            $_SESSION['message'] = "Item removed from cart";
        } else {
            $_SESSION['cart'][$index]['qty'] = $qty;
            $_SESSION['message'] = "Cart updated";
        }
        $_SESSION['message_type'] = "success";
    }
    header('Location: cart.php');
    ob_end_flush();
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $index = isset($_GET['index']) ? (int)$_GET['index'] : -1;
    
    if ($index >= 0 && isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $_SESSION['message'] = "Item removed from cart";
        $_SESSION['message_type'] = "success";
    }
    header('Location: cart.php');
    ob_end_flush();
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    $_SESSION['cart'] = [];
    $_SESSION['message'] = "Cart cleared";
    $_SESSION['message_type'] = "success";
    header('Location: cart.php');
    ob_end_flush();
    exit();
}

$pageTitle = "Shopping Cart";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
$shipping = $subtotal >= 50000 ? 0 : 250;
$total = $subtotal + $shipping;
$cart_count = array_sum(array_column($_SESSION['cart'], 'qty'));

ob_end_flush();
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5 customer-hero"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="fw-bold">Shopping Cart</h1>
        <p class="lead">Review your items before checkout</p>
    </div>
</div>

<div class="container py-4">
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['message']; 
        unset($_SESSION['message']); 
        unset($_SESSION['message_type']); 
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Cart Items (<?php echo count($_SESSION['cart']); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <div class="empty-state">
                            <i class="bi bi-cart-x"></i>
                            <h4>Your cart is empty</h4>
                            <p>Add some products to get started!</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="bi bi-shop me-2"></i>Browse Products
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">Image</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['cart'] as $index => $item): 
                                        $item_total = $item['price'] * $item['qty'];
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="cart-item-image"
                                                 onerror="this.src='../assets/img/default-product.png'">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small>
                                        </td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                                <input type="number" name="qty" class="form-control quantity-input" 
                                                       value="<?php echo $item['qty']; ?>" min="0" 
                                                       onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td>₱<?php echo number_format($item_total, 2); ?></td>
                                        <td>
                                            <a href="?action=remove&index=<?php echo $index; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Remove this item from cart?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                            </a>
                            <a href="?action=clear" class="btn btn-outline-danger" 
                               onclick="return confirm('Clear your entire cart?')">
                                <i class="bi bi-trash me-2"></i>Clear Cart
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 100px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span class="fw-bold">₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span class="fw-bold">
                            <?php if ($shipping == 0): ?>
                                <span class="text-success">FREE</span>
                            <?php else: ?>
                                ₱<?php echo number_format($shipping, 2); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5">Total:</span>
                        <span class="h5 fw-bold text-primary">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="checkout.php" class="btn btn-primary w-100">
                                <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
                            </a>
                        <?php else: ?>
                            <a href="../auth/login.php?redirect=cart.php" class="btn btn-warning w-100">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login to Checkout
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100" disabled>
                            <i class="bi bi-credit-card me-2"></i>Checkout
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-3 bg-light">
                <div class="card-body">
                    <h6><i class="bi bi-truck text-primary me-2"></i>Free Shipping</h6>
                    <p class="small text-muted mb-0">
                        Get free shipping on orders over ₱50,000!
                        <?php if ($subtotal > 0 && $subtotal < 50000): ?>
                            <br>Add ₱<?php echo number_format(50000 - $subtotal, 2); ?> more.
                        <?php elseif ($subtotal >= 50000): ?>
                            <br><span class="text-success">You qualify!</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>