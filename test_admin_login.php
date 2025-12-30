<?php
require_once 'db.php';

echo "<h2>Admin Login Debug</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }</style>";

// Step 1: Check database connection
echo "<div class='box'>";
echo "<h3>Step 1: Database Connection</h3>";
if ($conn) {
    echo "✅ Database connected successfully<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit;
}
echo "</div>";

// Step 2: Check if admin table exists
echo "<div class='box'>";
echo "<h3>Step 2: Admin Table Check</h3>";
$tables = $conn->query("SHOW TABLES LIKE 'admin'");
if ($tables->num_rows > 0) {
    echo "✅ Admin table exists<br>";
} else {
    echo "❌ Admin table does NOT exist. Run setup_db.php first!<br>";
    exit;
}
echo "</div>";

// Step 3: Check admin records
echo "<div class='box'>";
echo "<h3>Step 3: Admin Records</h3>";
$result = $conn->query("SELECT * FROM admin");
echo "Total admin accounts: " . $result->num_rows . "<br>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Username: " . htmlspecialchars($row['username']) . "<br>";
        echo "Password Hash: " . substr($row['password'], 0, 30) . "...<br>";
    }
} else {
    echo "⚠️ No admin accounts found!<br>";
}
echo "</div>";

// Step 4: Create/Update admin account
echo "<div class='box'>";
echo "<h3>Step 4: Creating Admin Account</h3>";

// Delete existing admin
$conn->query("DELETE FROM admin WHERE username = 'admin'");

// Create new admin
$password = "admin123";
$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
$username = "admin";
$stmt->bind_param("ss", $username, $hashed);

if ($stmt->execute()) {
    echo "✅ Admin account created<br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>admin123</strong><br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}
echo "</div>";

// Step 5: Test login
echo "<div class='box'>";
echo "<h3>Step 5: Test Login</h3>";

$test_username = "admin";
$test_password = "admin123";

$stmt = $conn->prepare("SELECT admin_id, username, password FROM admin WHERE username = ?");
$stmt->bind_param("s", $test_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    echo "✅ Admin account found in database<br>";
    $row = $result->fetch_assoc();
    
    if (password_verify($test_password, $row['password'])) {
        echo "✅ Password verification SUCCESSFUL<br>";
        echo "<div style='background: #dcfce7; padding: 15px; margin-top: 10px; border-radius: 5px;'>";
        echo "<strong>Login should work now!</strong><br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<a href='login.php' style='color: #2563eb; font-weight: bold;'>→ Go to Login Page</a>";
        echo "</div>";
    } else {
        echo "❌ Password verification FAILED<br>";
        echo "This means the password hash doesn't match.<br>";
    }
} else {
    echo "❌ Admin account NOT found in database<br>";
}
echo "</div>";

$conn->close();
?>
