<?php
require_once 'db.php';
header('Content-Type: application/json');

$q = isset($_GET['query']) ? $conn->real_escape_string($_GET['query']) : '';
$results = [];

if (strlen($q) >= 2) {
    // 1. Categories
    $res = $conn->query("SELECT category_id, name FROM vehicle_categories WHERE name LIKE '%$q%' LIMIT 5");
    if($res) {
        while ($row = $res->fetch_assoc()) {
            $results[] = [
                'type' => 'category',
                'id' => $row['category_id'],
                'name' => $row['name'], 
                'display' => $row['name'] . ' (Category)'
            ];
        }
    }

    // 2. Brands
    $res = $conn->query("SELECT b.brand_id, b.name, c.name as cat_name FROM vehicle_brands b JOIN vehicle_categories c ON b.category_id = c.category_id WHERE b.name LIKE '%$q%' LIMIT 5");
    if($res) {
        while ($row = $res->fetch_assoc()) {
            $results[] = [
                'type' => 'brand', 
                'id' => $row['brand_id'], 
                'name' => $row['name'],
                'display' => $row['name'] . ' (' . $row['cat_name'] . ')'
            ];
        }
    }

    // 3. Models
    $res = $conn->query("SELECT m.model_id, m.name, b.name as brand_name FROM vehicle_models m JOIN vehicle_brands b ON m.brand_id = b.brand_id WHERE m.name LIKE '%$q%' LIMIT 5");
    if($res) {
        while ($row = $res->fetch_assoc()) {
            $results[] = [
                'type' => 'model', 
                'id' => $row['model_id'], 
                'name' => $row['name'],
                'display' => $row['name'] . ' (Model - ' . $row['brand_name'] . ')'
            ];
        }
    }
}

echo json_encode($results);
?>
