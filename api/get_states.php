<?php
require_once '../config.php';
require_once '../project_manager.php';

header('Content-Type: application/json');

$projectManager = new ProjectManager();
$states = $projectManager->getAllStates();

echo json_encode($states);
?>