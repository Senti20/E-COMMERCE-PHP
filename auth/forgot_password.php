<?php
session_start();
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/traccar_sms.php";

$error = "";
$success = "";

function format_phone_for_display($phone) {
    if (preg_match('/^09(\d{9})$/', $phone, $matches)) {
        return "0" . $matches[1];
    }
    if (preg_match('/^\+63(\d{10})$/', $phone, $matches)) {
        return "0" . $matches[1];
    }
    return $phone;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = trim($_POST["phone"] ?? "");
    
    if (empty($phone)) {
        $error = "Please enter your mobile number.";
    } elseif (!preg_match('/^09\d{9}$/', $phone) && !preg_match('/^\+639\d{9}$/', $phone)) {
        $error = "Please enter a valid Philippine mobile number (e.g., 09123456789).";
    } else {
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($clean_phone, 0, 2) == "63") {
            $clean_phone = "0" . substr($clean_phone, 2);
        }
        
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE phone = ?");
        $stmt->bind_param("s", $clean_phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            $error = "No account found with that mobile number.";
        } else {
            $otp = sprintf("%06d", random_int(0, 999999));
            $otpHash = password_hash($otp, PASSWORD_DEFAULT);
            $expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));
            $user_id = (int)$user['id'];
            
            $update = $conn->prepare("UPDATE users SET reset_otp_hash = ?, reset_otp_expires = ?, reset_otp_attempts = 0 WHERE id = ?");
            $update->bind_param("ssi", $otpHash, $expires, $user_id);
            
            if ($update->execute()) {
                list($sms_ok, $sms_response) = send_otp_sms($phone, $otp);
                
                if ($sms_ok) {
                    $_SESSION['reset_user_id'] = $user_id;
                    $_SESSION['reset_phone'] = $clean_phone;
                    
                    $success = "An OTP has been sent to " . format_phone_for_display($phone) . ".";
                    
                    header("refresh:3;url=verify_otp.php");
                } else {
                    $error = "Failed to send OTP. Please try again.";
                    
                    $clear = $conn->prepare("UPDATE users SET reset_otp_hash = NULL, reset_otp_expires = NULL WHERE id = ?");
                    $clear->bind_param("i", $user_id);
                    $clear->execute();
                }
            } else {
                $error = "Failed to generate OTP. Please try again.";
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
    <title>Forgot Password - <?php echo defined('SITE_NAME') ? SITE_NAME : 'SKYNET PC'; ?></title>
    
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
                    <i class="bi bi-key"></i>
                </div>
                <h4>Forgot Password?</h4>
                <p>Enter your mobile number to reset your password</p>
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
                <?php echo htmlspecialchars($success); ?>
                <br><small>Redirecting to OTP verification...</small>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label required">Mobile Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="tel" class="form-control" name="phone" 
                               placeholder="09123456789" maxlength="11" 
                               pattern="[0-9]{11}" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                               required>
                    </div>
                    <small class="text-muted">Enter the mobile number associated with your account</small>
                </div>
                
                <button type="submit" class="btn-login" <?php echo $success ? 'disabled' : ''; ?>>
                    <i class="bi bi-send me-2"></i>Send OTP via SMS
                </button>
            </form>
            
            <div class="back-link mt-3">
                <a href="login.php">
                    <i class="bi bi-arrow-left me-2"></i>Back to Login
                </a>
            </div>
            
            <div class="back-link mt-2">
                <a href="../index.php">
                    <i class="bi bi-house"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>