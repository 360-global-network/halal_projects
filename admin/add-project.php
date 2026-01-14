<?php
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

$message = '';
$messageType = '';

// Define upload directory
$uploadDir = '../project_images/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 2 * 1024 * 1024; // 2MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageFileName = 'default-project.jpg'; // Default image
    
    // Handle file upload if provided
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['project_image'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = 'File upload error: ' . getUploadErrorMessage($file['error']);
            $messageType = 'error';
        } 
        // Check file size
        elseif ($file['size'] > $maxFileSize) {
            $message = 'File size exceeds 2MB limit.';
            $messageType = 'error';
        } 
        // Check file type
        else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $message = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP.';
                $messageType = 'error';
            } else {
                // Create upload directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $imageFileName = 'project_' . time() . '_' . uniqid() . '.' . $extension;
                $uploadPath = $uploadDir . $imageFileName;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Success - file uploaded
                    $message = 'Image uploaded successfully. ';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to upload image.';
                    $messageType = 'error';
                    $imageFileName = 'default-project.jpg';
                }
            }
        }
    }
    
    // Only proceed with project creation if no upload errors
    if ($messageType !== 'error' || !isset($_FILES['project_image'])) {
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
            'image_url' => $imageFileName  // Use uploaded filename or default
        ];
        
        if ($projectManager->addProject($data)) {
            $message .= ($message ? ' ' : '') . 'Project added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error adding project. Please try again.';
            $messageType = 'error';
            
            // If we uploaded a new image but project creation failed, delete the image
            if ($imageFileName !== 'default-project.jpg' && file_exists($uploadDir . $imageFileName)) {
                unlink($uploadDir . $imageFileName);
            }
        }
    }
}

// Helper function for upload error messages
function getUploadErrorMessage($errorCode) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Project | Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .image-upload-container {
            margin: 20px 0;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        
        .image-preview {
            max-width: 300px;
            margin-top: 15px;
            display: none;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .upload-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .form-control-file {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            background: white;
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'sidebar.php'; ?>

    <main class="admin-main">
        <?php include 'topbar.php'; ?>

        <div class="admin-content">
            <h1>Add New Project</h1>
            
            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Change form to support file upload -->
            <form method="POST" class="project-form" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="planned">Planned</option>
                            <option value="ongoing" selected>Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="delayed">Delayed</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">State *</label>
                        <select name="state_id" id="stateSelect" class="form-control" required>
                            <option value="">Select State</option>
                            <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">LGA *</label>
                        <select name="lga_id" id="lgaSelect" class="form-control" required disabled>
                            <option value="">Select State First</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Address *</label>
                    <textarea name="address" class="form-control" rows="2" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project Done by *</label>
                        <input type="text" name="contractor" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Budget (₦)</label>
                        <input type="number" name="budget" class="form-control" step="0.01">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expected Completion</label>
                        <input type="date" name="expected_completion" class="form-control">
                    </div>
                </div>

                <!-- Image Upload Section -->
                <div class="image-upload-container">
                    <div class="form-group">
                        <label class="form-label">Project Image *</label>
                        <input type="file" name="project_image" id="projectImage" 
                               class="form-control-file" accept="image/*" required>
                        <p class="upload-info">Max file size: 2MB. Allowed formats: JPG, PNG, GIF, WebP.</p>
                    </div>
                    
                    <div class="image-preview" id="imagePreview">
                        <img id="previewImage" src="#" alt="Image preview">
                        <p>Image Preview</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Project
                    </button>
                    <a href="projects.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Image preview functionality
        document.getElementById('projectImage').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');
            const previewImage = document.getElementById('previewImage');
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
        
        // Load LGAs based on selected state
        document.getElementById('stateSelect').addEventListener('change', function() {
            const stateId = this.value;
            const lgaSelect = document.getElementById('lgaSelect');
            
            if (!stateId) {
                lgaSelect.innerHTML = '<option value="">Select State First</option>';
                lgaSelect.disabled = true;
                return;
            }
            
            // Fetch LGAs via AJAX
            fetch(`get_lgas.php?state_id=${stateId}`)
                .then(response => response.json())
                .then(data => {
                    lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                    data.forEach(lga => {
                        const option = document.createElement('option');
                        option.value = lga.id;
                        option.textContent = lga.name;
                        lgaSelect.appendChild(option);
                    });
                    lgaSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading LGAs:', error);
                });
        });
    </script>
    <script src="../js/admin.js"></script>
</body>
</html>