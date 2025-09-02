<?php
require '../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../config/config.php';
$conn = getDBConnection();
ob_start(); // Start output buffering
include "../includes/Name.php";
$site_name = trim(ob_get_clean());
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';
$message = '';
$messageType = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                header("Location: ../Admin/dashboard.php");
            } else {
                header("Location: ../pages/dashboard.php");
            }
            exit();
        } else {
            $message = "Invalid password!";
            $messageType = "error";
        }
    } else {
        $message = "No user found with this email!";
        $messageType = "error";
    }
    $stmt->close();
}

// Handle Signup
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "error";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = "Email already registered!";
            $messageType = "error";
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (full_name, email, password, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                // Create token
                $token = bin2hex(random_bytes(32));

                // Store token in email_confirmations
                $confirm_query = "INSERT INTO email_confirmations (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
                $confirm_stmt = $conn->prepare($confirm_query);
                $confirm_stmt->bind_param("is", $user_id, $token);
                $confirm_stmt->execute();

                // Send verification email
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

                    $mail->setFrom($mail_user, $site_name);
                    $mail->addAddress($email, $username);
                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Email';

                    $verification_link = "http://localhost/TradeHub/auth/verify-email.php?token=$token";

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
                                    font-size: 24px;
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
                                    margin-top: 20px;
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
                                <div class='header'>Verify Your Email</div>
                                <div class='content'>
                                    Hi <strong>$username</strong>,<br><br>
                                    Please verify your email by clicking the button below:<br><br>
                                    <a href='$verification_link' class='button'>Verify Email</a><br><br>
                                    This link will expire in 1 hour.<br><br>
                                    Thanks,<br>
                                    $site_name Team
                                </div>
                                <div class='footer'>
                                    &copy; ".date('Y')." $site_name. All rights reserved.
                                </div>
                            </div>
                        </body>
                        </html>
                        ";
                        
                    $mail->send();

                    $success = 'Account created successfully! Please check your email to verify your account.';
                } catch (Exception $e) {
                    $error = 'Account created, but verification email failed: ' . $mail->ErrorInfo;
                }
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php include "../includes/Name.php";?> - <?php echo ucfirst($mode); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0f9ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(5, 150, 105, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translate(0px, 0px) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        .auth-container {
            width: 100%;
            max-width: 1000px;
            display: flex;
            min-height: 80vh;
            margin: 0 auto;
            padding: 20px;
            perspective: 1000px;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 
                0 32px 64px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            overflow: hidden;
            display: flex;
            min-height: 600px;
            position: relative;
            transform-style: preserve-3d;
            animation: cardEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes cardEntrance {
            0% {
                opacity: 0;
                transform: translateY(50px) rotateX(10deg);
            }
            100% {
                opacity: 1;
                transform: translateY(0) rotateX(0deg);
            }
        }

        .brand-card, .form-card {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .brand-card {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .brand-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .brand-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: brandGlow 8s ease-in-out infinite;
        }

        @keyframes brandGlow {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.1; }
            50% { transform: translate(-20px, -20px) scale(1.1); opacity: 0.2; }
        }

        .brand-content {
            position: relative;
            z-index: 2;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #ffffff, #f0fdf4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: logoShine 3s ease-in-out infinite;
        }

        @keyframes logoShine {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.2); }
        }

        .brand-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
            opacity: 0;
            animation: slideInUp 0.8s ease-out 0.3s forwards;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            opacity: 0;
            animation: slideInUp 0.8s ease-out 0.5s forwards;
        }

        @keyframes slideInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .form-content {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            opacity: 0;
            animation: fadeInDown 0.6s ease-out 0.2s forwards;
        }

        .form-subtitle {
            color: #6b7280;
            font-size: 1rem;
            opacity: 0;
            animation: fadeInDown 0.6s ease-out 0.4s forwards;
        }

        @keyframes fadeInDown {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
            opacity: 0;
            animation: slideInLeft 0.6s ease-out forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.6s; }
        .form-group:nth-child(2) { animation-delay: 0.7s; }
        .form-group:nth-child(3) { animation-delay: 0.8s; }
        .form-group:nth-child(4) { animation-delay: 0.9s; }

        @keyframes slideInLeft {
            0% {
                opacity: 0;
                transform: translateX(-30px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-input:hover {
            border-color: #d1d5db;
            transform: translateY(-1px);
        }

        .btn {
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .mode-switch {
            text-align: center;
            margin-top: 2rem;
            opacity: 0;
            animation: fadeIn 0.6s ease-out 1.2s forwards;
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        .mode-link {
            color: #10b981;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .mode-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 50%;
            background: #10b981;
            transition: all 0.3s ease;
        }

        .mode-link:hover::after {
            width: 100%;
            left: 0;
        }

        .mode-link:hover {
            color: #059669;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: alertSlide 0.5s ease-out;
        }

        @keyframes alertSlide {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Mode-specific layouts */
        .login-mode .brand-card {
            order: 1;
        }

        .login-mode .form-card {
            order: 2;
        }

        .signup-mode .brand-card {
            order: 2;
        }

        .signup-mode .form-card {
            order: 1;
        }

        /* Transition animations */
        .mode-transition {
            animation: modeSwitch 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes modeSwitch {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(0.98); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .auth-card {
                flex-direction: column;
                min-height: auto;
                margin: 10px;
            }

            .brand-card, .form-card {
                padding: 40px 30px;
            }

            .signup-mode .brand-card,
            .login-mode .brand-card {
                order: 1;
            }

            .signup-mode .form-card,
            .login-mode .form-card {
                order: 2;
            }

            .logo {
                font-size: 2rem;
            }

            .brand-title {
                font-size: 1.5rem;
            }

            .form-title {
                font-size: 1.75rem;
            }
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        a.forgot-link:link,
a.forgot-link:visited {
    color: #10B981 !important; /* Emerald 500 */
    text-decoration: none !important;
}
a.forgot-link:hover {
    color: #059669 !important; /* Emerald 600 */
    text-decoration: underline;
}
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="auth-container" >
        <div class="auth-card <?php echo $mode; ?>-mode" id="authCard">
            <div class="brand-card">
                <div class="brand-content">
                    <div class="logo"><?php include "../includes/Name.php";?></div>
                    <h2 class="brand-title">
                        <?php echo $mode === 'signup' ? 'Join Our Community' : 'Welcome Back'; ?>
                    </h2>
                    <p class="brand-subtitle">
                        <?php echo $mode === 'signup' 
                            ? 'Start your trading journey today and connect with traders worldwide.' 
                            : 'Sign in to access your trading dashboard and manage your exchanges.'; ?>
                    </p>
                </div>
            </div>

            <div class="form-card">
                <div class="form-content">
                    <div class="form-header">
                        <h1 class="form-title"><?php echo $mode === 'signup' ? 'Create Account' : 'Sign In'; ?></h1>
                        <p class="form-subtitle">
                            <?php echo $mode === 'signup' 
                                ? 'Fill in your details to get started' 
                                : 'Enter your credentials to continue'; ?>
                        </p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="authForm">
                        <?php if ($mode === 'signup'): ?>
                            <div class="form-group">
                                <label class="form-label" for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-input" required>
                            </div>
                        <?php endif; ?>

<div class="form-group">
    <label class="form-label" for="email">Email Address</label>
    <input type="email" id="email" name="email" class="form-input" required>
</div>

<div class="form-group">
    <label class="form-label" for="password">Password</label>
    <input type="password" id="password" name="password" class="form-input" required>

    <?php if ($mode !== 'signup'): ?>
    <a href="forgot-password.php" 
       class="forgot-link text-sm block text-right mt-2">
       Forgot Password?
    </a>
    <?php endif; ?>

</div>


</div>



                        <?php if ($mode === 'signup'): ?>
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                            </div>
                        <?php endif; ?>

                        <button type="submit" name="<?php echo $mode; ?>" class="btn" id="submitBtn">
                            <?php echo $mode === 'signup' ? 'Create Account' : 'Sign In'; ?>
                        </button>
                    </form>

                    <div class="mode-switch">
                        <?php if ($mode === 'login'): ?>
                            Don't have an account? <a href="" class="mode-link" onclick="switchMode('signup')">Sign up</a>
                        <?php else: ?>
                            Already have an account? <a href="" class="mode-link" onclick="switchMode('login')">Sign in</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchMode(newMode) {
            const authCard = document.getElementById('authCard');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Show loading overlay
            loadingOverlay.style.display = 'flex';
            
            // Add transition class
            authCard.classList.add('mode-transition');
            
            // Update URL without page refresh
            const newUrl = `auth.php?mode=${newMode}`;
            history.pushState({mode: newMode}, '', newUrl);
            
            // Simulate loading and fetch new content
            setTimeout(() => {
                fetch(newUrl)
                    .then(response => response.text())
                    .then(html => {
                        // Parse the response and update content
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContent = doc.querySelector('.auth-card').innerHTML;
                        
                        // Update the card content
                        authCard.innerHTML = newContent;
                        authCard.className = `auth-card ${newMode}-mode`;
                        
                        // Hide loading overlay
                        loadingOverlay.style.display = 'none';
                        
                        // Remove transition class
                        setTimeout(() => {
                            authCard.classList.remove('mode-transition');
                        }, 100);
                        
                        // Re-bind form events
                        bindFormEvents();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loadingOverlay.style.display = 'none';
                        // Fallback to page refresh
                        window.location.href = newUrl;
                    });
            }, 300);
        }

        function bindFormEvents() {
            const form = document.getElementById('authForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    submitBtn.classList.add('loading');
                    submitBtn.textContent = 'Processing...';
                });
            }
        }

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.mode) {
                switchMode(e.state.mode);
            }
        });

        // Initialize form events
        document.addEventListener('DOMContentLoaded', function() {
            bindFormEvents();
            
            // Set initial state
            const currentMode = new URLSearchParams(window.location.search).get('mode') || 'login';
            history.replaceState({mode: currentMode}, '', window.location.href);
        });

        // Add input focus animations
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateX(5px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>
