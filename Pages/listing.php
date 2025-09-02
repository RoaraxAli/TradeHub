<?php
require_once '../config/config.php';

$conn = getDBConnection();

// Get listing ID from URL
$listing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($listing_id <= 0) {
    // Redirect or show error
    header('Location: marketplace.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

// Increment views only once per user per listing
if ($user_id > 0) {
    // Check if already viewed
    $checkQuery = "SELECT 1 FROM listing_views WHERE user_id = ? AND listing_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $user_id, $listing_id);
    $stmt->execute();
    $alreadyViewed = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$alreadyViewed) {
        // Insert new view
        $insertQuery = "INSERT INTO listing_views (user_id, listing_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ii", $user_id, $listing_id);
        $stmt->execute();
        $stmt->close();

        // Increment the listing's views count
        $updateQuery = "UPDATE listings SET views = views + 1 WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $listing_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch listing details
$query = "SELECT l.*, u.full_name, u.location, u.bio, u.avatar_url, u.role, c.name AS category_name
          FROM listings l
          JOIN users u ON l.user_id = u.id
          LEFT JOIN categories c ON l.category = c.slug
          WHERE l.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $listing_id);
$stmt->execute();
$result = $stmt->get_result();
$listing = $result->fetch_assoc();
$stmt->close();

if (!$listing) {
    // Listing not found
    header('Location: marketplace.php');
    exit;
}

// Fetch user rating
$query = "SELECT AVG(rating) AS user_rating FROM reviews WHERE reviewed_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $listing['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$rating_row = $result->fetch_assoc();
$user_rating = $rating_row['user_rating'] ? number_format($rating_row['user_rating'], 1) : null;
$stmt->close();

// Fetch trade count
$query = "SELECT COUNT(*) AS trade_count FROM trades WHERE (requester_id = ? OR owner_id = ?) AND status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $listing['user_id'], $listing['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$trade_row = $result->fetch_assoc();
$trade_count = $trade_row['trade_count'];
$stmt->close();

// Fetch reviews for the owner
$query = "SELECT r.*, reviewer.full_name AS reviewer_name, t.offered_item_id, t.requested_item_id,
          offered.title AS offered_title, requested.title AS requested_title
          FROM reviews r
          JOIN users reviewer ON r.reviewer_id = reviewer.id
          JOIN trades t ON r.trade_id = t.id
          JOIN listings offered ON t.offered_item_id = offered.id
          JOIN listings requested ON t.requested_item_id = requested.id
          WHERE r.reviewed_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $listing['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Fetch similar listings (same category, active, limit 3)
$query = "SELECT l.*, u.full_name FROM listings l JOIN users u ON l.user_id = u.id WHERE l.category = ? AND l.status = 'active' AND l.id != ? LIMIT 3";
$stmt = $conn->prepare($query);
$stmt->bind_param('si', $listing['category'], $listing_id);
$stmt->execute();
$result = $stmt->get_result();
$similar_listings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch other listings by the same user (active, limit 3)
$query = "SELECT l.*, u.full_name FROM listings l JOIN users u ON l.user_id = u.id WHERE l.user_id = ? AND l.status = 'active' AND l.id != ? LIMIT 3";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $listing['user_id'], $listing_id);
$stmt->execute();
$result = $stmt->get_result();
$other_listings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Process images
$images = !empty($listing['image_url']) ? explode(',', $listing['image_url']) : [];

// Process looking_for
$looking_for = json_decode($listing['looking_for'] ?? '[]', true) ?? [];

$page_title = htmlspecialchars($listing['title']) . ' - ' . include "../includes/Name.php";
include '../includes/header.php';
?>

<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-emerald-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-sync-alt text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-slate-900"><?php include "../includes/Name.php";?></span>
            </a>
            <div class="flex items-center space-x-4">
                <a href="messages.php" class="text-slate-600 hover:text-slate-900">
                    <i class="fas fa-envelope"></i>
                </a>
                <a href="notifications.php" class="text-slate-600 hover:text-slate-900">
                    <i class="fas fa-bell"></i>
                </a>
                <a href="profile.php" class="text-slate-600 hover:text-slate-900">
                    <i class="fas fa-user"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm text-slate-600">
            <a href="marketplace.php" class="hover:text-emerald-500">Marketplace</a> > 
            <a href="marketplace.php?category=<?php echo htmlspecialchars($listing['category']); ?>" class="hover:text-emerald-500"><?php echo htmlspecialchars($listing['category_name']); ?></a> > 
            <?php echo htmlspecialchars($listing['title']); ?>
        </nav>

    <!-- Main Listing Content -->
    <div class="grid lg:grid-cols-3 gap-8 h-full">
        <!-- Listing Details -->
        <div class="lg:col-span-2 flex flex-col h-full">
            <div class="bg-white rounded-3xl shadow-lg p-6 flex-1">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-slate-900"><?php echo htmlspecialchars($listing['title']); ?></h1>
                    <span class="px-3 py-1 bg-<?php echo $listing['status'] === 'active' ? 'emerald' : 'slate'; ?>-100 text-<?php echo $listing['status'] === 'active' ? 'emerald' : 'slate'; ?>-700 rounded-xl text-sm">
                        <?php echo ucfirst($listing['status']); ?>
                    </span>
                </div>

                <!-- Images Gallery -->
                <?php if (!empty($images)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <?php foreach ($images as $image): ?>
                            <img src="<?php echo htmlspecialchars(trim($image)); ?>" alt="Listing image" class="w-full h-64 object-cover rounded-2xl">
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="w-full h-64 bg-slate-200 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> text-slate-400 text-6xl"></i>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-900 mb-2">Description</h2>
                    <p class="text-slate-600"><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-sm text-slate-500">Type</p>
                        <p class="font-medium"><?php echo ucfirst($listing['type']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Category</p>
                        <p class="font-medium"><?php echo htmlspecialchars($listing['category_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Created</p>
                        <p class="font-medium"><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Updated</p>
                        <p class="font-medium"><?php echo date('M d, Y', strtotime($listing['updated_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Views</p>
                        <p class="font-medium"><?php echo $listing['views']; ?></p>
                    </div>
                </div>

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-900 mb-2">Looking For</h2>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($looking_for as $item): ?>
                            <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-xl text-sm">
                                <?php echo htmlspecialchars($item); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>                
        </div>

        <!-- Sidebar: Owner Info, Actions, Reviews, More From, Similar -->
        <div class="lg:col-span-1 flex flex-col h-full space-y-8">
            <!-- Owner Profile -->
            <div class="bg-white rounded-3xl shadow-lg p-6 flex-1">
                <!-- Owner Header -->
                <div class="flex items-center mb-4">
                    <?php if ($listing['avatar_url']): ?>
                        <img src="<?php echo htmlspecialchars($listing['avatar_url']); ?>" alt="Avatar" class="w-12 h-12 rounded-full mr-3">
                    <?php else: ?>
                        <div class="w-12 h-12 bg-slate-200 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user text-slate-600"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h3 class="font-semibold text-slate-900">
                            <?php echo htmlspecialchars($listing['full_name']); ?>
                        </h3>
                        <p class="text-sm text-slate-600">
                            <?php echo htmlspecialchars($listing['role']); ?>
                        </p>
                    </div>
                </div>

                <!-- Stats -->
                <?php if ($user_rating): ?>
                    <div class="flex items-center mb-2">
                        <i class="fas fa-star text-amber-400 mr-1"></i>
                        <span class="font-medium"><?php echo $user_rating; ?> (avg rating)</span>
                    </div>
                <?php endif; ?>
                <div class="flex items-center mb-2">
                    <i class="fas fa-handshake text-slate-600 mr-1"></i>
                    <span><?php echo $trade_count; ?> completed trades</span>
                </div>
                <?php if ($listing['location']): ?>
                    <div class="flex items-center mb-2">
                        <i class="fas fa-map-marker-alt text-slate-600 mr-1"></i>
                        <span><?php echo htmlspecialchars($listing['location']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Bio -->
                <?php if ($listing['bio']): ?>
                    <p class="text-sm text-slate-600 mt-2">
                        <?php echo nl2br(htmlspecialchars($listing['bio'])); ?>
                    </p>
                <?php endif; ?>

                <!-- Actions -->
                <div class="mt-4 space-y-2">
                    <a href="profile.php?id=<?php echo $listing['user_id']; ?>" 
                    class="block bg-slate-100 hover:bg-slate-200 py-2 rounded-2xl text-center text-sm font-medium">
                    View Profile
                    </a>
                    <a href="messages.php?user=<?php echo $listing['user_id']; ?>" 
                    class="block bg-emerald-500 hover:bg-emerald-600 text-white py-2 rounded-2xl text-center text-sm font-medium">
                    Message Owner
                    </a>
                    <?php if ($listing['status'] === 'active' && $listing['user_id'] != ($_SESSION['user_id'] ?? 0)): ?>
                        <a href="propose-trade.php?listing_id=<?php echo $listing['id']; ?>" 
                        class="block bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-2xl text-center text-sm font-medium">
                        Propose Trade
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reviews -->
            <div class="bg-white rounded-3xl shadow-lg p-6 flex-1">
                <h2 class="text-xl font-bold text-slate-900 mb-4">
                    Reviews for <?php echo htmlspecialchars($listing['full_name']); ?>
                </h2>
                <?php if (!empty($reviews)): ?>
                    <div class="space-y-4">
                        <?php foreach ($reviews as $review): ?>
                            <div class="p-4 bg-slate-50 rounded-2xl">
                                <div class="flex justify-between mb-2">
                                    <span class="font-semibold">
                                        <?php echo htmlspecialchars($review['reviewer_name']); ?>
                                    </span>
                                    <div class="flex items-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-amber-400' : 'text-slate-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php if (!empty($review['comment'])): ?>
                                    <p class="text-sm text-slate-600 mb-2">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="text-xs text-slate-500">
                                    Trade: <?php echo htmlspecialchars($review['offered_title'] . ' <-> ' . $review['requested_title']); ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-slate-500">No reviews yet.</p>
                <?php endif; ?>
            </div>
        </div>
        </div> <!-- closes the grid (lg:grid-cols-3) -->
    
    <!-- More Options (Full Width) -->
    <div class="mt-8">
        <div class="bg-white rounded-3xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-slate-900 mb-4">More Options</h2>

            <!-- Toggle Tabs -->
            <div class="flex border-b border-slate-200 mb-4">
                <button 
                    class="tab-btn px-4 py-2 text-sm font-medium text-slate-600 border-b-2 border-transparent hover:text-slate-900" 
                    data-target="more-from">
                    More from this user
                </button>
                <button 
                    class="tab-btn px-4 py-2 text-sm font-medium text-slate-600 border-b-2 border-transparent hover:text-slate-900 ml-4" 
                    data-target="similar">
                    Similar listings
                </button>
            </div>

            <!-- Tab Content: More From -->
            <div id="tab-more-from" class="tab-content">
                <?php if (!empty($other_listings)): ?>
                    <div class="space-y-3">
                        <?php foreach ($other_listings as $other): ?>
                            <a href="listing.php?id=<?php echo $other['id']; ?>" 
                               class="block p-3 bg-slate-50 rounded-2xl hover:bg-slate-100 transition">
                                <h3 class="font-semibold text-slate-900 truncate">
                                    <?php echo htmlspecialchars($other['title']); ?>
                                </h3>
                                <p class="text-sm text-slate-600">Type: <?php echo ucfirst($other['type']); ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-slate-500">This user has no other active listings.</p>
                <?php endif; ?>
            </div>

            <!-- Tab Content: Similar -->
            <div id="tab-similar" class="tab-content hidden">
                <?php if (!empty($similar_listings)): ?>
                    <div class="space-y-3">
                        <?php foreach ($similar_listings as $sim): ?>
                            <a href="listing.php?id=<?php echo $sim['id']; ?>" 
                               class="block p-3 bg-slate-50 rounded-2xl hover:bg-slate-100 transition">
                                <h3 class="font-semibold text-slate-900 truncate">
                                    <?php echo htmlspecialchars($sim['title']); ?>
                                </h3>
                                <p class="text-sm text-slate-600">
                                    <?php echo htmlspecialchars($sim['full_name']); ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-slate-500">No similar listings found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>


<!-- JS for Tabs -->
<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Reset all tabs
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('text-slate-900', 'border-blue-500');
            b.classList.add('text-slate-600', 'border-transparent');
        });
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));

        // Activate current
        btn.classList.remove('text-slate-600', 'border-transparent');
        btn.classList.add('text-slate-900', 'border-blue-500');
        document.getElementById('tab-' + btn.dataset.target).classList.remove('hidden');
    });
});

// Set default active
document.querySelector('.tab-btn').click();
</script>

    </div>
</div>

<?php include '../includes/footer.php'; ?>