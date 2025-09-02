<?php
require_once '../config/config.php';
requireLogin();
$conn = getDBConnection();

if (isset($_POST['receiver_id']) && isset($_POST['message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message = trim($_POST['message']);
    $sender_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
}
?>
