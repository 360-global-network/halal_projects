<?php
// edit_project.php
require_once '../config.php';
require_once '../admin_auth.php';
require_once '../project_manager.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$projectManager = new ProjectManager();
$states = $projectManager->getAllStates();

$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project = $projectManager->getProjectById($projectId);

if (!$project) {
    header('Location: projects.php');
    exit();
}

$message = '';
$messageType = '';
$uploadDir = '../project_images/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 2 * 1024 * 1024;

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_project'])) {
        // Handle project data update
        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'state_id' => $_POST['state_id'],
            'lga_id' => $_POST['lga_id'],
            'address' => $_POST['address'],
            'contractor' => $_POST['contractor'],
            'budget' => $_POST['budget'],
            'start_date' => $_POST['start_date'],
            'expected_completion' => $_POST['expected_completion'],
            'status' => $_POST['status'],
            'id' => $projectId
        ];
        
        if ($projectManager->updateProject($data)) {
            $message = 'Project updated successfully!';
            $messageType = 'success';
            $project = $projectManager->getProjectById($projectId); // Refresh project data
        } else {
            $message = 'Error updating project.';
            $messageType = 'error';
        }
    }
    
    // Handle image upload separately
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['project_image'];
        
        if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= $maxFileSize) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (in_array($mimeType, $allowedTypes)) {
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Delete old image if not default
                if ($project['image_url'] !== 'default-project.jpg' && 
                    file_exists($uploadDir . $project['image_url'])) {
                    unlink($uploadDir . $project['image_url']);
                }
                
                // Generate new filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $imageFileName = 'project_' . $projectId . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $imageFileName;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    if ($projectManager->updateProjectImage($projectId, $imageFileName)) {
                        $message .= ($message ? ' ' : '') . 'Image updated successfully!';
                        $messageType = 'success';
                        $project = $projectManager->getProjectById($projectId); // Refresh
                    }
                }
            }
        }
    }
    
    // Handle image removal
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == 1) {
        if ($project['image_url'] !== 'default-project.jpg' && 
            file_exists($uploadDir . $project['image_url'])) {
            unlink($uploadDir . $project['image_url']);
        }
        
        if ($projectManager->updateProjectImage($projectId, 'default-project.jpg')) {
            $message .= ($message ? ' ' : '') . 'Image removed successfully!';
            $messageType = 'success';
            $project = $projectManager->getProjectById($projectId); // Refresh
        }
    }
}
?>

<!-- HTML form similar to add_project.php but with edit functionality -->
<!-- Include current image display and remove option -->