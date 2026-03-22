<?php
$pageTitle = "My Orders";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'orders.php';
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order_number = $_GET['order'] ?? '';

$cust_query = "SELECT customer_id FROM customers WHERE user_id = ?";
$cust_stmt = $conn->prepare($cust_query);
$cust_stmt->bind_param("i", $user_id);
$cust_stmt->execute();
$cust_result = $cust_stmt->get_result();
$customer = $cust_result->fetch_assoc();
$customer_id = $customer['customer_id'] ?? 0;

if ($action == 'details' && $order_id > 0) {
    $query = "SELECT o.*, u.full_name, u.email, u.phone 
              FROM orders o 
              JOIN customers c ON o.customer_id = c.customer_id
              JOIN users u ON c.user_id = u.id
              WHERE o.order_id = ? AND o.customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    $items_query = "SELECT oi.*, p.product_name, p.image_path 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.product_id 
                    WHERE oi.order_id = ?";
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $items = $items_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="header-overlay"></div>
    <div class="container" style="position:relative; z-index:1;">
        <div class="d-flex flex-column align-items-center text-center text-white">
            <h1 class="fw-bold display-5">Order Details</h1>
            <p class="lead">Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
        </div>
    </div>
</div>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="orders.php">My Orders</a></li>
            <li class="breadcrumb-item active" aria-current="page">Order Details</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
            <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Orders
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Order Information</h6>
                    <p>
                        <strong>Order Date:</strong> <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?><br>
                        <strong>Order Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $order['order_status'] == 'delivered' ? 'success' : 
                                ($order['order_status'] == 'cancelled' ? 'danger' : 
                                ($order['order_status'] == 'shipped' ? 'info' : 'warning')); 
                        ?>"><?php echo ucfirst($order['order_status']); ?></span><br>
                        <strong>Payment Method:</strong> <?php echo strtoupper($order['payment_method']); ?><br>
                        <strong>Payment Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $order['payment_status'] == 'paid' ? 'success' : 
                                ($order['payment_status'] == 'failed' ? 'danger' : 'warning'); 
                        ?>"><?php echo ucfirst($order['payment_status']); ?></span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Shipping Address</h6>
                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                </div>
            </div>

            <h6>Order Items</h6>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        foreach ($items as $item): 
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($item['image_path'])): ?>
                                    <img src="../<?php echo $item['image_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₱<?php echo number_format($item_total, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                            <td>₱<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                            <td>₱<?php echo number_format($subtotal >= 50000 ? 0 : 250, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
} elseif ($action == 'confirmation' && !empty($order_number)) {
    $query = "SELECT o.*, u.full_name, u.email 
              FROM orders o 
              JOIN customers c ON o.customer_id = c.customer_id
              JOIN users u ON c.user_id = u.id
              WHERE o.order_number = ? AND o.customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $order_number, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    $items_query = "SELECT oi.*, p.product_name, p.image_path 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.product_id 
                    WHERE oi.order_id = ?";
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->bind_param("i", $order['order_id']);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $items = $items_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="header-overlay"></div>
    <div class="container" style="position:relative; z-index:1;">
        <div class="d-flex flex-column align-items-center text-center text-white">
            <h1 class="fw-bold display-5">Order Confirmed!</h1>
            <p class="lead">Thank you for your purchase</p>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="text-center mb-5">
        <div class="display-1 text-success mb-3">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h1 class="display-5 fw-bold">Order Confirmed!</h1>
        <p class="lead">Thank you for your purchase. Your order has been received.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Order Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="text-muted">Order Number</h6>
                            <p class="h5 fw-bold"><?php echo htmlspecialchars($order['order_number']); ?></p>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="text-muted">Order Date</h6>
                            <p class="h5"><?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="text-muted">Payment Method</h6>
                            <p class="h5">
                                <span class="badge bg-light text-dark p-3">
                                    <i class="bi bi-<?php echo $order['payment_method'] == 'cod' ? 'cash-stack' : 'phone'; ?> me-2"></i>
                                    <?php echo strtoupper($order['payment_method']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="text-muted">Order Status</h6>
                            <p class="h5">
                                <span class="badge bg-warning p-3">Pending</span>
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-muted">Shipping Address</h6>
                        <div class="bg-light p-3 rounded">
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </div>
                    </div>

                    <h6 class="text-muted mb-3">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                foreach ($items as $item): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['image_path'])): ?>
                                            <img src="../<?php echo $item['image_path']; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                 class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end align-middle">₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-end align-middle fw-bold">₱<?php echo number_format($item_total, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><strong>₱<?php echo number_format($subtotal, 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                    <td class="text-end">
                                        <strong>
                                            <?php 
                                            $shipping = $subtotal >= 50000 ? 0 : 250;
                                            echo $shipping == 0 ? '<span class="text-success">FREE</span>' : '₱' . number_format($shipping, 2);
                                            ?>
                                        </strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><h5 class="mb-0">Total:</h5></td>
                                    <td class="text-end"><h5 class="mb-0 text-primary fw-bold">₱<?php echo number_format($order['total_amount'], 2); ?></h5></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="orders.php" class="btn btn-primary btn-lg me-2">
                    <i class="bi bi-box me-2"></i>View My Orders
                </a>
                <a href="products.php" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-shop me-2"></i>Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<?php
} else {
    $query = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
              FROM orders o 
              LEFT JOIN order_items oi ON o.order_id = oi.order_id 
              WHERE o.customer_id = ? 
              GROUP BY o.order_id 
              ORDER BY o.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="header-overlay"></div>
    <div class="container" style="position:relative; z-index:1;">
        <div class="d-flex flex-column align-items-center text-center text-white">
            <h1 class="fw-bold display-5">My Orders</h1>
            <p class="lead">Track and manage your purchases</p>
        </div>
    </div>
</div>

<div class="container py-5">
    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <i class="bi bi-box-seam display-1 text-muted"></i>
        <h4 class="mt-3 text-muted">No orders yet</h4>
        <p class="text-muted">Start shopping to place your first order!</p>
        <a href="products.php" class="btn btn-primary mt-2">Browse Products</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                    <td><?php echo $order['item_count']; ?> item(s)</td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            echo $order['payment_status'] == 'paid' ? 'success' : 
                                ($order['payment_status'] == 'failed' ? 'danger' : 'warning'); 
                        ?>">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?php 
                            echo $order['order_status'] == 'delivered' ? 'success' : 
                                ($order['order_status'] == 'cancelled' ? 'danger' : 
                                ($order['order_status'] == 'shipped' ? 'info' : 'warning')); 
                        ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="orders.php?action=details&id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                            View Details
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
}

require_once '../includes/footer.php';
?>