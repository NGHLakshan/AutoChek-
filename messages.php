<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_role = $_POST['receiver_role'];
    $receiver_id = intval($_POST['receiver_id']);
    $message_body = trim($_POST['message_body']);
    
    if (!empty($message_body)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_role, sender_id, receiver_role, receiver_id, message_body) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisis", $role, $user_id, $receiver_role, $receiver_id, $message_body);
        $stmt->execute();
        
        // Redirect to avoid form resubmission
        header("Location: messages.php?chat_role=$receiver_role&chat_id=$receiver_id");
        exit;
    }
}

// Mark messages as read when viewing a conversation
if (isset($_GET['chat_role']) && isset($_GET['chat_id'])) {
    $chat_role = $_GET['chat_role'];
    $chat_id = intval($_GET['chat_id']);
    
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_role = ? AND receiver_id = ? AND sender_role = ? AND sender_id = ?");
    $stmt->bind_param("sisi", $role, $user_id, $chat_role, $chat_id);
    $stmt->execute();
}

// Get list of conversations (distinct users I've messaged with)
function getConversations($conn, $role, $user_id) {
    $conversations = [];
    
    // Get all unique users I've sent messages to or received messages from
    $sql = "SELECT DISTINCT 
                CASE 
                    WHEN sender_role = ? AND sender_id = ? THEN receiver_role
                    ELSE sender_role
                END as other_role,
                CASE 
                    WHEN sender_role = ? AND sender_id = ? THEN receiver_id
                    ELSE sender_id
                END as other_id,
                MAX(created_at) as last_message_time
            FROM messages 
            WHERE (sender_role = ? AND sender_id = ?) 
               OR (receiver_role = ? AND receiver_id = ?)
            GROUP BY other_role, other_id
            ORDER BY last_message_time DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisisisi", $role, $user_id, $role, $user_id, $role, $user_id, $role, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $other_role = $row['other_role'];
        $other_id = $row['other_id'];
        
        // Fetch the other user's name and photo
        $name = "Unknown";
        $photo = "";
        if ($other_role == 'buyer') {
            $user_res = $conn->query("SELECT name, profile_photo FROM buyer WHERE buyer_id = $other_id");
            if ($user_res && $user_res->num_rows > 0) {
                $row_u = $user_res->fetch_assoc();
                $name = $row_u['name'];
                $photo = $row_u['profile_photo'];
            }
        } elseif ($other_role == 'expert') {
            $user_res = $conn->query("SELECT name, profile_photo FROM expert WHERE expert_id = $other_id");
            if ($user_res && $user_res->num_rows > 0) {
                $row_u = $user_res->fetch_assoc();
                $name = $row_u['name'];
                $photo = $row_u['profile_photo'];
            }
        } elseif ($other_role == 'admin') {
            $name = "Admin";
        }
        
        // Count unread messages from this user
        $unread_stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE sender_role = ? AND sender_id = ? AND receiver_role = ? AND receiver_id = ? AND is_read = 0");
        $unread_stmt->bind_param("sisi", $other_role, $other_id, $role, $user_id);
        $unread_stmt->execute();
        $unread = $unread_stmt->get_result()->fetch_assoc()['unread'];
        
        $conversations[] = [
            'role' => $other_role,
            'id' => $other_id,
            'name' => $name,
            'photo' => $photo,
            'unread' => $unread,
            'last_time' => $row['last_message_time']
        ];
    }
    
    return $conversations;
}

