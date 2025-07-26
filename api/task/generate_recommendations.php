<?php
// 允许跨域请求
require_once '../config/cors.php';

// 设置响应格式
header("Content-Type: application/json; charset=UTF-8");

// 引入必要的文件
include_once '../config/Database.php';
include_once '../models/TaskRecommendation.php';
include_once '../models/User.php';

// 获取POST请求数据
$data = json_decode(file_get_contents("php://input"));

// 检查必要数据是否提供
if (empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(["message" => "缺少用户ID"]);
    exit;
}

try {
    // 获取数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    // 初始化对象
    $taskRecommendation = new TaskRecommendation($db);
    $user = new User($db);
    
    // 设置用户ID
    $taskRecommendation->user_id = $data->user_id;
    $user->id = $data->user_id;
    
    // 获取用户信息
    if (!$user->readOne()) {
        http_response_code(404);
        echo json_encode(["message" => "用户不存在"]);
        exit;
    }
    
    // 调试: 输出用户信息
    $debug_info = [
        "user_id" => $data->user_id,
        "mbti" => $user->mbti,
        "interests_weighted" => $user->interests_weighted
    ];
    error_log("调试信息 - 用户数据: " . json_encode($debug_info, JSON_UNESCAPED_UNICODE));
    
    // 准备调用Coze API的参数
    $mbti = $user->mbti;
    $interests_weighted = is_string($user->interests_weighted) 
        ? json_decode($user->interests_weighted, true) 
        : $user->interests_weighted;
    
    // 调用Coze API获取任务推荐
    $recommendations = callCozeApi($data->user_id, $mbti, $interests_weighted);
    
    if ($recommendations) {
        // 尝试将推荐保存到数据库
        $success = $taskRecommendation->createMultiple($recommendations);
        
        if ($success) {
            http_response_code(200);
            echo json_encode([
                "message" => "任务推荐成功",
                "recommendations" => $recommendations,
                "debug_info" => $debug_info
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "message" => "无法保存任务推荐",
                "recommendations" => $recommendations, // 仍然返回推荐结果
                "debug_info" => $debug_info
            ]);
        }
    } else {
        // API调用失败，返回一些默认推荐
        $defaultRecommendations = generateDefaultRecommendations();
        
        http_response_code(200);
        echo json_encode([
            "message" => "无法获取个性化推荐，使用默认推荐",
            "recommendations" => $defaultRecommendations,
            "is_default" => true,
            "debug_info" => $debug_info
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "错误: " . $e->getMessage(),
        "debug" => "异常详情: " . $e->getTraceAsString()
    ]);
}

/**
 * 调用Coze Workflow API获取任务推荐
 * 
 * @param int $userId 用户ID
 * @param string $mbti MBTI类型
 * @param array $interests_weighted 带权重的兴趣爱好
 * @return array|false 任务推荐数组或失败返回false
 */
function callCozeApi($userId, $mbti = null, $interests_weighted = null) {
    // API 端点与凭证
    $url = 'https://api.coze.cn/v1/workflow/run';
    $apiToken = getenv('COZE_API_TOKEN') ?: 'pat_YN6tfoiShdfT5hAwGj9czbjxMGDr25BuR2fTKi3CrTI0FqMQNKynZ5eEv3HPltx8';
    
    // 准备请求数据
    $requestData = [
        'workflow_id' => '7531226574436515878',
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
    
    // 调试: 输出请求数据
    error_log("Coze API 请求数据: " . json_encode($requestData, JSON_UNESCAPED_UNICODE));
    
    // 发起 API 请求
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
        CURLOPT_TIMEOUT => 60,
        CURLOPT_VERBOSE => true
    ]);
    
    // 捕获完整的请求和响应信息
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // 获取详细的请求和响应信息
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    error_log("Coze API 详细请求日志: " . $verboseLog);
    
    // 记录 HTTP 代码和任何错误
    error_log("Coze API HTTP 状态码: " . $httpCode);
    if ($curlError) {
        error_log("Coze API 错误: " . $curlError);
    }
    
    curl_close($ch);
    
    if ($curlError) {
        return false;
    }
    
    // 解析响应
    $responseData = json_decode($response, true);
    if (!$responseData) {
        error_log("无效的 JSON 响应: " . $response);
        return false;
    }
    
    // 记录完整响应用于调试
    error_log("Coze API 响应: " . json_encode($responseData, JSON_UNESCAPED_UNICODE));
    
    // 提取recommendations数据
    if (isset($responseData['data'])) {
        // data字段可能是JSON字符串，需要再解码一次
        $innerData = is_array($responseData['data']) 
            ? $responseData['data'] 
            : json_decode($responseData['data'], true);
        
        if (isset($innerData['output']) && is_string($innerData['output'])) {
            // 尝试解析output为JSON数组
            $recommendations = json_decode($innerData['output'], true);
            if (is_array($recommendations)) {
                return $recommendations;
            } else {
                error_log("无法解析推荐数据: " . $innerData['output']);
            }
        } else {
            error_log("响应中缺少 output 字段或格式不正确");
        }
    } else {
        error_log("响应中缺少 data 字段");
    }
    
    // 如果没有找到recommendations数据，返回false
    return false;
}

/**
 * 生成默认的任务推荐
 * 
 * @return array 默认任务推荐数组
 */
function generateDefaultRecommendations() {
    return [
        [
            "activities" => "阅读一小时",
            "tag" => "智力"
        ],
        [
            "activities" => "晨跑30分钟",
            "tag" => "体力"
        ],
        [
            "activities" => "与朋友聚会",
            "tag" => "社交"
        ],
        [
            "activities" => "学习一项新技能",
            "tag" => "智力"
        ],
        [
            "activities" => "做15分钟伸展运动",
            "tag" => "体力"
        ]
    ];
}
?> 