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
                'display' => $row['name'] . ' (Category)',
                'subtext' => 'Vehicle Category',
                'type_label' => 'Category'
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
                'display' => $row['name'] . ' (' . $row['cat_name'] . ')',
                'subtext' => $row['cat_name'],
                'type_label' => 'Brand'
            ];
        }
    }

    // 3. Models (Smart word-based matching)
    $words = explode(' ', $q);
    $word_queries = [];
    foreach ($words as $word) {
        $w = trim($word);
        if ($w !== '') {
            $word_queries[] = "(m.name LIKE '%$w%' OR b.name LIKE '%$w%')";
        }
    }
    
    if (!empty($word_queries)) {
        $where_clause = implode(' AND ', $word_queries);
        $res = $conn->query("SELECT m.model_id, m.name, b.name as brand_name FROM vehicle_models m JOIN vehicle_brands b ON m.brand_id = b.brand_id WHERE $where_clause LIMIT 5");
        if($res) {
            while ($row = $res->fetch_assoc()) {
                $results[] = [
                    'type' => 'model', 
                    'id' => $row['model_id'], 
                    'name' => $row['name'],
                    'display' => $row['name'] . ' (Model - ' . $row['brand_name'] . ')',
                    'subtext' => 'Brand: ' . $row['brand_name'],
                    'type_label' => 'Model'
                ];
            }
        }
    }

    // --- 4. GLOBAL FALLBACK (NHTSA API) ---
    // If we have very few results, try to suggest from Global Database
    if (count($results) < 5) {
        // Use first word as "Make" for API call, rest for filtering if needed
        $q_words = explode(' ', trim($q));
        $external_query = urlencode($q_words[0]); 
        
        // Strategy: Assume first word is a 'Make' (Brand) and fetch models
        $ctx = stream_context_create(['http' => ['timeout' => 1.5, 'header' => "User-Agent: AutoChekSearch/1.0\r\n"]]);
        $api_url = "https://vpic.nhtsa.dot.gov/api/vehicles/GetModelsForMake/$external_query?format=json";
        $api_data_text = @file_get_contents($api_url, false, $ctx);
        
        if ($api_data_text) {
            $api_data = json_decode($api_data_text, true);
            if (isset($api_data['Results']) && count($api_data['Results']) > 0) {
                $count = 0;
                foreach ($api_data['Results'] as $item) {
                    if ($count >= 5) break;
                    
                    $brand = $item['Make_Name'];
                    $model = $item['Model_Name'];
                    
                    // Avoid duplicates if it's already in our local results
                    $is_dupe = false;
                    foreach ($results as $r) {
                        if ($r['name'] == $model) { $is_dupe = true; break; }
                    }
                    
                    if (!$is_dupe) {
                        $results[] = [
                            'type' => 'external',
                            'id' => 0, // No local ID yet
                            'name' => $model,
                            'brand' => $brand,
                            'display' => "$brand $model (Global Database ✨)",
                            'subtext' => "Brand: $brand",
                            'type_label' => 'Global',
                            'external' => true
                        ];
                        $count++;
                    }
                }
            }
        }
    }
}

echo json_encode($results);
?>
