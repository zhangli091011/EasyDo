<?php
// Include CORS handling
require_once '../config/cors.php';

// Content type
header("Content-Type: application/json; charset=UTF-8");

// Include database and object files
include_once '../config/Database.php';
include_once '../models/Task.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize task object
$task = new Task($db);

// Check if user_id is set in the URL
if(isset($_GET["user_id"])) {
    $task->user_id = $_GET["user_id"];
    
    // Read the tasks
    $stmt = $task->readAll();
    $num = $stmt->rowCount();
    
    // Check if any tasks found
    if($num > 0) {
        // Tasks array
        $tasks_arr = array();
        $tasks_arr["records"] = array();
        
        // Retrieve our table contents
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Extract row
            extract($row);
            
            $task_item = array(
                "id" => $id,
                "user_id" => $user_id,
                "text" => $text,
                "color_index" => $color_index,
                "completed" => false, // Default to false since it's not in the database
                "created" => $created_at
            );
            
            // Push to "records"
            array_push($tasks_arr["records"], $task_item);
        }
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Show tasks data
        echo json_encode($tasks_arr);
    } else {
        // Set response code - 200 OK
        http_response_code(200);
        
        // No tasks found
        echo json_encode(array(
            "message" => "没有找到任务",
            "records" => array()
        ));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array("message" => "缺少用户ID参数"));
} 