<?php
require_once '../config/config.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($token)) {
     header("Location: ./forgot-password.php");
}

if (isset($_SESSION['user_id'])) {
    header("Location: ../Pages/dashboard.php");
    exit();
}

// Get MySQLi connection
$conn = getDBConnection();

// Verify token
$query = "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = 'Invalid or expired reset token';
} else {
    $reset_data = $result->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            // Update password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE email = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ss", $hashed_password, $reset_data['email']);

            if ($update_stmt->execute()) {
                // Mark token as used
                $token_query = "UPDATE password_resets SET used = 1 WHERE token = ?";
                $token_stmt = $conn->prepare($token_query);
                $token_stmt->bind_param("s", $token);
                $token_stmt->execute();

                $success = 'Password reset successfully! You can now log in with your new password.';
            } else {
                $error = 'Error updating password. Please try again.';
            }
        }
    }
}

$page_title = 'Reset Password';
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
                <h1 class="text-2xl font-bold text-slate-900">Reset Password</h1>
                <p class="text-slate-600">Enter your new password</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-4 alert-auto-hide">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl mb-4 alert-auto-hide">
                    <?php echo $success; ?>
                    <div class="mt-3">
                        <a href="auth.php" class="text-green-600 hover:text-green-700 font-medium">Go to Login</a>
                    </div>
                </div>
            <?php elseif (!$error || $error !== 'Invalid or expired reset token'): ?>
                <form method="POST" class="space-y-4">
                    <div class="space-y-2">
                        <label for="password" class="text-slate-700 font-medium">New Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-3 text-slate-400"></i>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                placeholder="Enter new password"
                                class="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                required
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="confirm_password" class="text-slate-700 font-medium">Confirm Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-3 text-slate-400"></i>
                            <input
                                id="confirm_password"
                                name="confirm_password"
                                type="password"
                                placeholder="Confirm new password"
                                class="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                required
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-2xl font-semibold transition-colors"
                    >
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center text-sm text-slate-600 mt-6">
                <a href="auth.php" class="text-emerald-500 hover:text-emerald-600 font-medium">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
