<?php
session_start();
require_once __DIR__ . "/db.php";

function redirect_to_login() {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION["role"]) && isset($_COOKIE["remember"])) {
    $raw = $_COOKIE["remember"];
    
    if (strpos($raw, ":") !== false) {
        list($uid, $token) = explode(":", $raw, 2);
        $uid = (int)$uid;
        
        if ($uid > 0 && $token) {
            $sql = "SELECT id, email, role, full_name, username, remember_token_hash, remember_token_expires FROM users WHERE id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user && $user["remember_token_hash"] && $user["remember_token_expires"]) {
                $now = new DateTime("now");
                $exp = new DateTime($user["remember_token_expires"]);
                
                $tokenHash = hash("sha256", $token);
                
                if (hash_equals($user["remember_token_hash"], $tokenHash) && $exp > $now) {
                    $_SESSION["user_id"] = (int)$user["id"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];
                    $_SESSION["full_name"] = $user["full_name"] ?? '';
                    $_SESSION["username"] = $user["username"] ?? '';
                } else {
                    setcookie("remember", "", time() - 3600, "/");
                }
            }
        }
    }
}

function require_role($role) {
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== $role) {
        redirect_to_login();
    }
}

function require_admin() {
    require_role("admin");
}

function require_customer() {
    require_role("customer");
}

function getCurrentUserName() {
    if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
        $name_parts = explode(' ', $_SESSION['full_name']);
        return $name_parts[0];
    } elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    return 'User';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>