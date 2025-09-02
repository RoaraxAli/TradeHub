<?php
// Ensure user is logged in
require_once '../config/config.php';
$current_page = basename($_SERVER['PHP_SELF']);
requireLogin();
?>
<style>
    .shimmer {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200px 100%;
            animation: shimmer 2s infinite;
        }

        .sidebar {
            overflow-x: hidden;
            width: 280px;
            transition: width 0.3s ease-in-out;
            flex-shrink: 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }
        .sidebar.collapsed {
            width: 80px;
        }
        .sidebar-text {
            opacity: 1;
            transition: opacity 0.2s ease-in-out;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar.collapsed .sidebar-text {
            opacity: 0;
            pointer-events: none;
        }

        .card-hover {
            transition: all 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
            transition: all 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease-out;
        }
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .status-indicator {
            transition: all 0.2s ease-out;
        }
        .status-indicator:hover {
            transform: scale(1.02);
        }

        .particle-bg {
            position: relative;
        }

        .focus-ring:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
        }

        .icon-hover {
            transition: transform 0.2s ease-out;
        }
        .icon-hover:hover {
            transform: scale(1.1);
        }
            
</style>
<aside>
<div id="sidebar" class="sidebar collapsed border-r justify-between border-slate-200/50 h-full custom-scrollbar overflow-y-hidden flex flex-col animate-slide-in-left">
    <div class="py-3 px-4 p-6 border-b border-slate-200/50">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 via-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-xl flex-shrink-0 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent"></div>
                <i class="fas fa-sync-alt text-white text-xl relative z-10"></i>
            </div>
        </div>
    </div>

    <nav class="p-4 space-y-3 flex-1">
        <!-- Dashboard -->
        <a href="dashboard.php" 
            class="flex items-center space-x-3 px-3 py-3 rounded-2xl font-semibold shadow-lg group relative overflow-hidden animate-fade-in-up animate-delay-100
            <?php echo $current_page === 'dashboard.php' 
                ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white' 
                : 'text-slate-600 hover:bg-gradient-to-r hover:from-slate-50 hover:to-emerald-50 hover:text-emerald-700'; ?>">
            <i class="fas fa-home w-5 flex-shrink-0 relative z-10 icon-hover"></i>
            <span class="sidebar-text relative z-10">Dashboard</span>
        </a>

        <!-- Marketplace -->
        <a href="marketplace.php" 
            class="flex items-center space-x-3 px-3 py-3 rounded-2xl transition-all duration-300 group animate-fade-in-up animate-delay-200 focus-ring
            <?php echo $current_page === 'marketplace.php' 
                ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold shadow-lg' 
                : 'text-slate-600 hover:bg-gradient-to-r hover:from-slate-50 hover:to-emerald-50 hover:text-emerald-700'; ?>">
            <i class="fas fa-search w-5 flex-shrink-0 icon-hover"></i>
            <span class="sidebar-text font-medium">Marketplace</span>
        </a>

        <!-- My Listings -->
        <a href="my-listings.php" 
            class="flex items-center space-x-3 px-3 py-3 rounded-2xl transition-all duration-300 group animate-fade-in-up animate-delay-300 focus-ring
            <?php echo $current_page === 'my-listings.php' 
                ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold shadow-lg' 
                : 'text-slate-600 hover:bg-gradient-to-r hover:from-slate-50 hover:to-emerald-50 hover:text-emerald-700'; ?>">
            <i class="fas fa-list w-5 flex-shrink-0 icon-hover"></i>
            <span class="sidebar-text font-medium">My Listings</span>
        </a>

        <!-- Trades -->
        <a href="trades.php" 
            class="flex items-center space-x-3 px-3 py-3 rounded-2xl transition-all duration-300 group animate-fade-in-up animate-delay-400 focus-ring
            <?php echo $current_page === 'trades.php' 
                ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold shadow-lg' 
                : 'text-slate-600 hover:bg-gradient-to-r hover:from-slate-50 hover:to-emerald-50 hover:text-emerald-700'; ?>">
            <i class="fas fa-handshake w-5 flex-shrink-0 icon-hover"></i>
            <span class="sidebar-text font-medium">Trades</span>
        </a>

        <!-- Messages -->
        <a href="messages.php" 
            class="flex items-center space-x-3 px-3 py-3 rounded-2xl transition-all duration-300 group animate-fade-in-up animate-delay-500 focus-ring
            <?php echo $current_page === 'messages.php' 
                ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold shadow-lg' 
                : 'text-slate-600 hover:bg-gradient-to-r hover:from-slate-50 hover:to-emerald-50 hover:text-emerald-700'; ?>">
            <i class="fas fa-envelope w-5 flex-shrink-0 icon-hover"></i>
            <span class="sidebar-text font-medium">Messages</span>
        </a>
    </nav>

    <div class="p-4 border-t border-slate-200/50 bg-gradient-to-r from-slate-50/50 to-white">
        <div class="relative">
            <button onclick="toggleDropdown()" class="flex items-center space-x-3 w-full text-slate-600 md:hover:bg-white md:hover:shadow-md md:rounded-2xl transition-all duration-300 group md:focus-ring">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 via-purple-500 to-purple-600 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg relative overflow-hidden">
                    <i class="fas fa-user text-white text-sm relative z-10"></i>
                </div>
                <div class="flex-1 text-left sidebar-text">
                    <p class="font-semibold text-slate-900 text-sm"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p class="text-xs text-emerald-600 font-medium">View Profile</p>
                </div>
                <i class="fas fa-chevron-up text-xs sidebar-text text-slate-400 group-hover:text-emerald-500 transition-colors"></i>
            </button>
            <div id="dropdown" class="hidden absolute bottom-full left-0 right-0 bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-white/50">
                <p id="openModalBtn3" class="flex items-center space-x-3 px-3 py-3 text-slate-600 hover:bg-gradient-to-r hover:from-slate-50 hover:to-emerald-50 hover:text-emerald-700 rounded-2xl transition-all duration-300 group animate-fade-in-up animate-delay-300 focus-ring">
                <i class="fas fa-user w-5 flex-shrink-0 icon-hover"></i>
                <span class="sidebar-text font-medium">Profile</span>
                </p>
                <p id="openModalBtn2" class="flex items-center space-x-3 px-3 py-3 text-slate-600 hover:bg-gradient-to-r hover:from-slate-50 hover:to-emerald-50 hover:text-emerald-700 rounded-2xl transition-all duration-300 group animate-fade-in-up animate-delay-400 focus-ring">
                    <i class="fas fa-cog w-5 flex-shrink-0 icon-hover"></i>
                    <span class="sidebar-text font-medium">Settings</span>
                </p>
                <a href="../auth/logout.php" class="flex items-center space-x-3 px-3 py-3 text-red-600 hover:bg-red-50 rounded-2xl transition-all duration-300 group animate-fade-in-up animate-delay-500 focus-ring">
                    <i class="fas fa-sign-out-alt flex-shrink-0 icon-hover"></i>
                    <span class="sidebar-text font-medium">Logout</span>
                </a>
            </div>
        </div>
    </div>
