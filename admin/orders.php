<?php
$pageTitle = "Manage Orders";
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $order_status = $_POST['order_status'];
    $payment_status = $_POST['payment_status'];
    
    $query = "UPDATE orders SET order_status = :order_status, payment_status = :payment_status WHERE order_id = :order_id";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([
        ':order_status' => $order_status,
        ':payment_status' => $payment_status,
        ':order_id' => $order_id
    ])) {
        $_SESSION['message'] = "Order #$order_id updated successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update order";
        $_SESSION['message_type'] = "danger";
    }
    header('Location: orders.php');
    exit();
}

$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$query = "SELECT o.*, u.full_name, u.email, u.phone,
          COUNT(oi.order_item_id) as item_count
          FROM orders o 
          LEFT JOIN customers c ON o.customer_id = c.customer_id
          LEFT JOIN users u ON c.user_id = u.id
          LEFT JOIN order_items oi ON o.order_id = oi.order_id
          WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND o.order_status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " GROUP BY o.order_id ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                COALESCE(SUM(total_amount), 0) as total_revenue
                FROM orders";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Orders</h6>
                <h2 class="mb-0"><?php echo $stats['total_orders']; ?></h2>
                <small>₱<?php echo number_format($stats['total_revenue'], 2); ?> revenue</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Pending</h6>
                <h3 class="mb-0"><?php echo $stats['pending']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Processing</h6>
                <h3 class="mb-0"><?php echo $stats['processing']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Shipped</h6>
                <h3 class="mb-0"><?php echo $stats['shipped']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Delivered</h6>
                <h3 class="mb-0"><?php echo $stats['delivered']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Order Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                <a href="orders.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Orders List</h5>
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <h5 class="mt-3 text-muted">No orders found</h5>
            <p class="text-muted">Try adjusting your filters.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($order['email'] ?? ''); ?></small>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?><br>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                        </td>
                        <td><?php echo $order['item_count']; ?> item(s)</td>
                        <td>
                            <strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $order['payment_status'] == 'paid' ? 'success' : 
                                    ($order['payment_status'] == 'failed' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                            <br>
                            <small class="text-muted"><?php echo strtoupper($order['payment_method']); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $order['order_status'] == 'delivered' ? 'success' : 
                                    ($order['order_status'] == 'cancelled' ? 'danger' : 
                                    ($order['order_status'] == 'shipped' ? 'primary' : 
                                    ($order['order_status'] == 'processing' ? 'info' : 'warning'))); 
                            ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" 
                                    data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['order_id']; ?>">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($orders as $order): 
    $items_query = "SELECT oi.*, p.product_name, p.image_path 
                   FROM order_items oi 
                   LEFT JOIN products p ON oi.product_id = p.product_id 
                   WHERE oi.order_id = :order_id";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([':order_id' => $order['order_id']]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="modal fade" id="orderModal<?php echo $order['order_id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p>
                            <strong>Name:</strong> <?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <p>
                            <strong>Order Date:</strong> <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?><br>
                            <strong>Payment Method:</strong> <?php echo strtoupper($order['payment_method']); ?><br>
                            <strong>Payment Status:</strong> 
                            <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <h6>Shipping Address</h6>
                <p class="mb-3"><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? 'No address provided')); ?></p>
                
                <h6>Order Items</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
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
                                        <?php if (!empty($item['image_path']) && file_exists('../' . $item['image_path'])): ?>
                                        <img src="../<?php echo $item['image_path']; ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
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
                
                <form method="POST" action="" class="mt-3 p-3 bg-light rounded">
                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                    <h6>Update Order Status</h6>
                    <div class="row">
                        <div class="col-md-5 mb-2">
                            <select class="form-select" name="order_status">
                                <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-5 mb-2">
                            <select class="form-select" name="payment_status">
                                <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="update_order" class="btn btn-primary w-100">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>