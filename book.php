<?php
session_start();
require_once 'db.php';

// Check if logged in and is a BUYER
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit;
}

$buyer_id = $_SESSION['user_id'];
$expert_id = isset($_GET['expert_id']) ? intval($_GET['expert_id']) : 0;
$message = "";

// Fetch Expert Data (Name and Packages)
$expert_name = "Unknown Expert";
$expert_packages = [];
$expert_specialties = []; // To store categories, brands, models

if ($expert_id > 0) {
    $res = $conn->query("SELECT name, packages FROM expert WHERE expert_id = $expert_id");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $expert_name = $row['name'];
        $expert_packages = json_decode($row['packages'], true) ?: [];
    }

    // 1. Fetch Specialization Categories
    $res = $conn->query("SELECT c.name FROM expert_vehicle_categories esc JOIN vehicle_categories c ON esc.category_id = c.category_id WHERE esc.expert_id = $expert_id");
    while($row = $res->fetch_assoc()) $expert_specialties[] = $row['name'];

    // 2. Fetch Specialization Brands
    $res = $conn->query("SELECT b.name FROM expert_vehicle_brands esb JOIN vehicle_brands b ON esb.brand_id = b.brand_id WHERE esb.expert_id = $expert_id");
    while($row = $res->fetch_assoc()) $expert_specialties[] = $row['name'];

    // 3. Fetch Specialization Models
    $res = $conn->query("SELECT m.name FROM expert_vehicle_models esm JOIN vehicle_models m ON esm.model_id = m.model_id WHERE esm.expert_id = $expert_id");
    while($row = $res->fetch_assoc()) $expert_specialties[] = $row['name'];
    
    $expert_specialties = array_unique($expert_specialties);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_type = $_POST['vehicle_type'];
    $location = $_POST['location'];
    $booking_date = $_POST['booking_date'];
    $expert_id = $_POST['expert_id'];
    $package_info = explode('|', $_POST['package_info']); // "Name|Price"
    $p_name = $package_info[0];
    $p_price = floatval($package_info[1]);
    $service_type = $_POST['service_type'];

    $stmt = $conn->prepare("INSERT INTO booking (buyer_id, expert_id, vehicle_type, location, booking_date, status, package_name, package_price, service_type) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
    $stmt->bind_param("iissssds", $buyer_id, $expert_id, $vehicle_type, $location, $booking_date, $p_name, $p_price, $service_type);

    if ($stmt->execute()) {
        $message = "<div class='alert-success'>Booking Request Sent! <a href='dashboard.php'>Go to Dashboard</a></div>";
    } else {
        $message = "<div class='alert-error'>Error: " . $stmt->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Inspection | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #ecfdf5;
            --secondary: #64748b;
            --text-dark: #1e293b;
        }

        body {
            background-color: #f8fafc;
            color: var(--text-dark);
        }

        .form-container {
            max-width: 550px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }

        .form-group { position: relative; margin-bottom: 24px; }
        label { display: block; margin-bottom: 10px; font-weight: 700; color: #475569; font-size: 0.9rem; }
        
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.ph-main {
            position: absolute;
            left: 16px;
            color: #94a3b8;
            font-size: 1.2rem;
            pointer-events: none;
            z-index: 5;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px 16px 14px 48px !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            font-family: inherit;
            font-size: 1rem !important;
            color: var(--text-dark) !important;
            background: #fff !important;
            transition: all 0.2s ease !important;
            box-sizing: border-box;
        }

        input:focus, select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px var(--primary-light) !important;
            outline: none !important;
        }

        #locate-btn {
            position: absolute;
            right: 12px;
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
            z-index: 10;
        }

        #locate-btn:hover {
            background: var(--primary-light);
            color: var(--primary);
            transform: scale(1.05);
        }

        .search-suggestions-box {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 100%;
            background: white;
            border-radius: 12px;
            z-index: 1000;
            display: none;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            animation: slideUpFade 0.2s ease;
        }

        .suggestion-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        .suggestion-item:hover {
            background: var(--primary-light);
            padding-left: 20px;
        }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(8px); }
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

        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            font-size: 1rem;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }

        .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 10px; margin-bottom: 24px; border-left: 4px solid var(--primary); }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 10px; margin-bottom: 24px; border-left: 4px solid #ef4444; }

        /* Specialty Tags */
        .specialty-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .specialty-tag {
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .specialty-tag:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .specialty-tag i {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2 style="margin-top:0;">Book Inspection</h2>
            <p>Requesting service from: <strong><?php echo htmlspecialchars($expert_name); ?></strong></p>
            
            <?php echo $message; ?>

            <form method="POST" action="" id="booking-form">
                <input type="hidden" name="expert_id" value="<?php echo $expert_id; ?>">
                <input type="hidden" name="lat" id="search-lat" value="0">
                <input type="hidden" name="lng" id="search-lng" value="0">
                
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <div class="input-wrapper">
                        <i class="ph ph-car ph-main"></i>
                        <select name="vehicle_type" id="vehicle-input" required style="padding-left: 48px !important;">
                            <option value="" disabled selected>Select a vehicle type the expert specializes in</option>
                            <?php if (!empty($expert_specialties)): ?>
                                <?php foreach ($expert_specialties as $tag): ?>
                                    <option value="<?php echo htmlspecialchars($tag); ?>">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="General Vehicle">General Vehicle Inspection</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php if (empty($expert_specialties)): ?>
                        <small style="color: #64748b; margin-top: 8px; display: block;">Note: This expert handles generic vehicle inspections.</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Inspection Location</label>
                    <div class="input-wrapper">
                        <i class="ph ph-map-pin ph-main"></i>
                        <input type="text" name="location" id="location-input" placeholder="Ex. Badulla Town" required autocomplete="off">
                        <i class="ph ph-crosshair" id="locate-btn" title="Live Location"></i>
                    </div>
                    <div id="location-suggestions" class="search-suggestions-box"></div>
                </div>

                <div class="form-group">
                    <label>Preferred Date</label>
                    <div class="input-wrapper">
                        <i class="ph ph-calendar ph-main"></i>
                        <input type="date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>" style="padding-left: 48px !important;">
                    </div>
                </div>

                <div class="form-group">
                    <label>Select Inspection Package</label>
                    <div class="input-wrapper">
                        <i class="ph ph-package ph-main"></i>
                        <select name="package_info" required style="padding-left: 48px !important;">
                            <?php if (!empty($expert_packages)): ?>
                                <?php foreach ($expert_packages as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['name'] . '|' . $p['price']); ?>">
                                        <?php echo htmlspecialchars($p['name']); ?> - LKR <?php echo number_format($p['price'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="Standard|5000">Standard Inspection - LKR 5,000.00</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Service Type</label>
                    <div class="mobile-stack" style="display: flex; gap: 24px; align-items: center; padding: 8px 5px;">
                        <label style="font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; margin-bottom: 0; color: #475569;">
                            <input type="radio" name="service_type" value="Physical" checked style="width: auto; padding: 0 !important; margin: 0;"> Physical
                        </label>
                        <label style="font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; margin-bottom: 0; color: #475569;">
                            <input type="radio" name="service_type" value="Virtual" style="width: auto; padding: 0 !important; margin: 0;"> Virtual
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Send Request</button>
            </form>
        </div>

        <!-- Reviews Section -->
        <div style="max-width: 500px; margin: 20px auto 40px auto;">
            <h3 style="margin-bottom: 15px;">What customers say about <?php echo htmlspecialchars($expert_name); ?></h3>
            <?php
            $rev_sql = "SELECT r.*, b.name FROM review r JOIN buyer b ON r.buyer_id = b.buyer_id WHERE r.expert_id = $expert_id ORDER BY r.review_date DESC LIMIT 5";
            $reviews = $conn->query($rev_sql);
            
            if ($reviews && $reviews->num_rows > 0) {
                while($rev = $reviews->fetch_assoc()) {
                    ?>
                    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <strong><?php echo htmlspecialchars($rev['name']); ?></strong>
                            <span style="color: #f59e0b;">★ <?php echo $rev['rating']; ?>.0</span>
                        </div>
                        <p style="font-size: 0.9rem; color: #475569; margin: 0; line-height: 1.4;"><?php echo htmlspecialchars($rev['comment']); ?></p>
                        
                        <?php if (!empty($rev['expert_reply'])): ?>
                            <div style="background: #f8fafc; border-left: 3px solid #10b981; padding: 10px; margin-top: 10px; border-radius: 4px; font-size: 0.85rem;">
                                <div style="font-weight: 600; color: #166534; margin-bottom: 2px;">Expert's Response:</div>
                                <p style="color: #334155; margin: 0; font-style: italic;"><?php echo htmlspecialchars($rev['expert_reply']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            } else {
                echo "<p style='color: #94a3b8; font-size: 0.9rem; text-align: center;'>No reviews yet for this expert.</p>";
            }
            ?>
        </div>
    </div>

    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        window.onload = function() {

            // --- Location Autocomplete Logic ---
            const locInput = document.getElementById('location-input');
            const locSuggestions = document.getElementById('location-suggestions');
            const latInput = document.getElementById('search-lat');
            const lngInput = document.getElementById('search-lng');
            let locDebounce;

            const badullaTowns = ["Badulla", "Bandarawela", "Haputale", "Ella", "Welimada", "Mahiyanganaya", "Diyatalawa", "Hali Ela", "Passara", "Akkarasiyaya", "Ambagasdowa", "Boralanda", "Demodara", "Ettampitiya", "Gurutalawa", "Haldummulla", "Koslanda", "Lunugala", "Madulsima", "Meegahakiula", "Namunukula", "Wewatta"];
            const townCoords = {"Badulla":{"lat":6.9934,"lng":81.055},"Bandarawela":{"lat":6.831,"lng":80.9984},"Haputale":{"lat":6.7686,"lng":80.9576},"Ella":{"lat":6.8761,"lng":81.0475},"Welimada":{"lat":6.9031,"lng":80.9142},"Mahiyanganaya":{"lat":7.3204,"lng":81.0028},"Diyatalawa":{"lat":6.8189,"lng":80.9592},"Hali Ela":{"lat":6.9531,"lng":81.0315},"Passara":{"lat":7.0006,"lng":81.1444}};

            locInput.addEventListener('input', function() {
                clearTimeout(locDebounce);
                const query = this.value.toLowerCase();
                if (query.length < 2) {
                    locSuggestions.style.display = 'none';
                    return;
                }

                const localMatches = badullaTowns.filter(town => town.toLowerCase().includes(query));
                renderLocSuggestions(localMatches, []);

                locDebounce = setTimeout(() => {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=lk&limit=5&addressdetails=1`)
                        .then(res => res.json())
                        .then(data => {
                            const apiMatches = data.filter(p => JSON.stringify(p).toLowerCase().includes('badulla') || JSON.stringify(p).toLowerCase().includes('uva'));
                            renderLocSuggestions(localMatches, apiMatches);
                        });
                }, 500);
            });

            function renderLocSuggestions(localList, apiList) {
                locSuggestions.innerHTML = '';
                if (localList.length === 0 && apiList.length === 0) {
                    locSuggestions.style.display = 'none';
                    return;
                }

                localList.forEach(town => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.style.fontWeight = '600';
                    div.style.color = 'var(--primary-dark)';
                    div.textContent = town + " (Badulla Dist.)";
                    div.onclick = () => {
                        locInput.value = town;
                        locSuggestions.style.display = 'none';
                        if (townCoords[town]) {
                            latInput.value = townCoords[town].lat;
                            lngInput.value = townCoords[town].lng;
                        }
                    };
                    locSuggestions.appendChild(div);
                });

                apiList.forEach(place => {
                    const name = place.address.village || place.address.town || place.address.city || place.name;
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.textContent = name + (place.address.state ? `, ${place.address.state}` : '');
                    div.onclick = () => {
                        locInput.value = name;
                        latInput.value = place.lat;
                        lngInput.value = place.lon;
                        locSuggestions.style.display = 'none';
                    };
                    locSuggestions.appendChild(div);
                });
                locSuggestions.style.display = 'block';
            }

            // --- Live Location Logic ---
            const locateBtn = document.getElementById('locate-btn');
            locateBtn.addEventListener('click', function() {
                if (!navigator.geolocation) return;
                locateBtn.classList.add('ph-spinner', 'ph-spin', 'locating-active');
                locInput.placeholder = "Locating...";
                locInput.classList.add('locating-active');

                navigator.geolocation.getCurrentPosition((pos) => {
                    latInput.value = pos.coords.latitude;
                    lngInput.value = pos.coords.longitude;
                    locInput.value = "Current Location";
                    locInput.classList.remove('locating-active');
                    locateBtn.classList.remove('ph-spinner', 'ph-spin', 'locating-active');
                }, () => {
                    locInput.classList.remove('locating-active');
                    locateBtn.classList.remove('ph-spinner', 'ph-spin', 'locating-active');
                    locInput.placeholder = "Ex. Badulla Town";
                });
            });

            // Close suggestions on click outside
            document.addEventListener('click', function(e) {
                if (!vehicleInput.contains(e.target)) vehicleSuggestions.style.display = 'none';
                if (!locInput.contains(e.target)) locSuggestions.style.display = 'none';
            });
        };
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>
