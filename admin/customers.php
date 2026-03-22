<?php
$pageTitle = "Manage Customers";
require_once 'includes/header.php';

$search = $_GET['search'] ?? '';

$query = "SELECT u.*, 
          COUNT(o.order_id) as order_count,
          COALESCE(SUM(o.total_amount), 0) as total_spent,
          MAX(o.created_at) as last_order_date
          FROM users u 
          LEFT JOIN customers c ON u.id = c.user_id
          LEFT JOIN orders o ON c.customer_id = o.customer_id
          WHERE u.role = 'customer'";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats_query = "SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_30days
                FROM users 
                WHERE role = 'customer'";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Customers</h6>
                <h2 class="mb-0"><?php echo $stats['total_customers']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">New Customers (30 days)</h6>
                <h2 class="mb-0"><?php echo $stats['new_30days']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by name, email, username, or phone..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary me-2">Search</button>
                <a href="customers.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Customers List</h5>
    </div>
    <div class="card-body">
        <?php if (empty($customers)): ?>
        <div class="text-center py-5">
            <i class="bi bi-people display-1 text-muted"></i>
            <h5 class="mt-3 text-muted">No customers found</h5>
            <p class="text-muted">Customers will appear here once they register.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Joined</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Last Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo $customer['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="../<?php echo $customer['profile_image'] ?? 'assets/img/default-avatar.png'; ?>" 
                                     alt="Profile" class="rounded-circle me-2" 
                                     style="width: 40px; height: 40px; object-fit: cover;">
                                <div>
                                    <strong><?php echo htmlspecialchars($customer['full_name'] ?? 'N/A'); ?></strong><br>
                                    <small class="text-muted">@<?php echo htmlspecialchars($customer['username'] ?? 'N/A'); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($customer['email']); ?><br>
                            <i class="bi bi-phone me-1"></i> <?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($customer['created_at'])); ?><br>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($customer['created_at'])); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $customer['order_count'] > 0 ? 'success' : 'secondary'; ?>">
                                <?php echo $customer['order_count']; ?> orders
                            </span>
                        </td>
                        <td>
                            <?php if ($customer['total_spent'] > 0): ?>
                            <strong>₱<?php echo number_format($customer['total_spent'], 2); ?></strong>
                            <?php else: ?>
                            <span class="text-muted">₱0.00</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($customer['last_order_date']): ?>
                            <?php echo date('M d, Y', strtotime($customer['last_order_date'])); ?>
                            <?php else: ?>
                            <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info" 
                                    data-bs-toggle="modal" data-bs-target="#customerModal<?php echo $customer['id']; ?>">
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

<!-- Customer Detail Modals -->
<?php foreach ($customers as $customer): 
    // Fetch customer's orders
    $orders_query = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                    FROM orders o 
                    LEFT JOIN customers c ON o.customer_id = c.customer_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    WHERE c.user_id = :user_id
                    GROUP BY o.order_id
                    ORDER BY o.created_at DESC
                    LIMIT 5";
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute([':user_id' => $customer['id']]);
    $customer_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="modal fade" id="customerModal<?php echo $customer['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <img src="../<?php echo $customer['profile_image'] ?? 'assets/img/default-avatar.png'; ?>" 
                             alt="Profile" class="rounded-circle img-fluid" 
                             style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #0d6efd;">
                        <h5 class="mt-3"><?php echo htmlspecialchars($customer['full_name'] ?? 'N/A'); ?></h5>
                        <p class="text-muted">@<?php echo htmlspecialchars($customer['username'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-8">
                        <h6>Contact Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th style="width: 120px;">Email:</th>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo htmlspecialchars($customer['phone'] ?? 'Not provided'); ?></td>
                            </tr>
                            <tr>
                                <th>Member Since:</th>
                                <td><?php echo date('F d, Y h:i A', strtotime($customer['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Total Orders:</th>
                                <td><?php echo $customer['order_count']; ?></td>
                            </tr>
                            <tr>
                                <th>Total Spent:</th>
                                <td><strong>₱<?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($customer_orders)): ?>
                <hr>
                <h6>Recent Orders</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customer_orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $order['item_count']; ?> items</td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
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
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>