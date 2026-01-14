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

$project_id = $_GET['id'] ?? 0;
$project = $projectManager->getProjectById($project_id);

if (!$project) {
    header('Location: projects.php');
    exit();
}

// Get similar projects
$similarProjects = $projectManager->conn->query("
    SELECT p.*, s.name as state_name, l.name as lga_name 
    FROM projects p 
    JOIN states s ON p.state_id = s.id 
    JOIN lgas l ON p.lga_id = l.id 
    WHERE p.state_id = {$project['state_id']} 
    AND p.id != {$project_id}
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

// Get project timeline
$timeline = [
    'planning' => $project['start_date'] ? date('M Y', strtotime($project['start_date'])) : 'Not set',
    'start' => $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'Not started',
    'expected_completion' => $project['expected_completion'] ? date('M d, Y', strtotime($project['expected_completion'])) : 'Not set',
    'status' => $project['status']
];

// Calculate project duration
$duration = '';
if ($project['start_date'] && $project['expected_completion']) {
    $start = new DateTime($project['start_date']);
    $end = new DateTime($project['expected_completion']);
    $interval = $start->diff($end);
    $duration = $interval->format('%m months, %d days');
}

// Calculate progress percentage
$progress = 0;
switch ($project['status']) {
    case 'planned':
        $progress = 10;
        break;
    case 'ongoing':
        $progress = 50;
        break;
    case 'completed':
        $progress = 100;
        break;
    case 'delayed':
        $progress = 30;
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> | Project Details</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .project-view {
            padding: 30px;
        }
        
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .project-title-section h1 {
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .project-meta {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
        }
        
        .meta-item i {
            color: var(--secondary);
        }
        
        .project-actions {
            display: flex;
            gap: 10px;
        }
        
        .project-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .project-content {
                grid-template-columns: 1fr;
            }
        }
        
        .project-details-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .project-details-card h3 {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .project-details-card h3 i {
            color: var(--secondary);
        }
        
        .detail-item {
            margin-bottom: 15px;
            display: flex;
        }
        
        .detail-label {
            width: 150px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .detail-value {
            flex: 1;
            color: var(--gray);
        }
        
        .detail-value strong {
            color: var(--primary);
        }
        
        .project-image {
            width: 100%;
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .project-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .status-badge-large {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .status-ongoing {
            background: #fef5e7;
            color: #7d6608;
        }
        
        .status-completed {
            background: #d5f4e6;
            color: #186a3b;
        }
        
        .status-planned {
            background: #d6eaf8;
            color: #21618c;
        }
        
        .status-delayed {
            background: #fadbd8;
            color: #943126;
        }
        
        .progress-section {
            margin: 30px 0;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-bar-large {
            height: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--secondary), #2980b9);
            border-radius: 10px;
            transition: width 1s ease;
        }
        
        .progress-text {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            text-align: center;
            line-height: 20px;
            color: white;
            font-weight: 600;
        }
        
        .map-container {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
        }
        
        .similar-projects {
            margin-top: 40px;
        }
        
        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .similar-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .similar-card:hover {
            transform: translateY(-5px);
        }
        
        .similar-image {
            height: 150px;
            background: linear-gradient(135deg, var(--secondary), #2980b9);
        }
        
        .similar-content {
            padding: 20px;
        }
        
        .similar-content h4 {
            margin-bottom: 10px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #eee;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -36px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--secondary);
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--secondary);
        }
        
        .timeline-date {
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .timeline-event {
            color: var(--gray);
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'sidebar.php'; ?>

    <main class="admin-main">
        <?php include 'topbar.php'; ?>

        <div class="project-view">
            <!-- Project Header -->
            <div class="project-header">
                <div class="project-title-section">
                    <h1><?php echo htmlspecialchars($project['title']); ?></h1>
                    <div class="project-meta">
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($project['lga_name'] . ', ' . $project['state_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Started: <?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'Not set'; ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-hard-hat"></i>
                            <span><?php echo htmlspecialchars($project['contractor'] ?? 'No contractor assigned'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="project-actions">
                    <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="btn-primary">
                        <i class="fas fa-edit"></i> Edit Project
                    </a>
                    <a href="projects.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Projects
                    </a>
                </div>
            </div>

            <!-- Project Content -->
            <div class="project-content">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- Project Image -->
                    <div class="project-details-card">
                        <div class="project-image">
                            <?php if ($project['image_url']): ?>
                            <img src="../uploads/<?php echo $project['image_url']; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>">
                            <?php else: ?>
                            <div style="background: linear-gradient(135deg, var(--secondary), #2980b9); height: 100%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-building" style="font-size: 4rem;"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="status-badge-large status-<?php echo $project['status']; ?>">
                            <?php echo strtoupper($project['status']); ?>
                        </div>
                        
                        <div class="progress-section">
                            <div class="progress-header">
                                <span>Project Progress</span>
                                <span><?php echo $progress; ?>%</span>
                            </div>
                            <div class="progress-bar-large">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                <div class="progress-text"><?php echo $progress; ?>% Complete</div>
                            </div>
                        </div>
                        
                        <h3><i class="fas fa-info-circle"></i> Project Description</h3>
                        <p style="line-height: 1.6; color: #555;"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                    </div>

                    <!-- Location Details -->
                    <div class="project-details-card">
                        <h3><i class="fas fa-map-marked-alt"></i> Location Details</h3>
                        <div class="detail-item">
                            <div class="detail-label">Address:</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($project['address'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">State:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($project['state_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">LGA:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($project['lga_name']); ?></div>
                        </div>
                        <?php if ($project['latitude'] && $project['longitude']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Coordinates:</div>
                            <div class="detail-value">
                                <strong>Lat:</strong> <?php echo $project['latitude']; ?>° N,
                                <strong>Lng:</strong> <?php echo $project['longitude']; ?>° E
                            </div>
                        </div>
                        <div class="map-container" id="projectMap"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <!-- Project Information -->
                    <div class="project-details-card">
                        <h3><i class="fas fa-clipboard-list"></i> Project Information</h3>
                        <div class="detail-item">
                            <div class="detail-label">Project ID:</div>
                            <div class="detail-value"><strong>#<?php echo str_pad($project['id'], 5, '0', STR_PAD_LEFT); ?></strong></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Contractor:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($project['contractor'] ?? 'Not assigned'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Budget:</div>
                            <div class="detail-value"><strong>₦<?php echo number_format($project['budget'] ?? 0, 2); ?></strong></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Location Type:</div>
                            <div class="detail-value"><?php echo ucfirst($project['location_type'] ?? 'approximate'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Created:</div>
                            <div class="detail-value"><?php echo date('M d, Y', strtotime($project['created_at'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Last Updated:</div>
                            <div class="detail-value"><?php echo date('M d, Y', strtotime($project['updated_at'])); ?></div>
                        </div>
                    </div>

                    <!-- Project Timeline -->
                    <div class="project-details-card">
                        <h3><i class="fas fa-timeline"></i> Project Timeline</h3>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-date">Planning Phase</div>
                                <div class="timeline-event">Project initiated and planned</div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-date"><?php echo $timeline['start']; ?></div>
                                <div class="timeline-event">Construction work started</div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-date"><?php echo $timeline['expected_completion']; ?></div>
                                <div class="timeline-event">Expected completion date</div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-date">Current Status</div>
                                <div class="timeline-event">
                                    <span class="status-badge status-<?php echo $project['status']; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($duration): ?>
                        <div class="detail-item" style="margin-top: 20px;">
                            <div class="detail-label">Duration:</div>
                            <div class="detail-value"><strong><?php echo $duration; ?></strong></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="project-details-card">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="btn-primary" style="text-align: center;">
                                <i class="fas fa-edit"></i> Edit Project Details
                            </a>
                            <button onclick="updateStatus()" class="btn-secondary">
                                <i class="fas fa-sync-alt"></i> Update Status
                            </button>
                            <button onclick="addNote()" class="btn-secondary">
                                <i class="fas fa-sticky-note"></i> Add Note
                            </button>
                            <button onclick="generateReport()" class="btn-secondary">
                                <i class="fas fa-file-pdf"></i> Generate Report
                            </button>
                            <button onclick="shareProject()" class="btn-secondary">
                                <i class="fas fa-share"></i> Share Project
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Similar Projects -->
            <?php if (!empty($similarProjects)): ?>
            <div class="similar-projects">
                <h3>Similar Projects in <?php echo htmlspecialchars($project['state_name']); ?></h3>
                <div class="similar-grid">
                    <?php foreach ($similarProjects as $similar): ?>
                    <div class="similar-card">
                        <div class="similar-image"></div>
                        <div class="similar-content">
                            <h4><?php echo htmlspecialchars($similar['title']); ?></h4>
                            <p><?php echo substr($similar['description'], 0, 100) . '...'; ?></p>
                            <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                                <span class="status-badge status-<?php echo $similar['status']; ?>">
                                    <?php echo ucfirst($similar['status']); ?>
                                </span>
                                <a href="view-project.php?id=<?php echo $similar['id']; ?>" class="btn-small">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($project['latitude'] && $project['longitude']): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map
            const map = L.map('projectMap').setView([
                <?php echo $project['latitude']; ?>,
                <?php echo $project['longitude']; ?>
            ], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            
            // Add marker
            const marker = L.marker([
                <?php echo $project['latitude']; ?>,
                <?php echo $project['longitude']; ?>
            ]).addTo(map);
            
            marker.bindPopup(`
                <b><?php echo htmlspecialchars($project['title']); ?></b><br>
                <?php echo htmlspecialchars($project['lga_name'] . ', ' . $project['state_name']); ?>
            `).openPopup();
        });
        
        function updateStatus() {
            const newStatus = prompt('Enter new status (planned/ongoing/completed/delayed):');
            if (newStatus) {
                fetch('update-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        project_id: <?php echo $project['id']; ?>,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Status updated successfully!');
                        location.reload();
                    } else {
                        alert('Error updating status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status');
                });
            }
        }
        
        function addNote() {
            const note = prompt('Enter your note:');
            if (note) {
                alert('Note added: ' + note);
                // In production, save to database
            }
        }
        
        function generateReport() {
            alert('Generating PDF report for this project...');
        }
        
        function shareProject() {
            const url = window.location.href;
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo htmlspecialchars($project['title']); ?>',
                    text: 'Check out this project in Auchi Projects',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url);
                alert('Link copied to clipboard!');
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>