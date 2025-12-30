<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = "";

// 1. Handle Form Submission
// 1. Handle Form Submission & AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['action'])) {
    
    // AJAX SEARCH API
    if (isset($_GET['action']) && $_GET['action'] == 'search_vehicle') {
        header('Content-Type: application/json');
        $q = isset($_GET['query']) ? $conn->real_escape_string($_GET['query']) : '';
        $results = [];

        if (strlen($q) >= 2) {
            // 1. Categories
            $res = $conn->query("SELECT category_id, name FROM vehicle_categories WHERE name LIKE '%$q%' LIMIT 5");
            while ($row = $res->fetch_assoc()) $results[] = ['type' => 'category', 'id' => $row['category_id'], 'name' => $row['name'] . ' (Category)'];

            // 2. Brands
            $res = $conn->query("SELECT b.brand_id, b.name, c.name as cat_name FROM vehicle_brands b JOIN vehicle_categories c ON b.category_id = c.category_id WHERE b.name LIKE '%$q%' LIMIT 5");
            while ($row = $res->fetch_assoc()) $results[] = ['type' => 'brand', 'id' => $row['brand_id'], 'name' => $row['name'] . ' (' . $row['cat_name'] . ')'];

            // 3. Models
            $res = $conn->query("SELECT m.model_id, m.name, b.name as brand_name FROM vehicle_models m JOIN vehicle_brands b ON m.brand_id = b.brand_id WHERE m.name LIKE '%$q%' LIMIT 5");
            while ($row = $res->fetch_assoc()) $results[] = ['type' => 'model', 'id' => $row['model_id'], 'name' => $row['name'] . ' (Model - ' . $row['brand_name'] . ')'];
        }
        echo json_encode($results);
        exit;
    }

    if ($role == 'buyer') {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $bio = $_POST['bio'] ?? '';
        $location = $_POST['location'];
        
        // Photo Upload for Buyer
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $target_dir = "uploads/profiles/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
            $filename = "buyer_" . $user_id . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_dir . $filename)) {
                $conn->query("UPDATE buyer SET profile_photo = '$filename' WHERE buyer_id = $user_id");
            }
        }

        $stmt = $conn->prepare("UPDATE buyer SET name = ?, phone = ?, bio = ?, location = ? WHERE buyer_id = ?");
        $stmt->bind_param("ssssi", $name, $phone, $bio, $location, $user_id);
        
        if ($stmt->execute()) {
             // Account & Notif Settings
             $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
             $sms_notif = isset($_POST['sms_notifications']) ? 1 : 0;
             $conn->query("UPDATE buyer SET email_notifications = $email_notif, sms_notifications = $sms_notif WHERE buyer_id = $user_id");

             // Password Change
             $pass_message = "";
             if (!empty($_POST['new_password'])) {
                 $current_pass = $_POST['current_password'];
                 $new_pass = $_POST['new_password'];
                 $confirm_pass = $_POST['confirm_password'];
                 $row = $conn->query("SELECT password FROM buyer WHERE buyer_id = $user_id")->fetch_assoc();
                 if (password_verify($current_pass, $row['password'])) {
                     if ($new_pass === $confirm_pass && strlen($new_pass) >= 6) {
                         $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                         $conn->query("UPDATE buyer SET password = '$hashed' WHERE buyer_id = $user_id");
                         $pass_message = " & password changed";
                     } else {
                         $message = "<div class='alert-error'>Password mismatch or too short.</div>";
                     }
                 } else {
                     $message = "<div class='alert-error'>Incorrect current password.</div>";
                 }
             }

             // Delete Account
             if (isset($_POST['action']) && $_POST['action'] == 'delete_account') {
                 $conn->query("DELETE FROM buyer WHERE buyer_id = $user_id");
                 session_destroy();
                 header("Location: index.php?msg=account_deleted");
                 exit;
             }

             $message = "<div class='alert-success'>Profile updated" . $pass_message . "!</div>";
             $_SESSION['name'] = $name;
        } else {
             $message = "<div class='alert-error'>Error: " . $conn->error . "</div>";
        }
    } elseif ($role == 'expert') {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $bio = $_POST['bio'] ?? '';
        $district = $_POST['district'];
        $qualification = $_POST['qualification'];
        $experience = intval($_POST['experience']);
        $linkedin = $_POST['linkedin_url'] ?? '';
        $website = $_POST['website_url'] ?? '';
        
        // Location & Availability
        $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        // File Upload Processing (Photo & Cert)
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $target_dir = "uploads/profiles/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
            $filename = "expert_" . $user_id . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_dir . $filename)) {
                $conn->query("UPDATE expert SET profile_photo = '$filename' WHERE expert_id = $user_id");
            }
        }

        if (isset($_FILES['certification']) && $_FILES['certification']['error'] == 0) {
            $target_dir = "uploads/certs/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            
            $file_ext = strtolower(pathinfo($_FILES["certification"]["name"], PATHINFO_EXTENSION));
            $new_filename = "cert_" . $user_id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["certification"]["tmp_name"], $target_file)) {
                $stmt = $conn->prepare("UPDATE expert SET certification_file = ? WHERE expert_id = ?");
                $stmt->bind_param("si", $new_filename, $user_id);
                $stmt->execute();
            }
        }

        // Handle Packages (JSON)
        $packages_json = null;
        if (isset($_POST['package_names']) && isset($_POST['package_prices'])) {
            $p_names = $_POST['package_names'];
            $p_prices = $_POST['package_prices'];
            $packages = [];
            for ($i = 0; $i < count($p_names); $i++) {
                if (!empty(trim($p_names[$i]))) {
                    $packages[] = [
                        'name' => trim($p_names[$i]),
                        'price' => floatval($p_prices[$i])
                    ];
                }
            }
            $packages_json = json_encode($packages);
        }
        
        $stmt = $conn->prepare("UPDATE expert SET name = ?, phone = ?, bio = ?, district = ?, qualification = ?, experience = ?, latitude = ?, longitude = ?, packages = ?, linkedin_url = ?, website_url = ? WHERE expert_id = ?");
        $stmt->bind_param("ssssiddisssi", $name, $phone, $bio, $district, $qualification, $experience, $latitude, $longitude, $packages_json, $linkedin, $website, $user_id);
        
        if ($stmt->execute()) {
             // Handle HIERARCHICAL Specializations
             // We receive spec_type[] (category/brand/model) and spec_id[]
             // First clear all existing
             $conn->query("DELETE FROM expert_vehicle_categories WHERE expert_id = $user_id");
             $conn->query("DELETE FROM expert_vehicle_brands WHERE expert_id = $user_id");
             $conn->query("DELETE FROM expert_vehicle_models WHERE expert_id = $user_id");

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
                        $stmt_s->bind_param("ii", $user_id, $id);
                        $stmt_s->execute();
                     }
                 }
             }

             $message = "<div class='alert-success'>Profile Updated!</div>";
             $_SESSION['name'] = $name;
        } else {
             $message = "<div class='alert-error'>Error: " . $conn->error . "</div>";
        }
    }
}

