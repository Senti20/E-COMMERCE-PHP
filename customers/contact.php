<?php
$pageTitle = "Contact Us";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    $success = "Thank you for contacting us! We'll get back to you soon.";
}
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5 customer-hero"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="fw-bold display-5">Contact Us</h1>
        <p class="lead">Get in touch with our expert team</p>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="mb-4">
                <h4 class="fw-bold">Phone Support</h4>
                <p class="text-muted mb-2">Call us for immediate assistance</p>
                <h5 class="text-primary fw-bold">(02) 8123-4567</h5>
                <p class="text-muted small mb-0">Monday - Friday: 9AM - 6PM</p>
                <p class="text-muted small">Saturday: 10AM - 4PM</p>
            </div>

            <div class="mb-4">
                <h4 class="fw-bold">Email Us</h4>
                <p class="text-muted mb-2">We'll respond within 24 hours</p>
                <p class="mb-1"><a href="mailto:support@pcartshub.ph" class="text-primary text-decoration-none">support@pcartshub.ph</a></p>
                <p><a href="mailto:sales@pcartshub.ph" class="text-primary text-decoration-none">sales@pcartshub.ph</a></p>
            </div>

            <div class="mb-4">
                <h4 class="fw-bold">Visit Our Store</h4>
                <p class="text-muted mb-2">Visit our physical store</p>
                <p class="fw-bold mb-0">PC Parts Hub Main Store</p>
                <p class="text-muted mb-0">123 Tech Avenue, Makati City</p>
                <p class="text-muted">Metro Manila, Philippines 1200</p>
            </div>

            <div class="mt-4">
                <h4 class="fw-bold mb-3">Follow Us</h4>
                <p class="text-muted mb-3">Stay updated with our latest products</p>
                <div class="d-flex gap-2">
                    <a href="#" class="social-btn facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-btn instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-btn twitter"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="social-btn youtube"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="social-btn linkedin"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="mb-4">
                <h4 class="fw-bold mb-3">Our Location</h4>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12345.67890!2d120.123456!3d17.123456!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1234567890abcdef%3A0x1234567890abcdef!2sBontoc%2C%20Mountain%20Province!5e0!3m2!1sen!2sph!4v1234567890"
                        width="100%" 
                        height="400" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                </div>
            </div>

            <div>
                <h4 class="fw-bold mb-3">Business Hours</h4>
                <table class="business-hours-table">
                    <tr>
                        <td>Monday - Friday</td>
                        <td class="fw-bold">9:00 AM - 6:00 PM</td>
                    </tr>
                    <tr>
                        <td>Saturday</td>
                        <td class="fw-bold">10:00 AM - 4:00 PM</td>
                    </tr>
                    <tr>
                        <td>Sunday</td>
                        <td class="fw-bold text-danger">Closed</td>
                    </tr>
                    <tr>
                        <td>Public Holidays</td>
                        <td class="fw-bold text-danger">Closed</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>