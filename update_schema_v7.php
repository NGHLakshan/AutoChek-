<?php
require_once 'db.php';

$sql = "ALTER TABLE booking ADD COLUMN cancellation_reason TEXT DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column 'cancellation_reason' added successfully to 'booking' table.";
} else {
    // Check if it already exists
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "Column 'cancellation_reason' already exists.";
    } else {
        echo "Error updating table: " . $conn->error;
    }
}

$conn->close();
?>
