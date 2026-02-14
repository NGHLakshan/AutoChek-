<?php
session_start();
require_once 'db.php';

$expert_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($expert_id <= 0) {
    header("Location: experts.php");
    exit;
}

// Fetch Expert Data
$expert_sql = "SELECT * FROM expert WHERE expert_id = $expert_id AND verified = 1";
$expert_res = $conn->query($expert_sql);
$expert = $expert_res->fetch_assoc();

if (!$expert) {
    die("Expert profile not found or not verified.");
}

// Fetch Specializations
$saved_specs = [];
// Cats
$r = $conn->query("SELECT c.name FROM expert_vehicle_categories e JOIN vehicle_categories c ON e.category_id = c.category_id WHERE e.expert_id = $expert_id");
while($row = $r->fetch_assoc()) $saved_specs[] = $row['name'] . ' (Category)';
// Brands
$r = $conn->query("SELECT b.name, c.name as c_name FROM expert_vehicle_brands e JOIN vehicle_brands b ON e.brand_id = b.brand_id JOIN vehicle_categories c ON b.category_id = c.category_id WHERE e.expert_id = $expert_id");
while($row = $r->fetch_assoc()) $saved_specs[] = $row['name'] . ' (' . $row['c_name'] . ')';
// Models
$r = $conn->query("SELECT m.name, b.name as b_name FROM expert_vehicle_models e JOIN vehicle_models m ON e.model_id = m.model_id JOIN vehicle_brands b ON m.brand_id = b.brand_id WHERE e.expert_id = $expert_id");
while($row = $r->fetch_assoc()) $saved_specs[] = $row['name'] . ' (' . $row['b_name'] . ')';

$packages = !empty($expert['packages']) ? json_decode($expert['packages'], true) : [];

// Fetch Statistics
// Total Completed Inspections
$completed_inspections_sql = "SELECT COUNT(*) as total FROM booking WHERE expert_id = $expert_id AND status = 'completed'";
$completed_res = $conn->query($completed_inspections_sql);
$completed_count = $completed_res->fetch_assoc()['total'];

// Average Rating and Review Count
$rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM review WHERE expert_id = $expert_id";
$rating_res = $conn->query($rating_sql);
$rating_data = $rating_res->fetch_assoc();
$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$review_count = $rating_data['review_count'];

