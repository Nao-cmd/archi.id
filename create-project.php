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
$category = trim($_POST['category'] ?? '');

if (empty($title) || empty($description) || empty($location) || empty($category)) {
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
if (isset($_FILES['model_3d']) && $_FILES['model_3d']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['model_3d']['error'] === UPLOAD_ERR_OK) {
        $max_3d_size = 50 * 1024 * 1024; // 50MB
        
        // Validate by extension, not MIME type (more reliable)
        $ext = strtolower(pathinfo($_FILES['model_3d']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['glb', 'gltf'];
        
        if (!in_array($ext, $allowed_ext)) {
            $_SESSION['error'] = 'Format model 3D harus GLB atau GLTF. File anda: .' . htmlspecialchars($ext);
            header('Location: dashboard.php?tab=create');
            exit();
        }
        
        if ($_FILES['model_3d']['size'] > $max_3d_size) {
            $_SESSION['error'] = 'Ukuran model 3D terlalu besar (maksimal 50MB). Ukuran file anda: ' . round($_FILES['model_3d']['size'] / 1024 / 1024, 2) . 'MB.';
            header('Location: dashboard.php?tab=create');
            exit();
        }
        
        // Ensure models directory exists
        $upload_dir = 'assets/models';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $_SESSION['error'] = 'Gagal membuat folder assets/models. Hubungi administrator.';
                header('Location: dashboard.php?tab=create');
                exit();
            }
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            $_SESSION['error'] = 'Folder assets/models tidak dapat ditulis. Hubungi administrator.';
            header('Location: dashboard.php?tab=create');
            exit();
        }
        
        $filename_3d = 'model_3d_' . $user_id . '_' . time() . '.' . $ext;
        $filepath_3d = $upload_dir . '/' . $filename_3d;
        
        if (move_uploaded_file($_FILES['model_3d']['tmp_name'], $filepath_3d)) {
            $model_3d_file = $filepath_3d;
        } else {
            $_SESSION['error'] = 'Gagal mengupload model 3D. Pastikan file tidak lebih dari 50MB dan folder assets/models/ dapat ditulis.';
            header('Location: dashboard.php?tab=create');
            exit();
        }
    } else {
        // Handle other upload errors
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi batas server)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi batas form)',
            UPLOAD_ERR_PARTIAL => 'File upload tidak lengkap',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak tersedia',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dibatalkan oleh extension PHP'
        ];
        $error_msg = $upload_errors[$_FILES['model_3d']['error']] ?? 'Error upload tidak diketahui';
        $_SESSION['error'] = 'Error upload model 3D: ' . $error_msg;
        header('Location: dashboard.php?tab=create');
        exit();
    }
}

// Insert ke database
$query = "INSERT INTO projects (user_id, title, description, location, category, image_thumbnail, model_3d_file, status, created_at, updated_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = $conn->prepare($query);
if (!$stmt) {
    $_SESSION['error'] = 'Database error: ' . $conn->error;
    header('Location: dashboard.php?tab=create');
    exit();
}

$status = 'active';
$stmt->bind_param('isssssss', $user_id, $title, $description, $location, $category, $image_thumbnail, $model_3d_file, $status);

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