// 2. Fetch Current Data
$user = [];
if ($role == 'buyer') {
    $res = $conn->query("SELECT * FROM buyer WHERE buyer_id = $user_id");
    if ($res) $user = $res->fetch_assoc();
} elseif ($role == 'expert') {
    $res = $conn->query("SELECT * FROM expert WHERE expert_id = $user_id");
    if ($res) $user = $res->fetch_assoc();
    
    // Fetch Saved Specializations for UI
    $saved_specs = [];
    // Cats
    $r = $conn->query("SELECT c.category_id, c.name FROM expert_vehicle_categories e JOIN vehicle_categories c ON e.category_id = c.category_id WHERE e.expert_id = $user_id");
    while($row = $r->fetch_assoc()) $saved_specs[] = ['type'=>'category', 'id'=>$row['category_id'], 'name'=>$row['name'] . ' (Category)'];
    // Brands
    $r = $conn->query("SELECT b.brand_id, b.name, c.name as c_name FROM expert_vehicle_brands e JOIN vehicle_brands b ON e.brand_id = b.brand_id JOIN vehicle_categories c ON b.category_id = c.category_id WHERE e.expert_id = $user_id");
    while($row = $r->fetch_assoc()) $saved_specs[] = ['type'=>'brand', 'id'=>$row['brand_id'], 'name'=>$row['name'] . ' (' . $row['c_name'] . ')'];
    // Models
    $r = $conn->query("SELECT m.model_id, m.name, b.name as b_name FROM expert_vehicle_models e JOIN vehicle_models m ON e.model_id = m.model_id JOIN vehicle_brands b ON m.brand_id = b.brand_id WHERE e.expert_id = $user_id");
    while($row = $r->fetch_assoc()) $saved_specs[] = ['type'=>'model', 'id'=>$row['model_id'], 'name'=>$row['name'] . ' (Model - ' . $row['b_name'] . ')'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .form-container { max-width: 700px; margin: 40px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
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
            <h2>Profile Settings</h2>
            <?php echo $message; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 30px;">
                    <div id="photoPreview" style="width: 100px; height: 100px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <?php if (!empty($user['profile_photo'])): ?>
                            <img src="uploads/profiles/<?php echo $user['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 2.5rem; color: #94a3b8;"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label>Profile Photo</label>
                        <input type="file" name="profile_photo" accept="image/*" onchange="previewImage(this)">
                    </div>
                </div>

                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Bio / About You</label>
                    <textarea name="bio" rows="4" placeholder="Tell us a bit about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <?php if ($role == 'buyer'): ?>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                    </div>

                    <hr style="margin: 40px 0; border: none; border-top: 2px solid #f1f5f9;">

                    <h3>🔔 Notifications</h3>
                    <div class="form-group" style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
                        <label style="display:flex; align-items:center; gap:12px; cursor:pointer; font-weight: 500; margin-bottom: 15px;">
                            <input type="checkbox" name="email_notifications" value="1" <?php if($user['email_notifications']) echo 'checked'; ?> style="width: 18px; height: 18px;">
                            Email Notifications
                        </label>
                        <label style="display:flex; align-items:center; gap:12px; cursor:pointer; font-weight: 500; margin-bottom: 0;">
                            <input type="checkbox" name="sms_notifications" value="1" <?php if($user['sms_notifications']) echo 'checked'; ?> style="width: 18px; height: 18px;">
                            SMS Notifications
                        </label>
                    </div>

                    <h3>🔒 Password Security</h3>
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" placeholder="••••••••" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="New Password" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm Password" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="margin-top: 40px; padding: 25px; border: 1px solid #fee2e2; border-radius: 12px; background: #fffafb; margin-bottom: 30px;">
                        <h4 style="color: #991b1b; margin-top:0;">⚠️ Danger Zone</h4>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Deleting your account is permanent. All your data will be wiped.</p>
                        <button type="button" onclick="confirmDelete()" class="btn" style="background:#ef4444; color:white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Delete Account Permanently</button>
                    </div>

                    <script>
                    function confirmDelete() {
                        if (confirm('WARNING: Are you sure you want to permanently delete your account?')) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.innerHTML = '<input type="hidden" name="action" value="delete_account">';
                            document.body.appendChild(form);
                            form.submit();
                        }
                    }
                    </script>
                <?php endif; ?>

                <?php if ($role == 'expert'): ?>
                    <hr>
                    <h3>Expert Professional Profile</h3>
                    

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

                    <div class="form-group">
                        <label>Inspection Packages / Pricing</label>
                        <small style="color: #64748b; margin-bottom: 10px; display: block;">Add different service tiers (e.g. Basic, Standard, Full). Buyers will choose one during booking.</small>
                        <div id="packages-container">
                            <!-- JS will populate existing packages -->
                        </div>
                        <button type="button" class="btn" style="background:#f1f5f9; color:#475569; padding: 8px 15px; font-size: 0.9rem;" onclick="addPackageRow()">+ Add Package</button>
                    </div>

                    <div class="form-group">
                        <label>Service Area / Location</label>
                        <small style="color: #64748b; margin-bottom: 10px; display: block;">Enter your service area or drag the pin on the map to set your exact location.</small>
                        
                        <!-- Text Input with Autocomplete -->
                        <div style="position: relative;">
                            <input type="text" name="district" id="district-input" value="<?php echo htmlspecialchars($user['district'] ?? ''); ?>" required placeholder="e.g. Badulla" style="margin-bottom: 10px;" autocomplete="off">
                            <div id="location-suggestions"></div>
                        </div>

                        <!-- Map -->
                        <div id="map"></div>
                        <input type="hidden" name="latitude" id="lat" value="<?php echo $user['latitude'] ?? ''; ?>">
                        <input type="hidden" name="longitude" id="lng" value="<?php echo $user['longitude'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Qualifications</label>
                        <textarea name="qualification" rows="3"><?php echo htmlspecialchars($user['qualification'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Certification / Proof (PDF or Image)</label>
                        <?php if (!empty($user['certification_file'])): ?>
                            <div style="margin-bottom: 10px; font-size: 0.9rem;">
                                Current File: <a href="uploads/certs/<?php echo $user['certification_file']; ?>" target="_blank" style="color: #2563eb;">View Certification</a>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="certification" accept=".pdf,.jpg,.jpeg,.png">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>LinkedIn Profile URL</label>
                            <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($user['linkedin_url'] ?? ''); ?>" placeholder="https://linkedin.com/in/yourprofile">
                        </div>
                        <div class="form-group">
                            <label>Professional Website URL</label>
                            <input type="url" name="website_url" value="<?php echo htmlspecialchars($user['website_url'] ?? ''); ?>" placeholder="https://yourwebsite.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Experience (Years)</label>
                        <input type="number" name="experience" value="<?php echo htmlspecialchars($user['experience'] ?? 0); ?>" required>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Save Profile</button>
            </form>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('photoPreview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        <?php if ($role == 'expert'): ?>
            
            // --- Specialization Tag Logic ---
            const initialSpecs = <?php echo json_encode($saved_specs); ?>;
            const tagContainer = document.getElementById('tagContainer');
            const searchInput = document.getElementById('specSearch');
            const suggestionsBox = document.getElementById('suggestions');
            const hiddenInputs = document.getElementById('hiddenInputs');
            let addedSpecs = [];

            // Render Initial Tags
            initialSpecs.forEach(spec => addTag(spec.type, spec.id, spec.name));

            function addTag(type, id, name) {
                // Prevent Dupes
                if (addedSpecs.some(s => s.type === type && s.id === id)) return;

                addedSpecs.push({type, id, name});

                // Visual Tag
                const tag = document.createElement('div');
                tag.className = 'tag';
                tag.innerHTML = `${name} <span class="remove" onclick="removeTag('${type}', ${id}, this)">&times;</span>`;
                tagContainer.insertBefore(tag, searchInput);

                // Hidden Input
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

            // Search Interaction
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

            // Close suggestions on click outside
            document.addEventListener('click', function(e) {
                if (!tagContainer.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });


            // --- Map Logic ---
            var defaultLat = 6.9271; 
            var defaultLng = 79.8612;
            var map = L.map('map').setView([defaultLat, defaultLng], 8);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var markers = L.layerGroup().addTo(map);

            function addMarker(lat, lng, popupText = "Service Area") {
                markers.clearLayers();
                var marker = L.marker([lat, lng], {draggable: true});
                marker.bindPopup(popupText).openPopup();
                markers.addLayer(marker);
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
                return marker;
            }
            
            var savedLat = '<?php echo $user['latitude']; ?>';
            var savedLng = '<?php echo $user['longitude']; ?>';
            var savedDistrict = <?php echo json_encode($user['district'] ?? ''); ?>;

            if (savedLat && savedLng) {
               addMarker(savedLat, savedLng, savedDistrict || "Your Location");
               map.setView([savedLat, savedLng], 12);
            }

            map.on('click', function(e) {
                var marker = addMarker(e.latlng.lat, e.latlng.lng, "Selected Location");
                marker.on('dragend', function(event) {
                    var position = event.target.getLatLng();
                    updateCoordsAndReverseGeocode(position.lat, position.lng);
                });
                updateCoordsAndReverseGeocode(e.latlng.lat, e.latlng.lng);
            });

            function updateCoordsAndReverseGeocode(lat, lng) {
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
                reverseGeocode(lat, lng);
            }

            function reverseGeocode(lat, lng) {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                    .then(res => res.json())
                    .then(data => {
                        let city = data.address.city || data.address.town || data.address.village || "";
                        if (city) {
                            document.getElementById('district-input').value = city;
                        }
                    });
            }

            // --- Location Autocomplete Logic ---
            const locInput = document.getElementById('district-input');
            const locSuggestions = document.getElementById('location-suggestions');
            let locDebounce;
            
            const badullaTowns = ["Badulla", "Bandarawela", "Haputale", "Ella", "Welimada", "Mahiyanganaya", "Diyatalawa", "Hali Ela", "Passara", "Girandurukotte", "Mirahawatte", "Demodara", "Lunugala", "Kandaketiya", "Meegahakiula", "Soranathota", "Akkarasiyaya", "Aluketiyawa", "Aluttaramma", "Ambadandegama", "Ambagahawatta", "Ambagasdowa", "Amunumulla", "Arawa", "Arawakumbura", "Arawatta", "Atakiriya", "Baduluoya", "Ballaketuwa", "Bambarapana", "Beramada", "Beragala", "Bibilegama", "Bogahakumbura", "Boragas", "Boralanda", "Bowela", "Dambana", "Diganatenna", "Dikkapitiya", "Dimbulana", "Divulapelessa", "Dulgolla", "Egodawela", "Ettampitiya", "Galauda", "Galedanda", "Galporuyaya", "Gamewela", "Gawarawela", "Godunna", "Gurutalawa", "Haldummulla", "Hangunnawa", "Hebarawa", "Heeloya", "Helahalpe", "Helapupula", "Hewanakumbura", "Hingurukaduwa", "Hopton", "Idalgashinna", "Jangulla", "Kahataruppa", "Kalubululanda", "Kalugahakandura", "Kalupahana", "Kandegedara", "Kandepuhulpola", "Kebillawela", "Kendagolla", "Keppetipola", "Keselpotha", "Ketawatta", "Kiriwanagama", "Koslanda", "Kotamuduna", "Kuruwitenna", "Kuttiyagolla", "Landewela", "Liyangahawela", "Lunuwatta", "Madulsima", "Makulella", "Malgoda", "Maliyadda", "Mapakadawewa", "Maspanna", "Maussagolla", "Medawela Udukinda", "Medawelagama", "Metigahatenna", "Miriyabedda", "Miyanakandura", "Namunukula", "Narangala", "Nelumgama", "Nikapotha", "Nugatalawa", "Ohiya", "Pahalarathkinda", "Pallekiruwa", "Pangaragammana", "Pattiyagedara", "Pelagahatenna", "Perawella", "Pitapola", "Pitamaruwa", "Puhulpola", "Ratkarawwa", "Ridimaliyadda", "Rilpola", "Sirimalgoda", "Silmiyapura", "Soragune", "Sorabora Colony", "Spring Valley", "Taldena", "Tennapanguwa", "Timbirigaspitiya", "Uduhawara", "Uraniya", "Uva Deegalla", "Uva Karandagolla", "Uva Mawelagama", "Uva Paranagama", "Uva Tenna", "Uva Tissapura", "Uva Uduwara", "Uvaparanagama", "Wewatta", "Wineethagama", "Yalagamuwa", "Yalwela"];

            locInput.addEventListener('input', function() {
                clearTimeout(locDebounce);
                const query = this.value.toLowerCase();
                locSuggestions.innerHTML = '';
                if(query.length < 2) { locSuggestions.style.display = 'none'; return; }
                const localMatches = badullaTowns.filter(town => town.toLowerCase().includes(query));
                renderLocationSuggestions(localMatches, []);
                locDebounce = setTimeout(() => {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=lk&limit=5&addressdetails=1`)
                        .then(res => res.json())
                        .then(data => {
                            const apiMatches = data.filter(place => {
                                const str = JSON.stringify(place).toLowerCase();
                                return str.includes('badulla') || str.includes('uva');
                            });
                            const uniqueApiMatches = apiMatches.filter(apiItem => {
                                let name = apiItem.address.village || apiItem.address.town || apiItem.display_name.split(',')[0];
                                return !localMatches.some(local => local.toLowerCase() === name.toLowerCase());
                            });
                            renderLocationSuggestions(localMatches, uniqueApiMatches);
                        });
                }, 500);
            });

            function renderLocationSuggestions(localList, apiList) {
                if (localList.length === 0 && apiList.length === 0) {
                    if(locSuggestions.innerHTML === '') locSuggestions.style.display = 'none';
                    return;
                }
                locSuggestions.innerHTML = '';
                localList.forEach(town => {
                    const div = document.createElement('div');
                    div.style.padding = '10px 15px'; div.style.borderBottom = '1px solid #f1f5f9'; div.style.cursor = 'pointer';
                    div.style.fontSize = '0.9rem'; div.style.fontWeight = '600'; div.style.color = '#166534';
                    div.textContent = town + " (Badulla Dist.)";
                    div.onclick = () => selectLocationAndGeocode(town);
                    div.onmouseover = () => div.style.background = '#f0fdf4';
                    div.onmouseout = () => div.style.background = 'white';
                    locSuggestions.appendChild(div);
                });
                apiList.forEach(place => {
                    const div = document.createElement('div');
                    div.style.padding = '10px 15px'; div.style.borderBottom = '1px solid #f1f5f9'; div.style.cursor = 'pointer';
                    div.style.fontSize = '0.9rem';
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
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(name + ", Badulla, Sri Lanka")}&limit=1`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lng = parseFloat(data[0].lon);
                            map.setView([lat, lng], 12);
                            var marker = addMarker(lat, lng, name);
                            marker.on('dragend', function(event) {
                                var position = event.target.getLatLng();
                                updateCoordsAndReverseGeocode(position.lat, position.lng);
                            });
                        }
                    });
            }

            function selectLocationWithCoords(name, lat, lng) {
                locInput.value = name;
                locSuggestions.style.display = 'none';
                map.setView([lat, lng], 12);
                var marker = addMarker(lat, lng, name);
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
                marker.on('dragend', function(event) {
                    var position = event.target.getLatLng();
                    updateCoordsAndReverseGeocode(position.lat, position.lng);
                });
            }

            document.addEventListener('click', function(e) {
                if (!locInput.contains(e.target) && !locSuggestions.contains(e.target)) {
                    locSuggestions.style.display = 'none';
                }
            });

            // --- Packages Logic ---
            const packagesContainer = document.getElementById('packages-container');
            const existingPackages = <?php echo !empty($user['packages']) ? $user['packages'] : '[]'; ?>;

            function addPackageRow(name = '', price = '') {
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.gap = '10px';
                row.style.marginBottom = '10px';
                row.innerHTML = `
                    <input type="text" name="package_names[]" value="${name}" placeholder="Package Name (e.g. Basic)" required style="flex: 2;">
                    <input type="number" name="package_prices[]" value="${price}" placeholder="Price (LKR)" required style="flex: 1;">
                    <button type="button" class="btn" style="background:#fee2e2; color:#b91c1c; padding: 5px 10px;" onclick="this.parentElement.remove()">&times;</button>
                `;
                packagesContainer.appendChild(row);
            }

            // Load existing
            if (existingPackages.length > 0) {
                existingPackages.forEach(p => addPackageRow(p.name, p.price));
            } else {
                // Add one empty row by default
                addPackageRow();
            }

        <?php endif; ?>
    </script>

