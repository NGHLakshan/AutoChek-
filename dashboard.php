<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle Actions
if (isset($_GET['action']) && $_GET['action'] == 'toggle_availability' && $role == 'expert') {
    $conn->query("UPDATE expert SET is_available = NOT is_available WHERE expert_id = $user_id");
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AutoChek</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .card { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1) !important; border: 1px solid #f1f5f9; }
        .request-item:hover { background-color: #f8fafc; transition: background 0.2s; }
        .avatar { flex-shrink: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        .card-footer { margin-top: auto; display: flex; gap: 10px; flex-direction: column; }

        /* Paper Filing Aesthetic (ISOLATED TO BUYER) */
        .buyer-view { background: #f0f4f8; min-height: 100vh; padding-bottom: 50px; margin: -20px -15px; padding: 20px 15px; }
        .buyer-view .booking-list { display: flex; flex-direction: column; gap: 12px; margin-top: 15px; }
        
        .buyer-view .booking-card { 
            background: white; 
            border-radius: 8px; /* Increased from 4px */
            padding: 20px 24px; /* Increased from 12px 24px */
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            transition: all 0.2s ease; 
            position: relative; 
            gap: 20px;
            border-left: 4px solid transparent;
        }
        .buyer-view .booking-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            z-index: 10;
        }
        
        /* Status Indicator Left Border */
        .buyer-view .booking-card.status-pending { border-left-color: #f59e0b; }
        .buyer-view .booking-card.status-approved { border-left-color: #10b981; }
        .buyer-view .booking-card.status-completed { border-left-color: #2563eb; }
        .buyer-view .booking-card.status-cancelled { border-left-color: #ef4444; }
        .buyer-view .booking-card.status-rejected { border-left-color: #64748b; }

        .buyer-view .expert-snapshot { display: flex; align-items: center; gap: 12px; width: 180px; flex-shrink: 0; }
        .buyer-view .expert-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 1px #e2e8f0; }
        
        .buyer-view .booking-info-main { display: flex; flex-direction: column; flex-grow: 1; min-width: 0; padding-left: 10px; }
        .buyer-view .vehicle-title { font-weight: 700; color: #334155; font-size: 0.95rem; letter-spacing: -0.01em; margin-bottom: 8px; }
        
        .buyer-view .booking-meta-row { display: flex; gap: 12px; margin-top: 4px; flex-wrap: wrap; }
        .buyer-view .meta-pill { 
            font-size: 0.7rem; 
            color: #475569; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
            background: #f8fafc; 
            padding: 4px 10px; 
            border-radius: 6px; 
            border: 1px solid #e2e8f0;
        }
        
        .buyer-view .status-and-actions { display: flex; align-items: center; gap: 20px; }
        .buyer-view .action-btns { display: flex; gap: 10px; }
        
        /* Action Buttons as Tactile Labels */
        .buyer-view .action-btn { 
            padding: 8px 16px; 
            font-size: 0.7rem; 
            border-radius: 6px; 
            font-weight: 700; 
            text-decoration: none; 
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transform: none;
        }
        .buyer-view .action-btn:nth-child(even) { transform: none; }
        .buyer-view .action-btn:hover { 
            background: #eff6ff; 
            border-color: #2563eb; 
            color: #2563eb; 
            transform: translateY(-1px); 
            z-index: 5;
        }
        .buyer-view .action-btn.btn-primary { 
            background: #2563eb; 
            color: white; 
            border-color: #2563eb; 
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }

        /* Filter Tabs as Folder Tabs */
        .buyer-view .filter-area { display: flex; align-items: flex-end; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 1px; margin-top: 20px; }
        .buyer-view .filter-tabs { display: flex; gap: 4px; }
        .buyer-view .filter-tab { 
            padding: 8px 20px; 
            background: #e2e8f0; 
            color: #64748b; 
            border-radius: 8px 8px 0 0; 
            font-size: 0.75rem; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.2s;
            position: relative;
            bottom: -2px;
            border: 2px solid transparent;
            border-bottom: none;
        }
        .buyer-view .filter-tab:hover { background: #cbd5e1; }
        .buyer-view .filter-tab.active { 
            background: white; 
            color: #1e293b; 
            border-color: #e2e8f0; 
            border-bottom-color: white;
            z-index: 2;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">

        <?php if ($role == 'expert'): ?>
            <?php
            // Fetch Expert Data
            $expert_res = $conn->query("SELECT verified, name, experience, is_available, profile_photo, bio, linkedin_url, website_url FROM expert WHERE expert_id = $user_id");
            $expert = ($expert_res) ? $expert_res->fetch_assoc() : null;
            if (!$expert) { echo "<div class='alert-error'>Expert account not found.</div>"; exit; }
            
            // Statistics
            $stats_pending = $conn->query("SELECT COUNT(*) as c FROM booking WHERE expert_id = $user_id AND status = 'pending'")->fetch_assoc()['c'];
            $stats_upcoming = $conn->query("SELECT COUNT(*) as c FROM booking WHERE expert_id = $user_id AND status = 'approved'")->fetch_assoc()['c'];
            $stats_completed = $conn->query("SELECT COUNT(*) as c FROM booking WHERE expert_id = $user_id AND status = 'completed'")->fetch_assoc()['c'];
            ?>

            <!-- EXPERT DASHBOARD -->
            <div class="mobile-stack" style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px;">
                <div class="avatar" style="width: 60px; height: 60px; font-size: 1.5rem; background: #dbeafe; color: #1e40af; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <?php if (!empty($expert['profile_photo'])): ?>
                        <img src="uploads/profiles/<?php echo $expert['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="ph ph-user-circle"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 style="margin: 0;">Welcome Back, <?php echo htmlspecialchars($expert['name']); ?>!</h2>
                    <p style="margin: 5px 0; color: #64748b; display: flex; align-items: center; gap: 6px;">
                        <?php echo $expert['verified'] ? '<i class="ph-fill ph-seal-check" style="color: #16a34a; font-size: 1.1rem;"></i> Verified Expert' : '<i class="ph ph-hourglass" style="color: #ca8a04;"></i> Waiting for Verification'; ?> 
                        • <?php echo $expert['experience']; ?> Years Experience
                    </p>
                    
                    <div style="display: flex; gap: 15px; margin-top: 10px; align-items: center; flex-wrap: wrap;">
                        <?php if (!empty($expert['linkedin_url'])): ?>
                            <a href="<?php echo htmlspecialchars($expert['linkedin_url']); ?>" target="_blank" style="color: #0077b5; font-size: 0.85rem; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                <i class="ph ph-linkedin-logo"></i>
                                LinkedIn
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($expert['website_url'])): ?>
                            <a href="<?php echo htmlspecialchars($expert['website_url']); ?>" target="_blank" style="color: #64748b; font-size: 0.85rem; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                <i class="ph ph-globe"></i>
                                Website
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($expert['bio'])): ?>
                        <div style="margin-top: 15px; font-size: 0.9rem; color: #475569; background: #f8fafc; padding: 10px 15px; border-radius: 8px; border-left: 3px solid #cbd5e1; max-width: 600px;">
                            <?php echo nl2br(htmlspecialchars($expert['bio'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            // Display success message if report was just submitted or booking cancelled
            if (isset($_GET['msg'])) {
                if ($_GET['msg'] == 'report_created') {
                    echo '<div style="background: #dcfce7; border-left: 4px solid #22c55e; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
                    echo '<h3 style="margin: 0 0 10px 0; color: #15803d; display: flex; align-items: center; gap: 8px;"><i class="ph-fill ph-check-circle"></i> Inspection Report Submitted Successfully!</h3>';
                    echo '<p style="margin: 0; color: #166534;">Your inspection report has been saved and the booking is now marked as completed. The buyer can now view the report and proceed with payment.</p>';
                    echo '</div>';
                } elseif ($_GET['msg'] == 'cancelled') {
                    echo '<div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
                    echo '<h3 style="margin: 0 0 10px 0; color: #991b1b; display: flex; align-items: center; gap: 8px;"><i class="ph-fill ph-x-circle"></i> Booking Cancelled</h3>';
                    echo '<p style="margin: 0; color: #7f1d1d;">The booking has been successfully cancelled.</p>';
                    echo '</div>';
                } elseif ($_GET['msg'] == 'reply_saved') {
                    echo '<div class="alert-success"><i class="ph-fill ph-check-circle"></i> Your reply has been posted successfully.</div>';
                } elseif ($_GET['msg'] == 'empty_reply') {
                    echo '<div class="alert-error"><i class="ph-fill ph-warning-circle"></i> Please enter a reply before saving.</div>';
                }
            }
            ?>

            <div class="main-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                
                <!-- Left Column -->
                <div class="left-col">
                    
                    <!-- Quick Status -->
                    <h3 class="section-title">Quick Status</h3>
                    <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 30px;">
                        <div class="card stat-card">
                            <span class="label"><i class="ph ph-hourglass"></i> Pending Requests</span>
                            <span class="value"><?php echo $stats_pending; ?></span>
                        </div>
                        <div class="card stat-card">
                            <span class="label"><i class="ph ph-calendar-check"></i> Upcoming Inspections</span>
                            <span class="value"><?php echo $stats_upcoming; ?></span>
                        </div>
                        <div class="card stat-card">
                            <span class="label"><i class="ph ph-check-square-offset"></i> Total Completed</span>
                            <span class="value"><?php echo $stats_completed; ?></span>
                        </div>
                    </div>

                    <!-- New Requests -->
                    <h3 class="section-title">New Requests</h3>
                    <div class="card">
                        <?php
                        // Fetch Pending Bookings
                        $sql = "SELECT b.*, u.name as buyer_name, u.phone as buyer_phone, u.location, u.email as buyer_email, u.profile_photo as buyer_photo 
                                FROM booking b 
                                JOIN buyer u ON b.buyer_id = u.buyer_id 
                                WHERE b.expert_id = $user_id AND b.status = 'pending' 
                                ORDER BY b.booking_date ASC";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="request-item">
                                    <div class="user-info">
                                        <div class="avatar" style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                            <?php if (!empty($row['buyer_photo'])): ?>
                                                <img src="uploads/profiles/<?php echo $row['buyer_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="ph ph-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="info-text">
                                            <h4><?php echo htmlspecialchars($row['buyer_name']); ?></h4>
                                            <p><strong><?php echo htmlspecialchars($row['vehicle_type']); ?></strong> • <?php echo date('M j, Y', strtotime($row['booking_date'])); ?></p>
                                            <p style="font-size: 0.85rem; color: #475569; margin: 4px 0;">
                                                <i class="ph ph-package"></i> <?php echo htmlspecialchars($row['package_name'] ?? 'Standard'); ?> (LKR <?php echo number_format($row['package_price'] ?? 5000, 2); ?>) • 
                                                <i class="ph ph-globe"></i> <?php echo htmlspecialchars($row['service_type'] ?? 'Physical'); ?>
                                            </p>
                                            <p style="font-size: 0.75rem; color: #94a3b8;"><i class="ph ph-map-pin"></i> <?php echo htmlspecialchars($row['location']); ?> • <i class="ph ph-phone"></i> <?php echo htmlspecialchars($row['buyer_phone']); ?></p>
                                        </div>
                                    </div>
                                    <div class="actions" style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <a href="messages.php?chat_role=buyer&chat_id=<?php echo $row['buyer_id']; ?>" class="btn btn-outline btn-sm"><i class="ph ph-chat-circle-text"></i></a>
                                        <a href="update_booking.php?id=<?php echo $row['booking_id']; ?>&status=approved" class="btn btn-primary btn-sm">Accept</a>
                                        <a href="update_booking.php?id=<?php echo $row['booking_id']; ?>&status=rejected" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: #fee2e2;">Reject</a>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p style='color: #94a3b8; text-align: center; padding: 20px;'>No new requests at the moment.</p>";
                        }
                        ?>
                    </div>

                    <!-- Upcoming Inspections -->
                    <h3 class="section-title" style="margin-top: 30px;">Upcoming Inspections</h3>
                    <div class="card">
                        <?php
                        $sql = "SELECT b.*, u.name as buyer_name, u.phone, u.location, u.profile_photo as buyer_photo, ir.report_id 
                                FROM booking b 
                                JOIN buyer u ON b.buyer_id = u.buyer_id 
                                LEFT JOIN inspection_report ir ON b.booking_id = ir.booking_id
                                WHERE b.expert_id = $user_id AND (b.status = 'approved' OR b.status = 'in_progress') 
                                ORDER BY b.booking_date ASC";
                        $result = $conn->query($sql);

                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="request-item">
                                    <div class="user-info">
                                        <div class="avatar" style="background:#dcfce7; font-size: 1rem; overflow: hidden;">
                                            <?php if (!empty($row['buyer_photo'])): ?>
                                                <img src="uploads/profiles/<?php echo $row['buyer_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="ph ph-calendar-blank" style="color: #166534;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="info-text">
                                            <h4>#<?php echo $row['booking_id']; ?> - <?php echo htmlspecialchars($row['buyer_name']); ?></h4>
                                            <p><strong><?php echo htmlspecialchars($row['vehicle_type']); ?></strong> • <?php echo date('M j, Y', strtotime($row['booking_date'])); ?></p>
                                            <p style="font-size: 0.85rem; color: #475569; margin: 4px 0;">
                                                <i class="ph ph-package"></i> <?php echo htmlspecialchars($row['package_name'] ?? 'Standard'); ?> (LKR <?php echo number_format($row['package_price'] ?? 5000, 2); ?>) • 
                                                <i class="ph ph-globe"></i> <?php echo htmlspecialchars($row['service_type'] ?? 'Physical'); ?>
                                            </p>
                                            <p style="font-size: 0.75rem; color: #64748b;"><i class="ph ph-phone"></i> <?php echo htmlspecialchars($row['phone']); ?> • <i class="ph ph-map-pin"></i> <?php echo htmlspecialchars($row['location']); ?></p>
                                            <p style="font-size: 0.75rem;">
                                                <?php 
                                                if ($row['status'] == 'approved') echo '<span class="status-badge status-approved">Approved</span>';
                                                elseif ($row['status'] == 'in_progress') echo '<span class="status-badge status-in_progress">In Progress</span>';
                                                elseif ($row['status'] == 'completed') echo '<span class="status-badge status-completed">Completed</span>';
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="actions" style="display: flex; gap: 5px;">
                                        <?php if ($row['status'] == 'approved' || $row['status'] == 'in_progress'): ?>
                                            <a href="upload_report.php?id=<?php echo $row['booking_id']; ?>" class="btn btn-primary btn-sm">Start Inspection</a>
                                            <a href="cancel_booking_form.php?id=<?php echo $row['booking_id']; ?>" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: #fee2e2;">Cancel</a>
                                        <?php endif; ?>
                                        <?php if ($row['status'] == 'completed' && !empty($row['report_id'])): ?>
                                            <a href="view_report.php?id=<?php echo $row['report_id']; ?>" target="_blank" class="btn btn-primary btn-sm">View Report</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p style='color: #94a3b8; text-align: center; padding: 20px;'>No upcoming inspections.</p>";
                        }
                        ?>
                    </div>

                    <!-- Completed Inspections (Collapsible) -->
                    <h3 class="section-title" style="margin-top: 30px; cursor: pointer; user-select: none;" onclick="toggleCompleted()">
                        <i class="ph ph-clipboard-text"></i> Completed Inspections <span id="toggleIcon" style="float: right;"><i class="ph ph-caret-down"></i></span>
                    </h3>
                    <div class="card" id="completedSection" style="display: none;">
                        <?php
                        $sql_completed = "SELECT b.*, u.name as buyer_name, u.phone, ir.report_id 
                                FROM booking b 
                                JOIN buyer u ON b.buyer_id = u.buyer_id 
                                LEFT JOIN inspection_report ir ON b.booking_id = ir.booking_id
                                WHERE b.expert_id = $user_id AND b.status = 'completed' 
                                ORDER BY b.booking_date DESC";
                        $result_completed = $conn->query($sql_completed);

                        if ($result_completed->num_rows > 0) {
                            while($row = $result_completed->fetch_assoc()) {
                                ?>
                                <div class="request-item">
                                    <div class="user-info">
                                        <div class="avatar" style="background:#dcfce7; color:#166534; display: flex; align-items: center; justify-content: center;"><i class="ph-fill ph-check-circle"></i></div>
                                        <div class="info-text">
                                            <h4><?php echo htmlspecialchars($row['buyer_name']); ?></h4>
                                            <p><?php echo htmlspecialchars($row['vehicle_type']); ?> • <?php echo $row['booking_date']; ?></p>
                                            <p style="font-size: 0.85rem; color: #475569; margin: 4px 0;">
                                                <i class="ph ph-package"></i> <?php echo htmlspecialchars($row['package_name'] ?? 'Standard'); ?> • <i class="ph ph-globe"></i> <?php echo htmlspecialchars($row['service_type'] ?? 'Physical'); ?>
                                            </p>
                                            <p style="font-size: 0.75rem;"><span class="status-badge status-completed">Completed</span></p>
                                        </div>
                                    </div>
                                    <div class="actions">
                                        <?php if (!empty($row['report_id'])): ?>
                                            <a href="view_report.php?id=<?php echo $row['report_id']; ?>" target="_blank" class="btn btn-primary btn-sm">View Report</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p style='color: #94a3b8; text-align: center; padding: 20px;'>No completed inspections yet.</p>";
                        }
                        ?>
                    </div>
                    
                    <!-- Cancelled Bookings (Collapsible) -->
                    <h3 class="section-title" style="margin-top: 30px; cursor: pointer; user-select: none;" onclick="toggleCancelled()">
                        <i class="ph ph-prohibit"></i> Cancelled Bookings <span id="cancelledToggleIcon" style="float: right;"><i class="ph ph-caret-down"></i></span>
                    </h3>
                    <div class="card" id="cancelledSection" style="display: none;">
                        <?php
                        $sql_cancelled = "SELECT b.*, u.name as buyer_name, u.phone 
                                FROM booking b 
                                JOIN buyer u ON b.buyer_id = u.buyer_id 
                                WHERE b.expert_id = $user_id AND b.status = 'cancelled' 
                                ORDER BY b.booking_date DESC";
                        $result_cancelled = $conn->query($sql_cancelled);

                        if ($result_cancelled->num_rows > 0) {
                            while($row = $result_cancelled->fetch_assoc()) {
                                ?>
                                <div class="request-item" style="border-left: 4px solid #ef4444;">
                                    <div class="user-info">
                                        <div class="avatar" style="background:#fee2e2; color:#991b1b; display: flex; align-items: center; justify-content: center;"><i class="ph-fill ph-x-circle"></i></div>
                                        <div class="info-text">
                                            <h4><?php echo htmlspecialchars($row['buyer_name']); ?></h4>
                                            <p><?php echo htmlspecialchars($row['vehicle_type']); ?> • <?php echo $row['booking_date']; ?></p>
                                            <?php if (!empty($row['cancellation_reason'])): ?>
                                                <p style="font-size: 0.85rem; color: #991b1b; background: #fff5f5; padding: 5px 10px; border-radius: 4px; margin-top: 5px;">
                                                    <strong>Reason:</strong> <?php echo htmlspecialchars($row['cancellation_reason']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p style='color: #94a3b8; text-align: center; padding: 20px;'>No cancelled bookings.</p>";
                        }
                        ?>
                    </div>

                    <script>
                    function toggleCompleted() {
                        var section = document.getElementById('completedSection');
                        var icon = document.getElementById('toggleIcon');
                        if (section.style.display === 'none') {
                            section.style.display = 'block';
                            icon.innerHTML = '<i class="ph ph-caret-up"></i>';
                        } else {
                            section.style.display = 'none';
                            icon.innerHTML = '<i class="ph ph-caret-down"></i>';
                        }
                    }
                    function toggleCancelled() {
                        var section = document.getElementById('cancelledSection');
                        var icon = document.getElementById('cancelledToggleIcon');
                        if (section.style.display === 'none') {
                            section.style.display = 'block';
                            icon.innerHTML = '<i class="ph ph-caret-up"></i>';
                        } else {
                            section.style.display = 'none';
                            icon.innerHTML = '<i class="ph ph-caret-down"></i>';
                        }
                    }
                    </script>
                </div>

                <!-- Right Column -->
                <div class="right-col">
                    <h3 class="section-title">Shortcuts</h3>
                    <div class="shortcut-grid">
                        <a href="expert_profile.php?id=<?php echo $user_id; ?>" class="shortcut-btn"><i class="ph ph-user"></i> View Profile</a>
                        
                        <!-- Toggle Availability -->
                        <a href="dashboard.php?action=toggle_availability" class="shortcut-btn" 
                           style="<?php echo $expert['is_available'] ? 'background: #dcfce7; color: #15803d;' : 'background: #f1f5f9; color: #64748b;'; ?>">
                           <?php echo $expert['is_available'] ? '<i class="ph-fill ph-toggle-right"></i> Availability: ON' : '<i class="ph ph-toggle-left"></i> Availability: OFF'; ?>
                        </a>

                        <a href="earnings.php" class="shortcut-btn"><i class="ph ph-currency-dollar"></i> View Earnings</a>
                        <a href="messages.php" class="shortcut-btn"><i class="ph ph-chat-circle-text"></i> Messages</a>
                    </div>

                    <h3 class="section-title" style="margin-top: 30px;">Customer Reviews</h3>
                    <div class="card">
                        <?php 
                        $rev_sql = "SELECT r.*, b.name, b.profile_photo FROM review r JOIN buyer b ON r.buyer_id = b.buyer_id WHERE r.expert_id = $user_id ORDER BY r.review_date DESC";
                        $reviews = $conn->query($rev_sql);
                        
                        if ($reviews && $reviews->num_rows > 0) {
                            while($rev = $reviews->fetch_assoc()) {
                                ?>
                                <div style="margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                                    <div style="display:flex; justify-content:space-between; align-items: center; margin-bottom:8px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 24px; height: 24px; border-radius: 50%; background: #fef3c7; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #92400e; overflow: hidden;">
                                                <?php if (!empty($rev['profile_photo'])): ?>
                                                    <img src="uploads/profiles/<?php echo $rev['profile_photo']; ?>" style="width:100%; height:100%; object-fit:cover;">
                                                <?php else: ?>
                                                    <i class="ph ph-user"></i>
                                                <?php endif; ?>
                                            </div>
                                            <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($rev['name']); ?></strong>
                                        </div>
                                        <span style="color: #f59e0b; font-weight: 600; font-size: 0.9rem;"><i class="ph-fill ph-star"></i> <?php echo $rev['rating']; ?>.0</span>
                                    </div>
                                    <p style="font-size: 0.85rem; color: #475569; margin: 0 0 10px 0; line-height: 1.5;"><?php echo htmlspecialchars($rev['comment']); ?></p>
                                    
                                    <?php if (!empty($rev['expert_reply'])): ?>
                                        <div style="background: #f8fafc; border-left: 3px solid #3b82f6; padding: 10px 15px; margin-top: 10px; border-radius: 4px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                <small style="font-weight: 600; color: #1e3a8a;">Your Response</small>
                                                <small style="color: #94a3b8;"><?php echo date('M j, Y', strtotime($rev['reply_date'])); ?></small>
                                            </div>
                                            <p style="font-size: 0.85rem; color: #334155; margin: 0; italic;"><?php echo htmlspecialchars($rev['expert_reply']); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn btn-sm" style="background: #f1f5f9; color: #475569; font-size: 0.75rem; padding: 4px 10px;" onclick="toggleReplyForm(<?php echo $rev['review_id']; ?>)">Reply</button>
                                        
                                        <form id="reply-form-<?php echo $rev['review_id']; ?>" action="reply_review.php" method="POST" style="display: none; margin-top: 10px;">
                                            <input type="hidden" name="review_id" value="<?php echo $rev['review_id']; ?>">
                                            <textarea name="expert_reply" placeholder="Write your response/correction..." required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; min-height: 60px; margin-bottom: 8px;"></textarea>
                                            <div style="display: flex; gap: 5px;">
                                                <button type="submit" class="btn btn-primary btn-sm" style="font-size: 0.75rem;">Post Reply</button>
                                                <button type="button" class="btn btn-outline btn-sm" style="font-size: 0.75rem;" onclick="toggleReplyForm(<?php echo $rev['review_id']; ?>)">Cancel</button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                        } else {
                            echo "<p style='color: #94a3b8; font-size: 0.9rem; text-align: center; padding: 10px;'>No reviews yet.</p>";
                        }
                        ?>
                    </div>

                    <script>
                    function toggleReplyForm(id) {
                        const form = document.getElementById('reply-form-' + id);
                        form.style.display = (form.style.display === 'none') ? 'block' : 'none';
                    }
                    </script>
                </div>

            </div>

        <?php elseif ($role == 'buyer'): ?>
            <!-- BUYER DASHBOARD -->
            <div class="buyer-view">
<?php
            $buyer_res = $conn->query("SELECT name, profile_photo, bio FROM buyer WHERE buyer_id = $user_id");
            $buyer = ($buyer_res) ? $buyer_res->fetch_assoc() : null;
            if (!$buyer) { echo "<div class='alert-error'>Buyer account not found.</div>"; exit; }
            ?>
            <div class="mobile-stack" style="display: flex; gap: 20px; align-items: center; margin-bottom: 30px;">
                <div class="avatar" style="width: 60px; height: 60px; font-size: 1.5rem; background: #fef3c7; color: #92400e; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <?php if (!empty($buyer['profile_photo'])): ?>
                        <img src="uploads/profiles/<?php echo $buyer['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="ph ph-user-circle"></i>
                    <?php endif; ?>
                </div>
                <div>
                   <h2 style="margin: 0;">My Bookings</h2>
                   <p style="margin: 5px 0; color: #64748b;">Manage your vehicle inspection requests</p>
                   <?php if (!empty($buyer['bio'])): ?>
                       <p style="margin: 5px 0; font-size: 0.85rem; color: #475569; font-style: italic;">
                           "<?php echo htmlspecialchars($buyer['bio']); ?>"
                       </p>
                   <?php endif; ?>
                </div>
            </div>
            
            <div class="filter-area">
                <div class="filter-tabs">
                    <div class="filter-tab active" data-filter="all">All Files</div>
                    <div class="filter-tab" data-filter="pending">Pending</div>
                    <div class="filter-tab" data-filter="approved">Approved</div>
                    <div class="filter-tab" data-filter="in_progress">Active</div>
                    <div class="filter-tab" data-filter="completed">Completed</div>
                    <div class="filter-tab" data-filter="cancelled">Cancelled</div>
                </div>
            </div>

            <div class="booking-list" id="bookingGrid">
                <?php
                $user_id = $_SESSION['user_id'];
                $sql = "SELECT b.*, e.name as expert_name, e.phone as expert_phone, e.expert_id, e.profile_photo as expert_photo, b.booking_id, r.review_id, ir.report_id, p.payment_id 
                        FROM booking b 
                        JOIN expert e ON b.expert_id = e.expert_id 
                        LEFT JOIN review r ON b.booking_id = r.booking_id 
                        LEFT JOIN inspection_report ir ON b.booking_id = ir.booking_id 
                        LEFT JOIN payment p ON b.booking_id = p.booking_id 
                        WHERE b.buyer_id = $user_id 
                        ORDER BY b.booking_date DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $status = strtolower($row['status']);
                        $cls = "status-" . $status;
                        ?>
                        <div class="booking-card <?php echo $cls; ?>">
                            <div class="expert-snapshot">
                                <?php if (!empty($row['expert_photo'])): ?>
                                    <img src="uploads/profiles/<?php echo $row['expert_photo']; ?>" class="expert-img">
                                <?php else: ?>
                                    <div class="expert-img"><?php echo strtoupper(substr($row['expert_name'], 0, 1)); ?></div>
                                <?php endif; ?>
                                <div>
                                    <h4 style="margin: 0; font-size: 0.9rem;"><?php echo htmlspecialchars($row['expert_name']); ?></h4>
                                    <p style="margin: 0; font-size: 0.75rem; color: #64748b;">#<?php echo $row['booking_id']; ?></p>
                                </div>
                            </div>

                            <div class="booking-info-main">
                                <div class="vehicle-title">
                                    <i class="ph ph-car"></i> <?php echo htmlspecialchars($row['vehicle_type']); ?>
                                </div>
                                <div class="booking-meta-row">
                                    <div class="meta-pill"><i class="ph ph-calendar-blank"></i> <strong><?php echo date('M j, Y', strtotime($row['booking_date'])); ?></strong></div>
                                    <div class="meta-pill"><i class="ph ph-package"></i> <strong><?php echo htmlspecialchars($row['package_name'] ?? 'Standard'); ?></strong></div>
                                    <div class="meta-pill"><i class="ph ph-globe"></i> <strong><?php echo htmlspecialchars($row['service_type'] ?? 'Physical'); ?></strong></div>
                                    <div class="meta-pill" style="color: #2563eb;"><i class="ph ph-money"></i> <strong>LKR <?php echo number_format($row['package_price'] ?? 5000, 2); ?></strong></div>
                                </div>
                                <?php if (!empty($row['cancellation_reason'])): ?>
                                    <div style="font-size: 0.7rem; color: #991b1b; margin-top: 5px;">
                                        Reason: <?php echo htmlspecialchars($row['cancellation_reason']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="status-and-actions">
                                <div class="action-btns">
                                    <a href="messages.php?chat_role=expert&chat_id=<?php echo $row['expert_id']; ?>" class="action-btn" title="Message">Chat</a>
                                    <?php if (!empty($row['report_id'])): ?>
                                        <a href="view_report.php?id=<?php echo $row['report_id']; ?>" target="_blank" class="action-btn btn-primary" title="Report">Report</a>
                                    <?php endif; ?>
                                    
                                    <?php if (($status == 'approved' || $status == 'completed') && empty($row['payment_id'])): ?>
                                        <a href="pay.php?id=<?php echo $row['booking_id']; ?>" class="action-btn" style="background: #22c55e; color: white; border: none;">Pay</a>
                                    <?php endif; ?>

                                    <?php if ($status == 'completed' && empty($row['review_id'])): ?>
                                        <a href="review_expert.php?id=<?php echo $row['booking_id']; ?>" class="action-btn" style="border-color: #f59e0b; color: #b45309;" title="Review Expert">Review Expert</a>
                                    <?php endif; ?>
                                </div>
                                <?php if (in_array($status, ['pending', 'approved', 'in_progress'])): ?>
                                    <a href="cancel_booking_form.php?id=<?php echo $row['booking_id']; ?>" style="color: #ef4444; font-size: 0.65rem; text-decoration: none; font-weight: 500;">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo "<div class='card' style='text-align: center; padding: 40px; color: #94a3b8;'>No bookings found. <br><br> <a href='experts.php' class='btn btn-primary'>Find an Expert</a></div>";
                }
                ?>
            </div>
            </div>

        <?php elseif ($role == 'admin'): ?>
            <!-- ADMIN DASHBOARD -->
            <?php
            // Fetch Platform Statistics
            $total_experts = $conn->query("SELECT COUNT(*) as c FROM expert WHERE verified = 1")->fetch_assoc()['c'];
            $total_buyers = $conn->query("SELECT COUNT(*) as c FROM buyer")->fetch_assoc()['c'];
            $total_bookings = $conn->query("SELECT COUNT(*) as c FROM booking")->fetch_assoc()['c'];
            $pending_approvals = $conn->query("SELECT COUNT(*) as c FROM expert WHERE verified = 0")->fetch_assoc()['c'];
            $active_inspections = $conn->query("SELECT COUNT(*) as c FROM booking WHERE status IN ('approved', 'in_progress')")->fetch_assoc()['c'];
            $revenue_month_result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payment WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
            $revenue_month = $revenue_month_result ? $revenue_month_result->fetch_assoc()['total'] : 0;
            
            // Get current tab
            $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
            ?>
            
            <h2>Admin Dashboard</h2>
            
            <!-- Statistics Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; text-align: center; border: none;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;"><?php echo $total_experts; ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 500;">Total Experts</div>
                </div>
                <div class="card" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; padding: 25px; text-align: center; border: none;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;"><?php echo $total_buyers; ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 500;">Total Buyers</div>
                </div>
                <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 25px; text-align: center; border: none;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;"><?php echo $total_bookings; ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 500;">Total Bookings</div>
                </div>
                <div class="card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 25px; text-align: center; border: none;">
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 8px;">LKR <?php echo number_format($revenue_month, 0); ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 500;">Revenue (Month)</div>
                </div>
                <div class="card" style="background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%); color: white; padding: 25px; text-align: center; border: none;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;"><?php echo $pending_approvals; ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 500;">Pending Approvals</div>
                </div>
                <div class="card" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); color: white; padding: 25px; text-align: center; border: none;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;"><?php echo $active_inspections; ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 500;">Active Inspections</div>
                </div>
            </div>
            
            <!-- Tab Navigation -->
            <div style="border-bottom: 2px solid #e2e8f0; margin-bottom: 30px;">
                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                    <a href="?tab=dashboard" class="<?php echo $current_tab == 'dashboard' ? 'active' : ''; ?>" style="padding: 12px 24px; text-decoration: none; color: <?php echo $current_tab == 'dashboard' ? '#2563eb' : '#64748b'; ?>; font-weight: 600; border-bottom: 3px solid <?php echo $current_tab == 'dashboard' ? '#2563eb' : 'transparent'; ?>; transition: all 0.2s;"><i class="ph ph-gauge"></i> Dashboard</a>
                    <a href="?tab=experts" class="<?php echo $current_tab == 'experts' ? 'active' : ''; ?>" style="padding: 12px 24px; text-decoration: none; color: <?php echo $current_tab == 'experts' ? '#2563eb' : '#64748b'; ?>; font-weight: 600; border-bottom: 3px solid <?php echo $current_tab == 'experts' ? '#2563eb' : 'transparent'; ?>; transition: all 0.2s;"><i class="ph ph-users-three"></i> Experts</a>
                    <a href="?tab=buyers" class="<?php echo $current_tab == 'buyers' ? 'active' : ''; ?>" style="padding: 12px 24px; text-decoration: none; color: <?php echo $current_tab == 'buyers' ? '#2563eb' : '#64748b'; ?>; font-weight: 600; border-bottom: 3px solid <?php echo $current_tab == 'buyers' ? '#2563eb' : 'transparent'; ?>; transition: all 0.2s;"><i class="ph ph-users"></i> Buyers</a>
                    <a href="?tab=bookings" class="<?php echo $current_tab == 'bookings' ? 'active' : ''; ?>" style="padding: 12px 24px; text-decoration: none; color: <?php echo $current_tab == 'bookings' ? '#2563eb' : '#64748b'; ?>; font-weight: 600; border-bottom: 3px solid <?php echo $current_tab == 'bookings' ? '#2563eb' : 'transparent'; ?>; transition: all 0.2s;"><i class="ph ph-calendar-check"></i> Bookings</a>
                    <a href="?tab=payments" class="<?php echo $current_tab == 'payments' ? 'active' : ''; ?>" style="padding: 12px 24px; text-decoration: none; color: <?php echo $current_tab == 'payments' ? '#2563eb' : '#64748b'; ?>; font-weight: 600; border-bottom: 3px solid <?php echo $current_tab == 'payments' ? '#2563eb' : 'transparent'; ?>; transition: all 0.2s;"><i class="ph ph-money"></i> Payments</a>
                    <a href="?tab=reviews" class="<?php echo $current_tab == 'reviews' ? 'active' : ''; ?>" style="padding: 12px 24px; text-decoration: none; color: <?php echo $current_tab == 'reviews' ? '#2563eb' : '#64748b'; ?>; font-weight: 600; border-bottom: 3px solid <?php echo $current_tab == 'reviews' ? '#2563eb' : 'transparent'; ?>; transition: all 0.2s;"><i class="ph ph-star"></i> Reviews</a>
                </div>
            </div>
            
            <?php if ($current_tab == 'dashboard'): ?>
                <!-- Dashboard Overview -->
                <div class="card" style="margin-bottom: 30px;">
                    <h3 style="margin: 0 0 20px 0;">Pending Expert Approvals</h3>
                    <table class="responsive-table-card" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                                <th style="padding: 12px;">Photo</th>
                                <th style="padding: 12px;">Name</th>
                                <th style="padding: 12px;">Contact</th>
                                <th style="padding: 12px;">Service Areas</th>
                                <th style="padding: 12px;">Experience</th>
                                <th style="padding: 12px;">Qualification</th>
                                <th style="padding: 12px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM expert WHERE verified = 0 ORDER BY expert_id DESC";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td data-label="Photo" style="padding: 12px;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                                <?php if (!empty($row['profile_photo'])): ?>
                                                    <img src="uploads/profiles/<?php echo $row['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <span style="font-size: 1.2rem;"><?php echo strtoupper(substr($row['name'], 0, 1)); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Name" style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td data-label="Contact" style="padding: 12px; font-size: 0.85rem;">
                                            <div style="margin-bottom: 4px;">📞 <?php echo htmlspecialchars($row['phone']); ?></div>
                                            <div style="margin-bottom: 4px; color: #64748b;">✉️ <?php echo htmlspecialchars($row['email']); ?></div>
                                            <?php if (!empty($row['linkedin_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['linkedin_url']); ?>" target="_blank" style="color: #0077b5; font-size: 0.8rem; text-decoration: none;"><i class="ph ph-linkedin-logo"></i> LinkedIn</a>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Service Areas" style="padding: 12px;"><?php echo htmlspecialchars($row['district']); ?></td>
                                        <td data-label="Experience" style="padding: 12px;"><?php echo $row['experience']; ?> Years</td>
                                        <td data-label="Qualification" style="padding: 12px; font-size: 0.85rem; color: #64748b;"><?php echo htmlspecialchars(substr($row['qualification'], 0, 50)) . '...'; ?></td>
                                        <td data-label="Action" style="padding: 12px;">
                                            <div style="display: flex; gap: 5px; flex-direction: column;">
                                                <button onclick="viewExpertDetails(<?php echo $row['expert_id']; ?>)" class="btn btn-outline btn-sm" style="font-size: 0.75rem;">View Details</button>
                                                <a href="admin_approve.php?id=<?php echo $row['expert_id']; ?>" class="btn btn-primary btn-sm" style="font-size: 0.75rem;">Approve</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='7' style='padding: 40px; text-align: center; color: #94a3b8;'>No pending approvals.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Recent Bookings -->
                <div class="card">
                    <h3 style="margin: 0 0 20px 0;">Recent Bookings</h3>
                    <table class="responsive-table-card" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                                <th style="padding: 12px;">ID</th>
                                <th style="padding: 12px;">Buyer</th>
                                <th style="padding: 12px;">Expert</th>
                                <th style="padding: 12px;">Vehicle</th>
                                <th style="padding: 12px;">Date</th>
                                <th style="padding: 12px;">Status</th>
                                <th style="padding: 12px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT b.*, buyer.name as buyer_name, expert.name as expert_name 
                                    FROM booking b 
                                    JOIN buyer ON b.buyer_id = buyer.buyer_id 
                                    JOIN expert ON b.expert_id = expert.expert_id 
                                    ORDER BY b.booking_date DESC LIMIT 10";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $status_colors = [
                                        'pending' => '#f59e0b',
                                        'approved' => '#10b981',
                                        'in_progress' => '#3b82f6',
                                        'completed' => '#2563eb',
                                        'cancelled' => '#ef4444',
                                        'rejected' => '#64748b'
                                    ];
                                    $color = $status_colors[$row['status']] ?? '#64748b';
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td data-label="ID" style="padding: 12px; font-weight: 600;">#<?php echo $row['booking_id']; ?></td>
                                        <td data-label="Buyer" style="padding: 12px;"><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                                        <td data-label="Expert" style="padding: 12px;"><?php echo htmlspecialchars($row['expert_name']); ?></td>
                                        <td data-label="Vehicle" style="padding: 12px; font-size: 0.9rem;"><?php echo htmlspecialchars($row['vehicle_type']); ?></td>
                                        <td data-label="Date" style="padding: 12px; font-size: 0.85rem; color: #64748b;"><?php echo date('M j, Y', strtotime($row['booking_date'])); ?></td>
                                        <td data-label="Status" style="padding: 12px;">
                                            <span style="background: <?php echo $color; ?>22; color: <?php echo $color; ?>; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td data-label="Amount" style="padding: 12px; font-weight: 600;">LKR <?php echo number_format($row['package_price'] ?? 0, 2); ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='7' style='padding: 40px; text-align: center; color: #94a3b8;'>No bookings found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($current_tab == 'experts'): ?>
                <!-- Expert Management -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0;">All Experts</h3>
                        <input type="text" id="expertSearch" placeholder="Search experts..." style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; width: 300px; max-width: 100%;">
                    </div>
                    
                    <table class="responsive-table-card" style="width: 100%; border-collapse: collapse;" id="expertsTable">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                                <th style="padding: 12px;">Photo</th>
                                <th style="padding: 12px;">Name</th>
                                <th style="padding: 12px;">District</th>
                                <th style="padding: 12px;">Experience</th>
                                <th style="padding: 12px;">Inspections</th>
                                <th style="padding: 12px;">Rating</th>
                                <th style="padding: 12px;">Status</th>
                                <th style="padding: 12px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT e.*, 
                                    COUNT(DISTINCT b.booking_id) as total_bookings,
                                    AVG(r.rating) as avg_rating
                                    FROM expert e
                                    LEFT JOIN booking b ON e.expert_id = b.expert_id AND b.status = 'completed'
                                    LEFT JOIN review r ON e.expert_id = r.expert_id
                                    GROUP BY e.expert_id
                                    ORDER BY e.expert_id DESC";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td data-label="Photo" style="padding: 12px;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                                <?php if (!empty($row['profile_photo'])): ?>
                                                    <img src="uploads/profiles/<?php echo $row['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <span style="font-size: 1.2rem;"><?php echo strtoupper(substr($row['name'], 0, 1)); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Name" style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td data-label="District" style="padding: 12px;"><?php echo htmlspecialchars($row['district']); ?></td>
                                        <td data-label="Experience" style="padding: 12px;"><?php echo $row['experience']; ?> Yrs</td>
                                        <td data-label="Inspections" style="padding: 12px; text-align: center; font-weight: 600; color: #2563eb;"><?php echo $row['total_bookings']; ?></td>
                                        <td data-label="Rating" style="padding: 12px;">
                                            <?php if ($row['avg_rating']): ?>
                                                <span style="color: #f59e0b; font-weight: 600;">⭐ <?php echo round($row['avg_rating'], 1); ?></span>
                                            <?php else: ?>
                                                <span style="color: #94a3b8; font-size: 0.85rem;">No reviews</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Status" style="padding: 12px;">
                                            <?php if ($row['verified']): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">✓ Verified</span>
                                            <?php else: ?>
                                                <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">⏳ Pending</span>
                                            <?php endif; ?>
                                            <?php if ($row['is_available']): ?>
                                                <span style="background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; margin-left: 5px;">Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Actions" style="padding: 12px;">
                                            <div style="display: flex; gap: 5px;">
                                                <a href="expert_profile.php?id=<?php echo $row['expert_id']; ?>" class="btn btn-outline btn-sm" target="_blank">View</a>
                                                <button onclick="if(confirm('Suspend this expert? They will not be able to receive bookings.')) window.location.href='admin_suspend_user.php?type=expert&id=<?php echo $row['expert_id']; ?>'" class="btn btn-sm" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">Suspend</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($current_tab == 'buyers'): ?>
                <!-- Buyer Management -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0;">All Buyers</h3>
                        <input type="text" id="buyerSearch" placeholder="Search buyers..." style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; width: 300px; max-width: 100%;">
                    </div>
                    
                    <table class="responsive-table-card" style="width: 100%; border-collapse: collapse;" id="buyersTable">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                                <th style="padding: 12px;">Photo</th>
                                <th style="padding: 12px;">Name</th>
                                <th style="padding: 12px;">Email</th>
                                <th style="padding: 12px;">Phone</th>
                                <th style="padding: 12px;">Location</th>
                                <th style="padding: 12px;">Bookings</th>
                                <th style="padding: 12px;">Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT b.*, COUNT(bk.booking_id) as total_bookings
                                    FROM buyer b
                                    LEFT JOIN booking bk ON b.buyer_id = bk.buyer_id
                                    GROUP BY b.buyer_id
                                    ORDER BY b.buyer_id DESC";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td data-label="Photo" style="padding: 12px;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #fef3c7; overflow: hidden; display: flex; align-items: center; justify-content: center; color: #92400e;">
                                                <?php if (!empty($row['profile_photo'])): ?>
                                                    <img src="uploads/profiles/<?php echo $row['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <span style="font-size: 1.2rem;"><?php echo strtoupper(substr($row['name'], 0, 1)); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Name" style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td data-label="Email" style="padding: 12px; font-size: 0.9rem; color: #64748b;"><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td data-label="Phone" style="padding: 12px;"><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td data-label="Location" style="padding: 12px; font-size: 0.9rem;"><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td data-label="Bookings" style="padding: 12px; text-align: center; font-weight: 600; color: #2563eb;"><?php echo $row['total_bookings']; ?></td>
                                        <td data-label="Joined" style="padding: 12px; font-size: 0.85rem; color: #64748b;"><?php echo date('M j, Y', strtotime($row['created_at'] ?? 'now')); ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($current_tab == 'bookings'): ?>
                <!-- Booking Management -->
                <div class="card">
                    <h3 style="margin: 0 0 20px 0;">All Bookings</h3>
                    
                    <!-- Status Filter -->
                    <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <button onclick="filterBookings('all')" class="btn btn-sm" style="background: #f1f5f9;">All</button>
                        <button onclick="filterBookings('pending')" class="btn btn-sm" style="background: #fef3c7; color: #92400e;">Pending</button>
                        <button onclick="filterBookings('approved')" class="btn btn-sm" style="background: #dcfce7; color: #166534;">Approved</button>
                        <button onclick="filterBookings('in_progress')" class="btn btn-sm" style="background: #dbeafe; color: #1e40af;">In Progress</button>
                        <button onclick="filterBookings('completed')" class="btn btn-sm" style="background: #e0e7ff; color: #3730a3;">Completed</button>
                        <button onclick="filterBookings('cancelled')" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;">Cancelled</button>
                    </div>
                    
                    <table class="responsive-table-card" style="width: 100%; border-collapse: collapse;" id="bookingsTable">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                                <th style="padding: 12px;">ID</th>
                                <th style="padding: 12px;">Buyer</th>
                                <th style="padding: 12px;">Expert</th>
                                <th style="padding: 12px;">Vehicle</th>
                                <th style="padding: 12px;">Package</th>
                                <th style="padding: 12px;">Date</th>
                                <th style="padding: 12px;">Status</th>
                                <th style="padding: 12px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT b.*, 
                                    buyer.name as buyer_name,
                                    expert.name as expert_name
                                    FROM booking b
                                    JOIN buyer ON b.buyer_id = buyer.buyer_id
                                    JOIN expert ON b.expert_id = expert.expert_id
                                    ORDER BY b.booking_date DESC";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $status_colors = [
                                        'pending' => '#f59e0b',
                                        'approved' => '#10b981',
                                        'in_progress' => '#3b82f6',
                                        'completed' => '#2563eb',
                                        'cancelled' => '#ef4444',
                                        'rejected' => '#64748b'
                                    ];
                                    $color = $status_colors[$row['status']] ?? '#64748b';
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;" data-status="<?php echo $row['status']; ?>">
                                        <td data-label="ID" style="padding: 12px; font-weight: 600;">#<?php echo $row['booking_id']; ?></td>
                                        <td data-label="Buyer" style="padding: 12px;"><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                                        <td data-label="Expert" style="padding: 12px;"><?php echo htmlspecialchars($row['expert_name']); ?></td>
                                        <td data-label="Vehicle" style="padding: 12px; font-size: 0.9rem;"><?php echo htmlspecialchars($row['vehicle_type']); ?></td>
                                        <td data-label="Package" style="padding: 12px; font-size: 0.85rem; color: #64748b;"><?php echo htmlspecialchars($row['package_name'] ?? 'Standard'); ?></td>
                                        <td data-label="Date" style="padding: 12px; font-size: 0.85rem; color: #64748b;"><?php echo date('M j, Y', strtotime($row['booking_date'])); ?></td>
                                        <td data-label="Status" style="padding: 12px;">
                                            <span style="background: <?php echo $color; ?>22; color: <?php echo $color; ?>; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td data-label="Amount" style="padding: 12px; font-weight: 600;">LKR <?php echo number_format($row['package_price'] ?? 0, 2); ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <script>
                function filterBookings(status) {
                    const rows = document.querySelectorAll('#bookingsTable tbody tr');
                    rows.forEach(row => {
                        if (status === 'all' || row.getAttribute('data-status') === status) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }
                </script>
                
            <?php elseif ($current_tab == 'payments'): ?>
                <!-- Payment Tracking -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <?php
                    $total_revenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payment")->fetch_assoc()['total'];
                    $total_payments = $conn->query("SELECT COUNT(*) as c FROM payment")->fetch_assoc()['c'];
                    $avg_payment = $total_payments > 0 ? $total_revenue / $total_payments : 0;
                    ?>
                    <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 25px; text-align: center; border: none;">
                        <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 8px;">LKR <?php echo number_format($total_revenue, 0); ?></div>
                        <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 500;">Total Revenue</div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 25px; text-align: center; border: none;">
                        <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px;"><?php echo $total_payments; ?></div>
                        <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 500;">Total Payments</div>
                    </div>
                    <div class="card" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 25px; text-align: center; border: none;">
                        <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 8px;">LKR <?php echo number_format($avg_payment, 0); ?></div>
                        <div style="font-size: 0.85rem; opacity: 0.9; font-weight: 500;">Avg Payment</div>
                    </div>
                </div>
                
                <div class="card">
                    <h3 style="margin: 0 0 20px 0;">Recent Transactions</h3>
                    <table class="responsive-table-card" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                                <th style="padding: 12px;">Payment ID</th>
                                <th style="padding: 12px;">Booking ID</th>
                                <th style="padding: 12px;">Buyer</th>
                                <th style="padding: 12px;">Expert</th>
                                <th style="padding: 12px;">Amount</th>
                                <th style="padding: 12px;">Method</th>
                                <th style="padding: 12px;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT p.*, 
                                    b.booking_id,
                                    buyer.name as buyer_name,
                                    expert.name as expert_name
                                    FROM payment p
                                    JOIN booking b ON p.booking_id = b.booking_id
                                    JOIN buyer ON b.buyer_id = buyer.buyer_id
                                    JOIN expert ON b.expert_id = expert.expert_id
                                    ORDER BY p.payment_date DESC
                                    LIMIT 50";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td data-label="Payment ID" style="padding: 12px; font-weight: 600;">#<?php echo $row['payment_id']; ?></td>
                                        <td data-label="Booking ID" style="padding: 12px; color: #2563eb;">#<?php echo $row['booking_id']; ?></td>
                                        <td data-label="Buyer" style="padding: 12px;"><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                                        <td data-label="Expert" style="padding: 12px;"><?php echo htmlspecialchars($row['expert_name']); ?></td>
                                        <td data-label="Amount" style="padding: 12px; font-weight: 600; color: #10b981;">LKR <?php echo number_format($row['amount'], 2); ?></td>
                                        <td data-label="Method" style="padding: 12px; font-size: 0.85rem; color: #64748b;"><?php echo htmlspecialchars($row['payment_method'] ?? 'N/A'); ?></td>
                                        <td data-label="Date" style="padding: 12px; font-size: 0.85rem; color: #64748b;"><?php echo date('M j, Y', strtotime($row['payment_date'])); ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='7' style='padding: 40px; text-align: center; color: #94a3b8;'>No payments found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($current_tab == 'reviews'): ?>
                <!-- Review Management -->
                <div class="card">
                    <h3 style="margin: 0 0 20px 0;">All Reviews</h3>
                    <table class="responsive-table-card" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #f1f5f9;">
                                <th style="padding: 12px;">Buyer</th>
                                <th style="padding: 12px;">Expert</th>
                                <th style="padding: 12px;">Rating</th>
                                <th style="padding: 12px;">Comment</th>
                                <th style="padding: 12px;">Date</th>
                                <th style="padding: 12px;">Reply</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT r.*, 
                                    buyer.name as buyer_name,
                                    expert.name as expert_name
                                    FROM review r
                                    JOIN buyer ON r.buyer_id = buyer.buyer_id
                                    JOIN expert ON r.expert_id = expert.expert_id
                                    ORDER BY r.review_date DESC";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td data-label="Buyer" style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                                        <td data-label="Expert" style="padding: 12px;"><?php echo htmlspecialchars($row['expert_name']); ?></td>
                                        <td data-label="Rating" style="padding: 12px;">
                                            <span style="color: #f59e0b; font-weight: 600; font-size: 1.1rem;">★ <?php echo $row['rating']; ?>.0</span>
                                        </td>
                                        <td data-label="Comment" style="padding: 12px; max-width: 300px;">
                                            <p style="margin: 0; font-size: 0.9rem; color: #475569;"><?php echo htmlspecialchars(substr($row['comment'], 0, 100)) . (strlen($row['comment']) > 100 ? '...' : ''); ?></p>
                                        </td>
                                        <td data-label="Date" style="padding: 12px; font-size: 0.85rem; color: #64748b;"><?php echo date('M j, Y', strtotime($row['review_date'])); ?></td>
                                        <td data-label="Reply" style="padding: 12px;">
                                            <?php if (!empty($row['expert_reply'])): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">✓ Replied</span>
                                            <?php else: ?>
                                                <span style="background: #f1f5f9; color: #64748b; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">No Reply</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='6' style='padding: 40px; text-align: center; color: #94a3b8;'>No reviews found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Search Functionality -->
            <script>
            // Expert Search
            const expertSearch = document.getElementById('expertSearch');
            if (expertSearch) {
                expertSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#expertsTable tbody tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
            
            // Buyer Search
            const buyerSearch = document.getElementById('buyerSearch');
            if (buyerSearch) {
                buyerSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#buyersTable tbody tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
            </script>
        <?php endif; ?>


    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.filter-tab');
            const cards = document.querySelectorAll('.booking-card');

            function applyFilters() {
                const activeFilter = document.querySelector('.filter-tab.active').getAttribute('data-filter');
                let visibleCount = 0;

                cards.forEach(card => {
                    // Check all classes on the card to find the status- class
                    let status = '';
                    card.classList.forEach(cls => {
                        if (cls.startsWith('status-')) {
                            status = cls.replace('status-', '');
                        }
                    });

                    if (activeFilter === 'all' || status === activeFilter) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Show empty state if no cards found
                let emptyMsg = document.getElementById('emptyFilterMsg');
                if (visibleCount === 0) {
                    if (!emptyMsg) {
                        emptyMsg = document.createElement('div');
                        emptyMsg.id = 'emptyFilterMsg';
                        emptyMsg.className = 'card';
                        emptyMsg.style.cssText = 'width: 100%; text-align: center; padding: 60px; color: #94a3b8; font-size: 0.9rem; border-radius: 4px; background: white;';
                        document.getElementById('bookingGrid').appendChild(emptyMsg);
                    }
                    
                    if (activeFilter !== 'all') {
                        emptyMsg.innerHTML = `No items tagged as <strong>${activeFilter.replace('_', ' ')}</strong>.`;
                    } else {
                        emptyMsg.innerHTML = "No records found.";
                    }
                    emptyMsg.style.display = 'block';
                } else if (emptyMsg) {
                    emptyMsg.style.display = 'none';
                }
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    applyFilters();
                });
            });
            
            // Initial filter
            applyFilters();
        });
    </script>
    
    <?php if ($role == 'admin'): ?>
        <?php include 'expert_details_modal.php'; ?>
    <?php endif; ?>
</body>
</html>
