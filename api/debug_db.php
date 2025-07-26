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
$result = array();

// Check user table
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'user'");
    $stmt->execute();
    $result["user_table_exists"] = ($stmt->rowCount() > 0);

    if ($result["user_table_exists"]) {
        // Check users in table
        $stmt = $db->prepare("SELECT id, username, email, phone, created FROM user");
        $stmt->execute();
        $result["users"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $result["user_error"] = $e->getMessage();
}

// Check tasks table
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'tasks'");
    $stmt->execute();
    $result["tasks_table_exists"] = ($stmt->rowCount() > 0);

    if ($result["tasks_table_exists"]) {
        // Get table structure
        $stmt = $db->prepare("SHOW CREATE TABLE tasks");
        $stmt->execute();
        $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $result["tasks_structure"] = $tableInfo["Create Table"];
        
        // Get tasks count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tasks");
        $stmt->execute();
        $countInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $result["tasks_count"] = $countInfo["count"];
    }
} catch (PDOException $e) {
    $result["tasks_error"] = $e->getMessage();
}

// Check settings table
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'settings'");
    $stmt->execute();
    $result["settings_table_exists"] = ($stmt->rowCount() > 0);
    
    if ($result["settings_table_exists"]) {
        // Get table structure
        $stmt = $db->prepare("SHOW CREATE TABLE settings");
        $stmt->execute();
        $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $result["settings_structure"] = $tableInfo["Create Table"];
    }
} catch (PDOException $e) {
    $result["settings_error"] = $e->getMessage();
}

// Return the result
http_response_code(200);
echo json_encode($result, JSON_PRETTY_PRINT);
?> 