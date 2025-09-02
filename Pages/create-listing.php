<?php
require_once '../config/config.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $type = sanitize($_POST['type']);
    $category = sanitize($_POST['category']);
    $looking_for = array_filter(array_map('trim', explode(',', $_POST['looking_for'])));

    if (empty($title) || empty($description) || empty($type) || empty($category)) {
        $error = 'Please fill in all required fields';
    } elseif (!isset($_FILES['images']) || count($_FILES['images']['name']) < 2 || count($_FILES['images']['name']) > 4) {
        $error = 'Please upload between 2 and 4 images';
    } else {
        $conn = getDBConnection();

        $image_urls = [];
        $upload_dir = UPLOAD_PATH . 'listings/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 10 * 1024 * 1024; // 10MB

        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $file_type = $_FILES['images']['type'][$key];
                $file_size = $_FILES['images']['size'][$key];

                if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                    $filename = uniqid() . '_' . basename($name);
                    $filepath = $upload_dir . $filename;

                    if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $filepath)) {
                        $image_urls[] = $filepath;
                    } else {
                        $error = 'Error uploading image: ' . $name;
                        break;
                    }
                } else {
                    $error = 'Invalid file type or size for: ' . $name;
                    break;
                }
            } else {
                $error = 'Error uploading image: ' . $name;
                break;
            }
        }

        if (empty($error) && count($image_urls) >= 2) {
            $query = "INSERT INTO listings (user_id, title, description, type, category, image_url, looking_for, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $looking_for_json = json_encode($looking_for);
                $image_urls_string = implode(',', $image_urls); // Store as comma-separated string
                $stmt->bind_param('issssss', $_SESSION['user_id'], $title, $description, $type, $category, $image_urls_string, $looking_for_json);

                if ($stmt->execute()) {
                    $success = 'Listing created successfully!';
                    $_POST = array();
                    $_FILES = array();
                } else {
                    $error = 'Error creating listing. Please try again.';
                }
                $stmt->close();
            }
            $conn->close();
        }
    }
}

$page_title = 'Create Listing';
include '../includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/30 to-slate-100 relative overflow-hidden">
    <!-- Added animated background elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-emerald-400/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-emerald-400/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-xl border-b border-slate-200/50 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <a href="my-listings.php" class="flex items-center space-x-2 text-slate-600 hover:text-slate-900 transition-all duration-300 hover:scale-105 group">
                <div class="w-8 h-8 bg-slate-100 rounded-xl flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                    <i class="fas fa-arrow-left text-sm group-hover:text-emerald-600 transition-colors"></i>
                </div>
                <span class="font-medium">Back to Listings</span>
            </a>
            <a href="dashboard.php" class="flex items-center space-x-2 hover:scale-105 transition-transform duration-300">
                <div class="w-8 h-8 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/25">
                    <i class="fas fa-sync-alt text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent"><?php include "../includes/Name.php";?></span>
            </a>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 relative z-10">
        <div class="max-w-2xl mx-auto">
            <!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 text-center animate-bounce-in">
        <div class="flex flex-col items-center space-y-4">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-3xl">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">Success!</h2>
            <p class="text-slate-600"><?php echo $success; ?></p>
            <div class="flex space-x-4 pt-4">
                <a href="my-listings.php"
                   class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-xl font-medium shadow-md transition-all duration-300">
                   View My Listings
                </a>
                <a href="my-listings.php"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-6 py-3 rounded-xl font-medium transition-all duration-300">
                        Close
                </a>
            </div>
        </div>
    </div>
