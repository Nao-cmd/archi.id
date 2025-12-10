<?php
session_start();
require 'config/database.php';
require 'config/session.php';

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Fetch detailed user profile from database
$stmt = $conn->prepare("SELECT id, name, email, profile_picture, phone, address, bio, role, is_verified FROM users WHERE id = ?");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Fetch user's projects count and stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_projects FROM projects WHERE user_id = ?");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();

// Fetch user's featured projects (limit 4)
$stmt = $conn->prepare("SELECT id, title, description, image_thumbnail FROM projects WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects_result = $stmt->get_result();
$featured_projects = $projects_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate average rating from reviews
$stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE project_id IN (SELECT id FROM projects WHERE user_id = ?)");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rating_result = $stmt->get_result();
$rating_stats = $rating_result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo htmlspecialchars($user['name']); ?> - Archi.ID</title>
    <link rel="icon" href="assets/images/favicon.png?v=2" type="image/png">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <h1><a href="index.php" style="text-decoration: none; color: inherit;">Archi.ID</a></h1>
            </div>
            <div class="navbar-menu">
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="marketplace.php">Projects</a></li>
                    <li><a href="dashboard.php">My Project</a></li>
                </ul>
            </div>
            <div class="navbar-auth">
                <div class="user-menu" style="display: flex; flex-direction: row-reverse; align-items: center;">
                    <img 
                        src="<?php 
                            if (!empty($_SESSION['user_id'])) {
                                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $user_nav = $result->fetch_assoc();
                                $stmt->close();
                                echo !empty($user_nav['profile_picture']) ? htmlspecialchars($user_nav['profile_picture']) : 'assets/images/default-profile.png';
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
                        <a href="profile.php">Profil</a>
                        <a href="dashboard.php">Dashboard</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Container -->
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-header-content">
                <div class="profile-picture-container">
                    <!-- Use default profile image if not uploaded -->
                    <img 
                        src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'assets/images/default-profile.png'; ?>" 
                        alt="<?php echo htmlspecialchars($user['name']); ?>" 
                        class="profile-picture"
                        onerror="this.src='assets/images/default-profile.png'"
                    >
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                    <strong><?php echo htmlspecialchars(ucfirst($user['role'])); ?></strong>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <!-- Fixed undefined array key warning by using isset check -->
                    <?php if (isset($user['is_verified']) && $user['is_verified']): ?>
                        <p class="verification-badge">✓ Terverifikasi</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_projects'] ?? 0; ?></div>
                <div class="stat-label">Total Proyek</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo round($rating_stats['avg_rating'] ?? 0, 1); ?></div>
                <div class="stat-label">Rating Rata-rata</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $rating_stats['total_reviews'] ?? 0; ?></div>
                <div class="stat-label">Total Review</div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
            <!-- Personal Information -->
            <div class="profile-section">
                <h2 class="section-title">Informasi Pribadi</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Nomor Telepon</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Alamat</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['address'] ?? '-'); ?></div>
                    </div>
                </div>
                <button class="edit-profile-btn" onclick="window.location.href='edit-profile.php'">Edit Profil</button>
            </div>

            <!-- Bio & About -->
            <?php if ($user['bio']): ?>
                <div class="profile-section">
                    <h2 class="section-title">Tentang Saya</h2>
                    <div style="color: var(--text-primary); line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Projects Showcase -->
            <div class="profile-section projects-section">
                <h2 class="section-title">Proyek Saya</h2>
                <?php if (!empty($featured_projects)): ?>
                    <div class="projects-grid">
                        <?php foreach ($featured_projects as $project): ?>
                            <div class="project-mini-card" style="cursor: default;">
                                <img src="<?php echo htmlspecialchars($project['image_thumbnail'] ?? 'assets/images/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="project-mini-image" onerror="this.src='assets/images/placeholder.png'">
                                <div class="project-mini-info">
                                    <div class="project-mini-title"><?php echo htmlspecialchars($project['title']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.8rem;"><?php echo htmlspecialchars(substr($project['description'], 0, 60) . '...'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-message">
                        <p>Belum ada proyek.</p>
                        <a href="create-project.php" class="create-project-link">Buat Proyek Pertama</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
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
</body>
</html>
