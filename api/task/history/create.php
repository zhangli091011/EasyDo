<?php
// 允许跨域请求
require_once '../../config/cors.php';

// 引入必要的文件
require_once '../../config/Database.php';
require_once '../../models/TaskHistory.php';

// 设置响应格式
header('Content-Type: application/json');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Method not allowed. Please use POST.']);
    exit;
}

// 获取提交的数据
$data = json_decode(file_get_contents('php://input'));

// 检查必要字段
if (
    !isset($data->user_id) || 
    !isset($data->task_text) || 
    empty($data->task_text)
) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Missing required fields']);
    exit;
}

try {
    // 数据库连接
    $database = new Database();
    $db = $database->connect();
    
    // 初始化任务历史对象
    $task_history = new TaskHistory($db);
    
    // 设置属性
    $task_history->user_id = $data->user_id;
    $task_history->task_id = $data->task_id ?? null; // 设置task_id（如果提供）
    $task_history->task_text = $data->task_text;
    $task_history->color_index = $data->color_index ?? 0;
    
    // 设置任务分类（如果提供）
    $task_history->task_category = $data->task_category ?? null;
    
    // 设置标签（如果提供）
    $task_history->tags = $data->tags ?? null;
    
    // 设置完成时间（如果未提供，使用当前时间）
    $task_history->completed_at = $data->completed_at ?? date('Y-m-d H:i:s');
    
    // 保存任务历史
    if ($task_history->create()) {
        // 获取最新的统计数据
        $task_history->user_id = $data->user_id;
        $stats = $task_history->getCompletionStats();
        
        http_response_code(201); // Created
        echo json_encode([
            'message' => 'Task history created',
            'stats' => $stats
        ]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['message' => 'Failed to create task history']);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 