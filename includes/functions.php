<?php
// Additional helper functions for animations and chat

function isUserOnline($last_activity) {
    if (!$last_activity) return false;
    
    $last_activity_time = strtotime($last_activity);
    $current_time = time();
    $time_diff = $current_time - $last_activity_time;
    
    // Consider user online if active within last 5 minutes
    return $time_diff < 300;
}

function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        return floor($time / 60) . 'm ago';
    } elseif ($time < 86400) {
        return floor($time / 3600) . 'h ago';
    } elseif ($time < 2592000) {
        return floor($time / 86400) . 'd ago';
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}

function getMessageStatusIcon($status, $is_read = false) {
    switch ($status) {
        case 'sending':
            return '<i class="fas fa-clock text-xs text-slate-400"></i>';
        case 'sent':
            return '<i class="fas fa-check text-xs text-slate-400"></i>';
        case 'delivered':
            return '<i class="fas fa-check-double text-xs text-slate-400"></i>';
        case 'read':
            return '<i class="fas fa-check-double text-xs text-emerald-500"></i>';
        default:
            return '';
    }
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function asset($path) {
    return '/assets/' . ltrim($path, '/');
}
?>
