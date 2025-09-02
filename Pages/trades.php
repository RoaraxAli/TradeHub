<?php include '../includes/maintenance.php'?>
<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();

// Handle trade actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['trade_id'])) {
        $trade_id = (int)$_POST['trade_id'];
        $action = $_POST['action'];

        // Verify user is involved in the trade
        $query = "SELECT * FROM trades WHERE id = ? AND (requester_id = ? OR owner_id = ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $trade_id, $_SESSION['user_id'], $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $trade = $result->fetch_assoc();

        if ($trade) {
            if ($action === 'accept' && $trade['owner_id'] == $_SESSION['user_id']) {
                // Store trade_id in session for modal processing
                $_SESSION['pending_trade_id'] = $trade_id;
            } elseif ($action === 'confirm_trade_method' && isset($_POST['trade_method'])) {
                $trade_method = $_POST['trade_method'];
                // Clear pending trade_id
                unset($_SESSION['pending_trade_id']);
                // Redirect based on trade method without accepting trade
                if ($trade_method === 'company_inspection') {
                    header("Location: payment.php?trade_id=$trade_id&method=company_inspection");
                    exit();
                } elseif ($trade_method === 'meetup') {
                    header("Location: meetup-arrangement.php?trade_id=$trade_id&method=meetup");
                    exit();
                } elseif ($trade_method === 'secure_location') {
                    header("Location: secure-trade.php?trade_id=$trade_id&method=secure_location");
                    exit();
                } else {
                    header("Location: trades.php?error=Invalid trade method");
                    exit();
                }
            } elseif ($action === 'decline' && $trade['owner_id'] == $_SESSION['user_id']) {
                $query = "UPDATE trades SET status = 'declined' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $trade_id);
                $stmt->execute();
            } elseif ($action === 'complete') {
                // Store trade_id in session for review modal
                $_SESSION['pending_complete_trade_id'] = $trade_id;
            } elseif ($action === 'submit_review' && isset($_POST['rating']) && isset($_POST['comment'])) {
                $conn->begin_transaction();
                try {
                    // Update trade status to completed
                    $query = "UPDATE trades SET status = 'completed' WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $trade_id);
                    $stmt->execute();

                    // Update offered and requested items to inactive in listings table
                    $query = "UPDATE listings SET status = 'inactive' WHERE id IN (?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ii", $trade['offered_item_id'], $trade['requested_item_id']);
                    $stmt->execute();

                    // Insert review
                    $rating = (int)$_POST['rating'];
                    $comment = trim($_POST['comment']);
                    $reviewed_user_id = ($trade['requester_id'] == $_SESSION['user_id']) ? $trade['owner_id'] : $trade['requester_id'];
                    
                    $query = "INSERT INTO reviews (reviewer_id, reviewed_user_id, trade_id, rating, comment, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("iiiss", $_SESSION['user_id'], $reviewed_user_id, $trade_id, $rating, $comment);
                    $stmt->execute();

                    // Clear pending trade_id
                    unset($_SESSION['pending_complete_trade_id']);
                    
                    // Commit the transaction
                    $conn->commit();
                } catch (Exception $e) {
                    // Rollback on error
                    $conn->rollback();
                    header("Location: trades.php?error=Failed to complete trade or submit review");
                    exit();
                }
            } elseif ($action === 'cancel') {
                $query = "UPDATE trades SET status = 'cancelled' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $trade_id);
                $stmt->execute();
            }
        } else {
            header("Location: trades.php?error=Invalid trade or unauthorized action");
            exit();
        }
    }
}

// Get user's trades
$query = "SELECT t.*, 
          l1.title as offered_item_title, l1.image_url as offered_item_image,
          l2.title as requested_item_title, l2.image_url as requested_item_image,
          u1.full_name as requester_name, u1.avatar_url as requester_avatar,
          u2.full_name as owner_name, u2.avatar_url as owner_avatar
          FROM trades t
          JOIN listings l1 ON t.offered_item_id = l1.id
          JOIN listings l2 ON t.requested_item_id = l2.id
          JOIN users u1 ON t.requester_id = u1.id
          JOIN users u2 ON t.owner_id = u2.id
          WHERE t.requester_id = ? OR t.owner_id = ?
          ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$trades = $result->fetch_all(MYSQLI_ASSOC);

