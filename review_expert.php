<?php
session_start();
require_once 'db.php';

// Security: Must be Buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    die("Access Denied");
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$buyer_id = $_SESSION['user_id'];
$message = "";

// Security: Verify booking belongs to this buyer AND is completed
$check = $conn->query("SELECT * FROM booking WHERE booking_id = $booking_id AND buyer_id = $buyer_id AND status = 'completed'");
if ($check->num_rows == 0) {
    die("Booking not valid for review.");
}

// Get expert_id from booking
$booking = $check->fetch_assoc();
$expert_id = $booking['expert_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = floatval($_POST['rating']);
    $comment = $_POST['comment'];

    // Insert into review table
    // review_id, booking_id, buyer_id, expert_id, rating, comment, review_date
    $stmt = $conn->prepare("INSERT INTO review (booking_id, buyer_id, expert_id, rating, comment, review_date) VALUES (?, ?, ?, ?, ?, CURRENT_DATE)");
    $stmt->bind_param("iiids", $booking_id, $buyer_id, $expert_id, $rating, $comment);

    if ($stmt->execute()) {
        $message = "<div class='alert-success'>Thank you for your review! <a href='dashboard.php'>Back to Bookings</a></div>";
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
    <title>Leave a Review | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 6px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; }
        
        .rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 10px; }
        .rating input { display: none; }
        .rating label { cursor: pointer; font-size: 30px; color: #cbd5e1; }
        .rating input:checked ~ label, .rating label:hover, .rating label:hover ~ label { color: #f59e0b; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2>Rate your Expert</h2>
            <p>How was the inspection service?</p>
            
            <?php echo $message; ?>

            <form method="POST">
                <div class="form-group">
                    <label style="display:block; margin-bottom: 5px;">Rating</label>
                    <div class="rating-grid" style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php for($i=5.0; $i>=0.5; $i-=0.5): ?>
                            <div style="flex: 1 1 18%; min-width: 70px;">
                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="r<?php echo $i; ?>" style="display: none;" required>
                                <label for="r<?php echo $i; ?>" style="display: block; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center; cursor: pointer; font-weight: 600; color: #64748b; transition: all 0.2s;">
                                    <?php echo number_format($i, 1); ?> ★
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <style>
                        .rating-grid input:checked + label {
                            background: #2563eb;
                            color: white;
                            border-color: #2563eb;
                            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
                        }
                    </style>
                </div>

                <div class="form-group">
                    <label style="display:block; margin-bottom: 8px;">Comment</label>
                    <textarea name="comment" rows="4" style="width:100%; border:1px solid #ccc; padding:10px;" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Review Expert</button>
            </form>
        </div>
    </div>

</body>
</html>
