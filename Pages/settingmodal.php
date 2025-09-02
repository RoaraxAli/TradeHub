<div id="customModal" 
class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
<div class="bg-white rounded-2xl shadow-lg p-6 w-[80%] relative"
    style="max-height: 70vh;min-height: 70vh;">
    <button id="closeModalBtn" 
            class="absolute top-3 right-3 text-gray-500 hover:text-gray-700">
    &times;
    </button>
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
include '../includes/header.php';
?>

<div class="bg-slate-50 p-4 sm:p-6 rounded-2xl">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Settings</h1>
        <p class="text-slate-600 text-sm">Manage your account preferences and security</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-2xl mb-4 text-sm">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-2xl mb-4 text-sm">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-4">
        <!-- Tabs Navigation -->
        <div class="space-y-2">
            <button onclick="showSection('profile')" id="profile-tab" class="w-full text-left px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 font-medium text-sm">
                <i class="fas fa-user mr-2"></i> Profile Info
            </button>
            <button onclick="showSection('security')" id="security-tab" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 text-sm">
                <i class="fas fa-lock mr-2"></i> Security
            </button>
            <button onclick="showSection('notifications')" id="notifications-tab" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 text-sm">
                <i class="fas fa-bell mr-2"></i> Notifications
            </button>
            <button onclick="showSection('privacy')" id="privacy-tab" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 text-sm">
                <i class="fas fa-shield-alt mr-2"></i> Privacy
            </button>
        </div>

        <!-- Content Area -->
        <div class="md:col-span-2 space-y-4">
            <!-- Profile Section -->
            <div id="profile-section" class="bg-white rounded-xl shadow p-4 sm:p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Profile Information</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 text-sm mb-1">Full Name *</label>
                            <input name="full_name" type="text"
                                value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-400" required>
                        </div>
                        <div>
                            <label class="block text-slate-700 text-sm mb-1">Email *</label>
                            <input name="email" type="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-400" required>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-700 text-sm mb-1">Phone</label>
                            <input name="phone" type="tel"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-400">
                        </div>
                        <div>
                            <label class="block text-slate-700 text-sm mb-1">Location</label>
                            <input name="location" type="text"
                                value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-sm mb-1">Bio</label>
                        <textarea name="bio" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-400"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-medium">
                        Update Profile
                    </button>
                </form>
            </div>

            <!-- Security Section -->
            <div id="security-section" class="bg-white rounded-xl shadow p-4 sm:p-6 hidden">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Security Settings</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="change_password" value="1">
                    <div>
                        <label class="block text-slate-700 text-sm mb-1">Current Password</label>
                        <input name="current_password" type="password" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-400" required>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-sm mb-1">New Password</label>
                        <input name="new_password" type="password" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-400" required>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-sm mb-1">Confirm Password</label>
                        <input name="confirm_password" type="password" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-400" required>
                    </div>
                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-medium">
                        Change Password
                    </button>
                </form>
            </div>

            <!-- Notifications Section -->
            <div id="notifications-section" class="bg-white rounded-xl shadow p-4 sm:p-6 hidden">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Notification Preferences</h2>
                <div class="space-y-4 text-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-slate-900">Trade Proposals</h4>
                            <p class="text-slate-600 text-xs">Get notified when someone proposes a trade</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-4 after:w-4 after:rounded-full after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-slate-900">Messages</h4>
                            <p class="text-slate-600 text-xs">Get notified about new messages</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-4 after:w-4 after:rounded-full after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-slate-900">Trade Updates</h4>
                            <p class="text-slate-600 text-xs">Get notified about trade status changes</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-4 after:w-4 after:rounded-full after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Privacy Section -->
            <div id="privacy-section" class="bg-white rounded-xl shadow p-4 sm:p-6 hidden">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Privacy Settings</h2>
                <div class="space-y-4 text-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-slate-900">Profile Visibility</h4>
                            <p class="text-slate-600 text-xs">Make your profile visible to other users</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-4 after:w-4 after:rounded-full after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-slate-900">Show Location</h4>
                            <p class="text-slate-600 text-xs">Display your location to other traders</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-4 after:w-4 after:rounded-full after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-slate-900">Trade History</h4>
                            <p class="text-slate-600 text-xs">Show your completed trades on your profile</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-4 after:w-4 after:rounded-full after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
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

<?php include '../includes/footer.php'; ?>
</div>
</div>