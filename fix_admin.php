<?php
require_once 'db.php';

echo "<h2>Admin Account Check & Fix</h2>";

// Check if admin table exists
$check_table = $conn->query("SHOW TABLES LIKE 'admin'");
if ($check_table->num_rows == 0) {
    echo "<p style='color: red;'>❌ Admin table does not exist! Run setup_db.php first.</p>";
    exit;
}

// Check if admin account exists
$result = $conn->query("SELECT * FROM admin WHERE username = 'admin'");

if ($result->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ Admin account exists. Deleting old account...</p>";
    $conn->query("DELETE FROM admin WHERE username = 'admin'");
}

// Create fresh admin account
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $admin_password);
$username = "admin";

if ($stmt->execute()) {
    echo "<p style='color: green;'>✅ Admin account created successfully!</p>";
    echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3>Login Credentials:</h3>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><a href='login.php' style='color: #2563eb; font-weight: 600;'>→ Go to Login Page</a></p>";
    echo "</div>";
} else {
    echo "<p style='color: red;'>❌ Error creating admin account: " . $conn->error . "</p>";
}

// Verify the account
echo "<hr>";
echo "<h3>Verification:</h3>";
$verify = $conn->query("SELECT username FROM admin WHERE username = 'admin'");
if ($verify->num_rows > 0) {
    echo "<p style='color: green;'>✅ Admin account verified in database</p>";
} else {
    echo "<p style='color: red;'>❌ Admin account not found in database</p>";
}

$conn->close();
?>
