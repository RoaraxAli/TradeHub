<?php
require_once '../config/config.php';
$conn = getDBConnection();
$res = mysqli_query($conn, "SELECT name FROM categories");
$categories = mysqli_fetch_all($res, MYSQLI_ASSOC);
echo json_encode($categories);
?>
