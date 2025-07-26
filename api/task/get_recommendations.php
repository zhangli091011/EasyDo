<?php
// 允许跨域请求
require_once '../config/cors.php';

// 设置响应格式
header("Content-Type: application/json; charset=UTF-8");

// 引入必要的文件
include_once '../config/Database.php';
include_once '../models/TaskRecommendation.php';

// 检查是否是GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "方法不允许，请使用GET请求"]);
    exit;
}

// 检查必要参数
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
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
    $taskRecommendation->user_id = $_GET['user_id'];
    
    // 读取用户未使用的推荐
    $stmt = $taskRecommendation->readUnusedByUser();
    $num = $stmt->rowCount();
    
    // 如果没有未使用的推荐，或者请求强制生成新的推荐
    $forceNew = isset($_GET['force_new']) && $_GET['force_new'] === 'true';
    
    if ($num === 0 || $forceNew) {
        // 重定向到生成推荐的端点
        http_response_code(307);
        header("Location: /api/task/generate_recommendations.php");
        echo json_encode([
            "message" => "没有未使用的推荐，需要生成新的推荐",
            "redirect" => true
        ]);
        exit;
    }
    
    // 准备响应数据
    $recommendations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recommendations[] = [
            "id" => $row['id'],
            "activities" => $row['activities'],
            "tag" => $row['tag'],
            "created_at" => $row['created_at']
        ];
    }
    
    // 返回成功响应
    http_response_code(200);
    echo json_encode([
        "message" => "成功获取任务推荐",
        "recommendations" => $recommendations
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "错误: " . $e->getMessage()]);
}
?> 