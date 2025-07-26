<?php
// Include CORS handling
require_once '../config/cors.php';

// Content type
header("Content-Type: application/json; charset=UTF-8");

// Include database and model files
include_once '../config/Database.php';
include_once '../models/Task.php';
include_once '../models/User.php';

// 获取数据库连接
$database = new Database();
$db = $database->getConnection();

// 初始化对象
$task = new Task($db);
$user = new User($db);

// 获取POST请求数据
$data = json_decode(file_get_contents("php://input"));

// 检查必要的数据是否提供
if (!empty($data->user_id)) {
    try {
        // 设置用户ID
        $user->id = $data->user_id;
        
        // 获取用户的必做任务
        $task->user_id = $data->user_id;
        $existingTasks = $task->readUserTasks();
        
        // 提取MBTI和兴趣爱好信息
        $mbti = !empty($data->mbti) ? $data->mbti : null;
        $interests = !empty($data->interests) ? $data->interests : [];
        
        // 调用推荐函数（这里可以集成AI/Agent）
        $recommendations = getTaskRecommendations(
            $existingTasks, 
            $mbti, 
            $interests,
            $user->id
        );
        
        // 返回推荐结果
        http_response_code(200);
        echo json_encode([
            "message" => "任务推荐获取成功",
            "recommendations" => $recommendations
        ]);
        
    } catch (Exception $e) {
        // 服务器错误
        http_response_code(500);
        echo json_encode([
            "message" => "服务器错误：" . $e->getMessage()
        ]);
    }
} else {
    // 数据不完整
    http_response_code(400);
    echo json_encode(["message" => "无法推荐任务。数据不完整。"]);
}

/**
 * 生成任务推荐
 * 
 * @param array $existingTasks 用户现有任务
 * @param string $mbti 用户MBTI类型
 * @param array $interests 用户兴趣爱好
 * @param int $userId 用户ID
 * @return array 推荐任务列表
 */
function getTaskRecommendations($existingTasks, $mbti, $interests, $userId) {
    // 存储推荐任务
    $recommendations = [];
    
    // 根据MBTI类型推荐任务
    $mbtiTasks = getMbtiBasedTasks($mbti);
    $recommendations = array_merge($recommendations, $mbtiTasks);
    
    // 根据兴趣爱好推荐任务
    $interestTasks = getInterestBasedTasks($interests);
    $recommendations = array_merge($recommendations, $interestTasks);
    
    // 检查是否有外部API/Agent配置（预留扩展接口）
    $externalRecommendations = callExternalRecommendationService($userId, $mbti, $interests, $existingTasks);
    if (!empty($externalRecommendations)) {
        // 如果外部推荐可用，则使用外部推荐
        $recommendations = array_merge($recommendations, $externalRecommendations);
    }
    
    // 确保任务不重复
    $recommendations = filterDuplicateTasks($recommendations, $existingTasks);
    
    // 最多返回5个推荐
    return array_slice($recommendations, 0, 5);
}

/**
 * 基于MBTI类型推荐任务
 */
function getMbtiBasedTasks($mbti) {
    $tasks = [];
    
    if (empty($mbti)) {
        return $tasks;
    }
    
    // 根据MBTI的第一个字母推荐
    $firstLetter = substr($mbti, 0, 1);
    
    if ($firstLetter == 'I') {  // 内向型
        $tasks[] = [
            "text" => "在安静环境中冥想20分钟",
            "color_index" => 0
        ];
        $tasks[] = [
            "text" => "记录今日三个感恩的事物",
            "color_index" => 2
        ];
    } else if ($firstLetter == 'E') {  // 外向型
        $tasks[] = [
            "text" => "与朋友进行一次深度交流",
            "color_index" => 1
        ];
        $tasks[] = [
            "text" => "参加一个小组活动或线上讨论",
            "color_index" => 0
        ];
    }
    
    // 根据MBTI的第二个字母推荐
    $secondLetter = substr($mbti, 1, 1);
    
    if ($secondLetter == 'N') {  // 直觉型
        $tasks[] = [
            "text" => "阅读一本关于新概念的书籍",
            "color_index" => 2
        ];
    } else if ($secondLetter == 'S') {  // 感觉型
        $tasks[] = [
            "text" => "组织整理个人空间",
            "color_index" => 1
        ];
    }
    
    return $tasks;
}

/**
 * 基于兴趣推荐任务
 */
