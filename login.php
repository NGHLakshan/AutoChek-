<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email']; // Using 'email' field for both email and username input
    $password = $_POST['password'];

    // 1. Check Admin Table
    $stmt = $conn->prepare("SELECT admin_id, username, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['admin_id'];
            $_SESSION['name'] = $row['username'];
            $_SESSION['role'] = 'admin';
            header("Location: dashboard.php");
            exit;
        }
    }
    $stmt->close();

    // 2. Check Expert Table
    $stmt = $conn->prepare("SELECT expert_id, name, password, verified FROM expert WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Check if expert is verified
            if ($row['verified'] == 0) {
                $error = "Your expert account is pending admin approval. Please wait for verification before logging in.";
                $stmt->close();
            } else {
                $_SESSION['user_id'] = $row['expert_id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = 'expert';
                $_SESSION['verified'] = $row['verified'];
                header("Location: dashboard.php");
                exit;
            }
        }
    }
    $stmt->close();

    // 3. Check Buyer Table
    $stmt = $conn->prepare("SELECT buyer_id, name, password FROM buyer WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['buyer_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = 'buyer';
            header("Location: dashboard.php");
            exit;
        }
    }
    $stmt->close();

    $error = "Invalid email/username or password";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .error-msg { color: #ef4444; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="login-container">
            <h2 style="text-align: center; margin-top: 0;">Welcome Back</h2>
            
            <?php if (isset($_GET['registered'])): ?>
                <p style="color: #166534; background: #dcfce7; padding: 10px; border-radius: 6px; font-size: 14px;">Account created! Please login.</p>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="text" name="email" required placeholder="Enter email or admin username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            <p style="text-align: center; margin-top: 15px; font-size: 14px;">
                <a href="reset_expert_password.php" style="color: #2563eb;">Forgot Password?</a>
            </p>
            <p style="text-align: center; margin-top: 20px; font-size: 14px;">
                New here? <a href="register_buyer.php">Register as Buyer</a> or <a href="register_expert.php">Expert</a>
            </p>
        </div>
    </div>

</body>
</html>
