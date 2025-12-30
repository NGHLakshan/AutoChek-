<?php
require_once 'db.php';
echo "Checking Experts Table:\n";
$res = $conn->query("SELECT expert_id, name, district, bio FROM expert");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['expert_id'] . " | Name: " . $row['name'] . " | District: " . $row['district'] . " | Bio: " . substr($row['bio'], 0, 50) . "...\n";
}

echo "\nChecking for 'Bandarawela':\n";
$res = $conn->query("SELECT * FROM expert WHERE district LIKE '%Bandarawela%' OR bio LIKE '%Bandarawela%'");
if($res->num_rows > 0) {
    echo "Found " . $res->num_rows . " matches.\n";
    while($row = $res->fetch_assoc()) {
         echo "Match found: " . $row['name'] . " (District: " . $row['district'] . ")\n";
    }
} else {
    echo "No matches found for 'Bandarawela'.\n";
}
?>
