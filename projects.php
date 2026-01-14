<?php
// Quick fix for projects.php
require_once 'config.php';

// Create direct database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get projects directly
    $sql = "SELECT p.*, s.name as state_name, l.name as lga_name 
            FROM projects p
            LEFT JOIN states s ON p.state_id = s.id
            LEFT JOIN lgas l ON p.lga_id = l.id
            ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get states for filter
    $stmt = $pdo->prepare("SELECT * FROM states ORDER BY name");
    $stmt->execute();
    $states = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $projects = [];
    $states = [];
    error_log("Database error: " . $e->getMessage());
}

// ========== PAGINATION ==========
$projectsPerPage = 12;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalPages = ceil(count($projects) / $projectsPerPage);

// Validate current page
if ($currentPage < 1) $currentPage = 1;
if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;

// Slice array for current page
$startIndex = ($currentPage - 1) * $projectsPerPage;
$currentPageProjects = array_slice($projects, $startIndex, $projectsPerPage);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auchi Building Projects | Projects</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/projects.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Project Image Styles */
        .project-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .project-image-container {
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .project-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .project-card:hover .project-image {
            transform: scale(1.05);
        }
        
        .project-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #3498db, #2ecc71);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .project-status-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            z-index: 2;
        }
        
        .status-planned { background: #3498db; }
        .status-ongoing { background: #f39c12; }
        .status-completed { background: #2ecc71; }
        .status-delayed { background: #e74c3c; }
        
        .project-info {
            padding: 20px;
        }
        
        .project-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 18px;
            line-height: 1.4;
        }
        
        .project-description {
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .project-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .project-location {
            display: flex;
            align-items: center;
            color: #34495e;
            font-size: 13px;
        }
        
        .project-location i {
            margin-right: 5px;
            color: #3498db;
        }
        
        .view-details-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .view-details-btn:hover {
            background: #2980b9;
        }
        
        /* Modal Image */
        .modal-project-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        /* Loading and Error States */
        .loading {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px;
            color: #7f8c8d;
            font-size: 18px;
        }
        
        .no-projects {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px;
            color: #7f8c8d;
        }
        
        .no-projects i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

/* Enhanced Loading Animation */
.loading-spinner {
    text-align: center;
    padding: 40px;
}

.loading-spinner i {
    color: #3498db;
    margin-bottom: 15px;
}

/* Pulse animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.pulse {
    animation: pulse 1.5s infinite;
}
        /* FIXED MODAL STYLES */
#projectModal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 9999; /* Very high z-index */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.7);
}

#projectModal .modal-content {
    background-color: #fff;
    margin: 50px auto; /* Center it */
    padding: 0;
    border: 1px solid #888;
    width: 90%;
    max-width: 800px;
    border-radius: 10px;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
    position: relative;
}

#projectModal .modal-header {
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 10px 10px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#projectModal .modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #333;
}

#projectModal .close-modal {
    background: none;
    border: none;
    font-size: 28px;
    font-weight: bold;
    color: #666;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

#projectModal .close-modal:hover {
    color: #000;
}

#projectModal .modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

/* Make sure modal is on top of everything */
#projectModal * {
    box-sizing: border-box;
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
                <a href="index.php">Home</a>
                <a href="projects.php" class="active">Projects</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <a href="admin/login.php" class="admin-btn">
                    <i class="fas fa-lock"></i> Admin
                </a>
            </div>
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Projects Header -->
    <section class="projects-header">
        <div class="container">
            <h1>All Building Projects</h1>
            <p>Browse through all ongoing and completed projects in Auchi and surrounding areas</p>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filters">
        <div class="container">
            <div class="filter-container">
                <div class="filter-group">
                    <label for="stateFilter"><i class="fas fa-map"></i> State</label>
                    <select id="stateFilter" class="filter-select">
                        <option value="">All States</option>
                        <?php foreach ($states as $state): ?>
                        <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="lgaFilter"><i class="fas fa-map-marker-alt"></i> LGA</label>
                    <select id="lgaFilter" class="filter-select" disabled>
                        <option value="">Select State First</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="statusFilter"><i class="fas fa-tasks"></i> Status</label>
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="planned">Planned</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="delayed">Delayed</option>
                    </select>
                </div>
                <button class="btn-primary" id="applyFilters">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <button class="btn-secondary" id="resetFilters">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>
    </section>

    <!-- Projects Grid -->
    <section class="all-projects">
        <div class="container">
            <div class="projects-grid" id="projectsGrid">
                
<?php if (empty($currentPageProjects)): ?>
    <div class="no-projects">
        <i class="fas fa-search"></i>
        <h3>No projects found</h3>
        <p>There are currently no projects in the database.</p>
        <a href="admin/login.php" class="btn-primary">Add Projects</a>
    </div>
