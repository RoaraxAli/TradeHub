<?php
require_once '../config/config.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($token)) {
    $error = 'Invalid verification link';
} else {
    $conn = getDBConnection();

    // Step 1: Check if token exists and not expired in email_confirmations
    $query = "SELECT ec.user_id, u.email_verified 
              FROM email_confirmations ec 
              JOIN users u ON ec.user_id = u.id 
              WHERE ec.token = ? AND ec.expires_at > NOW() AND u.email_verified = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();

        // Step 2: Update user table to mark email verified
        $update_query = "UPDATE users SET email_verified = 1 WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $data['user_id']);

        if ($update_stmt->execute()) {
            // Step 3: Delete used token from email_confirmations
            $delete_query = "DELETE FROM email_confirmations WHERE token = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("s", $token);
            $delete_stmt->execute();

            $success = 'Email verified successfully! You can now log in to your account.';
        } else {
            $error = 'Error verifying email. Please try again.';
        }
    } else {
        $error = 'Invalid or expired verification link';
    }
}

$page_title = 'Verify Email';
include '../includes/header.php';
?>


<div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-flex items-center space-x-2">
                <div class="w-10 h-10 bg-emerald-500 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-sync-alt text-white"></i>
                </div>
                <span class="text-2xl font-bold text-slate-900"><?php include "../includes/Name.php";?></span>
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-xl p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center <?php echo $success ? 'bg-green-100' : 'bg-red-100'; ?>">
                    <i class="fas fa-<?php echo $success ? 'check' : 'times'; ?> text-2xl <?php echo $success ? 'text-green-600' : 'text-red-600'; ?>"></i>
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Email Verification</h1>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-4 text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl mb-4 text-center">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="text-center space-y-4">
                <a href="login.php" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-2xl font-semibold transition-colors inline-block">
                    Go to Login
                </a>
                <a href="../index.php" class="text-emerald-500 hover:text-emerald-600 font-medium">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
