<?php
$pageTitle = "PC Cases";
$category_name = "Case";
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

$color = $category['color'] ?? 'purple';
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5 customer-hero"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="fw-bold">PC Cases</h1>
        <p class="lead">Gaming & Workstation Cases for stylish builds</p>
        <a href="products.php" class="btn btn-outline-light">
            <i class="bi bi-arrow-left me-2"></i>Back to Categories
        </a>
    </div>
</div>

<div class="container py-4">
    <?php if (count($products) > 0): ?>
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-<?php echo $color; ?> border-2 product-card">
                <img src="../<?php echo $product['image_path']; ?>" alt="<?php echo $product['product_name']; ?>" class="card-img-top">
                <div class="card-body">
                    <span class="badge bg-<?php echo $color; ?>">Case</span>
                    <h5 class="card-title"><?php echo $product['product_name']; ?></h5>
                    <p class="card-text"><?php echo substr($product['description'], 0, 80) . '...'; ?></p>
                    <div class="fs-5 fw-bold text-dark mb-2">₱<?php echo number_format($product['price'], 2); ?></div>
                    
                    <?php if ($product['quantity'] > 0): ?>
                    <a href="cart.php?action=add&id=<?php echo $product['product_id']; ?>&qty=1" 
                       class="btn btn-<?php echo $color; ?> w-100">
                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                    </a>
                    <?php else: ?>
                    <button class="btn btn-secondary w-100" disabled>
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
        <i class="bi bi-pc-display"></i>
        <h4>No PC case products available</h4>
        <p>Check back later for new case arrivals!</p>
        <a href="products.php" class="btn btn-primary">
            <i class="bi bi-arrow-left me-2"></i>Browse Other Categories
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>