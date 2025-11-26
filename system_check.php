<?php
/**
 * MyLearn LMS - System Check
 * 
 * This script checks if your server meets the requirements for running MyLearn LMS.
 * Upload this file to your server root and access it via your browser.
 * 
 * IMPORTANT: Delete this file after checking for security reasons.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyLearn LMS - System Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        .check-pass {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }
        .check-fail {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .check-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .status {
            font-weight: bold;
            margin-right: 10px;
            min-width: 60px;
        }
        .pass { color: #28a745; }
        .fail { color: #dc3545; }
        .warning { color: #ffc107; }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>MyLearn LMS - System Requirements Check</h1>
        
        <div class="warning-box">
            <strong>⚠️ Security Warning:</strong> Please delete this file (<code>system_check.php</code>) after checking your system requirements.
        </div>

        <?php
        $allPassed = true;
        
        // Check PHP Version
        $phpVersion = phpversion();
        $phpVersionOk = version_compare($phpVersion, '7.4.0', '>=');
        if (!$phpVersionOk) $allPassed = false;
        ?>
        
        <div class="check-item <?php echo $phpVersionOk ? 'check-pass' : 'check-fail'; ?>">
            <span class="status <?php echo $phpVersionOk ? 'pass' : 'fail'; ?>">
                <?php echo $phpVersionOk ? '✓ PASS' : '✗ FAIL'; ?>
            </span>
            <div>
                <strong>PHP Version:</strong> <?php echo $phpVersion; ?>
                <?php if (!$phpVersionOk): ?>
                    <br><small>Required: PHP 7.4 or higher. Please upgrade your PHP version.</small>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Check mysqli extension
        $mysqliLoaded = extension_loaded('mysqli');
        if (!$mysqliLoaded) $allPassed = false;
        ?>
        
        <div class="check-item <?php echo $mysqliLoaded ? 'check-pass' : 'check-fail'; ?>">
            <span class="status <?php echo $mysqliLoaded ? 'pass' : 'fail'; ?>">
                <?php echo $mysqliLoaded ? '✓ PASS' : '✗ FAIL'; ?>
            </span>
            <div>
                <strong>MySQLi Extension:</strong> <?php echo $mysqliLoaded ? 'Installed' : 'NOT INSTALLED'; ?>
                <?php if (!$mysqliLoaded): ?>
                    <br><small><strong>This is required!</strong> Enable it in cPanel > Select PHP Version > Extensions > mysqli</small>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Check JSON extension
        $jsonLoaded = extension_loaded('json');
        ?>
        
        <div class="check-item <?php echo $jsonLoaded ? 'check-pass' : 'check-warning'; ?>">
            <span class="status <?php echo $jsonLoaded ? 'pass' : 'warning'; ?>">
                <?php echo $jsonLoaded ? '✓ PASS' : '⚠ WARN'; ?>
            </span>
            <div>
                <strong>JSON Extension:</strong> <?php echo $jsonLoaded ? 'Installed' : 'Not installed (recommended)'; ?>
            </div>
        </div>

        <?php
        // Check fileinfo extension
        $fileinfoLoaded = extension_loaded('fileinfo');
        ?>
        
        <div class="check-item <?php echo $fileinfoLoaded ? 'check-pass' : 'check-warning'; ?>">
            <span class="status <?php echo $fileinfoLoaded ? 'pass' : 'warning'; ?>">
                <?php echo $fileinfoLoaded ? '✓ PASS' : '⚠ WARN'; ?>
            </span>
            <div>
                <strong>Fileinfo Extension:</strong> <?php echo $fileinfoLoaded ? 'Installed' : 'Not installed (recommended for file uploads)'; ?>
            </div>
        </div>

        <?php
        // Check upload directory
        $uploadDir = __DIR__ . '/assets/uploads/';
        $uploadDirExists = is_dir($uploadDir);
        $uploadDirWritable = $uploadDirExists && is_writable($uploadDir);
        ?>
        
        <div class="check-item <?php echo $uploadDirWritable ? 'check-pass' : 'check-warning'; ?>">
            <span class="status <?php echo $uploadDirWritable ? 'pass' : 'warning'; ?>">
                <?php echo $uploadDirWritable ? '✓ PASS' : '⚠ WARN'; ?>
            </span>
            <div>
                <strong>Upload Directory:</strong> 
                <?php 
                if (!$uploadDirExists) {
                    echo 'Not found (will be created automatically)';
                } elseif (!$uploadDirWritable) {
                    echo 'Not writable - Run: <code>chmod -R 755 assets/uploads/</code>';
                } else {
                    echo 'Writable';
                }
                ?>
            </div>
        </div>

        <?php
        // Check max upload size
        $maxUpload = ini_get('upload_max_filesize');
        $maxPost = ini_get('post_max_size');
        ?>
        
        <div class="check-item check-pass">
            <span class="status pass">ℹ INFO</span>
            <div>
                <strong>Upload Limits:</strong> 
                Max file size: <?php echo $maxUpload; ?>, 
                Max post size: <?php echo $maxPost; ?>
                <br><small>Recommended: 50M or higher for video uploads</small>
            </div>
        </div>

        <hr style="margin: 30px 0;">

        <?php if ($allPassed): ?>
            <div class="info">
                <h3 style="margin-top: 0; color: #28a745;">✓ All Critical Requirements Met!</h3>
                <p>Your server meets all the critical requirements to run MyLearn LMS.</p>
                <p><strong>Next steps:</strong></p>
                <ol>
                    <li>Configure database settings in <code>config/database.php</code></li>
                    <li>Import database schema from <code>config/init_db.sql</code></li>
                    <li>Delete this <code>system_check.php</code> file for security</li>
                    <li>Access the login page at <code>index.php</code></li>
                </ol>
            </div>
        <?php else: ?>
            <div class="warning-box">
                <h3 style="margin-top: 0; color: #dc3545;">✗ Some Requirements Not Met</h3>
                <p>Please fix the issues marked as <strong>FAIL</strong> above before proceeding.</p>
                
                <?php if (!$mysqliLoaded): ?>
                    <h4>To Enable MySQLi Extension on cPanel:</h4>
                    <ol>
                        <li>Login to your cPanel</li>
                        <li>Go to "Software" section</li>
                        <li>Click "Select PHP Version" or "MultiPHP Manager"</li>
                        <li>Click on "Extensions" tab</li>
                        <li>Find and check "mysqli"</li>
                        <li>Click "Save"</li>
                        <li>Refresh this page to verify</li>
                    </ol>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="info" style="margin-top: 30px;">
            <strong>Server Information:</strong><br>
            PHP Version: <?php echo phpversion(); ?><br>
            Server Software: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
            Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?>
        </div>
    </div>
</body>
</html>
