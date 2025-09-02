<?php
require_once '../config/config.php';
require_once 'includes/admin_auth.php';

$conn = getDBConnection();

$success = '';
$error = '';

// Handle trade actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'create':
                $requester_id = (int)$_POST['requester_id'];
                $owner_id = (int)$_POST['owner_id'];
                $offered_item_id = (int)$_POST['offered_item_id'];
                $requested_item_id = (int)$_POST['requested_item_id'];
                $message = sanitize($_POST['message']);
                
                $query = "INSERT INTO trades (requester_id, owner_id, offered_item_id, requested_item_id, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiiis", $requester_id, $owner_id, $offered_item_id, $requested_item_id, $message);
                if ($stmt->execute()) {
                    $success = 'Trade created successfully';
                } else {
                    $error = 'Failed to create trade';
                }
                break;
                
            case 'update':
                $trade_id = (int)$_POST['trade_id'];
                $status = sanitize($_POST['status']);
                $message = sanitize($_POST['message']);
                
                $query = "UPDATE trades SET status = ?, message = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssi", $status, $message, $trade_id);
                if ($stmt->execute()) {
                    $success = 'Trade updated successfully';
                } else {
                    $error = 'Failed to update trade';
                }
                break;
                
            case 'delete':
                $trade_id = (int)$_POST['trade_id'];
                $query = "DELETE FROM trades WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $trade_id);
                if ($stmt->execute()) {
                    $success = 'Trade deleted successfully';
                } else {
                    $error = 'Failed to delete trade';
                }
                break;
                
            case 'complete':
                $trade_id = (int)$_POST['trade_id'];
                $query = "UPDATE trades SET status = 'completed', completed_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $trade_id);
                if ($stmt->execute()) {
                    $success = 'Trade marked as completed';
                }
                break;
                
            case 'cancel':
                $trade_id = (int)$_POST['trade_id'];
                $query = "UPDATE trades SET status = 'cancelled' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $trade_id);
                if ($stmt->execute()) {
                    $success = 'Trade cancelled successfully';
                }
                break;
        }
    }
}

