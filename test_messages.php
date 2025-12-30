<?php
session_start();
require_once 'db.php';

echo "<h2>Messages Debug</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }</style>";

// Check if user is logged in
echo "<div class='box'>";
echo "<h3>Step 1: Session Check</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User is logged in<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
} else {
    echo "❌ User is NOT logged in<br>";
    echo "<a href='login.php'>Go to Login</a><br>";
}
echo "</div>";

// Check if messages table exists
echo "<div class='box'>";
echo "<h3>Step 2: Messages Table Check</h3>";
$tables = $conn->query("SHOW TABLES LIKE 'messages'");
if ($tables->num_rows > 0) {
    echo "✅ Messages table exists<br>";
    
    // Count messages
    $count = $conn->query("SELECT COUNT(*) as total FROM messages")->fetch_assoc()['total'];
    echo "Total messages in database: " . $count . "<br>";
} else {
    echo "❌ Messages table does NOT exist!<br>";
    echo "<strong>Solution:</strong> Run this URL:<br>";
    echo "<a href='update_schema_messages.php' style='color: #2563eb; font-weight: bold;'>update_schema_messages.php</a><br>";
}
echo "</div>";

// Test messages.php access
echo "<div class='box'>";
echo "<h3>Step 3: Messages Page Access</h3>";
if (file_exists('messages.php')) {
    echo "✅ messages.php file exists<br>";
    echo "<a href='messages.php' style='color: #2563eb; font-weight: bold;'>→ Open Messages Page</a><br>";
} else {
    echo "❌ messages.php file NOT found<br>";
}
echo "</div>";

// Check for PHP errors
echo "<div class='box'>";
echo "<h3>Step 4: Error Log</h3>";
echo "If messages.php shows a blank page, check your PHP error log or enable error display.<br>";
echo "Current error reporting: " . error_reporting() . "<br>";
echo "</div>";

$conn->close();
?>
