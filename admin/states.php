<?php
// Fix the path to config.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../admin_auth.php';
require_once __DIR__ . '/../project_manager.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

class ProjectManager {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // Get database connection (FIXED - ADDED THIS METHOD)
    public function getConnection() {
        return $this->conn;
    }
    
    // Get all projects
    public function getAllProjects() {
        $query = "SELECT p.*, s.name as state_name, l.name as lga_name 
                  FROM projects p 
                  JOIN states s ON p.state_id = s.id 
                  JOIN lgas l ON p.lga_id = l.id 
                  ORDER BY p.created_at DESC";
        
        $result = $this->conn->query($query);
        $projects = [];
        
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        return $projects;
    }
    
    // Get project by ID
    public function getProjectById($id) {
        $stmt = $this->conn->prepare("SELECT p.*, s.name as state_name, l.name as lga_name 
                                      FROM projects p 
                                      JOIN states s ON p.state_id = s.id 
                                      JOIN lgas l ON p.lga_id = l.id 
                                      WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    // Add new project
    public function addProject($data) {
        $stmt = $this->conn->prepare("INSERT INTO projects 
                                    (title, description, state_id, lga_id, address, 
                                     latitude, longitude, location_type,
                                     contractor, budget, start_date, expected_completion, 
                                     status, image_url) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssiissdsssdsss", 
            $data['title'], $data['description'], $data['state_id'], $data['lga_id'],
            $data['address'], $data['latitude'], $data['longitude'], $data['location_type'],
            $data['contractor'], $data['budget'], $data['start_date'],
            $data['expected_completion'], $data['status'], $data['image_url']
        );
        
        return $stmt->execute();
    }
    
    // Update project
    public function updateProject($id, $data) {
        $stmt = $this->conn->prepare("UPDATE projects SET 
                                    title = ?, description = ?, state_id = ?, lga_id = ?, 
                                    address = ?, latitude = ?, longitude = ?, location_type = ?,
                                    contractor = ?, budget = ?, start_date = ?, 
                                    expected_completion = ?, status = ?, image_url = ? 
                                    WHERE id = ?");
        
        $stmt->bind_param("ssiissdsssdsssi", 
            $data['title'], $data['description'], $data['state_id'], $data['lga_id'],
            $data['address'], $data['latitude'], $data['longitude'], $data['location_type'],
            $data['contractor'], $data['budget'], $data['start_date'],
            $data['expected_completion'], $data['status'], $data['image_url'], $id
        );
        
        return $stmt->execute();
    }
    
    // Delete project
    public function deleteProject($id) {
        $stmt = $this->conn->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Get all states
    public function getAllStates() {
        $result = $this->conn->query("SELECT * FROM states ORDER BY name");
        $states = [];
        
        while ($row = $result->fetch_assoc()) {
            $states[] = $row;
        }
        
        return $states;
    }
    
    // Get state by ID
    public function getStateById($state_id) {
        $stmt = $this->conn->prepare("SELECT * FROM states WHERE id = ?");
        $stmt->bind_param("i", $state_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Get LGAs by state ID
    public function getLGAsByState($state_id) {
        $stmt = $this->conn->prepare("SELECT * FROM lgas WHERE state_id = ? ORDER BY name");
        $stmt->bind_param("i", $state_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $lgas = [];
        while ($row = $result->fetch_assoc()) {
            $lgas[] = $row;
        }
        
        return $lgas;
    }
    
    // Get projects near location
    public function getProjectsNearLocation($lat, $lng, $radius_km = 10) {
        // Earth's radius in kilometers
        $earth_radius = 6371;
        
        // Calculate bounding coordinates
        $max_lat = $lat + rad2deg($radius_km / $earth_radius);
        $min_lat = $lat - rad2deg($radius_km / $earth_radius);
        $max_lng = $lng + rad2deg($radius_km / $earth_radius / cos(deg2rad($lat)));
        $min_lng = $lng - rad2deg($radius_km / $earth_radius / cos(deg2rad($lat)));
        
        $query = "SELECT p.*, s.name as state_name, l.name as lga_name,
                  (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                  cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                  sin(radians(latitude)))) AS distance 
                  FROM projects p 
                  JOIN states s ON p.state_id = s.id 
                  JOIN lgas l ON p.lga_id = l.id 
                  WHERE latitude BETWEEN ? AND ? 
                  AND longitude BETWEEN ? AND ?
                  HAVING distance < ? 
                  ORDER BY distance 
                  LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("dddddddd", 
            $lat, $lng, $lat,
            $min_lat, $max_lat, $min_lng, $max_lng,
            $radius_km
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        return $projects;
    }
    
    // Get projects by map bounds
    public function getProjectsByBounds($north, $south, $east, $west) {
        $query = "SELECT p.*, s.name as state_name, l.name as lga_name 
                  FROM projects p 
                  JOIN states s ON p.state_id = s.id 
                  JOIN lgas l ON p.lga_id = l.id 
                  WHERE latitude BETWEEN ? AND ? 
                  AND longitude BETWEEN ? AND ?
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("dddd", $south, $north, $west, $east);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        return $projects;
    }
    
    // Get project coordinates
    public function getProjectCoordinates($project_id) {
        $stmt = $this->conn->prepare("SELECT latitude, longitude FROM projects WHERE id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return [
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude']
            ];
        }
        
        return null;
    }
    
    // Count projects by status
    public function countProjectsByStatus($status = null) {
        if ($status) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM projects WHERE status = ?");
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'];
        } else {
            $result = $this->conn->query("SELECT COUNT(*) as count FROM projects");
            $row = $result->fetch_assoc();
            return $row['count'];
        }
    }
    
    // Get recent projects
    public function getRecentProjects($limit = 5) {
        $query = "SELECT p.*, s.name as state_name, l.name as lga_name 
                  FROM projects p 
                  JOIN states s ON p.state_id = s.id 
                  JOIN lgas l ON p.lga_id = l.id 
                  ORDER BY p.created_at DESC 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        return $projects;
    }
    
    // Count LGAs for a state
    public function countLGAsByState($state_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM lgas WHERE state_id = ?");
        $stmt->bind_param("i", $state_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    // Count projects for a state
    public function countProjectsByState($state_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM projects WHERE state_id = ?");
        $stmt->bind_param("i", $state_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    // Count projects for an LGA
    public function countProjectsByLGA($lga_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM projects WHERE lga_id = ?");
        $stmt->bind_param("i", $lga_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    // Search projects
    public function searchProjects($keyword) {
        $keyword = "%{$keyword}%";
        $stmt = $this->conn->prepare("
            SELECT p.*, s.name as state_name, l.name as lga_name 
            FROM projects p 
            JOIN states s ON p.state_id = s.id 
            JOIN lgas l ON p.lga_id = l.id 
            WHERE p.title LIKE ? OR p.description LIKE ? OR p.address LIKE ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        return $projects;
    }
    
    // Get project statistics for dashboard
    public function getDashboardStats() {
        $stats = [];
        
        // Total projects
        $stats['total'] = $this->countProjectsByStatus();
        
        // Projects by status
        $stats['ongoing'] = $this->countProjectsByStatus('ongoing');
        $stats['completed'] = $this->countProjectsByStatus('completed');
        $stats['planned'] = $this->countProjectsByStatus('planned');
        $stats['delayed'] = $this->countProjectsByStatus('delayed');
        
        // Budget statistics
        $result = $this->conn->query("
            SELECT 
                SUM(budget) as total_budget,
                AVG(budget) as avg_budget,
                MIN(budget) as min_budget,
                MAX(budget) as max_budget
            FROM projects 
            WHERE budget IS NOT NULL
        ");
        $budgetStats = $result->fetch_assoc();
        
        $stats['total_budget'] = $budgetStats['total_budget'] ?? 0;
        $stats['avg_budget'] = $budgetStats['avg_budget'] ?? 0;
        $stats['min_budget'] = $budgetStats['min_budget'] ?? 0;
        $stats['max_budget'] = $budgetStats['max_budget'] ?? 0;
        
        return $stats;
    }
    
    // Get projects by status
    public function getProjectsByStatus($status) {
        $stmt = $this->conn->prepare("
            SELECT p.*, s.name as state_name, l.name as lga_name 
            FROM projects p 
            JOIN states s ON p.state_id = s.id 
            JOIN lgas l ON p.lga_id = l.id 
            WHERE p.status = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        return $projects;
    }
    
    // Get projects by date range
    public function getProjectsByDateRange($start_date, $end_date) {
        $stmt = $this->conn->prepare("
            SELECT p.*, s.name as state_name, l.name as lga_name 
            FROM projects p 
            JOIN states s ON p.state_id = s.id 
            JOIN lgas l ON p.lga_id = l.id 
            WHERE p.start_date BETWEEN ? AND ?
            ORDER BY p.start_date DESC
        ");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        return $projects;
    }
    
    // Add new state
    public function addState($state_name) {
        $stmt = $this->conn->prepare("INSERT INTO states (name) VALUES (?)");
        $stmt->bind_param("s", $state_name);
        return $stmt->execute();
    }
    
    // Add new LGA
    public function addLGA($state_id, $lga_name) {
        $stmt = $this->conn->prepare("INSERT INTO lgas (state_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $state_id, $lga_name);
        return $stmt->execute();
    }
    
    // Update LGA
    public function updateLGA($lga_id, $lga_name) {
        $stmt = $this->conn->prepare("UPDATE lgas SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $lga_name, $lga_id);
        return $stmt->execute();
    }
    
    // Delete LGA
    public function deleteLGA($lga_id) {
        $stmt = $this->conn->prepare("DELETE FROM lgas WHERE id = ?");
        $stmt->bind_param("i", $lga_id);
        return $stmt->execute();
    }
}
?>