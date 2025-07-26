<?php
// 允许跨域请求
require_once '../config/cors.php';

// 设置响应格式
header("Content-Type: application/json; charset=UTF-8");

// 引入必要的文件
include_once '../config/Database.php';
include_once '../models/TaskTypeStats.php';

// 检查是否是GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "方法不允许，请使用GET请求"]);
    exit;
}

// 验证数据
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(["message" => "缺少用户ID"]);
    exit;
}

try {
    // 连接数据库
    $database = new Database();
    $db = $database->getConnection();
    
    // 初始化任务类型统计对象
    $stats = new TaskTypeStats($db);
    $stats->user_id = $_GET['user_id'];
    
    // 获取统计数据
    if ($stats->readOrCreate()) {
        http_response_code(200);
        echo json_encode([
            "message" => "成功获取统计数据",
            "stats" => [
                "intellectual_points" => (int)$stats->intellectual_points,
                "physical_points" => (int)$stats->physical_points,
                "social_points" => (int)$stats->social_points
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "无法获取统计数据"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "错误: " . $e->getMessage()]);
}
?> 