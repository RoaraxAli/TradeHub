<?php
// Additional helper functions for TradeHub

function getUserById($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM users WHERE id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getListingById($listing_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT l.*, u.full_name as owner_name, u.location as owner_location 
              FROM listings l 
              JOIN users u ON l.user_id = u.id 
              WHERE l.id = ? AND l.status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $listing_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserListings($user_id, $limit = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM listings WHERE user_id = ? AND status != 'deleted' ORDER BY created_at DESC";
    if ($limit) {
        $query .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserTrades($user_id, $limit = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT t.*, 
                     l1.title as offered_item_title, l1.image_url as offered_item_image,
                     l2.title as requested_item_title, l2.image_url as requested_item_image,
                     u1.full_name as requester_name,
                     u2.full_name as owner_name
              FROM trades t
              JOIN listings l1 ON t.offered_item_id = l1.id
              JOIN listings l2 ON t.requested_item_id = l2.id
              JOIN users u1 ON t.requester_id = u1.id
              JOIN users u2 ON t.owner_id = u2.id
              WHERE t.requester_id = ? OR t.owner_id = ?
              ORDER BY t.created_at DESC";
    
    if ($limit) {
        $query .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $user_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserCredits($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT SUM(CASE WHEN type IN ('earned', 'bonus') THEN amount ELSE -amount END) as total_credits
              FROM credits WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_credits'] ?? 0;
}

function getUnreadMessageCount($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}

function getUnreadNotificationCount($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}

function createNotification($user_id, $type, $title, $message, $data = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO notifications (user_id, type, title, message, data, created_at) 
              VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $type);
    $stmt->bindParam(3, $title);
    $stmt->bindParam(4, $message);
    $stmt->bindParam(5, json_encode($data));
    
    return $stmt->execute();
}

function addCredits($user_id, $amount, $type, $description, $trade_id = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO credits (user_id, amount, type, description, trade_id, created_at) 
              VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $amount);
    $stmt->bindParam(3, $type);
    $stmt->bindParam(4, $description);
    $stmt->bindParam(5, $trade_id);
    
    return $stmt->execute();
}

function getCategories() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchListings($query, $category = null, $type = null, $limit = 20, $offset = 0) {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT l.*, u.full_name as owner_name, u.location as owner_location 
            FROM listings l 
            JOIN users u ON l.user_id = u.id 
            WHERE l.status = 'active' AND (l.title LIKE ? OR l.description LIKE ?)";
    
    $params = ["%$query%", "%$query%"];
    
    if ($category) {
        $sql .= " AND l.category = ?";
        $params[] = $category;
    }
    
    if ($type) {
        $sql .= " AND l.type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatCurrency($amount) {
    return number_format($amount, 0);
}

function getImageUrl($filename) {
    if (empty($filename)) {
        return '/placeholder.svg?height=200&width=200';
    }
    return SITE_URL . '/' . UPLOAD_PATH . $filename;
}

function isOnline($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT last_activity FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) return false;
    
    $lastActivity = strtotime($result['last_activity']);
    $fiveMinutesAgo = time() - (5 * 60);
    
    return $lastActivity > $fiveMinutesAgo;
}
?>
