<?php
require_once '../config/config.php';
requireLogin();

$conn = getDBConnection(); // mysqli connection

$error = '';
$success = '';

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $location = sanitize($_POST['location']);
        $bio = sanitize($_POST['bio']);

        if (empty($full_name) || empty($email)) {
            $error = 'Name and email are required';
        } else {
            // Check if email is already taken
            $query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $email, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = 'Email is already taken';
            } else {
                $query = "UPDATE users SET full_name = ?, email = ?, phone = ?, location = ?, bio = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssssi", $full_name, $email, $phone, $location, $bio, $_SESSION['user_id']);

                if ($stmt->execute()) {
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['user_email'] = $email;
                    $success = 'Profile updated successfully';

                    $user['full_name'] = $full_name;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
                    $user['location'] = $location;
                    $user['bio'] = $bio;
                } else {
                    $error = 'Error updating profile';
                }
            }
        }

    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);

            if ($stmt->execute()) {
                $success = 'Password changed successfully';
            } else {
                $error = 'Error changing password';
            }
        }
    }
}

$page_title = 'Settings';
include '../includes/header.php';
?>


<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
        <a href="javascript:void(0);"
   onclick="history.go(-2);"
   class="flex items-center space-x-2 text-slate-600 hover:text-slate-900">
    <i class="fas fa-arrow-left"></i>
    <span>Back</span>
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
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Settings</h1>
                <p class="text-slate-600">Manage your account preferences and security</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl mb-6">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Settings Navigation -->
                <div class="space-y-2">
                    <button onclick="showSection('profile')" id="profile-tab" class="w-full text-left px-4 py-3 rounded-2xl bg-emerald-50 text-emerald-700 font-medium">
                        <i class="fas fa-user mr-3"></i>
                        Profile Information
                    </button>
                    <button onclick="showSection('security')" id="security-tab" class="w-full text-left px-4 py-3 rounded-2xl hover:bg-slate-50 text-slate-700">
                        <i class="fas fa-lock mr-3"></i>
                        Security
                    </button>
                    <button onclick="showSection('notifications')" id="notifications-tab" class="w-full text-left px-4 py-3 rounded-2xl hover:bg-slate-50 text-slate-700">
                        <i class="fas fa-bell mr-3"></i>
                        Notifications
                    </button>
                    <button onclick="showSection('privacy')" id="privacy-tab" class="w-full text-left px-4 py-3 rounded-2xl hover:bg-slate-50 text-slate-700">
                        <i class="fas fa-shield-alt mr-3"></i>
                        Privacy
                    </button>
                </div>

                <!-- Settings Content -->
                <div class="md:col-span-2">
                    <!-- Profile Section -->
                    <div id="profile-section" class="bg-white rounded-3xl shadow-lg p-8">
                        <h2 class="text-xl font-bold text-slate-900 mb-6">Profile Information</h2>
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="full_name" class="block text-slate-700 font-medium mb-2">Full Name *</label>
                                    <input
                                        id="full_name"
                                        name="full_name"
                                        type="text"
                                        value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                        required
                                    />
                                </div>
                                <div>
                                    <label for="email" class="block text-slate-700 font-medium mb-2">Email *</label>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="<?php echo htmlspecialchars($user['email']); ?>"
                                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="phone" class="block text-slate-700 font-medium mb-2">Phone</label>
                                    <input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                    />
                                </div>
                                <div>
                                    <label for="location" class="block text-slate-700 font-medium mb-2">Location</label>
                                    <input
                                        id="location"
                                        name="location"
                                        type="text"
                                        value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>"
                                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="bio" class="block text-slate-700 font-medium mb-2">Bio</label>
                                <textarea
                                    id="bio"
                                    name="bio"
                                    rows="4"
                                    placeholder="Tell others about yourself..."
                                    class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                ><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>

                            <button
                                type="submit"
                                class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold"
                            >
                                Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- Security Section -->
                    <div id="security-section" class="bg-white rounded-3xl shadow-lg p-8 hidden">
                        <h2 class="text-xl font-bold text-slate-900 mb-6">Security Settings</h2>
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div>
                                <label for="current_password" class="block text-slate-700 font-medium mb-2">Current Password</label>
                                <input
                                    id="current_password"
                                    name="current_password"
                                    type="password"
                                    class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                    required
                                />
                            </div>

                            <div>
                                <label for="new_password" class="block text-slate-700 font-medium mb-2">New Password</label>
                                <input
                                    id="new_password"
                                    name="new_password"
                                    type="password"
                                    class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                    required
                                />
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-slate-700 font-medium mb-2">Confirm New Password</label>
                                <input
                                    id="confirm_password"
                                    name="confirm_password"
                                    type="password"
                                    class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500 focus:ring-opacity-20"
                                    required
                                />
                            </div>

                            <button
                                type="submit"
                                class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-2xl font-semibold"
                            >
                                Change Password
                            </button>
                        </form>
                    </div>

                    <!-- Notifications Section -->
                    <div id="notifications-section" class="bg-white rounded-3xl shadow-lg p-8 hidden">
                        <h2 class="text-xl font-bold text-slate-900 mb-6">Notification Preferences</h2>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-slate-900">Trade Proposals</h4>
                                    <p class="text-slate-600 text-sm">Get notified when someone proposes a trade</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-slate-900">Messages</h4>
                                    <p class="text-slate-600 text-sm">Get notified about new messages</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-slate-900">Trade Updates</h4>
                                    <p class="text-slate-600 text-sm">Get notified about trade status changes</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Section -->
                    <div id="privacy-section" class="bg-white rounded-3xl shadow-lg p-8 hidden">
                        <h2 class="text-xl font-bold text-slate-900 mb-6">Privacy Settings</h2>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-slate-900">Profile Visibility</h4>
                                    <p class="text-slate-600 text-sm">Make your profile visible to other users</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-slate-900">Show Location</h4>
                                    <p class="text-slate-600 text-sm">Display your location to other traders</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-slate-900">Trade History</h4>
                                    <p class="text-slate-600 text-sm">Show your completed trades on your profile</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showSection(section) {
    // Hide all sections
    document.querySelectorAll('[id$="-section"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id$="-tab"]').forEach(el => {
        el.classList.remove('bg-emerald-50', 'text-emerald-700');
        el.classList.add('hover:bg-slate-50', 'text-slate-700');
    });
    
    // Show selected section
    document.getElementById(section + '-section').classList.remove('hidden');
    document.getElementById(section + '-tab').classList.add('bg-emerald-50', 'text-emerald-700');
    document.getElementById(section + '-tab').classList.remove('hover:bg-slate-50', 'text-slate-700');
}
</script>

