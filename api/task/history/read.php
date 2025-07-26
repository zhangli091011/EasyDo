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
    
    // 检查是否需要筛选
    $month = isset($_GET['month']) ? $_GET['month'] : null;
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    
    // 获取筛选后的历史记录
    if ($month || $category || $limit) {
        $stmt = $task_history->readUserHistoryWithFilter($month, $category, $limit);
    } else {
        $stmt = $task_history->readUserHistory();
    }
    
    // 获取可用的月份选项
    $available_months = $task_history->getAvailableMonths();
    
    // 获取完成任务的统计
    $stats = $task_history->getCompletionStats();
    
    // 检查是否有记录 - IMPORTANT: Always use rowCount() for PDOStatement objects, never use count($stmt)
    $num = $stmt->rowCount();
    
    if ($num > 0) {
        // 任务历史数组
        $task_history_arr = [];
        $task_history_arr['records'] = [];
        $task_history_arr['months'] = $available_months;
        $task_history_arr['stats'] = $stats;
        
        // 提取数据
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // 格式化数据
            $task_item = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'task_text' => $row['task_text'],
                'color_index' => $row['color_index'],
                'task_category' => $row['task_category'],
                'tags' => $row['tags'],
                'completed_at' => $row['completed_at'],
                'created_at' => $row['created_at']
            ];
            
            // 添加到数组
            array_push($task_history_arr['records'], $task_item);
        }
        
        // 设置响应码 - 200 成功
        http_response_code(200);
        
        // 发送JSON
        echo json_encode($task_history_arr);
    } else {
        // 没有找到历史记录
        http_response_code(200);
        echo json_encode([
            'message' => 'No task history found',
            'records' => [],
            'months' => $available_months,
            'stats' => $stats
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 