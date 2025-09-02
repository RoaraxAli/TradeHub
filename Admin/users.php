<?php
require_once '../config/config.php';
require_once 'includes/admin_auth.php';

$conn = getDBConnection();

$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'create':
                $full_name = sanitize($_POST['full_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (full_name, email, phone, password, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $full_name, $email, $phone, $password);
                if ($stmt->execute()) {
                    $success = 'User created successfully';
                } else {
                    $error = 'Failed to create user';
                }
                break;
                
            case 'update':
                $user_id = (int)$_POST['user_id'];
                $full_name = sanitize($_POST['full_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                
                $query = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
                if ($stmt->execute()) {
                    $success = 'User updated successfully';
                } else {
                    $error = 'Failed to update user';
                }
                break;
                
            case 'activate':
                $user_id = (int)$_POST['user_id'];
                $query = "UPDATE users SET status = 'active' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $success = 'User activated successfully';
                }
                break;
                
            case 'suspend':
                $user_id = (int)$_POST['user_id'];
                $query = "UPDATE users SET status = 'suspended' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $success = 'User suspended successfully';
                }
                break;
                
            case 'delete':
                $user_id = (int)$_POST['user_id'];
                $query = "UPDATE users SET status = 'inactive' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $success = 'User deleted successfully';
                }
                break;
        }
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ?)";
    $search_like = "%$search%";
    $params[] = &$search_like;
    $params[] = &$search_like;
    $param_types .= 'ss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = &$status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_users = $result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

// Get users
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM listings WHERE user_id = u.id) as listings_count,
          (SELECT COUNT(*) FROM trades WHERE requester_id = u.id OR owner_id = u.id) as trades_count
          FROM users u 
          $where_clause 
          ORDER BY u.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

$params_with_limit = $params;
$param_types_with_limit = $param_types . 'ii';
$params_with_limit[] = &$per_page;
$params_with_limit[] = &$offset;

if (!empty($params_with_limit)) {
    $stmt->bind_param($param_types_with_limit, ...$params_with_limit);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

$page_title = 'User Management';
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
    max-width: 500px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>

<div class="p-6 min-h-screen">
    <!-- Enhanced header with modern design -->
    <div class="mb-8">
        <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">User Management</h1>
                    <p class="text-gray-600 text-lg">Manage all registered users and their platform activities</p>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <button onclick="openCreateModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 hover:shadow-md">
                        <i class="fas fa-plus mr-2"></i>
                        Add User
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

    <!-- Enhanced stats cards with green color scheme -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <?php
        $active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
        $suspended_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'suspended'")->fetch_assoc()['count'];
        $inactive_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'inactive'")->fetch_assoc()['count'];
        ?>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-100 transition-colors duration-200">
                        <i class="fas fa-users text-green-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $total_users; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">All users</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-100 transition-colors duration-200">
                        <i class="fas fa-user-check text-green-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Active Users</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $active_users; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Active status</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gray-50 rounded-xl flex items-center justify-center group-hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-user-clock text-gray-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Suspended</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $suspended_users; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-gray-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Suspended</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gray-50 rounded-xl flex items-center justify-center group-hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-user-slash text-gray-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Inactive</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $inactive_users; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-gray-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Inactive</span>
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
                        placeholder="Search users by name or email..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                    />
                </div>
            </div>
            <div>
                <select
                    name="status"
                    class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                >
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 hover:shadow-md">
                <i class="fas fa-search mr-2"></i>
                Search
            </button>
        </form>
    </div>

    <!-- Enhanced users table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Activity</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-user text-green-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        <div class="text-sm text-gray-500">ID: <?php echo $user['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                <?php if ($user['phone']): ?>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="font-medium text-gray-900"><?php echo $user['listings_count']; ?> listings</div>
                                <div class="text-gray-500"><?php echo $user['trades_count']; ?> trades</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php 
                                    switch($user['status']) {
                                        case 'active': echo 'bg-green-100 text-green-700'; break;
                                        case 'inactive': echo 'bg-gray-100 text-gray-700'; break;
                                        case 'suspended': echo 'bg-gray-100 text-gray-700'; break;
                                        default: echo 'bg-gray-100 text-gray-700'; break;
                                    }
                                ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo formatTimeAgo($user['created_at']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-3">
                                    <a href="user-details.php?id=<?php echo $user['id']; ?>" class="text-green-600 hover:text-green-700 p-2 rounded-lg hover:bg-green-50 transition-all duration-200" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="text-gray-600 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-all duration-200" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['status'] !== 'active'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="text-green-600 hover:text-green-700 p-2 rounded-lg hover:bg-green-50 transition-all duration-200" title="Activate">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="suspend">
                                            <button type="submit" class="text-gray-600 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-all duration-200" title="Suspend">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Next</a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 font-medium">
                            Showing <span class="font-semibold"><?php echo $offset + 1; ?></span> to <span class="font-semibold"><?php echo min($offset + $per_page, $total_users); ?></span> of <span class="font-semibold"><?php echo $total_users; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="relative inline-flex items-center px-3 py-2 rounded-l-xl border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-green-50 border-green-500 text-green-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="relative inline-flex items-center px-3 py-2 rounded-r-xl border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Next</a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced modals -->
<!-- Create User Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">Create New User</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                <input type="text" name="full_name" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                <input type="text" name="phone" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div class="flex justify-end space-x-4 pt-4">
                <button type="button" onclick="closeCreateModal()" class="px-6 py-3 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all duration-200">Cancel</button>
                <button type="submit" class="px-6 py-3 bg-green-600 border border-transparent rounded-xl text-sm font-semibold text-white hover:bg-green-700 transition-all duration-200">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">Edit User</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                <input type="text" name="full_name" id="edit_full_name" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input type="email" name="email" id="edit_email" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                <input type="text" name="phone" id="edit_phone" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
            </div>
            <div class="flex justify-end space-x-4 pt-4">
                <button type="button" onclick="closeEditModal()" class="px-6 py-3 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all duration-200">Cancel</button>
                <button type="submit" class="px-6 py-3 bg-green-600 border border-transparent rounded-xl text-sm font-semibold text-white hover:bg-green-700 transition-all duration-200">Update User</button>
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

function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_phone').value = user.phone || '';
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
