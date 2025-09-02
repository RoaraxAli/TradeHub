<?php
require_once '../config/config.php';
require_once 'includes/admin_auth.php';

$conn = getDBConnection(); // Use MySQLi connection from config.php

// Get statistics
$stats = [];

// Total users
$query = "SELECT COUNT(*) as count FROM users WHERE status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_users'] = $result->fetch_assoc()['count'];
$stmt->close();

// Total listings
$query = "SELECT COUNT(*) as count FROM listings WHERE status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_listings'] = $result->fetch_assoc()['count'];
$stmt->close();

// Total trades
$query = "SELECT COUNT(*) as count FROM trades";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_trades'] = $result->fetch_assoc()['count'];
$stmt->close();

// Completed trades
$query = "SELECT COUNT(*) as count FROM trades WHERE status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$stats['completed_trades'] = $result->fetch_assoc()['count'];
$stmt->close();

// Recent users
$query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$recent_users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recent listings
$query = "SELECT l.*, u.full_name as user_name FROM listings l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$recent_listings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recent trades
$query = "SELECT t.*, u1.full_name as requester_name, u2.full_name as owner_name 
          FROM trades t 
          JOIN users u1 ON t.requester_id = u1.id 
          JOIN users u2 ON t.owner_id = u2.id 
          ORDER BY t.created_at DESC LIMIT 4";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$recent_trades = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Admin Dashboard';
include 'includes/header.php';
?>

<div class="p-6 min-h-screen">
    <!-- Enhanced welcome section with modern card design -->
    <div class="mb-8">
        <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Dashboard Overview
                    </h1>
                    <p class="text-gray-600 text-lg">
                        Welcome back, <span class="text-green-600 font-semibold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>! 
                        Here's your platform summary for today.
                    </p>
                </div>
                <div class="hidden md:block">
                    <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern stats cards with enhanced design and green accents -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-100 transition-colors duration-200">
                        <i class="fas fa-users text-green-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_users']); ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Active users</span>
                    </div>
                    <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">+12%</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-100 transition-colors duration-200">
                        <i class="fas fa-list text-green-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Active Listings</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_listings']); ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Live listings</span>
                    </div>
                    <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">+8%</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-100 transition-colors duration-200">
                        <i class="fas fa-handshake text-green-600 text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Trades</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_trades']); ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">All trades</span>
                    </div>
                    <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">+15%</span>
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
                        <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['completed_trades']); ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-gray-600 font-medium">Success rate</span>
                    </div>
                    <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">+22%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced activity sections with modern design -->
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Recent Users -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Recent Users</h2>
                        <p class="text-gray-500 text-sm mt-1">Latest registrations</p>
                    </div>
                    <a href="users.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 hover:shadow-md">
                        View All
                    </a>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach ($recent_users as $user): ?>
                        <div class="flex items-center space-x-4 p-4 rounded-xl hover:bg-gray-50 transition-all duration-200 group">
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center group-hover:bg-green-50 transition-colors duration-200">
                                <i class="fas fa-user text-gray-600 group-hover:text-green-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full font-medium">
                                    <?php echo formatTimeAgo($user['created_at']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Listings -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Recent Listings</h2>
                        <p class="text-gray-500 text-sm mt-1">Latest posts</p>
                    </div>
                    <a href="listings.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 hover:shadow-md">
                        View All
                    </a>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach ($recent_listings as $listing): ?>
                        <div class="flex items-center space-x-4 p-4 rounded-xl hover:bg-gray-50 transition-all duration-200 group">
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center group-hover:bg-green-50 transition-colors duration-200">
                                <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> text-gray-600 group-hover:text-green-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($listing['title']); ?></p>
                                <p class="text-sm text-gray-500">by <?php echo htmlspecialchars($listing['user_name']); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-700 rounded-full">
                                    <?php echo ucfirst($listing['type']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Trades -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Recent Trades</h2>
                        <p class="text-gray-500 text-sm mt-1">Latest activity</p>
                    </div>
                    <a href="trades.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 hover:shadow-md">
                        View All
                    </a>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach ($recent_trades as $trade): ?>
                        <div class="flex items-center space-x-4 p-4 rounded-xl hover:bg-gray-50 transition-all duration-200 group">
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center group-hover:bg-green-50 transition-colors duration-200">
                                <i class="fas fa-exchange-alt text-gray-600 group-hover:text-green-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 text-sm mb-2">
                                    <?php echo htmlspecialchars($trade['requester_name']); ?> ↔ <?php echo htmlspecialchars($trade['owner_name']); ?>
                                </p>
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full <?php 
                                    switch($trade['status']) {
                                        case 'pending': echo 'bg-gray-100 text-gray-700'; break;
                                        case 'accepted': echo 'bg-gray-100 text-gray-700'; break;
                                        case 'completed': echo 'bg-green-100 text-green-700'; break;
                                        case 'declined': echo 'bg-gray-100 text-gray-700'; break;
                                        default: echo 'bg-gray-100 text-gray-700'; break;
                                    }
                                ?>">
                                    <?php echo ucfirst($trade['status']); ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full font-medium">
                                    <?php echo formatTimeAgo($trade['created_at']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
