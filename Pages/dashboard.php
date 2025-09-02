<?php include '../includes/maintenance.php'?>
<?php
require_once '../config/config.php';
requireLogin();
// Get user stats
$user_id = $_SESSION['user_id'];

// Get recent listings
$conn = getDBConnection();
$query = "SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC LIMIT 2";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$recent_listings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent trades
$query = "SELECT 
    t.*,
    l1.title AS offered_item_title,
    l1.image_url AS offered_item_image,
    l2.title AS requested_item_title,
    l2.image_url AS requested_item_image,
    CASE 
        WHEN t.requester_id = ? THEN u2.full_name 
        ELSE u1.full_name 
    END AS partner_name
FROM trades t
JOIN listings l1 ON t.offered_item_id = l1.id
JOIN listings l2 ON t.requested_item_id = l2.id
JOIN users u1 ON t.requester_id = u1.id
JOIN users u2 ON t.owner_id = u2.id
WHERE t.requester_id = ? OR t.owner_id = ?
ORDER BY t.created_at DESC
LIMIT 2";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('iii', $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$recent_trades = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$query = "SELECT COUNT(*) AS total_active FROM listings WHERE user_id = ? AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$active_count = $stmt->get_result()->fetch_assoc()['total_active'];
$stmt->close();

$count_trades = "SELECT 
        COALESCE(SUM(status='pending'), 0) AS pending_count,
    COALESCE(SUM(status='completed'), 0) AS completed_count

FROM trades
WHERE requester_id = ? OR owner_id = ?";
$stmt = $conn->prepare($count_trades);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$trade_counts = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();

