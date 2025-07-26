<?php
// 配置信息
$host = "45.207.194.163";
$username = "advx";
$password = "adventurex";
$database = "advx";

try {
    // 创建连接
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected successfully. <br/>";
    
    // 创建数据库（如果不存在）
    $sql = "CREATE DATABASE IF NOT EXISTS $database";
    $conn->exec($sql);
    echo "Database created or exists already. <br/>";
    
    // 切换到该数据库
    $conn->exec("USE $database");
    
    // 禁用外键检查，以便我们可以无障碍地删除表
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 删除原有的表（如果存在）
    $tables = array("users", "tasks", "settings", "task_history");
    foreach($tables as $table) {
        $conn->exec("DROP TABLE IF EXISTS $table");
        echo "Table $table dropped. <br/>";
    }
    
    // 重新启用外键检查
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 创建users表
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        mbti VARCHAR(10) DEFAULT NULL,
        hobbies TEXT DEFAULT NULL,
        interests_weighted JSON DEFAULT NULL,
        profile_description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "Table 'users' created successfully. <br/>";
    
    // 创建tasks表
    $sql = "CREATE TABLE tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        text VARCHAR(255) NOT NULL,
        color_index INT DEFAULT 0,
        completed BOOLEAN DEFAULT FALSE,
        completed_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $conn->exec($sql);
    echo "Table 'tasks' created successfully. <br/>";
    
    // 创建settings表
    $sql = "CREATE TABLE settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        setting_key VARCHAR(50) NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY user_setting (user_id, setting_key),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $conn->exec($sql);
    echo "Table 'settings' created successfully. <br/>";
    
    // 创建task_history表
    $sql = "CREATE TABLE task_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        task_text VARCHAR(255) NOT NULL,
        color_index INT DEFAULT 0,
        task_category VARCHAR(50) DEFAULT NULL,
        tags VARCHAR(255) DEFAULT NULL,
        completed_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $conn->exec($sql);
    echo "Table 'task_history' created successfully. <br/>";
    
    // 创建测试用户
    $password_hash = password_hash('test123', PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO users (username, password, mbti, hobbies, interests_weighted) 
            VALUES ('test', '$password_hash', 'INTJ', '阅读,编程,冥想', '{\"阅读\": 0.9, \"编程\": 0.7, \"冥想\": 0.5}')";
    
    $conn->exec($sql);
    $lastUserId = $conn->lastInsertId();
    echo "Test user created with ID: $lastUserId <br/>";
    
    // 创建测试任务
    $tasks = array(
        array("text" => "完成报告", "color_index" => 0),
        array("text" => "购买食材", "color_index" => 1),
        array("text" => "阅读一小时", "color_index" => 2)
    );
    
    foreach($tasks as $task) {
        $sql = "INSERT INTO tasks (user_id, text, color_index) 
                VALUES ('$lastUserId', '{$task['text']}', '{$task['color_index']}')";
        $conn->exec($sql);
        echo "Test task '{$task['text']}' created. <br/>";
    }
    
    // 创建测试设置
    $sql = "INSERT INTO settings (user_id, setting_key, setting_value) 
            VALUES ('$lastUserId', 'difficulty_mode', 'normal')";
    
    $conn->exec($sql);
    echo "Test setting created. <br/>";
    
    // 创建测试历史任务
    $historyTasks = array(
        array("text" => "制作演示文稿", "color_index" => 0, "category" => "学习任务", "completed_at" => date('Y-m-d H:i:s', strtotime('-1 day'))),
        array("text" => "整理房间", "color_index" => 1, "category" => "生活任务", "completed_at" => date('Y-m-d H:i:s', strtotime('-2 day'))),
        array("text" => "参加会议", "color_index" => 2, "category" => "工作任务", "completed_at" => date('Y-m-d H:i:s', strtotime('-3 day')))
    );
    
    foreach($historyTasks as $task) {
        $sql = "INSERT INTO task_history (user_id, task_text, color_index, task_category, completed_at) 
                VALUES ('$lastUserId', '{$task['text']}', '{$task['color_index']}', '{$task['category']}', '{$task['completed_at']}')";
        $conn->exec($sql);
        echo "Test history task '{$task['text']}' created. <br/>";
    }
    
    echo "Database setup completed successfully.";
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// 关闭连接
$conn = null;
?> 