<?php
session_start();
require_once __DIR__ . "/db.php";

if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
    header("Location: forgot_password.php");
    exit();
}

$error = "";
$success = "";
$user_id = (int)$_SESSION['reset_user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    
    if (empty($password)) {
        $error = "Password is required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $update = $conn->prepare("UPDATE users SET password_hash = ?, reset_otp_hash = NULL, reset_otp_expires = NULL, reset_otp_attempts = 0, remember_token_hash = NULL, remember_token_expires = NULL WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user_id);
        
        if ($update->execute()) {
            $success = "Password reset successful! Redirecting to login...";
            
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_phone']);
            unset($_SESSION['reset_verified']);
            
            setcookie("remember", "", time() - 3600, "/");
            
            header("refresh:3;url=login.php");
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo defined('SITE_NAME') ? SITE_NAME : 'PC Parts Hub'; ?></title>
    
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
            font-family: 'Inter', sans-serif;
        }
        .auth-container {
            max-width: 450px;
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
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #2c3e50, #212529);
            color: white;
        }
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 0.5rem;
            transition: all 0.3s;
        }
        .back-link {
            text-align: center;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .back-link a:hover {
            color: #764ba2;
        }
        .required::after {
            content: '*';
            color: #dc3545;
            margin-left: 3px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="login-card">
            <div class="login-header">
                <div class="icon-wrapper">
                    <i class="bi bi-key-fill"></i>
                </div>
                <h4>Reset Password</h4>
                <p>Create a new password for your account</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label required">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" 
                               id="password" placeholder="Enter new password" 
                               onkeyup="checkPasswordStrength()" required>
                    </div>
                    <small class="text-muted">Minimum 6 characters</small>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label required">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" name="confirm_password" 
                               id="confirm_password" placeholder="Confirm new password" 
                               onkeyup="checkPasswordMatch()" required>
                    </div>
                    <small id="passwordMatchMsg" class="text-muted"></small>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-check-circle me-2"></i>Reset Password
                </button>
            </form>
            <?php endif; ?>
            
            <div class="back-link mt-3">
                <a href="login.php">
                    <i class="bi bi-arrow-left me-2"></i>Back to Login
                </a>
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
            msg.className = 'text-muted';
        } else if (password === confirm) {
            msg.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>Passwords match';
            msg.className = 'text-success';
        } else {
            msg.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Passwords do not match';
            msg.className = 'text-danger';
        }
    }
    </script>
</body>
</html>