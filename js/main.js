// Home Page Featured Projects Loader
document.addEventListener('DOMContentLoaded', function() {
    loadFeaturedProjects();
    
    function loadFeaturedProjects() {
        const featuredContainer = document.getElementById('featuredProjects');
        if (!featuredContainer) {
            console.log('Featured projects container not found');
            return;
        }
        
        // Show loading state
        featuredContainer.innerHTML = `
            <div class="loading" style="grid-column: 1/-1; text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading featured projects...</p>
            </div>
        `;
        
        // Fetch featured projects from API
        fetch('api/get_featured_projects.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.projects) {
                    displayFeaturedProjects(data.projects, featuredContainer);
                } else {
                    showError(featuredContainer, data.message || 'No projects found');
                }
            })
            .catch(error => {
                console.error('Error loading featured projects:', error);
                // Fallback to sample data
                loadSampleFeaturedProjects(featuredContainer);
            });
    }
    
    function displayFeaturedProjects(projects, container) {
        if (!projects || projects.length === 0) {
            showError(container, 'No featured projects available');
            return;
        }
        
        container.innerHTML = projects.map(project => {
            // Format dates
            const formatDate = (dateString) => {
                if (!dateString || dateString === '0000-00-00') return 'Not set';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    year: 'numeric' 
                });
            };
            
            const startDate = formatDate(project.start_date);
            const completionDate = formatDate(project.expected_completion);
            
            // Get budget (already formatted in PHP)
            const budget = project.budget || 'Not specified';
            
            return `
                <div class="project-card animate__animated">
                    <div class="project-image">
                        <div class="project-status ${project.status}">${project.status}</div>
                        <div class="progress-bar">
                            <div class="progress" style="width: ${project.progress}%"></div>
                        </div>
                    </div>
                    <div class="project-content">
                        <h3>${project.title || 'Untitled Project'}</h3>
                        <p>${project.description || 'No description available.'}</p>
                        <div class="project-details">
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>${project.lga_name || ''}, ${project.state_name || ''}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>${budget}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>${startDate} - ${completionDate}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-hard-hat"></i>
                                <span>${project.contractor || 'Not specified'}</span>
                            </div>
                        </div>
                        
                    </div>
                </div>
            `;
        }).join('');
        
        // Add animations
        setTimeout(() => {
            document.querySelectorAll('.project-card').forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        }, 100);
    }
    
    function loadSampleFeaturedProjects(container) {
        console.log('Loading sample featured projects');
        
        const sampleProjects = [
            {
                id: 1,
                title: "Auchi Modern Market",
                description: "Construction of a modern market with 500 shops and parking facilities",
                state_name: "Edo",
                lga_name: "Etsako West",
                status: "ongoing",
                progress: 65,
                budget: "₦250,000,000.00",
                contractor: "Raycon Construction Ltd",
                start_date: "2023-05-01",
                expected_completion: "2024-08-01"
            },
            {
                id: 2,
                title: "Auchi Polytechnic Hostel Blocks",
                description: "Construction of 4 new hostel blocks for students",
                state_name: "Edo",
                lga_name: "Etsako West",
                status: "ongoing",
                progress: 45,
                budget: "₦180,000,000.00",
                contractor: "Benjaminex Nigeria Ltd",
                start_date: "2023-03-01",
                expected_completion: "2024-02-01"
            },
            {
                id: 3,
                title: "Etsako West Council Secretariat",
                description: "New administrative building for local government operations",
                state_name: "Edo",
                lga_name: "Etsako West",
                status: "completed",
                progress: 100,
                budget: "₦350,000,000.00",
                contractor: "State Govt Contractors",
                start_date: "2022-11-01",
                expected_completion: "2023-12-01"
            }
        ];
        
        displayFeaturedProjects(sampleProjects, container);
    }
    
    function showError(container, message) {
        container.innerHTML = `
            <div class="no-projects" style="grid-column: 1/-1; text-align: center; padding: 40px;">
                <i class="fas fa-exclamation-circle"></i>
                <h3>Unable to load projects</h3>
                <p>${message}</p>
                <button onclick="loadFeaturedProjects()" class="btn-small" style="margin-top: 15px;">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        `;
    }

        // Add modal functionality for home page
    function initHomePageModal() {
        // Add click event listeners to View Details buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-details-btn')) {
                const button = e.target.closest('.view-details-btn');
                const projectId = button.getAttribute('data-project-id');
            }
        });
    }
    
    // Initialize modal functionality
    initHomePageModal();
});

