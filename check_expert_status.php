<?php
require_once 'db.php';

echo "<h2>Expert Account Status Checker</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #dcfce7; color: #166534; } .warning { background: #fef3c7; color: #92400e; } .error { background: #fee2e2; color: #991b1b; }</style>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    echo "<div class='box'>";
    echo "<h3>Checking Expert Account: " . htmlspecialchars($email) . "</h3>";
    
    $stmt = $conn->prepare("SELECT expert_id, name, email, verified FROM expert WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $expert = $result->fetch_assoc();
        
        echo "<p><strong>Expert Found!</strong></p>";
        echo "<p>Expert ID: " . $expert['expert_id'] . "</p>";
        echo "<p>Name: " . htmlspecialchars($expert['name']) . "</p>";
        echo "<p>Email: " . htmlspecialchars($expert['email']) . "</p>";
        
        if ($expert['verified'] == 1) {
            echo "<div class='box success'>";
            echo "<h4>✅ Account Status: VERIFIED</h4>";
            echo "<p>Your account is approved. You should be able to log in.</p>";
            echo "<p>If login still fails, use <a href='test_login.php'>test_login.php</a> to check your password.</p>";
            echo "</div>";
        } else {
            echo "<div class='box warning'>";
            echo "<h4>⏳ Account Status: WAITING FOR APPROVAL</h4>";
            echo "<p><strong>This is why you can't log in!</strong></p>";
            echo "<p>Expert accounts must be approved by an admin before you can log in.</p>";
            echo "<p><strong>Solution:</strong></p>";
            echo "<ol>";
            echo "<li>Log in as admin (username: <code>admin</code>, password: <code>admin123</code>)</li>";
            echo "<li>Go to the Admin Dashboard</li>";
            echo "<li>Click 'Approve' on your expert account</li>";
            echo "<li>Then try logging in as expert again</li>";
            echo "</ol>";
            echo "<p><a href='login.php' style='color: #2563eb; font-weight: bold;'>→ Go to Login Page</a></p>";
            echo "</div>";
        }
    } else {
        echo "<div class='box error'>";
        echo "<h4>❌ Expert Account Not Found</h4>";
        echo "<p>No expert account exists with email: " . htmlspecialchars($email) . "</p>";
        echo "<p>Please <a href='register_expert.php'>register as an expert</a> first.</p>";
        echo "</div>";
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expert Account Status</title>
</head>
<body>
    <div class="box">
        <h3>Check Your Expert Account Status</h3>
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label>Enter your expert email:</label><br>
                <input type="email" name="email" required style="width: 300px; padding: 8px;">
            </div>
            <button type="submit" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">Check Status</button>
        </form>
    </div>
    
    <div class="box" style="background: #eff6ff; border-left: 4px solid #2563eb;">
        <h4>📋 How Expert Registration Works:</h4>
        <ol>
            <li><strong>Register</strong> - Create your expert account</li>
            <li><strong>Wait for Approval</strong> - Admin must approve your account (verified = 0)</li>
            <li><strong>Get Approved</strong> - Admin clicks "Approve" (verified = 1)</li>
            <li><strong>Login</strong> - Now you can log in!</li>
        </ol>
    </div>
</body>
</html>
