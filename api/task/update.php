<?php
// Include CORS handling
require_once '../config/cors.php';

// Content type
header("Content-Type: application/json; charset=UTF-8");

// Include database and model files
include_once '../config/Database.php';
include_once '../models/Task.php';

// 获取数据库连接
$database = new Database();
$db = $database->getConnection();

// 初始化Task对象
$task = new Task($db);

// 获取PUT请求数据
$data = json_decode(file_get_contents("php://input"));

// 检查必要的数据是否提供
if (!empty($data->id) && !empty($data->user_id)) {
    // 设置任务属性
    $task->id = $data->id;
    $task->user_id = $data->user_id;
    
    // 根据提供的数据设置属性
    if (isset($data->completed)) {
        $task->completed = $data->completed;
    }
    if (isset($data->text)) {
        $task->text = $data->text;
    }
    if (isset($data->color_index)) {
        $task->color_index = $data->color_index;
    }
    
    // 更新任务
    if ($task->update()) {
        // 成功
        http_response_code(200);
        echo json_encode(array("message" => "任务已更新"));
    } else {
        // 服务器错误
        http_response_code(503);
        echo json_encode(array("message" => "无法更新任务"));
    }
} else {
    // 数据不完整
    http_response_code(400);
    echo json_encode(array("message" => "无法更新任务，数据不完整"));
} 