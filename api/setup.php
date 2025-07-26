<?php
// This script updates all PHP API endpoints to include the CORS handler

// Function to update file content
function updateFile($filePath) {
    $content = file_get_contents($filePath);
    
    // Check if the file already includes the CORS handler
    if (strpos($content, "require_once '../config/cors.php'") !== false) {
        echo "File $filePath already updated.\n";
        return;
    }
    
    // Replace the old headers with the CORS include
    $pattern = "/\/\/ Headers\s+header\(\"Access-Control-Allow-Origin: \*\"\);\s+header\(\"Content-Type: application\/json; charset=UTF-8\"\);\s+header\(\"Access-Control-Allow-Methods: [^\"]+\"\);\s+header\(\"Access-Control-Max-Age: 3600\"\);\s+header\(\"Access-Control-Allow-Headers: [^\"]+\"\);/";
    $replacement = "// Include CORS handling\nrequire_once '../config/cors.php';\n\n// Content type\nheader(\"Content-Type: application/json; charset=UTF-8\");";
    
    $newContent = preg_replace($pattern, $replacement, $content);
    
    // If pattern not found, insert after <?php
    if ($newContent === $content) {
        $newContent = str_replace("<?php", "<?php\n// Include CORS handling\nrequire_once '../config/cors.php';\n", $content);
    }
    
    // Save the updated content
    file_put_contents($filePath, $newContent);
    echo "Updated $filePath\n";
}

// Directories to scan
$dirs = [
    __DIR__ . '/auth',
    __DIR__ . '/task',
    __DIR__ . '/setting'
];

// Loop through directories and update PHP files
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $files = glob("$dir/*.php");
        foreach ($files as $file) {
            updateFile($file);
        }
    }
}

echo "CORS setup completed.\n";
?> 