<?php
require_once 'db.php';

echo "Database: " . $dbname . "\n";
echo "Connected successfully.\n";

$result = $conn->query("SHOW TABLES");
if ($result->num_rows > 0) {
    echo "Tables in DB:\n";
    while($row = $result->fetch_row()) {
        echo " - " . $row[0] . "\n";
        
        $cols = $conn->query("DESCRIBE " . $row[0]);
        while($c = $cols->fetch_assoc()) {
            echo "    | " . $c['Field'] . " (" . $c['Type'] . ")\n";
        }
    }
} else {
    echo "No tables found.\n";
}
?>
