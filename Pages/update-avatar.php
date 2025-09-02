<?php
require_once '../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['avatar'];

    // Validate file type and size
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed) || $file['size'] > 2 * 1024 * 1024) {
        die("Invalid file. Must be JPG/PNG/WEBP and <= 2MB.");
    }

    // Upload directory
    $upload_dir = './uploads/avatars/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
    $target_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Save URL to database
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $url_path = './uploads/avatars/' . $new_filename;
        $stmt->bind_param('si', $url_path, $user_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        header("Location: profile.php?id=$user_id");
        exit;
    } else {
        die("Failed to upload image.");
    }
}
