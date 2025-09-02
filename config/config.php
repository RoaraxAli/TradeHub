<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'TradeHub');
define('DB_USER', 'root');
define('DB_PASS', '');
// Site configuration
define('SITE_URL', 'http://localhost/TradeHub');
define('SITE_NAME', getSiteName());
define('UPLOAD_PATH', 'uploads/');

function getSiteName() {
    $conn = getDBConnection();
    $query = "SELECT setting_value FROM site_settings WHERE setting_key = 'site_name' LIMIT 1";
    $result = $conn->query($query);
    $siteName = "TradeHub"; // fallback value
    if ($result && $row = $result->fetch_assoc()) {
        $siteName = $row['setting_value'];
    }
    $conn->close();
    return $siteName;
}
// Database connection using MySQLi
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Helper functions
function redirect($url) {
    // Handle relative URLs
    if (!str_starts_with($url, 'http')) {
        $url = SITE_URL . '/' . ltrim($url, '/');
    }
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('auth/auth.php');
    }
}

function sanitize($data) {
    $conn = getDBConnection();
    $data = $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
    $conn->close();
    return $data;
}

function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}

// Get base URL for assets
function getBaseUrl() {
    return SITE_URL;
}

// Get asset URL
function asset($path) {
    return getBaseUrl() . '/assets/' . ltrim($path, '/');
}
?>