<?php else: ?>
    <?php foreach ($currentPageProjects as $project): ?>
                        <?php
                        // Determine status color
                        $statusColors = [
                            'planned' => 'status-planned',
                            'ongoing' => 'status-ongoing',
                            'completed' => 'status-completed',
                            'delayed' => 'status-delayed'
                        ];
                        $statusClass = $statusColors[$project['status']] ?? 'status-planned';
                        
                        // Image URL
                        $imageUrl = !empty($project['image_url']) ? 
                            'project_images/' . htmlspecialchars($project['image_url']) : 
                            'images/default-project.jpg';
                        
                        // Truncate description
                        $description = htmlspecialchars($project['description']);
                        if (strlen($description) > 100) {
                            $description = substr($description, 0, 100) . '...';
                        }
                        ?>
                        
                        <div class="project-card" data-id="<?php echo $project['id']; ?>">
                            <div class="project-image-container">
                                <?php if (!empty($project['image_url'])): ?>
                                    <img src="<?php echo $imageUrl; ?>" 
                                         alt="<?php echo htmlspecialchars($project['title']); ?>" 
                                         class="project-image"
                                         onerror="this.src='images/default-project.jpg'">
                                <?php else: ?>
                                    <div class="project-image-placeholder">
                                        <i class="fas fa-building"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="project-status-badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                            <div class="project-info">
                                <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                                <p class="project-description"><?php echo $description; ?></p>
                                <div class="project-meta">
                                    <div class="project-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($project['lga_name'] ?? 'Unknown Location'); ?></span>
                                    </div>
                                    <button class="view-details-btn" 
        data-id="<?php echo $project['id']; ?>"
        onclick="viewProjectDetails(<?php echo $project['id']; ?>)">
    View Details
</button>>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3><i class="fas fa-building"></i> AuchiProjects</h3>
                    <p>Tracking development projects in Auchi, Edo State, Nigeria.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <a href="index.php">Home</a>
                    <a href="projects.php">Projects</a>
                    <a href="about.php">About</a>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-map-marker-alt"></i> Auchi, Edo State</p>
                    <p><i class="fas fa-envelope"></i> info@auchiprojects.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Auchi Projects. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Project Details Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalProjectTitle">Project Details</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body" id="modalProjectContent">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading project details...</p>
                </div>
            </div>
        </div>
    </div>

 <script>




// Make function globally available
window.viewProjectDetails = function(projectId) {
    console.log('🔍 DEBUG: viewProjectDetails called with ID:', projectId);
    
    // 1. Get modal elements
    const modal = document.getElementById('projectModal');
    const modalContent = document.getElementById('modalProjectContent');
    const modalTitle = document.getElementById('modalProjectTitle');
    
    console.log('🔍 DEBUG - Elements:', {
        modal: modal,
        modalContent: modalContent,
        modalTitle: modalTitle
    });
    
    // 2. Check if elements exist
    if (!modal) {
        console.error('❌ ERROR: Modal element not found!');
        alert('Modal element not found! Check HTML structure.');
        return;
    }
    
    if (!modalContent) {
        console.error('❌ ERROR: Modal content element not found!');
        return;
    }
    
    // 3. Show modal with FORCE
    console.log('🔍 DEBUG: Setting modal display to block');
    modal.style.display = 'block';
    modal.style.opacity = '1';
    modal.style.visibility = 'visible';
    
    // Force modal to be visible
    modal.style.setProperty('display', 'block', 'important');
    
    // 4. Set loading content
    modalTitle.textContent = `Project #${projectId}`;
    modalContent.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #3498db;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Loading project details...</p>
            <p><small>Project ID: ${projectId}</small></p>
        </div>
    `;
    
    // 5. Log modal state
    console.log('🔍 DEBUG: Modal display style:', window.getComputedStyle(modal).display);
    console.log('🔍 DEBUG: Modal visibility:', window.getComputedStyle(modal).visibility);
    console.log('🔍 DEBUG: Modal position:', window.getComputedStyle(modal).position);
    
    // 6. Try to fetch data after a short delay
    setTimeout(() => {
        fetchProjectData(projectId);
    }, 100);
    
    function fetchProjectData(id) {
        console.log('🌐 DEBUG: Fetching project data for ID:', id);
        
        fetch(`api/get_projects.php`)
            .then(response => {
                console.log('📡 DEBUG: Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📦 DEBUG: API data received:', data);
                
                if (data.success && data.projects) {
                    const project = data.projects.find(p => p.id == id);
                    
                    if (project) {
                        console.log('✅ DEBUG: Project found:', project.title);
                        displayProjectData(project);
                    } else {
                        showError(`Project #${id} not found in database`);
                    }
                } else {
                    showError(data.message || 'Invalid API response');
                }
            })
            .catch(error => {
                console.error('❌ DEBUG: Fetch error:', error);
                showError(`Failed to load: ${error.message}`);
            });
    }
  

  // Add this escapeHtml function if you haven't already
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Get the current page URL
function getCurrentProjectUrl(projectId) {
  // Get current URL without query parameters
  const baseUrl = window.location.href.split('?')[0];
  // Add the project ID as a parameter
  return `${baseUrl}?project=${projectId}`;
}

