<?php
$pageTitle = "Dashboard";
require_once 'includes/header.php';

$stats = [];

$prod_query = "SELECT COUNT(*) as total FROM products";
$prod_stmt = $db->prepare($prod_query);
$prod_stmt->execute();
$stats['products'] = $prod_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$active_query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$active_stmt = $db->prepare($active_query);
$active_stmt->execute();
$stats['active_products'] = $active_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$cat_query = "SELECT COUNT(*) as total FROM categories";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$stats['categories'] = $cat_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$active_cat_query = "SELECT COUNT(*) as total FROM categories WHERE status = 'active'";
$active_cat_stmt = $db->prepare($active_cat_query);
$active_cat_stmt->execute();
$stats['active_categories'] = $active_cat_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$cust_query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$cust_stmt = $db->prepare($cust_query);
$cust_stmt->execute();
$stats['customers'] = $cust_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$order_query = "SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as revenue FROM orders";
$order_stmt = $db->prepare($order_query);
$order_stmt->execute();
$order_stats = $order_stmt->fetch(PDO::FETCH_ASSOC);
$stats['orders'] = $order_stats['total'];
$stats['revenue'] = $order_stats['revenue'];

$pending_query = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'";
$pending_stmt = $db->prepare($pending_query);
$pending_stmt->execute();
$stats['pending_orders'] = $pending_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$low_stock_query = "SELECT COUNT(*) as total FROM products WHERE quantity <= 5 AND quantity > 0";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute();
$stats['low_stock'] = $low_stock_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$out_stock_query = "SELECT COUNT(*) as total FROM products WHERE quantity = 0";
$out_stock_stmt = $db->prepare($out_stock_query);
$out_stock_stmt->execute();
$stats['out_stock'] = $out_stock_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$recent_query = "SELECT o.*, u.email, u.full_name 
                 FROM orders o 
                 LEFT JOIN customers c ON o.customer_id = c.customer_id
                 LEFT JOIN users u ON c.user_id = u.id
                 ORDER BY o.created_at DESC 
                 LIMIT 5";
$recent_stmt = $db->prepare($recent_query);
$recent_stmt->execute();
$recent_orders = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

$recent_products_query = "SELECT p.*, c.category_name, c.color 
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.category_id
                          ORDER BY p.product_id DESC 
                          LIMIT 5";
$recent_products_stmt = $db->prepare($recent_products_query);
$recent_products_stmt->execute();
$recent_products = $recent_products_stmt->fetchAll(PDO::FETCH_ASSOC);

$low_stock_query = "SELECT p.*, c.category_name, c.color 
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE p.quantity <= 5
                    ORDER BY p.quantity ASC
                    LIMIT 5";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock_products = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stats-card primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-icon primary mb-3">
                        <i class="bi bi-box"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['products']; ?></div>
                    <div class="stats-label">Total Products</div>
                    <small class="text-white-50"><?php echo $stats['active_products']; ?> active</small>
                </div>
                <a href="products.php" class="btn btn-sm btn-light">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-icon success mb-3">
                        <i class="bi bi-grid"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['categories']; ?></div>
                    <div class="stats-label">Categories</div>
                    <small class="text-white-50"><?php echo $stats['active_categories']; ?> active</small>
                </div>
                <a href="categories.php" class="btn btn-sm btn-light">Manage</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card info">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-icon info mb-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['customers']; ?></div>
                    <div class="stats-label">Customers</div>
                </div>
                <a href="customers.php" class="btn btn-sm btn-light">View</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stats-icon warning mb-3">
                        <i class="bi bi-cart"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['orders']; ?></div>
                    <div class="stats-label">Total Orders</div>
                    <small class="text-white-50">₱<?php echo number_format($stats['revenue'], 2); ?> revenue</small>
                </div>
                <a href="orders.php" class="btn btn-sm btn-light">View</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="card border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1"><?php echo $stats['pending_orders']; ?></h5>
                        <p class="text-muted mb-0">Pending Orders</p>
                        <a href="orders.php?status=pending" class="small text-warning text-decoration-none">View pending →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-md-4">
        <div class="card border-start border-danger border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="bi bi-box-seam text-danger fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1"><?php echo $stats['out_stock']; ?></h5>
                        <p class="text-muted mb-0">Out of Stock</p>
                        <a href="products.php?filter=out" class="small text-danger text-decoration-none">View products →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-md-4">
        <div class="card border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="bi bi-hourglass-split text-info fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1"><?php echo $stats['low_stock']; ?></h5>
                        <p class="text-muted mb-0">Low Stock Items</p>
                        <a href="products.php?filter=low" class="small text-info text-decoration-none">View low stock →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2 text-primary"></i>
                    Recent Orders
                </h5>
                <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_orders)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-2">No orders yet</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($order['full_name'] ?? $order['email'] ?? 'Guest'); ?>
                                </td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $order['order_status'] == 'delivered' ? 'success' : 
                                            ($order['order_status'] == 'cancelled' ? 'danger' : 
                                            ($order['order_status'] == 'shipped' ? 'info' : 'warning')); 
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
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-box-seam me-2 text-success"></i>
                    Recently Added Products
                </h5>
                <a href="products.php" class="btn btn-sm btn-outline-success">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_products)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-2">No products yet</p>
                    <a href="products.php" class="btn btn-primary btn-sm">Add Product</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_products as $product): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($product['image_path']) && file_exists('../' . $product['image_path'])): ?>
                                        <img src="../<?php echo $product['image_path']; ?>" 
                                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($product['product_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $product['color'] ?? 'secondary'; ?>">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </span>
                                </td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $product['quantity'] <= 5 ? 'warning' : 'success'; ?>">
                                        <?php echo $product['quantity']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($low_stock_products)): ?>
<div class="row">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Low Stock Alert (<?php echo count($low_stock_products); ?> items)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_products as $product): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($product['image_path']) && file_exists('../' . $product['image_path'])): ?>
                                        <img src="../<?php echo $product['image_path']; ?>" 
                                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($product['product_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $product['color'] ?? 'secondary'; ?>">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold <?php echo $product['quantity'] == 0 ? 'text-danger' : 'text-warning'; ?>">
                                        <?php echo $product['quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['quantity'] == 0): ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Low Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="products.php?edit=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> Update Stock
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="products.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="bi bi-plus-circle d-block fs-3 mb-2"></i>
                            Add Product
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="categories.php" class="btn btn-outline-success w-100 py-3">
                            <i class="bi bi-plus-square d-block fs-3 mb-2"></i>
                            Add Category
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="orders.php" class="btn btn-outline-warning w-100 py-3">
                            <i class="bi bi-truck d-block fs-3 mb-2"></i>
                            Manage Orders
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="profile.php" class="btn btn-outline-info w-100 py-3">
                            <i class="bi bi-person-gear d-block fs-3 mb-2"></i>
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>