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

$user_id = $_SESSION['user_id'];
$project_id = intval($_GET['id'] ?? 0);

if ($project_id === 0) {
    header('Location: dashboard.php');
    exit();
}

// Get project data
$query = "SELECT * FROM projects WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $project_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit();
}

$project = $result->fetch_assoc();

// Handle form submission
$update_message = '';
$update_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    if (empty($title) || empty($description) || empty($location)) {
        $update_error = 'Mohon isi semua field yang wajib.';
    } else {
        // Handle image update
        $image_thumbnail = $project['image_thumbnail'];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
                $upload_dir = 'assets/projects/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $filename = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                    // Delete old image
                    if (file_exists($project['image_thumbnail'])) {
                        unlink($project['image_thumbnail']);
                    }
                    $image_thumbnail = $filepath;
                }
            }
        }
        
        // Update database
        $update_query = "UPDATE projects SET title = ?, description = ?, location = ?, image_thumbnail = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('ssssii', $title, $description, $location, $image_thumbnail, $project_id, $user_id);
        
        if ($update_stmt->execute()) {
            $update_message = 'Proyek berhasil diperbarui!';
            // Refresh project data
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ii', $project_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $project = $result->fetch_assoc();
        } else {
            $update_error = 'Gagal memperbarui proyek.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Proyek - Archi.ID</title>
    <link rel="icon" href="assets/images/favicon.png?v=2" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php">Archi.ID</a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="edit-project-container">
        <div class="edit-project-content">
            <h1>Edit Proyek</h1>
            
            <?php if ($update_message): ?>
                <div class="alert alert-success"><?php echo $update_message; ?></div>
            <?php endif; ?>
            
            <?php if ($update_error): ?>
                <div class="alert alert-error"><?php echo $update_error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="edit-form">
                <div class="form-group">
                    <label for="title">Judul Proyek *</label>
                    <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($project['title']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi *</label>
                    <textarea id="description" name="description" required rows="5"><?php echo htmlspecialchars($project['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Lokasi *</label>
                        <input type="text" id="location" name="location" required value="<?php echo htmlspecialchars($project['location']); ?>">
                    </div>

                    <!-- Removed price field as it's no longer part of project schema -->
                </div>

                <div class="form-group">
                    <label>Gambar Saat Ini</label>
                    <div class="current-image">
                        <img src="<?php echo htmlspecialchars($project['image_thumbnail']); ?>" alt="Current image">
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Ganti Gambar (Optional)</label>
                    <div class="file-upload">
                        <input type="file" id="image" name="image" accept="image/*">
                        <span class="file-label">Pilih File atau Drag & Drop</span>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    <a href="dashboard.php" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