</div>
</aside>
<script>
    function toggleDropdown() {
        const dropdown = document.getElementById('dropdown');
        dropdown.classList.toggle('hidden');
        
        if (!dropdown.classList.contains('hidden')) {
            dropdown.style.animation = 'scaleIn 0.2s ease-out';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.3s ease-in-out';
        
        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 50);
    });

    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('dropdown');
        const button = event.target.closest('button');
        
        if (!button || !button.onclick || button.onclick.toString().indexOf('toggleDropdown') === -1) {
            dropdown.classList.add('hidden');
        }
    });
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById('customModal');
    const openBtn = document.getElementById('openModalBtn2');
    const closeBtn = document.getElementById('closeModalBtn');

    if (openBtn) {
        openBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
            history.pushState(null, "", "?modal=settings");

            // Redirect for small screens
            if (window.innerWidth < 800) {
                window.location.href = "settings.php";
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
            history.pushState(null, "", window.location.pathname);
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            history.pushState(null, "", window.location.pathname);
        }
    });

    // Show modal if URL contains ?modal=settings
    if (window.location.search.includes("modal=settings")) {
        if (window.innerWidth < 800) {
            window.location.href = "settings.php";
        } else {
            modal.classList.remove('hidden');
        }
    }
});
document.addEventListener("DOMContentLoaded", () => {
    let isSmallScreen = window.innerWidth < 800;
    const modal = document.getElementById('customModal');
    const openBtn = document.getElementById('openModalBtn2');
    const closeBtn = document.getElementById('closeModalBtn');

    function handleModalState() {
        isSmallScreen = window.innerWidth < 800;

        // If modal is open and we shrink to <800, redirect
        if (isSmallScreen && !modal.classList.contains('hidden')) {
            window.location.href = "settings.php";
        }
    }

    // Initial check if URL has ?modal=settings
    if (isSmallScreen && window.location.search.includes("modal=settings")) {
        window.location.href = "settings.php";
        return;
    } else if (window.location.search.includes("modal=settings")) {
        modal.classList.remove('hidden');
    }

    // Event for opening modal
    if (openBtn) {
        openBtn.addEventListener('click', () => {
            if (isSmallScreen) {
                window.location.href = "settings.php";
            } else {
                modal.classList.remove('hidden');
                history.pushState(null, "", "?modal=settings");
            }
        });
    }

    // Event for closing modal
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
            history.pushState(null, "", window.location.pathname);
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            history.pushState(null, "", window.location.pathname);
        }
    });

    // Watch for real-time resizing
    window.addEventListener('resize', handleModalState);
});
document.addEventListener("DOMContentLoaded", () => {
    let isSmallScreen = window.innerWidth < 800;
    const modal = document.getElementById('customModal2');
    const openBtn = document.getElementById('openModalBtn3');
    const closeBtn = document.getElementById('closeModalBtn2');

    function handleModalState() {
        isSmallScreen = window.innerWidth < 800;

        // If modal is open and we shrink to <800, redirect
        if (isSmallScreen && !modal.classList.contains('hidden')) {
            window.location.href = "profile.php";
        }
    }

    // Initial check if URL has ?modal=settings
    if (isSmallScreen && window.location.search.includes("modal=profile")) {
        window.location.href = "profile.php";
        return;
    } else if (window.location.search.includes("modal=profile")) {
        modal.classList.remove('hidden');
    }

    // Event for opening modal
    if (openBtn) {
        openBtn.addEventListener('click', () => {
            if (isSmallScreen) {
                window.location.href = "profile.php";
            } else {
                modal.classList.remove('hidden');
                history.pushState(null, "", "?modal=profile");
            }
        });
    }

    // Event for closing modal
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
            history.pushState(null, "", window.location.pathname);
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            history.pushState(null, "", window.location.pathname);
        }
    });

    // Watch for real-time resizing
    window.addEventListener('resize', handleModalState);
});


</script>
