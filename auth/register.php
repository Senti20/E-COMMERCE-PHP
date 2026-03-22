<?php
$pageTitle = "Register";

ob_start();

require_once __DIR__ . "/db.php";

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../index.php');
    }
    exit();
}

$errors = [];
$form_data = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data['full_name'] = trim($_POST['full_name'] ?? '');
    $form_data['username'] = trim($_POST['username'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($form_data['full_name'])) {
        $errors[] = "Full name is required";
    }
    
    if (empty($form_data['username'])) {
        $errors[] = "Username is required";
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = "Username must be at least 3 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }
    
    if (empty($form_data['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (!empty($form_data['phone']) && !preg_match('/^[0-9]{11}$/', $form_data['phone'])) {
        $errors[] = "Phone number must be 11 digits";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $form_data['username'], $form_data['email']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists";
        }
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer';
        
        $conn->begin_transaction();
        
        try {
            $insert_query = "INSERT INTO users (email, phone, full_name, username, profile_image, password_hash, role, created_at) 
                             VALUES (?, ?, ?, ?, 'assets/img/default-avatar.png', ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssssss", 
                $form_data['email'], 
                $form_data['phone'], 
                $form_data['full_name'], 
                $form_data['username'], 
                $hashed_password, 
                $role
            );
            $insert_stmt->execute();
            $user_id = $conn->insert_id;
            
            $cust_query = "INSERT INTO customers (user_id, full_name, username, created_at) 
                           VALUES (?, ?, ?, NOW())";
            $cust_stmt = $conn->prepare($cust_query);
            $cust_stmt->bind_param("iss", $user_id, $form_data['full_name'], $form_data['username']);
            $cust_stmt->execute();
            
            $conn->commit();
            
            $_SESSION['success_message'] = "Registration successful! Please login.";
            header('Location: login.php');
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #1f23fcff 0%, #000270ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }
        .auth-container {
            max-width: 500px;
            width: 100%;
            padding: 1rem;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
            border: none;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header .icon-wrapper {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1f23fcff 0%, #000270ff 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            border: 3px solid rgba(255,255,255,0.3);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        .login-header i {
            font-size: 2.5rem;
            color: white;
        }
        .login-header h4 {
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #000000ff, #000000ff);
            border: none;
            color: white;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #1f23fcff 0%, #000270ff 100%);
            color: white;
        }
        .btn-login i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        .btn-login:hover i {
            transform: translateX(5px);
        }
        .back-link {
            text-align: center;
        }
        .back-link a {
            color: #002fffff;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .back-link a:hover {
            color: #8000ffff;
        }
        .required::after {
            content: '*';
            color: #dc3545;
            margin-left: 3px;
        }
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 0.5rem;
            transition: all 0.3s;
        }
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .alert ul {
            margin-top: 0.5rem;
            padding-left: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="login-card">
            <div class="login-header">
                <div class="icon-wrapper">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h4>Create Account</h4>
                <p>Join <?php echo SITE_NAME; ?> today</p>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" onsubmit="return validateForm()">
                <div class="mb-3">
                    <label class="form-label required">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" name="full_name" 
                               value="<?php echo htmlspecialchars($form_data['full_name']); ?>" 
                               placeholder="Enter your full name" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label required">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                        <input type="text" class="form-control" name="username" 
                               value="<?php echo htmlspecialchars($form_data['username']); ?>" 
                               placeholder="Choose a username" required>
                    </div>
                    <small class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Minimum 3 characters (letters, numbers, underscores only)
                    </small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label required">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo htmlspecialchars($form_data['email']); ?>" 
                               placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Phone Number (Optional)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="text" class="form-control" name="phone" 
                               value="<?php echo htmlspecialchars($form_data['phone']); ?>" 
                               placeholder="09123456789" maxlength="11">
                    </div>
                    <small class="form-text">11-digit mobile number</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label required">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" 
                               id="password" placeholder="Create a password" 
                               onkeyup="checkPasswordStrength()" required>
                    </div>
                    <small class="form-text">
                        <i class="bi bi-shield-check me-1"></i>
                        Minimum 6 characters
                    </small>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label required">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" name="confirm_password" 
                               id="confirm_password" placeholder="Confirm your password" 
                               onkeyup="checkPasswordMatch()" required>
                    </div>
                    <small id="passwordMatchMsg" class="form-text"></small>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>
            
            <div class="back-link mt-3">
                <span class="text-muted">Already have an account?</span>
                <a href="login.php">Login Here</a>
            </div>
            
            <div class="back-link mt-2">
                <a href="../index.php">
                    <i class="bi bi-house"></i>
                    Back to Home
                </a>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="bi bi-shield-check me-1"></i>
                    Your information is secure and encrypted
                </small>
            </div>
        </div>
    </div>

    <script>
    function checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const strengthBar = document.getElementById('passwordStrength');
        
        let strength = 0;
        
        if (password.length >= 6) strength += 1;
        if (password.length >= 8) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        const colors = ['#dc3545', '#ffc107', '#fd7e14', '#0dcaf0', '#198754'];
        const widths = ['20%', '40%', '60%', '80%', '100%'];
        
        if (strength === 0) {
            strengthBar.style.width = '0';
            strengthBar.style.backgroundColor = 'transparent';
            strengthBar.style.height = '0';
        } else {
            strengthBar.style.width = widths[strength - 1];
            strengthBar.style.backgroundColor = colors[strength - 1];
            strengthBar.style.height = '5px';
            strengthBar.style.borderRadius = '5px';
        }
    }
    
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const msg = document.getElementById('passwordMatchMsg');
        
        if (confirm === '') {
            msg.innerHTML = '';
            msg.className = 'form-text';
        } else if (password === confirm) {
            msg.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>Passwords match';
            msg.className = 'text-success form-text';
        } else {
            msg.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Passwords do not match';
            msg.className = 'text-danger form-text';
        }
    }
    
    function validateForm() {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const username = document.querySelector('input[name="username"]').value;
        const usernameRegex = /^[a-zA-Z0-9_]+$/;
        const phone = document.querySelector('input[name="phone"]').value;
        const email = document.querySelector('input[name="email"]').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const fullName = document.querySelector('input[name="full_name"]').value;
        
        if (fullName.trim() === '') {
            alert('Full name is required');
            return false;
        }
        
        if (username.length < 3) {
            alert('Username must be at least 3 characters long!');
            return false;
        }
        
        if (!usernameRegex.test(username)) {
            alert('Username can only contain letters, numbers, and underscores!');
            return false;
        }
        
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address!');
            return false;
        }
        
        if (phone !== '' && !/^[0-9]{11}$/.test(phone)) {
            alert('Phone number must be 11 digits if provided!');
            return false;
        }
        
        if (password.length < 6) {
            alert('Password must be at least 6 characters long!');
            return false;
        }
        
        if (password !== confirm) {
            alert('Passwords do not match!');
            return false;
        }
        
        return true;
    }
    </script>
</body>
</html>
<?php ob_end_flush(); ?>