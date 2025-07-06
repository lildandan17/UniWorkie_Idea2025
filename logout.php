<?php
session_start();

// No need for config.php if you're just destroying the session
// If you need DB connection for cleanup, include your config with proper path

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Determine where to redirect
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$is_seller_logout = strpos($referer, '/seller/') !== false;

// Redirect to appropriate login page
if ($is_seller_logout) {
    header("Location: ../login.php"); // Goes up one level from seller to root
} else {
    header("Location: login.php"); // Stays in root
}
exit();
?>