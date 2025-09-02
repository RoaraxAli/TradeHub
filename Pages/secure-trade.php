<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();

// Validate trade_id and method
if (!isset($_GET['trade_id']) || !is_numeric($_GET['trade_id']) || !isset($_GET['method']) || $_GET['method'] !== 'secure_location') {
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

// Handle secure trade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $card_number = $_POST['card_number'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    // Basic validation
    $errors = [];
    if ($payment_method === 'card') {
        if (empty($card_number) || empty($expiry_date) || empty($cvv)) {
            $errors[] = "All card fields are required";
        } elseif (!preg_match('/^\d{4}\s\d{4}\s\d{4}\s\d{4}$/', $card_number)) {
            $errors[] = "Invalid card number format";
        } elseif (!preg_match('/^\d{2}\/\d{2}$/', $expiry_date)) {
            $errors[] = "Invalid expiry date format";
        } elseif (!preg_match('/^\d{3}$/', $cvv)) {
            $errors[] = "Invalid CVV";
        }
    } elseif ($payment_method !== 'cod') {
        $errors[] = "Invalid payment method";
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update trade status and method
            $query = "UPDATE trades SET status = 'accepted', trade_method = 'secure_location' WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $trade_id);
            $stmt->execute();

            // Store payment details
            $payment_query = "INSERT INTO payments (trade_id, user_id, amount, method, status) VALUES (?, ?, ?, ?, 'completed')";
            $stmt = $conn->prepare($payment_query);
            $amount = 10.00; // $10 security fee
            $stmt->bind_param("iids", $trade_id, $_SESSION['user_id'], $amount, $payment_method);
            $stmt->execute();

            // Send message to requester
            $message = "I have accepted your trade request for {$trade['offered_item_title']} in exchange for {$trade['requested_item_title']}. The trade method is Secure Trade Location, paid via " . ($payment_method === 'card' ? 'Credit/Debit Card' : 'Cash on Delivery') . ". Please bring your item to our secure facility to complete the trade.";
            $message_query = "INSERT INTO messages (sender_id, receiver_id, trade_id, message) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($message_query);
            $stmt->bind_param("iiis", $_SESSION['user_id'], $trade['requester_id'], $trade_id, $message);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            header("Location: trades.php?success=Trade accepted, security fee processed, and message sent to requester");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to process security fee and accept trade: " . $e->getMessage();
        }
    }
}

$page_title = 'Secure Trade';
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
            <h1 class="text-2xl font-bold text-slate-900 mb-4">Secure Trade Location</h1>
            <p class="text-slate-600 mb-6">Please provide payment details for trading <?php echo htmlspecialchars($trade['offered_item_title']); ?> with <?php echo htmlspecialchars($trade['requested_item_title']); ?> at our secure facility.</p>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Payment Summary -->
            <div class="bg-slate-50 rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Payment Summary</h3>
                <div class="flex justify-between mb-2">
                    <span class="text-slate-600">Security Fee</span>
                    <span class="text-slate-900">$10.00</span>
                </div>
                <div class="border-t border-slate-200 pt-2 mt-2 flex justify-between">
                    <span class="text-slate-900 font-semibold">Total</span>
                    <span class="text-slate-900 font-semibold">$10.00</span>
                </div>
            </div>

            <!-- Refund Policy -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 mb-6">
                <p class="text-yellow-700 text-sm">
                    Important: If the product you are trading is not as described, the money will not be refunded, but the product will be returned to you.
                </p>
            </div>

            <!-- Payment Form -->
            <form method="POST" class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">Payment Method</h3>
                    <div class="flex space-x-4 mb-4">
                        <label class="flex items-center space-x-2">
                            <input type="radio" name="payment_method" value="card" class="form-radio text-emerald-500" checked>
                            <span class="text-slate-600">Credit/Debit Card</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="radio" name="payment_method" value="cod" class="form-radio text-emerald-500">
                            <span class="text-slate-600">Cash on Delivery</span>
                        </label>
                    </div>

                    <div id="card-details">
                        <!-- Card Number -->
                        <div class="mb-4">
                            <label class="block text-slate-600 text-sm mb-1">Card Number</label>
                            <input type="text" name="card_number"
                                id="card_number"
                                maxlength="19"
                                class="w-full border border-slate-200 rounded-2xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                placeholder="1234 5678 9012 3456"
                                inputmode="numeric">
                        </div>

                        <!-- Expiry Date and CVV -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-slate-600 text-sm mb-1">Expiry Date</label>
                                <input type="text" name="expiry_date"
                                    id="expiry_date"
                                    maxlength="5"
                                    class="w-full border border-slate-200 rounded-2xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="MM/YY"
                                    inputmode="numeric">
                            </div>
                            <div>
                                <label class="block text-slate-600 text-sm mb-1">CVV</label>
                                <input type="text" name="cvv"
                                    id="cvv"
                                    maxlength="3"
                                    class="w-full border border-slate-200 rounded-2xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="123"
                                    inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold">
                    Confirm Payment & Accept Trade
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format card number
    document.getElementById('card_number').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.slice(0, 16);
        const formatted = value.match(/.{1,4}/g)?.join(' ') || '';
        e.target.value = formatted;
    });

    // Format expiry date
    document.getElementById('expiry_date').addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        if (value.length >= 2) {
            value = value.slice(0, 2) + '/' + value.slice(2, 4);
        }
        e.target.value = value.slice(0, 5);
    });

    // Toggle card details visibility
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('card-details').style.display = this.value === 'card' ? 'block' : 'none';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>