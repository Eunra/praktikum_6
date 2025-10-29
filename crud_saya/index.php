<?php
require_once 'config/session.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Redirect to login if not logged in
header('Location: login.php');
exit();
?>
