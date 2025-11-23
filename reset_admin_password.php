<?php
/**
 * MyLearn LMS - Admin Password Reset Utility
 * 
 * This script resets the admin password to the default "admin123".
 * Use this if you can't login with the default credentials.
 * 
 * IMPORTANT: Delete this file immediately after use for security reasons.
 */

// Include database configuration
require_once __DIR__ . '/config/database.php';

// Check if mysqli extension is loaded
if (!extension_loaded('mysqli')) {
    die('ERROR: The mysqli extension is not installed or enabled on your server.');
}

$success = false;
$error = '';

try {
    $conn = getDBConnection();
    
    // New password hash for "admin123"
    $new_password = '$2y$10$XcbJ/p4qPFijy./isgOkDOmq2liNzfZ4Eyn6TqCieRlaE5ciPmCkK';
    
    // Update admin password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@mylearn.com' AND user_type = 'admin'");
    $stmt->bind_param("s", $new_password);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $success = true;
        } else {
            $error = 'Admin user not found in database. Please import the database schema first.';
        }
    } else {
        $error = 'Failed to update password: ' . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset - MyLearn LMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Password Reset</h1>
        
        <div class="warning">
            <strong>⚠️ Security Warning:</strong> Delete this file (<code>reset_admin_password.php</code>) immediately after use!
        </div>

        <?php if ($success): ?>
            <div class="success">
                <h3>✓ Password Reset Successful!</h3>
                <p>The admin password has been reset to: <code>admin123</code></p>
                <p><strong>Login credentials:</strong></p>
                <ul>
                    <li>Email: <code>admin@mylearn.com</code></li>
                    <li>Password: <code>admin123</code></li>
                </ul>
                <p><strong>⚠️ IMPORTANT:</strong></p>
                <ol>
                    <li>Delete this file NOW for security</li>
                    <li>Login and change the admin password immediately</li>
                    <li>Access the login page: <a href="index.php">index.php</a></li>
                </ol>
            </div>
        <?php elseif ($error): ?>
            <div class="error">
                <h3>✗ Error</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
                <p><strong>Possible solutions:</strong></p>
                <ul>
                    <li>Make sure you have imported the database schema from <code>config/init_db.sql</code></li>
                    <li>Check your database connection settings in <code>config/database.php</code></li>
                    <li>Verify the mysqli extension is enabled (run <code>system_check.php</code>)</li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="info">
            <h3>About This Tool</h3>
            <p>This utility resets the admin password to the default value. Use it if:</p>
            <ul>
                <li>You imported an older version of the database with an incorrect password hash</li>
                <li>The default admin login (admin@mylearn.com / admin123) doesn't work</li>
                <li>You need to reset the admin account</li>
            </ul>
        </div>

        <?php if ($success): ?>
            <div class="warning">
                <strong>Action Required:</strong> Click the button below to delete this file, then login.
                <br><br>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?delete=1" style="display:inline;">
                    <button type="submit" class="btn" style="background-color: #dc3545;">Delete This File Now</button>
                </form>
            </div>
            
            <?php
            if (isset($_GET['delete']) && $_GET['delete'] == '1') {
                if (unlink(__FILE__)) {
                    echo '<div class="success">File deleted successfully! Redirecting to login...</div>';
                    echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 2000);</script>';
                } else {
                    echo '<div class="error">Could not delete file automatically. Please delete <code>reset_admin_password.php</code> manually.</div>';
                }
            }
            ?>
        <?php endif; ?>
    </div>
</body>
</html>
