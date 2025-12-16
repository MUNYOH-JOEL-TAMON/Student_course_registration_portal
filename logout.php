<?php
// logout.php
session_start();

// Store logout message
$logout_message = "You have been successfully logged out.";

// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with message
header("Location: login.php?message=" . urlencode($logout_message));
exit();
?>