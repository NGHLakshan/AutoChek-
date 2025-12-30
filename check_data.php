<?php
require_once 'db.php';

// Check Bajaj
$res = $conn->query("SELECT * FROM vehicle_brands WHERE name LIKE '%Bajaj%'");
if ($res->num_rows == 0) {
    echo "Adding Bajaj...<br>";
    $cat_res = $conn->query("SELECT category_id FROM vehicle_categories WHERE name = 'Bike'");
    if ($cat_row = $cat_res->fetch_assoc()) {
        $cat_id = $cat_row['category_id'];
        $conn->query("INSERT INTO vehicle_brands (name, category_id) VALUES ('Bajaj', $cat_id)");
        $bajaj_id = $conn->insert_id;
        // Add some Bajaj models
        $conn->query("INSERT INTO vehicle_models (name, brand_id) VALUES ('Pulsar', $bajaj_id)");
        $conn->query("INSERT INTO vehicle_models (name, brand_id) VALUES ('CT 100', $bajaj_id)");
        $conn->query("INSERT INTO vehicle_models (name, brand_id) VALUES ('Platina', $bajaj_id)");
    }
} else {
    echo "Bajaj exists.<br>";
}

// Check Honda Dio
$res = $conn->query("SELECT * FROM vehicle_models WHERE name LIKE '%Dio%'");
if ($res->num_rows == 0) {
    echo "Adding Dio...<br>";
    // Find Honda
    $honda_res = $conn->query("SELECT brand_id FROM vehicle_brands WHERE name = 'Honda'");
    if ($honda_row = $honda_res->fetch_assoc()) {
        $honda_id = $honda_row['brand_id'];
        $conn->query("INSERT INTO vehicle_models (name, brand_id) VALUES ('Dio', $honda_id)");
    } else {
        // Create Honda if missing (unlikely if seeded, but safe check)
        // ... assuming Honda exists from previous seed
    }
} else {
     echo "Dio exists.<br>";
}

echo "Verification data ready.";
?>
