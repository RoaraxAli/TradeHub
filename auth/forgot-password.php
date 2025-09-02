<?php
require_once '../config/config.php';
require '../vendor/autoload.php';

ob_start(); // Start output buffering
include "../includes/Name.php";
$site_name = trim(ob_get_clean()); // Get buffer contents and clean

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['user_id'])) {
    header("Location: ../Pages/dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);

    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        $conn = getDBConnection();

        $query = "SELECT id FROM users WHERE email = ? AND status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $token = bin2hex(random_bytes(32));

            $insert_query = "INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ss", $email, $token);

            if ($insert_stmt->execute()) {
                // Send reset email using PHPMailer
                $mail = new PHPMailer(true);

                try {
                    include "../includes/mailingdata.php";
                    $mail = new PHPMailer(true); // <-- important!
                    $mail->isSMTP();
                    $mail->Host = $mail_host; // SMTP server
                    $mail->SMTPAuth = true;
                    $mail->Username = $mail_user; // Your Gmail address
                    $mail->Password = $mail_pass; // Gmail app password
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = $mail_port;

                    // Recipients
                    $mail->setFrom($mail_user, $site_name);
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset your '. $site_name .' password';
                    $resetLink = 'http://localhost/TradeHub/auth/reset-password.php?token=' . urlencode($token);
                    $mail->Body = "
<html>
<head>
    <style>
        body {
            background-color: #ffffff;
            font-family: Arial, sans-serif;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
        }
        .header {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #2ecc71; /* green */
            margin-bottom: 20px;
        }
        .content {
            font-size: 16px;
            line-height: 1.5;
        }
        .button {
            display: inline-block;
            background-color: #2ecc71; /* green */
            color: #ffffff;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #999999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>Reset Your Password</div>
        <div class='content'>
            <p>Hi,</p>
            <p>Click the button below to reset your password:</p>
            <p><a href='$resetLink' class='button'>Reset Password</a></p>
            <p>If you didn’t request this, just ignore this email.</p>
            <p>Thanks,<br>$site_name Team</p>
        </div>
        <div class='footer'>
            &copy; ".date('Y')." $site_name. All rights reserved.
        </div>
    </div>
</body>
</html>
";


                    $mail->send();
                    $success = 'Password reset instructions have been sent to your email address.';
                } catch (Exception $e) {
                    $error = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
                }
            } else {
                $error = 'Error processing request. Please try again.';
            }
        } else {
            $success = 'If an account with that email exists, password reset instructions have been sent.';
        }
    }
}

$page_title = 'Forgot Password';
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
                <span class="text-2xl font-bold text-slate-900"><?php echo $site_name;?></span>
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-xl p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-slate-900">Forgot Password</h1>
                <p class="text-slate-600">Enter your email to reset your password</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-4 alert-auto-hide">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl mb-4 alert-auto-hide">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="space-y-2">
                    <label for="email" class="text-slate-700 font-medium">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-3 text-slate-400"></i>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            placeholder="Enter your email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            class="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                            required
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-2xl font-semibold transition-colors"
                >
                    Send Reset Instructions
                </button>
            </form>

            <div class="text-center text-sm text-slate-600 mt-6">
                Remember your password?
                <a href="auth.php" class="text-emerald-500 hover:text-emerald-600 font-medium">Sign in</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