// SIMPLE & RELIABLE SHARE FUNCTIONS
window.shareOnFacebook = function(projectId, projectTitle) {
    console.log('Facebook sharing for:', projectId, projectTitle);
    
    // Create share URL
    const shareUrl = window.location.origin + window.location.pathname + '?project=' + encodeURIComponent(projectId);
    
    // Facebook share URL
    const facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl);
    
    // Open in popup - use setTimeout to bypass popup blockers
    setTimeout(function() {
        window.open(facebookUrl, 'fb-share', 'width=580,height=296');
    }, 100);
};

window.shareOnTwitter = function(projectId, projectTitle) {
    console.log('Twitter sharing for:', projectId, projectTitle);
    
    // Create share URL
    const shareUrl = window.location.origin + window.location.pathname + '?project=' + encodeURIComponent(projectId);
    
    // Twitter share URL
    const twitterUrl = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(shareUrl) + 
                       '&text=' + encodeURIComponent(projectTitle || 'Check this out!');
    
    setTimeout(function() {
        window.open(twitterUrl, 'tw-share', 'width=550,height=420');
    }, 100);
};

window.shareOnWhatsApp = function(projectId, projectTitle) {
    console.log('WhatsApp sharing for:', projectId, projectTitle);
    
    // Create share URL
    const shareUrl = window.location.origin + window.location.pathname + '?project=' + encodeURIComponent(projectId);
    
    // WhatsApp share URL
    const whatsappUrl = 'https://wa.me/?text=' + encodeURIComponent(
        (projectTitle || 'Check this out!') + ' ' + shareUrl
    );
    
    setTimeout(function() {
        window.open(whatsappUrl, 'wa-share', 'width=700,height=600');
    }, 100);
};

window.copyProjectLink = function(projectId) {
    console.log('Copying link for:', projectId);
    
    // Create share URL
    const shareUrl = window.location.origin + window.location.pathname + '?project=' + encodeURIComponent(projectId);
    
    // Try modern clipboard API first
    if (navigator.clipboard) {
        navigator.clipboard.writeText(shareUrl).then(function() {
            alert('✅ Link copied to clipboard!\n\n' + shareUrl);
        }).catch(function() {
            // Fallback
            copyFallback(shareUrl);
        });
    } else {
        // Fallback for older browsers
        copyFallback(shareUrl);
    }
};

