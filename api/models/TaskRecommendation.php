<?php
class TaskRecommendation {
    // 数据库连接和表名
    private $conn;
    private $table_name = "task_recommendations";
    
    // 对象属性
    public $id;
    public $user_id;
    public $activities;
    public $tag;
    public $created_at;
    public $is_used;
    
    // 构造函数
    public function __construct($db) {
        $this->conn = $db;
        $this->createTable();
    }
    
    // 创建表
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activities VARCHAR(255) NOT NULL,
            tag VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_used BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // 创建任务推荐
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                (user_id, activities, tag)
                VALUES
                (:user_id, :activities, :tag)";
        
        $stmt = $this->conn->prepare($query);
        
        // 净化数据
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->activities = htmlspecialchars(strip_tags($this->activities));
        $this->tag = htmlspecialchars(strip_tags($this->tag));
        
        // 绑定参数
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":activities", $this->activities);
        $stmt->bindParam(":tag", $this->tag);
        
        // 执行查询
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // 批量创建任务推荐
    public function createMultiple($recommendations) {
        $this->conn->beginTransaction();
        
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (user_id, activities, tag)
                    VALUES
                    (:user_id, :activities, :tag)";
            
            $stmt = $this->conn->prepare($query);
            
            foreach($recommendations as $rec) {
                // 绑定参数
                $stmt->bindParam(":user_id", $this->user_id);
                $stmt->bindParam(":activities", $rec['activities']);
                $stmt->bindParam(":tag", $rec['tag']);
                
                $stmt->execute();
            }
            
            $this->conn->commit();
            return true;
            
        } catch(Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    // 获取用户未使用的推荐
    public function readUnusedByUser() {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE user_id = ? AND is_used = FALSE 
                ORDER BY created_at DESC
                LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // 获取用户最近的推荐（无论是否使用）
    public function readRecentByUser() {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE user_id = ? 
                ORDER BY created_at DESC
                LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // 标记推荐为已使用
    public function markAsUsed() {
        $query = "UPDATE " . $this->table_name . " 
                SET is_used = TRUE 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->user_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // 删除过期的推荐（例如超过7天的）
    public function deleteOldRecommendations() {
        $query = "DELETE FROM " . $this->table_name . " 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $stmt = $this->conn->prepare($query);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?> 