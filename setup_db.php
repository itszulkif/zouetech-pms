<?php
// setup_db.php - RUN THIS ONCE TO INITIALIZE DATABASE

$host = 'localhost';
$username = 'root';
$password = '';
$db_name = 'dosti_pms';

// 1. Create Connection to MySQL
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create Database if not exists
echo "Creating database if not exists...<br>";
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    echo "Database created or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// 3. Select Database
$conn->select_db($db_name);

// 4. Read SQL file
$sql_file = 'db_schema.sql';
if (!file_exists($sql_file)) {
    die("Error: $sql_file not found.");
}

$sql_content = file_get_contents($sql_file);

// 5. Execute Schema Multi-Query
echo "Importing schema...<br>";
if ($conn->multi_query($sql_content)) {
    // Collect all results
    do { 
        if ($result = $conn->store_result()) $result->free(); 
    } while ($conn->next_result());
    
    echo "<b>SUCCESS:</b> Database tables initialized successfully.<br>";
    
    // 6. Insert Seed Data
    echo "Inserting seed data...<br>";
    $seed_sql = "
        INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `performance_score`) VALUES
        (1, 'Super Admin', 'admin@zouetech.org', 'hashed_pass', 'Super Admin', 100),
        (2, 'Sarah Jenkins', 'sarah@zouetech.org', 'hashed_pass', 'Project Head', 95),
        (3, 'John Doe', 'john@zouetech.org', 'hashed_pass', 'Team Member', 82)
        ON DUPLICATE KEY UPDATE id=id;

        INSERT INTO `departments` (`id`, `name`, `head_id`) VALUES
        (1, 'One Room School', 1),
        (2, 'CSR Projects', 1)
        ON DUPLICATE KEY UPDATE id=id;

        INSERT INTO `projects` (`id`, `name`, `department_id`, `project_head_id`, `status`) VALUES
        (1, 'School Construction - Lahore', 1, 2, 'In Progress'),
        (2, 'Community Outreach', 2, 1, 'Planned')
        ON DUPLICATE KEY UPDATE id=id;

        INSERT INTO `tasks` (`title`, `project_id`, `assigned_to`, `priority`, `status`, `due_date`) VALUES
        ('Finalize Blueprints', 1, 1, 'High', 'Pending', DATE_ADD(NOW(), INTERVAL 5 HOUR)),
        ('Site Visit', 1, 2, 'Medium', 'Pending', DATE_ADD(NOW(), INTERVAL 1 DAY)),
        ('Budget Review', 2, 3, 'Low', 'Pending', DATE_ADD(NOW(), INTERVAL 3 DAY));
    ";
    
    if ($conn->multi_query($seed_sql)) {
        do { 
            if ($result = $conn->store_result()) $result->free(); 
        } while ($conn->next_result());
        echo "<b>SUCCESS:</b> Seed data inserted.<br>";
    } else {
        echo "Error inserting seed data: " . $conn->error . "<br>";
    }

    echo "<br><a href='dashboard.php' style='display:inline-block; padding:10px 20px; background: #06b6d4; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Launch Dashboard</a>";
} else {
    echo "Error importing schema: " . $conn->error . "<br>";
}

$conn->close();
?>
