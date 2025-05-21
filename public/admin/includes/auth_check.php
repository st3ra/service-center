<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'worker', 'editor'])) {
    header('Location: /admin/login.php');
    exit;
}