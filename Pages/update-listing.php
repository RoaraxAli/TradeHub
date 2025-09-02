<?php
require_once '../config/config.php';
requireLogin();
$conn = getDBConnection();

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$listing_id = (int)$_POST['listing_id'];

// Verify ownership
$stmt = $conn->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $listing_id, $_SESSION['user_id']);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$listing) {
    echo json_encode(['error' => 'Listing not found']);
    exit;
}

// Sanitize inputs
$title = sanitize($_POST['title']);
$description = sanitize($_POST['description']);
$type = sanitize($_POST['type']);
$category = sanitize($_POST['category']);
$looking_for = array_filter(array_map('trim', explode(',', $_POST['looking_for'])));

if(empty($title) || empty($description) || empty($type) || empty($category)){
    echo json_encode(['error' => 'Please fill in all required fields']);
    exit;
}

// Handle images
$image_url = $listing['image_url'];
if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    $upload_dir = UPLOAD_PATH . 'listings/';
    if(!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    $allowed_types = ['image/jpeg','image/png','image/gif'];
    $new_image_urls = [];

    // Delete old images
    foreach(explode(',', $listing['image_url']) as $old_img){
        if(file_exists($old_img)) unlink($old_img);
    }

    $num_files = count(array_filter($_FILES['images']['name']));
    if($num_files < 2 || $num_files > 4){
        echo json_encode(['error'=>'Please upload 2-4 images']);
        exit;
    }

    for($i=0;$i<$num_files;$i++){
        $file_tmp = $_FILES['images']['tmp_name'][$i];
        $file_name = $_FILES['images']['name'][$i];
        $file_type = $_FILES['images']['type'][$i];
        $file_size = $_FILES['images']['size'][$i];

        if(in_array($file_type,$allowed_types) && $file_size<=10*1024*1024){
            $filename = uniqid().'_'.basename($file_name);
            $filepath = $upload_dir.$filename;
            if(move_uploaded_file($file_tmp,$filepath)){
                $new_image_urls[] = $filepath;
            }
        }
    }

    if(!empty($new_image_urls)) $image_url = implode(',', $new_image_urls);
}

// Update DB
$looking_for_json = json_encode($looking_for);
$stmt = $conn->prepare("UPDATE listings SET title=?, description=?, type=?, category=?, image_url=?, looking_for=?, updated_at=NOW() WHERE id=?");
$stmt->bind_param('ssssssi', $title, $description, $type, $category, $image_url, $looking_for_json, $listing_id);

if($stmt->execute()){
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['error'=>'Error updating listing']);
}

$stmt->close();
$conn->close();
?>
