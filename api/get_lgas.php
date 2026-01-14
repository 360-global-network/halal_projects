<?php
require_once '../config.php';
require_once '../project_manager.php';

header('Content-Type: application/json');

$state_id = $_GET['state_id'] ?? 0;

if (!$state_id) {
    echo json_encode([]);
    exit();
}

$projectManager = new ProjectManager();
$lgas = $projectManager->getLGAsByState($state_id);

echo json_encode($lgas);
?>