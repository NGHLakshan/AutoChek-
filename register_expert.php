<?php
require_once 'db.php';
$message = "";

// 1. AJAX SEARCH API (Same as profile_settings.php)
if (isset($_GET['action']) && $_GET['action'] == 'search_vehicle') {
    header('Content-Type: application/json');
    $q = isset($_GET['query']) ? $conn->real_escape_string($_GET['query']) : '';
    $results = [];

    if (strlen($q) >= 2) {
        // Categories
        $res = $conn->query("SELECT category_id, name FROM vehicle_categories WHERE name LIKE '%$q%' LIMIT 5");
        while ($row = $res->fetch_assoc()) $results[] = ['type' => 'category', 'id' => $row['category_id'], 'name' => $row['name'] . ' (Category)'];

        // Brands
        $res = $conn->query("SELECT b.brand_id, b.name, c.name as cat_name FROM vehicle_brands b JOIN vehicle_categories c ON b.category_id = c.category_id WHERE b.name LIKE '%$q%' LIMIT 5");
        while ($row = $res->fetch_assoc()) $results[] = ['type' => 'brand', 'id' => $row['brand_id'], 'name' => $row['name'] . ' (' . $row['cat_name'] . ')'];

        // Models
        $res = $conn->query("SELECT m.model_id, m.name, b.name as brand_name FROM vehicle_models m JOIN vehicle_brands b ON m.brand_id = b.brand_id WHERE m.name LIKE '%$q%' LIMIT 5");
        while ($row = $res->fetch_assoc()) $results[] = ['type' => 'model', 'id' => $row['model_id'], 'name' => $row['name'] . ' (Model - ' . $row['brand_name'] . ')'];
    }
    echo json_encode($results);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $district = $_POST['district'];
    $qualification = $_POST['qualification'];
    $experience = intval($_POST['experience']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Location
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

    // File Upload
    $cert_filename = null;
    if (isset($_FILES['certification']) && $_FILES['certification']['error'] == 0) {
        $target_dir = "uploads/certs/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_ext = strtolower(pathinfo($_FILES["certification"]["name"], PATHINFO_EXTENSION));
        // Temp filename, we rename after ID generation or just use timestamp
        $new_filename = "cert_new_" . time() . "_" . uniqid() . "." . $file_ext; 
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["certification"]["tmp_name"], $target_file)) {
            $cert_filename = $new_filename;
        }
    }

    // Insert Expert
    $stmt = $conn->prepare("INSERT INTO expert (name, email, phone, district, qualification, experience, password, latitude, longitude, certification_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiddds", $name, $email, $phone, $district, $qualification, $experience, $password, $latitude, $longitude, $cert_filename);
    
    if ($stmt->execute()) {
        $new_expert_id = $conn->insert_id;

        // Handle HIERARCHICAL Specializations
        if (isset($_POST['spec_type']) && isset($_POST['spec_id'])) {
             $types = $_POST['spec_type'];
             $ids = $_POST['spec_id'];

             for ($i = 0; $i < count($types); $i++) {
                 $type = $types[$i];
                 $id = intval($ids[$i]);

                 if ($type == 'category') {
                     $stmt_s = $conn->prepare("INSERT INTO expert_vehicle_categories (expert_id, category_id) VALUES (?, ?)");
                 } elseif ($type == 'brand') {
                     $stmt_s = $conn->prepare("INSERT INTO expert_vehicle_brands (expert_id, brand_id) VALUES (?, ?)");
                 } elseif ($type == 'model') {
                     $stmt_s = $conn->prepare("INSERT INTO expert_vehicle_models (expert_id, model_id) VALUES (?, ?)");
                 }
                 
                 if (isset($stmt_s)) {
                    $stmt_s->bind_param("ii", $new_expert_id, $id);
                    $stmt_s->execute();
                 }
             }
         }

        $message = "<div class='alert-success'><strong>✅ Registration Successful!</strong><br><br>Your expert account has been created. However, you cannot log in yet.<br><br><strong>Next Steps:</strong><ol style='margin: 10px 0; padding-left: 20px;'><li>Wait for admin approval (usually within 24 hours)</li><li>You'll receive confirmation once approved</li><li>Then you can <a href='login.php' style='color: #166534; text-decoration: underline;'>log in here</a></li></ol><p style='margin-top: 10px;'><strong>Note:</strong> All expert accounts require manual verification for quality assurance.</p></div>";
    } else {
        $message = "<div class='alert-error'>Error: " . $stmt->error . "</div>";
        if ($conn->errno == 1062) {
             $message = "<div class='alert-error'>That email is already registered.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join as an Expert | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .form-container { max-width: 700px; margin: 40px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], textarea, select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        
        #map { height: 300px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; margin-top: 10px; }

        /* Tag Input Styles */
        .tag-container { display: flex; flex-wrap: wrap; gap: 8px; border: 1px solid #cbd5e1; padding: 5px; border-radius: 6px; min-height: 42px; }
        .tag { background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 15px; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; }
        .tag span.remove { cursor: pointer; font-weight: bold; color: #60a5fa; }
        .tag span.remove:hover { color: #1e40af; }
        .tag-input { border: none; outline: none; flex: 1; min-width: 150px; padding: 5px; }
        .suggestions-list { border: 1px solid #cbd5e1; border-radius: 0 0 6px 6px; position: absolute; background: white; width: 100%; max-height: 200px; overflow-y: auto; z-index: 1000; display: none; margin-top: -1px; }
        .suggestion-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
        .suggestion-item:hover { background: #f8fafc; }
        .suggestion-item:last-child { border-bottom: none; }
        
        /* Location Autocomplete Suggestions */
        #location-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #cbd5e1;
            border-top: none;
            border-radius: 0 0 6px 6px;
            z-index: 1000;
            display: none;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            margin-top: -1px;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2 style="margin-top:0;">Expert Registration</h2>
            <p>Join Badulla's #1 network of vehicle inspectors.</p>
            
            <?php echo $message; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Account Info -->
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Ex. Kamal Perera">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required placeholder="07x xxxxxxx">
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;">

                <!-- VEHICLE SPECIALIZATIONS (New) -->
                <div class="form-group">
                    <label>Vehicle Specializations</label>
                    <small style="color: #64748b; margin-bottom: 5px; display: block;">Search and add vehicles you inspect (Category, Brand, or Model).</small>
                    <div style="position: relative;">
                            <div class="tag-container" id="tagContainer">
                                <input type="text" class="tag-input" id="specSearch" placeholder="Type to search (e.g. 'Toyota', 'Bike')..." autocomplete="off">
                            </div>
                            <div class="suggestions-list" id="suggestions"></div>
                    </div>
                    <!-- Hidden inputs generated by JS -->
                    <div id="hiddenInputs"></div>
                </div>

                <!-- WORK LOCATION (Combined Map + Text Input) -->
                <div class="form-group">
                    <label>Work Location</label>
                    <small style="color: #64748b; margin-bottom: 10px; display: block;">Enter your service area or drag the pin on the map to set your exact location.</small>
                    
                    <!-- Text Input for Location/District with Autocomplete -->
                    <div style="position: relative;">
                        <input type="text" name="district" id="district-input" required placeholder="e.g. Badulla" style="margin-bottom: 10px;" autocomplete="off">
                        <div id="location-suggestions"></div>
                    </div>
                    
                    <!-- Map -->
                    <div id="map"></div>
                    <input type="hidden" name="latitude" id="lat">
                    <input type="hidden" name="longitude" id="lng">
                </div>

                <div class="form-group">
                    <label>Qualifications</label>
                    <textarea name="qualification" rows="2" placeholder="Ex. NVQ Level 4 in Auto Mechanics, 5 years at Toyota" required></textarea>
                </div>
                
                <!-- CERTIFICATION (New) -->
                <div class="form-group">
                    <label>Certification / Proof (PDF or Image)</label>
                    <input type="file" name="certification" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <div class="form-group">
                    <label>Experience (Years)</label>
                    <input type="number" name="experience" min="0" required placeholder="Ex. 5">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Expert Account</button>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // --- Specialization Tag Logic ---
        const tagContainer = document.getElementById('tagContainer');
        const searchInput = document.getElementById('specSearch');
        const suggestionsBox = document.getElementById('suggestions');
        const hiddenInputs = document.getElementById('hiddenInputs');
        let addedSpecs = [];

        function addTag(type, id, name) {
            if (addedSpecs.some(s => s.type === type && s.id === id)) return;
            addedSpecs.push({type, id, name});

            const tag = document.createElement('div');
            tag.className = 'tag';
            tag.innerHTML = `${name} <span class="remove" onclick="removeTag('${type}', ${id}, this)">&times;</span>`;
            tagContainer.insertBefore(tag, searchInput);

            renderHiddenInputs();
            searchInput.value = '';
            suggestionsBox.style.display = 'none';
        }

        window.removeTag = function(type, id, el) {
            addedSpecs = addedSpecs.filter(s => !(s.type === type && s.id === id));
            el.parentElement.remove();
            renderHiddenInputs();
        }

        function renderHiddenInputs() {
            hiddenInputs.innerHTML = '';
            addedSpecs.forEach(spec => {
                hiddenInputs.innerHTML += `<input type="hidden" name="spec_type[]" value="${spec.type}">`;
                hiddenInputs.innerHTML += `<input type="hidden" name="spec_id[]" value="${spec.id}">`;
            });
        }

        searchInput.addEventListener('input', function() {
            const query = this.value;
            if (query.length < 2) {
                suggestionsBox.style.display = 'none';
                return;
            }
            fetch('?action=search_vehicle&query=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            div.textContent = item.name;
                            div.onclick = () => addTag(item.type, item.id, item.name);
                            suggestionsBox.appendChild(div);
                        });
                        suggestionsBox.style.display = 'block';
                    } else {
                        suggestionsBox.style.display = 'none';
                    }
                });
        });

        document.addEventListener('click', function(e) {
            if (!tagContainer.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });

        // --- Map Logic ---
        var defaultLat = 6.9271; 
        var defaultLng = 79.8612;
        var map = L.map('map').setView([defaultLat, defaultLng], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        var markers = L.layerGroup().addTo(map);

        function addMarker(lat, lng, popupText) {
            markers.clearLayers();
            var marker = L.marker([lat, lng], {draggable: true});
            marker.bindPopup(popupText).openPopup();
            markers.addLayer(marker);
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
            return marker;
        }

        map.on('click', function(e) {
            var marker = addMarker(e.latlng.lat, e.latlng.lng, "Selected Location");
            
            // When marker is dragged, update coordinates and reverse geocode
            marker.on('dragend', function(event) {
                var position = event.target.getLatLng();
                document.getElementById('lat').value = position.lat;
                document.getElementById('lng').value = position.lng;
                reverseGeocode(position.lat, position.lng);
            });
            
            // Reverse Geocode to update text input
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });
        
        // Reverse Geocode Function
        function reverseGeocode(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => {
                    let city = data.address.city || data.address.town || data.address.village || "";
                    if (city) {
                        let input = document.getElementById('district-input');
                        input.value = city;
                    }
                });
        }
        
        // Forward Geocode: When user types location, update map
        let geocodeTimeout;
        document.getElementById('district-input').addEventListener('input', function() {
            clearTimeout(geocodeTimeout);
            const location = this.value.trim();
            
            if (location.length < 3) return;
            
            geocodeTimeout = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(location)}&limit=1`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lng = parseFloat(data[0].lon);
                            map.setView([lat, lng], 12);
                            var marker = addMarker(lat, lng, location);
                            
                            // Enable dragging on geocoded marker
                            marker.on('dragend', function(event) {
                                var position = event.target.getLatLng();
                                document.getElementById('lat').value = position.lat;
                                document.getElementById('lng').value = position.lng;
                                reverseGeocode(position.lat, position.lng);
                            });
                        }
                    });
            }, 800); // Debounce for 800ms
        });
        
        // Try to get user location
        if (navigator.geolocation) {
             navigator.geolocation.getCurrentPosition(function(pos) {
                 var lat = pos.coords.latitude;
                 var lng = pos.coords.longitude;
                 map.setView([lat, lng], 12);
                 var marker = addMarker(lat, lng, "Your Location");
                 
                 // Enable dragging on initial marker
                 marker.on('dragend', function(event) {
                     var position = event.target.getLatLng();
                     document.getElementById('lat').value = position.lat;
                     document.getElementById('lng').value = position.lng;
                     reverseGeocode(position.lat, position.lng);
                 });
             });
        }
        
        // --- Location Autocomplete Logic ---
        const locInput = document.getElementById('district-input');
        const locSuggestions = document.getElementById('location-suggestions');
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
            
            const query = this.value.toLowerCase();
            locSuggestions.innerHTML = '';
            
            if(query.length < 2) {
                locSuggestions.style.display = 'none';
                return;
            }
            
            // 1. Local Search (Instant)
            const localMatches = badullaTowns.filter(town => town.toLowerCase().includes(query));
            
            renderLocationSuggestions(localMatches, []);
            
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
                            renderLocationSuggestions(localMatches, uniqueApiMatches);
                        }
                    });
            }, 500); // 500ms delay to avoid spamming API
        });
        
        function renderLocationSuggestions(localList, apiList) {
            if (localList.length === 0 && apiList.length === 0) {
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
                div.onclick = () => selectLocationAndGeocode(town);
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
                div.onclick = () => selectLocationWithCoords(display, place.lat, place.lon);
                div.onmouseover = () => div.style.background = '#f8fafc';
                div.onmouseout = () => div.style.background = 'white';
                locSuggestions.appendChild(div);
            });
            
            locSuggestions.style.display = 'block';
        }
        
        function selectLocationAndGeocode(name) {
            locInput.value = name;
            locSuggestions.style.display = 'none';
            
            // Geocode the selected location and update map
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(name + ", Badulla, Sri Lanka")}&limit=1`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        map.setView([lat, lng], 12);
                        var marker = addMarker(lat, lng, name);
                        
                        // Enable dragging on marker
                        marker.on('dragend', function(event) {
                            var position = event.target.getLatLng();
                            document.getElementById('lat').value = position.lat;
                            document.getElementById('lng').value = position.lng;
                            reverseGeocode(position.lat, position.lng);
                        });
                    }
                });
        }
        
        function selectLocationWithCoords(name, lat, lng) {
            locInput.value = name;
            locSuggestions.style.display = 'none';
            
            map.setView([lat, lng], 12);
            var marker = addMarker(lat, lng, name);
            
            // Enable dragging on marker
            marker.on('dragend', function(event) {
                var position = event.target.getLatLng();
                document.getElementById('lat').value = position.lat;
                document.getElementById('lng').value = position.lng;
                reverseGeocode(position.lat, position.lng);
            });
        }
        
        // Close suggestions on click outside
        document.addEventListener('click', function(e) {
            if (!locInput.contains(e.target) && !locSuggestions.contains(e.target)) {
                locSuggestions.style.display = 'none';
            }
        });
    </script>

</body>
</html>
