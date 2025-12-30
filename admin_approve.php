<?php
session_start();
require_once 'db.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied");
}

$expert_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($expert_id > 0) {
    // Approve: Set verified = 1 in expert table
    $sql = "UPDATE expert SET verified = 1 WHERE expert_id = $expert_id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: dashboard.php?msg=approved");
    } else {
        echo "Error approving expert: " . $conn->error;
    }
} else {
    header("Location: dashboard.php");
}
?>
