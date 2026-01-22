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

// --- 1. HANDLE FORM SUBMISSIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['action'])) {
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // --- UPDATE ACCOUNT & NOTIFS ---
    if ($action == 'update_account') {
        $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notif = isset($_POST['sms_notifications']) ? 1 : 0;
        
        // Determine table and ID column based on role
        if ($role == 'buyer') {
            $table = 'buyer';
            $id_col = 'buyer_id';
        } elseif ($role == 'admin') {
            $table = 'admin';
            $id_col = 'admin_id';
        } else {
            $table = 'expert';
            $id_col = 'expert_id';
        }
        
        $availability_sql = "";
        if ($role == 'expert') {
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            $availability_sql = ", is_available = $is_available";
        }
        
        $conn->query("UPDATE $table SET email_notifications = $email_notif, sms_notifications = $sms_notif $availability_sql WHERE $id_col = $user_id");
        
        // Password Change
        if (!empty($_POST['new_password'])) {
            $current_pass = $_POST['current_password'];
            $new_pass = $_POST['new_password'];
            $confirm_pass = $_POST['confirm_password'];
            $row = $conn->query("SELECT password FROM $table WHERE $id_col = $user_id")->fetch_assoc();
            if (password_verify($current_pass, $row['password'])) {
                if ($new_pass === $confirm_pass && strlen($new_pass) >= 6) {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $conn->query("UPDATE $table SET password = '$hashed' WHERE $id_col = $user_id");
                    $message = "<div class='alert-success'>Account updated & password changed!</div>";
                } else {
                    $message = "<div class='alert-error'>Password mismatch or too short.</div>";
                }
            } else {
                $message = "<div class='alert-error'>Incorrect current password.</div>";
            }
        } else {
            $message = "<div class='alert-success'>Preferences updated!</div>";
        }
    }

    // ACTION: DELETE ACCOUNT
    if ($action == 'delete_account') {
        if ($role == 'buyer') {
            $table = 'buyer';
            $id_col = 'buyer_id';
        } elseif ($role == 'admin') {
            $table = 'admin';
            $id_col = 'admin_id';
        } else {
            $table = 'expert';
            $id_col = 'expert_id';
        }
        $conn->query("DELETE FROM $table WHERE $id_col = $user_id");
        session_destroy();
        header("Location: index.php?msg=account_deleted");
        exit;
    }

    // ACTION: REMOVE DETAIL (AJAX)
    if (isset($_GET['action']) && $_GET['action'] == 'remove_detail') {
        header('Content-Type: application/json');
        $field = $_GET['field'] ?? '';
        $allowed = ['name', 'phone', 'bio', 'location', 'district', 'linkedin_url', 'website_url'];
        
        if (in_array($field, $allowed)) {
            if ($role == 'buyer') {
                $table = 'buyer';
                $id_col = 'buyer_id';
            } elseif ($role == 'admin') {
                $table = 'admin';
                $id_col = 'admin_id';
            } else {
                $table = 'expert';
                $id_col = 'expert_id';
            }
            if ($conn->query("UPDATE $table SET $field = NULL WHERE $id_col = $user_id")) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid field']);
        }
        exit;
    }
}

// --- 2. FETCH CURRENT DATA ---
if ($role == 'buyer') {
    $table = 'buyer';
    $id_col = 'buyer_id';
} elseif ($role == 'admin') {
    $table = 'admin';
    $id_col = 'admin_id';
} else {
    $table = 'expert';
    $id_col = 'expert_id';
}
$user = $conn->query("SELECT * FROM $table WHERE $id_col = $user_id")->fetch_assoc();

