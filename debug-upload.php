<?php
session_start();

// Debug folder structure and permissions
echo "<h2>Upload Debug Information</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>Not logged in. Please login first.</p>";
    exit;
}

// Get the root path
$root_path = dirname(__FILE__);
$models_dir = $root_path . '/assets/models';

echo "<h3>Path Information:</h3>";
echo "<ul>";
echo "<li>Root Path: " . htmlspecialchars($root_path) . "</li>";
echo "<li>Models Directory: " . htmlspecialchars($models_dir) . "</li>";
echo "</ul>";

echo "<h3>Folder Status:</h3>";
echo "<ul>";
echo "<li>Models folder exists: " . (is_dir($models_dir) ? 'YES' : 'NO') . "</li>";
echo "<li>Models folder is writable: " . (is_writable($models_dir) ? 'YES' : 'NO') . "</li>";
echo "<li>Models folder permissions: " . substr(sprintf('%o', fileperms($models_dir)), -4) . "</li>";
echo "</ul>";

// List all files in models folder
echo "<h3>Files in models folder:</h3>";
if (is_dir($models_dir)) {
    $files = scandir($models_dir);
    if (count($files) > 2) {
        echo "<ul>";
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "<li>" . htmlspecialchars($file) . " (" . filesize($models_dir . '/' . $file) . " bytes)</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>No files found in models folder</p>";
    }
} else {
    echo "<p style='color: red;'>Models folder does not exist!</p>";
}

// Test file upload
echo "<h3>Test File Upload:</h3>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='test_file' accept='.glb,.gltf'>";
echo "<button type='submit'>Upload Test File</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    $file = $_FILES['test_file'];
    echo "<h3>Upload Result:</h3>";
    echo "<ul>";
    echo "<li>Filename: " . htmlspecialchars($file['name']) . "</li>";
    echo "<li>Temp path: " . htmlspecialchars($file['tmp_name']) . "</li>";
    echo "<li>File size: " . $file['size'] . " bytes</li>";
    echo "<li>File type: " . htmlspecialchars($file['type']) . "</li>";
    echo "<li>Error: " . ($file['error'] === 0 ? 'No error' : 'Error code: ' . $file['error']) . "</li>";
    
    if ($file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'model_3d_test_' . time() . '.' . $ext;
        $destination = $models_dir . '/' . $new_filename;
        
        echo "<li>Destination: " . htmlspecialchars($destination) . "</li>";
        echo "<li>Temp file exists: " . (file_exists($file['tmp_name']) ? 'YES' : 'NO') . "</li>";
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo "<li style='color: green;'><strong>Upload SUCCESS!</strong></li>";
            echo "<li>File saved to: " . htmlspecialchars($new_filename) . "</li>";
        } else {
            echo "<li style='color: red;'><strong>Upload FAILED!</strong></li>";
            echo "<li>move_uploaded_file() returned false</li>";
        }
    }
    echo "</ul>";
}
?>
