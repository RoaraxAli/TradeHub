<?php
require_once '../config/config.php';
require_once 'includes/admin_auth.php';

$conn = getDBConnection();

$success = '';
$error = '';

// Handle listing actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['listing_id'])) {
        $listing_id = (int)$_POST['listing_id'];
        $action = $_POST['action'];
        
        switch ($action) {
            case 'activate':
                $query = "UPDATE listings SET status = 'active' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $listing_id);
                if ($stmt->execute()) {
                    $success = 'Listing activated successfully';
                }
                break;
                
            case 'deactivate':
                $query = "UPDATE listings SET status = 'inactive' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $listing_id);
                if ($stmt->execute()) {
                    $success = 'Listing deactivated successfully';
                }
                break;
                
            case 'delete':
                $query = "UPDATE listings SET status = 'deleted' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $listing_id);
                if ($stmt->execute()) {
                    $success = 'Listing deleted successfully';
                }
                break;
        }
    }
    
    if (isset($_POST['create_listing'])) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $type = sanitize($_POST['type']);
        $category = sanitize($_POST['category']);
        $user_id = (int)$_POST['user_id'];
        
        $query = "INSERT INTO listings (title, description, price, type, category, user_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdssi", $title, $description, $price, $type, $category, $user_id);
        if ($stmt->execute()) {
            $success = 'Listing created successfully';
        } else {
            $error = 'Failed to create listing';
        }
    }
    
    if (isset($_POST['update_listing'])) {
        $listing_id = (int)$_POST['listing_id'];
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $type = sanitize($_POST['type']);
        $category = sanitize($_POST['category']);
        
        $query = "UPDATE listings SET title = ?, description = ?, price = ?, type = ?, category = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdssi", $title, $description, $price, $type, $category, $listing_id);
        if ($stmt->execute()) {
            $success = 'Listing updated successfully';
        } else {
            $error = 'Failed to update listing';
        }
    }
}

// Get listings with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : '';

$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $search_like = "%$search%";
    $params[] = &$search_like;
    $params[] = &$search_like;
    $param_types .= 'ss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "l.status = ?";
    $params[] = &$status_filter;
    $param_types .= 's';
}

