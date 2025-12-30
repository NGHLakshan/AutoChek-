<?php
session_start();
require_once 'db.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$expert_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($expert_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid expert ID']);
    exit;
}

// Fetch expert data
$sql = "SELECT * FROM expert WHERE expert_id = $expert_id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $expert = $result->fetch_assoc();
    
    // Fetch specializations
    $specializations = [];
    
    // Categories
    $cat_res = $conn->query("SELECT c.name FROM expert_vehicle_categories e JOIN vehicle_categories c ON e.category_id = c.category_id WHERE e.expert_id = $expert_id");
    while($row = $cat_res->fetch_assoc()) {
        $specializations[] = $row['name'] . ' (Category)';
    }
    
    // Brands
    $brand_res = $conn->query("SELECT b.name, c.name as cat_name FROM expert_vehicle_brands e JOIN vehicle_brands b ON e.brand_id = b.brand_id JOIN vehicle_categories c ON b.category_id = c.category_id WHERE e.expert_id = $expert_id");
    while($row = $brand_res->fetch_assoc()) {
        $specializations[] = $row['name'] . ' (' . $row['cat_name'] . ')';
    }
    
    // Models
    $model_res = $conn->query("SELECT m.name, b.name as brand_name FROM expert_vehicle_models e JOIN vehicle_models m ON e.model_id = m.model_id JOIN vehicle_brands b ON m.brand_id = b.brand_id WHERE e.expert_id = $expert_id");
    while($row = $model_res->fetch_assoc()) {
        $specializations[] = $row['name'] . ' (' . $row['brand_name'] . ')';
    }
    
    $expert['specializations'] = $specializations;
    
    echo json_encode(['success' => true, 'expert' => $expert]);
} else {
    echo json_encode(['success' => false, 'message' => 'Expert not found']);
}
?>
