<div id="successModal" style="display:none; position:fixed; top:20px; left:50%; transform:translateX(-50%); background:#4caf50; color:white; padding:10px 20px; border-radius:6px; font-family:sans-serif;">
    Listing created successfully!
</div>
<div id="listingModal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-2xl shadow-lg w-[60%] max-h-[80vh] overflow-y-auto relative">
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
                    echo "<script>
                        const url = new URL(window.location);
                        url.searchParams.delete('open');
                        url.searchParams.set('success', '1');
                        window.history.replaceState({}, '', url);
                        location.reload(); // Refresh so PHP re-runs
                    </script>";

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
<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
    20%, 40%, 60%, 80% { transform: translateX(2px); }
}
.animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
.animate-shake { animation: shake 0.5s ease-in-out; }
</style>
<div class="container mx-auto px-4">
    <div class="max-w-3xl mx-auto bg-white bg-opacity-90 backdrop-blur-lg rounded-2xl shadow-xl p-8">
        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 animate-shake flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-teal-50 border border-teal-200 text-teal-700 px-4 py-3 rounded-lg mb-6 animate-fade-in-up flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?php echo $success; ?></span>
                <a href="my-listings.php" class="ml-4 text-teal-600 hover:text-teal-800 flex items-center">
                    <span>View My Listings</span>
                    <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <!-- Type Selection -->
            <div class="space-y-4 animate-fade-in-up" style="animation-delay: 0.1s;">
                <label class="block text-gray-700 font-semibold">What are you offering?</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="relative group cursor-pointer">
                        <input type="radio" name="type" value="product" class="sr-only peer" <?php echo (isset($_POST['type']) && $_POST['type'] === 'product') ? 'checked' : ''; ?> required>
                        <div class="border border-gray-200 rounded-lg p-6 text-center peer-checked:bg-teal-50 peer-checked:border-teal-500 hover:border-teal-300 transition-all duration-300">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 peer-checked:bg-teal-100">
                                <i class="fas fa-box text-xl text-gray-600 peer-checked:text-teal-600"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800">Product</h3>
                            <p class="text-sm text-gray-500">Physical items you want to trade</p>
                        </div>
                    </label>
                    <label class="relative group cursor-pointer">
                        <input type="radio" name="type" value="service" class="sr-only peer" <?php echo (isset($_POST['type']) && $_POST['type'] === 'service') ? 'checked' : ''; ?> required>
                        <div class="border border-gray-200 rounded-lg p-6 text-center peer-checked:bg-teal-50 peer-checked:border-teal-500 hover:border-teal-300 transition-all duration-300">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 peer-checked:bg-teal-100">
                                <i class="fas fa-tools text-xl text-gray-600 peer-checked:text-teal-600"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800">Service</h3>
                            <p class="text-sm text-gray-500">Skills or services you provide</p>
                        </div>
                    </label>
                </div>
            </div>
            <!-- Title and Category in Columns -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 space-y-0 animate-fade-in-up" style="animation-delay: 0.2s;">
                <!-- Title -->
                <div class="space-y-3">
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

                <!-- Category -->
                <div class="space-y-3">
                    <label for="category" class="text-slate-700 font-semibold text-lg flex items-center space-x-2">
                        <span>Category</span>
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative group">
                        <select name="category" id="category"
                            class="w-full px-6 py-4 rounded-2xl border-2 border-slate-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all duration-300 bg-white/50 backdrop-blur-sm hover:border-slate-300 text-lg appearance-none cursor-pointer"
                            required>
                            <option value="" selected disabled>Select a category</option>
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
                        <div class="absolute right-4 top-1/2 transform -translate-y-1/2 pointer-events-none">
                            <i class="fas fa-chevron-down text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="space-y-2 animate-fade-in-up" style="animation-delay: 0.3s;">
                <label for="description" class="block text-gray-700 font-semibold">Description <span class="text-red-500">*</span></label>
                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    placeholder="Describe your item or service in detail..."
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition-all bg-white resize-none"
                    required
                ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            

            <!-- Image Upload -->
            <div class="space-y-2 animate-fade-in-up" style="animation-delay: 0.5s;">
                <label for="images" class="block text-gray-700 font-semibold">Photos (2-4 required) <span class="text-red-500">*</span></label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-teal-400 transition-all bg-gray-50">
                    <input type="file" id="images" name="images[]" accept="image/*" multiple class="hidden" onchange="previewImages(this)" required>
                    <div id="uploadArea" class="cursor-pointer" onclick="document.getElementById('images').click()">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-camera text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-700 font-medium">Click to upload photos (2-4)</p>
                        <p class="text-sm text-gray-500">JPG, PNG, GIF up to 10MB each</p>
                    </div>
                    <div id="imagePreviews" class="grid grid-cols-2 gap-4 mt-4"></div>
                </div>
            </div>

            <!-- Looking For -->
            <div class="space-y-2 animate-fade-in-up" style="animation-delay: 0.6s;">
                <label for="looking_for" class="block text-gray-700 font-semibold">What are you looking for? <span class="text-red-500">*</span></label>
                <input
                    id="looking_for"
                    name="looking_for"
                    type="text"
                    placeholder="e.g., Guitar lessons, Photography services, Web design"
                    value="<?php echo isset($_POST['looking_for']) ? htmlspecialchars($_POST['looking_for']) : ''; ?>"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition-all bg-white"
                    required
                />
                <p class="text-sm text-gray-500 flex items-center">
                    <i class="fas fa-info-circle mr-1"></i>
                    Separate multiple items with commas
                </p>
            </div>

            <!-- Submit Button -->
            <div class="animate-fade-in-up" style="animation-delay: 0.7s;">
                <button
                    type="submit"
                    class="w-full bg-teal-500 hover:bg-teal-600 text-white py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
                >
                    <i class="fas fa-plus mr-2"></i>Create Listing
                </button>
            </div>
        </form>
    </div>
