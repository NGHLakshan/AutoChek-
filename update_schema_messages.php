<?php
require_once 'db.php';

echo "<h3>Adding Messages Table...</h3>";

// Create messages table
$sql = "CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_role ENUM('admin', 'buyer', 'expert') NOT NULL,
    sender_id INT NOT NULL,
    receiver_role ENUM('admin', 'buyer', 'expert') NOT NULL,
    receiver_id INT NOT NULL,
    message_body TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_receiver (receiver_role, receiver_id),
    INDEX idx_sender (sender_role, sender_id),
    INDEX idx_created (created_at)
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'messages' created successfully.<br>";
} else {
    echo "❌ Error creating table 'messages': " . $conn->error . "<br>";
}

echo "<p>Schema update complete!</p>";
$conn->close();
?>
