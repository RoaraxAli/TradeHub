<?php
require_once '../config/config.php';
$conn = getDBConnection();

$query = "SELECT setting_key, setting_value FROM site_settings 
          WHERE setting_key IN ('smtp_username', 'smtp_password', 'smtp_host', 'smtp_port')";
$result = $conn->query($query);

$smtp_settings = [];
while ($row = $result->fetch_assoc()) {
    $smtp_settings[$row['setting_key']] = $row['setting_value'];
}

// Example usage
$mail_host = $smtp_settings['smtp_host'] ?? '';
$mail_user = $smtp_settings['smtp_username'] ?? '';
$mail_pass = $smtp_settings['smtp_password'] ?? '';
$mail_port = $smtp_settings['smtp_port'] ?? '';
?>