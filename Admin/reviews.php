<?php
require_once '../config/config.php';
require_once 'includes/admin_auth.php';

$conn = getDBConnection();

$success = '';
$error = '';

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['review_id'])) {
        $review_id = (int)$_POST['review_id'];
        $action = $_POST['action'];
        
        switch ($action) {
            case 'delete':
                $query = "DELETE FROM reviews WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $review_id);
                if ($stmt->execute()) {
                    $success = 'Review deleted successfully';
                }
                break;
            case 'edit':
                $comment = sanitize($_POST['comment']);
                $rating = (int)$_POST['rating'];
                $query = "UPDATE reviews SET comment = ?, rating = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sii", $comment, $rating, $review_id);
                if ($stmt->execute()) {
                    $success = 'Review updated successfully';
                }
                break;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'create') {
        $reviewer_id = (int)$_POST['reviewer_id'];
        $reviewed_user_id = (int)$_POST['reviewed_user_id'];
        $trade_id = (int)$_POST['trade_id'];
        $rating = (int)$_POST['rating'];
        $comment = sanitize($_POST['comment']);
        
        $query = "INSERT INTO reviews (reviewer_id, reviewed_user_id, trade_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiis", $reviewer_id, $reviewed_user_id, $trade_id, $rating, $comment);
        if ($stmt->execute()) {
            $success = 'Review created successfully';
        }
    }
}

// Get reviews with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : '';

$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(r.comment LIKE ? OR reviewer.full_name LIKE ? OR reviewed.full_name LIKE ?)";
    $search_like = "%$search%";
    $params[] = &$search_like;
    $params[] = &$search_like;
    $params[] = &$search_like;
    $param_types .= 'sss';
}

