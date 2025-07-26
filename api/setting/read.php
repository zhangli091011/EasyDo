<?php
// Include CORS handling
require_once '../config/cors.php';

// Content type
header("Content-Type: application/json; charset=UTF-8");

// Include database and object files
include_once '../config/Database.php';
include_once '../models/Setting.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize setting object
$setting = new Setting($db);

// Check if user_id is set in the URL
if(isset($_GET["user_id"])) {
    $setting->user_id = $_GET["user_id"];
    
    // Check if a specific setting is requested
    if(isset($_GET["setting_key"])) {
        $setting->setting_key = $_GET["setting_key"];
        
        // Read the specific setting
        if($setting->read()) {
            // Create array
            $setting_arr = array(
                "id" => $setting->id,
                "user_id" => $setting->user_id,
                "setting_key" => $setting->setting_key,
                "setting_value" => $setting->setting_value,
                "created" => $setting->created,
                "modified" => $setting->modified
            );
            
            // Set response code - 200 OK
            http_response_code(200);
            
            // Make it json format
            echo json_encode($setting_arr);
        } else {
            // Set response code - 404 Not found
            http_response_code(404);
            
            // Tell the user setting does not exist
            echo json_encode(array("message" => "设置不存在"));
        }
    } else {
        // Read all settings for the user
        $stmt = $setting->readAll();
        $num = $stmt->rowCount();
        
        // Check if any settings found
        if($num > 0) {
            // Settings array
            $settings_arr = array();
            $settings_arr["records"] = array();
            
            // Retrieve table contents
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Extract row
                extract($row);
                
                $setting_item = array(
                    "id" => $id,
                    "user_id" => $user_id,
                    "setting_key" => $setting_key,
                    "setting_value" => $setting_value,
                    "created" => $created,
                    "modified" => $modified
                );
                
                // Push to "records"
                array_push($settings_arr["records"], $setting_item);
            }
            
            // Set response code - 200 OK
            http_response_code(200);
            
            // Show settings data
            echo json_encode($settings_arr);
        } else {
            // Set response code - 200 OK
            http_response_code(200);
            
            // No settings found
            echo json_encode(array(
                "message" => "没有找到设置",
                "records" => array()
            ));
        }
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array("message" => "缺少用户ID参数"));
} 