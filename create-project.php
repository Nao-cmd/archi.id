<?php
// Start session dan include config
session_start();
require_once 'config/database.php';
require_once 'config/session.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Cek method request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Validasi input
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');
$price = intval($_POST['price'] ?? 0);

if (empty($title) || empty($description) || empty($location) || $price <= 0) {
    $_SESSION['error'] = 'Mohon isi semua field yang wajib.';
    header('Location: dashboard.php?tab=create');
    exit();
}

// Handle file upload - Thumbnail
$image_thumbnail = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($_FILES['image']['type'], $allowed_types)) {
        $_SESSION['error'] = 'Format gambar tidak didukung. Gunakan JPEG, PNG, GIF, atau WebP.';
        header('Location: dashboard.php?tab=create');
        exit();
    }
    
    if ($_FILES['image']['size'] > $max_size) {
        $_SESSION['error'] = 'Ukuran gambar terlalu besar (maksimal 5MB).';
        header('Location: dashboard.php?tab=create');
        exit();
    }
    
    $upload_dir = 'assets/projects/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
        $image_thumbnail = $filepath;
    } else {
        $_SESSION['error'] = 'Gagal mengupload gambar.';
        header('Location: dashboard.php?tab=create');
        exit();
    }
} else {
    $_SESSION['error'] = 'Gambar thumbnail wajib diupload.';
    header('Location: dashboard.php?tab=create');
    exit();
}

// Handle file upload - Model 3D (optional)
$model_3d_file = '';
if (isset($_FILES['model_3d']) && $_FILES['model_3d']['error'] === UPLOAD_ERR_OK) {
    $allowed_3d_types = ['model/gltf-binary', 'model/gltf+json', 'application/octet-stream'];
    $max_3d_size = 50 * 1024 * 1024; // 50MB
    
    if ($_FILES['model_3d']['size'] > $max_3d_size) {
        $_SESSION['error'] = 'Ukuran model 3D terlalu besar (maksimal 50MB).';
        header('Location: dashboard.php?tab=create');
        exit();
    }
    
    $upload_dir = 'assets/models/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename_3d = 'model_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($_FILES['model_3d']['name'], PATHINFO_EXTENSION);
    $filepath_3d = $upload_dir . $filename_3d;
    
    if (move_uploaded_file($_FILES['model_3d']['tmp_name'], $filepath_3d)) {
        $model_3d_file = $filepath_3d;
    }
}

// Insert ke database
$query = "INSERT INTO projects (user_id, title, description, location, price, image_thumbnail, model_3d_file, status, created_at, updated_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $conn->prepare($query);
if (!$stmt) {
    $_SESSION['error'] = 'Database error: ' . $conn->error;
    header('Location: dashboard.php?tab=create');
    exit();
}

$status = 'active';
$stmt->bind_param('isssisss', $user_id, $title, $description, $location, $price, $image_thumbnail, $model_3d_file, $status);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Proyek berhasil dipublikasikan!';
    header('Location: dashboard.php');
    exit();
} else {
    $_SESSION['error'] = 'Gagal menyimpan proyek: ' . $stmt->error;
    header('Location: dashboard.php?tab=create');
    exit();
}
?>
