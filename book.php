<?php
session_start();
require_once 'db.php';

// Check if logged in and is a BUYER
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit;
}

$buyer_id = $_SESSION['user_id'];
$expert_id = isset($_GET['expert_id']) ? intval($_GET['expert_id']) : 0;
$message = "";

// Fetch Expert Data (Name and Packages)
$expert_name = "Unknown Expert";
$expert_packages = [];
if ($expert_id > 0) {
    $res = $conn->query("SELECT name, packages FROM expert WHERE expert_id = $expert_id");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $expert_name = $row['name'];
        $expert_packages = json_decode($row['packages'], true) ?: [];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_type = $_POST['vehicle_type'];
    $location = $_POST['location'];
    $booking_date = $_POST['booking_date'];
    $expert_id = $_POST['expert_id'];
    $package_info = explode('|', $_POST['package_info']); // "Name|Price"
    $p_name = $package_info[0];
    $p_price = floatval($package_info[1]);
    $service_type = $_POST['service_type'];

    $stmt = $conn->prepare("INSERT INTO booking (buyer_id, expert_id, vehicle_type, location, booking_date, status, package_name, package_price, service_type) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
    $stmt->bind_param("iissssds", $buyer_id, $expert_id, $vehicle_type, $location, $booking_date, $p_name, $p_price, $service_type);

    if ($stmt->execute()) {
        $message = "<div class='alert-success'>Booking Request Sent! <a href='dashboard.php'>Go to Dashboard</a></div>";
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
    <title>Book Inspection | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2 style="margin-top:0;">Book Inspection</h2>
            <p>Requesting service from: <strong><?php echo htmlspecialchars($expert_name); ?></strong></p>
            
            <?php echo $message; ?>

            <form method="POST" action="">
                <input type="hidden" name="expert_id" value="<?php echo $expert_id; ?>">
                
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <input type="text" name="vehicle_type" placeholder="Ex. 2015 Toyota Aqua" required>
                </div>

                <div class="form-group">
                    <label>Inspection Location</label>
                    <input type="text" name="location" placeholder="Ex. Badulla Town" required>
                </div>

                <div class="form-group">
                    <label>Preferred Date</label>
                    <input type="date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Select Inspection Package</label>
                    <select name="package_info" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
                        <?php if (!empty($expert_packages)): ?>
                            <?php foreach ($expert_packages as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['name'] . '|' . $p['price']); ?>">
                                    <?php echo htmlspecialchars($p['name']); ?> - LKR <?php echo number_format($p['price'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="Standard|5000">Standard Inspection - LKR 5,000.00</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Service Type</label>
                    <div style="display: flex; gap: 20px; align-items: center; padding: 5px;">
                        <label style="font-weight: 400; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="radio" name="service_type" value="Physical" checked> Physical (On-site)
                        </label>
                        <label style="font-weight: 400; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="radio" name="service_type" value="Virtual"> Virtual (Remote)
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Send Request</button>
            </form>
        </div>

        <!-- Reviews Section -->
        <div style="max-width: 500px; margin: 20px auto 40px auto;">
            <h3 style="margin-bottom: 15px;">What customers say about <?php echo htmlspecialchars($expert_name); ?></h3>
            <?php
            $rev_sql = "SELECT r.*, b.name FROM review r JOIN buyer b ON r.buyer_id = b.buyer_id WHERE r.expert_id = $expert_id ORDER BY r.review_date DESC LIMIT 5";
            $reviews = $conn->query($rev_sql);
            
            if ($reviews && $reviews->num_rows > 0) {
                while($rev = $reviews->fetch_assoc()) {
                    ?>
                    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <strong><?php echo htmlspecialchars($rev['name']); ?></strong>
                            <span style="color: #f59e0b;">★ <?php echo $rev['rating']; ?>.0</span>
                        </div>
                        <p style="font-size: 0.9rem; color: #475569; margin: 0; line-height: 1.4;"><?php echo htmlspecialchars($rev['comment']); ?></p>
                        
                        <?php if (!empty($rev['expert_reply'])): ?>
                            <div style="background: #f8fafc; border-left: 3px solid #3b82f6; padding: 10px; margin-top: 10px; border-radius: 4px; font-size: 0.85rem;">
                                <div style="font-weight: 600; color: #1e3a8a; margin-bottom: 2px;">Expert's Response:</div>
                                <p style="color: #334155; margin: 0; font-style: italic;"><?php echo htmlspecialchars($rev['expert_reply']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            } else {
                echo "<p style='color: #94a3b8; font-size: 0.9rem; text-align: center;'>No reviews yet for this expert.</p>";
            }
            ?>
        </div>
    </div>

</body>
</html>