$page_title = 'My Trades';
include '../includes/header.php';
?>

<!-- Ensure Tailwind CSS and Font Awesome are included -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="min-h-screen bg-slate-50">
    <div class="md:p-9">
        <div class="max-w-full mx-auto h-[100vh] md:h-[calc(95vh-3rem)]">
            <div class="flex h-full bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl border border-white/50 overflow-hidden animate-scale-in">
                <?php include '../includes/sidebar.php'; ?>

                <div class="flex-1 h-full overflow-y-auto">
    <div class="flex-1 flex flex-col">
    <?php include '../includes/head.php'; ?>
    <div class="p-8 sm:px-6 py-6 sm:py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">My Trades</h1>
            <p class="text-slate-600">Track your trading activity and manage proposals</p>
        </div>

        <!-- Error Message -->
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-6">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-3xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm">Total Trades</p>
                        <p class="text-2xl font-bold text-slate-900"><?php echo count($trades); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-handshake text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm">Pending</p>
                        <p class="text-2xl font-bold text-slate-900">
                            <?php echo count(array_filter($trades, function($t) { return $t['status'] === 'pending'; })); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-clock text-amber-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm">Completed</p>
                        <p class="text-2xl font-bold text-slate-900">
                            <?php echo count(array_filter($trades, function($t) { return $t['status'] === 'completed'; })); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-check text-emerald-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-600 text-sm">Success Rate</p>
                        <p class="text-2xl font-bold text-slate-900">
                            <?php 
                            $completed = count(array_filter($trades, function($t) { return $t['status'] === 'completed'; }));
                            $total = count($trades);
                            echo $total > 0 ? round(($completed / $total) * 100) : 0;
                            ?>%
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trade Method Modal -->
        <div id="tradeMethodModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-3xl p-6 w-full max-w-md">
                <h2 class="text-xl font-bold text-slate-900 mb-4">Choose Trade Method</h2>
                <form method="POST" id="tradeMethodForm">
                    <input type="hidden" name="trade_id" value="<?php echo isset($_SESSION['pending_trade_id']) ? $_SESSION['pending_trade_id'] : ''; ?>">
                    <input type="hidden" name="action" value="confirm_trade_method">
                    <div class="space-y-4">
                        <div class="border border-slate-200 p-4 rounded-2xl">
                            <label class="flex items-center space-x-3">
                                <input type="radio" name="trade_method" value="meetup" class="form-radio text-emerald-500">
                                <div>
                                    <p class="font-medium text-slate-900">Arrange Meetup</p>
                                    <p class="text-sm text-slate-600">Meet in person to exchange items</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="location.reload()" class="border border-slate-200 hover:bg-slate-50 px-4 py-2 rounded-2xl text-sm">Cancel</button>
                        <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-2xl text-sm">Confirm</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Review Modal -->
        <div id="reviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-3xl p-6 w-full max-w-md">
                <h2 class="text-xl font-bold text-slate-900 mb-4">Review Trade Partner</h2>
                <form method="POST" id="reviewForm">
                    <input type="hidden" name="trade_id" value="<?php echo isset($_SESSION['pending_complete_trade_id']) ? $_SESSION['pending_complete_trade_id'] : ''; ?>">
                    <input type="hidden" name="action" value="submit_review">
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Rating</label>
                            <div class="flex space-x-1 mt-2 star-rating">
                                <i class="fas fa-star text-gray-300 cursor-pointer" data-rating="1"></i>
                                <i class="fas fa-star text-gray-300 cursor-pointer" data-rating="2"></i>
                                <i class="fas fa-star text-gray-300 cursor-pointer" data-rating="3"></i>
                                <i class="fas fa-star text-gray-300 cursor-pointer" data-rating="4"></i>
                                <i class="fas fa-star text-gray-300 cursor-pointer" data-rating="5"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Comment</label>
                            <textarea name="comment" class="w-full mt-2 p-2 border border-slate-200 rounded-2xl" rows="4" placeholder="Share your experience..." required></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="location.reload()" class="border border-slate-200 hover:bg-slate-50 px-4 py-2 rounded-2xl text-sm">Cancel</button>
                        <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-2xl text-sm">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Trades List -->
        <?php if (empty($trades)): ?>
            <div class="bg-white rounded-3xl shadow-lg p-12 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-handshake text-slate-400 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-slate-900 mb-2">No trades yet</h3>
                <p class="text-slate-600 mb-6">Start browsing the marketplace to find items you'd like to trade for</p>
                <a href="marketplace.php" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold">
                    Browse Marketplace
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($trades as $trade): ?>
                    <?php 
                    $is_requester = $trade['requester_id'] == $_SESSION['user_id'];
                    $partner_name = $is_requester ? $trade['owner_name'] : $trade['requester_name'];
                    $partner_avatar = $is_requester ? $trade['owner_avatar'] : $trade['requester_avatar'];
                    ?>
                    <div class="bg-white rounded-3xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center">
                                    <?php if ($partner_avatar): ?>
                                        <img src="<?php echo htmlspecialchars($partner_avatar); ?>" alt="" class="w-full h-full rounded-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-user text-slate-600"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">
                                        Trade with <?php echo htmlspecialchars($partner_name); ?>
                                    </h3>
                                    <p class="text-slate-600 text-sm">
                                        <?php echo $is_requester ? 'You proposed this trade' : 'Proposed to you'; ?> • 
                                        <?php echo formatTimeAgo($trade['created_at']); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-xl text-xs font-medium <?php 
                                switch($trade['status']) {
                                    case 'pending': echo 'bg-amber-100 text-amber-700'; break;
                                    case 'accepted': echo 'bg-blue-100 text-blue-700'; break;
                                    case 'completed': echo 'bg-emerald-100 text-emerald-700'; break;
                                    case 'declined': echo 'bg-red-100 text-red-700'; break;
                                    case 'cancelled': echo 'bg-slate-100 text-slate-700'; break;
                                }
                            ?>">
                                <?php echo ucfirst($trade['status']); ?>
                            </span>
                        </div>

                        <!-- Trade Items -->
                        <div class="grid md:grid-cols-3 gap-6 mb-6">
                            <!-- Offered Item -->
                            <div class="text-center">
                                <div class="w-20 h-20 bg-slate-200 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <?php
                                if (!empty($trade['offered_item_image'])) {
                                    $images = array_map('trim', explode(',', $trade['offered_item_image']));
                                    $firstImage = $images[0] ?? null;

                                    if ($firstImage) {
                                        ?>
                                        <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="" class="w-full h-full rounded-2xl object-cover">
                                        <?php
                                    } else {
                                        ?>
                                        <i class="fas fa-box text-slate-400 text-2xl"></i>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <i class="fas fa-box text-slate-400 text-2xl"></i>
                                    <?php
                                }
                                ?>
                                </div>
                                <h4 class="font-medium text-slate-900 text-sm"><?php echo htmlspecialchars($trade['offered_item_title']); ?></h4>
                                <p class="text-slate-500 text-xs">
                                    <?php echo $is_requester ? 'Your item' : 'Their offer'; ?>
                                </p>
                            </div>

                            <!-- Exchange Arrow -->
                            <div class="flex items-center justify-center">
                                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-exchange-alt text-emerald-600"></i>
                                </div>
                            </div>

                            <!-- Requested Item -->
                            <div class="text-center">
                                <div class="w-20 h-20 bg-slate-200 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <?php
                                if (!empty($trade['requested_item_image'])) {
                                    $images = array_map('trim', explode(',', $trade['requested_item_image']));
                                    $firstImage = $images[0] ?? null;

                                    if ($firstImage) {
                                        ?>
                                        <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="" class="w-full h-full rounded-2xl object-cover">
                                        <?php
                                    } else {
                                        ?>
                                        <i class="fas fa-box text-slate-400 text-2xl"></i>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <i class="fas fa-box text-slate-400 text-2xl"></i>
                                    <?php
                                }
                                ?>
                                </div>
                                <h4 class="font-medium text-slate-900 text-sm"><?php echo htmlspecialchars($trade['requested_item_title']); ?></h4>
                                <p class="text-slate-500 text-xs">
                                    <?php echo $is_requester ? 'What you want' : 'Your item'; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Message -->
                        <?php if ($trade['message']): ?>
                            <div class="bg-slate-50 rounded-2xl p-4 mb-4">
                                <p class="text-slate-700 text-sm"><?php echo htmlspecialchars($trade['message']); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-3">
                                <a href="messages.php?user=<?php echo $is_requester ? $trade['owner_id'] : $trade['requester_id']; ?>" 
                                   class="border border-slate-200 hover:bg-slate-50 px-4 py-2 rounded-2xl text-sm">
                                    <i class="fas fa-comment mr-2"></i>
                                    Message
                                </a>
                                <a href="trade-details.php?id=<?php echo $trade['id']; ?>" 
                                   class="border border-slate-200 hover:bg-slate-50 px-4 py-2 rounded-2xl text-sm">
                                    <i class="fas fa-eye mr-2"></i>
                                    View Details
                                </a>
                            </div>

                            <?php if ($trade['status'] === 'pending'): ?>
                                <div class="flex space-x-2">
                                    <?php if (!$is_requester): ?>
                                        <!-- Owner can accept/decline -->
                                        <form method="POST" class="inline accept-form" data-trade-id="<?php echo $trade['id']; ?>">
                                            <input type="hidden" name="trade_id" value="<?php echo $trade['id']; ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-2xl text-sm">
                                                Accept
                                            </button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="trade_id" value="<?php echo $trade['id']; ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <button type="submit" class="border border-red-200 hover:bg-red-50 text-red-600 px-4 py-2 rounded-2xl text-sm">
                                                Decline
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <!-- Requester can cancel -->
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="trade_id" value="<?php echo $trade['id']; ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="border border-slate-200 hover:bg-slate-50 text-slate-600 px-4 py-2 rounded-2xl text-sm">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($trade['status'] === 'accepted'): ?>
                                <form method="POST" class="inline complete-form" data-trade-id="<?php echo $trade['id']; ?>">
                                    <input type="hidden" name="trade_id" value="<?php echo $trade['id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-2xl text-sm">
                                        Mark Complete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.star-rating .fa-star {
    font-size: 1.5rem;
    transition: color 0.2s;
}
.star-rating .fa-star.selected {
    color: #facc15; /* Golden yellow color */
}
.star-rating .fa-star:hover {
    color: #facc15; /* Golden yellow on hover */
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle accept form submission
    document.querySelectorAll('.accept-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const tradeId = form.querySelector('input[name="trade_id"]').value;
            const tradeMethodModal = document.getElementById('tradeMethodModal');
            if (tradeMethodModal) {
                document.querySelector('#tradeMethodForm input[name="trade_id"]').value = tradeId;
                tradeMethodModal.classList.remove('hidden');
            } else {
                console.error('Trade Method Modal not found');
            }
        });
    });

    // Handle complete form submission
    document.querySelectorAll('.complete-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const tradeId = form.querySelector('input[name="trade_id"]').value;
            const reviewModal = document.getElementById('reviewModal');
            if (reviewModal) {
                document.querySelector('#reviewForm input[name="trade_id"]').value = tradeId;
                reviewModal.classList.remove('hidden');
            } else {
                console.error('Review Modal not found');
            }
        });
    });

    // Handle trade method form submission
    document.getElementById('tradeMethodForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const form = this;
        const formData = new FormData(form);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                window.location.href = 'trades.php?error=Failed to process trade method';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'trades.php?error=Network error occurred';
        });
    });

    // Handle review form submission
    document.getElementById('reviewForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const form = this;
        const formData = new FormData(form);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                window.location.href = 'trades.php';
            } else {
                window.location.href = 'trades.php?error=Failed to submit review';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'trades.php?error=Network error occurred';
        });
    });

    // Handle star rating selection
    document.querySelectorAll('.star-rating .fa-star').forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            document.getElementById('ratingInput').value = rating;
            document.querySelectorAll('.star-rating .fa-star').forEach(s => {
                const starRating = parseInt(s.getAttribute('data-rating'));
                s.classList.toggle('selected', starRating <= rating);
            });
        });
    });
});

</script>

<?php include '../includes/footer.php'; ?>