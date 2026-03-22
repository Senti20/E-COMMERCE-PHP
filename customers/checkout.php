<?php
ob_start();

$pageTitle = "Checkout";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['checkout_redirect'] = true;
    header('Location: ../auth/login.php');
    ob_end_flush();
    exit();
}

if (empty($_SESSION['cart'])) {
    $_SESSION['message'] = "Your cart is empty";
    $_SESSION['message_type'] = "warning";
    header('Location: cart.php');
    ob_end_flush();
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];

$query = "SELECT u.*, c.customer_id, c.address 
          FROM users u 
          LEFT JOIN customers c ON u.id = c.user_id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$customer_id = $user['customer_id'] ?? 0;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid security token";
    } else {
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $payment_method = $_POST['payment_method'] ?? 'cod';
        
        if (empty($shipping_address)) $errors[] = "Shipping address is required";
        if (empty($city)) $errors[] = "City is required";
        if (empty($province)) $errors[] = "Province is required";
        if (empty($postal_code)) $errors[] = "Postal code is required";
        elseif (!preg_match('/^\d{4}$/', $postal_code)) $errors[] = "Postal code must be 4 digits";
        if (empty($phone)) $errors[] = "Phone number is required";
        elseif (!preg_match('/^[0-9]{11}$/', $phone)) $errors[] = "Phone number must be 11 digits";
        
        $cart_items = $_SESSION['cart'] ?? [];
        if (empty($cart_items)) {
            header('Location: cart.php');
            ob_end_flush();
            exit();
        }
        
        if (empty($errors)) {
            try {
                $conn->begin_transaction();
                
                $subtotal = 0;
                foreach ($cart_items as $item) {
                    $subtotal += $item['price'] * $item['qty'];
                }
                $shipping_fee = $subtotal >= 50000 ? 0 : 250;
                $total_amount = $subtotal + $shipping_fee;
                
                $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
                $full_address = $shipping_address . ', ' . $city . ', ' . $province . ' ' . $postal_code;
                
                $order_query = "INSERT INTO orders (customer_id, order_number, total_amount, shipping_address, payment_method, payment_status, order_status, created_at) 
                              VALUES (?, ?, ?, ?, ?, 'pending', 'pending', NOW())";
                $order_stmt = $conn->prepare($order_query);
                $order_stmt->bind_param("issss", $customer_id, $order_number, $total_amount, $full_address, $payment_method);
                $order_stmt->execute();
                $order_id = $conn->insert_id;
                
                foreach ($cart_items as $item) {
                    $product_query = "SELECT product_id, quantity, price FROM products WHERE product_name = ?";
                    $product_stmt = $conn->prepare($product_query);
                    $product_stmt->bind_param("s", $item['name']);
                    $product_stmt->execute();
                    $product_result = $product_stmt->get_result();
                    $product = $product_result->fetch_assoc();
                    
                    if ($product) {
                        if ($product['quantity'] < $item['qty']) {
                            throw new Exception("Insufficient stock for " . $item['name']);
                        }
                        
                        $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                        $item_stmt = $conn->prepare($item_query);
                        $item_stmt->bind_param("iiid", $order_id, $product['product_id'], $item['qty'], $item['price']);
                        $item_stmt->execute();
                        
                        $update_query = "UPDATE products SET quantity = quantity - ? WHERE product_id = ?";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->bind_param("ii", $item['qty'], $product['product_id']);
                        $update_stmt->execute();
                    }
                }
                
                $conn->commit();
                
                unset($_SESSION['cart']);
                
                header('Location: orders.php?action=confirmation&order=' . $order_number);
                ob_end_flush();
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Order failed: " . $e->getMessage();
            }
        }
    }
}

$subtotal = 0;
$cart_items = $_SESSION['cart'] ?? [];
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
$shipping = $subtotal >= 50000 ? 0 : 250;
$total = $subtotal + $shipping;

ob_end_flush();
?>
<div class="d-flex flex-column justify-content-center align-items-center py-5"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="header-overlay"></div>
    <div class="container" style="position:relative; z-index:1;">
        <div class="d-flex flex-column align-items-center text-center text-white">
            <h1 class="fw-bold display-5">Checkout</h1>
            <p class="lead">Complete your purchase</p>
        </div>
    </div>
</div>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following errors:</h5>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-truck me-2 text-primary"></i>Shipping Information</h5>
                </div>
                <div class="card-body">
                    <form id="checkoutForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label required">Street Address / Building / Barangay</label>
                                <input type="text" class="form-control" name="shipping_address" 
                                       value="<?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">City / Municipality</label>
                                <input type="text" class="form-control" name="city" 
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Province</label>
                                <input type="text" class="form-control" name="province" 
                                       value="<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Postal Code</label>
                                <input type="text" class="form-control" name="postal_code" 
                                       value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>" 
                                       maxlength="4" pattern="\d{4}" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label required">Contact Number</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ($user['phone'] ?? '')); ?>" 
                                       placeholder="09123456789" maxlength="11" pattern="[0-9]{11}" required>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>Payment Method</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" 
                                           <?php echo (!isset($_POST['payment_method']) || $_POST['payment_method'] == 'cod') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cod">
                                        <i class="bi bi-cash-stack me-2 text-success"></i>Cash on Delivery
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="gcash" value="gcash"
                                           <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'gcash') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="gcash">
                                        <i class="bi bi-phone me-2 text-primary"></i>GCash
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 100px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-bag-check me-2 text-primary"></i>Order Summary</h5>
                </div>
                <div class="card-body">
                    <div id="orderItemsList" class="mb-3">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></span><br>
                                <small class="text-muted">Qty: <?php echo $item['qty']; ?> x ₱<?php echo number_format($item['price'], 2); ?></small>
                            </div>
                            <span class="fw-bold">₱<?php echo number_format($item['price'] * $item['qty'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
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
                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5">Total:</span>
                        <span class="h5 fw-bold text-primary">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <button type="submit" form="checkoutForm" class="btn btn-primary w-100 btn-lg" onclick="return confirm('Place this order?')">
                        <i class="bi bi-check-circle me-2"></i>Place Order
                    </button>
                    <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="bi bi-arrow-left me-2"></i>Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>