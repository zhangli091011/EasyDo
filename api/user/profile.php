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

// Handle request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // GET - Retrieve user profile
    case 'GET':
        // Check if user_id is provided
        if (!empty($_GET['user_id'])) {
            // Set user ID
            $user->id = $_GET['user_id'];
            
            // Try to get user details
            if ($user->readOne()) {
                // Set response code - 200 OK
                http_response_code(200);
                
                // Create response array
                $response = array(
                    "id" => $user->id,
                    "username" => $user->username,
                    "mbti" => $user->mbti,
                    "hobbies" => $user->hobbies,
                    "profile_description" => $user->profile_description,
                    "created_at" => $user->created_at
                );
                
                // Return response
                echo json_encode($response);
            } else {
                // Set response code - 404 Not found
                http_response_code(404);
                
                // Return message
                echo json_encode(array("message" => "用户不存在"));
            }
        } else {
            // Set response code - 400 Bad request
            http_response_code(400);
            
            // Return message
            echo json_encode(array("message" => "缺少用户ID"));
        }
        break;
        
    // PUT - Update user profile
    case 'PUT':
        // Get posted data
        $data = json_decode(file_get_contents("php://input"));
        
        // Check if user_id is provided
        if (!empty($data->user_id)) {
            // Set user properties
            $user->id = $data->user_id;
            $user->mbti = isset($data->mbti) ? $data->mbti : null;
            
            // 处理带权重的兴趣爱好
            if (isset($data->interests_weighted)) {
                $user->interests_weighted = $data->interests_weighted;
                
                // 从interests_weighted中提取兴趣词汇并保存到hobbies字段
                $interests = [];
                
                // 如果是字符串，先转换为对象
                $interestsObj = $data->interests_weighted;
                if (is_string($interestsObj)) {
                    $interestsObj = json_decode($interestsObj);
                }
                
                // 提取所有兴趣的键（即兴趣词汇）
                if (is_object($interestsObj) || is_array($interestsObj)) {
                    foreach ($interestsObj as $interest => $weight) {
                        $interests[] = $interest;
                    }
                    // 将兴趣词汇以逗号分隔的形式保存到hobbies字段
                    $user->hobbies = implode(', ', $interests);
                }
            } else {
                $user->hobbies = isset($data->hobbies) ? $data->hobbies : null;
            }
            
            $user->profile_description = isset($data->profile_description) ? $data->profile_description : null;
            
            // Update user profile
            if ($user->updateProfile()) {
                // Set response code - 200 OK
                http_response_code(200);
                
                // Return success message
                echo json_encode(array("message" => "用户画像已更新"));
            } else {
                // Set response code - 503 Service unavailable
                http_response_code(503);
                
                // Return error message
                echo json_encode(array("message" => "无法更新用户画像"));
            }
        } else {
            // Set response code - 400 Bad request
            http_response_code(400);
            
            // Return message
            echo json_encode(array("message" => "缺少用户ID"));
        }
        break;
        
    // Default - Method not allowed
    default:
        // Set response code - 405 Method not allowed
        http_response_code(405);
        
        // Return message
        echo json_encode(array("message" => "方法不被允许"));
        break;
} 