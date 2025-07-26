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

// Check if required data is provided
if (!empty($data->user_id)) {
    // Set user ID
    $user->id = $data->user_id;
    
    // First, fetch the current user data
    if ($user->readOne()) {
        // Prepare data for Coze API
        $mbti = !empty($data->mbti) ? $data->mbti : $user->mbti;
        
        // Get weighted interests
        $interests_weighted = null;
        if (!empty($data->interests_weighted)) {
            // Use provided interests
            $interests_weighted = $data->interests_weighted;
        } else if (!empty($user->interests_weighted)) {
            // Use existing interests from database
            if (is_string($user->interests_weighted)) {
                $interests_weighted = json_decode($user->interests_weighted, true);
            } else {
                $interests_weighted = $user->interests_weighted;
            }
        }
        
        // Call Coze API to generate profile description
        $profile_description = callCozeApi($user->id, $mbti, $interests_weighted);
        
        // If API call successful, update the user profile
        if ($profile_description) {
            // Update user with new profile description
            $user->profile_description = $profile_description;
            
            // If MBTI was provided, update it too
            if (!empty($data->mbti)) {
                $user->mbti = $data->mbti;
            }
            
            // If interests_weighted was provided, update it too
            if (!empty($data->interests_weighted)) {
                // 确保interests_weighted是JSON字符串
                if (is_object($data->interests_weighted) || is_array($data->interests_weighted)) {
                    $user->interests_weighted = json_encode($data->interests_weighted, JSON_UNESCAPED_UNICODE);
                    
                    // 从interests_weighted提取兴趣词汇到hobbies字段
                    $interests = [];
                    foreach ($data->interests_weighted as $interest => $weight) {
                        $interests[] = $interest;
                    }
                    
                    // 保存到hobbies字段
                    if (!empty($interests)) {
                        $user->hobbies = implode(', ', $interests);
                    }
                } else {
                    $user->interests_weighted = $data->interests_weighted;
                }
            }
            
            // Update user profile
            if ($user->updateProfile()) {
                // Set response code - 200 OK
                http_response_code(200);
                
                // Return success response
                echo json_encode(array(
                    "message" => "用户画像生成成功",
                    "profile_description" => $profile_description
                ));
            } else {
                // Set response code - 503 Service unavailable
                http_response_code(503);
                
                // Return error message
                echo json_encode(array("message" => "无法更新用户画像"));
            }
        } else {
            // API call failed
            http_response_code(500);
            echo json_encode(array("message" => "生成用户画像失败"));
        }
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

/**
 * 调用Coze Workflow API生成用户画像
 * 
 * @param int $userId 用户ID
 * @param string $mbti MBTI类型
 * @param array $interests_weighted 带权重的兴趣爱好
 * @return string|false 生成的用户画像或失败返回false
 */
function callCozeApi($userId, $mbti = null, $interests_weighted = null) {
    // --------- API 端点与凭证 ---------
    $url = 'https://api.coze.cn/v1/workflow/run';
    $apiToken = getenv('COZE_API_TOKEN') ?: 'pat_YN6tfoiShdfT5hAwGj9czbjxMGDr25BuR2fTKi3CrTI0FqMQNKynZ5eEv3HPltx8';
    
    // 准备请求数据
    $requestData = [
        'workflow_id' => '7531212626267095078',
        'parameters' => [
            'id' => (string)$userId,
        ]
    ];
    
    // 添加MBTI信息（如果有）
    if (!empty($mbti)) {
        $requestData['parameters']['mbti'] = $mbti;
    }
    
    // 添加带权重的兴趣爱好（如果有）
    if (!empty($interests_weighted) && is_array($interests_weighted)) {
        $requestData['parameters']['interests_weighted'] = json_encode($interests_weighted, JSON_UNESCAPED_UNICODE);
    }
    
    // --------- 发起 API 请求 ---------
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData, JSON_UNESCAPED_UNICODE),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Coze API Error: $curlError");
        return false;
    }
    
    // --------- 解析响应 ---------
    $responseData = json_decode($response, true);
    if (!$responseData) {
        error_log("Invalid JSON response from Coze API");
        return false;
    }
    
    // 记录完整响应用于调试
    error_log("Coze API Response: " . json_encode($responseData, JSON_UNESCAPED_UNICODE));
    
    // 提取output数据
    if (isset($responseData['data'])) {
        // data字段可能是JSON字符串，需要再解码一次
        $innerData = is_array($responseData['data']) 
            ? $responseData['data'] 
            : json_decode($responseData['data'], true);
        
        if (isset($innerData['output'])) {
            return $innerData['output'];
        }
    }
    
    // 如果没有找到output数据，返回false
    return false;
} 