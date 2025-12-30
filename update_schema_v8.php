<?php
require_once 'db.php';

// Columns to add
$buyer_cols = [
    "profile_photo VARCHAR(255) DEFAULT NULL",
    "bio TEXT DEFAULT NULL",
    "email_notifications BOOLEAN DEFAULT 1",
    "sms_notifications BOOLEAN DEFAULT 1"
];

$expert_cols = [
    "profile_photo VARCHAR(255) DEFAULT NULL",
    "bio TEXT DEFAULT NULL",
    "email_notifications BOOLEAN DEFAULT 1",
    "sms_notifications BOOLEAN DEFAULT 1",
    "linkedin_url VARCHAR(255) DEFAULT NULL",
    "website_url VARCHAR(255) DEFAULT NULL"
];

function addColumns($conn, $table, $cols) {
    foreach ($cols as $col) {
        $col_name = explode(' ', $col)[0];
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col_name'");
        if ($check->num_rows == 0) {
            $sql = "ALTER TABLE `$table` ADD COLUMN $col";
            if ($conn->query($sql)) {
                echo "Added $col_name to $table.<br>";
            } else {
                echo "Error adding $col_name to $table: " . $conn->error . "<br>";
            }
        } else {
            echo "$col_name already exists in $table.<br>";
        }
    }
}

echo "<h2>Database Update</h2>";
addColumns($conn, 'buyer', $buyer_cols);
addColumns($conn, 'expert', $expert_cols);

$conn->close();
?>
