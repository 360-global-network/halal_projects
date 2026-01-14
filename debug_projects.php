<?php
// debug_project.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debugging Project Details</h2>";

// Test database connection
require_once 'config.php';
$db = getDBConnection();

if (!$db) {
    echo "<p style='color: red;'>Database connection failed!</p>";
    exit;
}
echo "<p style='color: green;'>✓ Database connection successful</p>";

echo "<h3>Step 1: Check if tables exist</h3>";

// List all tables
$result = $db->query("SHOW TABLES");
if ($result === false) {
    echo "<p style='color: red;'>Error showing tables: " . $db->error . "</p>";
} else {
    echo "<h4>Available tables in database:</h4>";
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
}

echo "<h3>Step 2: Check projects table structure</h3>";

$result = $db->query("DESCRIBE projects");
if ($result === false) {
    echo "<p style='color: red;'>Error describing projects table: " . $db->error . "</p>";
    
    // Try to see if the table exists with different case
    $result = $db->query("SHOW TABLES LIKE 'projects'");
    if ($result->num_rows > 0) {
        echo "<p>Table 'projects' exists but has an error</p>";
    } else {
        echo "<p>Table 'projects' does not exist</p>";
    }
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Step 3: Check if states and lgas tables exist</h3>";

$tables = ['states', 'lgas'];
foreach ($tables as $table) {
    $result = $db->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does NOT exist</p>";
    }
}

echo "<h3>Step 4: Check data in projects table</h3>";

// First, let's count projects
$result = $db->query("SELECT COUNT(*) as count FROM projects");
if ($result === false) {
    echo "<p style='color: red;'>Error counting projects: " . $db->error . "</p>";
} else {
    $row = $result->fetch_assoc();
    echo "<p>Total projects in database: " . $row['count'] . "</p>";
    
    if ($row['count'] > 0) {
        // Show sample of projects
        $result = $db->query("SELECT id, title, state_id, lga_id FROM projects LIMIT 10");
        echo "<h4>Sample projects:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>State ID</th><th>LGA ID</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['title']}</td>";
            echo "<td>{$row['state_id']}</td>";
            echo "<td>{$row['lga_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No projects found in database</p>";
    }
}

echo "<h3>Step 5: Test the actual query from getProjectById</h3>";

$test_id = 1;
$query = "SELECT p.*, s.name as state_name, l.name as lga_name 
          FROM projects p 
          JOIN states s ON p.state_id = s.id 
          JOIN lgas l ON p.lga_id = l.id 
          WHERE p.id = $test_id";

echo "<p><strong>Query:</strong> " . htmlspecialchars($query) . "</p>";

$result = $db->query($query);
if ($result === false) {
    echo "<p style='color: red;'>Query failed: " . $db->error . "</p>";
    
    // Test individual parts
    echo "<h4>Testing individual tables:</h4>";
    
    // Test projects table
    $result = $db->query("SELECT * FROM projects WHERE id = $test_id");
    if ($result === false) {
        echo "<p style='color: red;'>Error querying projects: " . $db->error . "</p>";
    } else if ($result->num_rows == 0) {
        echo "<p>No project found with ID $test_id</p>";
    } else {
        $project = $result->fetch_assoc();
        echo "<p>✓ Project found: " . htmlspecialchars($project['title']) . "</p>";
        echo "<p>State ID: " . $project['state_id'] . ", LGA ID: " . $project['lga_id'] . "</p>";
    }
    
    // Test states table
    $result = $db->query("SELECT * FROM states LIMIT 5");
    if ($result === false) {
        echo "<p style='color: red;'>Error querying states: " . $db->error . "</p>";
    } else {
        echo "<p>States table has " . $result->num_rows . " rows</p>";
    }
    
    // Test lgas table
    $result = $db->query("SELECT * FROM lgas LIMIT 5");
    if ($result === false) {
        echo "<p style='color: red;'>Error querying lgas: " . $db->error . "</p>";
    } else {
        echo "<p>LGAs table has " . $result->num_rows . " rows</p>";
    }
} else {
    if ($result->num_rows == 0) {
        echo "<p>No project found with ID $test_id</p>";
    } else {
        $project = $result->fetch_assoc();
        echo "<pre>";
        print_r($project);
        echo "</pre>";
    }
}

echo "<h3>Step 6: Create sample data if needed</h3>";

// Check if we need to create sample data
$result = $db->query("SELECT COUNT(*) as count FROM projects");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo "<p>No projects found. Would you like to create sample data?</p>";
    echo "<form method='post'>";
    echo "<input type='submit' name='create_sample' value='Create Sample Data'>";
    echo "</form>";
    
    if (isset($_POST['create_sample'])) {
        // First create states if they don't exist
        $db->query("INSERT IGNORE INTO states (id, name) VALUES (1, 'Edo')");
        $db->query("INSERT IGNORE INTO states (id, name) VALUES (2, 'Lagos')");
        $db->query("INSERT IGNORE INTO states (id, name) VALUES (3, 'Abuja')");
        
        // Create LGAs for Edo state
        $db->query("INSERT IGNORE INTO lgas (id, state_id, name) VALUES (1, 1, 'Etsako West')");
        $db->query("INSERT IGNORE INTO lgas (id, state_id, name) VALUES (2, 1, 'Etsako East')");
        $db->query("INSERT IGNORE INTO lgas (id, state_id, name) VALUES (3, 1, 'Esan West')");
        
        // Create sample projects
        $projects = [
            "INSERT INTO projects (title, description, state_id, lga_id, contractor, budget, status, start_date, expected_completion) VALUES 
            ('Auchi Modern Market', 'Construction of a modern market with 500 shops and parking facilities', 1, 1, 'Raycon Construction Ltd', 250000000.00, 'ongoing', '2023-05-01', '2024-08-01')",
            
            "INSERT INTO projects (title, description, state_id, lga_id, contractor, budget, status, start_date, expected_completion) VALUES 
            ('Auchi Polytechnic Hostel Blocks', 'Construction of 4 new hostel blocks for students', 1, 1, 'Benjaminex Nigeria Ltd', 180000000.00, 'ongoing', '2023-03-01', '2024-02-01')",
            
            "INSERT INTO projects (title, description, state_id, lga_id, contractor, budget, status, start_date, expected_completion) VALUES 
            ('Etsako West Council Secretariat', 'New administrative building for local government operations', 1, 1, 'State Govt Contractors', 350000000.00, 'completed', '2022-11-01', '2023-12-01')"
        ];
        
        foreach ($projects as $sql) {
            if ($db->query($sql)) {
                echo "<p>✓ Added sample project</p>";
            } else {
                echo "<p style='color: red;'>Error: " . $db->error . "</p>";
            }
        }
        
        echo "<p>Sample data created! <a href='debug_project.php'>Refresh page</a></p>";
    }
}

$db->close();
?>