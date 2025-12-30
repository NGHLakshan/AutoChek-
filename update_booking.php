<?php
session_start();
require_once 'db.php';

// Check if expert
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'expert') {
    die("Access Denied");
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

$valid_status = ['approved', 'rejected'];

if ($booking_id > 0 && in_array($status, $valid_status)) {
    // Verify this booking belongs to the current expert and is still pending
    $expert_id = $_SESSION['user_id'];
    
    // Update booking table only if it's pending
    $sql = "UPDATE booking SET status = '$status' 
            WHERE booking_id = $booking_id AND expert_id = $expert_id AND status = 'pending'";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: dashboard.php");
    } else {
        echo "Error updating record: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}
?>
