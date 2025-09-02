<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection();

// Get user's credit balance (unchanged)
$query = "SELECT COALESCE(SUM(CASE WHEN type = 'earned' OR type = 'bonus' THEN amount ELSE -amount END), 0) as balance 
          FROM credits WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$balance_row = $result->fetch_assoc();
$balance = $balance_row['balance'] ?? 0;

// Get recent trades
$query = "SELECT t.*, 
                 l1.title AS offered_item_title, 
                 l2.title AS requested_item_title,
                 u1.full_name AS requester_name,
                 u2.full_name AS owner_name
          FROM trades t
          LEFT JOIN listings l1 ON t.offered_item_id = l1.id
          LEFT JOIN listings l2 ON t.requested_item_id = l2.id
          LEFT JOIN users u1 ON t.requester_id = u1.id
          LEFT JOIN users u2 ON t.owner_id = u2.id
          WHERE t.requester_id = ? OR t.owner_id = ?
          ORDER BY t.created_at DESC
          LIMIT 20";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$trades = $result->fetch_all(MYSQLI_ASSOC);

$page_title = 'Credits Wallet';
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
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-wallet text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Credits Wallet</h1>
            <p class="text-slate-600">Manage your trading credits and trade history</p>
        </div>

        <!-- Balance Card -->
        <div class="bg-gradient-to-br from-emerald-500 to-blue-500 rounded-3xl shadow-xl p-8 text-white mb-8 max-w-md mx-auto">
            <div class="text-center">
                <p class="text-emerald-100 mb-2">Current Balance</p>
                <p class="text-4xl font-bold mb-4"><?php echo number_format($balance, 0); ?> Credits</p>
            </div>
        </div>

        <!-- How Credits Work -->
        <div class="bg-white rounded-3xl shadow-lg p-8 mb-8">
            <h2 class="text-xl font-bold text-slate-900 mb-6 text-center">How Credits Work</h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-handshake text-emerald-600"></i>
                    </div>
                    <h3 class="font-semibold text-slate-900 mb-2">Complete Trades</h3>
                    <p class="text-slate-600 text-sm">Earn 10-50 credits for each successful trade based on item value</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-star text-blue-600"></i>
                    </div>
                    <h3 class="font-semibold text-slate-900 mb-2">Get Reviews</h3>
                    <p class="text-slate-600 text-sm">Receive 5 credits for each positive review from trading partners</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shopping-cart text-purple-600"></i>
                    </div>
                    <h3 class="font-semibold text-slate-900 mb-2">Spend Credits</h3>
                    <p class="text-slate-600 text-sm">Use credits to boost listings, unlock premium features, or trade for items</p>
                </div>
            </div>
        </div>

        <!-- Trade History -->
        <div class="bg-white rounded-3xl shadow-lg p-6 sm:p-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h2 class="text-lg sm:text-xl font-bold text-slate-900">Trade History</h2>
        
        <!-- Export Dropdown -->
        <div class="relative">
            <button onclick="toggleExportMenu()" 
                class="flex items-center text-emerald-500 hover:text-emerald-600 text-sm font-medium">
                <i class="fas fa-download mr-1"></i> Export
            </button>
            <div id="exportMenu" 
                class="hidden absolute right-0 mt-2 w-40 bg-white border rounded-xl shadow-lg z-20">
                <button onclick="exportTable('csv')" 
                    class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Export as CSV
                </button>
                <button onclick="exportTable('excel')" 
                    class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Export as Excel
                </button>
            </div>
        </div>
    </div>

    <?php if (empty($trades)): ?>
        <div class="text-center py-10 sm:py-12">
            <div class="w-14 h-14 sm:w-16 sm:h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-receipt text-slate-400 text-xl sm:text-2xl"></i>
            </div>
            <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-2">No trades yet</h3>
            <p class="text-slate-600 mb-6 text-sm sm:text-base">Start trading to see your trade history here</p>
            <a href="marketplace.php" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 sm:px-6 sm:py-3 rounded-2xl font-semibold text-sm sm:text-base">
                Browse Marketplace
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-3 sm:space-y-4">
            <?php foreach ($trades as $trade): ?>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0 p-4 bg-slate-50 rounded-2xl">
                    <div class="flex items-center space-x-3 sm:space-x-4">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center <?php 
                            echo $trade['status'] === 'completed' ? 'bg-emerald-100' : 
                                 ($trade['status'] === 'pending' ? 'bg-yellow-100' : 'bg-red-100'); 
                        ?>">
                            <i class="fas fa-<?php 
                                switch($trade['status']) {
                                    case 'completed': echo 'check text-emerald-600'; break;
                                    case 'pending': echo 'clock text-yellow-600'; break;
                                    default: echo 'times text-red-600'; break;
                                }
                            ?>"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-slate-900 text-sm sm:text-base">
                                Trade: <?php echo htmlspecialchars($trade['offered_item_title']); ?> 
                                for <?php echo htmlspecialchars($trade['requested_item_title']); ?>
                            </h4>
                            <p class="text-slate-500 text-xs sm:text-sm">
                                With <?php echo $trade['requester_id'] == $_SESSION['user_id'] ? 
                                    htmlspecialchars($trade['owner_name']) : htmlspecialchars($trade['requester_name']); ?> 
                                • <?php echo formatTimeAgo($trade['created_at']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="px-2 py-1 rounded-lg text-xs sm:text-sm font-medium <?php 
                            switch($trade['status']) {
                                case 'pending': echo 'bg-yellow-100 text-yellow-700'; break;
                                case 'accepted': echo 'bg-blue-100 text-blue-700'; break;
                                case 'completed': echo 'bg-emerald-100 text-emerald-700'; break;
                                default: echo 'bg-red-100 text-red-700'; break;
                            }
                        ?>">
                            <?php echo ucfirst($trade['status']); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>            
    <?php endif; ?>
</div>

<script>
// Toggle dropdown
function toggleExportMenu() {
    document.getElementById("exportMenu").classList.toggle("hidden");
}

// Export to CSV / Excel
function exportTable(type) {
    const data = <?php echo json_encode($trades); ?>;
    if (!data.length) return alert("No trades to export.");

    if (type === "csv") {
        let csv = "Offered Item,Requested Item,Status,With,Created At\n";
        data.forEach(trade => {
            csv += `"${trade.offered_item_title}","${trade.requested_item_title}","${trade.status}","${trade.requester_id == <?php echo $_SESSION['user_id']; ?> ? trade.owner_name : trade.requester_name}","${trade.created_at}"\n`;
        });

        const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", "trade_history.csv");
        link.click();
    } 
    else if (type === "excel") {
        let table = `<table border='1'>
            <tr><th>Offered Item</th><th>Requested Item</th><th>Status</th><th>With</th><th>Created At</th></tr>`;
        data.forEach(trade => {
            table += `<tr>
                <td>${trade.offered_item_title}</td>
                <td>${trade.requested_item_title}</td>
                <td>${trade.status}</td>
                <td>${trade.requester_id == <?php echo $_SESSION['user_id']; ?> ? trade.owner_name : trade.requester_name}</td>
                <td>${trade.created_at}</td>
            </tr>`;
        });
        table += `</table>`;

        const blob = new Blob([table], { type: "application/vnd.ms-excel" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", "trade_history.xls");
        link.click();
    }

    document.getElementById("exportMenu").classList.add("hidden");
}
</script>
    </div>
</div>

<?php include '../includes/footer.php'; ?>