<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

$current_user_id = $_SESSION['user_id'] ?? null;
$current_role = $_SESSION['role'] ?? null;

// Handle Global Toggle Availability for Experts
if (isset($_GET['nav_action']) && $_GET['nav_action'] == 'toggle_availability' && $current_role == 'expert') {
    $conn->query("UPDATE expert SET is_available = NOT is_available WHERE expert_id = $current_user_id");
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $redirect_url");
    exit;
}

// Fetch availability for expert if needed for nav
$nav_is_available = false;
if ($current_role == 'expert' && $current_user_id) {
    $nav_res = $conn->query("SELECT is_available FROM expert WHERE expert_id = $current_user_id");
    if ($nav_res) {
        $expert_data = $nav_res->fetch_assoc();
        $nav_is_available = $expert_data['is_available'];
    }
}
?>
<header>
    <div class="container">
        <h1><a href="index.php" style="text-decoration: none; color: inherit;">Auto<span>Chek</span></a></h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                
                <?php if ($current_user_id): ?>
                    <?php if ($current_role == 'buyer'): ?>
                        <li><a href="experts.php">Find Expert</a></li>
                        <li><a href="dashboard.php">Bookings</a></li>
                        <li><a href="profile_settings.php">Profile Settings</a></li>
                    <?php elseif ($current_role == 'admin'): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="settings.php">Settings</a></li>
                    <?php else: ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="profile_settings.php">Profile</a></li>
                        <li><a href="settings.php">Settings</a></li>
                    <?php endif; ?>

                    <li><a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a></li>
                <?php else: ?>
                    <li><a href="experts.php">Find Expert</a></li>
                    <li><a href="register_expert.php" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600;">Become an Expert</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
