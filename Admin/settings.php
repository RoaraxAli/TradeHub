<?php
require_once '../config/config.php';
require_once 'includes/admin_auth.php';

$conn = getDBConnection();

$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $settings = [
        'site_name' => sanitize($_POST['site_name']),
        'site_description' => sanitize($_POST['site_description']),
        'site_email' => sanitize($_POST['site_email']),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
        'email_verification' => isset($_POST['email_verification']) ? 1 : 0,
        'max_listings_per_user' => (int)$_POST['max_listings_per_user'],
        'max_file_size' => (int)$_POST['max_file_size'],
        'allowed_file_types' => sanitize($_POST['allowed_file_types']),
        'smtp_host' => sanitize($_POST['smtp_host']),
        'smtp_port' => (int)$_POST['smtp_port'],
        'smtp_username' => sanitize($_POST['smtp_username']),
        'smtp_password' => sanitize($_POST['smtp_password']),
        'google_analytics' => sanitize($_POST['google_analytics']),
        'facebook_pixel' => sanitize($_POST['facebook_pixel'])
    ];
    
    // Save settings
    $saved = true;
    $query = "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
              ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($query);

    foreach ($settings as $key => $value) {
        $stmt->bind_param("sss", $key, $value, $value);
        if (!$stmt->execute()) {
            $saved = false;
            break;
        }
    }
    
    if ($saved) {
        $success = 'Settings saved successfully!';
    } else {
        $error = 'Failed to save settings';
    }
}

