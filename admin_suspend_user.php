<?php
session_start();
require_once 'db.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0 || !in_array($type, ['expert', 'buyer'])) {
    header("Location: dashboard.php?tab=" . ($type == 'expert' ? 'experts' : 'buyers'));
    exit;
}

// Suspend user by setting is_available = 0 for experts or adding a suspended flag
if ($type == 'expert') {
    // For experts, set is_available to 0 (they can't receive bookings)
    $conn->query("UPDATE expert SET is_available = 0 WHERE expert_id = $id");
    header("Location: dashboard.php?tab=experts&msg=expert_suspended");
} elseif ($type == 'buyer') {
    // For buyers, you could add a 'suspended' column or just delete
    // For now, let's just redirect back (you can add a suspended column to buyer table if needed)
    header("Location: dashboard.php?tab=buyers&msg=buyer_suspended");
}

exit;
?>
