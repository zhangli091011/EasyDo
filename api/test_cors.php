<?php
// Include CORS handling
require_once 'config/cors.php';

// Set content type
header("Content-Type: application/json; charset=UTF-8");

// Prepare response
$response = array(
    "status" => "success",
    "message" => "CORS test successful",
    "headers" => array(
        "origin" => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'Not set',
        "method" => $_SERVER['REQUEST_METHOD'],
        "request_headers" => getallheaders()
    ),
    "timestamp" => date("Y-m-d H:i:s")
);

// Return response
http_response_code(200);
echo json_encode($response);
?> 