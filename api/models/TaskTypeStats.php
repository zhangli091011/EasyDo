<?php
class TaskTypeStats {
    // 数据库连接和表名
    private $conn;
    private $table_name = "task_type_stats";
    
    // 对象属性
    public $id;
    public $user_id;
    public $intellectual_points;
    public $physical_points;
    public $social_points;
    public $updated_at;
    
    // 构造函数，带有数据库连接
    public function __construct($db) {
        $this->conn = $db;
        $this->createTable();
    }
    
    // 如果表不存在，则创建表
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            intellectual_points INT DEFAULT 0,
            physical_points INT DEFAULT 0,
            social_points INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
    
    // 获取用户的统计数据，如果不存在则创建
    public function readOrCreate() {
        // 查询单个用户的统计数据
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ?";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定ID
        $stmt->bindParam(1, $this->user_id);
        
        // 执行查询
        $stmt->execute();
        
        // 获取行数
        $num = $stmt->rowCount();
        
        // 如果统计数据存在
        if($num > 0) {
            // 获取记录详情
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 设置对象属性的值
            $this->id = $row['id'];
            $this->intellectual_points = $row['intellectual_points'];
            $this->physical_points = $row['physical_points'];
            $this->social_points = $row['social_points'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        } else {
            // 创建新记录
            return $this->create();
        }
    }
    
    // 创建新的统计记录
    public function create() {
        // 插入记录的查询
        $query = "INSERT INTO " . $this->table_name . " 
                (user_id, intellectual_points, physical_points, social_points) 
                VALUES (:user_id, 0, 0, 0)";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 清理输入
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        
        // 绑定值
        $stmt->bindParam(':user_id', $this->user_id);
        
        // 执行查询
        if($stmt->execute()) {
            // 设置初始值
            $this->intellectual_points = 0;
            $this->physical_points = 0;
            $this->social_points = 0;
            return true;
        }
        
        return false;
    }
    
    // 增加特定类型的点数
    public function incrementPoints($type) {
        // 确保类型有效
        if(!in_array($type, ['intellectual', 'physical', 'social'])) {
            return false;
        }
        
        // 确保统计数据存在
        if(!$this->readOrCreate()) {
            return false;
        }
        
        // 增加点数的查询
        $query = "UPDATE " . $this->table_name . " 
                SET {$type}_points = {$type}_points + 1 
                WHERE user_id = :user_id";
        
        // 准备查询
        $stmt = $this->conn->prepare($query);
        
        // 绑定值
        $stmt->bindParam(':user_id', $this->user_id);
        
        // 执行查询
        if($stmt->execute()) {
            // 增加本地计数
            $property = $type . "_points";
            $this->$property++;
            return true;
        }
        
        return false;
    }
}
?> 