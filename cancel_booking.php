<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle both GET (old way, though mostly replaced) and POST (new way with reason)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $reason = isset($_POST['reason']) ? $conn->real_escape_string($_POST['reason']) : '';
} else {
    $booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $reason = '';
}

if ($booking_id <= 0) {
    die("Invalid Booking ID");
}

// Security: Check if the booking belongs to the current user (buyer or expert)
if ($role == 'buyer') {
    $sql = "SELECT * FROM booking WHERE booking_id = $booking_id AND buyer_id = $user_id";
} elseif ($role == 'expert') {
    $sql = "SELECT * FROM booking WHERE booking_id = $booking_id AND expert_id = $user_id";
} else {
    die("Access Denied");
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

// Update status to cancelled and save reason
$update_sql = "UPDATE booking SET status = 'cancelled', cancellation_reason = '$reason' WHERE booking_id = $booking_id";

if ($conn->query($update_sql) === TRUE) {
    header("Location: dashboard.php?msg=cancelled");
} else {
    echo "Error updating record: " . $conn->error;
}
?>
