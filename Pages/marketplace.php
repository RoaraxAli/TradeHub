<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();

// Get search parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : 'all';
$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';

// Build query
$where_conditions = ["l.status = 'active'", "l.user_id != ?"];
$params = [$_SESSION['user_id']];

if (!empty($search)) {
    $where_conditions[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category !== 'all') {
    $where_conditions[] = "l.category = ?";
    $params[] = $category;
}

if ($type !== 'all') {
    $where_conditions[] = "l.type = ?";
    $params[] = $type;
}

$where_clause = implode(' AND ', $where_conditions);

$query = "SELECT l.*, u.full_name as user_name, u.location as user_location,
          (SELECT AVG(rating) FROM reviews WHERE reviewed_user_id = l.user_id) as user_rating,
          (SELECT COUNT(*) FROM trades WHERE (requester_id = l.user_id OR owner_id = l.user_id) AND status = 'completed') as trade_count
          FROM listings l 
          JOIN users u ON l.user_id = u.id 
          WHERE $where_clause 
          ORDER BY l.created_at DESC";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$listings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch categories
$categories = [];
$sql = "SELECT slug, name FROM categories ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[$row['slug']] = $row['name'];
    }
}

$page_title = 'Marketplace';
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - TradeHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
<div class="md:p-9">
        <div class="max-w-full mx-auto h-[100vh] md:h-[calc(95vh-3rem)]">
            <div class="flex h-full bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl border border-white/50 overflow-hidden animate-scale-in">
                <?php include '../includes/sidebar.php'; ?>
    <div class="flex-1 h-full overflow-y-auto">
    <div class="flex-1 flex flex-col">
    <?php include '../includes/head.php'; ?>
    <div class="p-8 sm:px-6 py-6 sm:py-8">
        <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">Marketplace</h1>
            <p class="text-slate-600 text-sm sm:text-base">Discover products and services from your community</p>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-3xl shadow-lg p-4 sm:p-6 mb-6 sm:mb-8">
            <form method="GET" class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm sm:text-base"></i>
                    <input
                        name="search"
                        type="text"
                        placeholder="Search for products or services..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full pl-10 pr-4 py-2 sm:py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20 text-sm sm:text-base"
                    />
                </div>
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-2">
                    <select name="type" class="px-4 py-2 sm:py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 text-sm sm:text-base">
                        <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="product" <?php echo $type === 'product' ? 'selected' : ''; ?>>Products</option>
                        <option value="service" <?php echo $type === 'service' ? 'selected' : ''; ?>>Services</option>
                    </select>
                    <select name="category" class="px-4 py-2 sm:py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 text-sm sm:text-base">
                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $slug => $name): ?>
                            <option value="<?php echo $slug; ?>" <?php echo $category === $slug ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-4 sm:px-6 py-2 sm:py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl text-sm sm:text-base">
                        <i class="fas fa-search mr-1 sm:mr-2"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Count -->
        <div class="mb-4 sm:mb-6">
            <p class="text-slate-600 text-sm sm:text-base">
                Showing <?php echo count($listings); ?> <?php echo count($listings) === 1 ? 'result' : 'results'; ?>
            </p>
        </div>

        <!-- Listings Grid -->
        <?php if (empty($listings)): ?>
            <div class="text-center py-8 sm:py-12">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-slate-400 text-xl sm:text-2xl"></i>
                </div>
                <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-2">No results found</h3>
                <p class="text-slate-600 text-sm sm:text-base mb-4">Try adjusting your search or filters</p>
                <a href="marketplace.php" class="inline-block border border-slate-200 hover:bg-slate-50 px-4 sm:px-6 py-2 sm:py-3 rounded-2xl text-sm sm:text-base">
                    Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <?php foreach ($listings as $listing): ?>
                    <div class="bg-white rounded-3xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden flex flex-col h-full">
    
                    <!-- Image section -->
                    <div class="relative">
                        <?php 
                        $imageUrls = !empty($listing['image_url']) ? explode(',', $listing['image_url']) : [];
                        $firstImage = !empty($imageUrls) ? trim($imageUrls[0]) : null;
                        ?>
                        <?php if (!empty($firstImage)): ?>
                            <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>" class="w-full h-40 sm:h-48 object-cover">
                        <?php else: ?>
                            <div class="w-full h-40 sm:h-48 bg-slate-200 flex items-center justify-center">
                                <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> text-slate-400 text-3xl sm:text-4xl"></i>
                            </div>
                        <?php endif; ?>
                        <div class="absolute top-2 sm:top-3 left-2 sm:left-3">
                            <span class="px-2 sm:px-3 py-1 rounded-xl text-xs font-medium <?php echo $listing['type'] === 'product' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>">
                                <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> mr-1"></i>
                                <?php echo ucfirst($listing['type']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Content section -->
                    <div class="p-4 sm:p-6 flex flex-col flex-grow"> <!-- flex-grow to stretch -->

                        <div class="mb-3 sm:mb-4">
                            <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-1 sm:mb-2 truncate">
                                <?php echo htmlspecialchars($listing['title']); ?>
                            </h3>
                            <p class="text-slate-600 text-xs sm:text-sm line-clamp-2">
                                <?php echo htmlspecialchars($listing['description']); ?>
                            </p>
                        </div>

                        <div class="flex items-center space-x-2 sm:space-x-3 mb-3 sm:mb-4">
                            <div class="w-6 h-6 sm:w-8 sm:h-8 bg-slate-200 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-slate-600 text-xs sm:text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 text-xs sm:text-sm truncate">
                                    <?php echo htmlspecialchars($listing['user_name']); ?>
                                </p>
                                <div class="flex items-center space-x-1">
                                    <i class="fas fa-map-marker-alt text-slate-400 text-xs"></i>
                                    <span class="text-xs text-slate-500 truncate">
                                        <?php echo htmlspecialchars($listing['user_location'] ?? 'Location not set'); ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($listing['user_rating']): ?>
                                <div class="flex items-center space-x-1 flex-shrink-0">
                                    <i class="fas fa-star text-amber-400 text-xs sm:text-sm"></i>
                                    <span class="text-xs sm:text-sm font-medium text-slate-700">
                                        <?php echo number_format($listing['user_rating'], 1); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3 sm:mb-4">
                            <p class="text-xs text-slate-500 mb-1 sm:mb-2">Looking for:</p>
                            <div class="flex flex-wrap gap-1 sm:gap-2">
                                <?php 
                                $looking_for = json_decode($listing['looking_for'], true) ?? [];
                                foreach (array_slice($looking_for, 0, 3) as $item): 
                                ?>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-xl text-xs">
                                        <?php echo htmlspecialchars($item); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- stick-to-bottom -->
                        <div class="mt-auto">
                            <div class="flex space-x-2">
                                <a href="propose-trade.php?listing_id=<?php echo $listing['id']; ?>" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-2 rounded-2xl text-center text-xs sm:text-sm font-medium transition-colors">
                                    Propose 
                                </a>
                                <a href="listing.php?id=<?php echo $listing['id']; ?>" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-2xl text-center text-xs sm:text-sm font-medium transition-colors">
                                    View 
                                </a>
                                <a href="profile.php?id=<?php echo $listing['user_id']; ?>" class="border border-slate-200 hover:bg-slate-50 p-2 rounded-2xl text-center flex items-center justify-center transition-colors">
                                    <i class="fas fa-user text-slate-600 text-xs sm:text-sm"></i>
                                </a>
                            </div>

                            <div class="mt-2 sm:mt-3 text-xs text-slate-500 text-center">
                                Posted <?php echo formatTimeAgo($listing['created_at']); ?> • <?php echo $listing['trade_count']; ?> trades
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php 
include '../includes/footer.php';
$conn->close();
?>

</body>
</html>