</div>

            <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 p-8 hover:shadow-3xl transition-all duration-500 animate-slide-up">
                <?php if ($error): ?>
                    <div class="bg-gradient-to-r from-red-50 to-red-100/50 border border-red-200/50 text-red-700 px-6 py-4 rounded-2xl mb-6 animate-shake backdrop-blur-sm">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-exclamation-triangle text-red-500"></i>
                            <span><?php echo $error; ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-gradient-to-r from-emerald-50 to-emerald-100/50 border border-emerald-200/50 text-emerald-700 px-6 py-4 rounded-2xl mb-6 animate-bounce-in backdrop-blur-sm">
                        <div class="flex items-center space-x-2 mb-2">
                            <i class="fas fa-check-circle text-emerald-500"></i>
                            <span><?php echo $success; ?></span>
                        </div>
                        <div class="mt-3">
                            <a href="my-listings.php" class="inline-flex items-center space-x-2 text-emerald-600 hover:text-emerald-700 font-medium transition-colors hover:scale-105 transform duration-200">
                                <span>View My Listings</span>
                                <i class="fas fa-arrow-right text-sm"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="createListingForm" enctype="multipart/form-data" class="space-y-8">
                    <!-- Type Selection -->
                    <div class="space-y-4 animate-fade-in-up" style="animation-delay: 0.1s;">
                        <label class="text-slate-700 font-semibold text-lg">What are you offering?</label>
                        <div class="grid grid-cols-2 gap-6">
                            <label class="relative group cursor-pointer">
                                <input type="radio" name="type" value="product" class="sr-only peer" <?php echo (isset($_POST['type']) && $_POST['type'] === 'product') ? 'checked' : ''; ?> required>
                                <div class="border-2 border-slate-200 rounded-3xl p-8 text-center cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-gradient-to-br peer-checked:from-emerald-50 peer-checked:to-emerald-100/50 hover:border-emerald-300 hover:shadow-lg transition-all duration-300 hover:scale-105 group-hover:shadow-emerald-500/10">
                                    <div class="w-16 h-16 bg-gradient-to-r from-slate-100 to-slate-200 peer-checked:from-emerald-100 peer-checked:to-emerald-200 rounded-2xl flex items-center justify-center mx-auto mb-4 transition-all duration-300">
                                        <i class="fas fa-box text-2xl text-slate-600 peer-checked:text-emerald-600 transition-colors"></i>
                                    </div>
                                    <h3 class="font-bold text-slate-900 text-lg mb-2">Product</h3>
                                    <p class="text-sm text-slate-600">Physical item</p>
                                </div>
                            </label>
                            <label class="relative group cursor-pointer">
                                <input type="radio" name="type" value="service" class="sr-only peer" <?php echo (isset($_POST['type']) && $_POST['type'] === 'service') ? 'checked' : ''; ?> required>
                                <div class="border-2 border-slate-200 rounded-3xl p-8 text-center cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-gradient-to-br peer-checked:from-emerald-50 peer-checked:to-emerald-100/50 hover:border-emerald-300 hover:shadow-lg transition-all duration-300 hover:scale-105 group-hover:shadow-emerald-500/10">
                                    <div class="w-16 h-16 bg-gradient-to-r from-slate-100 to-slate-200 peer-checked:from-emerald-100 peer-checked:to-emerald-200 rounded-2xl flex items-center justify-center mx-auto mb-4 transition-all duration-300">
                                        <i class="fas fa-tools text-2xl text-slate-600 peer-checked:text-emerald-600 transition-colors"></i>
                                    </div>
                                    <h3 class="font-bold text-slate-900 text-lg mb-2">Service</h3>
                                    <p class="text-sm text-slate-600">Skills or service</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="space-y-3 animate-fade-in-up" style="animation-delay: 0.2s;">
                        <label for="title" class="text-slate-700 font-semibold text-lg flex items-center space-x-2">
                            <span>Title</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative group">
                            <input
                                id="title"
                                name="title"
                                type="text"
                                placeholder="What are you offering?"
                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                class="w-full px-6 py-4 rounded-2xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all duration-300 bg-white/50 backdrop-blur-sm hover:border-slate-300 text-lg placeholder-slate-400"
                                required
                            />
                            <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-emerald-500/0 to-emerald-500/0 group-focus-within:from-emerald-500/5 group-focus-within:to-emerald-600/5 transition-all duration-300 pointer-events-none"></div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-3 animate-fade-in-up" style="animation-delay: 0.3s;">
                        <label for="description" class="text-slate-700 font-semibold text-lg flex items-center space-x-2">
                            <span>Description</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative group">
                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                placeholder="Describe your item or service in detail..."
                                class="w-full px-6 py-4 rounded-2xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all duration-300 bg-white/50 backdrop-blur-sm hover:border-slate-300 text-lg placeholder-slate-400 resize-none"
                                required
                            ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-emerald-500/0 to-emerald-500/0 group-focus-within:from-emerald-500/5 group-focus-within:to-emerald-600/5 transition-all duration-300 pointer-events-none"></div>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="space-y-3 animate-fade-in-up" style="animation-delay: 0.4s;">
                        <label for="category" class="text-slate-700 font-semibold text-lg flex items-center space-x-2">
                            <span>Category</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative group">
                            <select
                                id="category"
                                name="category"
                                class="w-full px-6 py-4 rounded-2xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all duration-300 bg-white/50 backdrop-blur-sm hover:border-slate-300 text-lg appearance-none cursor-pointer"
                                required
                            >
                                <option value="">Select a category</option>
                                <option value="music" <?php echo (isset($_POST['category']) && $_POST['category'] === 'music') ? 'selected' : ''; ?>>🎵 Music & Instruments</option>
                                <option value="technology" <?php echo (isset($_POST['category']) && $_POST['category'] === 'technology') ? 'selected' : ''; ?>>💻 Technology</option>
                                <option value="health-beauty" <?php echo (isset($_POST['category']) && $_POST['category'] === 'health-beauty') ? 'selected' : ''; ?>>💄 Health & Beauty</option>
                                <option value="education" <?php echo (isset($_POST['category']) && $_POST['category'] === 'education') ? 'selected' : ''; ?>>📚 Education</option>
                                <option value="design" <?php echo (isset($_POST['category']) && $_POST['category'] === 'design') ? 'selected' : ''; ?>>🎨 Design</option>
                                <option value="electronics" <?php echo (isset($_POST['category']) && $_POST['category'] === 'electronics') ? 'selected' : ''; ?>>⚡ Electronics</option>
                            </select>
                            <div class="absolute right-4 top-1/2 transform -translate-y-1/2 pointer-events-none">
                                <i class="fas fa-chevron-down text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="space-y-3 animate-fade-in-up" style="animation-delay: 0.5s;">
                        <label for="images" class="text-slate-700 font-semibold text-lg flex items-center space-x-2">
                            <span>Photos (2-4 required)</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="border-3 border-dashed border-slate-300 rounded-3xl p-8 text-center hover:border-emerald-400 transition-all duration-300 bg-gradient-to-br from-slate-50/50 to-white/50 backdrop-blur-sm group hover:shadow-lg">
                            <input type="file" id="images" name="images[]" accept="image/*" multiple class="hidden" onchange="previewImages(this)" required>
                            <div id="uploadArea" class="cursor-pointer" onclick="document.getElementById('images').click()">
                                <div class="w-20 h-20 bg-gradient-to-r from-slate-100 to-slate-200 rounded-3xl flex items-center justify-center mx-auto mb-4 group-hover:from-emerald-100 group-hover:to-emerald-200 transition-all duration-300">
                                    <i class="fas fa-camera text-4xl text-slate-400 group-hover:text-emerald-500 transition-colors"></i>
                                </div>
                                <p class="text-slate-700 mb-2 font-medium text-lg">Click to upload photos (2-4)</p>
                                <p class="text-sm text-slate-500">JPG, PNG, GIF up to 10MB each</p>
                            </div>
                            <div id="imagePreviews" class="grid grid-cols-2 gap-4 mt-6"></div>
                        </div>
                    </div>

                    <!-- Looking For -->
                    <div class="space-y-3 animate-fade-in-up" style="animation-delay: 0.6s;">
                        <label for="looking_for" class="text-slate-700 font-semibold text-lg flex items-center space-x-2">
                            <span>What are you looking for?</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative group">
                            <input
                                id="looking_for"
                                name="looking_for"
                                type="text"
                                placeholder="e.g., Guitar lessons, Photography services, Web design"
                                value="<?php echo isset($_POST['looking_for']) ? htmlspecialchars($_POST['looking_for']) : ''; ?>"
                                class="w-full px-6 py-4 rounded-2xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all duration-300 bg-white/50 backdrop-blur-sm hover:border-slate-300 text-lg placeholder-slate-400"
                                required
                            />
                            <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-emerald-500/0 to-emerald-500/0 group-focus-within:from-emerald-500/5 group-focus-within:to-emerald-600/5 transition-all duration-300 pointer-events-none"></div>
                        </div>
                        <p class="text-sm text-slate-500 flex items-center space-x-2">
                            <i class="fas fa-info-circle text-slate-400"></i>
                            <span>Separate multiple items with commas</span>
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6 animate-fade-in-up" style="animation-delay: 0.7s;">
                        <button
                            type="submit"
                            class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white py-5 rounded-2xl font-bold text-xl transition-all duration-300 shadow-2xl shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:scale-[1.02] active:scale-[0.98] relative overflow-hidden group"
                        >
                            <span class="relative z-10 flex items-center justify-center space-x-3">
                                <i class="fas fa-plus"></i>
                                <span>Create Listing</span>
                            </span>
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fade-in-up {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slide-up {
    from { opacity: 0; transform: translateY(50px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes bounce-in {
    0% { opacity: 0; transform: scale(0.3); }
    50% { opacity: 1; transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { opacity: 1; transform: scale(1); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
    20%, 40%, 60%, 80% { transform: translateX(2px); }
}

.animate-fade-in { animation: fade-in 0.6s ease-out; }
.animate-fade-in-up { animation: fade-in-up 0.6s ease-out both; }
.animate-slide-up { animation: slide-up 0.8s ease-out; }
.animate-bounce-in { animation: bounce-in 0.6s ease-out; }
.animate-shake { animation: shake 0.5s ease-in-out; }

.border-3 { border-width: 3px; }
</style>

<script>
function previewImages(input) {
    const previewsContainer = document.getElementById('imagePreviews');
    const uploadArea = document.getElementById('uploadArea');
    previewsContainer.innerHTML = '';

    if (input.files && input.files.length >= 2 && input.files.length <= 4) {
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative group animate-fade-in';
                div.style.animationDelay = `${index * 0.1}s`;

                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-48 object-cover rounded-2xl shadow-lg group-hover:shadow-xl transition-all duration-300 border-2 border-white">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="absolute top-2 right-2 w-8 h-8 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer hover:bg-red-600" onclick="removeImage(this, ${index})">
                        <i class="fas fa-times text-white text-sm"></i>
                    </div>
                `;
                previewsContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
        uploadArea.style.display = 'none';
    } else {
        uploadArea.style.display = 'block';
        previewsContainer.innerHTML = '';
        if (input.files && input.files.length > 0) {
            // Create error message with animation
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl animate-shake';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Please select between 2 and 4 images.';
            previewsContainer.appendChild(errorDiv);

            setTimeout(() => {
                errorDiv.remove();
            }, 3000);
        }
    }
}

function removeImage(element, index) {
    element.parentElement.remove();
    // Reset file input to trigger change event
    const fileInput = document.getElementById('images');
    fileInput.value = '';
    document.getElementById('uploadArea').style.display = 'block';
}

// Add smooth scroll behavior
document.addEventListener('DOMContentLoaded', function() {
    // Animate form elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.animate-fade-in-up').forEach(el => {
        el.style.animationPlayState = 'paused';
        observer.observe(el);
    });
});
</script>
<?php if ($success): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('createListingForm').style.display = 'none';
    document.getElementById('successModal').classList.remove('hidden');
});
</script>
<?php endif; ?>


<?php include '../includes/footer.php'; ?>
