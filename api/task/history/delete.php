<?php
// 允许跨域请求
require_once '../../config/cors.php';

// 引入必要的文件
require_once '../../config/Database.php';
require_once '../../models/TaskHistory.php';

// 设置响应格式
header('Content-Type: application/json');

// 只允许DELETE请求
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    // 允许OPTIONS请求（预检请求）
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Method not allowed. Please use DELETE.']);
    exit;
}

// 获取提交的数据
$data = json_decode(file_get_contents('php://input'));

// 检查必要字段
if (!isset($data->id) || !isset($data->user_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Missing required fields: id and user_id']);
    exit;
}

try {
    // 数据库连接
    $database = new Database();
    $db = $database->connect();
    
    // 初始化任务历史对象
    $task_history = new TaskHistory($db);
    
    // 设置属性
    $task_history->id = $data->id;
    $task_history->user_id = $data->user_id;
    
    // 删除历史记录
    if ($task_history->delete()) {
        http_response_code(200);
        echo json_encode(['message' => 'Task history deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Task history not found or could not be deleted']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 