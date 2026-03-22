<?php
$pageTitle = "Edit Profile";
require_once 'includes/header.php';
require_once __DIR__ . '/../auth/db.php';

$admin_id = $_SESSION['user_id'];
$admin = getUserById($conn, $admin_id);
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ssi", $username, $email, $admin_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = "Username or email already exists";
    }
    
    $password_changed = false;
    if (!empty($new_password) || !empty($current_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } else {
            $pass_query = "SELECT password_hash FROM users WHERE id = ?";
            $pass_stmt = $conn->prepare($pass_query);
            $pass_stmt->bind_param("i", $admin_id);
            $pass_stmt->execute();
            $pass_result = $pass_stmt->get_result();
            $user_data = $pass_result->fetch_assoc();
            
            if (!password_verify($current_password, $user_data['password_hash'])) {
                $errors[] = "Current password is incorrect";
            }
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if ($new_password != $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            $password_changed = true;
        }
    }
    
    $profile_image = $admin['profile_image'] ?? 'assets/img/default-avatar.png';
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
            
            $new_filename = 'admin_' . $admin_id . '_' . uniqid() . '.' . $ext;
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
        if ($password_changed) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET 
                            full_name = ?, 
                            username = ?, 
                            email = ?, 
                            phone = ?,
                            profile_image = ?, 
                            password_hash = ? 
                            WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssssssi", $full_name, $username, $email, $phone, $profile_image, $new_password_hash, $admin_id);
        } else {
            $update_query = "UPDATE users SET 
                            full_name = ?, 
                            username = ?, 
                            email = ?, 
                            phone = ?,
                            profile_image = ? 
                            WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssssi", $full_name, $username, $email, $phone, $profile_image, $admin_id);
        }
        
        if ($update_stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username'] = $username;
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = "success";
            
            $admin = getUserById($conn, $admin_id);
            $success = "Profile updated successfully!";
        } else {
            $errors[] = "Failed to update profile";
        }
    }
}

$admin = getUserById($conn, $admin_id);
?>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <img src="../<?php echo $admin['profile_image'] ?? 'assets/img/default-avatar.png'; ?>" 
                     alt="Profile" class="rounded-circle img-fluid mb-3" 
                     style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #0d6efd;">
                <h4><?php echo htmlspecialchars($admin['full_name'] ?? 'Administrator'); ?></h4>
                <p class="text-muted">@<?php echo htmlspecialchars($admin['username'] ?? 'admin'); ?></p>
                <p class="text-muted"><i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($admin['email'] ?? ''); ?></p>
                <p class="text-muted"><i class="bi bi-phone me-2"></i><?php echo htmlspecialchars($admin['phone'] ?? 'No phone'); ?></p>
                <hr>
                <p class="text-muted small">
                    <i class="bi bi-calendar me-2"></i>Member since: <?php echo date('F d, Y', strtotime($admin['created_at'] ?? 'now')); ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label required">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label required">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>" 
                                   placeholder="09123456789">
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                   accept="image/*" onchange="previewImage(this, 'imagePreview')">
                            <small class="text-muted">Leave empty to keep current image. Max size: 2MB. Allowed: JPG, PNG, GIF</small>
                            <div class="mt-2 text-center">
                                <img id="imagePreview" src="#" alt="Preview" 
                                     style="max-width: 150px; max-height: 150px; display: none; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Change Password (Leave empty to keep current)</h6>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   onkeyup="checkPasswordStrength()">
                            <small class="text-muted">Minimum 6 characters</small>
                            <div class="progress mt-1" style="height: 5px;">
                                <div id="passwordStrength" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   onkeyup="checkPasswordMatch()">
                            <small id="passwordMatchMsg" class="text-muted"></small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            alert('File size too large. Maximum size is 2MB.');
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.style.display = 'block';
            preview.src = e.target.result;
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        preview.src = '#';
    }
}

function checkPasswordStrength() {
    const password = document.getElementById('new_password').value;
    const strengthBar = document.getElementById('passwordStrength');
    let strength = 0;
    
    if (password.length >= 6) strength += 20;
    if (password.length >= 8) strength += 20;
    if (/[A-Z]/.test(password)) strength += 20;
    if (/[0-9]/.test(password)) strength += 20;
    if (/[^A-Za-z0-9]/.test(password)) strength += 20;
    
    strengthBar.style.width = strength + '%';
    
    if (strength < 40) {
        strengthBar.className = 'progress-bar bg-danger';
    } else if (strength < 80) {
        strengthBar.className = 'progress-bar bg-warning';
    } else {
        strengthBar.className = 'progress-bar bg-success';
    }
}

function checkPasswordMatch() {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    const msg = document.getElementById('passwordMatchMsg');
    
    if (confirmPass === '') {
        msg.innerHTML = '';
        msg.className = 'text-muted';
    } else if (newPass === confirmPass) {
        msg.innerHTML = '✓ Passwords match';
        msg.className = 'text-success';
    } else {
        msg.innerHTML = '✗ Passwords do not match';
        msg.className = 'text-danger';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>