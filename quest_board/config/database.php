<?php
/**
 * Database Configuration
 * Adventurer Guild Registration & Quest Board
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quest_board_db');

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Max file size: 2MB
$MAX_FILE_SIZE = 2 * 1024 * 1024;

// Allowed image extensions
$ALLOWED_IMAGE_EXTENSIONS = array('jpg', 'jpeg', 'png', 'gif', 'webp');

/**
 * Create a new MySQLi database connection.
 * @return mysqli
 */
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Close a database connection safely
function closeConnection($conn) {
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
