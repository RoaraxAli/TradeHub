<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// update last seen to NOW
$stmt = $conn->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
$stmt->execute([$user_id]);
?>