<?php
require_once '../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$conn = getDBConnection();
$stmt = $conn->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: profile.php?id=$user_id");
exit;
