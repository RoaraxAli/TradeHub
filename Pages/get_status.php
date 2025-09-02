<?php
date_default_timezone_set('Asia/Karachi');

require_once '../config/config.php';

$conn = getDBConnection();
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// use $conn from config.php (must be mysqli connection)
$stmt = $conn->prepare("SELECT last_seen FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

function formatLastSeen($last_seen) {
    if (!$last_seen) return "Offline";

    $last_seen_time = strtotime($last_seen);
    $diff = time() - $last_seen_time;

    if ($diff <= 60) {
        return "Active Now";
    } elseif ($diff < 3600) {
        return "Active " . floor($diff / 60) . "m ago";
    } elseif ($diff < 86400) {
        return "Active " . floor($diff / 3600) . "h ago";
    } else {
        return "Offline";
    }
}

echo formatLastSeen($user['last_seen'] ?? null);

$stmt->close();
$conn->close();
