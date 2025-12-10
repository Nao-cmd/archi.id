<?php
session_start();
require 'config/database.php';
require 'config/session.php';

requireLogin();

// Validasi ID
if (!isset($_GET['id'])) {
    die("ID Proyek tidak ditemukan.");
}

$project_id = intval($_GET['id']);
$user_id = $_SESSION['user_id']; // Kita tahu user_id karena sudah dicek login

// Ambil path file
$query = "SELECT model_3d_file, title FROM projects WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if (!$project || empty($project['model_3d_file'])) {
    die("File model tidak ditemukan untuk proyek ini.");
}

$full_path = __DIR__ . '/' . $project['model_3d_file'];

if (file_exists($full_path)) {
    // Paksa browser download
    $ext = pathinfo($full_path, PATHINFO_EXTENSION);
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $project['title']) . '.' . $ext;

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($full_path));
    
    readfile($full_path);
    exit;
} else {
    die("File fisik tidak ditemukan di server: " . $project['model_3d_file']);
}
?>