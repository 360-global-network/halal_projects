<?php
// project-detail.php
require_once 'config.php';
require_once 'project_manager.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get project ID from URL
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize ProjectManager
$projectManager = new ProjectManager();

// Get project data
if ($project_id > 0) {
    $project = $projectManager->getProjectById($project_id);
}

// If no project found, show error
if (empty($project)) {
    $project_id = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project_id ? htmlspecialchars($project['title']) : 'Project Not Found'; ?> - Project Details</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>
    <div class="container">
        <?php if ($project_id && $project): ?>
            <!-- Project found, display details -->
            <div class="project-detail-header">
                <a href="projects.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
                <h1 class="animate__animated animate__fadeIn"><?php echo htmlspecialchars($project['title']); ?></h1>
                <div class="project-status-badge <?php echo htmlspecialchars($project['status']); ?>">
                    <?php echo strtoupper($project['status']); ?>
                </div>
            </div>
            
            <div class="project-detail-content">
                <div class="project-info-card">
                    <h2><i class="fas fa-info-circle"></i> Project Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Description:</strong>
                            <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                        </div>
                        <div class="info-item">
                            <strong>Location:</strong>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($project['lga_name'] . ', ' . $project['state_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <strong>Contractor:</strong>
                            <p><i class="fas fa-hard-hat"></i> <?php echo htmlspecialchars($project['contractor']); ?></p>
                        </div>
                        <div class="info-item">
                            <strong>Budget:</strong>
                            <p><i class="fas fa-money-bill-wave"></i> ₦<?php echo number_format($project['budget'], 2); ?></p>
                        </div>
                        <div class="info-item">
                            <strong>Start Date:</strong>
                            <p><i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($project['start_date'])); ?></p>
                        </div>
                        <div class="info-item">
                            <strong>Expected Completion:</strong>
                            <p><i class="fas fa-calendar-check"></i> <?php echo date('F j, Y', strtotime($project['expected_completion'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <?php if ($project['address']): ?>
                <div class="project-info-card">
                    <h2><i class="fas fa-map"></i> Location Details</h2>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($project['address']); ?></p>
                    <?php if ($project['latitude'] && $project['longitude']): ?>
                    <div class="map-placeholder">
                        <i class="fas fa-map-marked-alt"></i>
                        <p>Location coordinates: <?php echo $project['latitude']; ?>, <?php echo $project['longitude']; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- Project not found -->
            <div class="no-project-found">
                <i class="fas fa-exclamation-triangle fa-4x"></i>
                <h2>Project Not Found</h2>
                <p>The project you're looking for doesn't exist or has been removed.</p>
                <a href="projects.php" class="btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>