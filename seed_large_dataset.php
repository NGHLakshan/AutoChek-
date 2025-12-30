<?php
require_once 'db.php';
set_time_limit(300); // Allow time for massive inserts

echo "Starting massive vehicle population...<br>";

// 1. Define Helper Function
function getOrInsertCategory($conn, $name) {
    $res = $conn->query("SELECT category_id FROM vehicle_categories WHERE name = '$name'");
    if ($row = $res->fetch_assoc()) return $row['category_id'];
    $conn->query("INSERT INTO vehicle_categories (name) VALUES ('$name')");
    return $conn->insert_id;
}

function insertBrandAndModels($conn, $cat_id, $brand_name, $models) {
    // Check if brand exists
    $brand_id = 0;
    $res = $conn->query("SELECT brand_id FROM vehicle_brands WHERE name = '$brand_name' AND category_id = $cat_id");
    if ($row = $res->fetch_assoc()) {
        $brand_id = $row['brand_id'];
    } else {
        $conn->query("INSERT INTO vehicle_brands (name, category_id) VALUES ('$brand_name', $cat_id)");
        $brand_id = $conn->insert_id;
    }

    foreach ($models as $model) {
        // Insert Ignore to avoid dupes
        $conn->query("INSERT IGNORE INTO vehicle_models (name, brand_id) VALUES ('$model', $brand_id)");
    }
}

// 2. Categories
$cat_car = getOrInsertCategory($conn, 'Car');
$cat_bike = getOrInsertCategory($conn, 'Bike');
$cat_scooter = getOrInsertCategory($conn, 'Scooter');
$cat_van = getOrInsertCategory($conn, 'Van');
$cat_suv = getOrInsertCategory($conn, 'SUV');
$cat_lorry = getOrInsertCategory($conn, 'Lorry');
$cat_bus = getOrInsertCategory($conn, 'Bus');
$cat_hw = getOrInsertCategory($conn, 'Heavy Machinery');
$cat_tuk = getOrInsertCategory($conn, 'Three-Wheeler');

// 3. Data Definitions (Massive List)

// --- CARS ---
$cars = [
    'Toyota' => ['Corolla', 'Axio', 'Prius', 'Yaris', 'Camry', 'Vitz', 'Wigo', 'Allion', 'Premio', 'Aqua', 'Crown', 'Passo'],
    'Honda' => ['Civic', 'Fit', 'Jazz', 'Grace', 'Insight', 'Accord', 'Vezel', 'City', 'CR-Z'],
    'Nissan' => ['Sunny', 'Leaf', 'March', 'Tiida', 'Sylphy', 'Note', 'GTR', 'Skyline', 'Dayz'],
    'Suzuki' => ['Alto', 'WagonR', 'Swift', 'Celerio', 'Ignis', 'Baleno', 'Spacia', 'Every'],
    'Mitsubishi' => ['Lancer', 'Mirage', 'Galant', 'Eclipse'],
    'Mazda' => ['Axela', 'Demio', 'RX-8', 'CX-3', 'Mazda3', 'Mazda6'],
    'BMW' => ['3 Series', '5 Series', '7 Series', 'X1', 'X3', 'X5', 'i8', 'i3'],
    'Mercedes-Benz' => ['C-Class', 'E-Class', 'S-Class', 'A-Class', 'CLA', 'GLA'],
    'Audi' => ['A3', 'A4', 'A6', 'Q2', 'Q3', 'Q5', 'Q7'],
    'Kia' => ['Picanto', 'Rio', 'Sorento', 'Sportage'],
    'Hyundai' => ['Elantra', 'Tucson', 'Santa Fe', 'Grand i10', 'Sonata'],
    'Perodua' => ['Axia', 'Bezza', 'Viva', 'Kelisa'],
    'Micro' => ['Panda', 'Emgrand', 'Mx7'],
    'Tesla' => ['Model S', 'Model 3', 'Model X', 'Model Y'],
    'Ford' => ['Mustang', 'Fiesta', 'Focus', 'Mondeo'],
    'Chevrolet' => ['Cruze', 'Spark', 'Camaro', 'Corvette']
];
foreach ($cars as $brand => $models) insertBrandAndModels($conn, $cat_car, $brand, $models);

// --- SUVs ---
$suvs = [
    'Toyota' => ['Land Cruiser', 'Prado', 'Hilux Surf', 'Fortuner', 'RAV4', 'C-HR', 'Harrier', 'Rush'],
    'Mitsubishi' => ['Montero', 'Pajero', 'Outlander'],
    'Nissan' => ['X-Trail', 'Patrol', 'Navara', 'Juke'],
    'Land Rover' => ['Defender', 'Range Rover', 'Range Rover Sport', 'Evoque', 'Discovery'],
    'Jeep' => ['Wrangler', 'Cherokee', 'Compass'],
    'Suzuki' => ['Grand Vitara', 'Jimny', 'Hustler']
];
foreach ($suvs as $brand => $models) insertBrandAndModels($conn, $cat_suv, $brand, $models);

