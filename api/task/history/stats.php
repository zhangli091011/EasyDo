<?php
// 允许跨域请求
require_once '../../config/cors.php';

// 引入必要的文件
require_once '../../config/Database.php';
require_once '../../models/TaskHistory.php';

// 设置响应格式
header('Content-Type: application/json');

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Method not allowed. Please use GET.']);
    exit;
}

// 检查必要参数
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing user_id parameter']);
    exit;
}

try {
    // 数据库连接
    $database = new Database();
    $db = $database->connect();
    
    // 初始化任务历史对象
    $task_history = new TaskHistory($db);
    $task_history->user_id = $_GET['user_id'];
    
    // 获取统计信息
    $stats = $task_history->getCompletionStats();
    
    // 获取可用的月份
    $available_months = $task_history->getAvailableMonths();
    
    // 计算每月完成的任务数量
    $monthly_stats = [];
    
    // 如果有月份数据
    if (!empty($available_months)) {
        foreach ($available_months as $month) {
            // 获取该月的任务历史
            $stmt = $task_history->readUserHistoryWithFilter($month);
            $monthly_stats[$month] = $stmt->rowCount();
        }
    }
    
    // 准备响应数据
    $response = [
        'stats' => $stats,
        'months' => $available_months,
        'monthly_stats' => $monthly_stats
    ];
    
    // 设置响应码
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 