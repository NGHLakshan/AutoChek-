<?php
require_once 'db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $location = $_POST['location'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO buyer (name, email, phone, location, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $location, $password);

    if ($stmt->execute()) {
        header("Location: login.php?registered=1");
        exit;
    } else {
        $message = "<div class='alert-error'>Error: " . $stmt->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Registration | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-container {
            max-width: 400px;
            margin: 60px auto;
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
        .alert-error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2 style="text-align: center; margin-top: 0;">Create Buyer Account</h2>
            <?php echo $message; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required placeholder="07x xxxxxxx">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" required placeholder="City/Town">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Sign Up</button>
            </form>
            <p style="text-align: center; margin-top: 20px;">
                Already have an account? <a href="login.php">Login</a>
            </p>
        </div>
    </div>

</body>
</html>
