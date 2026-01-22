<?php
session_start();
require_once 'db.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 0;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find an Expert | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .expert-card { 
            background: white; 
            border-radius: 12px; 
            padding: 24px; 
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); 
            display: block; 
            margin-bottom: 24px;
            border: 1px solid #f1f5f9;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .expert-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
        .expert-info h3 { margin: 0 0 5px 0; color: #1e293b; }
        .expert-meta { color: #64748b; font-size: 0.9rem; margin-bottom: 10px; }
        .distance-badge { background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-left: 10px; }
        .search-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin: 30px 0; display: flex; gap: 30px; }
        .search-box input, .search-box select { flex: 1; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
        .search-box button { padding: 12px 30px; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        
        <?php
        // Initialize Variables
        $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
        $brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
        $model_id = isset($_GET['model_id']) ? intval($_GET['model_id']) : 0;

        // Fetch Categories
        $cats_result = $conn->query("SELECT * FROM vehicle_categories ORDER BY name ASC");
        $categories = [];
        if($cats_result) while($c = $cats_result->fetch_assoc()) $categories[] = $c;
        ?>

        <form action="" method="GET" class="search-box" style="flex-wrap: wrap;" autocomplete="off">
            <input type="hidden" name="lat" id="search-lat" value="<?php echo $lat; ?>">
            <input type="hidden" name="lng" id="search-lng" value="<?php echo $lng; ?>">
            
            <div style="flex: 1; min-width: 200px; position: relative;">
                <input type="text" id="main-search" name="search" placeholder="Search for any vehicle (e.g. 'Toyota', 'Benz', 'Bike')..." value="<?php echo htmlspecialchars($search); ?>" style="width: 100%; margin: 0;">
                <div id="search-suggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-top: none; border-radius: 0 0 8px 8px; z-index: 1000; display: none; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"></div>
            </div>

            <div style="flex: 1; min-width: 200px; max-width: 300px; display: flex; gap: 10px; position: relative;">
                <input type="text" id="location-input" name="location" placeholder="Location (e.g. Badulla)" value="<?php echo htmlspecialchars(isset($_GET['location']) ? $_GET['location'] : ''); ?>" style="flex: 1; min-width: 0; margin: 0;" autocomplete="off">
                 <div id="location-suggestions" style="position: absolute; top: 100%; left: 0; right: 45px; background: white; border: 1px solid #cbd5e1; border-top: none; border-radius: 0 0 8px 8px; z-index: 1000; display: none; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"></div>

            </div>

            <div style="min-width: 130px; display: none;"> <!-- Hidden as we want to drive via search but kept for compatibility -->
                <select name="category_id" id="category_select" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="0">Type</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php if($category_id == $cat['category_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="min-width: 130px; display: none;">
                <select name="brand_id" id="brand_select" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="0">Brand</option>
                </select>
            </div>

            <div style="min-width: 130px; display: none;">
                 <select name="model_id" id="model_select" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="0">Model</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <div id="location-msg" style="margin-bottom: 20px; color: #64748b; font-size: 0.9rem;"></div>

        <h2>Trusted Vehicle Experts</h2>

        <?php
        // HIERARCHICAL MATCHING LOGIC
        
        $target_cat_id = $category_id;
        $target_brand_id = $brand_id;
        
        if ($model_id > 0) {
            $res = $conn->query("SELECT m.brand_id, b.category_id FROM vehicle_models m JOIN vehicle_brands b ON m.brand_id = b.brand_id WHERE m.model_id = $model_id");
            if ($res && $row = $res->fetch_assoc()) {
                $target_brand_id = $row['brand_id'];
                $target_cat_id = $row['category_id'];
            }
        } elseif ($brand_id > 0) {
            $res = $conn->query("SELECT category_id FROM vehicle_brands WHERE brand_id = $brand_id");
            if ($res && $row = $res->fetch_assoc()) {
                $target_cat_id = $row['category_id'];
            }
        }
        
        // Base Query
        $sql = "SELECT DISTINCT e.expert_id, e.name, e.district, e.qualification, e.experience, e.latitude, e.longitude, e.profile_photo, e.bio, e.linkedin_url, e.website_url ";
        if ($lat != 0 && $lng != 0) {
            $sql .= ", ( 6371 * acos( cos( radians($lat) ) * cos( radians( e.latitude ) ) * cos( radians( e.longitude ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( e.latitude ) ) ) ) AS distance ";
        }
        $sql .= " FROM expert e ";
        $sql .= " LEFT JOIN expert_vehicle_categories evc ON e.expert_id = evc.expert_id ";
        $sql .= " LEFT JOIN vehicle_categories vc ON evc.category_id = vc.category_id ";
        $sql .= " LEFT JOIN expert_vehicle_brands evb ON e.expert_id = evb.expert_id ";
        $sql .= " LEFT JOIN vehicle_brands vb ON evb.brand_id = vb.brand_id ";
        $sql .= " LEFT JOIN expert_vehicle_models evm ON e.expert_id = evm.expert_id ";
        $sql .= " LEFT JOIN vehicle_models vm ON evm.model_id = vm.model_id ";
        $sql .= " WHERE e.verified = 1 AND e.is_available = 1 ";

        $conditions = [];

        // Badulla Towns List for Mapping
        $badulla_towns = [
            "Badulla", "Bandarawela", "Haputale", "Ella", "Welimada", "Mahiyanganaya", 
            "Diyatalawa", "Hali Ela", "Passara", "Girandurukotte", "Mirahawatte", 
            "Demodara", "Lunugala", "Kandaketiya", "Meegahakiula", "Soranathota",
            "Akkarasiyaya", "Aluketiyawa", "Aluttaramma", "Ambadandegama", "Ambagahawatta",
            "Ambagasdowa", "Amunumulla", "Arawa", "Arawakumbura", "Arawatta", "Atakiriya",
            "Baduluoya", "Ballaketuwa", "Bambarapana", "Beramada", "Beragala", "Bibilegama",
            "Bogahakumbura", "Boragas", "Boralanda", "Bowela", "Dambana", "Diganatenna",
            "Dikkapitiya", "Dimbulana", "Divulapelessa", "Dulgolla", "Egodawela", "Ettampitiya",
            "Galauda", "Galedanda", "Galporuyaya", "Gamewela", "Gawarawela", "Godunna",
            "Gurutalawa", "Haldummulla", "Hangunnawa", "Hebarawa", "Heeloya", "Helahalpe",
            "Helapupula", "Hewanakumbura", "Hingurukaduwa", "Hopton", "Idalgashinna", "Jangulla",
            "Kahataruppa", "Kalubululanda", "Kalugahakandura", "Kalupahana", "Kandegedara",
            "Kandepuhulpola", "Kebillawela", "Kendagolla", "Keppetipola", "Keselpotha",
            "Ketawatta", "Kiriwanagama", "Koslanda", "Kotamuduna", "Kuruwitenna", "Kuttiyagolla",
            "Landewela", "Liyangahawela", "Lunuwatta", "Madulsima", "Makulella", "Malgoda",
            "Maliyadda", "Mapakadawewa", "Maspanna", "Maussagolla", "Medawela Udukinda",
            "Medawelagama", "Metigahatenna", "Miriyabedda", "Miyanakandura", "Namunukula",
            "Narangala", "Nelumgama", "Nikapotha", "Nugatalawa", "Ohiya", "Pahalarathkinda",
            "Pallekiruwa", "Pangaragammana", "Pattiyagedara", "Pelagahatenna", "Perawella",
            "Pitapola", "Pitamaruwa", "Puhulpola", "Ratkarawwa", "Ridimaliyadda", "Rilpola",
            "Sirimalgoda", "Silmiyapura", "Soragune", "Sorabora Colony", "Spring Valley",
            "Taldena", "Tennapanguwa", "Timbirigaspitiya", "Uduhawara", "Uraniya", "Uva Deegalla",
            "Uva Karandagolla", "Uva Mawelagama", "Uva Paranagama", "Uva Tenna", "Uva Tissapura",
            "Uva Uduwara", "Uvaparanagama", "Wewatta", "Wineethagama", "Yalagamuwa", "Yalwela"
        ];

        // --- LOCATION FILTERING (Strict) ---
        $location_text = isset($_GET['location']) ? trim($conn->real_escape_string($_GET['location'])) : '';
        $target_district = '';

        // Check if location is in Badulla list
        if (!empty($location_text)) {
            foreach ($badulla_towns as $town) {
                if (stripos($location_text, $town) !== false) {
                    $target_district = 'Badulla';
                    break;
                }
            }
            // If not found in list, assume location text itself might be the district (e.g. user typed "Colombo")
            if (empty($target_district)) {
                $target_district = $location_text;
            }
        }

        $having_clause = "";

        if ($lat != 0 && $lng != 0) {
             // GPS: Filter within 15km OR District
             // We use HAVING because 'distance' is a calculated alias
             if (!empty($target_district)) {
                 $having_clause = " HAVING (distance <= 50 OR district LIKE '%$target_district%') ";
             } else {
                 $having_clause = " HAVING distance <= 50 ";
             }
        } elseif (!empty($location_text)) {
             // Text Only: Filter by District
             // If we mapped it to Badulla, show all Badulla
             if (!empty($target_district)) {
                 $conditions[] = "(e.district LIKE '%$target_district%' OR e.bio LIKE '%$location_text%')";
             } else {
                 $conditions[] = "(e.district LIKE '%$location_text%' OR e.bio LIKE '%$location_text%')";
             }
        }
        
        if ($target_cat_id > 0) {
            if ($model_id > 0) {
                 // Match Expert who has this Model OR Brand OR Category
                 $conditions[] = "(evc.category_id = $target_cat_id OR evb.brand_id = $target_brand_id OR evm.model_id = $model_id)";
            } elseif ($target_brand_id > 0) {
                 // Match Expert who has this Brand OR Category
                 $conditions[] = "(evc.category_id = $target_cat_id OR evb.brand_id = $target_brand_id)";
            } else {
                 // Match Expert who has Category OR any Brand in Category OR any Model in Category
                 $sub_cond_1 = "evc.category_id = $target_cat_id";
                 $sub_cond_2 = "e.expert_id IN (SELECT expert_id FROM expert_vehicle_brands eb JOIN vehicle_brands vb ON eb.brand_id = vb.brand_id WHERE vb.category_id = $target_cat_id)";
                 $sub_cond_3 = "e.expert_id IN (SELECT expert_id FROM expert_vehicle_models em JOIN vehicle_models vm ON em.model_id = vm.model_id JOIN vehicle_brands vb2 ON vm.brand_id = vb2.brand_id WHERE vb2.category_id = $target_cat_id)";
                 
                 $conditions[] = "($sub_cond_1 OR $sub_cond_2 OR $sub_cond_3)";
            }
        }
        
        if (count($conditions) > 0) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }

        // Text Search Filter
        // We apply text search alongside vehicle ID filters to allow refining (e.g. Brand="Toyota" + Search="Corolla")
        if (!empty($search)) {
            $s = $conn->real_escape_string($search);
            // Search name/qual/bio/vehicle names
            $sql .= " AND (e.qualification LIKE '%$s%' OR e.name LIKE '%$s%' OR e.bio LIKE '%$s%' OR vc.name LIKE '%$s%' OR vb.name LIKE '%$s%' OR vm.name LIKE '%$s%')";
        }

        // Apply HAVING clause for GPS filtering
        if (!empty($having_clause)) {
            $sql .= $having_clause;
        }

        // Apply Having for Distance if GPS used
        if ($lat != 0 && $lng != 0) {
            $sql .= " ORDER BY distance ASC";
        } elseif (!empty($location_text)) {
            $sql .= " ORDER BY e.district ASC";
        } else {
            $sql .= " ORDER BY e.expert_id DESC";
        }

        $result = $conn->query($sql);

        // Categorize results
        $nearby_experts = [];
        $district_experts = [];

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Categorize based on distance if GPS is used
                if (isset($row['distance']) && $row['distance'] !== null) {
                    if ($row['distance'] <= 15) {
                        $nearby_experts[] = $row;
                    } else {
                        $district_experts[] = $row;
                    }
                } else {
                    // If no GPS, put all in district experts
                    $district_experts[] = $row;
                }
            }
        }

        // Function to render expert card
        function renderExpertCard($row, $location_text, $target_district) {
            ?>
            <div class="expert-card" style="display: block;">
                <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 15px;">
                    <div class="expert-photo" style="width: 80px; height: 80px; border-radius: 12px; background: #e2e8f0; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #94a3b8; border: 2px solid #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <?php if (!empty($row['profile_photo'])): ?>
                            <img src="uploads/profiles/<?php echo $row['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="ph ph-user-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="expert-info" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h3 style="margin: 0; font-size: 1.25rem;">
                                <?php echo htmlspecialchars($row['name']); ?> 
                                <?php if (isset($row['distance']) && $row['distance'] !== null): ?>
                                    <?php if ($row['distance'] > 15): ?>
                                        <span class="distance-badge" style="background: #fff7ed; color: #c2410c;">District Other Expert</span>
                                    <?php else: ?>
                                        <span class="distance-badge"><?php echo round($row['distance'], 1); ?> km away</span>
                                    <?php endif; ?>
                                <?php elseif (!empty($location_text) && stripos($row['district'], $location_text) !== false): ?>
                                     <span class="distance-badge" style="background: #f0fdf4; color: #15803d;">Service Area Match</span>
                                <?php elseif (!empty($target_district) && stripos($row['district'], $target_district) !== false): ?>
                                     <span class="distance-badge" style="background: #fff7ed; color: #c2410c;">District Expert</span>
                                <?php endif; ?>
                            </h3>
                            <div class="expert-meta" style="margin: 0; font-weight: 500;">
                                Exp: <?php echo $row['experience']; ?> Yrs
                            </div>
                        </div>
                        <div class="expert-meta" style="margin-top: 5px;"><i class="ph-fill ph-map-pin"></i> Service Areas: <?php echo htmlspecialchars($row['district']); ?></div>
                        <div style="font-size: 0.9rem; margin-top: 8px; color: #475569;"><i class="ph ph-graduation-cap"></i> <?php echo htmlspecialchars($row['qualification']); ?></div>
                    </div>
                </div>
                
                <?php if (!empty($row['bio'])): ?>
                    <div style="font-size: 0.95rem; color: #475569; background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 3px solid #cbd5e1;">
                        <?php echo nl2br(htmlspecialchars($row['bio'])); ?>
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                    <div class="social-links" style="display: flex; gap: 15px;">
                        <?php if (!empty($row['linkedin_url'])): ?>
                            <a href="<?php echo $row['linkedin_url']; ?>" target="_blank" style="color: #0077b5; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; text-decoration: none;">
                                <i class="ph ph-linkedin-logo" style="font-size: 1.2em;"></i>
                                LinkedIn
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($row['website_url'])): ?>
                            <a href="<?php echo $row['website_url']; ?>" target="_blank" style="color: #64748b; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; text-decoration: none;">
                                <i class="ph ph-globe" style="font-size: 1.2em;"></i>
                                Website
                            </a>
                        <?php endif; ?>
                    </div>
                    <a href="expert_profile.php?id=<?php echo htmlspecialchars($row['expert_id']); ?>" class="btn btn-primary" style="padding: 8px 25px;">View Profile</a>
                </div>
            </div>
            <?php
        }

        // Display Results
        if (count($nearby_experts) > 0 || count($district_experts) > 0) {
            // Display Nearby Experts Section
            if (count($nearby_experts) > 0) {
                ?>
                <div style="margin-bottom: 40px;">
                    <h3 style="color: #166534; font-size: 1.3rem; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #dcfce7;">
                        <i class="ph-fill ph-map-pin"></i> Nearby Experts (Within 15km)
                    </h3>
                    <?php
                    foreach ($nearby_experts as $expert) {
                        renderExpertCard($expert, $location_text, $target_district);
                    }
                    ?>
                </div>
                <?php
            }

            // Display Other District Experts Section
            if (count($district_experts) > 0) {
                ?>
                <div style="margin-bottom: 40px;">
                    <h3 style="color: #c2410c; font-size: 1.3rem; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #fff7ed;">
                        <i class="ph ph-buildings"></i> Other District Experts
                    </h3>
                    <?php
                    foreach ($district_experts as $expert) {
                        renderExpertCard($expert, $location_text, $target_district);
                    }
                    ?>
                </div>
                <?php
            }
        } else {
             echo "<p style='text-align:center; padding: 40px; color: #64748b;'>No available experts found matching your criteria.</p>";
        }
        ?>

    </div>

    <script>
        <?php
            // Brands by Cat
            $brands_by_cat = [];
            $all_brands_res = $conn->query("SELECT * FROM vehicle_brands ORDER BY name ASC");
            if($all_brands_res) while($b = $all_brands_res->fetch_assoc()) $brands_by_cat[$b['category_id']][] = $b;

            // Models by Brand
            $models_by_brand = [];
            $all_models_res = $conn->query("SELECT * FROM vehicle_models ORDER BY name ASC");
            if($all_models_res) while($m = $all_models_res->fetch_assoc()) $models_by_brand[$m['brand_id']][] = $m;
        ?>
        const brandsData = <?php echo json_encode($brands_by_cat); ?>;
        const modelsData = <?php echo json_encode($models_by_brand); ?>;
        
        const selectedCatId = <?php echo $category_id; ?>;
        const selectedBrandId = <?php echo $brand_id; ?>;
        const selectedModelId = <?php echo $model_id; ?>;

        const catSelect = document.getElementById('category_select');
        const brandSelect = document.getElementById('brand_select');
        const modelSelect = document.getElementById('model_select');

        function updateBrands() {
            const catId = catSelect.value;
            brandSelect.innerHTML = '<option value="0">Brand</option>';
            modelSelect.innerHTML = '<option value="0">Model</option>';
            
            if (brandsData[catId]) {
                brandsData[catId].forEach(brand => {
                    const opt = document.createElement('option');
                    opt.value = brand.brand_id;
                    opt.textContent = brand.name;
                    if (brand.brand_id == selectedBrandId) opt.selected = true;
                    brandSelect.appendChild(opt);
                });
            }
            updateModels();
        }

        function updateModels() {
            const brandId = brandSelect.value;
            modelSelect.innerHTML = '<option value="0">Model</option>';
            
            if (modelsData[brandId]) {
                modelsData[brandId].forEach(model => {
                    const opt = document.createElement('option');
                    opt.value = model.model_id;
                    opt.textContent = model.name;
                    if (model.model_id == selectedModelId) opt.selected = true;
                    modelSelect.appendChild(opt);
                });
            }
        }

        catSelect.addEventListener('change', updateBrands);
        brandSelect.addEventListener('change', updateModels);
        
        if (selectedCatId > 0) {
            updateBrands();
            brandSelect.value = selectedBrandId;
            updateModels();
            modelSelect.value = selectedModelId;
        }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            // JS Moved to manual click to avoid annoying auto-popups


            // If user types in location input, clear lat/lng to avoid conflict
            document.getElementById('location-input').addEventListener('input', function() {
                 document.getElementById('search-lat').value = 0;
                 document.getElementById('search-lng').value = 0;
            });
            
            // Check params on load to set UI state
            // Check params on load to set UI state
            if(urlParams.get('lat') && urlParams.get('lat') != 0 && document.getElementById('location-input').value === '') {
                document.getElementById('location-input').value = "Current Location";
            }

            // --- Autocomplete Logic ---
            const searchInput = document.getElementById('main-search');
            const suggestionsBox = document.getElementById('search-suggestions');
            let debounceTimer;

            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value;
                
                if (query.length < 2) {
                    suggestionsBox.style.display = 'none';
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch('ajax_search_vehicle.php?query=' + encodeURIComponent(query))
                        .then(res => res.json())
                        .then(data => {
                            suggestionsBox.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(item => {
                                    const div = document.createElement('div');
                                    div.style.padding = '10px 15px';
                                    div.style.borderBottom = '1px solid #f1f5f9';
                                    div.style.cursor = 'pointer';
                                    div.style.color = '#1e293b';
                                    div.style.fontSize = '0.95rem';
                                    div.onmouseover = () => div.style.background = '#f8fafc';
                                    div.onmouseout = () => div.style.background = 'white';
                                    
                                    div.textContent = item.display;
                                    
                                    div.onclick = () => {
                                        searchInput.value = item.name;
                                        suggestionsBox.style.display = 'none';
                                        
                                        // Update hidden/visible selects and submit
                                        if (item.type === 'category') {
                                            window.location.href = `?category_id=${item.id}&search=${encodeURIComponent(item.name)}`;
                                        } else if (item.type === 'brand') {
                                            window.location.href = `?brand_id=${item.id}&search=${encodeURIComponent(item.name)}`;
                                        } else if (item.type === 'model') {
                                            window.location.href = `?model_id=${item.id}&search=${encodeURIComponent(item.name)}`;
                                        }
                                    };
                                    suggestionsBox.appendChild(div);
                                });
                                suggestionsBox.style.display = 'block';
                            } else {
                                suggestionsBox.style.display = 'none';
                            }
                        });
                }, 300);
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });

            // Geocoding Logic for Location Search
            const searchForm = document.querySelector('.search-box');
            
            // Add submit handler to geocode location if needed
            searchForm.addEventListener('submit', function(e) {
                const locationValue = document.getElementById('location-input').value.trim();
                const latValue = parseFloat(document.getElementById('search-lat').value);
                const lngValue = parseFloat(document.getElementById('search-lng').value);
                
                // If location is entered but coordinates are missing or zero
                if (locationValue && (!latValue || !lngValue || (latValue === 0 && lngValue === 0))) {
                    e.preventDefault(); // Stop form submission
                    
                    // Show loading indicator
                    const submitBtn = searchForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    submitBtn.textContent = 'Locating...';
                    submitBtn.disabled = true;
                    
                    // Set a timeout to proceed with search even if geocoding is slow
                    const timeoutId = setTimeout(() => {
                        console.log('Geocoding timeout - proceeding with search anyway');
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                        searchForm.submit();
                    }, 5000); // 5 second timeout
                    
                    // Geocode the location
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(locationValue + ', Badulla, Sri Lanka')}&limit=1`)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                document.getElementById('search-lat').value = data[0].lat;
                                document.getElementById('search-lng').value = data[0].lon;
                                console.log(`Geocoded ${locationValue}: ${data[0].lat}, ${data[0].lon}`);
                                clearTimeout(timeoutId);
                                submitBtn.textContent = originalText;
                                submitBtn.disabled = false;
                                searchForm.submit();
                            } else {
                                // Fallback: try without district specification
                                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(locationValue + ', Sri Lanka')}&limit=1`)
                                    .then(res => res.json())
                                    .then(fallbackData => {
                                        if (fallbackData && fallbackData.length > 0) {
                                            document.getElementById('search-lat').value = fallbackData[0].lat;
                                            document.getElementById('search-lng').value = fallbackData[0].lon;
                                            console.log(`Geocoded ${locationValue} (fallback): ${fallbackData[0].lat}, ${fallbackData[0].lon}`);
                                        }
                                        clearTimeout(timeoutId);
                                        submitBtn.textContent = originalText;
                                        submitBtn.disabled = false;
                                        searchForm.submit();
                                    })
                                    .catch(err => {
                                        console.error('Fallback geocoding error:', err);
                                        clearTimeout(timeoutId);
                                        submitBtn.textContent = originalText;
                                        submitBtn.disabled = false;
                                        searchForm.submit();
                                    });
                            }
                        })
                        .catch(err => {
                            console.error('Geocoding error:', err);
                            clearTimeout(timeoutId);
                            submitBtn.textContent = originalText;
                            submitBtn.disabled = false;
                            searchForm.submit(); // Proceed anyway
                        });
                }
            });
            
            // Location Autocomplete
            const locInput = document.getElementById('location-input');
            const locSuggestions = document.getElementById('location-suggestions');
            const latInput = document.getElementById('search-lat');
            const lngInput = document.getElementById('search-lng');
            let locDebounce;

            // Badulla Towns & Villages Data (Comprehensive Local Source)
            const badullaTowns = [
                "Badulla", "Bandarawela", "Haputale", "Ella", "Welimada", "Mahiyanganaya", 
                "Diyatalawa", "Hali Ela", "Passara", "Girandurukotte", "Mirahawatte", 
                "Demodara", "Lunugala", "Kandaketiya", "Meegahakiula", "Soranathota",
                "Akkarasiyaya", "Aluketiyawa", "Aluttaramma", "Ambadandegama", "Ambagahawatta",
                "Ambagasdowa", "Amunumulla", "Arawa", "Arawakumbura", "Arawatta", "Atakiriya",
                "Baduluoya", "Ballaketuwa", "Bambarapana", "Beramada", "Beragala", "Bibilegama",
                "Bogahakumbura", "Boragas", "Boralanda", "Bowela", "Dambana", "Diganatenna",
                "Dikkapitiya", "Dimbulana", "Divulapelessa", "Dulgolla", "Egodawela", "Ettampitiya",
                "Galauda", "Galedanda", "Galporuyaya", "Gamewela", "Gawarawela", "Godunna",
                "Gurutalawa", "Haldummulla", "Hangunnawa", "Hebarawa", "Heeloya", "Helahalpe",
                "Helapupula", "Hewanakumbura", "Hingurukaduwa", "Hopton", "Idalgashinna", "Jangulla",
                "Kahataruppa", "Kalubululanda", "Kalugahakandura", "Kalupahana", "Kandegedara",
                "Kandepuhulpola", "Kebillawela", "Kendagolla", "Keppetipola", "Keselpotha",
                "Ketawatta", "Kiriwanagama", "Koslanda", "Kotamuduna", "Kuruwitenna", "Kuttiyagolla",
                "Landewela", "Liyangahawela", "Lunuwatta", "Madulsima", "Makulella", "Malgoda",
                "Maliyadda", "Mapakadawewa", "Maspanna", "Maussagolla", "Medawela Udukinda",
                "Medawelagama", "Metigahatenna", "Miriyabedda", "Miyanakandura", "Namunukula",
                "Narangala", "Nelumgama", "Nikapotha", "Nugatalawa", "Ohiya", "Pahalarathkinda",
                "Pallekiruwa", "Pangaragammana", "Pattiyagedara", "Pelagahatenna", "Perawella",
                "Pitapola", "Pitamaruwa", "Puhulpola", "Ratkarawwa", "Ridimaliyadda", "Rilpola",
                "Sirimalgoda", "Silmiyapura", "Soragune", "Sorabora Colony", "Spring Valley",
                "Taldena", "Tennapanguwa", "Timbirigaspitiya", "Uduhawara", "Uraniya", "Uva Deegalla",
                "Uva Karandagolla", "Uva Mawelagama", "Uva Paranagama", "Uva Tenna", "Uva Tissapura",
                "Uva Uduwara", "Uvaparanagama", "Wewatta", "Wineethagama", "Yalagamuwa", "Yalwela"
            ];

            locInput.addEventListener('input', function() {
                clearTimeout(locDebounce);
                latInput.value = 0; // Reset coords
                lngInput.value = 0;
                
                const query = this.value.toLowerCase();
                locSuggestions.innerHTML = '';
                
                if(query.length < 2) {
                    locSuggestions.style.display = 'none';
                    return;
                }

                // 1. Local Search (Instant)
                const localMatches = badullaTowns.filter(town => town.toLowerCase().includes(query));
                
                // Clear previous API results/loading state but keep local if valid
                // Actually, simplest is to render local, then append API results later.
                renderSuggestions(localMatches, []);

                // 2. API Background Search (for smaller villages)
                locDebounce = setTimeout(() => {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=lk&limit=5&addressdetails=1`)
                        .then(res => res.json())
                        .then(data => {
                            // Filter for Badulla/Uva
                            const apiMatches = data.filter(place => {
                                const str = JSON.stringify(place).toLowerCase();
                                return str.includes('badulla') || str.includes('uva');
                            });
                            
                            // Combine unique results
                            const uniqueApiMatches = apiMatches.filter(apiItem => {
                                let name = apiItem.address.village || apiItem.address.town || apiItem.display_name.split(',')[0];
                                return !localMatches.some(local => local.toLowerCase() === name.toLowerCase());
                            });

                            if(uniqueApiMatches.length > 0) {
                                renderSuggestions(localMatches, uniqueApiMatches);
                            }
                        });
                }, 500); // 500ms delay to avoid spamming API
            });
            
            function renderSuggestions(localList, apiList) {
                if (localList.length === 0 && apiList.length === 0) {
                     // Don't hide immediately if waiting for API? 
                     // actually if local is empty, we hide. If API comes later, we show.
                     if(locSuggestions.innerHTML === '') locSuggestions.style.display = 'none';
                     return;
                }
                
                locSuggestions.innerHTML = '';
                
                // Render Local
                localList.forEach(town => {
                    const div = document.createElement('div');
                    div.style.padding = '10px 15px';
                    div.style.borderBottom = '1px solid #f1f5f9';
                    div.style.cursor = 'pointer';
                    div.style.fontSize = '0.9rem';
                    div.style.fontWeight = '600'; // Bold for local (popular)
                    div.style.color = '#166534'; 
                    div.textContent = town + " (Badulla Dist.)"; 
                    div.onclick = () => selectLocationWithGeocode(town);
                    div.onmouseover = () => div.style.background = '#f0fdf4';
                    div.onmouseout = () => div.style.background = 'white';
                    locSuggestions.appendChild(div);
                });

                // Render API
                apiList.forEach(place => {
                    const div = document.createElement('div');
                    div.style.padding = '10px 15px';
                    div.style.borderBottom = '1px solid #f1f5f9';
                    div.style.cursor = 'pointer';
                    div.style.fontSize = '0.9rem';
                    
                    // Display Name
                    let display = place.address.village || place.address.town || place.address.city || place.name;
                    if(place.address.state) display += `, ${place.address.state}`;
                    
                    div.textContent = display; 
                    div.onclick = () => selectLocation(display, place.lat, place.lon);
                    div.onmouseover = () => div.style.background = '#f8fafc';
                    div.onmouseout = () => div.style.background = 'white';
                    locSuggestions.appendChild(div);
                });
                
                locSuggestions.style.display = 'block';
            }

            function selectLocation(name, lat, lng) {
                locInput.value = name;
                latInput.value = lat;
                lngInput.value = lng;
                locSuggestions.style.display = 'none';
            }

            // Geocode location name to get coordinates
            function selectLocationWithGeocode(townName) {
                locInput.value = townName;
                locSuggestions.style.display = 'none';
                
                // Show loading state
                locInput.style.opacity = '0.6';
                
                // Geocode the town name to get coordinates
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(townName + ', Badulla, Sri Lanka')}&limit=1`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            latInput.value = data[0].lat;
                            lngInput.value = data[0].lon;
                            console.log(`Geocoded ${townName}: ${data[0].lat}, ${data[0].lon}`);
                        } else {
                            // Fallback: try without district
                            return fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(townName + ', Sri Lanka')}&limit=1`)
                                .then(res => res.json())
                                .then(fallbackData => {
                                    if (fallbackData && fallbackData.length > 0) {
                                        latInput.value = fallbackData[0].lat;
                                        lngInput.value = fallbackData[0].lon;
                                        console.log(`Geocoded ${townName} (fallback): ${fallbackData[0].lat}, ${fallbackData[0].lon}`);
                                    }
                                });
                        }
                    })
                    .catch(err => {
                        console.error('Geocoding error:', err);
                    })
                    .finally(() => {
                        locInput.style.opacity = '1';
                    });
            }

            // Close suggestions on click outside
            document.addEventListener('click', function(e) {
                if (!locInput.contains(e.target) && !locSuggestions.contains(e.target)) {
                    locSuggestions.style.display = 'none';
                }
            });


        };
    </script>
</body>
</html>
