<?php
// Include CORS handling
require_once 'config/cors.php';

// Content type
header("Content-Type: application/json; charset=UTF-8");

// Include database config
include_once 'config/Database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Check connection
if (!$db) {
    http_response_code(500);
    echo json_encode(array("message" => "数据库连接失败"));
    exit;
}

// Result array
$result = array(
    "steps" => [],
    "status" => "success",
    "errors" => []
);

// Flag to track if transaction was started
$transaction_started = false;

try {
    // Try to start transaction
    $transaction_started = $db->beginTransaction();
    $result["steps"][] = "事务开始: " . ($transaction_started ? "成功" : "失败");
    
    // If transaction failed to start, we'll continue without transaction safety
    if (!$transaction_started) {
        $result["steps"][] = "警告: 无法开启事务，将以非事务方式继续";
    }
    
    // Step 1: Drop all existing tables with CASCADE to avoid constraint issues
    $tables = ["tasks", "settings", "users", "user"];
    foreach ($tables as $table) {
        try {
            $sql = "DROP TABLE IF EXISTS `$table`";
            $db->exec($sql);
            $result["steps"][] = "删除表 $table (如果存在)";
        } catch (PDOException $e) {
            $result["steps"][] = "无法删除表 $table: " . $e->getMessage();
        }
    }
    
    // Step 2: Create users table
    $sql = "CREATE TABLE `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    $result["steps"][] = "创建 users 表";
    
    // Step 3: Create tasks table
    $sql = "CREATE TABLE `tasks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `text` VARCHAR(255) NOT NULL,
        `color_index` INT DEFAULT 0,
        `completed` BOOLEAN DEFAULT FALSE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    $result["steps"][] = "创建 tasks 表";
    
    // Step 4: Create settings table
    $sql = "CREATE TABLE `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `setting_key` VARCHAR(50) NOT NULL,
        `setting_value` TEXT,
        `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `user_setting` (`user_id`, `setting_key`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    $result["steps"][] = "创建 settings 表";
    
    // Step 5: Create a test user
    $username = "test";
    $password = password_hash("test123", PASSWORD_BCRYPT);
    $sql = "INSERT INTO `users` (`username`, `password`) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username, $password]);
    $userId = $db->lastInsertId();
    $result["steps"][] = "创建测试用户 (用户名: test, 密码: test123, ID: $userId)";
    
    // Step 6: Create some test tasks
    $taskTexts = ["完成项目报告", "与客户会面", "健身30分钟"];
    $colorIndexes = [0, 1, 2];
    
    for ($i = 0; $i < count($taskTexts); $i++) {
        $sql = "INSERT INTO `tasks` (`user_id`, `text`, `color_index`) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $taskTexts[$i], $colorIndexes[$i]]);
        $result["steps"][] = "为测试用户创建任务: " . $taskTexts[$i];
    }
    
    // Step 7: Create some test settings
    $sql = "INSERT INTO `settings` (`user_id`, `setting_key`, `setting_value`) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId, "difficulty_mode", "1"]);
    $result["steps"][] = "为测试用户创建设置: difficulty_mode = 1";
    
    // Commit the transaction only if one was successfully started
    if ($transaction_started) {
        $db->commit();
        $result["steps"][] = "事务提交成功";
    }
    
    $result["steps"][] = "数据库设置成功完成";
    
} catch (PDOException $e) {
    // If there was an error, roll back the transaction if one was started
    if ($transaction_started) {
        try {
            $db->rollBack();
            $result["steps"][] = "错误发生，事务已回滚";
        } catch (PDOException $rollbackEx) {
            $result["steps"][] = "事务回滚失败: " . $rollbackEx->getMessage();
        }
    }
    
    $result["status"] = "error";
    $result["errors"][] = $e->getMessage();
}

// Return the result
http_response_code(200);
echo json_encode($result, JSON_PRETTY_PRINT);
?> 