function getInterestBasedTasks($interests) {
    $tasks = [];
    
    // 兴趣任务映射表
    $interestTaskMap = [
        "阅读" => [
            ["text" => "阅读15分钟的新书", "color_index" => 0],
            ["text" => "整理读书笔记", "color_index" => 2]
        ],
        "电影" => [
            ["text" => "观看一部经典电影", "color_index" => 1],
            ["text" => "与朋友讨论最近看的电影", "color_index" => 0]
        ],
        "音乐" => [
            ["text" => "聆听一首新的音乐作品", "color_index" => 2],
            ["text" => "学习一段新的乐器片段", "color_index" => 0]
        ],
        "旅行" => [
            ["text" => "规划下一次旅行路线", "color_index" => 1],
            ["text" => "整理过去旅行的照片", "color_index" => 2]
        ],
        "摄影" => [
            ["text" => "在日常生活中寻找三个摄影主题", "color_index" => 0],
            ["text" => "学习一种新的摄影技巧", "color_index" => 1]
        ],
        "美食" => [
            ["text" => "尝试烹饪一道新菜", "color_index" => 2],
            ["text" => "记录一家值得推荐的餐厅", "color_index" => 0]
        ],
        "运动" => [
            ["text" => "进行30分钟有氧运动", "color_index" => 1],
            ["text" => "学习一套新的拉伸动作", "color_index" => 2]
        ],
        "艺术" => [
            ["text" => "参观一个艺术展览或线上展", "color_index" => 0],
            ["text" => "尝试一种新的艺术表现形式", "color_index" => 1]
        ],
        "科技" => [
            ["text" => "了解一项新兴科技趋势", "color_index" => 2],
            ["text" => "优化个人数字工作流程", "color_index" => 0]
        ],
        "时尚" => [
            ["text" => "整理衣橱，尝试新搭配", "color_index" => 1],
            ["text" => "了解可持续时尚理念", "color_index" => 2]
        ],
        "游戏" => [
            ["text" => "尝试一款新类型的游戏", "color_index" => 0],
            ["text" => "与朋友一起进行多人游戏", "color_index" => 1]
        ],
        "健身" => [
            ["text" => "制定每周健身计划", "color_index" => 2],
            ["text" => "学习正确的姿势做一个新动作", "color_index" => 0]
        ]
    ];
    
    // 基于用户兴趣生成任务
    foreach ($interests as $interest) {
        if (isset($interestTaskMap[$interest])) {
            // 从每个兴趣随机选一个任务
            $randomTask = $interestTaskMap[$interest][array_rand($interestTaskMap[$interest])];
            $tasks[] = $randomTask;
        }
    }
    
    // 如果没有基于兴趣的推荐，添加一些通用任务
    if (empty($tasks)) {
        $tasks[] = [
            "text" => "制定本周个人目标",
            "color_index" => 0
        ];
        $tasks[] = [
            "text" => "学习一项新技能",
            "color_index" => 1
        ];
        $tasks[] = [
            "text" => "与家人或朋友保持联系",
            "color_index" => 2
        ];
    }
    
    return $tasks;
}

/**
 * 调用外部推荐服务（如Coze或其他Agent）
 * 预留接口，当Agent准备好时可以实现
 */
function callExternalRecommendationService($userId, $mbti, $interests, $existingTasks) {
    // 此功能预留用于调用外部AI服务获取推荐
    // 可以实现与Coze API或其他AI服务的集成
    
    // 示例： 如果需要调用Coze API
    /*
    $url = 'https://api.coze.cn/v1/workflow/run';
    $apiToken = getenv('COZE_API_TOKEN') ?: 'your_api_token_here';
    
    // 准备请求数据
    $requestData = [
        'workflow_id' => 'your_workflow_id',
        'parameters' => [
            'user_id' => $userId,
            'mbti' => $mbti,
            'interests' => $interests,
            'existing_tasks' => $existingTasks
        ]
    ];
    
    // 发送API请求
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
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 如果请求成功
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        if (isset($responseData['data']['recommendations'])) {
            return $responseData['data']['recommendations'];
        }
    }
    */
    
    // 当前返回空数组，表示没有外部推荐
    return [];
}

/**
 * 过滤掉与现有任务重复的推荐
 */
function filterDuplicateTasks($recommendations, $existingTasks) {
    $filteredRecommendations = [];
    $existingTexts = [];
    
    // 收集现有任务的文本
    if (is_array($existingTasks)) {
        foreach ($existingTasks as $task) {
            if (isset($task['text'])) {
                $existingTexts[] = strtolower($task['text']);
            }
        }
    }
    
    // 过滤掉重复的推荐
    foreach ($recommendations as $recommendation) {
        if (!in_array(strtolower($recommendation['text']), $existingTexts)) {
            $filteredRecommendations[] = $recommendation;
            $existingTexts[] = strtolower($recommendation['text']); // 防止推荐中也有重复
        }
    }
    
    return $filteredRecommendations;
} 