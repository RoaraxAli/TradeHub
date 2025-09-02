<?php
require_once '../config/config.php';

function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getAdminId() {
    return isAdmin() ? $_SESSION['user_id'] : null;
}

function getAdminName() {
    return isAdmin() ? ($_SESSION['user_name'] ?? 'Admin') : null;
}

function requireAdminAuth() {
    if (!isAdmin()) {
        redirect('../auth/login.php'); // or wherever your login page is
        exit;
    }
}
?>
