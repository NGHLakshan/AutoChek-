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
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<header>
    <div class="container">
        <h1><a href="index.php" style="text-decoration: none; color: inherit;">Auto<span>Chek</span></a></h1>
        
        <button class="mobile-menu-btn" onclick="document.querySelector('header nav').classList.toggle('active')"><i class="ph ph-list" style="font-size: 1.5rem;"></i></button>
        
        <nav>
            <ul>
                <li><a href="index.php"><i class="ph ph-house"></i> Home</a></li>
                
                <?php if ($current_user_id): ?>
                    <li><a href="dashboard.php"><i class="ph ph-gauge"></i> Dashboard</a></li>
                    
                    <?php if ($current_role == 'buyer'): ?>
                        <li><a href="experts.php"><i class="ph ph-magnifying-glass"></i> Find Expert</a></li>
                        <li><a href="profile_settings.php"><i class="ph ph-user-gear"></i> Profile</a></li>
                    <?php elseif ($current_role == 'expert'): ?>
                        <li><a href="profile_settings.php"><i class="ph ph-user"></i> Profile</a></li>
                    <?php elseif ($current_role == 'admin'): ?>
                        <li><a href="settings.php"><i class="ph ph-gear"></i> Settings</a></li>
                    <?php endif; ?>

                    <li><a href="logout.php" onclick="return confirm('Are you sure you want to logout?')" style="display: flex; align-items: center; gap: 5px;"><i class="ph ph-sign-out"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="experts.php"><i class="ph ph-magnifying-glass"></i> Find Expert</a></li>
                    <li><a href="register_expert.php"><i class="ph ph-briefcase"></i> Become an Expert</a></li>
                    <li><a href="login.php"><i class="ph ph-sign-in"></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
