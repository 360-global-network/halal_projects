<?php
require_once '../config.php';
require_once '../project_manager.php';

header('Content-Type: application/json');

if (isset($_GET['state_id'])) {
    $projectManager = new ProjectManager();
    $lgas = $projectManager->getLGAsByState($_GET['state_id']);
    echo json_encode($lgas);
} else {
    echo json_encode([]);
}
?>