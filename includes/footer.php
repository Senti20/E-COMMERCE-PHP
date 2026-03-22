<?php
// Make sure BASE_URL is defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/E-COMMERCE');
}
?>
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0 text-white-50">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>