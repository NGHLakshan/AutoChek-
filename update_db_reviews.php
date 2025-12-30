<?php
require_once 'db.php';

// Add review columns to bookings table
$sql = "ALTER TABLE bookings ADD COLUMN rating INT(1) AFTER payment_status, ADD COLUMN review_text TEXT AFTER rating";

if ($conn->query($sql) === TRUE) {
    echo "Review columns added successfully.";
} else {
    echo "Error updating table (Columns might already exist): " . $conn->error;
}
?>
