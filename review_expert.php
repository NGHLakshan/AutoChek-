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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            background-color: #f8fafc;
        }

        .form-container { 
            max-width: 550px; 
            margin: 60px auto; 
            background: white; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }

        h2 {
            margin-top: 0;
            color: #0f172a;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        p.subtitle {
            color: #64748b;
            margin-bottom: 30px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .form-group { margin-bottom: 25px; }
        
        label { 
            display: block; 
            margin-bottom: 10px; 
            font-weight: 600; 
            color: #334155; 
            font-size: 0.95rem;
        }

        .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 10px; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 10px; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px; }
        .alert-success a { color: #15803d; font-weight: 600; text-decoration: underline; }
        
        /* Rating Chips */
        .rating-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        .rating-option {
            position: relative;
        }

        .rating-option input { display: none; }

        .rating-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 5px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s;
            background: #fff;
            height: 100%;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .rating-option label:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
        }

        .rating-option input:checked + label {
            background: #10b981;
            color: white;
            border-color: #10b981;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
            transform: translateY(-1px);
        }

        .rating-option input:checked + label:hover {
            background: #059669;
            border-color: #059669;
        }

        textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            padding: 14px;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: #0f172a;
            transition: all 0.2s;
            resize: vertical;
            min-height: 120px;
        }

        textarea:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .btn-submit {
            background: #10b981;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2><i class="ph-fill ph-star" style="color: #f59e0b;"></i> Rate your Expert</h2>
            <p class="subtitle">How was the inspection service provided by the expert? Your feedback helps us improve.</p>
            
            <?php echo $message; ?>

            <?php if (empty($message) || strpos($message, 'alert-error') !== false): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Overall Rating</label>
                    <div class="rating-grid">
                        <?php 
                        // Show simplified 1-5 star options for better UX, or keep 0.5 increments if strictly required.
                        // User screenshot showed 0.5 increments. Let's keep it but make grid better.
                        // Actually, 5.0 down to 0.5 is 10 items. 5 columns x 2 rows fits perfectly.
                        for($i=5.0; $i>=0.5; $i-=0.5): 
                        ?>
                            <div class="rating-option">
                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="r<?php echo str_replace('.', '_', $i); ?>" required>
                                <label for="r<?php echo str_replace('.', '_', $i); ?>">
                                    <?php echo $i; ?> <i class="ph-fill ph-star" style="font-size: 0.8rem; margin-left: 2px;"></i>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Your Review</label>
                    <textarea name="comment" rows="4" placeholder="Share details of your experience..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    Submit Review <i class="ph-bold ph-paper-plane-right"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
