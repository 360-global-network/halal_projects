<?php
require_once '../config.php';
require_once '../project_manager.php';

header('Content-Type: application/json');

$projectManager = new ProjectManager();

// Get filter parameters from GET request
$state_id = isset($_GET['state_id']) ? (int)$_GET['state_id'] : null;
$lga_id = isset($_GET['lga_id']) ? (int)$_GET['lga_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Get all projects or filtered projects
if ($state_id || $lga_id || $status) {
    $projects = [];
    
    // Use PDO instead of mysqli for consistency with your ProjectManager
    $db = $projectManager->getDB();
    
    $query = "SELECT p.*, s.name as state_name, l.name as lga_name 
              FROM projects p 
              JOIN states s ON p.state_id = s.id 
              JOIN lgas l ON p.lga_id = l.id 
              WHERE 1=1";
    
    $params = [];
    
    if ($state_id) {
        $query .= " AND p.state_id = ?";
        $params[] = $state_id;
    }
    
    if ($lga_id) {
        $query .= " AND p.lga_id = ?";
        $params[] = $lga_id;
    }
    
    if ($status) {
        $query .= " AND p.status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    $projects = $projectManager->getAllProjects();
}

// Format the response - FIXED: Don't use $this in closure
$response = [
    'success' => true,
    'count' => count($projects),
    'projects' => array_map(function($project) {
        // Calculate progress - don't use $this
        $progress = 0;
        switch($project['status']) {
            case 'planned': $progress = 10; break;
            case 'ongoing': $progress = rand(30, 80); break;
            case 'completed': $progress = 100; break;
            case 'delayed': $progress = rand(20, 50); break;
            default: $progress = 0;
        }
        
        return [
            'id' => $project['id'],
            'title' => $project['title'],
            'description' => $project['description'],
            'state' => $project['state_name'] ?? '',
            'lga' => $project['lga_name'] ?? '',
            'status' => $project['status'],
            'progress' => $progress,
            'budget' => $project['budget'] ? '₦' . number_format($project['budget'], 2) : 'N/A',
            'contractor' => $project['contractor'] ?: 'Not assigned',
            'startDate' => $project['start_date'] ? date('M Y', strtotime($project['start_date'])) : 'Not started',
            'completionDate' => $project['expected_completion'] ? date('M Y', strtotime($project['expected_completion'])) : 'Not set',
            'image_url' => $project['image_url'] ?? null,
            'address' => $project['address'] ?? '',
            'state_id' => $project['state_id'] ?? null,
            'lga_id' => $project['lga_id'] ?? null
        ];
    }, $projects)
];

echo json_encode($response);
?>