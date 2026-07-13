<?php
/**
 * Authentication Checking
 * Include this file at the top of any page that requires login.
 * If the user is not logged in, redirect to login page.
 */

session_start();

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Ensure guild_id is available in session
if (!isset($_SESSION['guild_id'])) {
    require_once __DIR__ . '/functions.php';
    $conn = getConnection();
    $user = getUserById($conn, $_SESSION['user_id']);
    if ($user) {
        $_SESSION['guild_id'] = $user['guild_id'];
    }
    closeConnection($conn);
}
?>
