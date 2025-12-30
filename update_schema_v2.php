<?php
require_once 'db.php';

echo "<h2>Updating Database Schema for Phase 2...</h2>";

// Add columns to 'expert' table
// latitude DECIMAL(10, 8)
// longitude DECIMAL(11, 8)
// is_available BOOLEAN DEFAULT 1

$updates = [
    "ALTER TABLE expert ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL",
    "ALTER TABLE expert ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL",
    "ALTER TABLE expert ADD COLUMN is_available BOOLEAN DEFAULT 1"
];

foreach ($updates as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color:green'>Successfully executed: $sql</div>";
    } else {
        // Ignore if column already exists (Duplicate column name)
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
