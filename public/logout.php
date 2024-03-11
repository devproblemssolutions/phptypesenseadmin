<?php
error_reporting(0);
ini_set('error_reporting', 0);

// Initialize the session
session_start();

// Unset all session values 
$_SESSION = array();

// Destroy the session 
session_destroy();

// If you want to kill the session entirely, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header("Location: index.php");
exit;
?>