// --- BIKES ---
$bikes = [
    'Honda' => ['CBR', 'Hornet', 'Jade', 'CD125', 'CB Shine', 'Unicorn'],
    'Yamaha' => ['FZ', 'Fazer', 'R15', 'MT-15', 'R1', 'R6', 'TW200'],
    'Bajaj' => ['Pulsar 150', 'Pulsar 180', 'Pulsar 200NS', 'CT 100', 'Platina', 'Discover', 'Avenger'],
    'TVS' => ['Apache 160', 'Apache 200', 'Metro', 'Stryker'],
    'Hero' => ['Hunk', 'Glamour', 'Splendor', 'Passion'],
    'KTM' => ['Duke 200', 'Duke 390', 'RC 200', 'RC 390'],
    'Royal Enfield' => ['Classic 350', 'Bullet', 'Himalayan'],
    'Suzuki' => ['Gixxer', 'GN125', 'Volty']
];
foreach ($bikes as $brand => $models) insertBrandAndModels($conn, $cat_bike, $brand, $models);

// --- SCOOTERS ---
$scooters = [
    'Honda' => ['Dio', 'Activa', 'Grazia', 'Scoopy'],
    'Yamaha' => ['Ray ZR', 'Fascino', 'NMAX'],
    'TVS' => ['Ntorq', 'Wego', 'Jupiter', 'Scooty Pep'],
    'Suzuki' => ['Burgman', 'Access'],
    'Vespa' => ['LX125', 'VXL', 'SXL']
];
foreach ($scooters as $brand => $models) insertBrandAndModels($conn, $cat_scooter, $brand, $models);

// --- THREE-WHEELERS ---
$tuks = [
    'Bajaj' => ['RE 205', 'RE 4S', 'Maxima'],
    'TVS' => ['King'],
    'Piaggio' => ['Ape']
];
foreach ($tuks as $brand => $models) insertBrandAndModels($conn, $cat_tuk, $brand, $models);

// --- VANS ---
$vans = [
    'Toyota' => ['Hiace', 'TownAce', 'RegiusAce', 'Noah', 'Voxy', 'Esquire'],
    'Nissan' => ['Caravan', 'Vanette', 'NV200', 'Urvan'],
    'Mazda' => ['Bongo'],
    'Suzuki' => ['Every'],
    'Mitsubishi' => ['Delica', 'L300']
];
foreach ($vans as $brand => $models) insertBrandAndModels($conn, $cat_van, $brand, $models);

// --- LORRIES / TRUCKS ---
$lorries = [
    'Isuzu' => ['Elf', 'NKR', 'NPR', 'Forward', 'Giga'],
    'Mitsubishi' => ['Canter', 'Fuso Fighter'],
    'Tata' => ['Ace', 'Xenon', 'LPK', 'Prima', '407', '1613'],
    'Ashok Leyland' => ['Dost', 'Ecomet', 'Comet', 'Taurus'],
    'Toyota' => ['Dyna', 'ToyoAce'],
    'Mahindra' => ['Bolero', 'Maxximo']
];
foreach ($lorries as $brand => $models) insertBrandAndModels($conn, $cat_lorry, $brand, $models);

// --- BUSES ---
$buses = [
    'Ashok Leyland' => ['Viking', 'Cheetah', 'Sunshine'],
    'Tata' => ['Starbus', 'Marcopolo', 'CityRide'],
    'Toyota' => ['Coaster'],
    'Mitsubishi' => ['Rosa'],
    'Nissan' => ['Civilian'],
    'Isuzu' => ['Journey']
];
foreach ($buses as $brand => $models) insertBrandAndModels($conn, $cat_bus, $brand, $models);

// --- HEAVY MACHINERY ---
$heavy = [
    'JCB' => ['3DX', 'JS205', 'Telehandler'],
    'Komatsu' => ['PC200', 'D65', 'GD511'],
    'Caterpillar' => ['320D', 'D6', 'Backhoe'],
    'Kubota' => ['U30', 'U50'],
    'Kobelco' => ['SK200']
];
foreach ($heavy as $brand => $models) insertBrandAndModels($conn, $cat_hw, $brand, $models);

echo "Massive database population complete. <br>";
echo "<a href='profile_settings.php'>Go back to Settings</a>";
?>
