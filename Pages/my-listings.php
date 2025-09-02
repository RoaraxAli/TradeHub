<?php include '../includes/maintenance.php'?>
<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection(); // MySQLi connection

// Handle listing actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['listing_id'])) {
        $listing_id = (int)$_POST['listing_id'];
        $action = $_POST['action'];

        // Verify ownership
        $query = "SELECT id FROM listings WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $listing_id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            if ($action === 'delete') {
                $query = "UPDATE listings SET status = 'deleted' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $listing_id);
                $stmt->execute();
            } elseif ($action === 'toggle_status') {
                $query = "UPDATE listings 
                          SET status = CASE 
                                WHEN status = 'active' THEN 'inactive' 
                                ELSE 'active' 
                              END 
                          WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $listing_id);
                $stmt->execute();
            }
        }
        $stmt->close();
    }
}

// Get user's listings with counts
$query = "SELECT l.*,
          (SELECT COUNT(*) FROM trades WHERE (offered_item_id = l.id OR requested_item_id = l.id) AND status = 'pending') as pending_trades,
          (SELECT COUNT(*) FROM trades WHERE (offered_item_id = l.id OR requested_item_id = l.id) AND status = 'completed') as completed_trades
          FROM listings l
          WHERE l.user_id = ? AND l.status != 'deleted'
          ORDER BY l.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$listings = $result->fetch_all(MYSQLI_ASSOC);

$page_title = 'My Listings';
include '../includes/header.php';
?>

<div class="min-h-screen bg-slate-50">
    <div class="md:p-9">
        <div class="max-w-full mx-auto h-[100vh] md:h-[calc(95vh-3rem)]">
            <div class="flex h-full bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl border border-white/50 overflow-hidden animate-scale-in">
                <?php include '../includes/sidebar.php'; ?>
                <div class="flex-1 h-full overflow-y-auto">
    <div class="flex-1 flex flex-col">
    <?php include '../includes/head.php'; ?>
   <!-- Edit Listing Modal -->
