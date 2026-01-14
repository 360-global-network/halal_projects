<?php
require_once 'config.php';

class ProjectManager {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
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
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
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
        
        if ($stmt === false) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
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
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $states[] = $row;
            }
        }
        
        return $states;
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
            if ($result) {
                $row = $result->fetch_assoc();
                return $row['count'];
            }
            return 0;
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
    
    // Get unique LGAs count
    public function getUniqueLGAsCount() {
        $query = "SELECT COUNT(DISTINCT lga_id) as count FROM projects";
        $result = $this->conn->query($query);
        
        if ($result && $row = $result->fetch_assoc()) {
            return $row['count'];
        }
        
        return 0;
    }
    
    // Get total investment
    public function getTotalInvestment() {
        $query = "SELECT SUM(budget) as total FROM projects";
        $result = $this->conn->query($query);
        
        if ($result && $row = $result->fetch_assoc()) {
            return $row['total'] ? (float)$row['total'] : 0;
        }
        
        return 0;
    }
    
    //Get Project Image
    function project_image_section($project) {
    echo '<!-- Image Section -->
    <div class="project-image">';
    
    // Check if image exists
    if (!empty($project['image'])) {
        echo '<img src="project_images/' . htmlspecialchars($project['image']) . '" alt="' . htmlspecialchars($project['title']) . '">';
    } else {
        echo '<div class="no-image">No Image Available</div>';
    }
    
    echo '</div>';
}

    // Get all stats in one query (more efficient)
    public function getAllStats() {
        $query = "SELECT 
                    SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as ongoing,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    COUNT(*) as total,
                    COUNT(DISTINCT lga_id) as lgas_covered,
                    SUM(budget) as total_investment
                  FROM projects";
        
        $result = $this->conn->query($query);
        
        if ($result && $row = $result->fetch_assoc()) {
            return [
                'ongoing' => (int)$row['ongoing'],
                'completed' => (int)$row['completed'],
                'pending' => (int)$row['pending'],
                'total' => (int)$row['total'],
                'lgas_covered' => (int)$row['lgas_covered'],
                'total_investment' => (float)$row['total_investment'] ?: 0
            ];
        }
        
        return [
            'ongoing' => 0,
            'completed' => 0,
            'pending' => 0,
            'total' => 0,
            'lgas_covered' => 0,
            'total_investment' => 0
        ];
    }
    


/**
 * Handle project image upload
 * 
 * @param array $file $_FILES array entry
 * @param int $projectId Project ID
 * @return array Result array with success/error info
 */
function handleProjectImageUpload($file, $projectId) {
    $result = [
        'success' => false,
        'message' => '',
        'errors' => [],
        'filename' => ''
    ];
    
    // Validate project ID
    if (!$projectId) {
        $result['errors'][] = "Invalid project ID.";
        return $result;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['errors'][] = getUploadError($file['error']);
        return $result;
    }
    
    // Validate file size (2MB max)
    $maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if ($file['size'] > $maxSize) {
        $result['errors'][] = "File size exceeds 2MB limit.";
        return $result;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $result['errors'][] = "Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.";
        return $result;
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = 'project_images/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'project_' . $projectId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Remove old image if exists
    $oldImage = getProjectImage($projectId);
    if ($oldImage && file_exists($uploadDir . $oldImage)) {
        unlink($uploadDir . $oldImage);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        if (updateProjectImage($projectId, $filename)) {
            $result['success'] = true;
            $result['message'] = "Image uploaded successfully!";
            $result['filename'] = $filename;
            
            // Create thumbnail (optional)
            createImageThumbnail($filepath, $uploadDir . 'thumbs/' . $filename, 200, 150);
        } else {
            $result['errors'][] = "Failed to update database.";
            // Remove the uploaded file if DB update failed
            unlink($filepath);
        }
    } else {
        $result['errors'][] = "Failed to upload file.";
    }
    
    return $result;
}

/**
 * Get upload error message
 */
function getUploadError($errorCode) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
    ];
    
    return $errors[$errorCode] ?? 'Unknown upload error.';
}

/**
 * Update project image in database
 */
function updateProjectImage($projectId, $filename) {
    global $pdo; // Your PDO connection
    
    try {
        $stmt = $pdo->prepare("UPDATE projects SET image = ? WHERE id = ?");
        return $stmt->execute([$filename, $projectId]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get project image filename
 */
function getProjectImage($projectId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT image FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['image'] ?? null;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

/**
 * Remove project image
 */
function removeProjectImage($projectId) {
    $uploadDir = 'project_images/';
    $image = getProjectImage($projectId);
    
    if ($image && file_exists($uploadDir . $image)) {
        unlink($uploadDir . $image);
    }
    
    // Clear from database
    updateProjectImage($projectId, null);
    
    return true;
}

/**
 * Create image thumbnail (optional)
 */
function createImageThumbnail($sourcePath, $destPath, $width, $height) {
    if (!file_exists(dirname($destPath))) {
        mkdir(dirname($destPath), 0755, true);
    }
    
    $info = getimagesize($sourcePath);
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    $thumb = imagecreatetruecolor($width, $height);
    
    // Preserve transparency for PNG and GIF
    if ($mime == 'image/png' || $mime == 'image/gif') {
        imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
    
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($thumb, $destPath, 85);
            break;
        case 'image/png':
            imagepng($thumb, $destPath, 8);
            break;
        case 'image/gif':
            imagegif($thumb, $destPath);
            break;
        case 'image/webp':
            imagewebp($thumb, $destPath, 85);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return true;
}
    // Check database connection
    public function checkConnection() {
        return $this->conn && !$this->conn->connect_error;
    }
    
} // END OF CLASS - MAKE SURE THIS CLOSING BRACE EXISTS
?>