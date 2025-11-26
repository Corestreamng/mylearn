<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mylearn_lms');

// Create connection
function getDBConnection() {
    // Check if mysqli extension is loaded
    if (!extension_loaded('mysqli')) {
        die('ERROR: The mysqli extension is not installed or enabled on your server. Please contact your hosting provider to enable it, or check your PHP configuration. For cPanel users, you can enable it through PHP Selector or MultiPHP Manager.');
    }
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}
?>
