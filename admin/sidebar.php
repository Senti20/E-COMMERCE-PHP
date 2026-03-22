<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_page = str_replace('.php', '', $current_page);

// Safety check - if somehow a non-admin gets here, redirect
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login');
    exit();
}

global $db;
$count_query = "SELECT COUNT(*) as pending FROM orders WHERE order_status = 'pending'";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$count = $count_stmt->fetch(PDO::FETCH_ASSOC);
$pending_count = $count['pending'] ?? 0;
?>
<div class="admin-sidebar">
    <div class="sidebar-header">
        <i class="bi bi-pc-display text-primary" style="font-size: 2.5rem;"></i>
        <h4 class="mb-0">SKYNET</h4>
        <p class="mb-0 small">Administrator</p>
    </div>
    
    <div class="sidebar-menu">
        
        <div class="menu-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
            <a href="dashboard">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </div>
        
        <div class="menu-item <?php echo $current_page == 'products' ? 'active' : ''; ?>">
            <a href="products">
                <i class="bi bi-box"></i>
                <span>Manage Products</span>
            </a>
        </div>
        
        <div class="menu-item <?php echo $current_page == 'categories' ? 'active' : ''; ?>">
            <a href="categories">
                <i class="bi bi-grid"></i>
                <span>Manage Categories</span>
            </a>
        </div>
        
        <div class="menu-item <?php echo $current_page == 'orders' ? 'active' : ''; ?>">
            <a href="orders">
                <i class="bi bi-cart"></i>
                <span>Orders</span>
                <?php if ($pending_count > 0): ?>
                <span class="badge bg-danger ms-auto"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="menu-item <?php echo $current_page == 'customers' ? 'active' : ''; ?>">
            <a href="customers">
                <i class="bi bi-people"></i>
                <span>Customers</span>
            </a>
        </div>
        
        <div class="menu-item <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
            <a href="profile">
                <i class="bi bi-person-circle"></i>
                <span>Edit Profile</span>
            </a>
        </div>
        
        <div class="menu-item">
            <a href="#" onclick="confirmLogout()">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>