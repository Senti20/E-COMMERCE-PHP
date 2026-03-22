<?php
$pageTitle = "About Us";
require_once '../includes/header.php';
require_once '../includes/navbar.php'; 
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5 customer-hero"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="fw-bold display-5">About <?php echo SITE_NAME; ?></h1>
        <p class="lead">Building excellence, one component at a time</p>
    </div>
</div>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <img src="../assets/img/About.webp" class="img-fluid rounded shadow" alt="PC Parts Hub Team"
                 style="height:400px; object-fit:cover; width:100%;">
        </div>
        <div class="col-lg-6">
            <h2 class="fw-bold mb-4 text-primary">Our Story</h2>
            <div class="bg-white border-start border-4 border-primary p-4 rounded shadow-sm">
                <p class="lead mb-4">Founded in 2020, <?php echo SITE_NAME; ?> began as a small startup with a big vision: to make high-performance PC components accessible to everyone.</p>
                <p class="text-muted">What started as a passion project among tech enthusiasts has grown into one of the most trusted PC component retailers in the country. We believe that everyone deserves access to quality hardware without breaking the bank.</p>
            </div>
        </div>
    </div>

    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary">Why Choose <?php echo SITE_NAME; ?>?</h2>
        <p class="text-muted">Building trust, one component at a time</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 stat-card">
                <div class="card-body text-center p-4">
                    <i class="bi bi-shield-check text-primary fs-1 mb-3"></i>
                    <h4 class="fw-bold mb-3">Genuine Products</h4>
                    <p class="text-muted">100% authentic components with manufacturer warranties. We partner directly with brands to ensure authenticity.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 stat-card">
                <div class="card-body text-center p-4">
                    <i class="bi bi-truck text-primary fs-1 mb-3"></i>
                    <h4 class="fw-bold mb-3">Fast Delivery</h4>
                    <p class="text-muted">Nationwide shipping with same-day dispatch for Metro Manila orders. Track your package in real-time.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 stat-card">
                <div class="card-body text-center p-4">
                    <i class="bi bi-headset text-primary fs-1 mb-3"></i>
                    <h4 class="fw-bold mb-3">Expert Support</h4>
                    <p class="text-muted">Our team of certified technicians provides free build consultations and troubleshooting assistance.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card text-center stat-card">
                <div class="card-body p-4">
                    <div class="stat-number">5,000+</div>
                    <div class="text-muted">Happy Customers</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center stat-card">
                <div class="card-body p-4">
                    <div class="stat-number">2,500+</div>
                    <div class="text-muted">Products Available</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center stat-card">
                <div class="card-body p-4">
                    <div class="stat-number">98.7%</div>
                    <div class="text-muted">Positive Reviews</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center stat-card">
                <div class="card-body p-4">
                    <div class="stat-number">24/7</div>
                    <div class="text-muted">Customer Support</div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary">Trusted Brands</h2>
        <p class="text-muted">Partnering with industry leaders to bring you the best</p>
    </div>

    <div class="row g-4 mb-5">
        <?php
        $brands = [
            ['name' => 'NVIDIA', 'color1' => '#76b900', 'color2' => '#5d9c00'],
            ['name' => 'AMD', 'color1' => '#ed1c24', 'color2' => '#c00'],
            ['name' => 'Intel', 'color1' => '#0071c5', 'color2' => '#005ea2'],
            ['name' => 'ASUS', 'color1' => '#000', 'color2' => '#333'],
            ['name' => 'MSI', 'color1' => '#ff6a00', 'color2' => '#ff8c00'],
            ['name' => 'Corsair', 'color1' => '#000080', 'color2' => '#0000cd'],
            ['name' => 'Gigabyte', 'color1' => '#8b0000', 'color2' => '#b22222'],
            ['name' => 'Seasonic', 'color1' => '#2e8b57', 'color2' => '#3cb371']
        ];
        
        foreach ($brands as $brand):
        ?>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body text-center p-4" style="background: linear-gradient(135deg, <?php echo $brand['color1']; ?>, <?php echo $brand['color2']; ?>);">
                    <h5 class="card-title fw-bold text-white mb-0"><?php echo $brand['name']; ?></h5>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-5">
        <a href="products.php" class="btn btn-outline-primary btn-lg">Browse Our Products</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>