<?php
// Include CORS handling
require_once 'config/cors.php';

// Content type
header("Content-Type: application/json; charset=UTF-8");

// Include database config
include_once 'config/Database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check connection
if (!$db) {
    http_response_code(500);
    echo json_encode(array("message" => "Database connection failed"));
    exit;
}

// Result array
$result = array(
    "steps" => [],
    "status" => "success",
    "errors" => []
);

try {
    // Start transaction
    $db->beginTransaction();
    
    // Step 1: Check if users table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    $usersTableExists = ($stmt->rowCount() > 0);
    
    $result["steps"][] = "Checked users table - " . ($usersTableExists ? "Exists" : "Does not exist");
    
    if (!$usersTableExists) {
        // Create users table
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->exec($sql);
        $result["steps"][] = "Created users table";
    }
    
    // Step 2: Drop foreign key constraint from tasks
    $sql = "ALTER TABLE tasks DROP FOREIGN KEY tasks_ibfk_1";
    $db->exec($sql);
    $result["steps"][] = "Dropped foreign key constraint from tasks table";
    
    // Step 3: Drop foreign key constraint from settings
    try {
        $sql = "ALTER TABLE settings DROP FOREIGN KEY settings_ibfk_1";
        $db->exec($sql);
        $result["steps"][] = "Dropped foreign key constraint from settings table";
    } catch (PDOException $e) {
        $result["steps"][] = "No foreign key constraint found on settings table: " . $e->getMessage();
    }
    
    // Step 4: Copy data from user to users if needed
    $stmt = $db->prepare("SELECT * FROM user");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        foreach ($users as $user) {
            // Check if the user already exists in the users table
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$user['username']]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingUser) {
                // Insert user into users table
                $sql = "INSERT INTO users (id, username, password, created_at) 
                        VALUES (?, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $user['id'],
                    $user['username'],
                    $user['password']
                ]);
                $result["steps"][] = "Copied user {$user['username']} to users table";
            } else {
                $result["steps"][] = "User {$user['username']} already exists in users table";
            }
        }
    }
    
    // Step 5: Update tasks to reference users table
    $sql = "ALTER TABLE tasks ADD CONSTRAINT tasks_ibfk_1 
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE";
    $db->exec($sql);
    $result["steps"][] = "Updated tasks table to reference users table";
    
    // Step 6: Update settings to reference users table (if it doesn't already)
    if (strpos($result["steps"][2], "No foreign key constraint found") === false) {
        $sql = "ALTER TABLE settings ADD CONSTRAINT settings_ibfk_1 
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE";
        $db->exec($sql);
        $result["steps"][] = "Updated settings table to reference users table";
    }
    
    // Step 7: Drop the user table
    $sql = "DROP TABLE IF EXISTS user";
    $db->exec($sql);
    $result["steps"][] = "Dropped user table";
    
    // Commit the transaction
    $db->commit();
    
    // Update our User model
    $modelPath = __DIR__ . '/models/User.php';
    if (file_exists($modelPath)) {
        $content = file_get_contents($modelPath);
        $content = str_replace('private $table_name = "user"', 'private $table_name = "users"', $content);
        file_put_contents($modelPath, $content);
        $result["steps"][] = "Updated User model to use 'users' table";
    }
    
    // Update our Task model
    $modelPath = __DIR__ . '/models/Task.php';
    if (file_exists($modelPath)) {
        $content = file_get_contents($modelPath);
        $content = str_replace('REFERENCES user(id)', 'REFERENCES users(id)', $content);
        file_put_contents($modelPath, $content);
        $result["steps"][] = "Updated Task model to reference 'users' table";
    }
    
    // Update our Setting model
    $modelPath = __DIR__ . '/models/Setting.php';
    if (file_exists($modelPath)) {
        $content = file_get_contents($modelPath);
        $content = str_replace('REFERENCES user(id)', 'REFERENCES users(id)', $content);
        file_put_contents($modelPath, $content);
        $result["steps"][] = "Updated Setting model to reference 'users' table";
    }
    
} catch (PDOException $e) {
    // If there was an error, roll back the transaction
    $db->rollBack();
    $result["status"] = "error";
    $result["errors"][] = $e->getMessage();
}

// Return the result
http_response_code(200);
echo json_encode($result, JSON_PRETTY_PRINT);
?> 