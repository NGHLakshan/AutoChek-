<?php
require_once 'db.php';

// 1. Create vehicle_brands table
$sql_brands = "CREATE TABLE IF NOT EXISTS vehicle_brands (
    brand_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(category_id) ON DELETE CASCADE
)";

if ($conn->query($sql_brands) === TRUE) {
    echo "Table 'vehicle_brands' created successfully.<br>";
} else {
    echo "Error creating table 'vehicle_brands': " . $conn->error . "<br>";
}

// 2. Create vehicle_models table
$sql_models = "CREATE TABLE IF NOT EXISTS vehicle_models (
    model_id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (brand_id) REFERENCES vehicle_brands(brand_id) ON DELETE CASCADE
)";

if ($conn->query($sql_models) === TRUE) {
    echo "Table 'vehicle_models' created successfully.<br>";
} else {
    echo "Error creating table 'vehicle_models': " . $conn->error . "<br>";
}

// 3. Helper to get category ID
function getCatId($conn, $name) {
    $res = $conn->query("SELECT category_id FROM vehicle_categories WHERE name = '$name'");
    if ($res && $row = $res->fetch_assoc()) return $row['category_id'];
    return null;
}

// 4. Seed Data
$seed_data = [
    'Car' => [
        'Toyota' => ['Corolla', 'Camry', 'Prius', 'Vitz', 'Axio'],
        'Honda' => ['Civic', 'Accord', 'Fit', 'Vezel'],
        'Nissan' => ['Sunny', 'Leaf', 'X-Trail'],
        'Suzuki' => ['Swift', 'Alto', 'WagonR'],
    ],
    'Bike' => [
        'Honda' => ['Dio', 'Hornet', 'CBR'],
        'Yamaha' => ['FZ', 'R15', 'Ray ZR'],
        'Bajaj' => ['Pulsar', 'Discover', 'CT100'],
        'TVS' => ['Apache', 'Scooty Pep', 'Ntorq']
    ],
    'Van' => [
        'Toyota' => ['Hiace', 'TownAce'],
        'Nissan' => ['Caravan', 'Vanette']
    ],
    'SUV' => [
        'Toyota' => ['Land Cruiser', 'Prado', 'RAV4'],
        'Mitsubishi' => ['Montero', 'Outlander'],
        'Land Rover' => ['Defender', 'Range Rover']
    ],
    'Lorry' => [
        'Isuzu' => ['Elf', 'N-Series'],
        'Tata' => ['Ace', '407'],
        'Mitsubishi' => ['Canter']
    ]
];

echo "<h3>Seeding Data...</h3>";
foreach ($seed_data as $cat_name => $brands) {
    $cat_id = getCatId($conn, $cat_name);
    if (!$cat_id) {
        echo "Skipping $cat_name (Category not found)<br>";
        continue;
    }

    foreach ($brands as $brand_name => $models) {
        // Insert Brand
        // Check if exists for this category first to avoid dupes on re-run
        $check_brand = $conn->query("SELECT brand_id FROM vehicle_brands WHERE name = '$brand_name' AND category_id = $cat_id");
        if ($check_brand && $check_brand->num_rows > 0) {
            $brand_id = $check_brand->fetch_assoc()['brand_id'];
        } else {
            $conn->query("INSERT INTO vehicle_brands (category_id, name) VALUES ($cat_id, '$brand_name')");
            $brand_id = $conn->insert_id;
            echo "Added Brand: $brand_name ($cat_name)<br>";
        }

        // Insert Models
        foreach ($models as $model_name) {
             $conn->query("INSERT IGNORE INTO vehicle_models (brand_id, name) VALUES ($brand_id, '$model_name')");
        }
    }
}
echo "Seeding Complete.<br>";

$conn->close();
?>
