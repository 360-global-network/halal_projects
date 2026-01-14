// Projects Page Functionality
document.addEventListener('DOMContentLoaded', function() {
    const stateFilter = document.getElementById('stateFilter');
    const lgaFilter = document.getElementById('lgaFilter');
    const statusFilter = document.getElementById('statusFilter');
    const applyFiltersBtn = document.getElementById('applyFilters');
    const resetFiltersBtn = document.getElementById('resetFilters');
    const projectsGrid = document.getElementById('projectsGrid');
    
    // LGAs data loaded from API
    const lgasByState = {};
    
    // Create modal HTML structure on page load
    createModalHTML();
    
    // Load states and LGAs initially
    loadStatesAndLGAs();
    
    // Load projects initially
    loadProjects();
    
    // Populate LGAs when state is selected
    stateFilter.addEventListener('change', function() {
        const stateId = this.value;
        lgaFilter.innerHTML = '<option value="">All LGAs</option>';
        lgaFilter.disabled = !stateId;
        
        if (stateId && lgasByState[stateId]) {
            lgasByState[stateId].forEach(lga => {
                const option = document.createElement('option');
                option.value = lga.id;
                option.textContent = lga.name;
                lgaFilter.appendChild(option);
            });
        } else if (stateId) {
            // Fetch LGAs for this state
            fetch(`api/get_lgas.php?state_id=${stateId}`)
                .then(response => response.json())
                .then(data => {
                    lgasByState[stateId] = data;
                    data.forEach(lga => {
                        const option = document.createElement('option');
                        option.value = lga.id;
                        option.textContent = lga.name;
                        lgaFilter.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading LGAs:', error);
                });
        }
    });
    
    // Apply filters
    applyFiltersBtn.addEventListener('click', function() {
        loadProjects();
    });
    
    // Reset filters
    resetFiltersBtn.addEventListener('click', function() {
        stateFilter.value = '';
        lgaFilter.innerHTML = '<option value="">Select State First</option>';
        lgaFilter.disabled = true;
        statusFilter.value = '';
        loadProjects();
    });
    
    // Load projects
    function loadProjects(filters = {}) {
        projectsGrid.innerHTML = '<div class="loading">Loading projects...</div>';
        
        // Build query string
        const params = new URLSearchParams();
        
        if (stateFilter.value) params.append('state_id', stateFilter.value);
        if (lgaFilter.value && lgaFilter.value !== '') params.append('lga_id', lgaFilter.value);
        if (statusFilter.value) params.append('status', statusFilter.value);
        
        // Fetch projects from API
        fetch(`api/get_projects.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayProjects(data.projects);
                    updatePagination(data.count);
                } else {
                    projectsGrid.innerHTML = `
                        <div class="no-projects">
                            <i class="fas fa-exclamation-circle"></i>
                            <h3>Error loading projects</h3>
                            <p>Please try again later</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading projects:', error);
                // Fallback to sample data
                displaySampleProjects();
            });
    }
    
    // Display projects
    function displayProjects(projects) {
        if (projects.length === 0) {
            projectsGrid.innerHTML = `
                <div class="no-projects">
                    <i class="fas fa-inbox"></i>
                    <h3>No projects found</h3>
                    <p>Try adjusting your filters</p>
                </div>
            `;
            return;
        }
        
        projectsGrid.innerHTML = projects.map(project => `
            <div class="project-card animate__animated">
                <div class="project-image">
                    <div class="project-status ${project.status}">${project.status}</div>
                    <div class="progress-bar">
                        <div class="progress" style="width: ${project.progress}%"></div>
                    </div>
                </div>
                <div class="project-content">
                    <h3>${project.title}</h3>
                    <p>${project.description}</p>
                    <div class="project-details">
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${project.lga}, ${project.state}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>${project.budget}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>${project.startDate} - ${project.completionDate}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-hard-hat"></i>
                            <span>${project.contractor}</span>
                        </div>
                    </div>
                    <div class="project-actions">
                        <button class="btn-small view-details-btn" data-project-id="${project.id}">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
        
        // Add animations
        document.querySelectorAll('.project-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Add click event listeners to View Details buttons
        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', function() {
                const projectId = this.getAttribute('data-project-id');
                showProjectModal(projectId);
            });
        });
    }
    
    // Fallback to sample projects
    function displaySampleProjects() {
        const sampleProjects = [
            {
                id: 1,
                title: "Auchi Modern Market",
                description: "Construction of a modern market with 500 shops and parking facilities",
                state: "Edo",
                lga: "Etsako West",
                status: "ongoing",
                progress: 65,
                budget: "₦250,000,000",
                contractor: "Raycon Construction Ltd",
                startDate: "May 2023",
                completionDate: "Aug 2024"
            },
            {
                id: 2,
                title: "Auchi Polytechnic Hostel Blocks",
                description: "Construction of 4 new hostel blocks for students",
                state: "Edo",
                lga: "Etsako West",
                status: "ongoing",
                progress: 45,
                budget: "₦180,000,000",
                contractor: "Benjaminex Nigeria Ltd",
                startDate: "Mar 2023",
                completionDate: "Feb 2024"
            },
            {
                id: 3,
                title: "Etsako West Council Secretariat",
                description: "New administrative building for local government operations",
                state: "Edo",
                lga: "Etsako West",
                status: "completed",
                progress: 100,
                budget: "₦350,000,000",
                contractor: "State Govt Contractors",
                startDate: "Nov 2022",
                completionDate: "Dec 2023"
            }
        ];
        
        displayProjects(sampleProjects);
    }
    
    // Load states and LGAs
    function loadStatesAndLGAs() {
        // Load states
        fetch('api/get_states.php')
            .then(response => response.json())
            .then(states => {
                // Clear existing options except the first one
                while (stateFilter.options.length > 1) {
                    stateFilter.remove(1);
                }
                
                // Add new state options
                states.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.id;
                    option.textContent = state.name;
                    stateFilter.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading states:', error);
            });
    }
    
    // Pagination function
    function updatePagination(totalProjects) {
        const pagination = document.getElementById('pagination');
        const itemsPerPage = 6;
        const totalPages = Math.ceil(totalProjects / itemsPerPage);
        
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }
        
        let paginationHTML = '<div class="pagination-controls">';
        
        // Previous button
        paginationHTML += '<button class="pagination-btn" id="prevPage"><i class="fas fa-chevron-left"></i></button>';
        
        // Page numbers (show max 5 pages)
        const currentPage = 1;
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
        
        // Next button
        paginationHTML += '<button class="pagination-btn" id="nextPage"><i class="fas fa-chevron-right"></i></button>';
        paginationHTML += '</div>';
        
        pagination.innerHTML = paginationHTML;
        
        // Add pagination event listeners
        document.getElementById('prevPage').addEventListener('click', () => {
            // Handle previous page
            console.log('Previous page');
        });
        
        document.getElementById('nextPage').addEventListener('click', () => {
            // Handle next page
            console.log('Next page');
        });
        
        document.querySelectorAll('.pagination-btn[data-page]').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = parseInt(this.dataset.page);
                // Load specific page
                console.log('Load page:', page);
            });
        });
    }
    
    // ===== MODAL FUNCTIONS (Inside DOMContentLoaded) =====
    
    function showProjectModal(projectId) {
        // Create modal HTML if it doesn't exist
        if (!document.getElementById('projectModal')) {
            createModalHTML();
        }
        
        const modal = document.getElementById('projectModal');
        const modalContent = document.getElementById('modalProjectContent');
        const modalTitle = document.getElementById('modalProjectTitle');
        
        // Show loading state
        modalContent.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Loading project details...</p>
            </div>
        `;
        modalTitle.textContent = 'Loading...';
        
        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Fetch project details
        fetch(`api/get_project.php?id=${projectId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                if (data.success && data.project) {
                    displayProjectInModal(data.project);
                } else {
                    // Try sample data
                    loadSampleProjectData(projectId);
                }
            })
            .catch(error => {
                console.log('API failed, using sample data:', error);
                loadSampleProjectData(projectId);
            });
    }
    
    function displayProjectInModal(project) {
        const modalTitle = document.getElementById('modalProjectTitle');
        const modalContent = document.getElementById('modalProjectContent');
        
        // Set title
        modalTitle.textContent = project.title || 'Project Details';
        
        // Format date
        const formatDate = (dateString) => {
            if (!dateString || dateString === '0000-00-00') return 'Not specified';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        };
        
        // Format budget
        const formatBudget = (budget) => {
            if (!budget) return 'Not specified';
            const amount = parseFloat(budget);
            if (isNaN(amount)) return budget;
            return '₦' + amount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };
        
        // Create HTML content
        modalContent.innerHTML = `
            <div class="modal-project-header">
                <span class="project-status-badge ${project.status || 'pending'}">
                    ${(project.status || 'pending').toUpperCase()}
                </span>
                <div class="progress-container">
                    <div class="progress-info">
                        <span>Project Progress</span>
                        <span>${project.progress || 0}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${project.progress || 0}%"></div>
                    </div>
                </div>
            </div>
            
            <div class="project-description">
                ${project.description || 'No description available.'}
            </div>
            
            <div class="project-details-grid">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="detail-content">
                        <strong>Location</strong>
                        <p>${project.lga_name || ''}, ${project.state_name || ''}</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-hard-hat"></i>
                    </div>
                    <div class="detail-content">
                        <strong>Contractor</strong>
                        <p>${project.contractor || 'Not specified'}</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="detail-content">
                        <strong>Budget</strong>
                        <p>${formatBudget(project.budget)}</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="detail-content">
                        <strong>Start Date</strong>
                        <p>${formatDate(project.start_date)}</p>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="detail-content">
                        <strong>Completion Date</strong>
                        <p>${formatDate(project.expected_completion)}</p>
                    </div>
                </div>
            </div>
            
            ${project.address ? `
            <div class="location-section">
                <h3><i class="fas fa-map-pin"></i> Address</h3>
                <p class="address-text">${project.address}</p>
                ${project.latitude && project.longitude ? `
                <div class="coordinates">
                    <i class="fas fa-globe-africa"></i>
                    <span>${project.latitude}, ${project.longitude}</span>
                </div>
                ` : ''}
            </div>
            ` : ''}
        `;
    }
    
    function loadSampleProjectData(projectId) {
        const sampleProjects = {
            1: {
                title: "Auchi Modern Market",
                description: "Construction of a modern market with 500 shops and parking facilities. This project aims to provide a modern trading environment for local businesses and improve the economic landscape of the area.",
                state_name: "Edo",
                lga_name: "Etsako West",
                status: "ongoing",
                progress: 65,
                budget: "250000000.00",
                contractor: "Raycon Construction Ltd",
                start_date: "2023-05-01",
                expected_completion: "2024-08-01",
                address: "Along Polytechnic Road, Auchi, Edo State"
            },
            2: {
                title: "Auchi Polytechnic Hostel Blocks",
                description: "Construction of 4 new hostel blocks for students with modern amenities including WiFi, study rooms, and recreational facilities.",
                state_name: "Edo",
                lga_name: "Etsako West",
                status: "ongoing",
                progress: 45,
                budget: "180000000.00",
                contractor: "Benjaminex Nigeria Ltd",
                start_date: "2023-03-01",
                expected_completion: "2024-02-01"
            },
            3: {
                title: "Etsako West Council Secretariat",
                description: "New administrative building for local government operations with modern offices, conference rooms, and public service areas.",
                state_name: "Edo",
                lga_name: "Etsako West",
                status: "completed",
                progress: 100,
                budget: "350000000.00",
                contractor: "State Govt Contractors",
                start_date: "2022-11-01",
                expected_completion: "2023-12-01"
            }
        };
        
        if (sampleProjects[projectId]) {
            displayProjectInModal(sampleProjects[projectId]);
        } else {
            document.getElementById('modalProjectContent').innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Project Not Found</h3>
                    <p>The project you're looking for is not available.</p>
                    <button class="btn-small" onclick="window.showProjectModal(1)">View Sample Project</button>
                </div>
            `;
        }
    }
    
    function createModalHTML() {
    // Check if modal already exists
    if (document.getElementById('projectModal')) {
        // Re-setup events in case they were lost
        setupModalEvents();
        return;
    }
    
    const modalHTML = `
        <div id="projectModal" class="modal">
            <div class="modal-overlay"></div>
            <div class="modal-container">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalProjectTitle">Project Details</h2>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="modalProjectContent">
                            <div class="loading-spinner">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p>Loading project details...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Add event listeners
    setupModalEvents();
}
    function setupModalEvents() {
    const modal = document.getElementById('projectModal');
    if (!modal) return;
    
    const closeBtn = modal.querySelector('.close-modal');
    const overlay = modal.querySelector('.modal-overlay');
    
    // Remove any existing event listeners first
    if (closeBtn) {
        closeBtn.replaceWith(closeBtn.cloneNode(true));
    }
    if (overlay) {
        overlay.replaceWith(overlay.cloneNode(true));
    }
    
    // Get fresh references after cloning
    const newCloseBtn = modal.querySelector('.close-modal');
    const newOverlay = modal.querySelector('.modal-overlay');
    
    // Close when X is clicked
    if (newCloseBtn) {
        newCloseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            closeModal();
        });
    }
    
    // Close when overlay is clicked
    if (newOverlay) {
        newOverlay.addEventListener('click', function(e) {
            e.stopPropagation();
            closeModal();
        });
    }
    
    // Close when clicking outside modal content (on the modal itself)
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
}
    
    function closeModal() {
        const modal = document.getElementById('projectModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }
});

// Make modal functions globally available
window.showProjectModal = function(projectId) {
    // Create modal HTML if it doesn't exist
    if (!document.getElementById('projectModal')) {
        // Find and call the createModalHTML function inside DOMContentLoaded
        // This is a workaround - you might need to trigger it differently
        console.log('Modal not created yet');
        return;
    }
    
    const modal = document.getElementById('projectModal');
    const modalContent = document.getElementById('modalProjectContent');
    const modalTitle = document.getElementById('modalProjectTitle');
    
    // Show loading state
    modalContent.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Loading project details...</p>
        </div>
    `;
    modalTitle.textContent = 'Loading...';
    
    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Fetch project details
    fetch(`api/get_project.php?id=${projectId}`)
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (data.success && data.project) {
                // You'll need to make displayProjectInModal global too
                if (window.displayProjectInModal) {
                    window.displayProjectInModal(data.project);
                }
            }
        })
        .catch(error => {
            console.log('API failed:', error);
        });
};