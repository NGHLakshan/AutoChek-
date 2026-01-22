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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .messages-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: calc(100vh - 200px);
            margin-top: 20px;
        }
        
        .conversations-list {
            background: white;
            border-radius: 12px;
            padding: 20px;
            overflow-y: auto;
            border: 1px solid #f1f5f9;
        }
        
        .conversation-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background 0.2s;
            border: 1px solid #f1f5f9;
        }
        
        .conversation-item:hover {
            background: #f8fafc;
        }
        
        .conversation-item.active {
            background: #eff6ff;
            border-color: #2563eb;
        }
        
        .conversation-name {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .conversation-role {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .chat-container {
            background: white;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            border: 1px solid #f1f5f9;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            word-wrap: break-word;
        }
        
        .message-sent {
            align-self: flex-end;
            background: #2563eb;
            color: white;
        }
        
        .message-received {
            align-self: flex-start;
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #f1f5f9;
        }
        
        .chat-input-form {
            display: flex;
            gap: 10px;
        }
        
        .chat-input-form textarea {
            flex: 1;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            resize: none;
            font-family: 'Inter', sans-serif;
        }
        
        .chat-input-form button {
            padding: 12px 24px;
        }
        
        .empty-state {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>
    
    <style>
        @media (max-width: 768px) {
            .messages-container {
                display: flex !important;
                flex-direction: column !important;
                height: auto !important;
            }
            .conversations-list {
                <?php if ($active_chat): ?>display: none;<?php endif; ?>
                width: 100%;
            }
            .chat-container {
                <?php if (!$active_chat): ?>display: none;<?php endif; ?>
                width: 100%;
                height: 70vh;
            }
            .chat-header {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .back-btn {
                display: inline-flex !important;
                align-items: center;
                padding: 8px;
                background: #f1f5f9;
                border-radius: 50%;
                color: #64748b;
                text-decoration: none;
            }
        }
        .back-btn { display: none; }
    </style>

    <div class="container">
        <h2>Messages</h2>
        
        <div class="messages-container">
            <!-- Conversations List -->
            <div class="conversations-list">
                <h3 class="section-title">Conversations</h3>
                <?php if (count($conversations) > 0): ?>
                    <?php foreach ($conversations as $conv): ?>
                        <a href="messages.php?chat_role=<?php echo $conv['role']; ?>&chat_id=<?php echo $conv['id']; ?>" 
                           class="conversation-item <?php echo ($active_chat && $active_chat['role'] == $conv['role'] && $active_chat['id'] == $conv['id']) ? 'active' : ''; ?>"
                           style="text-decoration: none; color: inherit; display: block;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div class="avatar" style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                    <?php if (!empty($conv['photo'])): ?>
                                        <img src="uploads/profiles/<?php echo $conv['photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        👤
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div class="conversation-name">
                                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($conv['name']); ?></span>
                                        <?php if ($conv['unread'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conv['unread']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-role">
                                        <?php echo ucfirst($conv['role']); ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #94a3b8; text-align: center; margin-top: 20px;">No conversations yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Chat Area -->
            <div class="chat-container">
                <?php if ($active_chat): ?>
                    <div class="chat-header" style="display: flex; align-items: center; gap: 15px;">
                        <a href="messages.php" class="back-btn"><i class="ph ph-arrow-left"></i></a>
                        <div class="avatar" style="width: 45px; height: 45px; border-radius: 50%; background: #f1f5f9; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($active_chat['photo'])): ?>
                                <img src="uploads/profiles/<?php echo $active_chat['photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                👤
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 style="margin: 0; line-height: 1.2;"><?php echo htmlspecialchars($chat_user_name); ?></h3>
                            <small style="color: #64748b;"><?php echo ucfirst($active_chat['role']); ?></small>
                        </div>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <?php if ($messages && $messages->num_rows > 0): ?>
                            <?php while ($msg = $messages->fetch_assoc()): ?>
                                <?php 
                                $is_sent = ($msg['sender_role'] == $role && $msg['sender_id'] == $user_id);
                                ?>
                                <div class="message-bubble <?php echo $is_sent ? 'message-sent' : 'message-received'; ?>">
                                    <div><?php echo nl2br(htmlspecialchars($msg['message_body'])); ?></div>
                                    <div class="message-time">
                                        <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">Start the conversation!</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input-area">
                        <form method="POST" class="chat-input-form">
                            <input type="hidden" name="receiver_role" value="<?php echo $active_chat['role']; ?>">
                            <input type="hidden" name="receiver_id" value="<?php echo $active_chat['id']; ?>">
                            <textarea name="message_body" rows="2" placeholder="Type your message..." required></textarea>
                            <button type="submit" name="send_message" class="btn btn-primary">Send</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        Select a conversation to start messaging
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom of messages
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>

</body>
</html>
