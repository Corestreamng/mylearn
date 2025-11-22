<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if (isset($_GET['logout'])) {
    logout();
}
?>
