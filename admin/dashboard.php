<?php
require_once '../config.php';
require_once '../admin_auth.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

require_once '../project_manager.php';
$projectManager = new ProjectManager();

// Get project statistics
$totalProjects = $projectManager->countProjectsByStatus();
$ongoingProjects = $projectManager->countProjectsByStatus('ongoing');
$completedProjects = $projectManager->countProjectsByStatus('completed');
$recentProjects = $projectManager->getRecentProjects(5);

// Get all projects for the table (limited to 10 for performance)
$allProjects = $projectManager->getAllProjects();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Auchi Projects</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-building"></i> <span>Admin Panel</span></h2>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="projects.php" class="nav-link">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
            <a href="add-project.php" class="nav-link">
                <i class="fas fa-plus-circle"></i>
                <span>Add Project</span>
            </a>
            <a href="states.php" class="nav-link">
                <i class="fas fa-map"></i>
                <span>States & LGAs</span>
            </a>
            <a href="settings.php" class="nav-link">
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

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Topbar -->
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
                    <img src="https://ui-avatars.com/api/?name=Admin&background=3498db&color=fff" alt="Admin">
                    <div class="user-info">
                        <h4>Administrator</h4>
                        <p>Admin</p>
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

        <!-- Dashboard Content -->
        <div class="admin-content">
            <h1>Dashboard</h1>
            <p>This section Shows All Completed and Ongoing Projects</p>

            <!-- Stats Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-project-diagram"></i>
                        <span class="card-stats"><?php echo $totalProjects; ?></span>
                    </div>
                    <h3 class="card-title">Total Projects</h3>
                </div>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-hard-hat"></i>
                        <span class="card-stats"><?php echo $ongoingProjects; ?></span>
                    </div>
                    <h3 class="card-title">Ongoing Projects</h3>
                </div>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-check-circle"></i>
                        <span class="card-stats"><?php echo $completedProjects; ?></span>
                    </div>
                    <h3 class="card-title">Completed Projects</h3>
                </div>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-users"></i>
                        <span class="card-stats">1</span>
                    </div>
                    <h3 class="card-title">Active Admins</h3>
                </div>
            </div>

            <!-- Recent Projects Table -->
            <div class="admin-table">
                <div class="table-header">
                    <h3>Recent Projects</h3>
                    <a href="add-project.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Add New Project
                    </a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Project Title</th>
                                <th>Location</th>
                                <th>Status</th>
                                <!-- <th>Budget</th> -->
                                <th>Start Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentProjects) > 0): ?>
                                <?php foreach ($recentProjects as $project): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                                    <td><?php echo htmlspecialchars($project['lga_name'] . ', ' . $project['state_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $project['status']; ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </td>
                                    <!-- <td>₦<?php echo number_format($project['budget'] ?? 0, 2); ?></td> -->
                                    <td><?php echo date('M d, Y', strtotime($project['start_date'] ?? 'now')); ?></td>
                                    <td>
                                        <button class="btn-action btn-view" onclick="viewProject(<?php echo $project['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action btn-edit" onclick="editProject(<?php echo $project['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteProject(<?php echo $project['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px;">
                                        <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                                        <p>No projects found. Add your first project!</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Activity Chart Section -->
            <div class="dashboard-section">
                <h3>Project Activity</h3>
                <div class="activity-chart">
                    <canvas id="projectChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/admin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function viewProject(id) {
            window.location.href = 'view-project.php?id=' + id;
        }
        
        function editProject(id) {
            window.location.href = 'edit-project.php?id=' + id;
        }
        
        function deleteProject(id) {
            if (confirm('Are you sure you want to delete this project?')) {
                window.location.href = 'delete-project.php?id=' + id;
            }
        }
        
        // Initialize Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('projectChart').getContext('2d');
            const projectChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Projects Started',
                        data: [2, 3, 1, 4, 2, 3],
                        backgroundColor: 'rgba(52, 152, 219, 0.8)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Projects Completed',
                        data: [1, 2, 1, 3, 2, 1],
                        backgroundColor: 'rgba(46, 204, 113, 0.8)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Monthly Project Activity'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>