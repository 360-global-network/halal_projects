<?php
require_once '../config.php';
require_once '../admin_auth.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2>
            <i class="fas fa-building"></i> 
            <span>Admin Panel</span>
        </h2>
        <p><?php echo $_SESSION['admin_username']; ?></p>
    </div>
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="projects.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>">
            <i class="fas fa-project-diagram"></i>
            <span>All Projects</span>
        </a>
        <a href="add-project.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add-project.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Add Project</span>
        </a>
        <a href="states.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'states.php' ? 'active' : ''; ?>">
            <i class="fas fa-map"></i>
            <span>States & LGAs</span>
        </a>
        <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <p>© <?php echo date('Y'); ?> Auchi Projects</p>
        <p>v1.0.0</p>
    </div>
</aside>