<?php
require_once 'db.php';

echo "<h2>Login Debug Tool</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }</style>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    echo "<div class='box'>";
    echo "<h3>Testing Login for: " . htmlspecialchars($email) . "</h3>";
    
    // Check Buyer
    $stmt = $conn->prepare("SELECT buyer_id, name, password FROM buyer WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<strong>Found in BUYER table</strong><br>";
        echo "Buyer ID: " . $row['buyer_id'] . "<br>";
        echo "Name: " . htmlspecialchars($row['name']) . "<br>";
        echo "Password Hash (first 30 chars): " . substr($row['password'], 0, 30) . "...<br>";
        
        if (password_verify($password, $row['password'])) {
            echo "<p style='color: green; font-weight: bold;'>✅ PASSWORD VERIFICATION SUCCESSFUL!</p>";
            echo "<p>Login should work. If it doesn't, there may be a session issue.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ PASSWORD VERIFICATION FAILED!</p>";
            echo "<p>The password you entered doesn't match the hash in the database.</p>";
            echo "<p>Try registering again or check if you're using the correct password.</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Not found in buyer table, checking expert...</p>";
        
        // Check Expert
        $stmt = $conn->prepare("SELECT expert_id, name, password FROM expert WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "<strong>Found in EXPERT table</strong><br>";
            echo "Expert ID: " . $row['expert_id'] . "<br>";
            echo "Name: " . htmlspecialchars($row['name']) . "<br>";
            echo "Password Hash (first 30 chars): " . substr($row['password'], 0, 30) . "...<br>";
            
            if (password_verify($password, $row['password'])) {
                echo "<p style='color: green; font-weight: bold;'>✅ PASSWORD VERIFICATION SUCCESSFUL!</p>";
                echo "<p>Login should work. If it doesn't, there may be a session issue.</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ PASSWORD VERIFICATION FAILED!</p>";
                echo "<p>The password you entered doesn't match the hash in the database.</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Account not found in buyer OR expert tables!</p>";
            echo "<p>Please register first.</p>";
        }
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Debug</title>
</head>
<body>
    <div class="box">
        <h3>Test Your Login Credentials</h3>
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label>Email:</label><br>
                <input type="text" name="email" required style="width: 300px; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Password:</label><br>
                <input type="password" name="password" required style="width: 300px; padding: 8px;">
            </div>
            <button type="submit" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">Test Login</button>
        </form>
    </div>
</body>
</html>
