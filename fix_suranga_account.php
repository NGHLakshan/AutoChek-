<?php
require_once 'db.php';

echo "<h2>Expert Account Fixer</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #dcfce7; color: #166534; } .error { background: #fee2e2; color: #991b1b; }</style>";

// For suranga@gmail.com specifically
$email = "suranga@gmail.com";
$password = "12345";

echo "<div class='box'>";
echo "<h3>Fixing account for: " . $email . "</h3>";

// Check if account exists
$check = $conn->query("SELECT expert_id, name, verified, password FROM expert WHERE email = '$email'");

if ($check->num_rows > 0) {
    $expert = $check->fetch_assoc();
    
    echo "<p><strong>Account Found:</strong></p>";
    echo "<p>Expert ID: " . $expert['expert_id'] . "</p>";
    echo "<p>Name: " . htmlspecialchars($expert['name']) . "</p>";
    echo "<p>Verified: " . ($expert['verified'] ? 'Yes ✅' : 'No ❌') . "</p>";
    echo "<p>Current password hash: " . substr($expert['password'], 0, 30) . "...</p>";
    
    // Fix 1: Make sure account is verified
    if ($expert['verified'] == 0) {
        echo "<p style='color: orange;'>⚠️ Account not verified. Fixing...</p>";
        $conn->query("UPDATE expert SET verified = 1 WHERE email = '$email'");
        echo "<p style='color: green;'>✅ Account verified!</p>";
    }
    
    // Fix 2: Reset password with proper hash
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    $conn->query("UPDATE expert SET password = '$new_hash' WHERE email = '$email'");
    
    echo "<div class='box success'>";
    echo "<h4>✅ Account Fixed!</h4>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<p>Email: <code>suranga@gmail.com</code></p>";
    echo "<p>Password: <code>12345</code></p>";
    echo "<p>Verified: <strong>YES</strong></p>";
    echo "<p><a href='login.php' style='color: #166534; font-weight: bold; font-size: 1.2rem;'>→ Try Logging In Now</a></p>";
    echo "</div>";
    
    // Test the password
    echo "<h4>Testing Password:</h4>";
    if (password_verify($password, $new_hash)) {
        echo "<p style='color: green;'>✅ Password verification test: PASSED</p>";
    } else {
        echo "<p style='color: red;'>❌ Password verification test: FAILED</p>";
    }
    
} else {
    echo "<div class='box error'>";
    echo "<p>❌ No expert account found with email: $email</p>";
    echo "<p>Please register first at <a href='register_expert.php'>register_expert.php</a></p>";
    echo "</div>";
}

echo "</div>";

$conn->close();
?>
