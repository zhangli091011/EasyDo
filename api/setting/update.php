<?php
// Include CORS handling
require_once '../config/cors.php';

// Content type
header("Content-Type: application/json; charset=UTF-8");

// Include database and object files
include_once '../config/Database.php';
include_once '../models/Setting.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize setting object
$setting = new Setting($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure data is not empty
if(
    !empty($data->user_id) &&
    !empty($data->setting_key) &&
    isset($data->setting_value)
) {
    // Set setting property values
    $setting->user_id = $data->user_id;
    $setting->setting_key = $data->setting_key;
    $setting->setting_value = $data->setting_value;
    
    // Create or update the setting
    if($setting->createOrUpdate()) {
        // Set response code - 200 OK
        http_response_code(200);
        
        // Display message
        echo json_encode(array("message" => "设置已更新"));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Display message
        echo json_encode(array("message" => "无法更新设置"));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Display message
    echo json_encode(array("message" => "无法更新设置，数据不完整"));
} 