$page_title = 'Dashboard';
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php include "../includes/Name.php";?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200px 0;
            }
            100% {
                background-position: calc(200px + 100%) 0;
            }
        }

        @keyframes subtleGlow {
            0%, 100% {
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
            }
            50% {
                box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .animate-slide-in-left {
            animation: slideInLeft 0.5s ease-out forwards;
        }

        .animate-scale-in {
            animation: scaleIn 0.4s ease-out forwards;
        }

        .animate-delay-100 { animation-delay: 0.1s; }
        .animate-delay-200 { animation-delay: 0.2s; }
        .animate-delay-300 { animation-delay: 0.3s; }
        .animate-delay-400 { animation-delay: 0.4s; }
        .animate-delay-500 { animation-delay: 0.5s; }
        .animate-delay-600 { animation-delay: 0.6s; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-emerald-50/30 to-slate-100">
    <div class="md:p-9">
        <div class="max-w-full mx-auto h-[100vh] md:h-[calc(95vh-3rem)]">
            
            <div class="flex h-full bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl border border-white/50 overflow-hidden animate-scale-in">
                <?php include '../includes/sidebar.php'; ?>
        
                <div class="flex-1 flex flex-col overflow-hidden">
                    <?php include '../includes/head.php'; ?>
                    <div class="flex-1 overflow-y-auto p-8 bg-gradient-to-br from-slate-50/50 to-white">
                        <div class="mb-4 animate-fade-in-up animate-delay-300">
                            <h2 class="text-2xl font-bold text-slate-900 mb-4 flex items-center">
                                <div class="w-1 h-8 bg-gradient-to-b from-emerald-500 to-emerald-600 rounded-full mr-4"></div>
                                Quick Actions
                            </h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                            <a href="my-listings.php" class="group p-6 rounded-3xl text-center transition-all transform hover:scale-105 bg-emerald-500 hover:bg-emerald-600 text-white shadow-md hover:shadow-2xl animate-scale-in animate-delay-200 flex flex-col justify-between h-full">
                                <i class="fas fa-plus text-2xl mb-4 group-hover:scale-110 transition-transform relative z-10 icon-hover"></i>
                                <h3 class="font-bold text-lg mb-2 relative z-10">Create Listing</h3>
                                <p class="text-sm relative z-10 line-clamp-3">Edit or add new item or service to trade</p>
                            </a>

                                <a href="marketplace.php" class="group glass-card hover:shadow-2xl p-6 rounded-3xl text-center transition-all transform hover:scale-105 card-hover border-2 border-slate-100 hover:border-emerald-200 animate-scale-in animate-delay-200">
                                    <i class="fas fa-search text-2xl mb-4 text-blue-500 group-hover:scale-110 group-hover:text-blue-600 transition-all icon-hover"></i>
                                    <h3 class="font-bold text-lg text-slate-900 mb-2">Browse Market</h3>
                                    <p class="text-sm text-slate-600">Discover amazing items to trade</p>
                                </a>
                                <a href="wallet.php" class="group glass-card hover:shadow-2xl p-6 rounded-3xl text-center transition-all transform hover:scale-105 card-hover border-2 border-slate-100 hover:border-amber-200 animate-scale-in animate-delay-300">
                                    <i class="fas fa-wallet text-2xl mb-4 text-amber-500 group-hover:scale-110 group-hover:text-amber-600 transition-all icon-hover"></i>
                                    <h3 class="font-bold text-lg text-slate-900 mb-2">Credits Wallet</h3>
                                    <p class="text-sm text-slate-600">Manage your trading credits</p>
                                </a>
                            </div>
                        </div>

                        <div class="mb-4 animate-fade-in-up animate-delay-400">
                            <h2 class="text-2xl font-bold text-slate-900 mb-8 flex items-center">
                                <div class="w-1 h-8 bg-gradient-to-b from-emerald-500 to-emerald-600 rounded-full mr-4"></div>
                                Overview
                            </h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                                <div class="bg-white p-4 sm:p-6 rounded-3xl shadow-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-slate-600 text-xs sm:text-sm">Active Listings</p>
                                            <p class="text-xl sm:text-2xl font-bold text-slate-900">
                                                <?php echo $active_count; ?>
                                            </p>
                                        </div>
                                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-list text-blue-600 text-base sm:text-lg"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white p-4 sm:p-6 rounded-3xl shadow-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-slate-600 text-xs sm:text-sm">Pending Trades</p>
                                            <p class="text-xl sm:text-2xl font-bold text-slate-900">
                                                <?php echo $trade_counts['pending_count']; ?>
                                            </p>
                                        </div>
                                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-amber-100 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-clock text-amber-600 text-base sm:text-lg"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white p-4 sm:p-6 rounded-3xl shadow-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-slate-600 text-xs sm:text-sm">Completed Trades</p>
                                            <p class="text-xl sm:text-2xl font-bold text-slate-900">
                                                <?php echo $trade_counts['completed_count']; ?>
                                            </p>
                                        </div>
                                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-100 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-check text-emerald-600 text-base sm:text-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="animate-fade-in-up animate-delay-500">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-3 sm:mb-4 flex items-center">
                                <div class="w-1 h-6 sm:h-8 bg-gradient-to-b from-emerald-500 to-emerald-600 rounded-full mr-3 sm:mr-4"></div>
                                Recent Activity
                            </h2>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-10">
                                
                                <!-- Recent Listings -->
                                <div class="bg-white rounded-2xl sm:rounded-3xl shadow-lg p-3 sm:p-6">
                                <div class="flex items-center justify-between mb-3 sm:mb-6">
                                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Recent Listings</h2>
                                    <a href="my-listings.php" class="text-emerald-500 hover:text-emerald-600 text-xs sm:text-sm">View All</a>
                                </div>

                                <?php if (empty($recent_listings)): ?>
                                    <div class="text-center py-4 sm:py-6">
                                    <i class="fas fa-box-open text-2xl sm:text-4xl text-slate-300 mb-3"></i>
                                    <p class="text-slate-600 text-xs sm:text-base">No listings yet</p>
                                    <a href="create-listing.php" class="text-emerald-500 hover:text-emerald-600 text-xs sm:text-sm">Create your first listing</a>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-3 sm:space-y-4">
                                    <?php foreach ($recent_listings as $listing): ?>
                                        <div class="flex items-center space-x-3 sm:space-x-4 p-2 sm:p-4 bg-slate-50 rounded-xl sm:rounded-2xl">
                                        <div class="w-9 h-9 sm:w-12 sm:h-12 bg-slate-200 rounded-lg sm:rounded-xl flex items-center justify-center overflow-hidden">
                                            <?php
                                            $firstImage = null;
                                            if (!empty($listing['image_url'])) {
                                            $images = explode(',', $listing['image_url']);
                                            $firstImage = trim($images[0]);
                                            }
                                            ?>
                                            <?php if (!empty($firstImage)): ?>
                                            <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="Listing Image" class="w-full h-full object-cover">
                                            <?php else: ?>
                                            <i class="fas fa-<?php echo $listing['type'] == 'product' ? 'box' : 'tools'; ?> text-slate-600 text-sm sm:text-xl"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1 min-w-0 hidden [@media(min-width:350px)]:block">
                                            <h3 class="font-medium text-slate-900 text-sm sm:text-base truncate">
                                            <?php echo htmlspecialchars($listing['title']); ?>
                                            </h3>
                                            <p class="text-xs sm:text-sm text-slate-600 truncate">
                                            <?php echo formatTimeAgo($listing['created_at']); ?>
                                            </p>
                                        </div>
                                        <span class="px-2 py-0.5 sm:px-3 sm:py-1 bg-emerald-100 text-emerald-700 rounded-lg sm:rounded-xl text-xs whitespace-nowrap">
                                            <?php echo ucfirst($listing['status']); ?>
                                        </span>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                </div>

                                <!-- Recent Trades -->
                                <div class="bg-white rounded-2xl sm:rounded-3xl shadow-lg p-3 sm:p-6">
                                <div class="flex items-center justify-between mb-3 sm:mb-6">
                                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Recent Trades</h2>
                                    <a href="trades.php" class="text-emerald-500 hover:text-emerald-600 text-xs sm:text-sm">View All</a>
                                </div>

                                <?php if (empty($recent_trades)): ?>
                                    <div class="text-center py-4 sm:py-6">
                                    <i class="fas fa-handshake text-2xl sm:text-4xl text-slate-300 mb-3"></i>
                                    <p class="text-slate-600 text-xs sm:text-base">No trades yet</p>
                                    <a href="marketplace.php" class="text-emerald-500 hover:text-emerald-600 text-xs sm:text-sm">Browse marketplace</a>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-3 sm:space-y-4">
                                    <?php foreach ($recent_trades as $trade): ?>
                                        <div class="flex items-center space-x-3 sm:space-x-4 p-2 sm:p-4 bg-slate-50 rounded-xl sm:rounded-2xl">
                                        <!-- Offered item -->
                                        <div class="w-9 h-9 sm:w-12 sm:h-12 bg-slate-200 rounded-lg sm:rounded-xl flex items-center justify-center overflow-hidden">
                                            <?php
                                            $firstImage = null;
                                            if (!empty($trade['offered_item_image'])) {
                                            $images = array_map('trim', explode(',', $trade['offered_item_image']));
                                            $firstImage = $images[0] ?? null;
                                            }
                                            ?>
                                            <?php if (!empty($firstImage)): ?>
                                            <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="" class="w-full h-full object-cover">
                                            <?php else: ?>
                                            <i class="fas fa-box text-slate-400 text-sm sm:text-xl"></i>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Exchange Icon -->
                                        <div class="w-5 h-5 sm:w-8 sm:h-8 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-exchange-alt text-emerald-600 text-xs sm:text-sm"></i>
                                        </div>

                                        <!-- Requested item -->
                                        <div class="w-9 h-9 sm:w-12 sm:h-12 bg-slate-200 rounded-lg sm:rounded-xl flex items-center justify-center overflow-hidden">
                                            <?php
                                            $firstImage = null;
                                            if (!empty($trade['requested_item_image'])) {
                                            $images = array_map('trim', explode(',', $trade['requested_item_image']));
                                            $firstImage = $images[0] ?? null;
                                            }
                                            ?>
                                            <?php if (!empty($firstImage)): ?>
                                            <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="" class="w-full h-full object-cover">
                                            <?php else: ?>
                                            <i class="fas fa-box text-slate-400 text-sm sm:text-xl"></i>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex-1 min-w-0 hidden [@media(min-width:449px)]:block">
                                            <h3 class="font-medium text-slate-900 text-sm sm:text-base truncate">
                                            <?php echo htmlspecialchars($trade['offered_item_title']); ?>
                                            </h3>
                                            <p class="text-xs sm:text-sm text-slate-600 truncate">
                                            with <?php echo htmlspecialchars($trade['partner_name']); ?>
                                            </p>
                                        </div>

                                        <span class="px-2 py-0.5 sm:px-3 sm:py-1 bg-blue-100 text-blue-700 rounded-lg sm:rounded-xl text-xs whitespace-nowrap">
                                            <?php echo ucfirst($trade['status']); ?>
                                        </span>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>    
<?php include '../includes/footer.php'; ?>
</body>
</html>