$users_query = "SELECT id, full_name, email FROM users WHERE status = 'active' ORDER BY full_name";
$users_result = $conn->query($users_query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

$listings_query = "SELECT id, title, type FROM listings WHERE status = 'active' ORDER BY title";
$listings_result = $conn->query($listings_query);
$listings = $listings_result->fetch_all(MYSQLI_ASSOC);

// Get trades with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(u1.full_name LIKE ? OR u2.full_name LIKE ?)";
    $search_like = "%$search%";
    $params[] = &$search_like;
    $params[] = &$search_like;
    $param_types .= 'ss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "t.status = ?";
    $params[] = &$status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM trades t 
                JOIN users u1 ON t.requester_id = u1.id 
                JOIN users u2 ON t.owner_id = u2.id 
                $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_trades = $result->fetch_assoc()['total'];
$total_pages = ceil($total_trades / $per_page);

// Get trades
$query = "SELECT t.*, 
          u1.full_name as requester_name, u1.email as requester_email,
          u2.full_name as owner_name, u2.email as owner_email,
          l1.title as offered_item_title, l1.type as offered_item_type,
          l2.title as requested_item_title, l2.type as requested_item_type
          FROM trades t
          JOIN users u1 ON t.requester_id = u1.id
          JOIN users u2 ON t.owner_id = u2.id
          JOIN listings l1 ON t.offered_item_id = l1.id
          JOIN listings l2 ON t.requested_item_id = l2.id
          $where_clause 
          ORDER BY t.created_at DESC 
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
$trades = $result->fetch_all(MYSQLI_ASSOC);

$page_title = 'Trade Management';
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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Trade Management</h1>
                    <p class="text-gray-600 text-lg">Monitor and manage all trading activities on the platform</p>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <button onclick="openCreateModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 hover:shadow-md">
                        <i class="fas fa-plus mr-2"></i>
                        Create Trade
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
        $pending_trades = $conn->query("SELECT COUNT(*) as count FROM trades WHERE status = 'pending'")->fetch_assoc()['count'];
        $completed_trades = $conn->query("SELECT COUNT(*) as count FROM trades WHERE status = 'completed'")->fetch_assoc()['count'];
        $active_trades = $conn->query("SELECT COUNT(*) as count FROM trades WHERE status IN ('pending', 'accepted')")->fetch_assoc()['count'];
        $cancelled_trades = $conn->query("SELECT COUNT(*) as count FROM trades WHERE status IN ('declined', 'cancelled')")->fetch_assoc()['count'];
        ?>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-100 transition-colors duration-200">
                        <i class="fas fa-handshake text-green-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Trades</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $total_trades; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">All trades</span>
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
                        <p class="text-sm font-medium text-gray-500 mb-1">Completed</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $completed_trades; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Successful</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gray-50 rounded-xl flex items-center justify-center group-hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-clock text-gray-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Active</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $active_trades; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-gray-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">In progress</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gray-50 rounded-xl flex items-center justify-center group-hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-times-circle text-gray-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Cancelled</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $cancelled_trades; ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-gray-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Unsuccessful</span>
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
                        placeholder="Search trades by user names..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                    />
                </div>
            </div>
            <div>
                <select name="status" class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="declined" <?php echo $status_filter === 'declined' ? 'selected' : ''; ?>>Declined</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 hover:shadow-md">
                <i class="fas fa-search mr-2"></i>
                Search
            </button>
        </form>
    </div>

    <!-- Enhanced trades table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Trade ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Participants</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($trades as $trade): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center mr-3">
                                        <i class="fas fa-handshake text-green-600"></i>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">#<?php echo $trade['id']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mr-2">
                                            <i class="fas fa-user text-gray-600 text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($trade['requester_name']); ?></p>
                                            <p class="text-xs text-gray-500">Requester</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center mr-2">
                                            <i class="fas fa-user text-gray-600 text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($trade['owner_name']); ?></p>
                                            <p class="text-xs text-gray-500">Owner</p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <i class="fas fa-<?php echo $trade['offered_item_type'] === 'product' ? 'box' : 'tools'; ?> mr-2 text-green-600"></i>
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($trade['offered_item_title']); ?></span>
                                    </div>
                                    <div class="flex items-center justify-center">
                                        <i class="fas fa-exchange-alt text-gray-400"></i>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-<?php echo $trade['requested_item_type'] === 'product' ? 'box' : 'tools'; ?> mr-2 text-green-600"></i>
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($trade['requested_item_title']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php 
                                    switch($trade['status']) {
                                        case 'pending': echo 'bg-gray-100 text-gray-700'; break;
                                        case 'accepted': echo 'bg-gray-100 text-gray-700'; break;
                                        case 'completed': echo 'bg-green-100 text-green-700'; break;
                                        case 'declined': echo 'bg-gray-100 text-gray-700'; break;
                                        case 'cancelled': echo 'bg-gray-100 text-gray-700'; break;
                                        default: echo 'bg-gray-100 text-gray-700'; break;
                                    }
                                ?>">
                                    <?php echo ucfirst($trade['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo formatTimeAgo($trade['created_at']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-3">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($trade)); ?>)" class="text-gray-600 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-all duration-200" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="trade-details.php?id=<?php echo $trade['id']; ?>" class="text-green-600 hover:text-green-700 p-2 rounded-lg hover:bg-green-50 transition-all duration-200" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($trade['status'] === 'accepted'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="trade_id" value="<?php echo $trade['id']; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="text-green-600 hover:text-green-700 p-2 rounded-lg hover:bg-green-50 transition-all duration-200" title="Mark Complete">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <button onclick="deleteTrade(<?php echo $trade['id']; ?>)" class="text-gray-600 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-all duration-200" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">Next</a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 font-medium">
                            Showing <span class="font-semibold"><?php echo $offset + 1; ?></span> to <span class="font-semibold"><?php echo min($offset + $per_page, $total_trades); ?></span> of <span class="font-semibold"><?php echo $total_trades; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                   class="relative inline-flex items-center px-3 py-2 rounded-l-xl border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-green-50 border-green-500 text-green-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
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
<!-- Create Trade Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">Create New Trade</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Requester</label>
                    <select name="requester_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        <option value="">Select Requester</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Owner</label>
                    <select name="owner_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        <option value="">Select Owner</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Offered Item</label>
                    <select name="offered_item_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        <option value="">Select Offered Item</option>
                        <?php foreach ($listings as $listing): ?>
                            <option value="<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Requested Item</label>
                    <select name="requested_item_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        <option value="">Select Requested Item</option>
                        <?php foreach ($listings as $listing): ?>
                            <option value="<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Message</label>
                <textarea name="message" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200" placeholder="Optional message..."></textarea>
            </div>
            <div class="flex justify-end space-x-4 pt-4">
                <button type="button" onclick="closeCreateModal()" class="px-6 py-3 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all duration-200">Cancel</button>
                <button type="submit" class="px-6 py-3 bg-green-600 border border-transparent rounded-xl text-sm font-semibold text-white hover:bg-green-700 transition-all duration-200">Create Trade</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Trade Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">Edit Trade</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="trade_id" id="edit_trade_id">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status" id="edit_status" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="completed">Completed</option>
                    <option value="declined">Declined</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Message</label>
                <textarea name="message" id="edit_message" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"></textarea>
            </div>
            <div class="flex justify-end space-x-4 pt-4">
                <button type="button" onclick="closeEditModal()" class="px-6 py-3 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all duration-200">Cancel</button>
                <button type="submit" class="px-6 py-3 bg-green-600 border border-transparent rounded-xl text-sm font-semibold text-white hover:bg-green-700 transition-all duration-200">Update Trade</button>
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

function openEditModal(trade) {
    document.getElementById('edit_trade_id').value = trade.id;
    document.getElementById('edit_status').value = trade.status;
    document.getElementById('edit_message').value = trade.message || '';
    document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

function deleteTrade(tradeId) {
    if (confirm('Are you sure you want to delete this trade? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="trade_id" value="${tradeId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
