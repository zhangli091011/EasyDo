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

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if the required data is provided
if(!empty($data->id) && !empty($data->user_id)) {
    // Set task ID and user ID to delete
    $task->id = $data->id;
    $task->user_id = $data->user_id;
    
    // Delete the task
    if($task->delete()) {
        // Set response code - 200 ok
        http_response_code(200);
        
        // Tell the user
        echo json_encode(array("message" => "任务已删除"));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array("message" => "无法删除任务"));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array("message" => "无法删除任务，缺少必要数据"));
} 