// Load existing settings
$query = "SELECT setting_key, setting_value FROM site_settings";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$existing_settings = [];
while ($row = $result->fetch_assoc()) {
    $existing_settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$defaults = [
    'site_name' => 'TradeHub',
    'site_description' => 'A community-driven barter marketplace',
    'site_email' => 'admin@TradeHub.com',
    'maintenance_mode' => 0,
    'email_verification' => 1,
    'max_listings_per_user' => 50,
    'max_file_size' => 10,
    'allowed_file_types' => 'jpg,jpeg,png,gif',
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'google_analytics' => '',
    'facebook_pixel' => ''
];

$settings = array_merge($defaults, $existing_settings);

$page_title = 'Site Settings';
include 'includes/header.php';
?>

<div class="flex-1 p-4 md:p-8 bg-gray-50 min-h-screen">
    <!-- Updated header section with modern styling and green color scheme -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 md:mb-8 space-y-4 sm:space-y-0">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Site Settings</h1>
            <p class="text-slate-600">Configure your website settings and preferences</p>
        </div>
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
            <button onclick="testEmailSettings()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg shadow hover:shadow-md transition-all text-sm md:text-base">
                <i class="fas fa-envelope mr-2"></i>
                Test Email
            </button>
            <button onclick="resetToDefaults()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg shadow hover:shadow-md transition-all text-sm md:text-base">
                <i class="fas fa-undo mr-2"></i>
                Reset Defaults
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6 md:space-y-8">
        <div class="grid lg:grid-cols-2 gap-6 md:gap-8">
            <!-- Updated all cards with modern styling and green color scheme -->
            <!-- General Settings -->
            <div class="bg-white rounded-lg shadow border p-4 md:p-6">
                <h2 class="text-lg md:text-xl font-bold text-slate-900 mb-4 md:mb-6">General Settings</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" 
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Site Description</label>
                        <textarea name="site_description" rows="3" 
                                  class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Admin Email</label>
                        <input type="email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email']); ?>" 
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base" required>
                    </div>

                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?> 
                                   class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                            <span class="ml-2 text-sm text-slate-700">Maintenance Mode</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="email_verification" <?php echo $settings['email_verification'] ? 'checked' : ''; ?> 
                                   class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                            <span class="ml-2 text-sm text-slate-700">Require Email Verification</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- User Limits -->
            <div class="bg-white rounded-lg shadow border p-4 md:p-6">
                <h2 class="text-lg md:text-xl font-bold text-slate-900 mb-4 md:mb-6">User Limits</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Max Listings per User</label>
                        <input type="number" name="max_listings_per_user" value="<?php echo $settings['max_listings_per_user']; ?>" 
                               min="1" max="1000" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Max File Size (MB)</label>
                        <input type="number" name="max_file_size" value="<?php echo $settings['max_file_size']; ?>" 
                               min="1" max="100" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Allowed File Types</label>
                        <input type="text" name="allowed_file_types" value="<?php echo htmlspecialchars($settings['allowed_file_types']); ?>" 
                               placeholder="jpg,jpeg,png,gif" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base">
                        <p class="text-xs text-slate-500 mt-1">Comma-separated list of file extensions</p>
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="bg-white rounded-lg shadow border p-4 md:p-6">
                <h2 class="text-lg md:text-xl font-bold text-slate-900 mb-4 md:mb-6">Email Settings (SMTP)</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" 
                               placeholder="smtp.gmail.com" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">SMTP Port</label>
                        <input type="number" name="smtp_port" value="<?php echo $settings['smtp_port']; ?>" 
                               placeholder="587" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">SMTP Username</label>
                        <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>" 
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">SMTP Password</label>
                        <input type="password" name="smtp_password" value="<?php echo htmlspecialchars($settings['smtp_password']); ?>" 
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base">
                    </div>
                </div>
            </div>

            <!-- Analytics -->
            <div class="bg-white rounded-lg shadow border p-4 md:p-6">
                <h2 class="text-lg md:text-xl font-bold text-slate-900 mb-4 md:mb-6">Analytics & Tracking</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Google Analytics ID</label>
                        <input type="text" name="google_analytics" value="<?php echo htmlspecialchars($settings['google_analytics']); ?>" 
                               placeholder="G-XXXXXXXXXX" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Facebook Pixel ID</label>
                        <input type="text" name="facebook_pixel" value="<?php echo htmlspecialchars($settings['facebook_pixel']); ?>" 
                               placeholder="123456789012345" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-20 text-sm md:text-base">
                    </div>
                </div>
            </div>
        </div>

        <!-- Updated action buttons with green color scheme and better responsive design -->
        <div class="flex flex-col sm:flex-row items-center justify-end space-y-3 sm:space-y-0 sm:space-x-4 pt-6 border-t border-gray-200">
            <button type="submit" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-6 md:px-8 py-2 md:py-3 rounded-lg font-semibold shadow hover:shadow-md transition-all text-sm md:text-base">
                <i class="fas fa-save mr-2"></i>
                Save Settings
            </button>
        </div>
    </form>
</div>

<script>
function resetToDefaults() {
    if (confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
        // Reset form to default values
        document.querySelector('input[name="site_name"]').value = 'TradeHub';
        document.querySelector('textarea[name="site_description"]').value = 'A community-driven barter marketplace';
        document.querySelector('input[name="site_email"]').value = 'admin@TradeHub.com';
        document.querySelector('input[name="maintenance_mode"]').checked = false;
        document.querySelector('input[name="email_verification"]').checked = true;
        document.querySelector('input[name="max_listings_per_user"]').value = '50';
        document.querySelector('input[name="max_file_size"]').value = '10';
        document.querySelector('input[name="allowed_file_types"]').value = 'jpg,jpeg,png,gif';
        
        // Clear SMTP settings
        document.querySelector('input[name="smtp_host"]').value = '';
        document.querySelector('input[name="smtp_port"]').value = '587';
        document.querySelector('input[name="smtp_username"]').value = '';
        document.querySelector('input[name="smtp_password"]').value = '';
        
        // Clear analytics
        document.querySelector('input[name="google_analytics"]').value = '';
        document.querySelector('input[name="facebook_pixel"]').value = '';
    }
}

function testEmailSettings() {
    const smtpHost = document.querySelector('input[name="smtp_host"]').value;
    const smtpPort = document.querySelector('input[name="smtp_port"]').value;
    const smtpUsername = document.querySelector('input[name="smtp_username"]').value;
    const smtpPassword = document.querySelector('input[name="smtp_password"]').value;
    
    if (!smtpHost || !smtpUsername || !smtpPassword) {
        alert('Please fill in all SMTP settings before testing.');
        return;
    }
    
    // Send test email request
    fetch('api/test-email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            smtp_host: smtpHost,
            smtp_port: smtpPort,
            smtp_username: smtpUsername,
            smtp_password: smtpPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test email sent successfully!');
        } else {
            alert('Failed to send test email: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error testing email settings: ' + error);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
