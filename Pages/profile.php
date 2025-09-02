<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();

$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$is_own_profile = $profile_user_id == $_SESSION['user_id'];

// Get user profile data
$query = "SELECT u.*, 
          (SELECT AVG(rating) FROM reviews WHERE reviewed_user_id = u.id) as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE reviewed_user_id = u.id) as review_count,
          (SELECT COUNT(*) FROM trades WHERE (requester_id = u.id OR owner_id = u.id) AND status = 'completed') as completed_trades
          FROM users u WHERE u.id = ? AND u.status = 'active'";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile_user = $result->fetch_assoc();
$stmt->close();

if (!$profile_user) {
    redirect('dashboard.php');
}

if ($is_own_profile) {
    // Fetch all listings
    $query_all = "SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query_all);
    $stmt->bind_param('i', $profile_user_id);
    $stmt->execute();
    $result_all = $stmt->get_result();
    $all_listings = $result_all->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch only active listings (for display in the card)
    $query_active = "SELECT * FROM listings WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 6";
    $stmt = $conn->prepare($query_active);
    $stmt->bind_param('i', $profile_user_id);
    $stmt->execute();
    $result_active = $stmt->get_result();
    $listings = $result_active->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // On someone else's profile → only active listings
    $query = "SELECT * FROM listings WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 6";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $profile_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $listings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $all_listings = []; // not needed, keep it empty
}


// Get user's active listings
$query = "SELECT * FROM listings WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 6";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();
$listings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent reviews
$query = "SELECT r.*, u.full_name as reviewer_name, u.avatar_url as reviewer_avatar 
          FROM reviews r 
          JOIN users u ON r.reviewer_id = u.id 
          WHERE r.reviewed_user_id = ? 
          ORDER BY r.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

$page_title = htmlspecialchars($profile_user['full_name']) . "'s Profile";
include '../includes/header.php';
?>

<div class="min-h-screen bg-slate-50">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
        <a href="javascript:void(0);"
   onclick="history.go(-2);"
   class="flex items-center space-x-2 text-slate-600 hover:text-slate-900">
    <i class="fas fa-arrow-left"></i>
    <span>Back</span>
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
        <!-- Avatar Modal -->
        <div id="avatarModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-80 max-w-full relative">
        <button id="closeModal" class="absolute top-2 right-2 text-slate-500 hover:text-slate-900">
            <i class="fas fa-times"></i>
        </button>
        <h3 class="text-xl font-bold mb-4 text-slate-900">Profile Avatar</h3>

        <div class="w-48 h-48 mx-auto rounded-full overflow-hidden border border-slate-200 mb-4">
            <img src="<?php echo htmlspecialchars($profile_user['avatar_url']); ?>" alt="Avatar" class="w-full h-full object-cover">
        </div>

        <!-- Upload Avatar Form -->
        <form action="update-avatar.php" method="post" enctype="multipart/form-data" class="flex flex-col items-center space-y-2">
            <input type="file" name="avatar" accept="image/*" class="border border-slate-300 rounded px-2 py-1">
            <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium flex items-center space-x-2">
                <i class="fas fa-upload"></i> 
                <span>Upload</span>
            </button>
        </form>

        <!-- Remove Avatar Button -->
        <form action="remove-avatar.php" method="post" class="mt-2 flex justify-center">
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl font-medium flex items-center space-x-2">
                <i class="fas fa-trash"></i>
                <span>Remove</span>
            </button>
        </form>
    </div>
</div>



        <!-- Profile Header -->
        <div class="bg-white rounded-3xl shadow-lg p-8 mb-8">
            <div class="flex flex-col md:flex-row items-start md:items-center space-y-6 md:space-y-0 md:space-x-8">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                <div class="w-24 h-24 bg-slate-200 rounded-full flex items-center justify-center cursor-pointer profile-avatar">
    <?php if ($profile_user['avatar_url']): ?>
        <img src="<?php echo htmlspecialchars($profile_user['avatar_url']); ?>" alt="" class="w-full h-full rounded-full object-cover">
    <?php else: ?>
        <i class="fas fa-user text-slate-600 text-3xl"></i>
    <?php endif; ?>
