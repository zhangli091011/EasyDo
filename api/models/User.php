<?php
class User {
    // Database connection and table name
    private $conn;
    private $table_name = "users";
    
    // Object properties
    public $id;
    public $username;
    public $password;
    public $mbti;
    public $hobbies;
    public $interests_weighted;
    public $profile_description;
    public $created_at;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create user table if not exists
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            mbti VARCHAR(10) DEFAULT NULL,
            hobbies TEXT DEFAULT NULL,
            interests_weighted JSON DEFAULT NULL,
            profile_description TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Register new user
    public function register() {
        // First check if table exists, create if not
        $this->createTable();
        
        // Check if username exists
        if($this->usernameExists()) {
            return false;
        }
        
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . " 
                SET username = :username, 
                    password = :password,
                    mbti = :mbti,
                    hobbies = :hobbies,
                    interests_weighted = :interests_weighted";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->mbti = $this->mbti ? htmlspecialchars(strip_tags($this->mbti)) : null;
        $this->hobbies = $this->hobbies ? htmlspecialchars(strip_tags($this->hobbies)) : null;
        // interests_weighted is JSON, we don't strip tags
        
        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":mbti", $this->mbti);
        $stmt->bindParam(":hobbies", $this->hobbies);
        $stmt->bindParam(":interests_weighted", $this->interests_weighted);
        
        // Execute query
        if($stmt->execute()) {
            // Get the new user ID
            $this->id = $this->conn->lastInsertId();
            return $this->id;
        }
        
        return false;
    }
    
    // Login user
    public function login() {
        // First check if table exists, create if not
        $this->createTable();
        
        // Query to check if username exists
        $query = "SELECT id, username, password, mbti, hobbies, interests_weighted, profile_description FROM " . $this->table_name . " 
                 WHERE username = ?";
        
        // Prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(1, $this->username);
        
        // Execute query
        $stmt->execute();
        
        // Get row count
        $num = $stmt->rowCount();
        
        // If user exists
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if(password_verify($this->password, $row['password'])) {
                // Set values to object properties for JWT token
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->mbti = $row['mbti'];
                $this->hobbies = $row['hobbies'];
                $this->interests_weighted = $row['interests_weighted'];
                $this->profile_description = $row['profile_description'];
                
                return true;
            }
        }
        
        return false;
    }
    
    // Check if username exists
    public function usernameExists() {
        // Query to check if username exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ?";
        
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        
        // Bind parameters
        $stmt->bindParam(1, $this->username);
        
        // Execute query
        $stmt->execute();
        
        // Get row count
        $num = $stmt->rowCount();
        
        // If username exists
        if($num > 0) {
            return true;
        }
        
        return false;
    }
    
    // Update user profile
    public function updateProfile() {
        // Query to update user profile
        $query = "UPDATE " . $this->table_name . "
                SET mbti = :mbti,
                    hobbies = :hobbies,
                    interests_weighted = :interests_weighted,
                    profile_description = :profile_description
                WHERE id = :id";
                
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->mbti = $this->mbti ? htmlspecialchars(strip_tags($this->mbti)) : null;
        $this->hobbies = $this->hobbies ? htmlspecialchars(strip_tags($this->hobbies)) : null;
        $this->profile_description = $this->profile_description ? htmlspecialchars(strip_tags($this->profile_description)) : null;
        
        // 确保interests_weighted是字符串
        if (is_object($this->interests_weighted) || is_array($this->interests_weighted)) {
            $this->interests_weighted = json_encode($this->interests_weighted, JSON_UNESCAPED_UNICODE);
        }
        
        // Bind values
        $stmt->bindParam(":mbti", $this->mbti);
        $stmt->bindParam(":hobbies", $this->hobbies);
        $stmt->bindParam(":interests_weighted", $this->interests_weighted);
        $stmt->bindParam(":profile_description", $this->profile_description);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Get user by ID
    public function readOne() {
        // Query to read single user
        $query = "SELECT username, mbti, hobbies, interests_weighted, profile_description, created_at
                FROM " . $this->table_name . "
                WHERE id = ?";
                
        // Prepare query
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Get row count
        $num = $stmt->rowCount();
        
        // If user exists
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set values to object properties
            $this->username = $row['username'];
            $this->mbti = $row['mbti'];
            $this->hobbies = $row['hobbies'];
            $this->interests_weighted = $row['interests_weighted'];
            $this->profile_description = $row['profile_description'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        
        return false;
    }
} 