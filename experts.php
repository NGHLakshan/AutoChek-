<?php
session_start();
require_once 'db.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 0;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : 0;
$location_text = isset($_GET['location']) ? trim($_GET['location']) : '';

// --- OPTIMIZATION: LOCAL TOWN LOOKUP ---
$town_coords = [
    "Badulla" => ["lat" => 6.9934, "lng" => 81.0550],
    "Bandarawela" => ["lat" => 6.8310, "lng" => 80.9984],
    "Haputale" => ["lat" => 6.7686, "lng" => 80.9576],
    "Ella" => ["lat" => 6.8761, "lng" => 81.0475],
    "Welimada" => ["lat" => 6.9031, "lng" => 80.9142],
    "Mahiyanganaya" => ["lat" => 7.3204, "lng" => 81.0028],
    "Diyatalawa" => ["lat" => 6.8189, "lng" => 80.9592],
    "Hali Ela" => ["lat" => 6.9531, "lng" => 81.0315],
    "Passara" => ["lat" => 7.0006, "lng" => 81.1444],
    "Colombo" => ["lat" => 6.9271, "lng" => 79.8612]
];

// --- SERVER-SIDE GEOCODING FALLBACK ---
if (!empty($location_text) && $location_text !== "Current Location" && ($lat == 0 || $lng == 0)) {
    // 1. Check Local Lookup
    $found_locally = false;
    foreach ($town_coords as $name => $coords) {
        if (stripos($location_text, $name) !== false) {
            $lat = $coords['lat'];
            $lng = $coords['lng'];
            $found_locally = true;
            break;
        }
    }

    // 2. External API (only if not found locally)
    if (!$found_locally) {
        $geo_url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($location_text . ", Sri Lanka") . "&limit=1";
        $opts = [
            "http" => [
                "timeout" => 2, // Fail fast
                "header" => "User-Agent: AutoChekSearch/1.0\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $geo_data_text = @file_get_contents($geo_url, false, $context);
        if ($geo_data_text) {
            $geo_data = json_decode($geo_data_text, true);
            if (isset($geo_data[0])) {
                $lat = floatval($geo_data[0]['lat']);
                $lng = floatval($geo_data[0]['lon']);
            }
        }
    }
}
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
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
            --secondary: #64748b;
            --accent: #8b5cf6;
            --bg-body: #f8fafc;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
            --glass-bg: rgba(255, 255, 255, 0.9);
            --glass-border: rgba(255, 255, 255, 0.3);
        }

        body { 
            background: var(--bg-body);
            background-image: radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.05) 0, transparent 50%), 
                              radial-gradient(at 50% 0%, rgba(139, 92, 246, 0.05) 0, transparent 50%);
            min-height: 100vh;
        }

        /* Hero Section */
        .hero-section {
            background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.08), transparent),
                        radial-gradient(circle at bottom left, rgba(139, 92, 246, 0.05), transparent),
                        white;
            padding: 120px 0 100px;
            color: var(--text-dark);
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: -60px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 70% 30%, rgba(16, 185, 129, 0.15) 0, transparent 70%);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            color: #0f172a;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .hero-title span {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: #64748b;
            max-width: 700px;
            margin: 0 auto 48px;
            line-height: 1.6;
        }

        /* Premium Search Bar */
        .search-container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }

        .search-box { 
            background: rgba(255, 255, 255, 0.95);
            padding: 12px;
            border-radius: 20px; 
            border: 1px solid #e2e8f0;
            box-shadow: var(--card-shadow);
            display: grid;
            grid-template-columns: 1fr auto 1fr auto;
            gap: 0;
            align-items: center;
        }

        .search-field {
            position: relative;
            display: flex;
            align-items: center;
            flex: 1;
            transition: all 0.2s ease;
            border-radius: 12px;
            min-height: 54px;
        }

        .search-field:hover {
            background: rgba(16, 185, 129, 0.03);
        }

        .search-field i.ph {
            position: absolute;
            left: 18px;
            color: #64748b;
            font-size: 1.2rem;
            pointer-events: none;
            z-index: 5;
        }

        .search-box input { 
            width: 100%;
            padding: 14px 15px 14px 50px !important;
            border: none !important;
            background: transparent !important;
            border-radius: 12px !important;
            font-size: 1rem !important;
            color: #1e293b !important;
            transition: all 0.2s ease !important;
            margin: 0 !important;
        }

        .search-box input:focus {
            background: white !important;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px var(--primary-light) !important;
        }

        #locate-btn {
            position: absolute;
            right: 12px;
            left: auto !important;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            border-radius: 8px;
            color: var(--secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            pointer-events: auto !important;
            z-index: 10;
        }

        #locate-btn:hover {
            background: var(--primary-light);
            color: var(--primary);
            transform: scale(1.05);
        }

        .btn-search {
            background: var(--primary);
            color: white;
            padding: 12px 32px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-left: 12px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-search:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }

        .search-suggestions-box {
            position: absolute;
            top: calc(100% + 12px);
            left: 0;
            width: 450px;
            background: white;
            border-radius: 16px;
            z-index: 1000;
            display: none;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
            border: 1px solid #e2e8f0;
            animation: slideUpFade 0.3s ease;
        }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse-locating {
            0% { opacity: 0.6; }
            50% { opacity: 1; color: var(--primary); }
            100% { opacity: 0.6; }
        }

        .locating-active {
            animation: pulse-locating 1.5s infinite !important;
        }

        /* Results Display */
        .results-section {
            padding: 40px 0;
            max-width: 1000px;
            margin: 0 auto;
        }

        .result-group-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--secondary);
            margin: 40px 0 20px;
        }

        .result-group-title i {
            color: var(--primary);
            background: var(--primary-light);
            padding: 8px;
            border-radius: 10px;
        }

        .expert-card { 
            background: white; 
            border-radius: 20px; 
            padding: 24px; 
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            border: 1px solid #f1f5f9;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: fadeInSlideUp 0.6s ease-out forwards;
        }

        @keyframes fadeInSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .expert-card:hover { 
            transform: translateY(-5px) scale(1.01); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-light);
        }

        .expert-photo-container {
            width: 90px;
            height: 90px;
            border-radius: 18px;
            overflow: hidden;
            background: #f8fafc;
            border: 3px solid white;
            box-shadow: 0 8px 16px rgba(0,0,0,0.05);
        }

        .expert-photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .distance-tag {
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .exp-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .btn-view {
            background: var(--primary);
            color: white;
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-view:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            color: white;
        }

        /* Loading Skeleton Mockup */
        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #f8f8f8 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
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

    <div class="hero-section">
        <div class="container" style="max-width: 900px;">
            <h1 class="hero-title">Expert <span>Vehicle</span> Inspection</h1>
            <p class="hero-subtitle">Find certified professionals to inspect your next vehicle. Local experts, thorough reports, total peace of mind.</p>
            
            <div class="search-container">
                <form action="" method="GET" class="search-box" autocomplete="off">
                    <input type="hidden" name="lat" id="search-lat" value="<?php echo $lat; ?>">
                    <input type="hidden" name="lng" id="search-lng" value="<?php echo $lng; ?>">
                    
                    <div class="search-field">
                        <i class="ph ph-magnifying-glass"></i>
                        <input type="text" id="main-search" name="search" placeholder="Vehicle (e.g. Toyota, Benz...)" value="<?php echo htmlspecialchars($search); ?>">
                        <div id="search-suggestions" class="search-suggestions-box"></div>
                    </div>

                    <div style="width: 1px; height: 35px; background: #e2e8f0; margin: 0 5px;"></div>

                    <div class="search-field">
                        <i class="ph ph-map-pin"></i>
                        <input type="text" id="location-input" name="location" placeholder="Location (e.g. Badulla)" value="<?php echo htmlspecialchars(isset($_GET['location']) ? $_GET['location'] : ''); ?>">
                        <i class="ph ph-crosshair" id="locate-btn" title="Current Location"></i>
                        <div id="location-suggestions" class="search-suggestions-box"></div>
                    </div>

                    <button type="submit" class="btn-search">
                        <span>Search</span>
                        <i class="ph ph-arrow-right"></i>
                    </button>
                    
                    <!-- Hidden filters for backwards compat -->
                    <select name="category_id" id="category_select" style="display:none;"><option value="0"></option></select>
                    <select name="brand_id" id="brand_select" style="display:none;"><option value="0"></option></select>
                    <select name="model_id" id="model_select" style="display:none;"><option value="0"></option></select>
                </form>
            </div>
        </div>
    </div>

    <div class="container results-section">
        <div id="location-msg" style="margin-bottom: 20px; color: #64748b; font-size: 0.9rem; text-align: center;"></div>

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
        $location_text_escaped = $conn->real_escape_string($location_text);
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
             // GPS: No strict limit, we want to show 'Nearby' vs 'Other' sections
             $having_clause = ""; 
        } elseif (!empty($location_text)) {
             // Text Only: Filter by District OR Location Text match in District or Bio
             $target_district_escaped = $conn->real_escape_string($target_district);
             if (!empty($target_district)) {
                 $conditions[] = "(e.district LIKE '%$target_district_escaped%' OR e.district LIKE '%$location_text_escaped%' OR e.bio LIKE '%$location_text_escaped%')";
             } else {
                 $conditions[] = "(e.district LIKE '%$location_text_escaped%' OR e.bio LIKE '%$location_text_escaped%')";
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

        // Text Search Filter (Word-based AND matching)
        if (!empty($search)) {
            $words = explode(' ', $search);
            $word_conditions = [];
            foreach ($words as $word) {
                $w = $conn->real_escape_string(trim($word));
                if (empty($w)) continue;
                $word_conditions[] = "(e.qualification LIKE '%$w%' OR e.name LIKE '%$w%' OR e.bio LIKE '%$w%' OR vc.name LIKE '%$w%' OR vb.name LIKE '%$w%' OR vm.name LIKE '%$w%')";
            }
            if (!empty($word_conditions)) {
                $sql .= " AND (" . implode(' AND ', $word_conditions) . ")";
            }
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
        $nearby_experts = [];   // <= 15km
        $other_experts = [];    // > 15km or no GPS

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Categorize based on distance if GPS is used
                if (isset($row['distance']) && $row['distance'] !== null) {
                    if ($row['distance'] <= 15) {
                        $nearby_experts[] = $row;
                    } else {
                        $other_experts[] = $row;
                    }
                } else {
                    // If no GPS, put all in other experts
                    $other_experts[] = $row;
                }
            }
        }

        // Function to render expert card
        function renderExpertCard($row, $location_text) {
            ?>
            <div class="expert-card">
                <div style="display: flex; gap: 24px; align-items: flex-start;">
                    <div class="expert-photo-container">
                        <?php if (!empty($row['profile_photo'])): ?>
                            <img src="uploads/profiles/<?php echo $row['profile_photo']; ?>" alt="Expert">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #cbd5e1; font-size: 2.5rem;">
                                <i class="ph ph-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div>
                                <h3 style="margin-bottom: 6px; font-size: 1.4rem; font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($row['name']); ?></h3>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if (isset($row['distance']) && $row['distance'] !== null): ?>
                                        <span class="distance-tag">
                                            <i class="ph-fill ph-map-pin"></i> <?php echo round($row['distance'], 1); ?> km away
                                        </span>
                                    <?php endif; ?>
                                    <span style="color: #64748b; font-size: 0.85rem; font-weight: 500; display: flex; align-items: center; gap: 4px;">
                                        <i class="ph ph-navigation-arrow"></i> <?php echo htmlspecialchars($row['district']); ?>
                                    </span>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span class="exp-badge"><?php echo $row['experience']; ?> Yrs Exp</span>
                            </div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <div style="font-weight: 600; color: #475569; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                                <i class="ph ph-shield-check" style="color: var(--primary);"></i>
                                <?php echo htmlspecialchars($row['qualification']); ?>
                            </div>
                        </div>

                        <?php if (!empty($row['bio'])): ?>
                            <p style="margin: 0 0 20px 0; color: #64748b; font-size: 0.95rem; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($row['bio']); ?>
                            </p>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 12px;">
                                <?php if (!empty($row['linkedin_url'])): ?>
                                    <a href="<?php echo $row['linkedin_url']; ?>" target="_blank" style="color: #64748b; font-size: 1.1rem;"><i class="ph ph-linkedin-logo"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($row['website_url'])): ?>
                                    <a href="<?php echo $row['website_url']; ?>" target="_blank" style="color: #64748b; font-size: 1.1rem;"><i class="ph ph-globe"></i></a>
                                <?php endif; ?>
                            </div>
                            <a href="expert_profile.php?id=<?php echo htmlspecialchars($row['expert_id']); ?>" class="btn-view">View Profile</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }


        // Display Results
        if (count($nearby_experts) > 0 || count($other_experts) > 0) {
            // 1. Nearby Experts Section
            if (count($nearby_experts) > 0) {
                ?>
                <div class="result-group">
                    <h3 class="result-group-title">
                        <i class="ph ph-navigation-arrow"></i> 
                        Nearby Experts <span>(Within 15km)</span>
                    </h3>
                    <?php foreach ($nearby_experts as $expert) renderExpertCard($expert, $location_text); ?>
                </div>
                <?php
            }

            // 2. Other District Experts Section
            if (count($other_experts) > 0) {
                ?>
                <div class="result-group" style="margin-top: 60px;">
                    <h3 class="result-group-title">
                        <i class="ph ph-buildings"></i> 
                        Other District Experts
                    </h3>
                    <?php foreach ($other_experts as $expert) renderExpertCard($expert, $location_text); ?>
                </div>
                <?php
            }
        } else {
             echo "<div style='text-align:center; padding: 80px 40px; background: white; border-radius: 20px; border: 1px dashed #e2e8f0;'>
                    <i class='ph ph-magnifying-glass' style='font-size: 3rem; color: #cbd5e1; margin-bottom: 20px;'></i>
                    <h3 style='color: #475569;'>No experts found</h3>
                    <p style='color: #94a3b8;'>Try adjusting your vehicle or location filters.</p>
                   </div>";
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
        
        const localTownCoords = <?php echo json_encode($town_coords); ?>;

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
                                    div.className = 'suggestion-item';
                                    div.style.padding = '14px 20px';
                                    div.style.cursor = 'pointer';
                                    div.style.transition = 'all 0.2s ease';
                                    div.style.borderBottom = '1px solid #f1f5f9';
                                    
                                    div.onmouseover = () => {
                                        div.style.background = 'var(--primary-light)';
                                        div.style.paddingLeft = '25px';
                                    };
                                    div.onmouseout = () => {
                                        div.style.background = 'transparent';
                                        div.style.paddingLeft = '20px';
                                    };
                                    
                                    div.innerHTML = `
                                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                            <div style="flex: 1; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; padding-right: 15px;">
                                                <div style="font-weight: 600; color: #1e293b; font-size: 1rem;">${item.name}</div>
                                                <div style="color: #64748b; font-size: 0.85rem; margin-top: 2px;">${item.subtext || ''}</div>
                                            </div>
                                            <span style="background: ${item.type === 'external' ? 'var(--primary-light)' : '#f1f5f9'}; 
                                                         color: ${item.type === 'external' ? 'var(--primary-dark)' : '#475569'}; 
                                                         padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; border: 1px solid ${item.type === 'external' ? 'var(--primary)' : '#e2e8f0'}; flex-shrink: 0; letter-spacing: 0.05em;">
                                                ${item.type_label}
                                            </span>
                                        </div>
                                    `;
                                    
                                    div.onclick = () => {
                                        const proceed = (itemData) => {
                                            const currentLoc = document.getElementById('location-input').value;
                                            const currentLat = document.getElementById('search-lat').value;
                                            const currentLng = document.getElementById('search-lng').value;
                                            let baseRedirect = '';
                                            
                                            if (itemData.type === 'category') {
                                                baseRedirect = `?category_id=${itemData.id}&search=${encodeURIComponent(itemData.name)}`;
                                            } else if (itemData.type === 'brand') {
                                                baseRedirect = `?brand_id=${itemData.id}&search=${encodeURIComponent(itemData.name)}`;
                                            } else if (itemData.type === 'model') {
                                                baseRedirect = `?model_id=${itemData.id}&search=${encodeURIComponent(itemData.name)}`;
                                            }

                                            if (currentLoc) {
                                                baseRedirect += `&location=${encodeURIComponent(currentLoc)}&lat=${currentLat}&lng=${currentLng}`;
                                            }
                                            window.location.href = baseRedirect;
                                        };

                                        if (item.external) {
                                            // Auto-Ingest from Global DB
                                            div.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving to local database...';
                                            const formData = new FormData();
                                            formData.append('brand', item.brand);
                                            formData.append('model', item.name);
                                            
                                            fetch('ingest_vehicle.php', { method: 'POST', body: formData })
                                                .then(res => res.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        proceed({ type: 'model', id: data.id, name: data.name });
                                                    } else {
                                                        alert("Error saving vehicle data.");
                                                    }
                                                });
                                        } else {
                                            proceed(item);
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
            
            searchForm.addEventListener('submit', function(e) {
                const locationInput = document.getElementById('location-input');
                const locationValue = locationInput.value.trim();
                const latInput = document.getElementById('search-lat');
                const lngInput = document.getElementById('search-lng');
                const latValue = parseFloat(latInput.value);
                const lngValue = parseFloat(lngInput.value);
                
                // If location is entered but coordinates are missing or zero
                if (locationValue && locationValue !== "Current Location" && (!latValue || !lngValue || (latValue === 0 && lngValue === 0))) {
                    // 1. Check Local Lookup (INSTANT)
                    let found = false;
                    for (const [name, coords] of Object.entries(localTownCoords)) {
                         if (locationValue.toLowerCase().includes(name.toLowerCase())) {
                             latInput.value = coords.lat;
                             lngInput.value = coords.lng;
                             found = true;
                             break;
                         }
                    }

                    if (found) {
                        return; // Allow form to submit normally
                    }

                    e.preventDefault(); // Stop form submission for API geocoding
                    
                    // Show loading indicator
                    const submitBtn = searchForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Locating...';
                    submitBtn.disabled = true;
                    
                    // Geocode the location with a timeout
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 2000);

                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(locationValue + ', Sri Lanka')}&limit=1`, { signal: controller.signal })
                        .then(res => res.json())
                        .then(data => {
                            clearTimeout(timeoutId);
                            if (data && data.length > 0) {
                                latInput.value = data[0].lat;
                                lngInput.value = data[0].lon;
                            }
                            searchForm.submit();
                        })
                        .catch(err => {
                            console.warn('Geocoding slow or failed:', err);
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
                
                // 1. Check Local Lookup
                for (const [name, coords] of Object.entries(localTownCoords)) {
                    if (townName.toLowerCase().includes(name.toLowerCase())) {
                        latInput.value = coords.lat;
                        lngInput.value = coords.lng;
                        return; // Instant
                    }
                }

                // Show loading state
                locInput.style.opacity = '0.6';
                
                // 2. Geocode manually if not in local map
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


            // --- Live Location Logic ---
            const locateBtn = document.getElementById('locate-btn');
            
            locateBtn.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported by your browser.');
                    return;
                }

                // UI Feedback
                locateBtn.style.color = 'var(--primary)';
                locateBtn.classList.add('ph-spinner', 'ph-spin', 'locating-active');
                locateBtn.classList.remove('ph-crosshair');
                const locInput = document.getElementById('location-input');
                locInput.placeholder = "Locating...";
                locInput.classList.add('locating-active');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        locInput.classList.remove('locating-active');
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        document.getElementById('search-lat').value = lat;
                        document.getElementById('search-lng').value = lng;
                        document.getElementById('location-input').value = "Current Location";
                        
                        // Submit immediately
                        document.querySelector('.search-box').submit();
                    },
                    (error) => {
                        console.error('Error getting location:', error);
                        alert('Unable to retrieve your location. Please check your browser permissions.');
                        
                        // Reset UI
                        locateBtn.style.color = '#64748b';
                        locateBtn.classList.remove('ph-spinner', 'ph-spin');
                        locateBtn.classList.add('ph-crosshair');
                        document.getElementById('location-input').placeholder = "Location (e.g. Badulla)";
                    }
                );
            });

        };
    </script>
</body>
</html>
