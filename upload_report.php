<?php
session_start();
require_once 'db.php';

// Security: Must be Expert
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'expert') {
    die("Access Denied");
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$expert_id = $_SESSION['user_id'];
$message = "";

// Security: Verify booking
$check = $conn->query("SELECT b.*, u.name as buyer_name, u.location FROM booking b JOIN buyer u ON b.buyer_id = u.buyer_id WHERE b.booking_id = $booking_id AND b.expert_id = $expert_id");
if ($check->num_rows == 0) {
    die("Booking not found or access denied.");
}
$booking = $check->fetch_assoc();

// Auto-update status to 'in_progress' when expert starts inspection
if ($booking['status'] == 'approved') {
    $conn->query("UPDATE booking SET status = 'in_progress' WHERE booking_id = $booking_id");
    $booking['status'] = 'in_progress'; // Update local variable
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $engine = $_POST['engine'];
    $body = $_POST['body'];
    $electrical = $_POST['electrical'];
    $test_drive = $_POST['test_drive'];
    $rating = floatval($_POST['overall_rating']);
    $comments = $_POST['comments'];

    // Insert into inspection_report
    $stmt = $conn->prepare("INSERT INTO inspection_report (booking_id, engine_condition, body_condition, electrical_condition, test_drive_status, overall_rating, comments, report_date) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_DATE)");
    $stmt->bind_param("isssssd", $booking_id, $engine, $body, $electrical, $test_drive, $rating, $comments);

    if ($stmt->execute()) {
        $conn->query("UPDATE booking SET status = 'completed' WHERE booking_id = $booking_id");
        header("Location: dashboard.php?msg=report_created");
        exit;
    } else {
        $message = "<div class='alert-error'>Database Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Report | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .report-grid { max-width: 700px; margin: 0 auto; }
        .form-section { background: white; border-radius: 12px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .input-group { margin-bottom: 25px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-dark); }
        .input-group textarea, .input-group input { 
            width: 100%; padding: 15px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 0.95rem; resize: vertical; box-sizing: border-box;
            background: #f8fafc; transition: border 0.2s;
        }
        .input-group textarea:focus, .input-group input:focus { border-color: var(--primary); background: white; outline: none; }
        
        .client-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        .client-avatar { width: 50px; height: 50px; border-radius: 50%; background: #e2e8f0; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        
        <div class="report-grid">
            <h2 class="section-title">Inspection Details</h2>
            
            <div class="client-header mobile-stack">
                <div class="avatar" style="width: 50px; height: 50px; font-size: 1.2rem;">👤</div>
                <div>
                    <h3 style="margin: 0;"><?php echo htmlspecialchars($booking['buyer_name']); ?></h3>
                    <p style="margin: 4px 0; color: #64748b;"><?php echo htmlspecialchars($booking['vehicle_type']); ?> • <?php echo htmlspecialchars($booking['location']); ?></p>
                </div>
                <div style="margin-left: auto;">
                    <?php 
                    $status_text = ucfirst(str_replace('_', ' ', $booking['status']));
                    $status_class = 'status-' . $booking['status'];
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>
            </div>

            <h2 class="section-title">Report Form</h2>
            
            <?php echo $message; ?>

            <form method="POST" class="form-section">
                
                <div class="input-group">
                    <label>Exterior Condition</label>
                    <textarea name="body" rows="3" placeholder="Scratches, dents, paint quality..." required></textarea>
                </div>

                <div class="input-group">
                    <label>Engine Performance</label>
                    <textarea name="engine" rows="3" placeholder="Noise, leaks, startup behavior..." required></textarea>
                </div>

                <div class="input-group">
                    <label>Electrical & Interior</label>
                    <textarea name="electrical" rows="3" placeholder="AC, lights, dashboard, seat quality..." required></textarea>
                </div>

                <div class="input-group">
                    <label>Test Drive Feedback</label>
                    <textarea name="test_drive" rows="3" placeholder="Suspension, brakes, transmission feel..." required></textarea>
                </div>

                <div class="input-group">
                    <label>Overall Rating (1-10)</label>
                    <input type="number" name="overall_rating" min="1" max="10" step="0.1" required>
                </div>

                <div class="input-group">
                    <label>Warnings for Buyer / Additional Comments</label>
                    <textarea name="comments" rows="3" placeholder="Any major red flags?"></textarea>
                </div>

                <!-- Mock Upload Section -->
                <div style="border: 2px dashed #cbd5e1; padding: 30px; text-align: center; border-radius: 8px; margin-bottom: 25px; color: #64748b;">
                    <strong>Upload Photos/Videos</strong><br>
                    <small>Add visual evidence to support your report.</small><br>
                    <button type="button" class="btn btn-outline btn-sm" style="margin-top: 10px;">Choose Files</button>
                    <p style="font-size: 0.8rem; margin-top: 5px;">(Feature coming soon)</p>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1rem;">Submit Report</button>

            </form>
        </div>

    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
