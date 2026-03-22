<?php
session_start();
require_once __DIR__ . "/db.php";

if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_phone'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = "";
$success = "";
$user_id = (int)$_SESSION['reset_user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp = trim($_POST["otp"] ?? "");
    
    if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
        $error = "Please enter a valid 6-digit OTP.";
    } else {
        $stmt = $conn->prepare("SELECT reset_otp_hash, reset_otp_expires, reset_otp_attempts FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user || !$user['reset_otp_hash'] || !$user['reset_otp_expires']) {
            $error = "No reset request found. Please start over.";
        } else {
            $attempts = $user['reset_otp_attempts'] + 1;
            
            $update = $conn->prepare("UPDATE users SET reset_otp_attempts = ? WHERE id = ?");
            $update->bind_param("ii", $attempts, $user_id);
            $update->execute();
            
            if ($attempts > 5) {
                $error = "Too many failed attempts. Please request a new code.";
                $clear = $conn->prepare("UPDATE users SET reset_otp_hash = NULL, reset_otp_expires = NULL, reset_otp_attempts = 0 WHERE id = ?");
                $clear->bind_param("i", $user_id);
                $clear->execute();
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_phone']);
            } else {
                $now = new DateTime();
                $expiry = new DateTime($user['reset_otp_expires']);
                
                if ($expiry < $now) {
                    $error = "OTP has expired. Please request a new one.";
                } else {
                    if (password_verify($otp, $user['reset_otp_hash'])) {
                        $_SESSION['reset_verified'] = true;
                        
                        $reset = $conn->prepare("UPDATE users SET reset_otp_attempts = 0 WHERE id = ?");
                        $reset->bind_param("i", $user_id);
                        $reset->execute();
                        
                        header("Location: reset_password.php");
                        exit();
                    } else {
                        $error = "Invalid OTP code. Please try again. Attempts remaining: " . (5 - $attempts);
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - <?php echo defined('SITE_NAME') ? SITE_NAME : 'PC Parts Hub'; ?></title>
    
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
        .otp-input {
            letter-spacing: 0.5rem;
            font-size: 2rem;
            text-align: center;
            font-weight: bold;
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
        .resend-link {
            color: #0d6efd;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="login-card">
            <div class="login-header">
                <div class="icon-wrapper">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h4>Verify OTP Code</h4>
                <p>Enter the 6-digit code sent to your mobile number</p>
                <small class="text-muted">Ending in <?php echo substr($_SESSION['reset_phone'], -4); ?></small>
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
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label required">6-Digit OTP Code</label>
                    <input type="text" class="form-control otp-input" name="otp" 
                           placeholder="••••••" maxlength="6" pattern="\d{6}" 
                           inputmode="numeric" autocomplete="off" autofocus required>
                    <small class="text-muted">Enter the 6-digit code sent via SMS</small>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-check-circle me-2"></i>Verify Code
                </button>
            </form>
            
            <div class="text-center mt-3">
                <p class="small text-muted mb-1">Didn't receive the code?</p>
                <a href="forgot_password.php" class="resend-link">
                    <i class="bi bi-arrow-repeat me-1"></i>Request Again
                </a>
            </div>
            
            <div class="back-link mt-3">
                <a href="login.php">
                    <i class="bi bi-arrow-left me-2"></i>Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>