<?php
// repair_database.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Repairing Database</h2>";

require_once 'config.php';
$db = getDBConnection();

if (!$db) {
    echo "<p style='color: red;'>Database connection failed!</p>";
    exit;
}

echo "<p style='color: green;'>✓ Database connection successful</p>";

// Check current tables
echo "<h3>Current Tables:</h3>";
$result = $db->query("SHOW TABLES");
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
}

echo "<h3>Recreating Tables...</h3>";

// Drop tables if they exist
$db->query("DROP TABLE IF EXISTS projects");
$db->query("DROP TABLE IF EXISTS lgas");
$db->query("DROP TABLE IF EXISTS states");
$db->query("DROP TABLE IF EXISTS admins");

// Create states table
$sql = "CREATE TABLE states (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($db->query($sql)) {
    echo "<p>✓ Created states table</p>";
} else {
    echo "<p style='color: red;'>Error creating states: " . $db->error . "</p>";
}

// Create lgas table
$sql = "CREATE TABLE lgas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    state_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($db->query($sql)) {
    echo "<p>✓ Created lgas table</p>";
} else {
    echo "<p style='color: red;'>Error creating lgas: " . $db->error . "</p>";
}

// Create projects table
$sql = "CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    state_id INT NOT NULL,
    lga_id INT NOT NULL,
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    location_type VARCHAR(50),
    contractor VARCHAR(255),
    budget DECIMAL(15, 2),
    start_date DATE,
    expected_completion DATE,
    status ENUM('ongoing', 'completed', 'pending') DEFAULT 'pending',
    progress INT DEFAULT 0,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES states(id),
    FOREIGN KEY (lga_id) REFERENCES lgas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($db->query($sql)) {
    echo "<p>✓ Created projects table</p>";
} else {
    echo "<p style='color: red;'>Error creating projects: " . $db->error . "</p>";
}

// Create admins table (if needed)
$sql = "CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($db->query($sql)) {
    echo "<p>✓ Created admins table</p>";
} else {
    echo "<p style='color: red;'>Error creating admins: " . $db->error . "</p>";
}

// Insert sample data
echo "<h3>Inserting Sample Data...</h3>";

// Insert states
$states = [
    ['id' => 1, 'name' => 'Edo'],
    ['id' => 2, 'name' => 'Lagos'],
    ['id' => 3, 'name' => 'Abuja']
];

foreach ($states as $state) {
    $sql = "INSERT INTO states (id, name) VALUES ({$state['id']}, '{$state['name']}')";
    if ($db->query($sql)) {
        echo "<p>✓ Added state: {$state['name']}</p>";
    }
}

// Insert LGAs for Edo state
$lgas = [
    ['id' => 1, 'state_id' => 1, 'name' => 'Etsako West'],
    ['id' => 2, 'state_id' => 1, 'name' => 'Etsako East'],
    ['id' => 3, 'state_id' => 1, 'name' => 'Esan West'],
    ['id' => 4, 'state_id' => 2, 'name' => 'Ikeja'],
    ['id' => 5, 'state_id' => 2, 'name' => 'Lagos Island'],
    ['id' => 6, 'state_id' => 3, 'name' => 'Abuja Municipal']
];

foreach ($lgas as $lga) {
    $sql = "INSERT INTO lgas (id, state_id, name) VALUES ({$lga['id']}, {$lga['state_id']}, '{$lga['name']}')";
    if ($db->query($sql)) {
        echo "<p>✓ Added LGA: {$lga['name']}</p>";
    }
}

// Insert sample projects
$projects = [
    "INSERT INTO projects (id, title, description, state_id, lga_id, contractor, budget, status, start_date, expected_completion, progress) VALUES 
    (1, 'Auchi Modern Market', 'Construction of a modern market with 500 shops and parking facilities', 1, 1, 'Raycon Construction Ltd', 250000000.00, 'ongoing', '2023-05-01', '2024-08-01', 65)",
    
    "INSERT INTO projects (id, title, description, state_id, lga_id, contractor, budget, status, start_date, expected_completion, progress) VALUES 
    (2, 'Auchi Polytechnic Hostel Blocks', 'Construction of 4 new hostel blocks for students', 1, 1, 'Benjaminex Nigeria Ltd', 180000000.00, 'ongoing', '2023-03-01', '2024-02-01', 45)",
    
    "INSERT INTO projects (id, title, description, state_id, lga_id, contractor, budget, status, start_date, expected_completion, progress) VALUES 
    (3, 'Etsako West Council Secretariat', 'New administrative building for local government operations', 1, 1, 'State Govt Contractors', 350000000.00, 'completed', '2022-11-01', '2023-12-01', 100)"
];

foreach ($projects as $sql) {
    if ($db->query($sql)) {
        echo "<p>✓ Added sample project</p>";
    } else {
        echo "<p style='color: red;'>Error adding project: " . $db->error . "</p>";
    }
}

// Insert sample admin
$sql = "INSERT INTO admins (username, password, email, full_name) VALUES 
        ('admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin@example.com', 'System Administrator')";

if ($db->query($sql)) {
    echo "<p>✓ Added admin user (username: admin, password: admin123)</p>";
}

echo "<h3 style='color: green;'>✓ Database repair complete!</h3>";
echo "<p><a href='debug_project.php'>Test the database</a></p>";
echo "<p><a href='projects.html'>Go to Projects Page</a></p>";

$db->close();
?>