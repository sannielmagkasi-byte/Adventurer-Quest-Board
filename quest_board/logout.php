<?php
/**
 * Logout Handler
 * Destroys the session and redirects to the home page.
 */

// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the "Remember Me" cookies
setcookie('guild_remember', '', time() - 3600, "/");

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: index.php");
exit();
?>
