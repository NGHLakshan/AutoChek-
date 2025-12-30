<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
    die("Invalid Booking ID");
}

// Fetch booking details for reference
if ($role == 'buyer') {
    $sql = "SELECT b.*, e.name as other_party_name FROM booking b JOIN expert e ON b.expert_id = e.expert_id WHERE b.booking_id = $booking_id AND b.buyer_id = $user_id";
    $other_role_label = "Expert";
} else {
    $sql = "SELECT b.*, u.name as other_party_name FROM booking b JOIN buyer u ON b.buyer_id = u.buyer_id WHERE b.booking_id = $booking_id AND b.expert_id = $user_id";
    $other_role_label = "Buyer";
}

$result = $conn->query($sql);
if ($result->num_rows == 0) {
    die("Booking not found or access denied.");
}

$booking = $result->fetch_assoc();

// Prevent cancelling completed or already cancelled bookings
if ($booking['status'] == 'completed') {
    die("Cannot cancel a completed booking.");
}
if ($booking['status'] == 'cancelled') {
    die("Booking is already cancelled.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Booking | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cancel-card {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .booking-summary {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .booking-summary p { margin: 5px 0; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #1e293b; }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            margin-bottom: 20px;
            resize: vertical;
            min-height: 100px;
        }
        .btn-group { display: flex; gap: 10px; }
        .btn-danger { background: #ef4444; color: white; border: none; }
        .btn-danger:hover { background: #dc2626; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="cancel-card">
            <h2 style="margin-top: 0; color: #991b1b;">Cancel Booking</h2>
            <p style="color: #64748b; margin-bottom: 20px;">Please provide a reason for canceling this booking. This help us improve our service.</p>
            
            <div class="booking-summary">
                <p><strong>Booking ID:</strong> #<?php echo $booking['booking_id']; ?></p>
                <p><strong><?php echo $other_role_label; ?>:</strong> <?php echo htmlspecialchars($booking['other_party_name']); ?></p>
                <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['vehicle_type']); ?></p>
                <p><strong>Date:</strong> <?php echo $booking['booking_date']; ?></p>
            </div>

            <form action="cancel_booking.php" method="POST">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                
                <label for="reason">Cancellation Reason / Comment</label>
                <textarea name="reason" id="reason" placeholder="Explain why you are canceling..." required></textarea>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                    <a href="dashboard.php" class="btn btn-outline">Go Back</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
