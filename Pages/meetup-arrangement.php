<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();

// Validate trade_id and method
if (!isset($_GET['trade_id']) || !is_numeric($_GET['trade_id']) || !isset($_GET['method']) || $_GET['method'] !== 'meetup') {
    header("Location: trades.php?error=Invalid trade or method");
    exit();
}

$trade_id = (int)$_GET['trade_id'];

// Verify trade exists and user is the owner
$query = "SELECT t.*, 
          l1.title as offered_item_title,
          l2.title as requested_item_title,
          u1.full_name as requester_name,
          u2.full_name as owner_name,
          u1.id as requester_id
          FROM trades t
          JOIN listings l1 ON t.offered_item_id = l1.id
          JOIN listings l2 ON t.requested_item_id = l2.id
          JOIN users u1 ON t.requester_id = u1.id
          JOIN users u2 ON t.owner_id = u2.id
          WHERE t.id = ? AND t.owner_id = ? AND t.status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $trade_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$trade = $result->fetch_assoc();

if (!$trade) {
    header("Location: trades.php?error=Invalid trade or unauthorized access");
    exit();
}

// Handle meetup arrangement submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $meetup_location = $_POST['meetup_location'] ?? '';
    $meetup_time = $_POST['meetup_time'] ?? '';

    // Basic validation
    $errors = [];
    if (empty($meetup_location)) {
        $errors[] = "Meetup location is required";
    }
    if (empty($meetup_time)) {
        $errors[] = "Meetup time is required";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $meetup_time)) {
        $errors[] = "Invalid meetup time format";
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update trade status and method
            $query = "UPDATE trades SET status = 'accepted', trade_method = 'meetup' WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $trade_id);
            $stmt->execute();

            // Store meetup details
            $meetup_query = "INSERT INTO meetups (trade_id, user_id, location, meetup_time) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($meetup_query);
            $stmt->bind_param("iiss", $trade_id, $_SESSION['user_id'], $meetup_location, $meetup_time);
            $stmt->execute();

            // Send message to requester
            $message = "I have accepted your trade request for {$trade['offered_item_title']} in exchange for {$trade['requested_item_title']}. Let's meet at {$meetup_location} on " . date('F j, Y, g:i A', strtotime($meetup_time)) . " to complete the trade.";
            $message_query = "INSERT INTO messages (sender_id, receiver_id, trade_id, message) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($message_query);
            $stmt->bind_param("iiis", $_SESSION['user_id'], $trade['requester_id'], $trade_id, $message);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            header("Location: trades.php?success=Trade accepted, meetup arranged, and message sent to requester");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to arrange meetup and accept trade: " . $e->getMessage();
        }
    }
}

$page_title = 'Arrange Meetup';
include '../includes/header.php';
?>

<div class="min-h-screen bg-slate-50">
    <header class="bg-white border-b border-slate-200">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <a href="trades.php" class="flex items-center space-x-2 text-slate-600 hover:text-slate-900">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Trades</span>
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
        <div class="max-w-2xl mx-auto bg-white rounded-3xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-slate-900 mb-4">Arrange Meetup</h1>
            <p class="text-slate-600 mb-6">Please provide meetup details for trading <?php echo htmlspecialchars($trade['offered_item_title']); ?> with <?php echo htmlspecialchars($trade['requested_item_title']); ?></p>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Meetup Form -->
            <form method="POST" class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">Meetup Details</h3>
                    <div class="mb-4">
                        <label class="block text-slate-600 text-sm mb-1">Meetup Location</label>
                        <input type="text" name="meetup_location"
                            class="w-full border border-slate-200 rounded-2xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            placeholder="Enter meetup location (e.g., Central Park, NYC)">
                    </div>
                    <div class="mb-4">
                        <label class="block text-slate-600 text-sm mb-1">Meetup Time</label>
                        <input type="datetime-local" name="meetup_time"
                            class="w-full border border-slate-200 rounded-2xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold">
                    Confirm Meetup & Accept Trade
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>