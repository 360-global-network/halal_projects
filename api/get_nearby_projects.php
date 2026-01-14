<?php
require_once '../config.php';
require_once '../project_manager.php';

header('Content-Type: application/json');

$projectManager = new ProjectManager();

// Get parameters
$lat = $_GET['lat'] ?? 7.0675; // Default to Auchi
$lng = $_GET['lng'] ?? 6.2676;
$radius = $_GET['radius'] ?? 10; // Default 10km radius

if (!is_numeric($lat) || !is_numeric($lng) || !is_numeric($radius)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

$projects = $projectManager->getProjectsNearLocation(
    (float)$lat,
    (float)$lng,
    (float)$radius
);

// Format response
$response = [
    'center' => ['lat' => (float)$lat, 'lng' => (float)$lng],
    'radius' => (float)$radius,
    'count' => count($projects),
    'projects' => array_map(function($project) {
        return [
            'id' => $project['id'],
            'title' => $project['title'],
            'description' => $project['description'],
            'latitude' => (float)$project['latitude'],
            'longitude' => (float)$project['longitude'],
            'state_name' => $project['state_name'],
            'lga_name' => $project['lga_name'],
            'status' => $project['status'],
            'distance' => round($project['distance'], 2)
        ];
    }, $projects)
];

echo json_encode($response);
?>