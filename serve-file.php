<?php
// Prevent direct file access - serve through proper handler
// This ensures correct MIME types and headers

session_start();

$file_path = isset($_GET['file']) ? $_GET['file'] : '';

// Sanitize input - prevent directory traversal
$file_path = basename($file_path);

// Only allow model files
if (!preg_match('/\.(glb|gltf)$/i', $file_path)) {
    http_response_code(403);
    die('Forbidden');
}

$full_path = __DIR__ . '/assets/models/' . $file_path;

// Check if file exists
if (!file_exists($full_path)) {
    http_response_code(404);
    die('File not found');
}

$mime_types = [
    'glb' => 'model/gltf-binary',
    'gltf' => 'model/gltf+json'
];

$ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
$mime_type = $mime_types[$ext] ?? 'application/octet-stream';

// Set headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($full_path));
header('Cache-Control: public, max-age=86400');
header('Access-Control-Allow-Origin: *');

// Serve file
readfile($full_path);
exit();
?>
