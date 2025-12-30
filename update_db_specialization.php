<?php
require_once 'db.php';

// 1. Add 'specialization' column
$sql_add = "ALTER TABLE expert_profiles ADD COLUMN specialization VARCHAR(255) AFTER user_id";
if ($conn->query($sql_add) === TRUE) {
    echo "Column 'specialization' added successfully.<br>";
} else {
    echo "Error adding column (or it exists): " . $conn->error . "<br>";
}

// 2. Drop 'city' column
$sql_drop = "ALTER TABLE expert_profiles DROP COLUMN city";
if ($conn->query($sql_drop) === TRUE) {
    echo "Column 'city' dropped successfully.<br>";
} else {
    echo "Error dropping column: " . $conn->error . "<br>";
}
?>
