<?php include '../includes/maintenance.php'?>
<?php
require_once '../config/config.php';
requireLogin();
date_default_timezone_set('Asia/Karachi');
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Handle AJAX actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Send a message
    if ($action === 'send') {
        $receiver_id = (int)$_POST['receiver_id'];
        $message = sanitize($_POST['message']);
        $trade_id = isset($_POST['trade_id']) ? (int)$_POST['trade_id'] : null;

        if (!empty($message) && $receiver_id > 0) {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, trade_id, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param('iiss', $user_id, $receiver_id, $trade_id, $message);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Delete a message
    if ($action === 'delete') {
        $message_id = (int)$_POST['message_id'];

        // Verify the user is the sender
        $stmt = $conn->prepare("SELECT sender_id FROM messages WHERE id = ?");
        $stmt->bind_param('i', $message_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $message = $result->fetch_assoc();
        $stmt->close();

        if ($message && $message['sender_id'] == $user_id) {
            $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
            $stmt->bind_param('ii', $message_id, $user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid message or permission']);
        }
        exit;
    }

    // Get new messages
    if ($action === 'get') {
        $selected_user_id = (int)$_POST['selected_user_id'];
        $last_id = (int)$_POST['last_id'];

        // Fetch new messages
        $stmt = $conn->prepare("
            SELECT m.*, u.full_name as sender_name 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id
            WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?))
              AND m.id > ?
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param('iiiii', $user_id, $selected_user_id, $selected_user_id, $user_id, $last_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Mark messages as read
        if (!empty($messages)) {
            $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
            $stmt->bind_param('ii', $selected_user_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // Fetch IDs of my messages that are now read
        $stmt = $conn->prepare("SELECT id FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 1");
        $stmt->bind_param('ii', $user_id, $selected_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $read_ids = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch conversation data
        $conv_stmt = $conn->prepare("
            SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END as other_user_id,
                (SELECT message FROM messages m2 
                 WHERE (m2.sender_id = ? AND m2.receiver_id = other_user_id) 
                    OR (m2.sender_id = other_user_id AND m2.receiver_id = ?)
                 ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages m2 
                 WHERE (m2.sender_id = ? AND m2.receiver_id = other_user_id) 
                    OR (m2.sender_id = other_user_id AND m2.receiver_id = ?)
                 ORDER BY m2.created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages m2 
                 WHERE m2.sender_id = other_user_id AND m2.receiver_id = ? AND m2.is_read = 0) as unread_count
            FROM messages m
            WHERE m.sender_id = ? OR m.receiver_id = ?
        ");
        $conv_stmt->bind_param('iiiiiiii', $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
        $conv_stmt->execute();
        $conv_result = $conv_stmt->get_result();
        $conversations = $conv_result->fetch_all(MYSQLI_ASSOC);
        $conv_stmt->close();

        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'read_ids' => $read_ids,
            'conversations' => $conversations
        ]);
        exit;
    }

    // Get conversation data
    if ($action === 'get_conversations') {
        $conv_stmt = $conn->prepare("
            SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END as other_user_id,
                (SELECT message FROM messages m2 
                 WHERE (m2.sender_id = ? AND m2.receiver_id = other_user_id) 
                    OR (m2.sender_id = other_user_id AND m2.receiver_id = ?)
                 ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages m2 
                 WHERE (m2.sender_id = ? AND m2.receiver_id = other_user_id) 
                    OR (m2.sender_id = other_user_id AND m2.receiver_id = ?)
                 ORDER BY m2.created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages m2 
                 WHERE m2.sender_id = other_user_id AND m2.receiver_id = ? AND m2.is_read = 0) as unread_count
            FROM messages m
            WHERE m.sender_id = ? OR m.receiver_id = ?
        ");
        $conv_stmt->bind_param('iiiiiiii', $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
        $conv_stmt->execute();
        $conv_result = $conv_stmt->get_result();
        $conversations = $conv_result->fetch_all(MYSQLI_ASSOC);
        $conv_stmt->close();

        echo json_encode(['success' => true, 'conversations' => $conversations]);
        exit;
    }
}

// Normal page load
$query = "SELECT DISTINCT 
          CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
          END as other_user_id,
          u.full_name as other_user_name,
          u.avatar_url as other_user_avatar,
          (SELECT message FROM messages m2 
           WHERE (m2.sender_id = ? AND m2.receiver_id = other_user_id) 
              OR (m2.sender_id = other_user_id AND m2.receiver_id = ?)
           ORDER BY m2.created_at DESC LIMIT 1) as last_message,
          (SELECT created_at FROM messages m2 
           WHERE (m2.sender_id = ? AND m2.receiver_id = other_user_id) 
              OR (m2.sender_id = other_user_id AND m2.receiver_id = ?)
           ORDER BY m2.created_at DESC LIMIT 1) as last_message_time,
          (SELECT COUNT(*) FROM messages m2 
           WHERE m2.sender_id = other_user_id AND m2.receiver_id = ? AND m2.is_read = 0) as unread_count
          FROM messages m
          JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
          WHERE m.sender_id = ? OR m.receiver_id = ? 
          ORDER BY last_message_time DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('iiiiiiiii', $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$conversations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get selected conversation messages
$selected_user_id = isset($_GET['user']) ? (int)$_GET['user'] : null;
$messages = [];
$selected_user = null;
$last_message_id = 0;

if ($selected_user_id) {
    $stmt = $conn->prepare("SELECT full_name,last_seen, avatar_url FROM users WHERE id = ?");
    $stmt->bind_param('i', $selected_user_id);
    $stmt->execute();
    $selected_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT m.*, u.full_name as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param('iiii', $user_id, $selected_user_id, $selected_user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($messages)) {
        $last_message_id = end($messages)['id'];
    }

    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->bind_param('ii', $selected_user_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

$page_title = 'Messages';
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .context-menu {
            position: absolute;
            z-index: 1000;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
        }
        .context-menu button {
            display: block;
            width: 100%;
            padding: 0.75rem 1.25rem;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            color: #374151;
            transition: all 0.2s ease;
        }
        .context-menu button:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        .three-dots {
            display: none;
            position: absolute;
            left: -32px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            padding: 4px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        .three-dots:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .message:hover .three-dots {
            display: block;
        }
        
        /* Enhanced scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Message animations */
        .message-enter {
            animation: messageSlideIn 0.3s ease-out;
        }
        
        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Enhanced conversation hover effects */
        .conversation-item {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        .conversation-item:hover {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border-left-color: #10b981;
            transform: translateX(2px);
        }
        .conversation-item.active {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left-color: #059669;
        }
        
        /* Enhanced message bubbles */
        .message-bubble {
            position: relative;
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }
        .message-bubble:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Typing indicator animation */
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }
        .typing-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #6b7280;
            animation: typingBounce 1.4s infinite ease-in-out;
        }
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typingBounce {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex flex-col">
<div class="md:p-9">
        <div class="max-w-full mx-auto h-[100vh] md:h-[calc(95vh-3rem)]">
            <div class="flex h-full bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl border border-white/50 overflow-hidden animate-scale-in">
                <?php include '../includes/sidebar.php'; ?>
    <div class="flex-1 h-full overflow-y-auto mx-auto max-w-6xl flex flex-col">
    <?php include '../includes/head.php'; ?>
        <div class="bg-white flex flex-col md:flex-row overflow-hidden">
            <!-- Enhanced conversations list with better styling -->
            <div class="w-full md:w-1/3 border-r border-gray-100 overflow-y-auto custom-scrollbar
                        <?php echo $selected_user_id ? 'hidden md:block' : 'block'; ?>">
                        <!-- Search Box -->
                <div class="relative px-4 py-4">
                <input
                    id="searchbox"
                    type="text" 
                    placeholder="Search..." 
                    class="w-full px-4 py-2 pl-10 pr-4 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                >
                <i class="fas fa-search absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchBox = document.getElementById('searchbox');
                
                if (searchBox) {
                    searchBox.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase().trim();
                        
                        document.querySelectorAll('.conversation-item').forEach(function(item) {
                            const userNameEl = item.querySelector('.font-semibold');
                            const lastMessageEl = item.querySelector('.last-message');
                            
                            const userName = userNameEl ? userNameEl.textContent.toLowerCase() : '';
                            const lastMessage = lastMessageEl ? lastMessageEl.textContent.toLowerCase() : '';
                            
                            if (searchTerm === '' || userName.includes(searchTerm) || lastMessage.includes(searchTerm)) {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    });
                }
            });
        </script>
                <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-green-50">
                    <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-comments text-emerald-600"></i>
                        Conversations
                    </h2>
                </div>
                
                <div class="divide-y divide-gray-50 overflow-hidden">
                    <?php foreach ($conversations as $conversation): ?>
                        <a href="?user=<?php echo $conversation['other_user_id']; ?>" 
                           class="conversation-item block p-4 <?php echo $selected_user_id == $conversation['other_user_id'] ? 'active' : ''; ?>"
                           data-user-id="<?php echo $conversation['other_user_id']; ?>">
                            <div class="flex items-center space-x-4">
                                <!-- Enhanced avatar with online indicator -->
                                <div class="relative">
                                    <div class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center shadow-md">
                                        <?php if ($conversation['other_user_avatar']): ?>
                                            <img src="<?php echo htmlspecialchars($conversation['other_user_avatar']); ?>" class="w-full h-full object-cover" alt="Avatar">
                                        <?php else: ?>
                                            <i class="fas fa-user text-emerald-600 text-lg"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 border-2 border-white rounded-full"
                                        id="status-dot-<?php echo $conversation['other_user_id']; ?>"
                                        data-user-id="<?php echo $conversation['other_user_id']; ?>"
                                        style="background-color: gray;">
                                    </div>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($conversation['other_user_name']); ?></p>
                                        <p class="text-xs text-gray-500 last-message-time"><?php echo date('M d, H:i', strtotime($conversation['last_message_time'])); ?></p>
                                    </div>
                                    <p class="text-sm text-gray-600 truncate last-message"><?php echo htmlspecialchars($conversation['last_message']); ?></p>
                                </div>
                                
                                <!-- Enhanced unread badge -->
                                <div class="flex flex-col items-end gap-1">
                                    <span class="unread-badge <?php echo $conversation['unread_count'] > 0 ? '' : 'hidden'; ?> bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-xs px-2.5 py-1 rounded-full font-medium shadow-md">
                                        <?php echo $conversation['unread_count']; ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Enhanced chat area with better styling -->
            <div class="flex-1 flex flex-col h-[90vh] <?php echo $selected_user_id ? 'block' : 'hidden md:block'; ?>">
                <?php if ($selected_user): ?>
                    <!-- Enhanced chat header -->
                    <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-white to-gray-50 flex items-center space-x-4">
                        <a href="messages.php" class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-all duration-200">
                            <i class="fas fa-arrow-left text-gray-600"></i>
                        </a>
                        
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center shadow-md overflow-hidden">
                                <?php if ($selected_user['avatar_url']): ?>
                                    <img src="<?php echo htmlspecialchars($selected_user['avatar_url']); ?>" class="w-full h-full object-cover" alt="Avatar">
                                <?php else: ?>
                                    <i class="fas fa-user text-emerald-600 text-lg"></i>
                                <?php endif; ?>
                            </div>
                            <div id="status-dot" class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-400 border-2 border-white rounded-full"></div>
                        </div>
                        
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg text-gray-800">
                                <?php echo htmlspecialchars($selected_user['full_name']); ?>
                            </h3>
                            <p class="text-sm text-gray-500 font-medium" id="user-status" data-user-id="<?php echo htmlspecialchars($selected_user_id); ?>">
                                <?php echo "Loading..."; ?>
                            </p>
                        </div>

                        <a href="call.php?user=<?php echo $selected_user_id; ?>" 
                            class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 transition-all duration-200 flex items-center justify-center call-btn"
                            data-user="<?php echo $selected_user_id; ?>" id="callBtn">
                            <i class="fas fa-phone text-gray-600"></i>
                            </a>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.call-btn').on('click', function(e) {
        e.preventDefault(); // stop immediate navigation

        let userId = $(this).data('user');
        let url = $(this).attr('href');

        // Send AJAX request to save "Calling" message
        $.post('send_message.php', { receiver_id: userId, message: 'Calling' }, function(response) {
            console.log('Message sent:', response);
            // After sending the message, navigate to call.php
            window.location.href = url;
        });
    });
});
</script>


                    </div>

                    <!-- Enhanced messages container -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-gradient-to-b from-gray-50/30 to-white custom-scrollbar" id="messagesContainer">
                        <?php foreach ($messages as $message): ?>
                            <div class="flex <?php echo $message['sender_id'] == $user_id ? 'justify-end' : 'justify-start'; ?> mb-3 relative message">
                                <div class="message-bubble <?php echo $message['sender_id'] == $user_id 
                                    ? 'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white' 
                                    : 'bg-white text-gray-800 border border-gray-200'; ?> max-w-[80%] sm:max-w-[60%] break-words break-all px-4 py-3 rounded-2xl shadow-md relative"
                                    data-msg-id="<?php echo $message['id']; ?>">
                                    <?php if ($message['sender_id'] == $user_id): ?>
                                        <div class="three-dots">
                                            <i class="fas fa-ellipsis-v text-white/70"></i>
                                        </div>
                                    <?php endif; ?>
                                    <p class="message-text leading-relaxed"><?php echo htmlspecialchars($message['message']); ?></p>
                                    <div class="flex items-center justify-between mt-2 text-xs opacity-75">
                                        <span><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
                                        <?php if ($message['sender_id'] == $user_id): ?>
                                            <span class="status-text ml-2">
                                                <?php if ($message['is_read']): ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                         class="w-4 h-4 inline-block text-blue-300">
                                                        <path d="M1 13l4 4L15 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                                        <path d="M9 13l4 4L23 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                                    </svg>
                                                <?php else: ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                         class="w-4 h-4 inline-block text-white/50">
                                                        <path d="M1 13l4 4L15 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                                        <path d="M9 13l4 4L23 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                                    </svg>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Enhanced message input area -->
                    <div class="p-4 border-t border-gray-100 bg-white">
                        <div class="flex items-center space-x-2 bg-gray-50 rounded-2xl p-2 md:mb-16">
                            
                            <!-- Add button -->
                            <button class="w-10 h-10 rounded-full bg-white hover:bg-gray-100 transition-all duration-200 flex items-center justify-center shadow-sm flex-shrink-0">
                            <i class="fas fa-plus text-gray-600"></i>
                            </button>

                            <!-- Message input -->
                            <input type="text" id="messageInput"
                            class="flex-1 min-w-0 bg-transparent border-none focus:outline-none px-3 py-2 text-gray-800 placeholder-gray-500"
                            placeholder="Type your message..."
                            autocomplete="off" />

                            <!-- Send button -->
                            <button id="sendBtn"
                            class="w-12 h-12 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-full hover:from-emerald-600 hover:to-emerald-700 transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-105 flex-shrink-0">
                            <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        </div>

                <?php else: ?>
                    <!-- Enhanced empty state -->
                    <div class="flex-1 hidden md:flex items-center justify-center bg-gradient-to-b from-gray-50/30 to-white h-full">
  <div class="text-center">
    <div class="w-24 h-24 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-full flex items-center justify-center mb-6 mx-auto">
      <i class="fas fa-comments text-4xl text-emerald-600"></i>
    </div>
    <h3 class="text-xl font-semibold text-gray-600 mb-2">Your messages</h3>
    <p class="text-gray-500">Select a message to start chatting</p>
  </div>
</div>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function refreshUserStatus() {
            // Get user ID from the data attribute
            const userId = document.getElementById("user-status").dataset.userId;
            const dot = document.getElementById("status-dot");
            fetch("get_status.php?id=" + userId)
                .then(res => res.text())
                .then(status => {
                    document.getElementById("user-status").innerText = status;
                    if (
    status.includes("Active Now")
) {
    dot.classList.remove("bg-gray-400");
    dot.classList.add("bg-emerald-400");
} else {
    dot.classList.remove("bg-emerald-400");
    dot.classList.add("bg-gray-400");
}

                })
        }

        setInterval(refreshUserStatus, 5000);
        refreshUserStatus();

        setInterval(() => {
        fetch("update_status.php", { method: "POST" });
        }, 10000);
        // Function to fetch conversation data
        function fetchConversations() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'get_conversations'})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.conversations) {
                    document.querySelectorAll("[data-user-id]").forEach(el => {
                        const uid = el.getAttribute("data-user-id");
                        const conversation = data.conversations.find(c => c.other_user_id == uid);
                        if (conversation) {
                            const badge = el.querySelector(".unread-badge");
                            const lastMessageEl = el.querySelector(".last-message");
                            const lastMessageTimeEl = el.querySelector(".last-message-time");

                            if (badge) {
                                if (conversation.unread_count > 0) {
                                    badge.textContent = conversation.unread_count;
                                    badge.classList.remove("hidden");
                                } else {
                                    badge.classList.add("hidden");
                                }
                            }

                            if (lastMessageEl && conversation.last_message) {
                                lastMessageEl.textContent = conversation.last_message;
                            }

                            if (lastMessageTimeEl && conversation.last_message_time) {
                                const date = new Date(conversation.last_message_time);
                                lastMessageTimeEl.textContent = date.toLocaleString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: false
                                });
                            }
                        }
                    });
                }
            })
            .catch(err => console.error("Conversations fetch failed", err));
        }

        // Call fetchConversations on page load and every 2 seconds
        window.addEventListener('load', () => {
            fetchConversations();
        });
        setInterval(fetchConversations, 2000);
        function refreshConversationStatus() {
            const dots = document.querySelectorAll("[id^='status-dot-']");
            
            dots.forEach(dot => {
                const userId = dot.dataset.userId;
                fetch("get_status.php?id=" + userId)
                    .then(res => res.text())
                    .then(status => {
                        // Green if Active Now, grey otherwise
                        if (status.toLowerCase().includes("active now")) {
                            dot.style.backgroundColor = "#34d399"; // emerald-400
                        } else {
                            dot.style.backgroundColor = "#9ca3af"; // gray-400
                        }
                    })
                    .catch(err => console.error("Error fetching status for user " + userId, err));
            });
        }

        // refresh every 30 seconds
        setInterval(refreshConversationStatus, 30000);
        refreshConversationStatus(); // run immediately

        <?php if ($selected_user_id): ?>
            let lastId = <?php echo $last_message_id; ?>;
            const userId = <?php echo $selected_user_id; ?>;
            const messagesContainer = document.getElementById('messagesContainer');

            // Scroll to bottom on initial load
            window.addEventListener('load', () => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            });

            // Scroll to bottom when keyboard opens (mobile)
            document.getElementById('messageInput').addEventListener('focus', () => {
                setTimeout(() => {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }, 300);
            });

            // Function to close all context menus
            function closeAllContextMenus() {
                document.querySelectorAll('.context-menu').forEach(menu => menu.remove());
            }

            // Function to show context menu
            function showContextMenu(messageDiv, x, y) {
                closeAllContextMenus();
                const msgId = messageDiv.getAttribute('data-msg-id');
                const menu = document.createElement('div');
                menu.className = 'context-menu';
                menu.style.left = `${x}px`;
                menu.style.top = `${y}px`;
                menu.innerHTML = `
                    <button class="delete-btn" data-msg-id="${msgId}">Delete</button>
                `;
                document.body.appendChild(menu);

                // Handle delete action
                menu.querySelector('.delete-btn').addEventListener('click', () => {
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            action: 'delete',
                            message_id: msgId
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            messageDiv.parentElement.remove();
                            fetchConversations(); // Update conversation list
                        } else {
                            alert(data.error || 'Failed to delete message');
                        }
                    });
                    closeAllContextMenus();
                });

            }

            // Handle three dots click
            messagesContainer.addEventListener('click', (e) => {
                if (e.target.closest('.three-dots')) {
                    const messageDiv = e.target.closest('[data-msg-id]');
                    const rect = messageDiv.getBoundingClientRect();
                    showContextMenu(messageDiv, rect.left - 100, rect.top);
                }
            });

            // Handle right-click
            messagesContainer.addEventListener('contextmenu', (e) => {
                const messageDiv = e.target.closest('[data-msg-id]');
                if (messageDiv && messageDiv.querySelector('.three-dots')) {
                    e.preventDefault();
                    showContextMenu(messageDiv, e.clientX, e.clientY);
                }
            });

            // Close context menu when clicking elsewhere
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.context-menu') && !e.target.closest('.three-dots')) {
                    closeAllContextMenus();
                }
            });

            function fetchMessages() {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({action: 'get', selected_user_id: userId, last_id: lastId})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Append new messages
                        if (data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                const div = document.createElement('div');
                                div.className = `flex ${msg.sender_id == <?php echo $user_id; ?> ? 'justify-end' : 'justify-start'} mb-3 relative message`;
                                div.innerHTML = `
                                    <div class="message-bubble ${msg.sender_id == <?php echo $user_id; ?>
                                    ? 'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white' 
                                    : 'bg-white text-gray-800 border border-gray-200'} 
                                    max-w-[80%] sm:max-w-[60%] break-words break-all px-4 py-3 rounded-2xl shadow-md relative"
                                    data-msg-id="${msg.id}">
                                        ${msg.sender_id == <?php echo $user_id; ?> ? 
                                            '<div class="three-dots"><i class="fas fa-ellipsis-v text-white/70"></i></div>' : ''}
                                        <p class="message-text leading-relaxed">${msg.message}</p>
                                        <div class="flex items-center justify-between mt-2 text-xs opacity-75">
                                            <span>${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })}</span>
                                            ${msg.sender_id == <?php echo $user_id; ?> 
                                                ? `<span class="status-text ml-2">
                                                    ${msg.is_read == 1 
                                                        ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" 
                                                                class="w-4 h-4 inline-block text-blue-300">
                                                                <path d="M1 13l4 4L15 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                                                <path d="M9 13l4 4L23 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                                            </svg>` 
                                                        : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" 
                                                                class="w-4 h-4 inline-block text-white/50">
                                                                <path d="M1 13l4 4L15 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                                                <path d="M9 13l4 4L23 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                                            </svg>`}
                                                </span>` 
                                                : ''}
                                        </div>
                                    </div>
                                `;
                                messagesContainer.appendChild(div);
                                lastId = msg.id;
                            });

                            requestAnimationFrame(() => {
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            });
                        }

                        // Update read statuses
                        if (data.read_ids) {
                            data.read_ids.forEach(r => {
                                const msgDiv = document.querySelector(`[data-msg-id='${r.id}'] .status-text`);
                                if (msgDiv) {
                                    msgDiv.innerHTML = `

                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                             class="w-4 h-4 inline-block text-blue-300">
                                            <path d="M1 13l4 4L15 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                            <path d="M9 13l4 4L23 7" stroke="currentColor" stroke-width="2" fill="none"/>
                                        </svg>` 
                                }
                            });
                        }
                    }
                })
                .catch(err => console.error("Messages fetch failed", err));
            }

            document.getElementById('sendBtn').addEventListener('click', () => {
                const message = document.getElementById('messageInput').value.trim();
                if (!message) return;
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({action: 'send', receiver_id: userId, message: message})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('messageInput').value = '';
                        fetchMessages();
                    }
                });
            });

            document.getElementById('messageInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    document.getElementById('sendBtn').click();
                }
            });

            // Poll for new messages
            setInterval(fetchMessages, 2000);
        <?php endif; ?>
    </script>
</body>
</html>
<?php include '../includes/footer.php'; ?>
