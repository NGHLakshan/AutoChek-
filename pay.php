<?php
session_start();
require_once 'db.php';

// Check if buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    die("Access Denied");
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$buyer_id = $_SESSION['user_id'];
$message = "";

// Security: Check if booking is valid and approved (or completed, allowing payment after?)
// Usually payment is done before inspection (Approved state) or after (Completed state)?
// Let's assume payment is done when status is 'approved' to confirm the slot.
$check = $conn->query("SELECT * FROM booking WHERE booking_id = $booking_id AND buyer_id = $buyer_id");
if ($check->num_rows == 0) {
    die("Booking not found.");
}
$booking = $check->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $booking['package_price']; 
    $method = $_POST['method']; // Card, Cash, etc.

    // Insert into payment table
    // payment_id, booking_id, buyer_id, amount, method, payment_date
    $stmt = $conn->prepare("INSERT INTO payment (booking_id, buyer_id, amount, method, payment_date) VALUES (?, ?, ?, ?, CURRENT_DATE)");
    $stmt->bind_param("iids", $booking_id, $buyer_id, $amount, $method);

    if ($stmt->execute()) {
        $message = "<div class='alert-success'>Payment Successful! Date confirmed. <a href='dashboard.php'>Back to Dashboard</a></div>";
        // Optionally update booking status to something like 'confirmed' if needed, but 'approved' is fine.
    } else {
        $message = "<div class='alert-error'>Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Inspection Fee | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .price-tag { font-size: 2rem; font-weight: 700; color: #1e293b; text-align: center; margin: 20px 0; }
        .payment-methods { display: flex; gap: 10px; justify-content: center; margin-bottom: 20px; }
        .method-btn { padding: 10px 20px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; background: white; }
        .method-btn.active { border-color: var(--primary-color); background: #e0f2fe; color: var(--primary-color); }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2 style="text-align: center;">Make Payment</h2>
            <p style="text-align: center; color: #64748b; margin-bottom: 5px;">
                <strong><?php echo htmlspecialchars($booking['package_name'] ?? 'Standard'); ?> Package</strong> for 
                <strong><?php echo htmlspecialchars($booking['vehicle_type']); ?></strong>
            </p>
            <p style="text-align: center; color: #64748b; margin-bottom: 10px;">
                Mode: <span class="badge" style="background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($booking['service_type'] ?? 'Physical'); ?></span>
            </p>
            
            <div class="price-tag">LKR <?php echo number_format($booking['package_price'], 2); ?></div>

            <?php echo $message; ?>

            <?php if (empty($message)): ?>
            <form method="POST">
                <div class="form-group">
                    <label style="display:block; margin-bottom:10px;">Select Payment Method</label>
                    <select name="method" style="width:100%; padding: 10px; border:1px solid #ccc; border-radius:6px;">
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash at Location">Cash at Location</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Card Number (Mock)</label>
                    <input type="text" placeholder="0000 0000 0000 0000" disabled style="background: #f1f5f9; width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:6px;">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Pay Now</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
