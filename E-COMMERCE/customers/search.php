<?php
$pageTitle = "Search Results";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

$results = [];
if (!empty($search_query)) {
    $query = "SELECT p.*, c.category_name, c.color 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.category_id 
              WHERE p.status = 'active' 
              AND (p.product_name LIKE :search OR p.description LIKE :search OR c.category_name LIKE :search)
              ORDER BY p.product_name";
    $stmt = $db->prepare($query);
    $search_term = "%$search_query%";
    $stmt->execute([':search' => $search_term]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5 customer-hero"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="fw-bold display-5">Search Results</h1>
        <p class="lead">
            <?php echo !empty($search_query) ? 'Showing results for "' . htmlspecialchars($search_query) . '"' : 'Enter a search term'; ?>
        </p>
    </div>
</div>

<div class="container py-5">
    <?php if (!empty($search_query)): ?>
        <div class="mb-4">
            <h2 class="text-primary"><?php echo count($results); ?> product(s) found</h2>
        </div>

        <?php if (count($results) > 0): ?>
            <div class="row g-4">
                <?php foreach ($results as $product): 
                    $color = $product['color'] ?? 'primary';
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-<?php echo $color; ?> border-2 product-card">
                        <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                             class="card-img-top" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-<?php echo $color; ?> mb-2 align-self-start">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </span>
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                            <p class="card-text text-muted flex-grow-1">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                            </p>
                            <div class="fs-4 fw-bold text-dark mt-auto mb-2">
                                ₱<?php echo number_format($product['price'], 2); ?>
                            </div>
                            <div class="d-grid gap-2">
                                <?php if ($product['quantity'] > 0): ?>
                                <a href="cart.php?action=add&id=<?php echo $product['product_id']; ?>&qty=1" 
                                   class="btn btn-<?php echo $color; ?>">
                                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                </a>
                                <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="bi bi-cart-plus me-2"></i>Out of Stock
                                </button>
                                <?php endif; ?>
                                <a href="products.php?action=details&id=<?php echo $product['product_id']; ?>" 
                                   class="btn btn-outline-<?php echo $color; ?>">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-search display-1 text-muted"></i>
                <h4 class="mt-3 text-muted">No products found</h4>
                <p class="text-muted">Try searching with different keywords</p>
                <a href="products.php" class="btn btn-primary mt-2">Browse All Categories</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-search display-1 text-muted"></i>
            <h4 class="mt-3 text-muted">Enter a search term</h4>
            <p class="text-muted">Use the search bar above to find products</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>