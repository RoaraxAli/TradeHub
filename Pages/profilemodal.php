<div id="customModal2" 
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-lg p-6 w-[80%] max-w-4xl relative"
         style="max-height: 80vh; overflow-y: auto;">
        <button id="closeModalBtn2" 
                class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl font-bold">
            &times;
        </button>
        <style>
/* For WebKit browsers (Chrome, Edge, Safari) */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 8px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 8px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Firefox */
* {
    scrollbar-width: thin;
    scrollbar-color: #888 #f1f1f1;
}
</style>

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

            // Fetch only active listings
            $query_active = "SELECT * FROM listings WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 6";
            $stmt = $conn->prepare($query_active);
            $stmt->bind_param('i', $profile_user_id);
            $stmt->execute();
            $result_active = $stmt->get_result();
            $listings1 = $result_active->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            // On someone else's profile → only active listings
            $query = "SELECT * FROM listings WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 6";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $profile_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $listings1 = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            $all_listings = [];
        }

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


        $page_title = htmlspecialchars($profile_user['full_name']) . "'s Profile";
        ?>

        <!-- Profile Header -->
        <div class="bg-white rounded-2xl p-6 mb-6">
            <div class="flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-6">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <div class="w-20 h-20 bg-slate-200 rounded-full flex items-center justify-center">
                        <?php if ($profile_user['avatar_url']): ?>
                            <img src="<?php echo htmlspecialchars($profile_user['avatar_url']); ?>" alt="" class="w-full h-full rounded-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-slate-600 text-2xl"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Info -->
                <div class="flex-1">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-3">
                        <div>
                        <div class="flex items-center justify-between mb-2">
                        <h1 class="text-2xl font-bold text-slate-900">
                            <?php echo htmlspecialchars($profile_user['full_name']); ?>
                        </h1>
                        <a href="profile.php" class="ms-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                            View Complete Profile
                        </a>

                    </div>

                            <div class="flex items-center space-x-3 text-slate-600 text-sm">
                                <?php if ($profile_user['location']): ?>
                                    <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($profile_user['location']); ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-calendar mr-1"></i>Joined <?php echo date('M Y', strtotime($profile_user['created_at'])); ?></span>
                            </div>
                        </div>
                        <?php if (!$is_own_profile): ?>
                            <div class="flex space-x-2 mt-3 md:mt-0">
                                <a href="messages.php?user=<?php echo $profile_user_id; ?>" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium text-sm">
                                    <i class="fas fa-comment mr-1"></i>Message
                                </a>
                                <button class="border border-slate-200 hover:bg-slate-50 px-4 py-2 rounded-xl text-sm">
                                    <i class="fas fa-flag mr-1"></i>Report
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bio -->
                    <?php if ($profile_user['bio']): ?>
                        <p class="text-slate-700 text-sm mb-3"><?php echo htmlspecialchars($profile_user['bio']); ?></p>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div class="flex flex-wrap gap-4">
                        <div class="text-center">
                            <div class="text-lg font-bold text-slate-900"><?php echo $profile_user['completed_trades']; ?></div>
                            <div class="text-xs text-slate-600">Completed Trades</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-slate-900"><?php echo count($listings1); ?></div>
                            <div class="text-xs text-slate-600">Active Listings</div>
                        </div>
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <span class="text-lg font-bold text-slate-900">
                                    <?php echo $profile_user['avg_rating'] ? number_format($profile_user['avg_rating'], 1) : 'N/A'; ?>
                                </span>
                                <?php if ($profile_user['avg_rating']): ?>
                                    <i class="fas fa-star text-amber-400"></i>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-600"><?php echo $profile_user['review_count']; ?> Reviews</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Active Listings -->
                <div class="bg-white rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-slate-900">
                            <?php echo $is_own_profile ? 'Your' : 'Active'; ?> Listings
                        </h2>
                        <?php if ($is_own_profile): ?>
                            <a href="my-listings.php" class="text-emerald-500 hover:text-emerald-600 text-sm">View All</a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($listings1)): ?>
                        <div class="text-center py-6">
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-box-open text-slate-400 text-xl"></i>
                            </div>
                            <p class="text-slate-600 text-sm">
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
                        <div class="grid md:grid-cols-2 gap-4">
                            <?php foreach ($listings1 as $listing): ?>
                                <div class="border border-slate-200 rounded-xl p-3 hover:shadow-md transition-shadow">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-12 h-12 bg-slate-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <?php if ($listing['image_url']): ?>
                                                <img src="<?php echo htmlspecialchars($listing['image_url']); ?>" alt="" class="w-full h-full rounded-lg object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> text-slate-400"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-slate-900 text-sm mb-1"><?php echo htmlspecialchars($listing['title']); ?></h3>
                                            <p class="text-slate-600 text-xs line-clamp-2 mb-2"><?php echo htmlspecialchars($listing['description']); ?></p>
                                            <div class="flex items-center justify-between">
                                                <span class="px-2 py-1 rounded-lg text-xs font-medium <?php echo $listing['type'] === 'product' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>">
                                                    <?php echo ucfirst($listing['type']); ?>
                                                </span>
                                                <?php if (!$is_own_profile): ?>
                                                    <a href="propose-trade.php?listing_id=<?php echo $listing['id']; ?>" class="text-emerald-500 hover:text-emerald-600 text-xs font-medium">
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
                <div class="bg-white rounded-2xl p-6">
                    <h2 class="text-lg font-bold text-slate-900 mb-4">Reviews</h2>
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-6">
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-star text-slate-400 text-xl"></i>
                            </div>
                            <p class="text-slate-600 text-sm">No reviews yet</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($reviews as $review): ?>
                                <div class="border-b border-slate-200 pb-4 last:border-b-0">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center flex-shrink-0">
                                            <?php if ($review['reviewer_avatar']): ?>
                                                <img src="<?php echo htmlspecialchars($review['reviewer_avatar']); ?>" alt="" class="w-full h-full rounded-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-slate-600 text-xs"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between mb-1">
                                                <h4 class="font-medium text-slate-900 text-sm"><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                                <div class="flex items-center space-x-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-amber-400' : 'text-slate-300'; ?> text-xs"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <?php if ($review['comment']): ?>
                                                <p class="text-slate-600 text-xs mb-1"><?php echo htmlspecialchars($review['comment']); ?></p>
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
            <div>
                <!-- Trading Stats -->
                <div class="bg-white rounded-2xl p-4">
                    <h3 class="font-bold text-slate-900 mb-3 text-sm">Trading Statistics</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-600 text-sm">Response Rate</span>
                            <span class="font-medium text-slate-900 text-sm">95%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600 text-sm">Avg Response Time</span>
                            <span class="font-medium text-slate-900 text-sm">2 hours</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600 text-sm">Member Since</span>
                            <span class="font-medium text-slate-900 text-sm"><?php echo date('M Y', strtotime($profile_user['created_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600 text-sm">Last Active</span>
                            <span class="font-medium text-slate-900 text-sm">Today</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>