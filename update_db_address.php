<?php
require_once 'db.php';

// Add 'address' column to expert_profiles if it doesn't exist
$sql = "ALTER TABLE expert_profiles ADD COLUMN address TEXT AFTER city";

if ($conn->query($sql) === TRUE) {
    echo "Column 'address' added successfully.";
} else {
    // Check if error is "Duplicate column name", which is fine
    if ($conn->errno == 1060) {
        echo "Column 'address' already exists.";
    } else {
        echo "Error updating table: " . $conn->error;
    }
}
?>
