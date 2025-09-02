<?php
require_once '../config/config.php';
$conn = getDBConnection();

$query = "SELECT setting_value FROM site_settings WHERE setting_key = 'site_name' LIMIT 1";
$result = $conn->query($query);

if ($result && $row = $result->fetch_assoc()) {
    echo htmlspecialchars($row['setting_value']);
}
?>
