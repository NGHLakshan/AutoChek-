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

// 1. Handle Form Submission & AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['action'])) {
    


    if ($role == 'buyer') {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $bio = $_POST['bio'] ?? '';

        
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

        $stmt = $conn->prepare("UPDATE buyer SET name = ?, phone = ?, bio = ? WHERE buyer_id = ?");
        $stmt->bind_param("sssi", $name, $phone, $bio, $user_id);
        
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
        $stmt->bind_param("sssssiddsssi", $name, $phone, $bio, $district, $qualification, $experience, $latitude, $longitude, $packages_json, $linkedin, $website, $user_id);
        
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
        }

        .settings-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .settings-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .settings-header {
            padding: 30px;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
        }

        .settings-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-content {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #334155;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
            pointer-events: none;
        }

        input[type="text"], 
        input[type="number"], 
        input[type="password"], 
        input[type="email"], 
        input[type="url"], 
        textarea {
            width: 100%;
            padding: 12px 16px;
            padding-left: 42px; /* Space for icon */
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #fff;
        }

        textarea {
            padding-left: 16px; /* No icon usually */
            resize: vertical;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .photo-upload-area {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px dashed #cbd5e1;
        }

        .current-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .btn-upload {
            border: 1px solid #e2e8f0;
            color: #475569;
            background-color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-upload:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }

        input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        /* Map styling */
        #map { 
            height: 350px; 
            width: 100%; 
            border-radius: 12px; 
            border: 1px solid #cbd5e1; 
            margin-top: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Tags and Pills */
        .tag-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            min-height: 46px;
            background: white;
        }
        
        .tag-container:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .tag { 
            background: #eff6ff; 
            color: #1d4ed8; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 0.85rem; 
            font-weight: 500;
            display: flex; 
            align-items: center; 
            gap: 6px; 
            border: 1px solid #dbeafe;
        }
        
        .tag span.remove { 
            cursor: pointer; 
            display: flex;
            align-items: center;
            justify-content: center;
            width: 16px; 
            height: 16px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
        }
        
        .tag span.remove:hover { 
            background: #bfdbfe; 
            color: #1e3a8a;
        }
        
        .tag-input { 
            border: none !important; 
            outline: none !important; 
            box-shadow: none !important;
            padding: 5px !important;
            flex: 1; 
            min-width: 150px; 
            background: transparent !important;
        }

        .suggestions-list, #location-suggestions {
            border: 1px solid #e2e8f0; 
            border-radius: 10px; 
            position: absolute; 
            background: white; 
            width: 100%; 
            max-height: 250px; 
            overflow-y: auto; 
            z-index: 1000; 
            display: none; 
            margin-top: 5px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        
        .suggestion-item { 
            padding: 12px 16px; 
            cursor: pointer; 
            border-bottom: 1px solid #f1f5f9; 
            transition: background 0.1s;
        }
        
        .suggestion-item:hover { 
            background: #f8fafc; 
        }

        /* Danger Zone */
        .danger-zone {
            margin-top: 40px;
            padding: 25px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            background: #fef2f2;
        }

        .danger-zone h4 {
            color: #991b1b;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-save {
            background: #2563eb;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-save:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }

        /* Notices */
        .alert-success { background: #dcfce7; color: #166534; padding: 16px; border-radius: 10px; margin-bottom: 24px; border: 1px solid #bbf7d0; display: flex; gap: 10px; align-items: center; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 16px; border-radius: 10px; margin-bottom: 24px; border: 1px solid #fecaca; display: flex; gap: 10px; align-items: center; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="settings-container">
        
        <?php echo $message; ?>

        <div class="settings-card">
            <div class="settings-header">
                <h2><i class="ph ph-gear"></i> Profile Settings</h2>
            </div>
            
            <div class="settings-content">
                <form method="POST" enctype="multipart/form-data">
                    
                    <!-- Basic Info -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="ph ph-user-circle"></i> Basic Information</h3>
                        
                        <div class="form-group">
                            <label>Profile Photo</label>
                            <div class="photo-upload-area">
                                <div class="current-photo" id="photoPreview">
                                    <?php if (!empty($user['profile_photo'])): ?>
                                        <img src="uploads/profiles/<?php echo $user['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="ph ph-user" style="font-size: 2.5rem; color: #94a3b8;"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="file-input-wrapper">
                                        <button class="btn-upload" type="button">Choose New Photo</button>
                                        <input type="file" name="profile_photo" accept="image/*" onchange="previewImage(this)">
                                    </div>
                                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #94a3b8;">JPG, PNG or GIF. Max size 2MB.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mobile-stack" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Your Name</label>
                                <div class="input-wrapper">
                                    <i class="ph ph-user"></i>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Phone Number</label>
                                <div class="input-wrapper">
                                    <i class="ph ph-phone"></i>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Bio / About You</label>
                            <textarea name="bio" rows="4" placeholder="Tell us a bit about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <?php if ($role == 'buyer'): ?>
                        <!-- BUYER SPECIFIC SETTINGS -->
                        <div class="form-section">
                            <h3 class="section-title"><i class="ph ph-bell-ringing"></i> Notifications</h3>
                            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <label style="display:flex; align-items:center; gap:12px; cursor:pointer; font-weight: 500; margin-bottom: 15px;">
                                    <input type="checkbox" name="email_notifications" value="1" <?php if($user['email_notifications']) echo 'checked'; ?> style="width: 18px; height: 18px; accent-color: #2563eb;">
                                    Email Notifications
                                </label>
                                <label style="display:flex; align-items:center; gap:12px; cursor:pointer; font-weight: 500; margin-bottom: 0;">
                                    <input type="checkbox" name="sms_notifications" value="1" <?php if($user['sms_notifications']) echo 'checked'; ?> style="width: 18px; height: 18px; accent-color: #2563eb;">
                                    SMS Notifications
                                </label>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title"><i class="ph ph-shield-check"></i> Security</h3>
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="input-wrapper">
                                    <i class="ph ph-lock-key"></i>
                                    <input type="password" name="current_password" placeholder="••••••••">
                                </div>
                            </div>
                            <div class="mobile-stack" style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                                <div class="form-group">
                                    <label>New Password</label>
                                    <div class="input-wrapper">
                                        <i class="ph ph-lock-key"></i>
                                        <input type="password" name="new_password" placeholder="New Password">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <div class="input-wrapper">
                                        <i class="ph ph-lock-key"></i>
                                        <input type="password" name="confirm_password" placeholder="Confirm Password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="danger-zone">
                            <h4><i class="ph-fill ph-warning-circle"></i> Danger Zone</h4>
                            <p style="font-size: 0.9rem; color: #7f1d1d; margin-bottom: 15px;">Deleting your account is permanent. All your data will be wiped.</p>
                            <button type="button" onclick="confirmDelete()" class="btn-delete">Delete Account Permanently</button>
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
                        <!-- EXPERT SPECIFIC SETTINGS -->
                        <div class="form-section">
                            <h3 class="section-title"><i class="ph ph-briefcase"></i> Professional Details</h3>
                            
                            <div class="form-group">
                                <label>Vehicle Specializations</label>
                                <small style="color: #64748b; margin-bottom: 8px; display: block;">Search and add vehicles you inspect (Category, Brand, or Model).</small>
                                <div style="position: relative;">
                                     <div class="tag-container" id="tagContainer">
                                         <i class="ph ph-magnifying-glass" style="color: #94a3b8; font-size: 1.1rem; margin-left: 5px;"></i>
                                         <input type="text" class="tag-input" id="specSearch" placeholder="Type to search (e.g. 'Toyota', 'Bike')..." autocomplete="off">
                                     </div>
                                     <div class="suggestions-list" id="suggestions"></div>
                                </div>
                                <!-- Hidden inputs generated by JS -->
                                <div id="hiddenInputs"></div>
                            </div>

                            <div class="form-group">
                                <label>Inspection Packages / Pricing</label>
                                <small style="color: #64748b; margin-bottom: 15px; display: block;">Add different service tiers (e.g. Basic, Standard, Full). Buyers will choose one during booking.</small>
                                <div id="packages-container" style="background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                    <!-- JS will populate existing packages -->
                                </div>
                                <button type="button" class="btn" style="background: white; border: 1px dashed #cbd5e1; color: #475569; padding: 10px; width: 100%; border-radius: 8px; margin-top: 10px; cursor: pointer; font-weight: 500; font-size: 0.9rem; transition: background 0.2s;" onclick="addPackageRow()">
                                    <i class="ph ph-plus"></i> Add Package
                                </button>
                            </div>

                            <div class="form-group" style="margin-top: 25px;">
                                <label>Qualifications</label>
                                <textarea name="qualification" rows="3"><?php echo htmlspecialchars($user['qualification'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mobile-stack" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Experience (Years)</label>
                                    <div class="input-wrapper">
                                        <i class="ph ph-briefcase"></i>
                                        <input type="number" name="experience" value="<?php echo htmlspecialchars($user['experience'] ?? 0); ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Certification Upload</label>
                                    <div class="file-input-wrapper" style="width: 100%;">
                                        <button class="btn-upload" type="button" style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
                                            <span>Upload PDF/Image</span>
                                            <i class="ph ph-upload-simple"></i>
                                        </button>
                                        <input type="file" name="certification" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>
                                    <?php if (!empty($user['certification_file'])): ?>
                                        <small style="display: block; margin-top: 5px;">
                                            Current: <a href="uploads/certs/<?php echo $user['certification_file']; ?>" target="_blank" style="color: #2563eb;">View File</a>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title"><i class="ph ph-map-pin"></i> Service Area</h3>
                            
                            <div class="form-group">
                                <label>Location</label>
                                <div style="position: relative; z-index: 1001;">
                                    <div class="input-wrapper">
                                        <i class="ph ph-map-pin"></i>
                                        <input type="text" name="district" id="district-input" value="<?php echo htmlspecialchars($user['district'] ?? ''); ?>" required placeholder="Search your city (e.g. Badulla)" autocomplete="off">
                                    </div>
                                    <div id="location-suggestions"></div>
                                </div>
                                <div id="location-error" class="alert-error" style="display:none; padding: 10px; font-size: 0.9rem; margin-top: 10px;"></div>

                                <!-- Map -->
                                <div id="map"></div>
                                <small style="color: #64748b; margin-top: 8px; display: block; text-align: center;">Drag the marker to pinpoint your exact location.</small>
                                <input type="hidden" name="latitude" id="lat" value="<?php echo $user['latitude'] ?? ''; ?>">
                                <input type="hidden" name="longitude" id="lng" value="<?php echo $user['longitude'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="section-title"><i class="ph ph-link"></i> Social Links</h3>
                            <div class="mobile-stack" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>LinkedIn Profile</label>
                                    <div class="input-wrapper">
                                        <i class="ph ph-linkedin-logo"></i>
                                        <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($user['linkedin_url'] ?? ''); ?>" placeholder="https://linkedin.com/in/..." >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Website</label>
                                    <div class="input-wrapper">
                                        <i class="ph ph-globe"></i>
                                        <input type="url" name="website_url" value="<?php echo htmlspecialchars($user['website_url'] ?? ''); ?>" placeholder="https://..." >
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-save">
                            <i class="ph ph-check-circle"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
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

            // Close suggestions on click outside
            document.addEventListener('click', function(e) {
                if (!tagContainer.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });


            // --- Map & Location Logic ---
            var savedLat = '<?php echo $user['latitude']; ?>';
            var savedLng = '<?php echo $user['longitude']; ?>';
            var startLat = savedLat ? parseFloat(savedLat) : 6.9847; 
            var startLng = savedLng ? parseFloat(savedLng) : 81.0564;
            var startZoom = savedLat ? 13 : 10;

            var map = L.map('map').setView([startLat, startLng], startZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var markers = L.layerGroup().addTo(map);
            
            const locInput = document.getElementById('district-input');
            const locSuggestions = document.getElementById('location-suggestions');
            const locError = document.getElementById('location-error'); // Must be added in HTML
            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');

            // Error Feedback
            function showError(msg) {
                if(locError) {
                    locError.textContent = msg;
                    locError.style.display = 'block';
                }
                locInput.style.borderColor = '#991b1b';
            }
            
            function clearError() {
                if(locError) locError.style.display = 'none';
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
            
            // Init Marker
            if (savedLat && savedLng) {
               addMarker(startLat, startLng, <?php echo json_encode($user['district'] ?? 'Your Location'); ?>);
               
               // Add listener to initial marker too
               markers.eachLayer(layer => {
                    layer.on('dragend', function(e) {
                        const pos = e.target.getLatLng();
                        validateAndSetLocation(pos.lat, pos.lng);
                    });
                });
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
                             // Fallback if nominatim fails to give address but gives coords? 
                             // Unlikely, usually means ocean or middle of nowhere.
                             showError("Could not identify location.");
                             return;
                        }

                        // Robust Validation: Check if 'Badulla' appears anywhere in the address details (District, City, State, etc.)
                        const addressText = Object.values(addr).join(' ').toLowerCase();
                        const isBadulla = addressText.includes("badulla");

                        if (isBadulla) {
                            // Success
                            let niceName = displayName;
                            if (!niceName) {
                                niceName = addr.village || addr.town || addr.city || addr.suburb || data.display_name.split(',')[0];
                            }
                            locInput.value = niceName;
                            
                            const marker = addMarker(lat, lng, niceName);
                            
                            marker.on('dragend', function(e) {
                                const pos = e.target.getLatLng();
                                validateAndSetLocation(pos.lat, pos.lng);
                            });
                            
                        } else {
                            // Failed Validation
                            console.warn("Validation failed. Address text:", addressText);
                            showError("Location must be in Badulla District. Detected: " + (addr.state_district || addr.city || "Unknown Area"));
                            latInput.value = "";
                            lngInput.value = "";
                        }
                    })
                    .catch(err => {
                        console.error(err); 
                        showError("Network error validating location.");
                    });
            }

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
                clearTimeout(locDebounce);
                clearError();
                const query = this.value.toLowerCase();
                locSuggestions.innerHTML = '';
                if(query.length < 2) { locSuggestions.style.display = 'none'; return; }
                
                const localMatches = badullaTowns.filter(town => town.toLowerCase().includes(query));
                renderLocationSuggestions(localMatches, []);
                
                locDebounce = setTimeout(() => {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + " Badulla")}&countrycodes=lk&limit=5&addressdetails=1`)
                    .then(res => res.json())
                    .then(data => {
                        const uniqueApiMatches = data.filter(apiItem => {
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
                
                function createItem(name, type, lat, lng) {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.style.padding = '10px 15px'; div.style.borderBottom = '1px solid #f1f5f9'; div.style.cursor = 'pointer';
                    if(type === 'local') {
                        div.style.fontWeight = '600'; div.style.color = '#166534';
                        div.textContent = name + " (Badulla Dist.)";
                    } else {
                        div.textContent = name;
                    }
                    div.onclick = () => {
                         if (lat && lng) selectLocation(name, lat, lng);
                         else selectLocalLocation(name);
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
                        selectLocation(name, lat, lng);
                    } else {
                        showError("Could not find coordinates.");
                    }
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
                    <input type="text" name="package_names[]" value="${name}" placeholder="Package Name (e.g. Basic)" required style="flex: 2; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <input type="number" name="package_prices[]" value="${price}" placeholder="Price (LKR)" required style="flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <button type="button" class="btn" style="background:#fee2e2; color:#b91c1c; padding: 5px 12px; border:none; border-radius:8px; cursor:pointer;" onclick="this.parentElement.remove()"><i class="ph-fill ph-trash"></i></button>
                `;
                packagesContainer.appendChild(row);
            }

            // Prevent Submission if Invalid Location
            document.querySelector('form').addEventListener('submit', function(e) {
                if (latInput.value === '' || lngInput.value === '') {
                    e.preventDefault();
                    showError("Please select a valid location inside Badulla District.");
                    locInput.focus();
                    /* Scroll to error */
                    locInput.scrollIntoView({behavior: "smooth", block: "center"});
                }
            });

            // Load existing
            if (existingPackages.length > 0) {
                existingPackages.forEach(p => addPackageRow(p.name, p.price));
            } else {
                // Add one empty row by default
                addPackageRow();
            }

        <?php endif; ?>
    </script>
</body>
</html>
