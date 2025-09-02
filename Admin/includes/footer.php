</div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        
        function toggleNotifications() {
            const dropdown = document.getElementById('notifications-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close notifications dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notifications-dropdown');
            const button = event.target.closest('button[onclick="toggleNotifications()"]');
            
            if (!button && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Auto-refresh dashboard stats every 30 seconds
        if (window.location.pathname.includes('dashboard.php')) {
            setInterval(function() {
                // Refresh stats without full page reload
                fetch('api/get-stats.php')
                    .then(response => response.json())
                    .then(data => {
                        // Update stats on page
                        console.log('Stats updated:', data);
                    })
                    .catch(error => console.error('Error updating stats:', error));
            }, 30000);
        }
    </script>
</body>
</html>