if ($rating_filter > 0) {
    $where_conditions[] = "r.rating = ?";
    $params[] = &$rating_filter;
    $param_types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total 
                FROM reviews r
                LEFT JOIN users reviewer ON r.reviewer_id = reviewer.id
                LEFT JOIN users reviewed ON r.reviewed_user_id = reviewed.id
                $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_reviews = $result->fetch_assoc()['total'];
$total_pages = ceil($total_reviews / $per_page);

// Get reviews
$query = "SELECT r.*, 
          reviewer.full_name as reviewer_name,
          reviewed.full_name as reviewed_name
          FROM reviews r
          LEFT JOIN users reviewer ON r.reviewer_id = reviewer.id
          LEFT JOIN users reviewed ON r.reviewed_user_id = reviewed.id
          $where_clause 
          ORDER BY r.created_at DESC 
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
$reviews = $result->fetch_all(MYSQLI_ASSOC);

$page_title = 'Review Management';
include 'includes/header.php';
?>

<div class="flex-1 p-4 md:p-8 bg-gray-50 min-h-screen">
    <!-- Added statistics cards section for consistency with other pages -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
        <div class="bg-white rounded-lg shadow border p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Reviews</p>
                    <p class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo number_format($total_reviews); ?></p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-star text-green-600 text-lg md:text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow border p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Average Rating</p>
                    <p class="text-2xl md:text-3xl font-bold text-gray-900">
                        <?php 
                        $avg_query = "SELECT AVG(rating) as avg_rating FROM reviews";
                        $avg_result = $conn->query($avg_query);
                        $avg_rating = $avg_result->fetch_assoc()['avg_rating'];
                        echo number_format($avg_rating, 1);
                        ?>
                    </p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-green-600 text-lg md:text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow border p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">5-Star Reviews</p>
                    <p class="text-2xl md:text-3xl font-bold text-gray-900">
                        <?php 
                        $five_star_query = "SELECT COUNT(*) as count FROM reviews WHERE rating = 5";
                        $five_star_result = $conn->query($five_star_query);
                        echo number_format($five_star_result->fetch_assoc()['count']);
                        ?>
                    </p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-thumbs-up text-green-600 text-lg md:text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow border p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">This Month</p>
                    <p class="text-2xl md:text-3xl font-bold text-gray-900">
                        <?php 
                        $month_query = "SELECT COUNT(*) as count FROM reviews WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
                        $month_result = $conn->query($month_query);
                        echo number_format($month_result->fetch_assoc()['count']);
                        ?>
                    </p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar text-green-600 text-lg md:text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Updated header with green color scheme and improved responsive design -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 md:mb-8 space-y-4 sm:space-y-0">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Review Management</h1>
            <p class="text-slate-600">Manage all user reviews and ratings</p>
        </div>
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
            <button onclick="openCreateModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg shadow hover:shadow-md transition-all text-sm md:text-base">
                <i class="fas fa-plus mr-2"></i>
                Add Review
            </button>
            <button class="bg-gray-600 hover:bg-gray-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg shadow hover:shadow-md transition-all text-sm md:text-base">
                <i class="fas fa-download mr-2"></i>
                Export Reviews
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Updated filters section with green color scheme -->
    <div class="bg-white rounded-lg shadow border p-4 md:p-6 mb-6 md:mb-8">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input
                    name="search"
                    type="text"
                    placeholder="Search reviews by comment or user names..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="w-full px-3 md:px-4 py-2 md:py-3 rounded-lg border border-slate-200 focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base"
                />
            </div>
            <div class="w-full md:w-auto">
                <select
                    name="rating"
                    class="w-full md:w-auto px-3 md:px-4 py-2 md:py-3 rounded-lg border border-slate-200 focus:border-green-500 text-sm md:text-base"
                >
                    <option value="">All Ratings</option>
                    <option value="1" <?php echo $rating_filter === 1 ? 'selected' : ''; ?>>1 Star</option>
                    <option value="2" <?php echo $rating_filter === 2 ? 'selected' : ''; ?>>2 Stars</option>
                    <option value="3" <?php echo $rating_filter === 3 ? 'selected' : ''; ?>>3 Stars</option>
                    <option value="4" <?php echo $rating_filter === 4 ? 'selected' : ''; ?>>4 Stars</option>
                    <option value="5" <?php echo $rating_filter === 5 ? 'selected' : ''; ?>>5 Stars</option>
                </select>
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg shadow hover:shadow-md transition-all text-sm md:text-base">
                <i class="fas fa-search mr-2"></i>
                Search
            </button>
        </form>
    </div>

    <!-- Enhanced reviews table with better styling and green accents -->
    <div class="bg-white rounded-lg shadow border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-gray-900">Reviewer</th>
                        <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-gray-900 hidden sm:table-cell">Reviewed User</th>
                        <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-gray-900 hidden lg:table-cell">Trade ID</th>
                        <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-gray-900">Rating</th>
                        <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-gray-900 hidden md:table-cell">Comment</th>
                        <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-gray-900 hidden lg:table-cell">Date</th>
                        <th class="px-3 md:px-6 py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($reviews as $review): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 md:px-6 py-3 md:py-4">
                                <div class="flex items-center space-x-2 md:space-x-3">
                                    <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-green-600 text-xs md:text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 text-xs md:text-sm"><?php echo htmlspecialchars($review['reviewer_name']); ?></p>
                                        <p class="text-xs text-gray-500">ID: <?php echo $review['reviewer_id']; ?></p>
                                        <p class="text-xs text-gray-500 sm:hidden">→ <?php echo htmlspecialchars($review['reviewed_name']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4 hidden sm:table-cell">
                                <p class="font-medium text-gray-900 text-xs md:text-sm"><?php echo htmlspecialchars($review['reviewed_name']); ?></p>
                                <p class="text-xs text-gray-500">ID: <?php echo $review['reviewed_user_id']; ?></p>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4 text-xs md:text-sm text-gray-600 hidden lg:table-cell">
                                <span class="bg-gray-100 px-2 py-1 rounded text-xs font-medium">#<?php echo $review['trade_id']; ?></span>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4">
                                <div class="flex items-center space-x-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star text-xs <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-200'; ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ml-1 md:ml-2 text-xs font-medium text-gray-600"><?php echo $review['rating']; ?>/5</span>
                                </div>
                                <div class="md:hidden mt-1">
                                    <p class="text-xs text-gray-600 truncate" title="<?php echo htmlspecialchars($review['comment']); ?>">
                                        <?php echo htmlspecialchars(substr($review['comment'], 0, 50)) . (strlen($review['comment']) > 50 ? '...' : ''); ?>
                                    </p>
                                </div>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4 text-xs md:text-sm text-gray-600 max-w-md hidden md:table-cell">
                                <div class="truncate" title="<?php echo htmlspecialchars($review['comment']); ?>">
                                    <?php echo htmlspecialchars($review['comment']); ?>
                                </div>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4 text-xs md:text-sm text-gray-500 hidden lg:table-cell">
                                <?php echo formatTimeAgo($review['created_at']); ?>
                            </td>
                            <td class="px-3 md:px-6 py-3 md:py-4">
                                <div class="flex space-x-2">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($review)); ?>)" 
                                            class="text-green-600 hover:text-green-700 transition-colors text-sm md:text-base" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this review?')">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="text-red-500 hover:text-red-600 transition-colors text-sm md:text-base" title="Delete">
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

        <!-- Updated pagination with green color scheme -->
        <?php if ($total_pages > 1): ?>
            <div class="px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between space-y-3 sm:space-y-0">
                    <p class="text-xs md:text-sm text-gray-600">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_reviews); ?> of <?php echo $total_reviews; ?> reviews
                    </p>
                    <div class="flex flex-wrap justify-center gap-1 md:gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&rating=<?php echo $rating_filter; ?>" 
                               class="px-2 md:px-3 py-1 md:py-2 border border-gray-200 rounded-lg hover:bg-white transition-colors text-xs md:text-sm">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&rating=<?php echo $rating_filter; ?>" 
                               class="px-2 md:px-3 py-1 md:py-2 border rounded-lg transition-colors text-xs md:text-sm <?php echo $i === $page ? 'bg-green-600 text-white border-green-600' : 'border-gray-200 hover:bg-white'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&rating=<?php echo $rating_filter; ?>" 
                               class="px-2 md:px-3 py-1 md:py-2 border border-gray-200 rounded-lg hover:bg-white transition-colors text-xs md:text-sm">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Updated modals with green color scheme and improved styling -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="p-4 md:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900">Add New Review</h3>
                    <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reviewer ID</label>
                            <input type="number" name="reviewer_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reviewed User ID</label>
                            <input type="number" name="reviewed_user_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Trade ID</label>
                            <input type="number" name="trade_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                            <select name="rating" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-green-500 text-sm">
                                <option value="">Select Rating</option>
                                <option value="1">1 Star</option>
                                <option value="2">2 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="5">5 Stars</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Comment</label>
                            <textarea name="comment" rows="3" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm"></textarea>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 mt-6">
                        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition-colors text-sm">
                            Create Review
                        </button>
                        <button type="button" onclick="closeCreateModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg transition-colors text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="p-4 md:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base md:text-lg font-semibold text-gray-900">Edit Review</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="review_id" id="editReviewId">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                            <select name="rating" id="editRating" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-green-500 text-sm">
                                <option value="1">1 Star</option>
                                <option value="2">2 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="5">5 Stars</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Comment</label>
                            <textarea name="comment" id="editComment" rows="3" required class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm"></textarea>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 mt-6">
                        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition-colors text-sm">
                            Update Review
                        </button>
                        <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg transition-colors text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openEditModal(review) {
    document.getElementById('editReviewId').value = review.id;
    document.getElementById('editRating').value = review.rating;
    document.getElementById('editComment').value = review.comment;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
