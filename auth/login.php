<?php
session_start();
require_once __DIR__ . "/db.php";

if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] === "admin") {
        header("Location: ../admin/dashboard");
    } else {
        header("Location: ../index");
    }
    exit;
}

$error = "";

function set_remember_me($conn, $userId) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);
    $expires = (new DateTime("+30 days"))->format("Y-m-d H:i:s");
    
    $stmt = $conn->prepare("UPDATE users SET remember_token_hash = ?, remember_token_expires = ? WHERE id = ?");
    $stmt->bind_param("ssi", $tokenHash, $expires, $userId);
    $stmt->execute();
    
    setcookie(
        "remember",
        $userId . ":" . $token,
        [
            "expires" => time() + (30 * 24 * 60 * 60),
            "path" => "/",
            "httponly" => true,
            "samesite" => "Lax"
        ]
    );
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $remember = isset($_POST["remember"]);
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Fetch full_name and username as well
        $stmt = $conn->prepare("SELECT id, email, password_hash, role, full_name, username FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION["user_id"] = (int)$user["id"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["full_name"] = $user["full_name"] ?? '';
            $_SESSION["username"] = $user["username"] ?? '';
            
            if ($remember) {
                set_remember_me($conn, (int)$user["id"]);
            }
            
            if ($user["role"] === "admin") {
                header("Location: ../admin/dashboard");
            } else {
                header("Location: ../index");
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
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
        .form-check-label {
            font-size: 0.95rem;
        }
        .small {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="login-card">
            <div class="login-header">
                <div class="icon-wrapper">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h4>Welcome Back!</h4>
                <p>Login to your <?php echo SITE_NAME; ?> account</p>
            </div>
            
            <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label required">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input class="form-control" name="email" type="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label required">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input class="form-control" name="password" type="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label small" for="remember">
                            Remember me
                        </label>
                    </div>
                    <a class="small text-decoration-none" href="forgot_password">
                        Forgot password?
                    </a>
                </div>
                
                <button class="btn-login" type="submit">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
            
            <div class="back-link mt-3">
                <span class="text-muted">Don't have an account?</span>
                <a href="register">Register Here</a>
            </div>
            
            <div class="back-link mt-2">
                <a href="../index">
                    <i class="bi bi-house"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>