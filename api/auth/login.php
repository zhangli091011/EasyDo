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

// Check if username and password are provided
if(!empty($data->username) && !empty($data->password)) {
    // Set user property values
    $user->username = $data->username;
    $user->password = $data->password;
    
    // Check if user exists and password is correct
    if($user->login()) {
        // Create simple token (in a real app, use JWT or similar)
        $token = bin2hex(random_bytes(16));
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Parse interests_weighted if it's a JSON string
        $interests = null;
        if (!empty($user->interests_weighted)) {
            if (is_string($user->interests_weighted)) {
                $interests = json_decode($user->interests_weighted, true);
            } else {
                $interests = $user->interests_weighted;
            }
        }
        
        // Create array for response
        $response = array(
            "message" => "登录成功",
            "user" => array(
                "id" => $user->id,
                "username" => $user->username,
                "mbti" => $user->mbti,
                "hobbies" => $user->hobbies,
                "interests_weighted" => $interests,
                "profile_description" => $user->profile_description
            ),
            "token" => $token
        );
        
        // Display response
        echo json_encode($response);
    } else {
        // Set response code - 401 Unauthorized
        http_response_code(401);
        
        // Display message
        echo json_encode(array("message" => "用户名或密码错误"));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Display message
    echo json_encode(array("message" => "请提供用户名和密码"));
} 