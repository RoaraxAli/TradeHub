<?php
require_once '../config/config.php';
requireLogin();
$conn = getDBConnection();

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
echo json_encode($data);
?>