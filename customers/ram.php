<?php
$pageTitle = "RAM Memory";
$category_name = "RAM";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$cat_query = "SELECT * FROM categories WHERE category_name = :name AND status = 'active'";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute([':name' => $category_name]);
$category = $cat_stmt->fetch(PDO::FETCH_ASSOC);

if ($category) {
    $product_query = "SELECT * FROM products WHERE category_id = :id AND status = 'active' ORDER BY product_name";
    $product_stmt = $db->prepare($product_query);
    $product_stmt->execute([':id' => $category['category_id']]);
    $products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $products = [];
}
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5 customer-hero"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="fw-bold display-5">RAM Memory</h1>
        <p class="lead">DDR4 & DDR5 memory kits for optimal performance</p>
        <div class="d-flex gap-3 mt-4">
            <a href="products.php" class="btn btn-outline-light">
                <i class="bi bi-arrow-left me-2"></i>Back to Categories
            </a>
        </div>
    </div>
</div>

<div class="container py-5">
    <?php if (count($products) > 0): ?>
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-<?php echo $category['color'] ?? 'secondary'; ?> border-2 product-card">
                <img src="../<?php echo $product['image_path']; ?>" alt="<?php echo $product['product_name']; ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                    <span class="badge bg-<?php echo $category['color'] ?? 'secondary'; ?> mb-2 align-self-start">RAM</span>
                    <h5 class="card-title fw-bold"><?php echo $product['product_name']; ?></h5>
                    <p class="card-text text-muted flex-grow-1"><?php echo substr($product['description'], 0, 100) . '...'; ?></p>
                    <?php if (!empty($product['specs'])): 
                        $specs = json_decode($product['specs'], true);
                        if ($specs && is_array($specs)):
                    ?>
                    <div class="specs mb-3">
                        <?php foreach (array_slice($specs, 0, 3) as $key => $value): ?>
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-check-circle-fill text-<?php echo $category['color'] ?? 'secondary'; ?> me-2" style="font-size: 0.8rem;"></i>
                            <small><strong><?php echo ucfirst($key); ?>:</strong> <?php echo $value; ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; endif; ?>
                    <div class="fs-4 fw-bold text-dark mt-auto mb-2">₱<?php echo number_format($product['price'], 2); ?></div>
                    
                    <?php if ($product['quantity'] > 0): ?>
                    <a href="cart.php?action=add&id=<?php echo $product['product_id']; ?>&qty=1" 
                       class="btn btn-<?php echo $category['color'] ?? 'secondary'; ?>">
                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                    </a>
                    <?php else: ?>
                    <button class="btn btn-secondary" disabled>
                        <i class="bi bi-cart-plus me-2"></i>Out of Stock
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="bi bi-memory display-1 text-muted"></i>
        <h4 class="mt-3 text-muted">No RAM products available</h4>
        <p class="text-muted">Check back later for new memory arrivals!</p>
        <a href="products.php" class="btn btn-primary mt-3">
            <i class="bi bi-arrow-left me-2"></i>Browse Other Categories
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>