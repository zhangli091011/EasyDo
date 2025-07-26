<?php
// 允许跨域请求
require_once '../config/cors.php';

// 设置响应格式
header("Content-Type: application/json; charset=UTF-8");

// 引入必要的文件
include_once '../config/Database.php';
include_once '../models/TaskRecommendation.php';

// 检查是否是PUT请求
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // 允许OPTIONS请求（预检请求）
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(["message" => "方法不允许，请使用PUT或POST请求"]);
    exit;
}

// 获取请求数据
$data = json_decode(file_get_contents("php://input"));

// 检查必要参数
if (!isset($data->id) || !isset($data->user_id)) {
    http_response_code(400);
    echo json_encode(["message" => "缺少必要参数"]);
    exit;
}

try {
    // 获取数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    // 初始化对象
    $taskRecommendation = new TaskRecommendation($db);
    $taskRecommendation->id = $data->id;
    $taskRecommendation->user_id = $data->user_id;
    
    // 标记为已使用
    if ($taskRecommendation->markAsUsed()) {
        http_response_code(200);
        echo json_encode(["message" => "推荐已标记为已使用"]);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "未找到该推荐或无法更新"]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "错误: " . $e->getMessage()]);
}
?> 