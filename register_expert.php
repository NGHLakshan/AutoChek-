<?php
require_once 'db.php';
$message = "";



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

    // Insert Expert (Pending Verification)
    $stmt = $conn->prepare("INSERT INTO expert (name, email, phone, district, qualification, experience, password, latitude, longitude, certification_file, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("sssssisdds", $name, $email, $phone, $district, $qualification, $experience, $password, $latitude, $longitude, $cert_filename);
    
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

        $message = "<div class='alert-success'><strong>✅ Account Created!</strong><br><br>Your expert account is pending admin approval. You will be able to log in once an administrator verifies your details.</div>";
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
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .form-container { max-width: 700px; margin: 40px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="password"], input[type="url"], input[type="number"], textarea, select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
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

        @media (max-width: 768px) {
            .form-container {
                margin: 20px 15px; /* Smaller margins on mobile */
                padding: 20px;
            }
            #map {
                height: 250px; /* Slightly smaller map on mobile */
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2 style="margin-top:0;">Expert Registration</h2>
            <p>Join Badulla's one network of vehicle inspectors.</p>
            
            <?php echo $message; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Account Info -->
                <!-- Account Info -->
                <div class="form-group">
                    <label>Full Name</label>
                    <div style="position: relative;">
                        <i class="ph ph-user" style="position: absolute; left: 10px; top: 12px; color: #94a3b8;"></i>
                        <input type="text" name="name" required placeholder="Ex. Kamal Perera" style="padding-left: 35px;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <div style="position: relative;">
                        <i class="ph ph-envelope" style="position: absolute; left: 10px; top: 12px; color: #94a3b8;"></i>
                        <input type="email" name="email" required style="padding-left: 35px;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <i class="ph ph-lock-key" style="position: absolute; left: 10px; top: 12px; color: #94a3b8;"></i>
                        <input type="password" name="password" required style="padding-left: 35px;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <div style="position: relative;">
                        <i class="ph ph-phone" style="position: absolute; left: 10px; top: 12px; color: #94a3b8;"></i>
                        <input type="text" name="phone" required placeholder="07x xxxxxxx" style="padding-left: 35px;">
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;">

                <!-- VEHICLE SPECIALIZATIONS (New) -->
                <div class="form-group">
                    <label>Vehicle Specializations</label>
                    <small style="color: #64748b; margin-bottom: 5px; display: block;">Search and add vehicles you inspect (Category, Brand, or Model).</small>
                    <div style="position: relative;">
                            <div class="tag-container" id="tagContainer">
                                <i class="ph ph-magnifying-glass" style="color: #94a3b8; margin: 0 5px;"></i>
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
                    <div style="position: relative; z-index: 1001;">
                        <i class="ph ph-map-pin" style="position: absolute; left: 10px; top: 12px; color: #94a3b8;"></i>
                        <input type="text" name="district" id="district-input" required placeholder="e.g. Badulla" style="margin-bottom: 10px; padding-left: 35px;" autocomplete="off">
                        <div id="location-suggestions"></div>
                    </div>
                    <div id="location-error" class="alert-error" style="display:none; padding: 10px; font-size: 0.9rem; margin-top: 5px;"></div>
                    
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
            fetch('ajax_search_vehicle.php?query=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600; color: #1e293b;">${item.name}</div>
                                    <div style="color: #64748b; font-size: 0.8rem;">${item.subtext || ''}</div>
                                </div>
                                <span style="background: ${item.type === 'external' ? '#fff7ed' : '#eff6ff'}; 
                                             color: ${item.type === 'external' ? '#c2410c' : '#1d4ed8'}; 
                                             padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; border: 1px solid ${item.type === 'external' ? '#ffedd5' : '#dbeafe'};">
                                    ${item.type_label}
                                </span>
                            </div>
                        `;
                        div.onclick = () => {
                            if (item.external) {
                                div.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving...';
                                const formData = new FormData();
                                formData.append('brand', item.brand);
                                formData.append('model', item.name);
                                fetch('ingest_vehicle.php', { method: 'POST', body: formData })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            addTag('model', data.id, data.display);
                                        }
                                    });
                            } else {
                                addTag(item.type, item.id, item.name);
                            }
                        };
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

        // --- Map & Location Logic ---
        var defaultLat = 6.9847; // Badulla default (approx center)
        var defaultLng = 81.0564;
        var map = L.map('map').setView([defaultLat, defaultLng], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        var markers = L.layerGroup().addTo(map);

        const locInput = document.getElementById('district-input');
        const locSuggestions = document.getElementById('location-suggestions');
        const locError = document.getElementById('location-error');
        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');

        // Error Feedback
        function showError(msg) {
            locError.textContent = msg;
            locError.style.display = 'block';
            locInput.style.borderColor = '#991b1b';
        }
        
        function clearError() {
            locError.style.display = 'none';
            locInput.style.borderColor = '#cbd5e1';
        }

        function addMarker(lat, lng, popupText) {
            markers.clearLayers();
            var marker = L.marker([lat, lng], {draggable: true});
            marker.bindPopup(popupText).openPopup();
            markers.addLayer(marker);
            latInput.value = lat;
            lngInput.value = lng;
            return marker;
        }

        // Core Validation & Sync Function
        function validateAndSetLocation(lat, lng, displayName = null) {
            clearError();
            console.log(`Checking location: ${lat}, ${lng}`);
            
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`)
                .then(res => res.json())
                .then(data => {
                    console.log("OSM Data:", data);
                    const addr = data.address;
                    if (!addr) {
                        showError("Could not verify location. Please try again.");
                        return;
                    }

                    // Robust Validation: Check if 'Badulla' appears anywhere in the address details
                    const addressText = Object.values(addr).join(' ').toLowerCase();
                    const isBadulla = addressText.includes("badulla");

                    if (isBadulla) {
                        // Success
                        let niceName = displayName;
                        if (!niceName) {
                            niceName = addr.village || addr.town || addr.city || addr.suburb || data.display_name.split(',')[0];
                        }
                        locInput.value = niceName;
                        
                        // 2. Add Marker
                        const marker = addMarker(lat, lng, niceName);
                        
                        // 3. Add Drag Listener to NEW marker
                        marker.on('dragend', function(e) {
                            const pos = e.target.getLatLng();
                            validateAndSetLocation(pos.lat, pos.lng);
                        });
                        
                    } else {
                        // Failed Validation
                         console.warn("Validation failed. Address text:", addressText);
                        showError("Location must be in Badulla District. Detected: " + (addr.state_district || addr.city || "Unknown Area"));
                        
                        // Clear invalid coordinates to prevent submission
                        latInput.value = "";
                        lngInput.value = "";
                    }
                })
                .catch(err => {
                    console.error(err);
                    showError("Network error validating location.");
                });
        }

        // Map Click
        map.on('click', function(e) {
            validateAndSetLocation(e.latlng.lat, e.latlng.lng);
        });

        
        // --- Autocomplete & Search ---
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
        
        let locDebounce;
        
        locInput.addEventListener('input', function() {
            // Clear coords when user types to prevent mismatch
            latInput.value = '';
            lngInput.value = '';
            
            clearTimeout(locDebounce);
            clearError(); 
            const query = this.value.toLowerCase();
            locSuggestions.innerHTML = '';
            
            if(query.length < 2) {
                locSuggestions.style.display = 'none';
                return;
            }
            
            // 1. Local Search
            const localMatches = badullaTowns.filter(town => town.toLowerCase().includes(query));
            renderLocationSuggestions(localMatches, []);
            
            // 2. API Search
            locDebounce = setTimeout(() => {
                // We restrict search to LK
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + " Badulla")}&countrycodes=lk&limit=5&addressdetails=1`)
                    .then(res => res.json())
                    .then(data => {
                        const uniqueApiMatches = data.filter(apiItem => {
                            let name = apiItem.address.village || apiItem.address.town || apiItem.display_name.split(',')[0];
                            return !localMatches.some(local => local.toLowerCase() === name.toLowerCase());
                        });
                        
                        if(uniqueApiMatches.length > 0) {
                            renderLocationSuggestions(localMatches, uniqueApiMatches);
                        }
                    });
            }, 500);
        });

        // Auto-select on Enter
        locInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.length > 2) {
                    selectLocalLocation(this.value);
                }
            }
        });

        // Auto-select on Blur (if not already selected)
        locInput.addEventListener('blur', function() {
            setTimeout(() => {
                // If coordinates are empty (meaning user typed but didn't select), try to resolve
                if ((!latInput.value || !lngInput.value) && this.value.length > 2) {
                    selectLocalLocation(this.value);
                }
            }, 300); // Small delay to allow click events on suggestions to fire first
        });
        
        function renderLocationSuggestions(localList, apiList) {
             if (localList.length === 0 && apiList.length === 0) {
                if(locSuggestions.innerHTML === '') locSuggestions.style.display = 'none';
                return;
            }
            locSuggestions.innerHTML = '';
            
            // Use a helper to create Item
            function createItem(name, type, lat, lng) {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.style.padding = '10px 15px';
                div.style.borderBottom = '1px solid #f1f5f9';
                div.style.cursor = 'pointer';
                if(type === 'local') {
                    div.style.fontWeight = '600'; 
                    div.style.color = '#166534';
                    div.textContent = name + " (Badulla Dist.)"; 
                } else {
                    div.textContent = name;
                }
                
                div.onclick = () => {
                    if (lat && lng) {
                        selectLocation(name, lat, lng);
                    } else {
                        // Need to fetch coords for local item first
                        selectLocalLocation(name);
                    }
                };
                div.onmouseover = () => div.style.background = '#f0fdf4';
                div.onmouseout = () => div.style.background = 'white';
                return div;
            }

            localList.forEach(town => locSuggestions.appendChild(createItem(town, 'local', null, null)));
            
            apiList.forEach(place => {
                 let dp = place.address.village || place.address.town || place.address.city || place.name;
                 if(place.address.state) dp += `, ${place.address.state}`;
                 locSuggestions.appendChild(createItem(dp, 'api', parseFloat(place.lat), parseFloat(place.lon)));
            });

            locSuggestions.style.display = 'block';
        }
        
        function selectLocation(name, lat, lng) {
            locSuggestions.style.display = 'none';
             map.setView([lat, lng], 13);
             validateAndSetLocation(lat, lng, name);
        }

        function selectLocalLocation(name) {
             locSuggestions.style.display = 'none';
             fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(name + ", Badulla, Sri Lanka")}&limit=1`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        // Forward to main handler
                        selectLocation(name, lat, lng);
                    } else {
                        showError("Could not find coordinates. Please drag the pin on map.");
                    }
                });
        }
        
        // Close suggestions on click outside
        document.addEventListener('click', function(e) {
            if (!locInput.contains(e.target) && !locSuggestions.contains(e.target)) {
                locSuggestions.style.display = 'none';
            }
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>
