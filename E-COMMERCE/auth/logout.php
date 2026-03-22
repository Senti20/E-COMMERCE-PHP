<?php
session_start();
require_once __DIR__ . "/db.php";

if (isset($_SESSION["user_id"])) {
    $uid = (int)$_SESSION["user_id"];
    $stmt = $conn->prepare("UPDATE users SET remember_token_hash = NULL, remember_token_expires = NULL WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
}

setcookie("remember", "", time() - 3600, "/");

$_SESSION = array();

session_destroy();

header("Location: ../index?logout=success");
exit;
?>