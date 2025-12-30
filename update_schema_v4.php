<?php
require_once 'db.php';

// 1. Create vehicle_categories table
$sql_cats = "CREATE TABLE IF NOT EXISTS vehicle_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
)";

if ($conn->query($sql_cats) === TRUE) {
    echo "Table 'vehicle_categories' created successfully.<br>";
    
    // Seed Data
    $categories = ['Bike', 'Car', 'Van', 'Lorry', 'SUV', 'Bus', 'Heavy Machinery'];
    foreach ($categories as $cat) {
        $sql_insert = "INSERT IGNORE INTO vehicle_categories (name) VALUES ('$cat')";
        $conn->query($sql_insert);
    }
    echo "Seeded 'vehicle_categories' with default data.<br>";
    
} else {
    echo "Error creating table 'vehicle_categories': " . $conn->error . "<br>";
}

// 2. Create expert_vehicle_categories table (Many-to-Many)
$sql_link = "CREATE TABLE IF NOT EXISTS expert_vehicle_categories (
    expert_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (expert_id, category_id),
    FOREIGN KEY (expert_id) REFERENCES expert(expert_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(category_id) ON DELETE CASCADE
)";

if ($conn->query($sql_link) === TRUE) {
    echo "Table 'expert_vehicle_categories' created successfully.<br>";
} else {
    echo "Error creating table 'expert_vehicle_categories': " . $conn->error . "<br>";
}

$conn->close();
?>
