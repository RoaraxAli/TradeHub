<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();
$error = '';
$success = '';
$listing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get listing data
$query = "SELECT * FROM listings WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    $error = 'Database error: ' . $conn->error;
    $conn->close();
    redirect('my-listings.php');
}
$stmt->bind_param('ii', $listing_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$listing = $result->fetch_assoc();
$stmt->close();

if (!$listing) {
    $conn->close();
    redirect('my-listings.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $type = sanitize($_POST['type']);
    $category = sanitize($_POST['category']);
    $looking_for = array_filter(array_map('trim', explode(',', $_POST['looking_for'])));
    
    if (empty($title) || empty($description) || empty($type) || empty($category)) {
        $error = 'Please fill in all required fields';
    } else {
        $image_urls = explode(',', $listing['image_url']); // existing images

        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {

            $num_files = count(array_filter($_FILES['images']['name']));
            if ($num_files < 2 || $num_files > 4) {
                $error = 'Please upload between 2 and 4 images.';
            } else {
                $upload_dir = UPLOAD_PATH . 'listings/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $new_image_urls = [];
        
                // Delete old images
                foreach ($image_urls as $old_img) {
                    if (file_exists($old_img)) unlink($old_img);
                }
        
                for ($i = 0; $i < $num_files; $i++) {
                    $file_tmp = $_FILES['images']['tmp_name'][$i];
                    $file_name = $_FILES['images']['name'][$i];
                    $file_type = $_FILES['images']['type'][$i];
                    $file_size = $_FILES['images']['size'][$i];
        
                    if (in_array($file_type, $allowed_types) && $file_size <= 10*1024*1024) {
                        $filename = uniqid() . '_' . basename($file_name);
                        $filepath = $upload_dir . $filename;
                        if (move_uploaded_file($file_tmp, $filepath)) {
                            $new_image_urls[] = $filepath;
                        }
                    }
                }
        
                // Update image_url string
                if (!empty($new_image_urls)) {
                    $image_url = implode(',', $new_image_urls);
                }
            }
        
        } else {
            // keep old images
            $image_url = $listing['image_url'];
        }
        

        
        $query = "UPDATE listings SET title = ?, description = ?, type = ?, category = ?, image_url = ?, looking_for = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $looking_for_json = json_encode($looking_for);
            $stmt->bind_param('ssssssi', $title, $description, $type, $category, $image_url, $looking_for_json, $listing_id);
            
            if ($stmt->execute()) {
                $success = 'Listing updated successfully!';
                // Refresh listing data
                $listing['title'] = $title;
                $listing['description'] = $description;
                $listing['type'] = $type;
                $listing['category'] = $category;
                $listing['image_url'] = $image_url;
                $listing['looking_for'] = json_encode($looking_for);
            } else {
                $error = 'Error updating listing. Please try again.';
            }
            $stmt->close();
        }
        $conn->close();
    }
}

$page_title = 'Edit Listing';
include '../includes/header.php';
?>

<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <a href="my-listings.php" class="flex items-center space-x-2 text-slate-600 hover:text-slate-900">
                <i class="fas fa-arrow-left"></i>
                <span>Back to My Listings</span>
            </a>
            <a href="dashboard.php" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-emerald-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-sync-alt text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-slate-900"><?php include "../includes/Name.php";?></span>
            </a>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <div class="w-full mx-auto px-4 sm:max-w-xl md:max-w-3xl lg:max-w-5xl xl:max-w-6xl">
            <div class="bg-white rounded-3xl shadow-lg p-8">
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-6">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl mb-6">
                        <?php echo $success; ?>
                        <div class="mt-3">
                            <a href="my-listings.php" class="text-green-600 hover:text-green-700 font-medium">View My Listings</a>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Type Selection -->
                    <div class="space-y-3">
    <label class="text-slate-700 font-medium">What are you offering?</label>
    <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
        <label class="relative">
            <input type="radio" name="type" value="product" class="sr-only peer" <?php echo $listing['type'] === 'product' ? 'checked' : ''; ?> required>
            <div class="border-2 border-slate-200 rounded-2xl p-6 text-center cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:border-slate-300 transition-colors h-full flex flex-col justify-between">
                <i class="fas fa-box text-2xl text-slate-600 peer-checked:text-emerald-600 mb-3"></i>
                <h3 class="font-semibold text-slate-900 mb-2">Product</h3>
                <p class="text-sm text-slate-600 line-clamp-3">Physical item to trade</p>
            </div>
        </label>

        <label class="relative">
            <input type="radio" name="type" value="service" class="sr-only peer" <?php echo $listing['type'] === 'service' ? 'checked' : ''; ?> required>
            <div class="border-2 border-slate-200 rounded-2xl p-6 text-center cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:border-slate-300 transition-colors h-full flex flex-col justify-between">
                <i class="fas fa-tools text-2xl text-slate-600 peer-checked:text-emerald-600 mb-3"></i>
                <h3 class="font-semibold text-slate-900 mb-2">Service</h3>
                <p class="text-sm text-slate-600 line-clamp-3">Skill / Service you provide</p>
            </div>
        </label>
    </div>
</div>

                    <!-- Title & Category Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Title -->
                    <div class="space-y-2">
                        <label for="title" class="text-slate-700 font-medium">Title *</label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            placeholder="What are you offering?"
                            value="<?php echo htmlspecialchars($listing['title']); ?>"
                            class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                            required
                        />
                    </div>

                    <!-- Category -->
                        <div class="space-y-2">
                            <label for="category" class="text-slate-700 font-medium">Category *</label>
                            <select
                                id="category"
                                name="category"
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                required
                            >
                            <option value="<?php echo htmlspecialchars($listing['category']); ?>">
                                <?php echo ucfirst(htmlspecialchars($listing['category'])); ?>
                            </option>

                                <?php
                                $query = "SELECT name FROM categories";
                                $result = mysqli_query($conn, $query);

                                if ($result && mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $categoryName = $row['name'];
                                        $selected = (isset($_POST['category']) && $_POST['category'] === $categoryName) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($categoryName) . "' $selected>" . htmlspecialchars($categoryName) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label for="description" class="text-slate-700 font-medium">Description *</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Describe your item or service in detail..."
                            class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                            required
                        ><?php echo htmlspecialchars($listing['description']); ?></textarea>
                    </div>

                    <!-- Current Image -->
                    <?php if (!empty($listing['image_url'])): ?>
                    <?php 
                        // Convert to array (comma-separated)
                        $images = array_map('trim', explode(',', $listing['image_url'])); 
                        $count = count($images);

                        // Decide grid classes based on image count
                        if ($count === 2) {
                            $gridClass = 'grid-cols-2';
                        } elseif ($count === 3) {
                            $gridClass = 'grid-cols-3';
                        } elseif ($count === 4) {
                            $gridClass = 'grid-cols-2'; // 2x2 grid
                        } else {
                            $gridClass = 'grid-cols-1';
                        }
                    ?>
                    
                    <div class="space-y-2">
                        <label class="text-slate-700 font-medium">Current Photos</label>
                        <div class="grid <?php echo $gridClass; ?> gap-4 border border-slate-200 rounded-2xl p-4">
                            <?php foreach ($images as $img): ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" 
                                    alt="Listing image" 
                                    class="w-full h-32 object-cover rounded-xl">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>


                    <!-- Image Upload -->
<div class="space-y-2">
    <label for="images" class="text-slate-700 font-medium">Update Photos (Optional, 2-4)</label>
    <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-emerald-400 transition-colors">
        <input type="file" id="images" name="images[]" accept="image/*" class="hidden" multiple onchange="previewImages(this)">
        <div id="uploadArea" class="cursor-pointer" onclick="document.getElementById('images').click()">
            <i class="fas fa-camera text-3xl text-slate-400 mb-3"></i>
            <p class="text-slate-600 mb-2">Click to upload 2-4 photos</p>
            <p class="text-xs text-slate-500">JPG, PNG, GIF up to 10MB each</p>
        </div>
        <div id="imagePreviews" class="grid grid-cols-2 gap-4 mt-4"></div>
    </div>
</div>


                    <!-- Looking For -->
                    <div class="space-y-2">
                        <label for="looking_for" class="text-slate-700 font-medium">What are you looking for? *</label>
                        <input
                            id="looking_for"
                            name="looking_for"
                            type="text"
                            placeholder="e.g., Guitar lessons, Photography services, Web design"
                            value="<?php echo htmlspecialchars(implode(', ', json_decode($listing['looking_for'], true) ?? [])); ?>"
                            class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                            required
                        />
                        <p class="text-xs text-slate-500">Separate multiple items with commas</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 flex space-x-4">
                        <button
                            type="submit"
                            class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-4 rounded-2xl font-semibold text-lg transition-colors"
                        >
                            Update
                        </button>
                        <a
                            href="my-listings.php"
                            class="flex-1 border border-slate-200 hover:bg-slate-50 text-slate-700 py-4 rounded-2xl font-semibold text-lg text-center transition-colors"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImages(input) {
    const previewContainer = document.getElementById('imagePreviews');
    const uploadArea = document.getElementById('uploadArea');
    previewContainer.innerHTML = '';
    
    if (input.files) {
        const files = Array.from(input.files).slice(0, 4); // max 4 files
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-full h-32 object-cover rounded-xl';
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
        uploadArea.classList.add('hidden');
    }
}

</script>

<?php include '../includes/footer.php'; ?>