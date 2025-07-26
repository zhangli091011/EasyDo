<?php
class Task {
    // Database connection and table name
    private $conn;
    private $table_name = "tasks";
    
    // Object properties
    public $id;
    public $user_id;
    public $text;
    public $color_index;
    public $completed;
    public $created_at;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create tasks table if not exists
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            text VARCHAR(255) NOT NULL,
            color_index INT DEFAULT 0,
            completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    
    // Create a new task
    public function create() {
        // First check if table exists, create if not
        $this->createTable();
        
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . " 
                SET user_id = :user_id, 
                    text = :text, 
                    color_index = :color_index";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->text = htmlspecialchars(strip_tags($this->text));
        $this->color_index = htmlspecialchars(strip_tags($this->color_index));
        
        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":text", $this->text);
        $stmt->bindParam(":color_index", $this->color_index);
        
        // Execute query
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // Get all tasks for a user
    public function readAll() {
        // First check if table exists, create if not
        $this->createTable();
        
        // Query to select all tasks for the user
        $query = "SELECT id, user_id, text, color_index, created_at 
                FROM " . $this->table_name . " 
                WHERE user_id = ? 
                ORDER BY created_at DESC";
        
        // Prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // Bind user_id
        $stmt->bindParam(1, $this->user_id);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Read single task
    public function readOne() {
        // Query to read single task
        $query = "SELECT id, user_id, text, color_index, created_at 
                FROM " . $this->table_name . " 
                WHERE id = ? AND user_id = ? 
                LIMIT 0,1";
        
        // Prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // Bind IDs
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->user_id);
        
        // Execute query
        $stmt->execute();
        
        // Get record details
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set values to object properties
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->text = $row['text'];
            $this->color_index = $row['color_index'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        
        return false;
    }
    
    // 更新任务
    public function update() {
        // 更新任务的SQL
        $query = "UPDATE " . $this->table_name . " 
                  SET ";
        
        $sets = array();
        
        // 检查每个字段是否需要更新
        if(isset($this->text)) {
            $sets[] = "text = :text";
        }
        if(isset($this->color_index)) {
            $sets[] = "color_index = :color_index";
        }
        if(isset($this->completed)) {
            $sets[] = "completed = :completed";
            
            // 如果标记为完成，添加完成时间
            if($this->completed == 1) {
                $sets[] = "completed_at = NOW()";
            } else {
                $sets[] = "completed_at = NULL";
            }
        }
        
        // 如果没有字段需要更新
        if(empty($sets)) {
            return false;
        }
        
        $query .= implode(", ", $sets);
        $query .= " WHERE id = :id AND user_id = :user_id";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        if(isset($this->text)) {
            $stmt->bindParam(":text", $this->text);
        }
        if(isset($this->color_index)) {
            $stmt->bindParam(":color_index", $this->color_index);
        }
        if(isset($this->completed)) {
            $stmt->bindParam(":completed", $this->completed);
        }
        
        // ID和user_id是必须的
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        
        // 执行查询
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete task
    public function delete() {
        // Query to delete record
        $query = "DELETE FROM " . $this->table_name . " 
                WHERE id = ? AND user_id = ?";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        
        // Bind parameters
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->user_id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // 读取用户任务
    public function readUserTasks() {
        // 查询读取用户任务
        $query = "SELECT id, user_id, text, color_index, created_at 
                  FROM " . $this->table_name . "
                  WHERE user_id = ?";
        
        // 准备查询语句
        $stmt = $this->conn->prepare($query);
        
        // 绑定参数
        $stmt->bindParam(1, $this->user_id);
        
        // 执行查询
        $stmt->execute();
        
        // 获取结果
        $tasks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = $row;
        }
        
        return $tasks;
    }
} 