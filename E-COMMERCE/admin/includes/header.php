<?php
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../auth/auth_check.php';
require_admin();

$admin_id = $_SESSION['user_id'];
$admin = getUserById($conn, $admin_id);

$admin_name = $admin['full_name'] ?? 'Administrator';
$admin_username = $admin['username'] ?? 'admin';
$admin_email = $admin['email'] ?? '';
$admin_image = $admin['profile_image'] ?? '../assets/img/default-avatar.png';

$current_page = basename($_SERVER['PHP_SELF']);

global $db;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Admin Panel' : 'Admin Panel'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <link rel="stylesheet" href="../css/admin-style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../sidebar.php'; ?>
        
        <div class="admin-main">
            
            <div class="container-fluid px-4 py-3">
                <div class="page-header mb-4">
                    <div>
                        <h4 class="mb-1"><?php echo $pageTitle ?? 'Dashboard'; ?></h4>
                        <small class="text-muted"><?php echo date('l, F j, Y'); ?></small>
                    </div>
                </div>