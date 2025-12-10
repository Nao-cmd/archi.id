<?php
session_start();
require 'config/database.php';
require 'config/session.php';

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $category = trim($_POST['category'] ?? '');
        
        if (empty($title) || empty($description) || empty($location)) {
            $errors[] = "Semua field harus diisi";
        } else {
            $image_thumbnail = '';
            $model_3d_file = '';
            
            // Handle thumbnail image upload
            if (!empty($_FILES['image_thumbnail']['name'])) {
                $file = $_FILES['image_thumbnail'];
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_ext)) {
                    $errors[] = "Format gambar harus JPG, JPEG, PNG, atau GIF";
                } elseif ($file['size'] > 5000000) { // 5MB
                    $errors[] = "Ukuran gambar maksimal 5MB";
                } else {
                    $filename = 'thumbnail_' . time() . '.' . $file_ext;
                    $filepath = 'assets/images/' . $filename;
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $image_thumbnail = $filepath;
                    } else {
                        $errors[] = "Gagal upload gambar thumbnail";
                    }
                }
            }
            
            // Handle 3D model file upload
            if (!empty($_FILES['model_3d_file']['name'])) {
                $file = $_FILES['model_3d_file'];
                $allowed_ext = ['glb', 'gltf'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_ext)) {
                    $errors[] = "Format model 3D harus GLB atau GLTF. File anda: ." . htmlspecialchars($file_ext);
                } elseif ($file['size'] > 50000000) { // 50MB
                    $errors[] = "Ukuran model 3D maksimal 50MB. Ukuran file anda: " . round($file['size'] / 1024 / 1024, 2) . "MB";
                } else {
                    $filename = 'model_3d_' . $user_id . '_' . time() . '.' . $file_ext;
                    $upload_dir = 'assets/models';
                    
                    error_log("[UPLOAD DEBUG] File: " . $file['name']);
                    error_log("[UPLOAD DEBUG] Temp: " . $file['tmp_name']);
                    error_log("[UPLOAD DEBUG] Size: " . $file['size']);
                    error_log("[UPLOAD DEBUG] Upload dir: " . realpath($upload_dir ?? '.'));
                    
                    // Ensure directory exists and is writable
                    if (!is_dir($upload_dir)) {
                        if (!@mkdir($upload_dir, 0755, true)) {
                            $errors[] = "Gagal membuat folder assets/models. Hubungi administrator.";
                            error_log("[UPLOAD DEBUG] Failed to create directory: " . $upload_dir);
                        }
                    }
                    
                    if (empty($errors) && !is_writable($upload_dir)) {
                        $errors[] = "Folder assets/models tidak dapat ditulis. Hubungi administrator.";
                        error_log("[UPLOAD DEBUG] Directory not writable: " . $upload_dir);
                    }
                    
                    if (empty($errors)) {
                        $filepath = $upload_dir . '/' . $filename;
                        
                        error_log("[UPLOAD DEBUG] Attempting move_uploaded_file from " . $file['tmp_name'] . " to " . $filepath);
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $model_3d_file = $filepath;
                            error_log("[UPLOAD DEBUG] File uploaded successfully to: " . $filepath);
                        } else {
                            $move_error = "Unknown error";
                            if (!file_exists($file['tmp_name'])) {
                                $move_error = "Temporary file not found";
                            } elseif (!is_uploaded_file($file['tmp_name'])) {
                                $move_error = "File is not an uploaded file (security check failed)";
                            }
                            $errors[] = "Gagal mengupload model 3D: " . $move_error;
                            error_log("[UPLOAD DEBUG] Failed to move file. Reason: " . $move_error);
                        }
                    }
                }
            }
            
            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO projects (user_id, title, description, location, category, image_thumbnail, model_3d_file, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("issssss", $user_id, $title, $description, $location, $category, $image_thumbnail, $model_3d_file);
                
                if ($stmt->execute()) {
                    $success[] = "Proyek berhasil ditambahkan";
                } else {
                    $errors[] = "Gagal menambahkan proyek";
                }
                $stmt->close();
            }
        }
    

        } elseif ($action === 'update') {
        $project_id = intval($_POST['project_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if (empty($title) || empty($description) || empty($location)) {
            $errors[] = "Semua field harus diisi";
        } else {
            // 1. Ambil data file lama dulu dari database
            $q_current = $conn->prepare("SELECT image_thumbnail, model_3d_file FROM projects WHERE id = ? AND user_id = ?");
            $q_current->bind_param("ii", $project_id, $user_id);
            $q_current->execute();
            $res_current = $q_current->get_result();
            $curr_project = $res_current->fetch_assoc();
            $q_current->close();

            // Default: gunakan file lama
            $final_thumbnail = $curr_project['image_thumbnail'];
            $final_model = $curr_project['model_3d_file'];

            // 2. Cek apakah ada upload Thumbnail baru
            if (!empty($_FILES['image_thumbnail']['name'])) {
                $file = $_FILES['image_thumbnail'];
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed) && $file['size'] <= 5000000) {
                    $new_name = 'thumbnail_' . time() . '_' . rand(100,999) . '.' . $ext;
                    $dest = 'assets/images/' . $new_name;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $final_thumbnail = $dest; // Update path jika sukses upload
                    }
                } else {
                    $errors[] = "Format gambar salah atau terlalu besar (Max 5MB)";
                }
            }

            // 3. Cek apakah ada upload Model 3D baru
            if (!empty($_FILES['model_3d_file']['name'])) {
                $file = $_FILES['model_3d_file'];
                $allowed = ['glb', 'gltf'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed) && $file['size'] <= 50000000) {
                    // Pastikan folder assets/models ada
                    if (!is_dir('assets/models')) mkdir('assets/models', 0755, true);
                    
                    $new_name = 'model_3d_' . $user_id . '_' . time() . '.' . $ext;
                    $dest = 'assets/models/' . $new_name;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $final_model = $dest; // Update path jika sukses upload
                    }
                } else {
                    $errors[] = "Format model harus GLB/GLTF atau terlalu besar (Max 50MB)";
                }
            }

            // 4. Update Database jika tidak ada error upload
            if (empty($errors)) {
                $stmt = $conn->prepare("UPDATE projects SET title = ?, description = ?, location = ?, category = ?, image_thumbnail = ?, model_3d_file = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ssssssii", $title, $description, $location, $category, $final_thumbnail, $final_model, $project_id, $user_id);
                
                if ($stmt->execute()) {
                    $success[] = "Proyek berhasil diperbarui";
                } else {
                    $errors[] = "Gagal memperbarui database: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    elseif ($action === 'delete') {
        $project_id = intval($_POST['project_id'] ?? 0);
        
        // 1. Ambil data file sebelum dihapus (untuk hapus file fisik)
        $q_check = $conn->prepare("SELECT image_thumbnail, model_3d_file FROM projects WHERE id = ? AND user_id = ?");
        $q_check->bind_param("ii", $project_id, $user_id);
        $q_check->execute();
        $res_check = $q_check->get_result();
        $data_to_delete = $res_check->fetch_assoc();
        $q_check->close();

        if ($data_to_delete) {
            // 2. HAPUS DULU REVIEW YANG TERKAIT (PENTING!)
            // Mencegah error foreign key constraint
            $del_reviews = $conn->prepare("DELETE FROM reviews WHERE project_id = ?");
            $del_reviews->bind_param("i", $project_id);
            $del_reviews->execute();
            $del_reviews->close();

            // 3. Hapus data proyek dari database
            $stmt = $conn->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $project_id, $user_id);
            
            if ($stmt->execute()) {
                // 4. Hapus file fisik dari folder assets
                if (!empty($data_to_delete['image_thumbnail']) && file_exists($data_to_delete['image_thumbnail'])) {
                    unlink($data_to_delete['image_thumbnail']);
                }
                if (!empty($data_to_delete['model_3d_file']) && file_exists($data_to_delete['model_3d_file'])) {
                    unlink($data_to_delete['model_3d_file']);
                }

                $success[] = "Proyek berhasil dihapus.";
            } else {
                $errors[] = "Gagal menghapus proyek: " . $conn->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Proyek tidak ditemukan atau Anda tidak memiliki izin.";
        }
    }
}


