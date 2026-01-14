<?php
require_once '../config.php';
require_once '../admin_auth.php';
require_once '../project_manager.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$projectManager = new ProjectManager();
$projects = $projectManager->getAllProjects();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Projects | Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php include 'sidebar.php'; ?>

    <main class="admin-main">
        <?php include 'topbar.php'; ?>

        <div class="admin-content">
            <div class="page-header">
                <h1>All Projects</h1>
                <div class="header-actions">
                    <a href="add-project.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Project
                    </a>
                    <button class="btn-secondary">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="filter-section">
                <div class="search-box">
                    <input type="text" placeholder="Search projects...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="filter-controls">
                    <select class="filter-select">
                        <option value="">All Status</option>
                        <option value="planned">Planned</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                    <select class="filter-select">
                        <option value="">All States</option>
                        <option value="1">Edo</option>
                        <option value="2">Lagos</option>
                    </select>
                    <button class="btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="admin-table">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>Project Title</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Budget</th>
                                <th>Start Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="row-checkbox" value="<?php echo $project['id']; ?>">
                                </td>
                                <td>
                                    <div class="project-title">
                                        <div class="project-image-small">
                                            <?php if ($project['image_url']): ?>
                                            <img src="../uploads/<?php echo $project['image_url']; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>">
                                            <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                            <p class="project-desc"><?php echo substr($project['description'], 0, 50) . '...'; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="location-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <div>
                                            <span><?php echo htmlspecialchars($project['lga_name']); ?></span>
                                            <small><?php echo htmlspecialchars($project['state_name']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $project['status']; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    ₦<?php echo number_format($project['budget'], 2); ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($project['start_date'])); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view-project.php?id=<?php echo $project['id']; ?>" class="btn-action btn-view" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="btn-action btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteProject(<?php echo $project['id']; ?>)" class="btn-action btn-delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php if ($project['latitude'] && $project['longitude']): ?>
                                        <a href="../projects-map.php?focus=<?php echo $project['id']; ?>" class="btn-action btn-map" title="View on Map">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="table-footer">
                    <div class="table-info">
                        Showing <?php echo count($projects); ?> of <?php echo count($projects); ?> projects
                    </div>
                    <div class="pagination">
                        <button class="page-btn" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">3</button>
                        <button class="page-btn">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/admin.js"></script>
    <script>
        function deleteProject(id) {
            if (confirm('Are you sure you want to delete this project?')) {
                fetch('delete-project.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Project deleted successfully!');
                            location.reload();
                        } else {
                            alert('Error deleting project: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting project');
                    });
            }
        }
        
        // Select all checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</body>
</html>