<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auchi Building Projects | Interactive Map</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/map.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.fullscreen@2.0.0/Control.FullScreen.css" />
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.html" class="logo">
                <i class="fas fa-building"></i>
                <span>Auchi</span>Projects
            </a>
            <div class="nav-links">
                <a href="index.html">Home</a>
                <a href="projects.html">List View</a>
                <a href="projects-map.html" class="active">Map View</a>
                <a href="index.html#about">About</a>
                <a href="admin/login.html" class="admin-btn">
                    <i class="fas fa-lock"></i> Admin
                </a>
            </div>
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Projects Map View -->
    <section class="projects-map-view">
        <div class="container">
            <div class="section-header">
                <h2>Projects Interactive Map</h2>
                <p>Explore building projects in Auchi and surrounding areas on the map</p>
            </div>
            
            <div class="map-toggle">
                <a href="projects.html" class="toggle-btn">
                    <i class="fas fa-list"></i> List View
                </a>
                <button class="toggle-btn active">
                    <i class="fas fa-map"></i> Map View
                </button>
            </div>
            
            <div class="map-container">
                <div id="projectsMap"></div>
                <div class="map-controls">
                    <button class="map-btn" id="locateBtn" title="Locate me">
                        <i class="fas fa-location-arrow"></i>
                    </button>
                    <button class="map-btn" id="zoomInBtn" title="Zoom in">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="map-btn" id="zoomOutBtn" title="Zoom out">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button class="map-btn" id="fullscreenBtn" title="Fullscreen">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div class="map-search">
                    <input type="text" class="map-search-box" id="mapSearch" 
                           placeholder="Search location...">
                </div>
                <div class="map-legend">
                    <h4>Project Status</h4>
                    <div class="legend-item">
                        <div class="legend-color planned"></div>
                        <span>Planned</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color ongoing"></div>
                        <span>Ongoing</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color completed"></div>
                        <span>Completed</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color delayed"></div>
                        <span>Delayed</span>
                    </div>
                </div>
            </div>
            
            <div class="map-stats">
                <div class="stat-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3 id="visibleProjects">0</h3>
                    <p>Projects Visible</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-ruler-combined"></i>
                    <h3 id="mapArea">0 km²</h3>
                    <p>Area Covered</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-crosshairs"></i>
                    <h3 id="coordinates">0,0</h3>
                    <p>Map Center</p>
                </div>
            </div>
            
            <div class="filter-controls">
                <h3>Filter Projects</h3>
                <div class="filter-row">
                    <select id="filterStatus" class="filter-select">
                        <option value="">All Status</option>
                        <option value="planned">Planned</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="delayed">Delayed</option>
                    </select>
                    
                    <select id="filterState" class="filter-select">
                        <option value="">All States</option>
                        <option value="1">Edo State</option>
                        <option value="2">Lagos</option>
                        <option value="3">Abuja</option>
                    </select>
                    
                    <button class="btn-primary" id="applyMapFilters">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button class="btn-secondary" id="resetMapFilters">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <!-- Footer content same as before -->
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.fullscreen@2.0.0/Control.FullScreen.js"></script>
    <script src="js/map.js"></script>
    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the map
            const map = new ProjectMap('projectsMap', {
                center: [7.0675, 6.2676],
                zoom: 13
            });
            
            // Load projects
            fetch('api/get_projects.php')
                .then(response => response.json())
                .then(projects => {
                    map.addProjects(projects);
                    updateStats(projects);
                });
            
            // Update stats when map moves
            map.map.on('moveend', function() {
                updateVisibleStats();
            });
            
            // Filter controls
            document.getElementById('applyMapFilters').addEventListener('click', function() {
                const status = document.getElementById('filterStatus').value;
                const state = document.getElementById('filterState').value;
                
                let url = 'api/get_projects.php?';
                const params = new URLSearchParams();
                
                if (status) params.append('status', status);
                if (state) params.append('state_id', state);
                
                fetch(url + params.toString())
                    .then(response => response.json())
                    .then(projects => {
                        map.addProjects(projects);
                        updateStats(projects);
                    });
            });
            
            document.getElementById('resetMapFilters').addEventListener('click', function() {
                document.getElementById('filterStatus').value = '';
                document.getElementById('filterState').value = '';
                
                fetch('api/get_projects.php')
                    .then(response => response.json())
                    .then(projects => {
                        map.addProjects(projects);
                        updateStats(projects);
                    });
            });
            
            function updateStats(projects) {
                document.getElementById('visibleProjects').textContent = projects.length;
                
                // Calculate approximate area (simplified)
                if (projects.length > 0) {
                    const bounds = map.getBounds();
                    const area = calculateArea(bounds);
                    document.getElementById('mapArea').textContent = area.toFixed(1) + ' km²';
                }
                
                const center = map.getCenter();
                document.getElementById('coordinates').textContent = 
                    center.lat.toFixed(4) + ', ' + center.lng.toFixed(4);
            }
            
            function updateVisibleStats() {
                const bounds = map.getBounds();
                const visibleProjects = map.markers.filter(marker => {
                    const lat = marker.project.latitude;
                    const lng = marker.project.longitude;
                    return lat >= bounds.south && lat <= bounds.north && 
                           lng >= bounds.west && lng <= bounds.east;
                });
                
                document.getElementById('visibleProjects').textContent = visibleProjects.length;
                const center = map.getCenter();
                document.getElementById('coordinates').textContent = 
                    center.lat.toFixed(4) + ', ' + center.lng.toFixed(4);
            }
            
            function calculateArea(bounds) {
                // Simplified area calculation
                const latDiff = bounds.north - bounds.south;
                const lngDiff = bounds.east - bounds.west;
                const latKm = latDiff * 111; // 1 degree latitude ≈ 111 km
                const lngKm = lngDiff * 111 * Math.cos(deg2rad((bounds.north + bounds.south) / 2));
                return latKm * lngKm;
            }
            
            function deg2rad(deg) {
                return deg * (Math.PI / 180);
            }
        });
    </script>
</body>
</html>