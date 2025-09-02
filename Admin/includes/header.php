<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Admin Panel' : 'Admin Panel'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/animations.css">
    
    <!-- Admin specific styles -->
<style>
    /* Updated to green-white-grey professional color scheme */
    .fixed-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 40;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .fixed-header {
        position: fixed;
        top: 0;
        left: 16rem;
        right: 0;
        z-index: 30;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
    }

    .main-content {
        margin-left: 16rem;
        padding-top: 5rem;
        min-height: 100vh;
        overflow-y: auto;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #f9fafb;
    }

    /* Enhanced mobile responsiveness with proper toggle functionality */
    @media (max-width: 1023px) {
        .fixed-sidebar {
            transform: translateX(-100%);
            width: 16rem;
        }
        
        .fixed-sidebar.sidebar-open {
            transform: translateX(0);
        }
        
        .fixed-header {
            left: 0;
        }
        
        .main-content {
            margin-left: 0;
        }
        
        /* Mobile overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 35;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
    }

    .admin-sidebar {
        background: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border-right: 1px solid #e5e7eb;
    }

    .admin-nav-item {
        transition: all 0.2s ease;
        border-radius: 0.75rem;
        padding: 0.875rem 1rem;
        margin: 0.125rem 0;
        color: #6b7280;
        font-weight: 500;
    }

    .admin-nav-item:hover {
        background: #f0fdf4;
        color: #374151;
        transform: translateX(4px);
    }

    .admin-nav-item.active {
        background: #f0fdf4;
        color: #15803d;
        border-left: 3px solid #15803d;
        font-weight: 600;
    }

    .admin-nav-item i {
        transition: all 0.2s ease;
        width: 1.25rem;
        color: #9ca3af;
    }

    .admin-nav-item:hover i,
    .admin-nav-item.active i {
        color: #15803d;
    }

    .fixed-header {
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        border-bottom: 1px solid #e5e7eb;
    }

    .fixed-header button i, .fixed-header a i {
        transition: all 0.2s ease;
        color: #6b7280;
    }

    .fixed-header button:hover i, .fixed-header a:hover i {
        color: #15803d;
    }

    #notifications-dropdown {
        border-radius: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
        transform-origin: top right;
        background: #ffffff;
        border: 1px solid #e5e7eb;
    }

    #notifications-dropdown.hidden {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }

    #notifications-dropdown .p-4:hover {
        background: #f9fafb;
        cursor: pointer;
    }

    .admin-sidebar .p-6 {
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .admin-sidebar h1 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #374151;
    }

    .admin-sidebar p {
        color: #6b7280;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .admin-nav-item span {
        font-size: 0.875rem;
        font-weight: 500;
    }

    .fixed-header h2 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #374151;
    }

    .fixed-header p {
        color: #6b7280;
        font-weight: 400;
        font-size: 0.875rem;
    }

    .notification-badge {
        background: #15803d;
        width: 0.5rem;
        height: 0.5rem;
        border: 2px solid #ffffff;
    }

    .logout-btn {
        background: #fef2f2 !important;
        color: #dc2626 !important;
        margin-top: 1rem;
        border: 1px solid #fecaca;
    }

    .logout-btn:hover {
        background: #fee2e2 !important;
        color: #b91c1c !important;
        transform: translateX(4px) !important;
    }

    .logout-btn i {
        color: #dc2626 !important;
    }

    /* Added logo styling with green accent */
    .logo-icon {
        background: #15803d;
        color: #ffffff;
    }

    /* Enhanced notification icons with green accents */
    .notification-icon-green {
        background: #dcfce7;
        color: #15803d;
    }

    .notification-icon-blue {
        background: #dbeafe;
        color: #3b82f6;
    }
</style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Added mobile overlay for proper sidebar functionality -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 admin-sidebar text-gray-700 flex-shrink-0 fixed-sidebar" id="sidebar">
            <!-- Logo -->
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <!-- Updated logo with green color scheme -->
                    <div class="w-10 h-10 logo-icon rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-sm"></i>
                    </div>
                    <div>
                        <h1 class="font-bold">Admin Panel</h1>
                        <p class="text-xs"><?php include "../includes/Name.php";?></p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="px-4 pb-4">
                <ul class="space-y-1">
                    <li>
                        <a href="dashboard.php" class="admin-nav-item flex items-center space-x-3 <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="admin-nav-item flex items-center space-x-3 <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="listings.php" class="admin-nav-item flex items-center space-x-3 <?php echo basename($_SERVER['PHP_SELF']) === 'listings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i>
                            <span>Listings</span>
                        </a>
                    </li>
                    <li>
                        <a href="trades.php" class="admin-nav-item flex items-center space-x-3 <?php echo basename($_SERVER['PHP_SELF']) === 'trades.php' ? 'active' : ''; ?>">
                            <i class="fas fa-handshake"></i>
                            <span>Trades</span>
                        </a>
                    </li>
                    <li>
                        <a href="reviews.php" class="admin-nav-item flex items-center space-x-3 <?php echo basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i>
                            <span>Reviews</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="admin-nav-item flex items-center space-x-3 <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="../auth/logout.php" class="admin-nav-item logout-btn flex items-center space-x-3">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col main-content">
            <!-- Top Bar -->
            <header class="px-6 py-4 fixed-header bg-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Updated mobile menu button with proper toggle functionality -->
                        <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h2 class="font-semibold text-gray-900"><?php echo $page_title ?? 'Admin Panel'; ?></h2>
                            <p class="text-sm text-gray-500"><?php echo date('l, F j, Y'); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="../index.php" target="_blank" class="text-gray-600 hover:text-green-700 p-2 rounded-lg hover:bg-gray-100 transition-all duration-200" title="View Website">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <div class="relative">
                            <button onclick="toggleNotifications()" class="text-gray-600 hover:text-green-700 relative p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                                <i class="fas fa-bell"></i>
                                <!-- Updated notification badge to green -->
                                <span class="absolute -top-1 -right-1 notification-badge rounded-full"></span>
                            </button>
                            <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border z-50">
                                <div class="p-4 border-b border-gray-200">
                                    <h3 class="font-semibold text-gray-900">Notifications</h3>
                                    <p class="text-sm text-gray-500 mt-1">Recent activity updates</p>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <div class="p-4 hover:bg-gray-50 border-b border-gray-100 transition-all duration-200">
                                        <div class="flex items-start space-x-3">
                                            <!-- Updated notification icons with green color scheme -->
                                            <div class="w-8 h-8 notification-icon-blue rounded-full flex items-center justify-center">
                                                <i class="fas fa-user-plus text-xs"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">New user registration</p>
                                                <p class="text-xs text-gray-500 mt-1">2 minutes ago</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 hover:bg-gray-50 transition-all duration-200">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-8 h-8 notification-icon-green rounded-full flex items-center justify-center">
                                                <i class="fas fa-handshake text-xs"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">New trade completed</p>
                                                <p class="text-xs text-gray-500 mt-1">1 hour ago</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

<!-- Added JavaScript functions for sidebar toggle functionality -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    function openSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.add('sidebar-open');
        overlay.classList.add('active');
        
        // Prevent body scroll when sidebar is open on mobile
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.remove('sidebar-open');
        overlay.classList.remove('active');
        
        // Restore body scroll
        document.body.style.overflow = '';
    }

    function toggleNotifications() {
        const notificationsDropdown = document.getElementById('notifications-dropdown');
        notificationsDropdown.classList.toggle('hidden');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const sidebarButton = event.target.closest('button[onclick="toggleSidebar()"]');
        
        if (!sidebar.contains(event.target) && !sidebarButton && sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });

    // Close sidebar on window resize if mobile breakpoint is exceeded
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            closeSidebar();
        }
    });
</script>
