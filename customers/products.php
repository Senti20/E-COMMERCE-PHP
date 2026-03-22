<?php
$pageTitle = "Products";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$action = $_GET['action'] ?? 'list';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action == 'details' && $product_id > 0) {
    $query = "SELECT p.*, c.category_name, c.color, c.category_id 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.category_id 
              WHERE p.product_id = :id AND p.status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: products.php');
        exit();
    }

    $related_query = "SELECT * FROM products 
                      WHERE category_id = :cat_id AND product_id != :id AND status = 'active' 
                      LIMIT 4";
    $related_stmt = $db->prepare($related_query);
    $related_stmt->execute([
        ':cat_id' => $product['category_id'],
        ':id' => $product_id
    ]);
    $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

    $specs = !empty($product['specs']) ? json_decode($product['specs'], true) : [];
    $pageTitle = $product['product_name'];
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5 customer-hero"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="fw-bold display-5"><?php echo $product['product_name']; ?></h1>
        <p class="lead"><?php echo $product['category_name']; ?></p>
    </div>
</div>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <li class="breadcrumb-item"><a href="category.php?id=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $product['product_name']; ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <div class="col-md-6">
            <img src="../<?php echo $product['image_path']; ?>" alt="<?php echo $product['product_name']; ?>" class="img-fluid rounded shadow" style="width: 100%; max-height: 500px; object-fit: cover;">
        </div>
        
        <div class="col-md-6">
            <span class="badge bg-<?php echo $product['color']; ?> mb-3" style="font-size: 1rem;"><?php echo $product['category_name']; ?></span>
            <h1 class="display-5 fw-bold mb-3"><?php echo $product['product_name']; ?></h1>
            
            <div class="mb-4">
                <span class="display-6 fw-bold text-primary">₱<?php echo number_format($product['price'], 2); ?></span>
                <?php if ($product['quantity'] > 0): ?>
                <span class="badge bg-success ms-3">In Stock (<?php echo $product['quantity']; ?> available)</span>
                <?php else: ?>
                <span class="badge bg-danger ms-3">Out of Stock</span>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <h5 class="fw-bold mb-3">Description</h5>
                <p class="text-muted"><?php echo nl2br($product['description']); ?></p>
            </div>
            
            <?php if (!empty($specs) && is_array($specs)): ?>
            <div class="mb-4">
                <h5 class="fw-bold mb-3">Specifications</h5>
                <table class="table table-bordered">
                    <?php foreach ($specs as $key => $value): ?>
                    <tr>
                        <th style="width: 40%;"><?php echo ucfirst(str_replace('_', ' ', $key)); ?></th>
                        <td><?php echo $value; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="d-flex gap-3">
                <?php if ($product['quantity'] > 0): ?>
                <a href="cart.php?action=add&id=<?php echo $product['product_id']; ?>&qty=1" 
                   class="btn btn-primary btn-lg flex-grow-1">
                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                </a>
                <?php else: ?>
                <button class="btn btn-secondary btn-lg flex-grow-1" disabled>
                    <i class="bi bi-cart-plus me-2"></i>Out of Stock
                </button>
                <?php endif; ?>
                <a href="products.php" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </div>
    
    <?php if (!empty($related_products)): ?>
    <hr class="my-5">
    
    <h3 class="fw-bold mb-4">Related Products</h3>
    <div class="row g-4">
        <?php foreach ($related_products as $related): ?>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 product-card">
                <img src="../<?php echo $related['image_path']; ?>" alt="<?php echo $related['product_name']; ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                <div class="card-body">
                    <h6 class="card-title"><?php echo $related['product_name']; ?></h6>
                    <p class="text-primary fw-bold mb-0">₱<?php echo number_format($related['price'], 2); ?></p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="products.php?action=details&id=<?php echo $related['product_id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php
} else {
    $cat_query = "SELECT * FROM categories WHERE status = 'active' ORDER BY category_name";
    $cat_stmt = $db->prepare($cat_query);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

    $prod_query = "SELECT p.*, c.category_name, c.color 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.status = 'active' 
                   ORDER BY p.product_id DESC";
    $prod_stmt = $db->prepare($prod_query);
    $prod_stmt->execute();
    $products = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5 customer-hero"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="fw-bold display-5">Our Products</h1>
        <p class="lead">Premium PC components for every build</p>
    </div>
</div>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="text-primary mb-4">Browse by Category</h2>
        <p class="text-muted">Click on any category to explore our products</p>
    </div>

    <div class="row g-4 mb-5">
        <?php foreach ($categories as $category): 
            $color = $category['color'];
            $icon = $category['icon'];
        ?>
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 border-<?php echo $color; ?> border-2 shadow-sm category-card" 
                 onclick="window.location.href='category.php?id=<?php echo $category['category_id']; ?>'">
                <div class="card-body text-center p-4">
                    <i class="bi <?php echo $icon; ?> category-icon text-<?php echo $color; ?> mb-3" style="font-size: 3rem;"></i>
                    <h5 class="fw-bold text-dark"><?php echo htmlspecialchars($category['category_name']); ?></h5>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($category['description']); ?></p>
                    <span class="btn btn-sm btn-outline-<?php echo $color; ?>">View All</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mb-5">
        <h2 class="text-primary mb-4">All Products</h2>
        <p class="text-muted">Browse our complete collection</p>
    </div>

    <div class="row g-4">
        <?php if (empty($products)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No products available at the moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): 
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
                               class="btn btn-<?php echo $color; ?> w-100">
                                <i class="bi bi-cart-plus me-2"></i>Add to Cart
                            </a>
                            <?php else: ?>
                            <button class="btn btn-secondary w-100" disabled>
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
        <?php endif; ?>
    </div>
</div>

<?php
}

require_once '../includes/footer.php';
?>