<?php
// 允许跨域请求
require_once '../config/cors.php';

// 设置响应格式
header("Content-Type: application/json; charset=UTF-8");

// 引入必要的文件
include_once '../config/Database.php';
include_once '../models/TaskTypeStats.php';

// 检查是否是POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "方法不允许，请使用POST请求"]);
    exit;
}

// 获取请求数据
$data = json_decode(file_get_contents("php://input"));

// 验证数据
if (!isset($data->user_id) || empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(["message" => "缺少用户ID"]);
    exit;
}

if (!isset($data->task_type) || empty($data->task_type)) {
    http_response_code(400);
    echo json_encode(["message" => "缺少任务类型"]);
    exit;
}

// 确认任务类型有效
if (!in_array($data->task_type, ['intellectual', 'physical', 'social'])) {
    http_response_code(400);
    echo json_encode(["message" => "无效的任务类型"]);
    exit;
}

try {
    // 连接数据库
    $database = new Database();
    $db = $database->getConnection();
    
    // 初始化任务类型统计对象
    $stats = new TaskTypeStats($db);
    $stats->user_id = $data->user_id;
    
    // 增加点数
    if ($stats->incrementPoints($data->task_type)) {
        http_response_code(200);
        echo json_encode([
            "message" => "点数增加成功",
            "stats" => [
                "intellectual_points" => $stats->intellectual_points,
                "physical_points" => $stats->physical_points,
                "social_points" => $stats->social_points
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "无法增加点数"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "错误: " . $e->getMessage()]);
}
?> 