<?php
require_once 'db.php';

echo "<h2>Updating Database Schema for Emergency SOS Feature...</h2>";

$updates = [
    "ALTER TABLE expert ADD COLUMN is_emergency_support BOOLEAN DEFAULT 0"
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
echo "<a href='index.php'>Go to Home</a>";
?>