<div id="editListingModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-3xl w-full max-w-3xl p-6 relative overflow-y-auto max-h-[90vh]">
    <button onclick="closeEditModal()" class="absolute top-4 right-4 text-slate-500 hover:text-slate-700">
      <i class="fas fa-times text-xl"></i>
    </button>
    <h2 class="text-xl font-bold mb-4">Edit Listing</h2>

    <form id="editListingForm" method="POST" enctype="multipart/form-data" class="space-y-6">
      <input type="hidden" name="listing_id" id="editListingId">

      <!-- Type -->
      <div class="space-y-3">
        <label class="text-slate-700 font-medium">What are you offering?</label>
        <div class="grid grid-cols-2 gap-4">
          <label class="relative">
            <input type="radio" name="type" value="product" class="sr-only peer" required>
            <div class="border-2 border-slate-200 rounded-2xl p-6 text-center cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:border-slate-300 transition-colors">
              <i class="fas fa-box text-2xl text-slate-600 peer-checked:text-emerald-600 mb-3"></i>
              <h3 class="font-semibold text-slate-900 mb-2">Product</h3>
            </div>
          </label>
          <label class="relative">
            <input type="radio" name="type" value="service" class="sr-only peer" required>
            <div class="border-2 border-slate-200 rounded-2xl p-6 text-center cursor-pointer peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:border-slate-300 transition-colors">
              <i class="fas fa-tools text-2xl text-slate-600 peer-checked:text-emerald-600 mb-3"></i>
              <h3 class="font-semibold text-slate-900 mb-2">Service</h3>
            </div>
          </label>
        </div>
      </div>

      <!-- Title & Category -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
          <label for="editTitle" class="text-slate-700 font-medium">Title *</label>
          <input id="editTitle" name="title" type="text" class="w-full px-4 py-3 rounded-2xl border border-slate-200">
        </div>
        <div class="space-y-2">
          <label for="editCategory" class="text-slate-700 font-medium">Category *</label>
          <select id="editCategory" name="category" class="w-full px-4 py-3 rounded-2xl border border-slate-200"></select>
        </div>
      </div>

      <!-- Description -->
      <div class="space-y-2">
        <label for="editDescription" class="text-slate-700 font-medium">Description *</label>
        <textarea id="editDescription" name="description" rows="4" class="w-full px-4 py-3 rounded-2xl border border-slate-200"></textarea>
      </div>

      <!-- Looking For -->
      <div class="space-y-2">
        <label for="editLookingFor" class="text-slate-700 font-medium">What are you looking for? *</label>
        <input id="editLookingFor" name="looking_for" type="text" class="w-full px-4 py-3 rounded-2xl border border-slate-200">
        <p class="text-xs text-slate-500">Separate multiple items with commas</p>
      </div>

      <!-- Images Upload -->
      <div class="space-y-2">
        <label class="text-slate-700 font-medium">Photos (2-4)</label>
        <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-emerald-400 transition-colors">
          <input type="file" id="editImages" name="images[]" accept="image/*" class="hidden" multiple onchange="previewEditImages(this)">
          <div id="editUploadArea" class="cursor-pointer" onclick="document.getElementById('editImages').click()">
            <i class="fas fa-camera text-3xl text-slate-400 mb-3"></i>
            <p class="text-slate-600 mb-2">Click to upload 2-4 photos</p>
            <p class="text-xs text-slate-500">JPG, PNG, GIF up to 10MB each</p>
          </div>
          <div id="editImagePreviews" class="grid grid-cols-2 gap-4 mt-4"></div>
        </div>
      </div>

      <!-- Submit -->
      <div class="pt-4 flex space-x-4">
        <button type="submit" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-4 rounded-2xl font-semibold text-lg">
          Update Listing
        </button>
        <button type="button" onclick="closeEditModal()" class="flex-1 border border-slate-200 hover:bg-slate-50 text-slate-700 py-4 rounded-2xl font-semibold text-lg">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>


    <div class="p-8 sm:px-6 py-6 sm:py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">My Listings</h1>
                <p class="text-slate-600">Manage your products and services</p>
            </div>
            <!-- Button -->
            <button id="openModalBtn" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold">
                <i class="fas fa-plus mr-2"></i>
                Create Listing
            </button>
            <?php include "listingmodal.php";?>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-3xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm">Total Listings</p>
                        <p class="text-2xl font-bold text-slate-900"><?php echo count($listings); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-list text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm">Active</p>
                        <p class="text-2xl font-bold text-slate-900">
                            <?php echo count(array_filter($listings, function($l) { return $l['status'] === 'active'; })); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-check text-emerald-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm">Pending Trades</p>
                        <p class="text-2xl font-bold text-slate-900">
                            <?php echo array_sum(array_column($listings, 'pending_trades')); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-clock text-amber-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm">Completed Trades</p>
                        <p class="text-2xl font-bold text-slate-900">
                            <?php echo array_sum(array_column($listings, 'completed_trades')); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-handshake text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listings -->
        <?php if (empty($listings)): ?>
            <div class="bg-white rounded-3xl shadow-lg p-12 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-box-open text-slate-400 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-slate-900 mb-2">No listings yet</h3>
                <p class="text-slate-600 mb-6">Create your first listing to start trading with the community</p>
                <button id="openModalBtn" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold">
                <i class="fas fa-plus mr-2"></i>
                    Create Your First Listing
            </button>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($listings as $listing): ?>
                    <div class="bg-white rounded-3xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex flex-col md:flex-row gap-6">
                            <!-- Image -->
                            <div class="md:w-48 flex-shrink-0">
                                <?php 
                                $imageUrls = explode(',', $listing['image_url']); 
                                $firstImage = trim($imageUrls[0]); 
                                ?>
                                <?php if (!empty($firstImage)): ?>
                                    <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>" class="w-full h-32 md:h-full object-cover rounded-2xl">
                                <?php else: ?>
                                    <div class="w-full h-32 md:h-full bg-slate-200 rounded-2xl flex items-center justify-center">
                                        <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> text-slate-400 text-3xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Content -->
                            <div class="flex-1">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between mb-4">
                                    <div>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 mb-2">
                                        <h3 class="text-xl font-semibold text-slate-900 mb-2 sm:mb-0">
                                            <?php echo htmlspecialchars($listing['title']); ?>
                                        </h3>

                                        <!-- Badges Container -->
                                        <div class="flex flex-wrap gap-2">
                                            <span class="px-3 py-1 rounded-xl text-xs font-medium <?php echo $listing['type'] === 'product' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>">
                                                <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> mr-1"></i>
                                                <?php echo ucfirst($listing['type']); ?>
                                            </span>

                                            <span class="px-3 py-1 rounded-xl text-xs font-medium <?php 
                                                echo $listing['completed_trades'] > 0 ? 'bg-green-100 text-green-700' : 
                                                    ($listing['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700');
                                            ?>">
                                                <?php echo $listing['completed_trades'] > 0 ? 'Traded' : ucfirst($listing['status']); ?>
                                            </span>
                                        </div>
                                    </div>

                                        <p class="text-slate-600 mb-3"><?php echo htmlspecialchars($listing['description']); ?></p>
                                        
                                        <!-- Looking For -->
                                        <div class="mb-3">
                                            <p class="text-xs text-slate-500 mb-1">Looking for:</p>
                                            <div class="flex flex-wrap gap-1">
                                                <?php 
                                                $looking_for = json_decode($listing['looking_for'], true) ?? [];
                                                foreach ($looking_for as $item): 
                                                ?>
                                                    <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-xl text-xs">
                                                        <?php echo htmlspecialchars($item); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Stats -->
                                        <div class="flex flex-wrap gap-2 text-sm text-slate-500">
                                            <span class="flex items-center"><i class="fas fa-clock mr-1"></i><?php echo formatTimeAgo($listing['created_at']); ?></span>
                                            <span class="flex items-center"><i class="fas fa-handshake mr-1"></i><?php echo $listing['pending_trades']; ?> pending</span>
                                            <span class="flex items-center"><i class="fas fa-check mr-1"></i><?php echo $listing['completed_trades']; ?> completed</span>
                                        </div>

                                    </div>
                                    <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row sm:items-center sm:space-x-2 space-y-2 sm:space-y-0 w-full sm:w-auto">
                                        <?php if ($listing['completed_trades'] == 0): ?>
                                            <form method="POST" class="w-full sm:w-auto">
                                                <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <button type="submit" 
                                                    class="w-full sm:w-auto p-2 border border-slate-200 hover:bg-slate-50 rounded-xl"
                                                    title="<?php echo $listing['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas fa-<?php echo $listing['status'] === 'active' ? 'pause' : 'play'; ?> text-slate-600"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button onclick="openEditModal(<?php echo $listing['id']; ?>)" 
                                            class="w-full sm:w-auto p-2 border border-slate-200 hover:bg-slate-50 rounded-xl flex items-center justify-center"
                                            title="Edit">
                                            <i class="fas fa-edit text-slate-600"></i>
                                        </button>

                                        <form method="POST" class="w-full sm:w-auto"
                                            onsubmit="return confirm('Are you sure you want to delete this listing?')">
                                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit"
                                                    class="w-full sm:w-auto p-2 border border-red-200 hover:bg-red-50 rounded-xl"
                                                    title="Delete">
                                                <i class="fas fa-trash text-red-600"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
</div>

<?php include '../includes/footer.php'; ?>
<script>
function openEditModal(listingId) {
    if (window.innerWidth <= 800) {
        window.location.href = 'edit-listing.php?id=' + listingId;
        return;
    }
  const modal = document.getElementById('editListingModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');

  fetch('get-listing.php?id=' + listingId)
    .then(res => res.json())
    .then(data => {
      document.getElementById('editListingId').value = data.id;
      document.getElementById('editTitle').value = data.title;
      document.getElementById('editDescription').value = data.description;
      document.getElementById('editLookingFor').value = (JSON.parse(data.looking_for) || []).join(', ');
      document.querySelector(`input[name="type"][value="${data.type}"]`).checked = true;

      // Populate category dropdown
      fetch('get-categories.php')
        .then(res => res.json())
        .then(categories => {
          const catSelect = document.getElementById('editCategory');
          catSelect.innerHTML = '';
          categories.forEach(c => {
            const option = document.createElement('option');
            option.value = c.name;
            option.textContent = c.name;
            if (c.name === data.category) option.selected = true;
            catSelect.appendChild(option);
          });
        });

      // Show current images
      const previews = document.getElementById('editImagePreviews');
      previews.innerHTML = '';
      (data.image_url.split(',') || []).forEach(img => {
        const image = document.createElement('img');
        image.src = img;
        image.className = 'w-full h-32 object-cover rounded-xl';
        previews.appendChild(image);
      });
    });
}

function closeEditModal() {
  const modal = document.getElementById('editListingModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.getElementById('editListingForm').reset();
  document.getElementById('editImagePreviews').innerHTML = '';
  document.getElementById('editUploadArea').classList.remove('hidden');
}

function previewEditImages(input) {
  const previewContainer = document.getElementById('editImagePreviews');
  const uploadArea = document.getElementById('editUploadArea');
  previewContainer.innerHTML = '';

  if (input.files) {
    const files = Array.from(input.files).slice(0, 4);
    files.forEach(file => {
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'w-full h-32 object-cover rounded-xl';
        previewContainer.appendChild(img);
      }
      reader.readAsDataURL(file);
    });
  }
}
document.getElementById('editListingForm').addEventListener('submit', function(e) {
    e.preventDefault(); // prevent normal submit

    const form = e.target;
    const formData = new FormData(form);

    fetch('update-listing.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeEditModal();
            // Reload the page to see the updated listing
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error(err);
    });
});

</script>
