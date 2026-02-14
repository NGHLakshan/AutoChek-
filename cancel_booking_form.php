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
        body {
            background-color: #f8fafc;
        }
        .cancel-card {
            max-width: 500px;
            margin: 60px auto;
            padding: 40px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }
        .booking-summary {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            border: 1px solid #e2e8f0;
        }
        .booking-summary p { margin: 8px 0; color: #334155; }
        .booking-summary strong { color: #0f172a; min-width: 100px; display: inline-block; }
        
        h2 {
            margin-top: 0;
            color: #0f172a;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        label { display: block; margin-bottom: 10px; font-weight: 600; color: #334155; }
        
        textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: #0f172a;
            margin-bottom: 25px;
            resize: vertical;
            min-height: 120px;
            transition: all 0.2s;
        }
        
        textarea:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .btn-group { display: flex; gap: 15px; }
        
        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .btn-danger { 
            background: #ef4444; 
            color: white; 
            border: 1px solid #ef4444; 
            flex: 1;
        }
        .btn-danger:hover { 
            background: #dc2626; 
            border-color: #dc2626;
        }
        
        .btn-outline { 
            background: white; 
            color: #64748b; 
            border: 1px solid #cbd5e1; 
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .btn-outline:hover { 
            background: #f1f5f9; 
            color: #0f172a;
            border-color: #94a3b8;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="cancel-card">
            <h2>Cancel Booking</h2>
            <p style="color: #64748b; margin-bottom: 30px; line-height: 1.5;">Please provide a reason for canceling this booking. This helps us improve our service.</p>
            
            <div class="booking-summary">
                <p><strong>Booking ID:</strong> <?php echo $booking['booking_id']; ?></p>
                <p><strong><?php echo $other_role_label; ?>:</strong> <?php echo htmlspecialchars($booking['other_party_name']); ?></p>
                <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['vehicle_type']); ?></p>
                <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></p>
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