</div>

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
                    div.className = 'relative group animate-fade-in-up';
                    div.style.animationDelay = `${index * 0.1}s`;

                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-40 object-cover rounded-lg shadow-md">
                        <div class="absolute inset-0 bg-black bg-opacity-20 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="absolute top-2 right-2 w-8 h-8 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer hover:bg-red-600" onclick="removeImage(this, ${index})">
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
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg animate-shake';
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
        const fileInput = document.getElementById('images');
        fileInput.value = '';
        document.getElementById('uploadArea').style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-fade-in-up').forEach(el => {
            observer.observe(el);
        });
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php include '../includes/footer.php'; ?>
</div>
</div>
<script>
const modal = document.getElementById("listingModal");
const openBtn = document.getElementById("openModalBtn");
const closeBtn = document.getElementById("closeModalBtn");

// Open modal or redirect on small screens
function openModalOrRedirect() {
    if (window.innerWidth <= 800) {
        window.location.href = "create-listing.php";
    } else {
        modal.classList.remove("hidden");
        const url = new URL(window.location);
        url.searchParams.set("open", "listing");
        window.history.pushState({}, "", url);
    }
}

// Close modal and update URL
function closeModalOrRedirect() {
    if (window.innerWidth <= 800) {
        window.location.href = "create-listing.php";
    } else {
        modal.classList.add("hidden");
        const url = new URL(window.location);
        url.searchParams.delete("open");
        window.history.pushState({}, "", url);
    }
}

// Event listeners
openBtn.addEventListener("click", openModalOrRedirect);
closeBtn.addEventListener("click", closeModalOrRedirect);

// Close modal when clicking outside content
modal.addEventListener("click", function(e) {
    if (e.target === modal) {
        closeModalOrRedirect();
    }
});

// Open modal if query exists on page load
function checkModalOnLoad() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("open") === "listing") {
        if (window.innerWidth <= 800) {
            window.location.href = "create-listing.php";
        } else {
            modal.classList.remove("hidden");
        }
    } else {
        modal.classList.add("hidden");
    }
}
checkModalOnLoad();

// Handle window resize dynamically
window.addEventListener("resize", () => {
    if (!modal.classList.contains("hidden") && window.innerWidth <= 800) {
        window.location.href = "create-listing.php";
    }
});

$(document).ready(function() {
    const url = new URL(window.location);
    const $successModal = $('#successModal');

    if (url.searchParams.get('success') === '1') {
        // Slide in from top
        $successModal.slideDown(500); // 500ms animation

        // Hide after 3 seconds with slide up
        setTimeout(() => {
            $successModal.slideUp(500, function() {
                url.searchParams.delete('success'); // Clean URL
                window.history.replaceState({}, '', url);
            });
        }, 3000);
    }
});
</script>

