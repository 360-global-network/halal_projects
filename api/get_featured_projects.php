<?php
// api/get_featured_projects.php
require_once '../config.php';
require_once '../project_manager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $projectManager = new ProjectManager();
    
    // Get recent projects (you can modify this to get featured projects)
    $projects = $projectManager->getRecentProjects(6); // Get 6 most recent projects
    
    // Format the response
    $formattedProjects = [];
    foreach ($projects as $project) {
        $formattedProjects[] = [
            'id' => $project['id'],
            'title' => $project['title'],
            'description' => $project['description'] ? substr($project['description'], 0, 100) . '...' : 'No description',
            'state_name' => $project['state_name'],
            'lga_name' => $project['lga_name'],
            'status' => $project['status'],
            'progress' => $project['progress'] ? (int)$project['progress'] : 0,
            'budget' => $project['budget'] ? '₦' . number_format($project['budget'], 2) : 'Not specified',
            'contractor' => $project['contractor'] ?: 'Not specified',
            'start_date' => $project['start_date'],
            'expected_completion' => $project['expected_completion']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'projects' => $formattedProjects
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading featured projects: ' . $e->getMessage()
    ]);
}
?>