<?php
// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);
$current_page = str_replace('.php', '', $current_page);

// Make sure BASE_URL is defined
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../auth/db.php';
}

// Get cart count from session
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['qty']) && is_numeric($item['qty'])) {
            $cart_count += (int)$item['qty'];
        }
    }
}

// Function to check if current page is a product-related page
function isProductPage($current_page) {
    $product_pages = ['products', 'category', 'cpu', 'gpu', 'motherboard', 'ram', 'storage', 'psu', 'cooling', 'cases', 'search'];
    return in_array($current_page, $product_pages);
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>/index">
            <i class="bi bi-pc-display me-2"></i><?php echo SITE_NAME; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index">
                        Home
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'about' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/customers/about">
                        About Us
                    </a>
                </li>
                
                <!-- Products Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo isProductPage($current_page) ? 'active' : ''; ?>" 
                       href="#" id="productsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Products
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="productsDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/customers/products">All Categories</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php
                        // Fetch categories for dropdown
                        $cat_query = "SELECT * FROM categories WHERE status = 'active' ORDER BY category_name";
                        $cat_stmt = $db->prepare($cat_query);
                        $cat_stmt->execute();
                        $nav_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($nav_categories as $cat):
                            $cat_file = BASE_URL . '/customers/';
                            switch(strtolower($cat['category_name'])) {
                                case 'case':
                                    $cat_file .= 'cases';
                                    break;
                                case 'cpu':
                                    $cat_file .= 'cpu';
                                    break;
                                case 'gpu':
                                    $cat_file .= 'gpu';
                                    break;
                                case 'motherboard':
                                    $cat_file .= 'motherboard';
                                    break;
                                case 'ram':
                                    $cat_file .= 'ram';
                                    break;
                                case 'storage':
                                    $cat_file .= 'storage';
                                    break;
                                case 'psu':
                                    $cat_file .= 'psu';
                                    break;
                                case 'cooling':
                                    $cat_file .= 'cooling';
                                    break;
                                default:
                                    $cat_file .= 'category?id=' . $cat['category_id'];
                            }
                        ?>
                        <li>
                            <a class="dropdown-item" href="<?php echo $cat_file; ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'contact' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/customers/contact">
                        Contact Us
                    </a>
                </li>
            </ul>
            
            <!-- Search Form -->
            <form class="d-flex me-2 search-form" action="<?php echo BASE_URL; ?>/customers/search" method="GET">
                <div class="input-group">
                    <input class="form-control" type="search" name="q" placeholder="Search products..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" required>
                    <button class="btn" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
            
            <ul class="navbar-nav">
                <!-- Cart Link -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/customers/cart">
                        Cart<?php if ($cart_count > 0): ?>(<?php echo $cart_count; ?>)<?php endif; ?>
                    </a>
                </li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php 
                        if (!empty($_SESSION['full_name'])) {
                            $name_parts = explode(' ', $_SESSION['full_name']);
                            echo htmlspecialchars($name_parts[0]);
                        } elseif (!empty($_SESSION['username'])) {
                            echo htmlspecialchars($_SESSION['username']);
                        } else {
                            echo 'User';
                        }
                        ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/customers/profile">My Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/customers/orders">My Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/logout" onclick="return confirm('Are you sure you want to logout?')">Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/login">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/register">Register</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>