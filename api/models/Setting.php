<?php
class Setting {
    // Database connection and table name
    private $conn;
    private $table_name = "settings";
    
    // Object properties
    public $id;
    public $user_id;
    public $setting_key;
    public $setting_value;
    public $created;
    public $modified;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create settings table if not exists
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            setting_key VARCHAR(50) NOT NULL,
            setting_value TEXT,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY user_setting (user_id, setting_key),
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
    
    // Get a setting
    public function read() {
        // First check if table exists, create if not
        $this->createTable();
        
        // Query to get setting
        $query = "SELECT id, user_id, setting_key, setting_value, created, modified 
                FROM " . $this->table_name . " 
                WHERE user_id = ? AND setting_key = ? 
                LIMIT 0,1";
        
        // Prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->setting_key = htmlspecialchars(strip_tags($this->setting_key));
        
        // Bind parameters
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->setting_key);
        
        // Execute query
        $stmt->execute();
        
        // Get row count
        $num = $stmt->rowCount();
        
        // If setting exists
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set values to object properties
            $this->id = $row['id'];
            $this->setting_value = $row['setting_value'];
            $this->created = $row['created'];
            $this->modified = $row['modified'];
            
            return true;
        }
        
        return false;
    }
    
    // Create or update setting
    public function createOrUpdate() {
        // First check if table exists, create if not
        $this->createTable();
        
        // Check if setting exists
        if($this->read()) {
            // Update existing setting
            $query = "UPDATE " . $this->table_name . " 
                    SET setting_value = :setting_value 
                    WHERE user_id = :user_id AND setting_key = :setting_key";
        } else {
            // Insert new setting
            $query = "INSERT INTO " . $this->table_name . " 
                    SET user_id = :user_id, 
                        setting_key = :setting_key, 
                        setting_value = :setting_value";
        }
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->setting_key = htmlspecialchars(strip_tags($this->setting_key));
        $this->setting_value = htmlspecialchars(strip_tags($this->setting_value));
        
        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":setting_key", $this->setting_key);
        $stmt->bindParam(":setting_value", $this->setting_value);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Get all settings for a user
    public function readAll() {
        // First check if table exists, create if not
        $this->createTable();
        
        // Query to get all settings for a user
        $query = "SELECT id, user_id, setting_key, setting_value, created, modified 
                FROM " . $this->table_name . " 
                WHERE user_id = ?";
        
        // Prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // Bind user_id
        $stmt->bindParam(1, $this->user_id);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Delete a setting
    public function delete() {
        // Query to delete setting
        $query = "DELETE FROM " . $this->table_name . " 
                WHERE user_id = ? AND setting_key = ?";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->setting_key = htmlspecialchars(strip_tags($this->setting_key));
        
        // Bind parameters
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->setting_key);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
} 