$is_owner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $expert_id && $_SESSION['role'] == 'expert');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($expert['name']); ?> | AutoChek Expert</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .profile-header {
            background: white;
            padding: 40px 0;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 40px;
        }
        .profile-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 40px;
        }
        .profile-sidebar {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
            text-align: center;
            height: fit-content;
        }
        .profile-photo {
            width: 130px; /* Reduced from 150px */
            height: 130px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #cbd5e1;
            border: 3px solid var(--primary); /* Thinner border */
        }
        .spec-tag {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin: 0 4px 4px 0;
            font-weight: 500;
            border: 1px solid #e2e8f0;
        }
        #map { height: 250px; width: 100%; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 15px; }
        .package-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        @media (max-width: 992px) {
            .profile-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container" style="margin-top: 40px;">
        <div class="profile-container">
            
            <aside class="profile-sidebar">
                <div class="profile-photo">
                    <?php if(!empty($expert['profile_photo'])): ?>
                        <img src="uploads/profiles/<?php echo $expert['profile_photo']; ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($expert['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <h2 style="margin: 10px 0;"><?php echo htmlspecialchars($expert['name']); ?></h2>
                <div style="color: var(--primary); font-weight: 600; margin-bottom: 20px;">
                    <?php if ($avg_rating > 0): ?>
                        ⭐ <?php echo $avg_rating; ?> (<?php echo $review_count; ?> reviews)
                    <?php else: ?>
                        ⭐ New Expert
                    <?php endif; ?>
                </div>

                <!-- Booking CTA -->
                <?php if (!$is_owner && (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin')): ?>
                    <a href="book.php?expert_id=<?php echo $expert['expert_id']; ?>" class="btn btn-primary" style="display: flex; width: 100%; box-sizing: border-box; justify-content: center; align-items: center; gap: 8px; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 1.05rem; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25); margin-bottom: 30px;">
                        <i class="ph ph-calendar-plus" style="font-size: 1.4rem;"></i>
                        Book Inspection
                    </a>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 25px;">
                    <div style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 12px; border-radius: 12px; text-align: center; color: white;">
                        <div style="font-size: 1.25rem; font-weight: 700; margin-bottom: 2px;"><?php echo $completed_count; ?></div>
                        <div style="font-size: 0.65rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.05em;">Inspections</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); padding: 12px; border-radius: 12px; text-align: center; color: white;">
                        <div style="font-size: 1.25rem; font-weight: 700; margin-bottom: 2px;"><?php echo $expert['experience']; ?></div>
                        <div style="font-size: 0.65rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.05em;">Years Exp</div>
                    </div>
                </div>
                
                <div style="text-align: left; font-size: 0.9rem; color: #475569; background: #f1f5f9; padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
                    <p style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 8px;">
                        <i class="ph ph-map-pin" style="margin-top: 3px; color: #64748b;"></i>
                        <span><strong>Location:</strong><br><?php echo htmlspecialchars($expert['district']); ?></span>
                    </p>
                    <p style="margin-bottom: 12px; display: flex; align-items: flex-start; gap: 8px;">
                        <i class="ph ph-graduation-cap" style="margin-top: 3px; color: #64748b;"></i>
                        <span><strong>Qualification:</strong><br><span style="color: #64748b;"><?php echo nl2br(htmlspecialchars($expert['qualification'])); ?></span></span>
                    </p>
                    
                    <?php if(!empty($expert['linkedin_url'])): ?>
                        <p style="margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-linkedin-logo" style="color: #0077b5;"></i>
                            <a href="<?php echo htmlspecialchars($expert['linkedin_url']); ?>" target="_blank" style="color: #0077b5; text-decoration: none; font-weight: 600;">LinkedIn Profile</a>
                        </p>
                    <?php endif; ?>
                    
                    <?php if(!empty($expert['website_url'])): ?>
                        <p style="margin-bottom: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-globe" style="color: #64748b;"></i>
                            <a href="<?php echo htmlspecialchars($expert['website_url']); ?>" target="_blank" style="color: #10b981; text-decoration: none; font-weight: 600;">Visit Website</a>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ($is_owner): ?>
                    <div style="background: #eff6ff; color: #1e40af; padding: 15px; border-radius: 12px; border: 1px solid #dbeafe; font-size: 0.9rem; font-weight: 500;">
                        👋 You are viewing your own public profile.
                        <a href="profile_settings.php" style="display: block; margin-top: 10px; color: #10b981; font-weight: 700; text-decoration: none;">Edit Settings →</a>
                    </div>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <div style="background: #f0fdf4; color: #15803d; padding: 15px; border-radius: 12px; border: 1px solid #bbf7d0; font-size: 0.9rem; font-weight: 500; text-align: center;">
                        👤 Admin View
                    </div>
                <?php endif; ?>
            </aside>

            <main>
                <div class="card" style="margin-bottom: 30px; padding: 30px;">
                    <h3 class="section-title" style="display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-info" style="color: var(--primary);"></i> About Expert
                    </h3>
                    <p style="line-height: 1.8; color: #475569; font-size: 1rem; margin-top: 15px;">
                        <?php echo nl2br(htmlspecialchars($expert['bio'])); ?>
                    </p>
                </div>

                <div class="card" style="margin-bottom: 30px; padding: 30px;">
                    <h3 class="section-title" style="display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-car" style="color: var(--primary);"></i> Vehicle Specializations
                    </h3>
                    <div style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php if (!empty($saved_specs)): ?>
                            <?php foreach ($saved_specs as $spec): ?>
                                <span class="spec-tag"><?php echo htmlspecialchars($spec); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #94a3b8; font-style: italic;">No specific vehicle types listed.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 30px; padding: 30px;">
                    <h3 class="section-title" style="display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-package" style="color: var(--primary);"></i> Inspection Packages
                    </h3>
                    <div style="margin-top: 20px;">
                        <?php if (!empty($packages)): ?>
                            <?php foreach ($packages as $pkg): ?>
                                <div class="package-card">
                                    <div>
                                        <h4 style="margin: 0; color: #1e293b;"><?php echo htmlspecialchars($pkg['name']); ?></h4>
                                        <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: #64748b;">Full vehicle diagnostics and report.</p>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="font-size: 1.15rem; font-weight: 700; color: #059669;">LKR <?php echo number_format($pkg['price'], 2); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px dashed #e2e8f0;">
                                <p style="color: #94a3b8; margin: 0; font-size: 0.9rem;">No standard packages listed. Please contact for a quote.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 40px; padding: 30px;">
                    <h3 class="section-title" style="display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-map-pin" style="color: var(--primary);"></i> Service Location
                    </h3>
                    <div id="map"></div>
                </div>

                <h3 class="section-title" style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                    <i class="ph ph-star" style="color: #f59e0b;"></i> Verified Reviews
                </h3>
                <?php
                $rev_sql = "SELECT r.*, b.name as buyer_name, b.profile_photo FROM review r JOIN buyer b ON r.buyer_id = b.buyer_id WHERE r.expert_id = $expert_id ORDER BY r.review_date DESC";
                $reviews = $conn->query($rev_sql);
                
                if ($reviews && $reviews->num_rows > 0):
                    while($rev = $reviews->fetch_assoc()):
                ?>
                    <div class="card" style="margin-bottom: 15px; padding: 25px;">
                        <div style="display:flex; justify-content:space-between; align-items: center; margin-bottom:12px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #e2e8f0;">
                                    <?php if(!empty($rev['profile_photo'])): ?>
                                        <img src="uploads/profiles/<?php echo $rev['profile_photo']; ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        👤
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong style="font-size: 1rem; color: #1e293b; display: block;"><?php echo htmlspecialchars($rev['buyer_name']); ?></strong>
                                    <small style="color: #94a3b8;"><?php echo date('M d, Y', strtotime($rev['review_date'])); ?></small>
                                </div>
                            </div>
                            <span style="color: #f59e0b; font-weight: 700; font-size: 1.1rem;">★ <?php echo $rev['rating']; ?>.0</span>
                        </div>
                        <p style="font-size: 0.95rem; color: #475569; margin: 0; line-height: 1.6;">"<?php echo htmlspecialchars($rev['comment']); ?>"</p>
                        
                        <?php if (!empty($rev['expert_reply'])): ?>
                            <div style="background: #f8fafc; border-left: 3px solid #10b981; padding: 15px; margin-top: 15px; border-radius: 8px;">
                                <small style="font-weight: 700; color: #166534; display: block; margin-bottom: 5px;">Response from Expert:</small>
                                <p style="font-size: 0.9rem; color: #334155; margin: 0; font-style: italic;"><?php echo htmlspecialchars($rev['expert_reply']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php 
                    endwhile; 
                else:
                    echo "<p style='color: #94a3b8;'>No reviews for this expert yet.</p>";
                endif;
                ?>
            </main>

        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var lat = <?php echo !empty($expert['latitude']) ? $expert['latitude'] : '6.9271'; ?>;
            var lng = <?php echo !empty($expert['longitude']) ? $expert['longitude'] : '79.8612'; ?>;
            var map = L.map('map').setView([lat, lng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            L.marker([lat, lng]).addTo(map)
                .bindPopup("<?php echo htmlspecialchars($expert['name']); ?>'s Service Area")
                .openPopup();
        });
    </script>

    <?php include 'footer.php'; ?>

</body>
</html>