function copyFallback(text) {
    // Create temporary input element
    const input = document.createElement('input');
    input.style.position = 'fixed';
    input.style.opacity = 0;
    input.value = text;
    document.body.appendChild(input);
    
    // Select and copy
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        alert('✅ Link copied to clipboard!\n\n' + text);
    } catch (err) {
        // Last resort - show in prompt
        prompt('📋 Please copy this link:', text);
    }
    
    // Clean up
    document.body.removeChild(input);
}
    function displayProjectData(project) {
    console.log('🎨 DEBUG: Displaying project:', project);
    
    modalTitle.textContent = project.title || 'Project Details';
    
    // SIMPLE DISPLAY - Just show basic info
    modalContent.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="color: #2c3e50; margin-top: 0;">${project.title || 'No Title'}</h3>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                <div style="display: flex; margin-bottom: 10px;">
                    <strong style="width: 150px;">Status:</strong>
                    <span style="color: ${getStatusColor(project.status)}">
                        ${(project.status || '').toUpperCase()}
                    </span>
                </div>
                
                <div style="display: flex; margin-bottom: 10px;">
                    <strong style="width: 150px;">Location:</strong>
                    <span>
                        <i class="fas fa-map-marker-alt" style="color: #3498db;"></i>
                        ${project.lga || project.lga_name || 'Unknown'}, ${project.state || project.state_name || 'Unknown'}
                    </span>
                </div>
                
                
                ${project.contractor ? `
                <div style="display: flex; margin-bottom: 10px;">
                    <strong style="width: 150px;">Contractor:</strong>
                    <span>${escapeHtml(project.contractor)}</span>
                </div>` : ''}
                
                ${project.start_date ? `
                <div style="display: flex; margin-bottom: 10px;">
                    <strong style="width: 150px;">Start Date:</strong>
                    <span>${formatDate(project.start_date)}</span>
                </div>` : ''}
                
                ${project.expected_completion ? `
                <div style="display: flex; margin-bottom: 10px;">
                    <strong style="width: 150px;">Completion Date:</strong>
                    <span>${formatDate(project.expected_completion)}</span>
                </div>` : ''}
            </div>
            
            <div style="margin-bottom: 15px;">
                <strong>Description:</strong>
                <p>${project.description || 'No description available.'}</p>
            </div>
            
            <!-- ========== ADD SOCIAL SHARING BUTTONS HERE ========== -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
                <div style="font-weight: bold; margin-bottom: 12px; color: #2c3e50; font-size: 16px;">
                    <i class="fas fa-share-alt" style="margin-right: 8px;"></i>Share this project:
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button onclick="shareOnFacebook(${project.id}, '${escapeHtml(project.title || 'Project')}')"
                            style="background: #3b5998; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </button>
                    
                    <button onclick="shareOnTwitter(${project.id}, '${escapeHtml(project.title || 'Project')}')"
                            style="background: #1da1f2; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                        <i class="fab fa-twitter"></i> Twitter
                    </button>
                    
                    <button onclick="shareOnWhatsApp(${project.id}, '${escapeHtml(project.title || 'Project')}')"
                            style="background: #25D366; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </button>

                       <button onclick="shareOnLinkedIn('${project.id}', '${escapeHtml(project.title || 'Project')}')"
                style="background: #0077b5; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;">
            <i class="fab fa-linkedin-in"></i> LinkedIn
        </button>
                    
                    <button onclick="copyProjectLink(${project.id})"
                            style="background: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                        <i class="fas fa-link"></i> Copy Link
                    </button>
                </div>
                <div style="margin-top: 10px; font-size: 12px; color: #7f8c8d;">
                    <i class="fas fa-info-circle"></i> Share with colleagues or on social media
                </div>
            </div>
            <!-- ========== END SOCIAL SHARING ========== -->
           
            <div style="text-align: center; margin-top: 25px;">
                <button onclick="closeModal()" 
                        style="background: #3498db; color: white; padding: 10px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                    Close Modal
                </button>
            </div>
        </div>
    `;
    
    console.log('✅ DEBUG: Project displayed in modal');
}
    



    function showError(message) {
        console.error('❌ DEBUG: Showing error:', message);
        
        modalContent.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #e74c3c;">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <h3>Error</h3>
                <p>${message}</p>
                <button onclick="closeModal()" 
                        style="background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin-top: 15px; cursor: pointer;">
                    Close
                </button>
            </div>
        `;
    }
    
    function getStatusColor(status) {
        switch(status) {
            case 'planned': return '#3498db';
            case 'ongoing': return '#f39c12';
            case 'completed': return '#2ecc71';
            case 'delayed': return '#e74c3c';
            default: return '#95a5a6';
        }
    }
    
    function formatNumber(num) {
        if (!num) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
};



// Close modal function
window.closeModal = function() {
    console.log('🔒 DEBUG: Closing modal');
    const modal = document.getElementById('projectModal');
    if (modal) {
        modal.style.display = 'none';
        console.log('✅ DEBUG: Modal hidden');
    }
};

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DEBUG: DOM loaded');
    
    // Setup close button
    const closeBtn = document.querySelector('.close-modal');
    const modal = document.getElementById('projectModal');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
        console.log('✅ DEBUG: Close button event listener added');
    }
    
    if (modal) {
        // Close when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        console.log('✅ DEBUG: Modal found and event listeners added');
    }
    
   
    
});

console.log('📜 DEBUG: JavaScript loaded');
</script>


</body>

    <script src="js/main.js"></script>



<script>
// Simple test functions
function testFunction() {
    console.log('✅ testFunction() is working');
    alert('JavaScript is working!');
}

function showModal() {
    const modal = document.getElementById('projectModal');
    if (modal) {
        modal.style.display = 'block';
        document.getElementById('modalProjectTitle').textContent = 'Test Modal';
        document.getElementById('modalProjectContent').innerHTML = '<p>Modal is working!</p>';
        console.log('✅ Modal displayed');
    } else {
        console.error('❌ Modal not found');
        alert('Modal element not found!');
    }
}

async function checkAPI() {
    try {
        const response = await fetch('api/get_projects.php');
        const data = await response.json();
        console.log('API Response:', data);
        alert(`API works! Found ${data.count || 0} projects`);
    } catch (error) {
        console.error('API Error:', error);
        alert('API Error: ' + error.message);
    }
}

// Test if viewProjectDetails exists
console.log('viewProjectDetails exists:', typeof viewProjectDetails);
</script>
</html>