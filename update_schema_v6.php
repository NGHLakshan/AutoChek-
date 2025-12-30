<?php
require_once 'db.php';

// 1. expert_vehicle_brands
$sql_exp_brands = "CREATE TABLE IF NOT EXISTS expert_vehicle_brands (
    expert_id INT NOT NULL,
    brand_id INT NOT NULL,
    PRIMARY KEY (expert_id, brand_id),
    FOREIGN KEY (expert_id) REFERENCES expert(expert_id) ON DELETE CASCADE,
    FOREIGN KEY (brand_id) REFERENCES vehicle_brands(brand_id) ON DELETE CASCADE
)";

if ($conn->query($sql_exp_brands) === TRUE) {
    echo "Table 'expert_vehicle_brands' created successfully.<br>";
} else {
    echo "Error creating table 'expert_vehicle_brands': " . $conn->error . "<br>";
}

// 2. expert_vehicle_models
$sql_exp_models = "CREATE TABLE IF NOT EXISTS expert_vehicle_models (
    expert_id INT NOT NULL,
    model_id INT NOT NULL,
    PRIMARY KEY (expert_id, model_id),
    FOREIGN KEY (expert_id) REFERENCES expert(expert_id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES vehicle_models(model_id) ON DELETE CASCADE
)";

if ($conn->query($sql_exp_models) === TRUE) {
    echo "Table 'expert_vehicle_models' created successfully.<br>";
} else {
    echo "Error creating table 'expert_vehicle_models': " . $conn->error . "<br>";
}

// 3. Add 'Scooter' Category if not exists
$conn->query("INSERT IGNORE INTO vehicle_categories (name) VALUES ('Scooter')");
// Link Scooter brands if needed (manually or just leave for now, let's add Honda to Scooter merely as example or leave flexible)
// Re-running previous seeding might be needed if we want specific scooter models distinct from bikes, but let's assume 'Bike' covers it for now unless user really wants 'Scooter' separate. 
// User mentioned "He can ride a scooter, he can ride a Dio bike". Dio is often a scooter.
// For now, simple category addition is enough.

$conn->close();
?>