// Get messages for a specific conversation
function getMessages($conn, $role, $user_id, $other_role, $other_id) {
    $stmt = $conn->prepare("SELECT * FROM messages 
                            WHERE (sender_role = ? AND sender_id = ? AND receiver_role = ? AND receiver_id = ?)
                               OR (sender_role = ? AND sender_id = ? AND receiver_role = ? AND receiver_id = ?)
                            ORDER BY created_at ASC");
    $stmt->bind_param("sisisisi", $role, $user_id, $other_role, $other_id, $other_role, $other_id, $role, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

$conversations = getConversations($conn, $role, $user_id);
$active_chat = null;
$messages = null;
$chat_user_name = "";

// If viewing a specific conversation
if (isset($_GET['chat_role']) && isset($_GET['chat_id'])) {
    $chat_role = $_GET['chat_role'];
    $chat_id = intval($_GET['chat_id']);
    
    $messages = getMessages($conn, $role, $user_id, $chat_role, $chat_id);
    
    // Get the chat user's name and photo
    $chat_user_photo = "";
    if ($chat_role == 'buyer') {
        $res = $conn->query("SELECT name, profile_photo FROM buyer WHERE buyer_id = $chat_id");
        if ($res && $res->num_rows > 0) {
            $u = $res->fetch_assoc();
            $chat_user_name = $u['name'];
            $chat_user_photo = $u['profile_photo'];
        }
    } elseif ($chat_role == 'expert') {
        $res = $conn->query("SELECT name, profile_photo FROM expert WHERE expert_id = $chat_id");
        if ($res && $res->num_rows > 0) {
            $u = $res->fetch_assoc();
            $chat_user_name = $u['name'];
            $chat_user_photo = $u['profile_photo'];
        }
    } elseif ($chat_role == 'admin') {
        $chat_user_name = "Admin";
    }
    
    $active_chat = ['role' => $chat_role, 'id' => $chat_id, 'photo' => $chat_user_photo];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            background: #f8fafc;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Prevent body scroll, handle in chat container */
        }

        .main-content-wrapper {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .messages-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            flex: 1;
            overflow: hidden;
            height: calc(100vh - 140px); /* Adjust based on header + padding */
        }
        
        /* Sidebar */
        .conversations-sidebar {
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .sidebar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .conversation-item {
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 4px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            gap: 12px;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
        
        .conversation-item:hover {
            background: #f8fafc;
        }
        
        .conversation-item.active {
            background: #eff6ff;
            /* border-left: 3px solid #2563eb; */
        }
        
        .user-avatar-small {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #64748b;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
        }
        
        .conversation-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .last-active {
            font-size: 0.7rem;
            color: #94a3b8;
        }

        .conversation-role {
            font-size: 0.75rem;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 0.7rem;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.25);
        }
        
        /* Chat Area */
        .chat-area {
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            z-index: 10;
        }
        
        .chat-user-info h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .chat-user-role {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #f8fafc;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
        }
        
        .message-wrapper {
            max-width: 65%;
            display: flex;
            flex-direction: column;
        }
        
        .message-sent {
            align-self: flex-end;
            align-items: flex-end;
        }
        
        .message-received {
            align-self: flex-start;
            align-items: flex-start;
        }
        
        .message-bubble {
            padding: 12px 16px;
            border-radius: 12px;
            word-wrap: break-word;
            font-size: 0.95rem;
            line-height: 1.5;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            position: relative;
        }
        
        .message-sent .message-bubble {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 12px 12px 0 12px;
        }
        
        .message-received .message-bubble {
            background: white;
            color: var(--text-dark);
            border: 1px solid #e2e8f0;
            border-radius: 12px 12px 12px 0;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 4px;
            padding: 0 4px;
        }
        
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            background: white;
        }
        
        .chat-input-form {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            background: #f1f5f9;
            padding: 8px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .chat-input-form:focus-within {
            background: white;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .chat-input-form textarea {
            flex: 1;
            padding: 8px 10px;
            border: none;
            background: transparent;
            resize: none;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            max-height: 100px;
            outline: none;
        }
        
        .btn-send {
            background: #10b981;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .btn-send:hover {
            background: #059669;
            transform: scale(1.05);
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
            text-align: center;
            padding: 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #e2e8f0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--text-dark);
            margin: 0 0 8px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .page-title { display: none; }
            .main-content-wrapper { padding: 0; height: calc(100vh - 60px); }
            .messages-layout { border-radius: 0; border: none; height: 100%; }
            
            .messages-layout.chat-active .conversations-sidebar {
                display: none;
            }
            
            .messages-layout:not(.chat-active) .chat-area {
                display: none;
            }
            
            .messages-layout.chat-active {
                grid-template-columns: 1fr;
            }
            
            .conversations-sidebar { width: 100%; }
            
            .back-btn {
                display: flex !important;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: #f1f5f9;
                color: #64748b;
                text-decoration: none;
                margin-right: 10px;
            }
        }
        
        .back-btn { display: none; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>
    
    <div class="main-content-wrapper">
        <h2 class="page-title"><i class="ph ph-chat-circle-text"></i> Messages</h2>
        
        <div class="messages-layout <?php echo $active_chat ? 'chat-active' : ''; ?>">
            
            <!-- Sidebar -->
            <div class="conversations-sidebar">
                <div class="sidebar-header">
                    <div class="sidebar-title">Conversations</div>
                </div>
                
                <div class="conversations-list">
                    <?php if (count($conversations) > 0): ?>
                        <?php foreach ($conversations as $conv): ?>
                            <?php 
                                $isActive = ($active_chat && $active_chat['role'] == $conv['role'] && $active_chat['id'] == $conv['id']);
                            ?>
                            <a href="messages.php?chat_role=<?php echo $conv['role']; ?>&chat_id=<?php echo $conv['id']; ?>" class="conversation-item <?php echo $isActive ? 'active' : ''; ?>">
                                <div class="user-avatar-small">
                                    <?php if (!empty($conv['photo'])): ?>
                                        <img src="uploads/profiles/<?php echo $conv['photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="ph ph-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-top">
                                        <div class="conversation-name"><?php echo htmlspecialchars($conv['name']); ?></div>
                                        <span class="last-active"><?php echo date('M j', strtotime($conv['last_time'])); ?></span>
                                    </div>
                                    <div class="conversation-role">
                                        <?php echo ucfirst($conv['role']); ?>
                                        <?php if ($conv['unread'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conv['unread']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                            <i class="ph ph-chats-circle" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            <p style="font-size: 0.9rem;">No conversations yet.<br>Book an expert to start chatting!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="chat-area">
                <?php if ($active_chat): ?>
                    <div class="chat-header">
                        <a href="messages.php" class="back-btn"><i class="ph ph-arrow-left"></i></a>
                        <div class="user-avatar-small" style="width: 40px; height: 40px;">
                            <?php if (!empty($active_chat['photo'])): ?>
                                <img src="uploads/profiles/<?php echo $active_chat['photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="ph ph-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="chat-user-info">
                            <h3><?php echo htmlspecialchars($chat_user_name); ?></h3>
                            <div class="chat-user-role"><?php echo ucfirst($active_chat['role']); ?></div>
                        </div>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <?php if ($messages && $messages->num_rows > 0): ?>
                            <?php while ($msg = $messages->fetch_assoc()): ?>
                                <?php 
                                $is_sent = ($msg['sender_role'] == $role && $msg['sender_id'] == $user_id);
                                ?>
                                <div class="message-wrapper <?php echo $is_sent ? 'message-sent' : 'message-received'; ?>">
                                    <div class="message-bubble">
                                        <?php echo nl2br(htmlspecialchars($msg['message_body'])); ?>
                                    </div>
                                    <div class="message-time">
                                        <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                        <?php if ($is_sent): ?>
                                            <i class="ph-fill ph-check-circle" style="font-size: 0.8rem; margin-left: 2px;"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="ph ph-hand-waving" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 10px;"></i>
                                <p>Say hello to <strong><?php echo htmlspecialchars($chat_user_name); ?></strong>! 👋</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input-area">
                        <form method="POST" class="chat-input-form">
                            <input type="hidden" name="receiver_role" value="<?php echo $active_chat['role']; ?>">
                            <input type="hidden" name="receiver_id" value="<?php echo $active_chat['id']; ?>">
                            <textarea name="message_body" rows="1" placeholder="Type your message..." required style="height: auto;" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                            <button type="submit" name="send_message" class="btn-send">
                                <i class="ph-fill ph-paper-plane-right"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="ph ph-chat-centered-text"></i>
                        <h3>Select a Conversation</h3>
                        <p>Choose a contact from the left sidebar to start messaging.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>
