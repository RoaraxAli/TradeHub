<?php
require_once '../config/config.php';
$conn = getDBConnection();

$query = "SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_mode' LIMIT 1";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row && $row['setting_value'] == '1') {
    die('<div style="text-align:center; padding:50px; font-size:24px; font-weight:bold; color:red;">
            🚧 Site Under Maintenance 🚧<br><br>
            Please check back later.
         </div>');
}
?>
