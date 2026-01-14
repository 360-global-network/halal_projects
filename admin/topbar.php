<?php
require_once '../config.php';
require_once '../admin_auth.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}
?>
<header class="admin-topbar">
    <button class="toggle-sidebar">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="topbar-search">
        <input type="text" placeholder="Search projects...">
        <button><i class="fas fa-search"></i></button>
    </div>
    
    <div class="admin-user">
        <div class="user-notifications">
            <button class="notification-btn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>
        </div>
        
        <div class="user-profile">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['admin_username']); ?>&background=3498db&color=fff" 
                 alt="Admin">
            <div class="user-info">
                <h4><?php echo $_SESSION['admin_username']; ?></h4>
                <p>Administrator</p>
            </div>
            <div class="user-dropdown">
                <button class="dropdown-toggle">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>