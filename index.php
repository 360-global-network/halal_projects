<?php 
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

// Fetch ALL projects (not just ones with images)
$featuredProjects = [];
$stats = [
    'ongoing' => 0,
    'completed' => 0,
    'lgas_covered' => 0
];

try {
    // Fetch ALL projects - get latest 6
    $result = $conn->query("
        SELECT id, title, description, status, progress, image_url 
        FROM projects 
        ORDER BY id DESC 
        LIMIT 6
    ");
    
    if ($result) {
        $featuredProjects = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Fetch statistics
    $result = $conn->query("SELECT COUNT(*) as ongoing FROM projects WHERE status IN ('ongoing', 'in-progress', 'active')");
    if ($result) {
        $ongoing = $result->fetch_assoc();
        $stats['ongoing'] = $ongoing['ongoing'] ?? 0;
    }
    
    $result = $conn->query("SELECT COUNT(*) as completed FROM projects WHERE status = 'completed'");
    if ($result) {
        $completed = $result->fetch_assoc();
        $stats['completed'] = $completed['completed'] ?? 0;
    }
    
    $stats['lgas_covered'] = 1; // Default value
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auchi Building Projects | Home</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
     
    /* Force images to show */
    .project-image img {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: static !important;
        width: 100% !important;
        height: 200px !important;
    }

        .project-image {
            position: relative;
            overflow: hidden;
            border-radius: 8px 8px 0 0;
            height: 200px;
            background: #f8f9fa;
        }
        
        .project-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #666;
            text-align: center;
            padding: 20px;
        }
        
        .image-placeholder i {
            font-size: 48px;
            margin-bottom: 10px;
            color: #95a5a6;
        }
        
        .project-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            z-index: 10;
            background: #f39c12;
        }
        
        .project-status.completed {
            background: #27ae60;
        }
        
        .project-status.ongoing {
            background: #f39c12;
        }
        
        .project-status.in-progress {
            background: #f39c12;
        }
        
        .project-status.planned {
            background: #3498db;
        }
        
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: rgba(0, 0, 0, 0.1);
            z-index: 2;
        }
        
        .progress-bar .progress {
            height: 100%;
            background: #2ecc71;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-building"></i>
                <span>Auchi</span>Projects
            </a>
            <div class="nav-links">
                <a href="index.php" class="active">Home</a>
                <a href="projects.php">Projects</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
                <a href="admin/login.php" class="admin-btn">
                    <i class="fas fa-lock"></i> Admin
                </a>
            </div>
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content animate__animated animate__fadeInUp">
                <h1>Building the Future of <span>Auchi</span></h1>
                <p>Tracking development projects and infrastructure improvements in Auchi, Edo State, Nigeria</p>
                <a href="projects.php" class="btn-primary">
                    View Projects <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="hero-image animate__animated animate__fadeIn">
                <img src="https://images.unsplash.com/photo-1541888946425-d81bb19240f5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Construction in Nigeria">
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stat-card" data-stat="ongoing">
                <i class="fas fa-hard-hat"></i>
                <h3 class="count" id="ongoingCount"><?php echo $stats['ongoing']; ?></h3>
                <p>Ongoing Projects</p>
            </div>
            <div class="stat-card" data-stat="completed">
                <i class="fas fa-check-circle"></i>
                <h3 class="count" id="completedCount"><?php echo $stats['completed']; ?></h3>
                <p>Completed Projects</p>
            </div>
            <div class="stat-card" data-stat="lgas">
                <i class="fas fa-map-marker-alt"></i>
                <h3 class="count" id="lgasCount"><?php echo $stats['lgas_covered']; ?></h3>
                <p>LGAs Covered</p>
            </div>
        </div>
    </section>

    <!-- Featured Projects -->
    <section class="featured-projects">
        <div class="container">
            <div class="section-header">
                <h2>Featured Projects</h2>
                <p>Recent and ongoing development projects in Auchi</p>
            </div>
            <div class="projects-grid" id="featuredProjects">
    <?php if (empty($featuredProjects)): ?>
        <div class="no-projects">
            <p>No projects available at the moment.</p>
        </div>
    <?php else: ?>
        <?php 
        $projectsWithImages = 0;
        foreach ($featuredProjects as $project): 
            $hasImage = !empty($project['image_url']) && trim($project['image_url']) !== '';
            if ($hasImage) $projectsWithImages++;
        ?>
        <div class="project-card animate__animated">
            <div class="project-image">
                <?php if ($hasImage): ?>
                    <?php 
                    // SIMPLE: Just get the image name
                    $imageName = trim($project['image_url']);
                    // Remove project_images/ if already there
                    $imageName = str_replace('project_images/', '', $imageName);
                    // Simple relative path
                    $imagePath = 'project_images/' . $imageName;
                    ?>
                    <!-- SIMPLE IMG TAG - NO complex onerror -->
                    <img src="<?php echo $imagePath; ?>" 
                         alt="<?php echo htmlspecialchars($project['title']); ?>"
                         title="<?php echo htmlspecialchars($project['title']); ?>">
                <?php else: ?>
                    <div class="image-placeholder">
                        <i class="fas fa-image"></i>
                        <span>No image available</span>
                        <small style="font-size: 11px; margin-top: 5px;"><?php echo htmlspecialchars($project['title']); ?></small>
                    </div>
                <?php endif; ?>
                
                <!-- Status badge -->
                <div class="project-status <?php echo htmlspecialchars($project['status']); ?>">
                    <?php 
                    $status = $project['status'];
                    if ($status === 'in-progress') $status = 'ongoing';
                    echo htmlspecialchars(ucfirst($status)); 
                    ?>
                </div>
                
                <!-- Progress bar -->
                <?php if (!empty($project['progress'])): ?>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo htmlspecialchars($project['progress']); ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="project-content">
                <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                <p class="project-description">
                    <?php 
                    $description = $project['description'] ?? 'No description available';
                    $cleanDescription = htmlspecialchars($description);
                    if (strlen($cleanDescription) > 100) {
                        echo substr($cleanDescription, 0, 100) . '...';
                    } else {
                        echo $cleanDescription;
                    }
                    ?>
                </p>
                <div class="project-meta">
                    <?php if (!empty($project['progress'])): ?>
                    <span><i class="fas fa-chart-line"></i> <?php echo htmlspecialchars($project['progress']); ?>% Complete</span>
                    <?php endif; ?>
                    <?php if (!empty($project['lga'])): ?>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($project['lga']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Debug info -->
        <div style="background: #f8f9fa; padding: 15px; grid-column: 1 / -1; border-radius: 5px; margin-top: 20px;">
            <h4>Debug Information:</h4>
            <p>Total projects displayed: <?php echo count($featuredProjects); ?></p>
            <p>Projects with images: <?php echo $projectsWithImages; ?></p>
            <p>Projects without images: <?php echo count($featuredProjects) - $projectsWithImages; ?></p>
            
            <?php 
            // List projects with their image status
            echo "<p><strong>Project Details:</strong></p>";
            foreach ($featuredProjects as $p) {
                $hasImg = !empty($p['image_url']) ? 'YES' : 'NO';
                echo "<div style='font-size: 12px; margin-bottom: 5px;'>";
                echo htmlspecialchars($p['title']) . ": " . $hasImg;
                if (!empty($p['image_url'])) {
                    echo " (" . htmlspecialchars($p['image_url']) . ")";
                }
                echo "</div>";
            }
            ?>
        </div>
    <?php endif; ?>
</div>
           
            <div class="text-center">
                <a href="projects.php" class="btn-secondary">
                    View All Projects <i class="fas fa-list"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <div class="about-content">
                <h2>About Auchi Projects</h2>
                <p>Auchi Projects is an initiative to track and showcase building and infrastructure projects in Auchi, Edo State, Nigeria. Our platform provides transparency and information about ongoing and completed development projects in the region.</p>
                <p>We work with local government authorities, contractors, and community leaders to bring accurate and up-to-date information about projects that shape the future of our community.</p>
                <div class="about-features">
                    <div class="feature">
                        <i class="fas fa-eye"></i>
                        <h4>Transparency</h4>
                        <p>Open access to project information</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <h4>Tracking</h4>
                        <p>Monitor project progress and timelines</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-users"></i>
                        <h4>Community</h4>
                        <p>Engaging citizens in development</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3><i class="fas fa-building"></i> AuchiProjects</h3>
                    <p>Tracking development projects in Auchi, Edo State, Nigeria for transparency and community engagement.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <a href="index.php">Home</a>
                    <a href="projects.php">Projects</a>
                    <a href="#about">About</a>
                    <a href="admin/login.php">Admin Login</a>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-map-marker-alt"></i> Auchi, Edo State, Nigeria</p>
                    <p><i class="fas fa-phone"></i> +234 812 345 6789</p>
                    <p><i class="fas fa-envelope"></i> info@auchiprojects.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Auchi Projects. All rights reserved.</p>
            </div>
        </div>
    </footer>
    

    <script src="js/main.js"></script>
    <script>
    // ==================== ANIMATE COUNTERS ====================
    document.addEventListener('DOMContentLoaded', function() {
        animateCounters();
        
        

    function animateCounters() {
        const counters = document.querySelectorAll('.count');
        if (counters.length === 0) return;
        
        counters.forEach(counter => {
            const target = parseInt(counter.textContent) || 0;
            if (target === 0) return;
            
            let current = 0;
            const duration = 1500;
            const increment = target / (duration / 16);
            
            counter.textContent = '0';
            
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = Math.floor(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target;
                }
            };
            
            updateCounter();
        });
    }
    
    // Test the Security House image specifically
    setTimeout(() => {
        const securityHouseImg = document.querySelector('img[alt*="Security House"]');
        if (securityHouseImg) {
            console.log('Security House image src:', securityHouseImg.src);
        }
    }, 1000);
    </script>
    <!-- Add this right before </body> -->
<!-- <div style="position: fixed; bottom: 10px; right: 10px; background: white; padding: 10px; border: 1px solid #ccc; z-index: 1000;">
    <h4>Quick Image Test:</h4>
    <p>Testing Security House image:</p>
    <img src="project_images/project_1768314461_6966565dd0bd0.jpg" 
         alt="Test" 
         style="width: 100px; height: 60px; object-fit: cover;"
         onload="console.log('Quick test: Image loaded!')"
         onerror="console.error('Quick test: Image failed!')">
</div> -->
</body>
</html>