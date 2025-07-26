<?php
class TaskHistory {
    private $conn;
    private $table_name = "task_history";
    
    // Task properties
    public $id;
    public $user_id;
    public $task_id;  // 添加task_id属性
    public $task_text;
    public $color_index;
    public $completed_at;
    public $created_at;
    public $task_category;
    public $tags;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->createTable();
    }
    
    // Create table if not exists
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            task_id INT(11) NULL,  /* 允许为NULL */
            task_text VARCHAR(255) NOT NULL,
            color_index INT(11) DEFAULT 0,
            task_category VARCHAR(50) DEFAULT NULL,
            tags VARCHAR(255) DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            CONSTRAINT task_history_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $this->conn->exec($query);
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Create task history entry
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET
                    user_id = :user_id,
                    task_id = :task_id,
                    task_text = :task_text,
                    color_index = :color_index,
                    task_category = :task_category,
                    tags = :tags,
                    completed_at = :completed_at";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->task_id = !empty($this->task_id) ? htmlspecialchars(strip_tags($this->task_id)) : null;
        $this->task_text = htmlspecialchars(strip_tags($this->task_text));
        $this->color_index = htmlspecialchars(strip_tags($this->color_index));
        $this->task_category = htmlspecialchars(strip_tags($this->task_category));
        $this->tags = htmlspecialchars(strip_tags($this->tags));
        $this->completed_at = htmlspecialchars(strip_tags($this->completed_at));
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':task_id', $this->task_id, PDO::PARAM_INT);
        $stmt->bindParam(':task_text', $this->task_text);
        $stmt->bindParam(':color_index', $this->color_index);
        $stmt->bindParam(':task_category', $this->task_category);
        $stmt->bindParam(':tags', $this->tags);
        $stmt->bindParam(':completed_at', $this->completed_at);
        
        // Execute query
        try {
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Read all history entries for a specific user
    public function readUserHistory() {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE user_id = :user_id 
                ORDER BY completed_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Read history entries for a specific user with filtering
    public function readUserHistoryWithFilter($month = null, $category = null, $limit = null) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE user_id = :user_id";
                
        // Add month filter if provided
        if($month) {
            // Extract year and month from the parameter (format: YYYY-MM)
            list($year, $month) = explode('-', $month);
            $query .= " AND YEAR(completed_at) = :year AND MONTH(completed_at) = :month";
        }
        
        // Add category filter if provided
        if($category) {
            $query .= " AND task_category = :category";
        }
        
        $query .= " ORDER BY completed_at DESC";
        
        // Add limit if provided
        if($limit) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Bind month parameters if provided
        if($month) {
            $stmt->bindParam(':year', $year);
            $stmt->bindParam(':month', $month);
        }
        
        // Bind category if provided
        if($category) {
            $stmt->bindParam(':category', $category);
        }
        
        // Bind limit if provided
        if($limit) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get task history count for a user
    public function getUserHistoryCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind user_id
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ?? 0;
    }
    
    // Delete task history entry
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        try {
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Get available months with history records for a user
    public function getAvailableMonths() {
        $query = "SELECT DISTINCT DATE_FORMAT(completed_at, '%Y-%m') as month 
                FROM " . $this->table_name . " 
                WHERE user_id = :user_id 
                ORDER BY month DESC";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind user_id
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        $stmt->execute();
        
        $months = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $months[] = $row['month'];
        }
        
        return $months;
    }
    
    // Get stats about completed tasks
    public function getCompletionStats() {
        $query = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN DATE(completed_at) = CURDATE() THEN 1 ELSE 0 END) as completed_today,
                    SUM(CASE WHEN DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as completed_this_week,
                    SUM(CASE WHEN DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as completed_this_month
                FROM " . $this->table_name . " 
                WHERE user_id = :user_id";
                
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind user_id
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?> 