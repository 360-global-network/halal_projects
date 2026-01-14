<?php
require_once 'config.php';
$conn = getDBConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test-box { background: #f5f5f5; padding: 20px; margin: 10px; border: 1px solid #ccc; }
        img { border: 2px solid red; margin: 10px; max-width: 200px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
<h1>Image Debug Test</h1>

<?php
// Test 1: Check if project_images folder exists
$projectImagesPath = __DIR__ . '/project_images/';
echo "<div class='test-box'>
<h2>Test 1: Check project_images folder</h2>
<p>Folder path: <code>" . $projectImagesPath . "</code></p>
<p>Folder exists: " . (is_dir($projectImagesPath) ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "</p>";

if (is_dir($projectImagesPath)) {
    $files = scandir($projectImagesPath);
    echo "<p>Files in folder: " . implode(', ', array_diff($files, ['.', '..'])) . "</p>";
}
echo "</div>";

// Test 2: Direct file check
echo "<div class='test-box'>
<h2>Test 2: Direct File Check</h2>";
$imageFile = 'project_1768314461_6966565dd0bd0.jpg';
$fullPath = $projectImagesPath . $imageFile;
echo "<p>Checking file: <code>" . $imageFile . "</code></p>";
echo "<p>Full path: <code>" . $fullPath . "</code></p>";

if (file_exists($fullPath)) {
    echo "<p>File exists: <span class='success'>YES</span></p>";
    echo "<p>File size: " . filesize($fullPath) . " bytes</p>";
    echo "<p>File readable: " . (is_readable($fullPath) ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "</p>";
    
    // Try to display directly
    echo "<h3>Direct Display Test:</h3>";
    $webPath = 'project_images/' . $imageFile;
    echo "<p>Web path: <code>" . $webPath . "</code></p>";
    echo "<img src='" . $webPath . "' alt='test image' onerror='alert(\"Image failed to load!\")'>";
    
    // Try absolute URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $absoluteUrl = $protocol . '://' . $host . '/projects/project_images/' . $imageFile;
    echo "<p>Absolute URL: <code>" . $absoluteUrl . "</code></p>";
    echo "<img src='" . $absoluteUrl . "' alt='test image' onerror='alert(\"Absolute URL failed!\")'>";
} else {
    echo "<p>File exists: <span class='error'>NO</span></p>";
}
echo "</div>";

// Test 3: Check database
echo "<div class='test-box'>
<h2>Test 3: Database Check</h2>";
$result = $conn->query("SELECT id, title, image_url FROM projects WHERE image_url LIKE '%project_1768314461_6966565dd0bd0.jpg%'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<p>Found project: <strong>" . htmlspecialchars($row['title']) . "</strong></p>";
        echo "<p>Image URL: <code>" . htmlspecialchars($row['image_url']) . "</code></p>";
    }
} else {
    echo "<p>No project found with that image URL</p>";
}
echo "</div>";
$conn->close();
?>

<div class='test-box'>
<h2>Test 4: Manual URL Test</h2>
<p>Try these links manually:</p>
<ol>
    <li><a href="project_images/project_1768314461_6966565dd0bd0.jpg" target="_blank">Relative: project_images/project_1768314461_6966565dd0bd0.jpg</a></li>
    <li><a href="/projects/project_images/project_1768314461_6966565dd0bd0.jpg" target="_blank">Absolute: /projects/project_images/project_1768314461_6966565dd0bd0.jpg</a></li>
    <li><a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/projects/project_images/project_1768314461_6966565dd0bd0.jpg" target="_blank">Full URL: http://<?php echo $_SERVER['HTTP_HOST']; ?>/projects/project_images/project_1768314461_6966565dd0bd0.jpg</a></li>
</ol>
</div>

<div class='test-box'>
<h2>Test 5: Simple Image Test</h2>
<p>If this external image loads but your local one doesn't, there's a problem with your local file or path:</p>
<img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200&h=150&fit=crop" alt="External test image">
</div>

<script>
// Test 6: JavaScript loading test
console.log('Image Debug Page Loaded');
</script>
</body>
</html>