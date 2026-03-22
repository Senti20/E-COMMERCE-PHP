<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../auth/db.php';

// Site configuration
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'SKYNET PC');
}

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Customer Pages CSS - Fix path for both root and customers folder -->
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/customers/') !== false) ? '../css/customer-style.css' : 'css/customer-style.css'; ?>">
</head>
<body>