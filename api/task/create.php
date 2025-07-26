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

// Make sure data is not empty
if(
    !empty($data->user_id) &&
    !empty($data->text)
) {
    // Set task property values
    $task->user_id = $data->user_id;
    $task->text = $data->text;
    $task->color_index = isset($data->color_index) ? $data->color_index : 0;
    
    // Create the task
    $task_id = $task->create();
    if($task_id) {
        // Set response code - 201 created
        http_response_code(201);
        
        // Display message
        echo json_encode(array(
            "message" => "任务已创建",
            "id" => $task_id
        ));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Display message
        echo json_encode(array("message" => "无法创建任务"));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Display message
    echo json_encode(array("message" => "无法创建任务，数据不完整"));
} 