<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();

// Mark notification as read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_read'])) {
    $notification_id = (int)$_POST['notification_id'];
    $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('ii', $notification_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// Mark all as read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_all_read'])) {
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// Get notifications
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unread count
$query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['unread_count'];
$stmt->close();

$conn->close();

$page_title = 'Notifications';
include '../includes/header.php';
?>

<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center space-x-2 text-slate-600 hover:text-slate-900">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
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
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Notifications</h1>
                <p class="text-slate-600">
                    <?php echo $unread_count; ?> unread notification<?php echo $unread_count !== 1 ? 's' : ''; ?>
                </p>
            </div>
            <?php if ($unread_count > 0): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="mark_all_read" value="1">
                    <button type="submit" class="text-emerald-500 hover:text-emerald-600 text-sm">
                        <i class="fas fa-check-double mr-1"></i>
                        Mark All Read
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Notifications -->
        <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
            <?php if (empty($notifications)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-bell text-slate-400 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-900 mb-2">No notifications yet</h3>
                    <p class="text-slate-600 mb-6">We'll notify you about trade proposals, messages, and other important updates</p>
                    <a href="marketplace.php" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold">
                        Browse Marketplace
                    </a>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-200">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="p-6 hover:bg-slate-50 transition-colors <?php echo !$notification['is_read'] ? 'bg-blue-50' : ''; ?>">
                            <div class="flex items-start space-x-4">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 <?php 
                                    switch($notification['type']) {
                                        case 'trade_proposal': echo 'bg-blue-100'; break;
                                        case 'trade_accepted': echo 'bg-emerald-100'; break;
                                        case 'trade_completed': echo 'bg-green-100'; break;
                                        case 'message': echo 'bg-purple-100'; break;
                                        case 'review': echo 'bg-amber-100'; break;
                                        default: echo 'bg-slate-100'; break;
                                    }
                                ?>">
                                    <i class="fas fa-<?php 
                                        switch($notification['type']) {
                                            case 'trade_proposal': echo 'handshake text-blue-600'; break;
                                            case 'trade_accepted': echo 'check text-emerald-600'; break;
                                            case 'trade_completed': echo 'trophy text-green-600'; break;
                                            case 'message': echo 'comment text-purple-600'; break;
                                            case 'review': echo 'star text-amber-600'; break;
                                            default: echo 'bell text-slate-600'; break;
                                        }
                                    ?>"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="font-semibold text-slate-900 mb-1"><?php echo htmlspecialchars($notification['title']); ?></h4>
                                            <p class="text-slate-600 text-sm mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <p class="text-slate-500 text-xs"><?php echo formatTimeAgo($notification['created_at']); ?></p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <?php if (!$notification['is_read']): ?>
                                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <input type="hidden" name="mark_read" value="1">
                                                    <button type="submit" class="text-slate-400 hover:text-slate-600 p-1">
                                                        <i class="fas fa-check text-xs"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>