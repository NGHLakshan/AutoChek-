<?php
require_once 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$brand_name = trim($_POST['brand'] ?? '');
$model_name = trim($_POST['model'] ?? '');

// Simple category detection
$category_name = 'Car';
$keywords = ['bike', 'motorcycle', 'scooter', 'moped', 'honda dio', 'tvs', 'bajaj', 'yamaha fz'];
$full_name = strtolower($brand_name . ' ' . $model_name);
foreach ($keywords as $kw) {
    if (strpos($full_name, $kw) !== false) {
        $category_name = 'Bike';
        break;
    }
}

if (empty($brand_name) || empty($model_name)) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

// 1. Get or Create Category
$res = $conn->query("SELECT category_id FROM vehicle_categories WHERE name = '$category_name'");
if ($res && $res->num_rows > 0) {
    $category_id = $res->fetch_assoc()['category_id'];
} else {
    $stmt = $conn->prepare("INSERT INTO vehicle_categories (name) VALUES (?)");
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
    $category_id = $stmt->insert_id;
}

// 2. Get or Create Brand
$res = $conn->query("SELECT brand_id FROM vehicle_brands WHERE name = '$brand_name' AND category_id = $category_id");
if ($res && $res->num_rows > 0) {
    $brand_id = $res->fetch_assoc()['brand_id'];
} else {
    $stmt = $conn->prepare("INSERT INTO vehicle_brands (category_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $category_id, $brand_name);
    $stmt->execute();
    $brand_id = $stmt->insert_id;
}

// 3. Get or Create Model
$res = $conn->query("SELECT model_id FROM vehicle_models WHERE name = '$model_name' AND brand_id = $brand_id");
if ($res && $res->num_rows > 0) {
    $model_id = $res->fetch_assoc()['model_id'];
} else {
    $stmt = $conn->prepare("INSERT INTO vehicle_models (brand_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $brand_id, $model_name);
    $stmt->execute();
    $model_id = $stmt->insert_id;
}

echo json_encode([
    'success' => true,
    'id' => $model_id,
    'brand_id' => $brand_id,
    'category_id' => $category_id,
    'name' => $model_name,
    'display' => $model_name . ' (Model - ' . $brand_name . ')'
]);
