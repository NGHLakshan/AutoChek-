<?php
session_start();
require_once 'db.php';

// Check if expert
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'expert') {
    die("Access Denied");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['review_id']) && isset($_POST['expert_reply'])) {
    $review_id = intval($_POST['review_id']);
    $expert_reply = trim($_POST['expert_reply']);
    $expert_id = $_SESSION['user_id'];
    $reply_date = date('Y-m-d');

    if (empty($expert_reply)) {
        header("Location: dashboard.php?msg=empty_reply");
        exit;
    }

    // Verify this review belongs to the current expert
    $stmt = $conn->prepare("UPDATE review SET expert_reply = ?, reply_date = ? WHERE review_id = ? AND expert_id = ?");
    $stmt->bind_param("ssii", $expert_reply, $reply_date, $review_id, $expert_id);

    if ($stmt->execute()) {
        header("Location: dashboard.php?msg=reply_saved");
    } else {
        echo "Error saving reply: " . $conn->error;
    }
} else {
    header("Location: dashboard.php");
}
?>
