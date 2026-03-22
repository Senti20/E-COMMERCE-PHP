<?php
$pageTitle = "My Profile";
require_once '../includes/header.php';
require_once '../includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'profile.php';
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$cust_query = "SELECT * FROM customers WHERE user_id = ?";
$cust_stmt = $conn->prepare($cust_query);
$cust_stmt->bind_param("i", $user_id);
$cust_stmt->execute();
$cust_result = $cust_stmt->get_result();
$customer = $cust_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ssi", $username, $email, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = "Username or email already exists";
    }
    
    if (!empty($new_password) || !empty($current_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if ($new_password != $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    $profile_image = $user['profile_image'] ?? 'assets/img/default-avatar.png';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['profile_image']['size'];
        
        if ($file_size > 2 * 1024 * 1024) {
            $errors[] = "File size too large. Maximum size is 2MB.";
        } elseif (in_array($ext, $allowed)) {
            $upload_dir = '../assets/img/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'user_' . $user_id . '_' . uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                if ($profile_image != 'assets/img/default-avatar.png' && file_exists('../' . $profile_image)) {
                    unlink('../' . $profile_image);
                }
                $profile_image = 'assets/img/profiles/' . $new_filename;
            } else {
                $errors[] = "Failed to upload image";
            }
        } else {
            $errors[] = "Invalid file type. Allowed: jpg, jpeg, png, gif";
        }
    }
    
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, profile_image = ?, password_hash = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssssssi", $full_name, $username, $email, $phone, $profile_image, $hashed_password, $user_id);
            } else {
                $update_query = "UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("sssssi", $full_name, $username, $email, $phone, $profile_image, $user_id);
            }
            $update_stmt->execute();
            
            if ($customer) {
                $cust_update = "UPDATE customers SET full_name = ?, username = ?, address = ? WHERE user_id = ?";
                $cust_stmt = $conn->prepare($cust_update);
                $cust_stmt->bind_param("sssi", $full_name, $username, $address, $user_id);
                $cust_stmt->execute();
            } else {
                $cust_insert = "INSERT INTO customers (user_id, full_name, username, address, created_at) VALUES (?, ?, ?, ?, NOW())";
                $cust_stmt = $conn->prepare($cust_insert);
                $cust_stmt->bind_param("isss", $user_id, $full_name, $username, $address);
                $cust_stmt->execute();
            }
            
            $conn->commit();
            
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username'] = $username;
            
            $success = "Profile updated successfully!";
            
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            $cust_stmt = $conn->prepare($cust_query);
            $cust_stmt->bind_param("i", $user_id);
            $cust_stmt->execute();
            $cust_result = $cust_stmt->get_result();
            $customer = $cust_result->fetch_assoc();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to update profile: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex flex-column justify-content-center align-items-center py-5"
     style="background-image: url('../assets/img/Pc.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
    <div class="header-overlay"></div>
    <div class="container" style="position:relative; z-index:1;">
        <div class="d-flex flex-column align-items-center text-center text-white">
            <h1 class="fw-bold display-5">My Profile</h1>
            <p class="lead">Manage your account information</p>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <img src="../<?php echo $user['profile_image'] ?? 'assets/img/default-avatar.png'; ?>" 
                         alt="Profile" class="rounded-circle img-fluid mb-3" 
                         style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #0d6efd;">
                    <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="text-muted small">
                        <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <p class="text-muted small">
                        <i class="bi bi-phone me-2"></i><?php echo htmlspecialchars($user['phone'] ?? 'No phone'); ?>
                    </p>
                    <hr>
                    <p class="text-muted small">
                        <i class="bi bi-calendar me-2"></i>Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </p>
                    <div class="d-grid">
                        <a href="orders.php" class="btn btn-outline-primary">
                            <i class="bi bi-box me-2"></i>My Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Profile</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label required">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label required">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label required">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                       placeholder="09123456789">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                       accept="image/*" onchange="previewImage(this, 'imagePreview')">
                                <small class="text-muted">Leave empty to keep current image. Max size: 2MB</small>
                                <div class="mt-2">
                                    <img id="imagePreview" src="#" alt="Preview" style="max-width: 150px; max-height: 150px; display: none;">
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                                <h6 class="mb-3">Change Password (Leave empty to keep current)</h6>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).style.display = 'block';
            document.getElementById(previewId).src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>