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
        $price = trim($_POST['price'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if (empty($title) || empty($description) || empty($price) || empty($location)) {
            $errors[] = "Semua field harus diisi";
        } else {
            $stmt = $conn->prepare("INSERT INTO projects (user_id, title, description, price, location, category, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isssss", $user_id, $title, $description, $price, $location, $category);
            
            if ($stmt->execute()) {
                $success[] = "Proyek berhasil ditambahkan";
            } else {
                $errors[] = "Gagal menambahkan proyek";
            }
            $stmt->close();
        }
    } elseif ($action === 'update') {
        $project_id = intval($_POST['project_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if (empty($title) || empty($description) || empty($price) || empty($location)) {
            $errors[] = "Semua field harus diisi";
        } else {
            $stmt = $conn->prepare("UPDATE projects SET title = ?, description = ?, price = ?, location = ?, category = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssssii", $title, $description, $price, $location, $category, $project_id, $user_id);
            
            if ($stmt->execute()) {
                $success[] = "Proyek berhasil diperbarui";
            } else {
                $errors[] = "Gagal memperbarui proyek";
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $project_id = intval($_POST['project_id'] ?? 0);
        
        $stmt = $conn->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $project_id, $user_id);
        
        if ($stmt->execute()) {
            $success[] = "Proyek berhasil dihapus";
        } else {
            $errors[] = "Gagal menghapus proyek";
        }
        $stmt->close();
    }
}

// Fetch user's projects
$stmt = $conn->prepare("SELECT id, title, description, price, location, category, created_at FROM projects WHERE user_id = ? ORDER BY created_at DESC");
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
                    <li><a href="dashboard.php">My Project</a></li>
                </ul>
            </nav>
            <div class="navbar-auth">
                <!-- improved user menu styling with dropdown support -->
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <div class="dropdown-menu">
                        <a href="profile.php">Profil</a>
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
                    <p class="card-description">Siarkan proyek arsitektur Anda ke marketplace</p>
                </div>
                <form method="POST" class="dashboard-form">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="title">Judul Proyek</label>
                        <input type="text" id="title" name="title" placeholder="Contoh: Rumah Minimalis Modern di Jakarta" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" placeholder="Jelaskan detail proyek, konsep desain, material yang digunakan, dll" rows="4" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Harga (Rp)</label>
                            <input type="number" id="price" name="price" placeholder="500000000" required>
                        </div>
                        <div class="form-group">
                            <label for="location">Lokasi</label>
                            <input type="text" id="location" name="location" placeholder="Jakarta, Indonesia" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <select id="category" name="category" required>
                            <option value="">Pilih kategori proyek</option>
                            <option value="modern">Modern</option>
                            <option value="minimalis">Minimalis</option>
                            <option value="tradisional">Tradisional</option>
                        </select>
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
                                    <span class="meta-item">Rp <?php echo number_format($project['price'], 0, ',', '.'); ?></span>
                                </div>
                                <p class="project-item-date">Dibuat: <?php echo date('d M Y', strtotime($project['created_at'])); ?></p>
                                <div class="project-item-actions">
                                    <button class="btn-action btn-edit-small" onclick="editProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars(str_replace("'", "\\'", $project['title'])); ?>', '<?php echo htmlspecialchars(str_replace("'", "\\'", $project['description'])); ?>', '<?php echo $project['price']; ?>', '<?php echo htmlspecialchars(str_replace("'", "\\'", $project['location'])); ?>', '<?php echo $project['category']; ?>')">Edit</button>
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
            <form method="POST" class="dashboard-form">
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
                        <label for="editPrice">Harga (Rp)</label>
                        <input type="number" id="editPrice" name="price" required>
                    </div>
                    <div class="form-group">
                        <label for="editLocation">Lokasi</label>
                        <input type="text" id="editLocation" name="location" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="editCategory">Kategori</label>
                    <select id="editCategory" name="category" required>
                        <option value="modern">Modern</option>
                        <option value="minimalis">Minimalis</option>
                        <option value="tradisional">Tradisional</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Update Proyek</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editProject(id, title, description, price, location, category) {
            document.getElementById('editProjectId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = description;
            document.getElementById('editPrice').value = price;
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
</body>
</html>