$saved_specs = [];
if ($role == 'expert') {
    $r = $conn->query("SELECT c.category_id, c.name FROM expert_vehicle_categories e JOIN vehicle_categories c ON e.category_id = c.category_id WHERE e.expert_id = $user_id");
    while($row = $r->fetch_assoc()) $saved_specs[] = ['type'=>'category', 'id'=>$row['category_id'], 'name'=>$row['name'] . ' (Category)'];
    $r = $conn->query("SELECT b.brand_id, b.name, c.name as c_name FROM expert_vehicle_brands e JOIN vehicle_brands b ON e.brand_id = b.brand_id JOIN vehicle_categories c ON b.category_id = c.category_id WHERE e.expert_id = $user_id");
    while($row = $r->fetch_assoc()) $saved_specs[] = ['type'=>'brand', 'id'=>$row['brand_id'], 'name'=>$row['name'] . ' (' . $row['c_name'] . ')'];
    $r = $conn->query("SELECT m.model_id, m.name, b.name as b_name FROM expert_vehicle_models e JOIN vehicle_models m ON e.model_id = m.model_id JOIN vehicle_brands b ON m.brand_id = b.brand_id WHERE e.expert_id = $user_id");
    while($row = $r->fetch_assoc()) $saved_specs[] = ['type'=>'model', 'id'=>$row['model_id'], 'name'=>$row['name'] . ' (Model - ' . $row['b_name'] . ')'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .settings-layout { display: grid; grid-template-columns: 240px 1fr; gap: 40px; margin-top: 30px; }
        .settings-nav { background: white; padding: 20px; border-radius: 12px; height: fit-content; border: 1px solid #f1f5f9; }
        .nav-item { padding: 12px 15px; border-radius: 8px; color: #64748b; font-weight: 500; transition: all 0.2s; margin-bottom: 5px; list-style: none; display: flex; align-items: center; gap: 10px; }
        .nav-item.active { background: #eff6ff; color: var(--primary); }
        
        .form-card { background: white; padding: 30px; border-radius: 12px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        
        .form-group { margin-bottom: 25px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.95rem; color: #1e293b; }
        input[type="password"] { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; background: #f8fafc; }
        
        .section-header { margin: 40px 0 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; font-size: 1.1rem; color: #334155; display: flex; align-items: center; gap: 10px; }
        .section-header:first-child { margin-top: 0; }

        .highlights-section { margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
        .highlights-title { font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 15px; }
        .highlight-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px; font-size: 0.85rem; color: #475569; border: 1px solid transparent; transition: all 0.2s; }
        .highlight-item:hover { border-color: #e2e8f0; background: white; }
        .highlight-item .remove-btn { color: #cbd5e1; cursor: pointer; font-size: 1.1rem; line-height: 1; transition: color 0.2s; }
        .highlight-item .remove-btn:hover { color: #ef4444; }
        .highlight-label { display: flex; align-items: center; gap: 8px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .highlight-label i { font-style: normal; opacity: 0.7; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <?php if ($role == 'buyer') include 'buyer_subnav.php'; ?>
        <div class="settings-layout">
            
            <aside class="settings-nav">
                <div class="nav-item active"><i class="ph-fill ph-gear"></i> <?php 
                    if ($role == 'admin') echo 'Admin Settings';
                    elseif ($role == 'expert') echo 'Expert Settings';
                    else echo 'Account Settings';
                ?></div>

                <?php if ($role != 'admin'): ?>
                <div class="highlights-section" id="sidebarHighlights">
                    <div class="highlights-title">Profile Highlights</div>
                    <?php 
                    $highlights = [];
                    
                    // For buyer: show name, phone, location
                    if ($role == 'buyer') {
                        $highlights[] = ['field' => 'name', 'icon' => '<i class="ph ph-user"></i>', 'label' => 'Name', 'val' => $user['name']];
                        $highlights[] = ['field' => 'phone', 'icon' => '<i class="ph ph-phone"></i>', 'label' => 'Phone', 'val' => $user['phone']];
                        $highlights[] = ['field' => 'location', 'icon' => '<i class="ph ph-map-pin"></i>', 'label' => 'Location', 'val' => $user['location'] ?? ''];
                    }
                    // For expert: show all fields
                    else {
                        $highlights[] = ['field' => 'name', 'icon' => '<i class="ph ph-user"></i>', 'label' => 'Name', 'val' => $user['name']];
                        $highlights[] = ['field' => 'phone', 'icon' => '<i class="ph ph-phone"></i>', 'label' => 'Phone', 'val' => $user['phone']];
                        $highlights[] = ['field' => 'bio', 'icon' => '<i class="ph ph-notepad"></i>', 'label' => 'Bio', 'val' => $user['bio']];
                        $highlights[] = ['field' => 'district', 'icon' => '<i class="ph ph-buildings"></i>', 'label' => 'District', 'val' => $user['district'] ?? ''];
                        $highlights[] = ['field' => 'linkedin_url', 'icon' => '<i class="ph ph-linkedin-logo"></i>', 'label' => 'LinkedIn', 'val' => $user['linkedin_url'] ?? ''];
                        $highlights[] = ['field' => 'website_url', 'icon' => '<i class="ph ph-globe"></i>', 'label' => 'Website', 'val' => $user['website_url'] ?? ''];
                    }
                    
                    
                    foreach ($highlights as $h): 
                        if (!empty($h['val'])):
                    ?>
                        <div class="highlight-item" data-field="<?php echo $h['field']; ?>">
                            <div class="highlight-label">
                                <i><?php echo $h['icon']; ?></i>
                                <span><?php echo htmlspecialchars($h['val']); ?></span>
                            </div>
                            <span class="remove-btn" onclick="removeHighlight('<?php echo $h['field']; ?>')">&times;</span>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <?php endif; ?>
            </aside>

            <main>
                <?php echo $message; ?>


                <div class="form-card">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_account">
                        
                        <?php if ($role == 'expert'): ?>
                            <div class="section-header"><i class="ph ph-coffee"></i> Availability Status</div>
                            <div class="form-group" style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <label style="display:flex; align-items:center; gap:12px; cursor:pointer; font-weight: 700; color: #1e293b; margin-bottom: 0;">
                                    <input type="checkbox" name="is_available" value="1" <?php if($user['is_available']) echo 'checked'; ?> style="width: 20px; height: 20px;">
                                    I am currently available for new bookings
                                </label>
                                <p style="font-size: 0.85rem; color: #64748b; margin: 10px 0 0 32px;">When disabled, your profile will be hidden from the search results.</p>
                            </div>
                        <?php endif; ?>

                        <div class="section-header"><i class="ph ph-lock-key"></i> Password Reset</div>
                        <div class="form-group">
                            <label>Current Password</label>
                            <div style="position: relative;">
                                 <i class="ph ph-lock-key" style="position: absolute; left: 10px; top: 12px; color: #94a3b8;"></i>
                                <input type="password" name="current_password" placeholder="••••••••" style="padding-left: 35px;">
                            </div>
                        </div>
                        <div class="mobile-stack" style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div class="form-group">
                                <label>New Password</label>
                                <div style="position: relative;">
                                     <i class="ph ph-lock-key" style="position: absolute; left: 10px; top: 12px; color: #94a3b8;"></i>
                                    <input type="password" name="new_password" placeholder="New Password" style="padding-left: 35px;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                 <div style="position: relative;">
                                     <i class="ph ph-lock-key" style="position: absolute; left: 10px; top: 12px; color: #94a3b8;"></i>
                                    <input type="password" name="confirm_password" placeholder="Confirm Password" style="padding-left: 35px;">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 10px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary">Update Settings</button>
                        </div>
                    </form>

                    <div style="margin-top: 60px; padding: 25px; border: 1px solid #fee2e2; border-radius: 12px; background: #fffafb;">
                        <h4 style="color: #991b1b; margin-top:0; display: flex; align-items: center; gap: 10px;"><i class="ph-fill ph-warning"></i> Danger Zone</h4>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Deleting your account is permanent and cannot be undone. All your data will be wiped.</p>
                        <form method="POST" onsubmit="return confirm('WARNING: Are you sure you want to permanently delete your account?')">
                            <input type="hidden" name="action" value="delete_account">
                            <button type="submit" class="btn" style="background:#ef4444; color:white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Delete Account Permanently</button>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function removeHighlight(field) {
            if (!confirm('Are you sure you want to remove this detail?')) return;
            fetch(`?action=remove_detail&field=${field}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const item = document.querySelector(`.highlight-item[data-field="${field}"]`);
                        if (item) item.remove();
                    } else { alert('Error: ' + data.error); }
                });
        }
    </script>
</body>
</html>
