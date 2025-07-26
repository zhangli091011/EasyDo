<?php
// Include CORS handling
require_once '../config/cors.php';

// Content type
header("Content-Type: application/json; charset=UTF-8");

// Include database and object files
include_once '../config/Database.php';
include_once '../models/User.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize user object
$user = new User($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure data is not empty
if(
    !empty($data->username) &&
    !empty($data->password)
) {
    // Set user property values
    $user->username = $data->username;
    $user->password = $data->password;
    $user->mbti = !empty($data->mbti) ? $data->mbti : null;
    $user->hobbies = !empty($data->hobbies) ? $data->hobbies : null;
    
    // Handle weighted interests
    if(!empty($data->interests)) {
        // Convert array of interests with weights to required JSON format
        $interests_weighted = [];
        $weights = [0.9, 0.6, 0.3]; // Default weights for 3 interests
        
        // Process each interest with its weight
        foreach($data->interests as $index => $interest) {
            if(!empty($interest)) {
                // Assign weight based on position (or use default if not enough weights)
                $weight = isset($weights[$index]) ? $weights[$index] : 0.1;
                $interests_weighted[$interest] = $weight;
            }
        }
        
        // Convert to JSON string
        if(!empty($interests_weighted)) {
            $user->interests_weighted = json_encode($interests_weighted, JSON_UNESCAPED_UNICODE);
        } else {
            $user->interests_weighted = null;
        }
    } else {
        $user->interests_weighted = null;
    }
    
    // Create the user
    $userId = $user->register();
    if($userId) {
        // Set response code - 201 created
        http_response_code(201);
        
        // Display message with user ID
        echo json_encode(array(
            "message" => "用户注册成功",
            "user_id" => $userId
        ));
    } else {
        // If unable to create user, tell the user
        // Check if the user already exists
        if($user->usernameExists()) {
            // Set response code - 400 bad request
            http_response_code(400);
            
            // Display message
            echo json_encode(array("message" => "用户名已存在"));
        } else {
            // Set response code - 503 service unavailable
            http_response_code(503);
            
            // Display message
            echo json_encode(array("message" => "无法创建用户"));
        }
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Display message
    echo json_encode(array("message" => "无法创建用户。请提供用户名和密码。"));
} 