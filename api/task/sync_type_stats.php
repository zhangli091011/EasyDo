<?php
// 允许跨域请求
require_once '../config/cors.php';

// 设置响应格式
header("Content-Type: application/json; charset=UTF-8");

// 引入必要的文件
include_once '../config/Database.php';
include_once '../models/TaskHistory.php';
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

try {
    // 连接数据库
    $database = new Database();
    $db = $database->getConnection();
    
    // 初始化对象
    $taskHistory = new TaskHistory($db);
    $taskHistory->user_id = $data->user_id;
    $taskTypeStats = new TaskTypeStats($db);
    $taskTypeStats->user_id = $data->user_id;
    
    // 获取用户的所有已完成任务历史
    $stmt = $taskHistory->readUserHistory();
    
    // 计数器
    $intellectualCount = 0;
    $physicalCount = 0;
    $socialCount = 0;
    $processedTasks = 0;
    
    // 处理每个历史任务
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // 尝试基于任务名称或类别确定任务类型
        $taskType = determineTaskType($row);
        
        if ($taskType) {
            switch ($taskType) {
                case 'intellectual':
                    $intellectualCount++;
                    break;
                case 'physical':
                    $physicalCount++;
                    break;
                case 'social':
                    $socialCount++;
                    break;
            }
            $processedTasks++;
        }
    }
    
    // 如果有处理过的任务，更新统计数据
    if ($processedTasks > 0) {
        // 确保用户在统计表中有记录
        $taskTypeStats->readOrCreate();
        
        // 手动更新点数
        $query = "UPDATE task_type_stats 
                SET intellectual_points = intellectual_points + :intellectual,
                    physical_points = physical_points + :physical,
                    social_points = social_points + :social
                WHERE user_id = :user_id";
                
        $stmt = $db->prepare($query);
        $stmt->bindParam(":intellectual", $intellectualCount, PDO::PARAM_INT);
        $stmt->bindParam(":physical", $physicalCount, PDO::PARAM_INT);
        $stmt->bindParam(":social", $socialCount, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $data->user_id);
        
        if ($stmt->execute()) {
            // 重新读取最新统计数据
            $taskTypeStats->readOrCreate();
            
            http_response_code(200);
            echo json_encode([
                "message" => "成功同步统计数据",
                "processed_tasks" => $processedTasks,
                "added_points" => [
                    "intellectual" => $intellectualCount,
                    "physical" => $physicalCount,
                    "social" => $socialCount
                ],
                "current_stats" => [
                    "intellectual_points" => (int)$taskTypeStats->intellectual_points,
                    "physical_points" => (int)$taskTypeStats->physical_points,
                    "social_points" => (int)$taskTypeStats->social_points
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "更新统计数据失败"]);
        }
    } else {
        http_response_code(200);
        echo json_encode(["message" => "没有找到可处理的任务历史"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "错误: " . $e->getMessage()]);
}

/**
 * 根据任务信息确定任务类型
 * 
 * @param array $taskData 任务数据
 * @return string|null 任务类型或null
 */
function determineTaskType($taskData) {
    // 如果任务有明确的类别信息
    if (isset($taskData['task_category'])) {
        $category = strtolower($taskData['task_category']);
        
        // 根据类别名称判断
        if (strpos($category, '学习') !== false || strpos($category, '知识') !== false || 
            strpos($category, '阅读') !== false || strpos($category, '读书') !== false) {
            return 'intellectual';
        }
        
        if (strpos($category, '运动') !== false || strpos($category, '锻炼') !== false || 
            strpos($category, '健身') !== false || strpos($category, '体力') !== false) {
            return 'physical';
        }
        
        if (strpos($category, '社交') !== false || strpos($category, '聚会') !== false || 
            strpos($category, '朋友') !== false || strpos($category, '交流') !== false) {
            return 'social';
        }
    }
    
    // 如果有任务文本，尝试从文本判断
    if (isset($taskData['task_text'])) {
        $text = strtolower($taskData['task_text']);
        
        // 智力任务关键词
        $intellectualKeywords = ['学习', '阅读', '读书', '思考', '研究', '写作', '计划', '分析', 
            '总结', '学校', '课程', '考试', '作业', '笔记', '文章', '报告', '编程', '设计'];
            
        // 体力任务关键词
        $physicalKeywords = ['运动', '锻炼', '健身', '跑步', '步行', '游泳', '骑车', '训练', 
            '户外', '散步', '伸展', '拉伸', '瑜伽', '打球', '健走', '体能'];
            
        // 社交任务关键词
        $socialKeywords = ['社交', '聚会', '朋友', '交流', '沟通', '聊天', '见面', '约会', 
            '电话', '团队', '合作', '讨论', '分享', '演讲', '访问', '拜访'];
        
        // 检查智力任务关键词
        foreach ($intellectualKeywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                return 'intellectual';
            }
        }
        
        // 检查体力任务关键词
        foreach ($physicalKeywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                return 'physical';
            }
        }
        
        // 检查社交任务关键词
        foreach ($socialKeywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                return 'social';
            }
        }
    }
    
    // 如果有颜色索引，可以基于颜色推测类型
    if (isset($taskData['color_index'])) {
        switch ($taskData['color_index']) {
            case 0: // 黄色通常用于智力任务
                return 'intellectual';
            case 1: // 粉色通常用于社交任务
                return 'social';
            case 2: // 绿色通常用于体力任务
                return 'physical';
        }
    }
    
    // 如果有标签信息
    if (isset($taskData['tags'])) {
        $tags = strtolower($taskData['tags']);
        
        if (strpos($tags, '智力') !== false || strpos($tags, '学习') !== false) {
            return 'intellectual';
        }
        
        if (strpos($tags, '体力') !== false || strpos($tags, '运动') !== false) {
            return 'physical';
        }
        
        if (strpos($tags, '社交') !== false || strpos($tags, '交流') !== false) {
            return 'social';
        }
    }
    
    // 无法确定类型
    return null;
}
?> 