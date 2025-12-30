<?php
require_once 'db.php';

echo "<h2>Password Reset Tool for Experts</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #dcfce7; color: #166534; }</style>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    
    echo "<div class='box'>";
    
    // Check if expert exists
    $stmt = $conn->prepare("SELECT expert_id, name FROM expert WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $expert = $result->fetch_assoc();
        
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $update_stmt = $conn->prepare("UPDATE expert SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $hashed_password, $email);
        
        if ($update_stmt->execute()) {
            echo "<div class='box success'>";
            echo "<h3>✅ Password Reset Successful!</h3>";
            echo "<p>Expert: " . htmlspecialchars($expert['name']) . "</p>";
            echo "<p>Email: " . htmlspecialchars($email) . "</p>";
            echo "<p>New Password: <strong>" . htmlspecialchars($new_password) . "</strong></p>";
            echo "<p><a href='login.php' style='color: #166534; font-weight: bold;'>→ Go to Login Page</a></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Error updating password: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No expert account found with email: " . htmlspecialchars($email) . "</p>";
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Expert Password</title>
</head>
<body>
    <div class="box">
        <h3>Reset Your Expert Password</h3>
        <p>Use this tool to reset your password if you're having login issues.</p>
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label><strong>Email Address:</strong></label><br>
                <input type="email" name="email" required style="width: 300px; padding: 8px;" placeholder="suranga@gmail.com">
            </div>
            <div style="margin-bottom: 15px;">
                <label><strong>New Password:</strong></label><br>
                <input type="text" name="new_password" required style="width: 300px; padding: 8px;" placeholder="Enter new password">
            </div>
            <button type="submit" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">Reset Password</button>
        </form>
    </div>
    
    <div class="box" style="background: #fef3c7; border-left: 4px solid #f59e0b;">
        <h4>⚠️ Note:</h4>
        <p>This will reset your password in the database. Make sure to remember your new password!</p>
    </div>
</body>
</html>