// Fetch user's projects
$stmt = $conn->prepare("SELECT id, title, description, location, category, image_thumbnail, model_3d_file, created_at FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects_result = $stmt->get_result();
$projects = $projects_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Project - Archi.ID</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
</head>
<body>
    <!-- Navigation Header -->
    <header class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <h1><a href="index.php" style="text-decoration: none; color: inherit;">Archi.ID</a></h1>
            </div>
            <nav class="navbar-menu">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="marketplace.php">Projects</a></li>
                    <li><a href="dashboard.php">My Project</a></li>
                </ul>
            </nav>
            <div class="navbar-auth">
                <!-- improved user menu styling with dropdown support -->
                <div class="user-menu" style="display: flex; flex-direction: row-reverse; align-items: center;">
                    <img 
                        src="<?php 
                            if (!empty($_SESSION['user_id'])) {
                                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $user = $result->fetch_assoc();
                                $stmt->close();
                                echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'assets/images/default-profile.png';
                            } else {
                                echo 'assets/images/default-profile.png';
                            }
                        ?>" 
                        alt="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" 
                        class="navbar-profile-pic"
                        onerror="this.src='assets/images/default-profile.png'"
                    >
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <div class="dropdown-menu">
                        <!-- updated profile link from .html to .php -->
                        <a href="profile.php">Profil</a>
                        <a href="dashboard.php">Dashboard</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="container">
            <div class="dashboard-header">
                <h1>My Project</h1>
                <p class="dashboard-subtitle">Kelola dan tampilkan portfolio proyek arsitektur Anda</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p>• <?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php foreach ($success as $msg): ?>
                        <p>✓ <?php echo htmlspecialchars($msg); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Add Project Form Section -->
            <div class="dashboard-card form-card">
                <div class="card-header">
                    <h2>Tambah Proyek Baru</h2>
                    <p class="card-description">Bagikan proyek arsitektur Anda ke platform</p>
                </div>
                <!-- Added enctype="multipart/form-data" for file uploads -->
                <form method="POST" class="dashboard-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="title">Judul Proyek</label>
                        <input type="text" id="title" name="title" placeholder="Contoh: Rumah Minimalis Modern di Jakarta" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" placeholder="Jelaskan detail proyek, konsep desain, material yang digunakan, dll" rows="4" required></textarea>
                    </div>

                    <!-- Removed price field, added location field in form-row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Lokasi</label>
                            <input type="text" id="location" name="location" placeholder="Jakarta, Indonesia" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Kategori</label>
                            <select id="category" name="category" required>
                                <option value="">Pilih kategori proyek</option>
                                <option value="modern">Modern</option>
                                <option value="minimalis">Minimalis</option>
                                <option value="tradisional">Tradisional</option>
                                <option value="contemporary">Contemporary</option>
                            </select>
                        </div>
                    </div>

                    <!-- Added image thumbnail upload -->
                    <div class="form-group">
                        <label for="image_thumbnail">Gambar Thumbnail</label>
                        <input type="file" id="image_thumbnail" name="image_thumbnail" accept="image/jpeg,image/png,image/gif" placeholder="Upload gambar thumbnail (JPG, PNG, GIF - Max 5MB)">
                        <small>Format: JPG, PNG, GIF | Ukuran maksimal: 5MB</small>
                    </div>

                    <!-- Added 3D model file upload -->
                    <div class="form-group">
                        <label for="model_3d_file">Model 3D (GLB/GLTF)</label>
                        <input type="file" id="model_3d_file" name="model_3d_file" accept=".glb,.gltf" placeholder="Upload file model 3D (GLB atau GLTF - Max 50MB)">
                        <small>Format: GLB atau GLTF | Ukuran maksimal: 50MB</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large">Tambah Proyek</button>
                </form>
            </div>

            <!-- Projects Display Section -->
            <div class="dashboard-card projects-card">
                <div class="card-header">
                    <h2>Daftar Proyek Anda</h2>
                    <p class="card-description"><?php echo count($projects); ?> proyek tersimpan</p>
                </div>
                
                <?php if (count($projects) > 0): ?>
                    <div class="projects-grid-dashboard">
                        <?php foreach ($projects as $project): ?>
                            <div class="project-item">
                                <div class="project-item-header">
                                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                                    <span class="project-badge-small"><?php echo htmlspecialchars($project['category']); ?></span>
                                </div>
                                <p class="project-item-desc"><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . '...'; ?></p>
                                <div class="project-item-meta">
                                    <span class="meta-item">📍 <?php echo htmlspecialchars($project['location']); ?></span>
                                </div>
                                <p class="project-item-date">Dibuat: <?php echo date('d M Y', strtotime($project['created_at'])); ?></p>
                                <div class="project-item-actions">
                                    <button class="btn-action btn-edit-small" onclick="editProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars(str_replace("'", "\\'", $project['title'])); ?>', '<?php echo htmlspecialchars(str_replace("'", "\\'", $project['description'])); ?>', '<?php echo htmlspecialchars(str_replace("'", "\\'", $project['location'])); ?>', '<?php echo $project['category']; ?>')">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus proyek ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <button type="submit" class="btn-action btn-delete-small">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <h3>Belum ada proyek</h3>
                        <p>Mulai dengan menambahkan proyek arsitektur pertama Anda di atas</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hidden Edit Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Proyek</h2>
            
            <form method="POST" class="dashboard-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="editProjectId" name="project_id">
                
                <div class="form-group">
                    <label for="editTitle">Judul Proyek</label>
                    <input type="text" id="editTitle" name="title" required>
                </div>

                <div class="form-group">
                    <label for="editDescription">Deskripsi</label>
                    <textarea id="editDescription" name="description" rows="4" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="editLocation">Lokasi</label>
                        <input type="text" id="editLocation" name="location" required>
                    </div>
                    <div class="form-group">
                        <label for="editCategory">Kategori</label>
                        <select id="editCategory" name="category" required>
                            <option value="modern">Modern</option>
                            <option value="minimalis">Minimalis</option>
                            <option value="tradisional">Tradisional</option>
                            <option value="contemporary">Contemporary</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="background: #f9f9f9; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    <label style="font-weight:bold;">Update File (Opsional)</label>
                    <p style="font-size: 0.8rem; color: #666; margin-bottom: 10px;">Biarkan kosong jika tidak ingin mengubah file saat ini.</p>
                    
                    <label for="editThumbnail" style="font-size: 0.9rem; margin-top: 5px;">Ganti Thumbnail (JPG/PNG)</label>
                    <input type="file" id="editThumbnail" name="image_thumbnail" accept="image/*">
                    
                    <label for="editModel" style="font-size: 0.9rem; margin-top: 10px;">Ganti Model 3D (GLB/GLTF)</label>
                    <input type="file" id="editModel" name="model_3d_file" accept=".glb,.gltf">
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Update Proyek</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Archi.ID</h4>
                    <p>Platform portfolio arsitektur terpercaya untuk inspirasi desain rumah impian Anda</p>
                </div>
                <div class="footer-section">
                    <h4>Navigasi</h4>
                    <ul>
                        <li><a href="marketplace.php">Projects</a></li>
                        <li><a href="index.php">Home</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Untuk Arsitek</h4>
                    <ul>
                        <li><a href="<?php echo isLoggedIn() ? 'dashboard.php' : 'register.php'; ?>">Bergabung Sekarang</a></li>
                        <li><a href="<?php echo isLoggedIn() ? 'dashboard.php' : '#'; ?>">Dashboard</a></li>
                        <li><a href="#">Panduan</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Kebijakan Privasi</a></li>
                        <li><a href="#">Syarat & Ketentuan</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Archi.ID. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <script>
        function editProject(id, title, description, location, category) {
            document.getElementById('editProjectId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = description;
            document.getElementById('editLocation').value = location;
            document.getElementById('editCategory').value = category;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    <!-- Add dropdown handler script for smooth dropdown behavior -->
    <script src="js/dropdown-handler.js"></script>
</body>
</html>
