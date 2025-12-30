<?php
require_once 'db.php';

echo "<h2>Updating Database Schema for Phase 3...</h2>";

// Add column to 'expert' table
// certification_file VARCHAR(255)

$updates = [
    "ALTER TABLE expert ADD COLUMN certification_file VARCHAR(255) DEFAULT NULL"
];

foreach ($updates as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color:green'>Successfully executed: $sql</div>";
    } else {
        if ($conn->errno == 1060) {
             echo "<div style='color:orange'>Column already exists (Skipped): $sql</div>";
        } else {
             echo "<div style='color:red'>Error: " . $conn->error . "</div>";
        }
    }
}

echo "<h3>Update Complete.</h3>";
echo "<a href='dashboard.php'>Go to Dashboard</a>";
?>