if (!empty($type_filter)) {
    $where_conditions[] = "l.type = ?";
    $params[] = &$type_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM listings l JOIN users u ON l.user_id = u.id $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_listings = $result->fetch_assoc()['total'];
$total_pages = ceil($total_listings / $per_page);

// Get listings
$query = "SELECT l.*, u.full_name as user_name, u.email as user_email
          FROM listings l 
          JOIN users u ON l.user_id = u.id
          $where_clause 
          ORDER BY l.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

// Add limit and offset
$params_with_limit = $params;
$param_types_with_limit = $param_types . 'ii';
$params_with_limit[] = &$per_page;
$params_with_limit[] = &$offset;

if (!empty($params_with_limit)) {
    $stmt->bind_param($param_types_with_limit, ...$params_with_limit);
}
$stmt->execute();
$result = $stmt->get_result();
$listings = $result->fetch_all(MYSQLI_ASSOC);

$users_query = "SELECT id, full_name, email FROM users WHERE status = 'active' ORDER BY full_name";
$users_result = $conn->query($users_query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

$page_title = 'Listing Management';
include 'includes/header.php';
?>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 16px;
    padding: 32px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    max-height: 90vh;
    overflow-y: auto;
}
</style>

<div class="p-6 min-h-screen">
    <!-- Enhanced header with modern design -->
    <div class="mb-8">
        <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Listing Management</h1>
                    <p class="text-gray-600 text-lg">Manage all products and services on the platform</p>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <button onclick="openCreateModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 hover:shadow-md">
                        <i class="fas fa-plus mr-2"></i>
                        Create Listing
                    </button>
                    <button class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 hover:shadow-md">
                        <i class="fas fa-download mr-2"></i>
                        Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl mb-6">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <span class="font-medium"><?php echo $success; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-xl mb-6">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <span class="font-medium"><?php echo $error; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Enhanced stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <?php
        $active_listings = $conn->query("SELECT COUNT(*) as count FROM listings WHERE status = 'active'")->fetch_assoc()['count'];
        $inactive_listings = $conn->query("SELECT COUNT(*) as count FROM listings WHERE status = 'inactive'")->fetch_assoc()['count'];
        $product_listings = $conn->query("SELECT COUNT(*) as count FROM listings WHERE type = 'product' AND status != 'deleted'")->fetch_assoc()['count'];
        $service_listings = $conn->query("SELECT COUNT(*) as count FROM listings WHERE type = 'service' AND status != 'deleted'")->fetch_assoc()['count'];
        ?>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-100 transition-colors duration-200">
                        <i class="fas fa-list text-green-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Listings</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $total_listings; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">All listings</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-100 transition-colors duration-200">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Active</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $active_listings; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Live listings</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gray-50 rounded-xl flex items-center justify-center group-hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-box text-gray-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Products</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $product_listings; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-gray-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Product type</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gray-50 rounded-xl flex items-center justify-center group-hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-tools text-gray-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Services</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $service_listings; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-gray-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Service type</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced filters section -->
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8 shadow-sm">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input
                        name="search"
                        type="text"
                        placeholder="Search listings by title or description..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                    />
                </div>
            </div>
            <div>
                <select name="type" class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                    <option value="">All Types</option>
                    <option value="product" <?php echo $type_filter === 'product' ? 'selected' : ''; ?>>Products</option>
                    <option value="service" <?php echo $type_filter === 'service' ? 'selected' : ''; ?>>Services</option>
                </select>
            </div>
            <div>
                <select name="status" class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="deleted" <?php echo $status_filter === 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                </select>
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 hover:shadow-md">
                <i class="fas fa-search mr-2"></i>
                Search
            </button>
        </form>
    </div>

    <!-- Enhanced listings table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Listing</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($listings as $listing): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <?php 
                                        $first_image = '';
                                        if ($listing['image_url']) {
                                            $images = explode(',', $listing['image_url']);
                                            $first_image = trim($images[0]);
                                            if (strpos($first_image, '1pic') !== false || strpos($first_image, 'pic1') !== false) {
                                                $first_image = $listing['image_url'];
                                            }
                                        }
                                        ?>
                                        <?php if ($first_image): ?>
                                            <img class="h-12 w-12 rounded-xl object-cover" src="../<?php echo htmlspecialchars($first_image); ?>" alt="">
                                        <?php else: ?>
                                            <div class="h-12 w-12 rounded-xl bg-gray-100 flex items-center justify-center">
                                                <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> text-green-600"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($listing['title']); ?></div>
                                        <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars(substr($listing['description'], 0, 50)) . '...'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($listing['user_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($listing['user_email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo $listing['type'] === 'product' ? 'bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-700'; ?>">
                                    <?php echo ucfirst($listing['type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo ucwords(str_replace('-', ' ', $listing['category'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php 
                                    switch($listing['status']) {
                                        case 'active': echo 'bg-green-100 text-green-700'; break;
                                        case 'inactive': echo 'bg-gray-100 text-gray-700'; break;
                                        case 'deleted': echo 'bg-gray-100 text-gray-700'; break;
                                        default: echo 'bg-gray-100 text-gray-700'; break;
                                    }
                                ?>">
                                    <?php echo ucfirst($listing['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo formatTimeAgo($listing['created_at']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-3">
                                    <a href="listing-details.php?id=<?php echo $listing['id']; ?>" class="text-green-600 hover:text-green-700 p-2 rounded-lg hover:bg-green-50 transition-all duration-200" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($listing)); ?>)" class="text-gray-600 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-all duration-200" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($listing['status'] !== 'active'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="text-green-600 hover:text-green-700 p-2 rounded-lg hover:bg-green-50 transition-all duration-200" title="Activate">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($listing['status'] === 'active'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="text-gray-600 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-all duration-200" title="Deactivate">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                        <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="text-gray-600 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-all duration-200" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Enhanced pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Next</a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 font-medium">
                            Showing <span class="font-semibold"><?php echo $offset + 1; ?></span> to <span class="font-semibold"><?php echo min($offset + $per_page, $total_listings); ?></span> of <span class="font-semibold"><?php echo $total_listings; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>" 
                                   class="relative inline-flex items-center px-3 py-2 rounded-l-xl border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-green-50 border-green-500 text-green-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>" 
                                   class="relative inline-flex items-center px-3 py-2 rounded-r-xl border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Next</a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced modals -->
<!-- Create Listing Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">Create New Listing</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
                <input type="text" name="title" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Price</label>
                    <input type="number" name="price" step="0.01" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                    <select name="type" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        <option value="">Select Type</option>
                        <option value="product">Product</option>
                        <option value="service">Service</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                <input type="text" name="category" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">User</label>
                <select name="user_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                    <option value="">Select User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']) . ' (' . htmlspecialchars($user['email']) . ')'; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end space-x-4 pt-4">
                <button type="button" onclick="closeCreateModal()" class="px-6 py-3 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all duration-200">Cancel</button>
                <button type="submit" name="create_listing" class="px-6 py-3 bg-green-600 border border-transparent rounded-xl text-sm font-semibold text-white hover:bg-green-700 transition-all duration-200">Create Listing</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Listing Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">Edit Listing</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="listing_id" id="edit_listing_id">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Title</label>
                <input type="text" name="title" id="edit_title" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="description" id="edit_description" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Price</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                    <select name="type" id="edit_type" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        <option value="">Select Type</option>
                        <option value="product">Product</option>
                        <option value="service">Service</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                <input type="text" name="category" id="edit_category" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div class="flex justify-end space-x-4 pt-4">
                <button type="button" onclick="closeEditModal()" class="px-6 py-3 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all duration-200">Cancel</button>
                <button type="submit" name="update_listing" class="px-6 py-3 bg-green-600 border border-transparent rounded-xl text-sm font-semibold text-white hover:bg-green-700 transition-all duration-200">Update Listing</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.add('show');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('show');
}

function openEditModal(listing) {
    document.getElementById('edit_listing_id').value = listing.id;
    document.getElementById('edit_title').value = listing.title;
    document.getElementById('edit_description').value = listing.description;
    document.getElementById('edit_price').value = listing.price;
    document.getElementById('edit_type').value = listing.type;
    document.getElementById('edit_category').value = listing.category;
    document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
