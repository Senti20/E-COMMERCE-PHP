<?php
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "eccomerce";

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

try {
    $db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("set names utf8");
} catch(PDOException $e) {
    die("PDO Connection error: " . $e->getMessage());
}

function getUserById($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, email, phone, role, full_name, username, profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getCustomerByUserId($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

define('SITE_NAME', 'SKYNET PC');
define('BASE_URL', 'http://localhost/E-COMMERCE');
define('SITE_URL', BASE_URL . '/');
define('ADMIN_URL', BASE_URL . '/admin/');

$site_settings = [
    'currency' => '₱',
    'shipping_fee' => 250,
    'free_shipping_min' => 50000
];

function isAdminLoggedIn() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . 'auth/login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . 'auth/login.php');
        exit();
    }
}
?>