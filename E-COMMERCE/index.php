<?php
$pageTitle = "Home";
require_once 'includes/header.php';
require_once 'includes/navbar.php';

if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    echo '<div class="container mt-3">';
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-check-circle-fill me-2"></i>';
    echo 'You have been successfully logged out.';
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    echo '</div>';
}

if (isset($_SESSION['welcome_message'])) {
    echo '<div class="container mt-3">';
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-check-circle-fill me-2"></i>';
    echo htmlspecialchars($_SESSION['welcome_message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    echo '</div>';
    unset($_SESSION['welcome_message']);
}

$query = "SELECT p.*, c.category_name, c.color 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE p.status = 'active' 
          ORDER BY p.product_id DESC 
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="d-flex flex-column justify-content-center align-items-center py-5"
     style="background-image: url('assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="header-overlay"></div>
    <div class="container" style="position:relative; z-index:1;">
        <div class="d-flex flex-column align-items-center text-center text-white">
            <h1 class="fw-bold display-5">Welcome to <?php echo SITE_NAME; ?></h1>
            <p class="lead">Building excellence, one component at a time</p>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div id="pcCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner rounded">
                    <div class="carousel-item active">
                        <img src="assets/img/Nvidia.webp" class="d-block w-100" alt="Graphics Cards" style="height:400px; object-fit:cover;">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Latest Graphics Cards</h5>
                            <p>NVIDIA RTX 40 Series & AMD Radeon RX 7000</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="assets/img/Ryzen.webp" class="d-block w-100" alt="Processors" style="height:400px; object-fit:cover;">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>High-Performance CPUs</h5>
                            <p>Intel Core & AMD Ryzen Processors</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="assets/img/Msi.jpg" class="d-block w-100" alt="Motherboards" style="height:400px; object-fit:cover;">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Motherboards</h5>
                            <p>ASUS, MSI, Gigabyte & ASRock</p>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#pcCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#pcCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>

        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="d-flex flex-column border border-primary rounded p-4 bg-white shadow-sm h-100">
                <h3 class="fw-bold text-primary mb-4">Featured Brands</h3>
                <div class="d-flex flex-wrap justify-content-between flex-grow-1">
                    <div class="d-flex flex-column me-3 mb-3">
                        <div class="d-flex align-items-center mb-3"><span class="text-primary me-2">•</span><span class="fw-medium">NVIDIA</span></div>
                        <div class="d-flex align-items-center mb-3"><span class="text-primary me-2">•</span><span class="fw-medium">AMD</span></div>
                        <div class="d-flex align-items-center mb-3"><span class="text-primary me-2">•</span><span class="fw-medium">Intel</span></div>
                        <div class="d-flex align-items-center mb-3"><span class="text-primary me-2">•</span><span class="fw-medium">ASUS</span></div>
                    </div>
                    <div class="d-flex flex-column mb-3">
                        <div class="d-flex align-items-center mb-3"><span class="text-primary me-2">•</span><span class="fw-medium">MSI</span></div>
                        <div class="d-flex align-items-center mb-3"><span class="text-primary me-2">•</span><span class="fw-medium">Corsair</span></div>
                        <div class="d-flex align-items-center mb-3"><span class="text-primary me-2">•</span><span class="fw-medium">Gigabyte</span></div>
                        <div class="d-flex align-items-center mb-3"><span class="text-primary me-2">•</span><span class="fw-medium">Seasonic</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-12 text-center">
            <h2 class="text-primary mb-4">Featured Products</h2>
            <p class="text-muted mb-5">Check out our top-rated PC components and accessories</p>
            
            <div class="row g-4">
                <?php if (empty($featured_products)): ?>
                    <div class="col-12">
                        <p class="text-muted">No featured products available at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($featured_products as $product): 
                        $color = $product['color'] ?? 'primary';
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-<?php echo $color; ?> border-2 product-card">
                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
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
                                    <a href="customers/cart?action=add&id=<?php echo $product['product_id']; ?>&qty=1" 
                                       class="btn btn-<?php echo $color; ?> w-100">
                                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="bi bi-cart-plus me-2"></i>Out of Stock
                                    </button>
                                    <?php endif; ?>
                                    <a href="customers/products?action=details&id=<?php echo $product['product_id']; ?>" 
                                       class="btn btn-outline-<?php echo $color; ?> w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="text-center mt-5">
                <a href="customers/products" class="btn btn-outline-primary btn-lg me-3">View All Products</a>
                <a href="customers/cart" class="btn btn-primary btn-lg"><i class="bi bi-cart-fill me-2"></i>View Cart</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>