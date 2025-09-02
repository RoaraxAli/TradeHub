<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();

$error = '';
$success = '';
$listing_id = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;

// Get the listing details
$listing = null;
if ($listing_id > 0) {
    $query = "SELECT l.*, u.full_name as owner_name, u.avatar_url as owner_avatar 
              FROM listings l 
              JOIN users u ON l.user_id = u.id 
              WHERE l.id = ? AND l.status = 'active' AND l.user_id != ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('ii', $listing_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $listing = $result->fetch_assoc();
    $stmt->close();
    
    if (!$listing) {
        redirect('marketplace.php');
    }
}

// Get user's active listings
$query = "SELECT * FROM listings WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_listings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle trade proposal
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $offered_item_id = (int)$_POST['offered_item_id'];
    $message = sanitize($_POST['message']);
    
    if ($offered_item_id <= 0) {
        $error = 'Please select an item to offer';
    } else {
        // Verify the offered item belongs to the user
        $query = "SELECT id FROM listings WHERE id = ? AND user_id = ? AND status = 'active'";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param('ii', $offered_item_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            // Create the trade proposal
            $query = "INSERT INTO trades (requester_id, owner_id, offered_item_id, requested_item_id, message, created_at) 
                      VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param('iiiis', $_SESSION['user_id'], $listing['user_id'], $offered_item_id, $listing_id, $message);
            if ($stmt->execute()) {
                $success = 'Trade proposal sent successfully!';
                $trade_id = $stmt->insert_id;
            
                // Insert message into messages table
                $msg_query = "INSERT INTO messages (sender_id, receiver_id, trade_id, message, is_read, created_at) 
                              VALUES (?, ?, ?, ?, 0, NOW())";
                $msg_stmt = $conn->prepare($msg_query);
                if ($msg_stmt === false) {
                    die("Prepare failed: " . $conn->error);
                }
                $msg_stmt->bind_param('iiis', $_SESSION['user_id'], $listing['user_id'], $trade_id, $message);
                $msg_stmt->execute();
                $msg_stmt->close();
            } else {
                $error = 'Error sending trade proposal. Please try again.';
            }    
        } else {
            $error = 'Invalid item selected';
            $stmt->close();
        }
    }
}

$conn->close();

$page_title = 'Propose Trade';
include '../includes/header.php';
?>

<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <a href="marketplace.php" class="flex items-center space-x-2 text-slate-600 hover:text-slate-900">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Marketplace</span>
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
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-emerald-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-handshake text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Propose a Trade</h1>
                <p class="text-slate-600">Make an offer for this item</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 text-center">
                    <i class="fas fa-check-circle text-2xl mb-2"></i>
                    <p class="font-semibold"><?php echo $success; ?></p>
                    <div class="mt-4 space-x-3">
                        <a href="trades.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm">
                            View My Trades
                        </a>
                        <a href="marketplace.php" class="border border-green-300 hover:bg-green-50 text-green-700 px-4 py-2 rounded-xl text-sm">
                            Back to Marketplace
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-8">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl shadow-lg p-8">
                <!-- Item They Want -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Item You Want</h2>
                    <div class="border border-slate-200 rounded-2xl p-6">
                        <div class="flex items-start space-x-4">
                            <div class="w-20 h-20 bg-slate-200 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <?php
if (!empty($listing['image_url'])) {
    $images = array_map('trim', explode(',', $listing['image_url']));
    $firstImage = $images[0] ?? null;

    if ($firstImage) {
        ?>
        <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="" class="w-full h-full rounded-2xl object-cover">
        <?php
    } else {
        ?>
        <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> text-slate-400 text-2xl"></i>
        <?php
    }
} else {
    ?>
    <i class="fas fa-<?php echo $listing['type'] === 'product' ? 'box' : 'tools'; ?> text-slate-400 text-2xl"></i>
    <?php
}
?>

                            </div>
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h3 class="text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($listing['title']); ?></h3>
                                    <span class="px-3 py-1 rounded-xl text-xs font-medium <?php echo $listing['type'] === 'product' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>">
                                        <?php echo ucfirst($listing['type']); ?>
                                    </span>
                                </div>
                                <p class="text-slate-600 mb-3"><?php echo htmlspecialchars($listing['description']); ?></p>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center">
                                        <?php if ($listing['owner_avatar']): ?>
                                            <img src="<?php echo htmlspecialchars($listing['owner_avatar']); ?>" alt="" class="w-full h-full rounded-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-user text-slate-600 text-sm"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span class="font-medium text-slate-900"><?php echo htmlspecialchars($listing['owner_name']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trade Proposal Form -->
                <form method="POST" class="space-y-6">
                    <!-- Select Item to Offer -->
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 mb-4">What Will You Offer?</h2>
                        
                        <?php if (empty($user_listings)): ?>
                            <div class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center">
                                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-plus text-slate-400"></i>
                                </div>
                                <h3 class="font-semibold text-slate-900 mb-2">No items to offer</h3>
                                <p class="text-slate-600 mb-4">You need to create a listing before you can propose trades</p>
                                <a href="create-listing.php" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold">
                                    Create Listing
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="grid md:grid-cols-2 gap-4">
                                <?php foreach ($user_listings as $item): ?>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="offered_item_id" value="<?php echo $item['id']; ?>" class="sr-only peer" required>
                                        <div class="border-2 border-slate-200 rounded-2xl p-4 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:border-slate-300 transition-colors">
                                            <div class="flex items-start space-x-3">
                                                <div class="w-16 h-16 bg-slate-200 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <?php
                                                if (!empty($item['image_url'])) {
                                                    $images = array_map('trim', explode(',', $item['image_url']));
                                                    $firstImage = $images[0] ?? null;

                                                    if ($firstImage) {
                                                        ?>
                                                        <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="" class="w-full h-full rounded-xl object-cover">
                                                        <?php
                                                    } else {
                                                        ?>
                                                        <i class="fas fa-<?php echo $item['type'] === 'product' ? 'box' : 'tools'; ?> text-slate-400"></i>
                                                        <?php
                                                    }
                                                } else {
                                                    ?>
                                                    <i class="fas fa-<?php echo $item['type'] === 'product' ? 'box' : 'tools'; ?> text-slate-400"></i>
                                                    <?php
                                                }
                                                ?>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="font-semibold text-slate-900 mb-1"><?php echo htmlspecialchars($item['title']); ?></h4>
                                                    <p class="text-slate-600 text-sm line-clamp-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                                    <span class="inline-block mt-2 px-2 py-1 rounded-lg text-xs font-medium <?php echo $item['type'] === 'product' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>">
                                                        <?php echo ucfirst($item['type']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Message -->
                    <?php if (!empty($user_listings)): ?>
                        <div>
                            <label for="message" class="block text-slate-700 font-medium mb-2">Personal Message</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="4"
                                required
                                placeholder="Add a personal message to explain why this trade would be beneficial for both parties..."
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                            ><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button
                                type="submit"
                                class="w-full bg-emerald-500 hover:bg-emerald-600 text-white py-4 rounded-2xl font-semibold text-lg transition-colors"
                            >
                                Send Trade Proposal
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>