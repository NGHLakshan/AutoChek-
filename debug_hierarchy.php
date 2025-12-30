<?php
require_once 'db.php';

echo "<h1>Vehicle Taxonomy Verification</h1>";

$cats = $conn->query("SELECT * FROM vehicle_categories");
while ($cat = $cats->fetch_assoc()) {
    echo "<h2>" . $cat['name'] . "</h2>";
    $cat_id = $cat['category_id'];
    
    $brands = $conn->query("SELECT * FROM vehicle_brands WHERE category_id = $cat_id");
    if ($brands->num_rows > 0) {
        echo "<ul>";
        while ($brand = $brands->fetch_assoc()) {
            echo "<li><strong>" . $brand['name'] . "</strong>: ";
            $brand_id = $brand['brand_id'];
            $models = $conn->query("SELECT * FROM vehicle_models WHERE brand_id = $brand_id");
            $model_names = [];
            while ($mod = $models->fetch_assoc()) {
                $model_names[] = $mod['name'];
            }
            echo implode(", ", $model_names);
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No brands defined.</p>";
    }
}
$conn->close();
?>