</div>

                </div>
                <script>
document.addEventListener('DOMContentLoaded', function() {
    const avatar = document.querySelector('.profile-avatar');
    const modal = document.getElementById('avatarModal');
    const closeModal = document.getElementById('closeModal');

    if(avatar && modal && closeModal){
        avatar.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        closeModal.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
    }
});
</script>

                <!-- Profile Info -->
                <div class="flex-1">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                        <div>
                            <h1 class="text-3xl font-bold text-slate-900 mb-2"><?php echo htmlspecialchars($profile_user['full_name']); ?></h1>
                            <div class="flex items-center space-x-4 text-slate-600">
                                <?php if ($profile_user['location']): ?>
                                    <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($profile_user['location']); ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-calendar mr-1"></i>Joined <?php echo date('M Y', strtotime($profile_user['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!$is_own_profile): ?>
                            <div class="flex space-x-3 mt-4 md:mt-0">
                                <a href="messages.php?user=<?php echo $profile_user_id; ?>" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold">
                                    <i class="fas fa-comment mr-2"></i>
                                    Message
                                </a>
                                <button class="border border-slate-200 hover:bg-slate-50 px-6 py-3 rounded-2xl">
                                    <i class="fas fa-flag mr-2"></i>
                                    Report
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bio -->
                    <?php if ($profile_user['bio']): ?>
                        <p class="text-slate-700 mb-4"><?php echo htmlspecialchars($profile_user['bio']); ?></p>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div class="flex flex-wrap gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-slate-900"><?php echo $profile_user['completed_trades']; ?></div>
                            <div class="text-sm text-slate-600">Completed Trades</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-slate-900"><?php echo count($listings); ?></div>
                            <div class="text-sm text-slate-600">Active Listings</div>
                        </div>
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <span class="text-2xl font-bold text-slate-900">
                                    <?php echo $profile_user['avg_rating'] ? number_format($profile_user['avg_rating'], 1) : 'N/A'; ?>
                                </span>
                                <?php if ($profile_user['avg_rating']): ?>
                                    <i class="fas fa-star text-amber-400"></i>
                                <?php endif; ?>
                            </div>
                            <div class="text-sm text-slate-600"><?php echo $profile_user['review_count']; ?> Reviews</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Active Listings -->
                <div class="bg-white rounded-3xl shadow-lg p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-slate-900">
                            <?php echo $is_own_profile ? 'Your' : 'Active'; ?> Listings
                        </h2>
                        <?php if ($is_own_profile): ?>
                            <a href="my-listings.php" class="text-emerald-500 hover:text-emerald-600 text-sm">View All</a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($listings)): ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-box-open text-slate-400 text-2xl"></i>
                            </div>
                            <p class="text-slate-600">
                                <?php if ($is_own_profile): ?>
                                    <?php if (empty($all_listings)): ?>
                                        You haven't created any listings yet
                                    <?php else: ?>
                                        You don't have any active listings
                                    <?php endif; ?>
                                <?php else: ?>
                                    No active listings
                                <?php endif; ?>
                            </p>
                            <?php if ($is_own_profile && empty($all_listings)): ?>
                                <a href="create-listing.php" class="text-emerald-500 hover:text-emerald-600 text-sm">
                                    Create your first listing
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="grid md:grid-cols-2 gap-6">
                            <?php foreach ($listings as $listing): ?>
                                <div class="border border-slate-200 rounded-2xl p-4 hover:shadow-lg transition-shadow">
                                    <div class="flex items-start space-x-4">
                                        <div class="w-16 h-16 bg-slate-200 rounded-xl flex items-center justify-center flex-shrink-0">
                                            <?php if ($listing['image_url']): ?>
                                                <img src="<?php echo htmlspecialchars($listing['image_url']); ?>" alt="" class="w-full h-full rounded-xl object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> text-slate-400"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-slate-900 mb-1"><?php echo htmlspecialchars($listing['title']); ?></h3>
                                            <p class="text-slate-600 text-sm line-clamp-2 mb-2"><?php echo htmlspecialchars($listing['description']); ?></p>
                                            <div class="flex items-center justify-between">
                                                <span class="px-2 py-1 rounded-lg text-xs font-medium <?php echo $listing['type'] === 'product' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>">
                                                    <?php echo ucfirst($listing['type']); ?>
                                                </span>
                                                <?php if (!$is_own_profile): ?>
                                                    <a href="propose-trade.php?listing_id=<?php echo $listing['id']; ?>" class="text-emerald-500 hover:text-emerald-600 text-sm font-medium">
                                                        Propose Trade
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Reviews -->
                <div class="bg-white rounded-3xl shadow-lg p-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">Reviews</h2>

                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-star text-slate-400 text-2xl"></i>
                            </div>
                            <p class="text-slate-600">No reviews yet</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($reviews as $review): ?>
                                <div class="border-b border-slate-200 pb-6 last:border-b-0">
                                    <div class="flex items-start space-x-4">
                                        <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center flex-shrink-0">
                                            <?php if ($review['reviewer_avatar']): ?>
                                                <img src="<?php echo htmlspecialchars($review['reviewer_avatar']); ?>" alt="" class="w-full h-full rounded-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-slate-600 text-sm"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="font-medium text-slate-900"><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                                <div class="flex items-center space-x-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-amber-400' : 'text-slate-300'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <?php if ($review['comment']): ?>
                                                <p class="text-slate-600 text-sm mb-2"><?php echo htmlspecialchars($review['comment']); ?></p>
                                            <?php endif; ?>
                                            <p class="text-slate-500 text-xs"><?php echo formatTimeAgo($review['created_at']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Contact Info -->
                <div class="bg-white rounded-3xl shadow-lg p-6">
                    <h3 class="font-bold text-slate-900 mb-4">Contact Information</h3>
                    <div class="space-y-3">
                        <?php if ($profile_user['email'] && ($is_own_profile || $profile_user['show_email'])): ?>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-envelope text-slate-400"></i>
                                <span class="text-slate-700"><?php echo htmlspecialchars($profile_user['email']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($profile_user['phone'] && ($is_own_profile || $profile_user['show_phone'])): ?>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-phone text-slate-400"></i>
                                <span class="text-slate-700"><?php echo htmlspecialchars($profile_user['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($profile_user['location']): ?>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-map-marker-alt text-slate-400"></i>
                                <span class="text-slate-700"><?php echo htmlspecialchars($profile_user['location']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Trading Stats -->
                <div class="bg-white rounded-3xl shadow-lg p-6">
                    <h3 class="font-bold text-slate-900 mb-4">Trading Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-slate-600">Response Rate</span>
                            <span class="font-medium text-slate-900">95%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Avg Response Time</span>
                            <span class="font-medium text-slate-900">2 hours</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Member Since</span>
                            <span class="font-medium text-slate-900"><?php echo date('M Y', strtotime($profile_user['created_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Last Active</span>
                            <span class="font-medium text-slate-900">Today</span>
                        </div>
                    </div>
                </div>

                <!-- Verification Status -->
                <div class="bg-white rounded-3xl shadow-lg p-6">
                    <h3 class="font-bold text-slate-900 mb-4">Verification</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-check-circle text-emerald-500"></i>
                            <span class="text-slate-700">Email Verified</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-check-circle text-emerald-500"></i>
                            <span class="text-slate-700">Phone Verified</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-times-circle text-slate-400"></i>
                            <span class="text-slate-500">ID Not Verified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>