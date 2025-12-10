<?php
session_start();
require 'config/database.php';
require 'config/session.php';

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'latest';
$page = intval($_GET['page'] ?? 1);
$perPage = 12;

// Build query
$query = "SELECT p.id, p.title, p.description, p.location, p.category, p.image_thumbnail, p.created_at, u.name as architect_name FROM projects p JOIN users u ON p.user_id = u.id WHERE p.status = 'active'";
$countQuery = "SELECT COUNT(*) as total FROM projects WHERE status = 'active'";
$params = [];
$types = "";

// Apply filters
if (!empty($search)) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ? OR u.name LIKE ?)";
    $countQuery .= " AND (title LIKE ? OR description LIKE ? OR (SELECT name FROM users WHERE id = user_id) LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = "sss";
}

if (!empty($category)) {
    $query .= " AND p.category = ?";
    $countQuery .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

// Apply sorting
switch ($sort) {
    case 'popular':
        $query .= " ORDER BY p.view_count DESC";
        break;
    default: // latest
        $query .= " ORDER BY p.created_at DESC";
}

// Pagination
$offset = ($page - 1) * $perPage;
$query .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

// Count total results
$countStmt = $conn->prepare($countQuery);
if (!empty($params) && strlen($types) > 2) {
    // Remove limit params from count query params
    $countParams = array_slice($params, 0, -2);
    $countTypes = substr($types, 0, -2);
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
}
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalResults = $countResult['total'];
$totalPages = ceil($totalResults / $perPage);
$countStmt->close();

// Fetch projects
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$projectsResult = $stmt->get_result();
$projects = $projectsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Updated page title -->
    <title>Projects - Archi.ID</title>
    <link rel="icon" href="assets/images/favicon.png?v=2" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .marketplace-hero {
            background: linear-gradient(135deg, #f5ede4 0%, #ffffff 100%);
            padding: 3rem 0;
            text-align: center;
        }

        .marketplace-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .marketplace-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            margin: 3rem 0;
        }

        .marketplace-sidebar {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            height: fit-content;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 100px;
        }

        .filter-group {
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1.5rem;
        }

        .filter-group:last-child {
            border-bottom: none;
        }

        .filter-group h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .filter-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.8rem;
            cursor: pointer;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .filter-group input[type="checkbox"],
        .filter-group input[type="radio"] {
            accent-color: var(--accent-warm);
            cursor: pointer;
        }

        .filter-group input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.95rem;
        }

        .marketplace-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .marketplace-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .marketplace-header .results-info {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .sort-dropdown {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: white;
            cursor: pointer;
            color: var(--text-primary);
        }

        .marketplace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .marketplace-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .marketplace-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-8px);
        }

        .marketplace-card-image {
            width: 100%;
            height: 200px;
            background-color: #f0f0f0;
            position: relative;
            overflow: hidden;
        }

        .marketplace-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .marketplace-card-image img[src] {
            display: block;
        }

        .marketplace-card-image::after {
            content: attr(data-placeholder);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ccc;
            font-size: 3rem;
            display: none;
        }

        .marketplace-card-image.image-failed::after {
            display: block;
        }

        .marketplace-card-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent-warm);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .marketplace-card-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .marketplace-card-content h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: var(--primary-dark);
        }

        .marketplace-card-architect {
            color: var(--accent-warm);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .marketplace-card-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            flex-grow: 1;
            line-height: 1.5;
        }

        .marketplace-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .marketplace-card-price {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1.1rem;
        }

        .marketplace-card-location {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .marketplace-card-footer {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-view-3d {
            flex: 1;
            background: var(--primary-dark);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
        }

        .btn-view-3d:hover {
            background: var(--accent-warm);
        }

        .btn-view-review {
            flex: 1;
            background: var(--accent-light);
            color: var(--primary-dark);
            border: 1px solid var(--accent-warm);
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            display: block;
        }

        .btn-view-review:hover {
            background: var(--accent-warm);
            color: white;
        }

        .btn-add-cart {
            flex: 1;
            background: var(--accent-light);
            color: var(--primary-dark);
            border: 1px solid var(--accent-warm);
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-add-cart:hover {
            background: var(--accent-warm);
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-decoration: none;
            color: var(--primary-dark);
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: var(--accent-warm);
            color: white;
            border-color: var(--accent-warm);
        }

        .pagination .active {
            background: var(--primary-dark);
            color: white;
            border-color: var(--primary-dark);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            color: var(--text-secondary);
        }

        .empty-state h2 {
            color: var(--primary-dark);
        }

        .navbar-profile-pic {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-warm);
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .marketplace-container {
                grid-template-columns: 1fr;
            }

            .marketplace-sidebar {
                position: static;
            }

            .marketplace-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .marketplace-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                    <!-- Changed Marketplace to Projects -->
                    <li><a href="marketplace.php">Projects</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="dashboard.php">My Project</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="navbar-auth">
                <?php if (isLoggedIn()): ?>
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
                            <a href="dashboard.php">Dashboard</a>
                            <a href="profile.php">Profil</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="marketplace-hero">
        <div class="container">
            <!-- Updated hero heading from "Marketplace" to "Projects" -->
            <h1>Jelajahi Projects Arsitektur</h1>
            <p>Temukan desain rumah impian dari ribuan arsitek profesional</p>
        </div>
    </section>

    <!-- Main Marketplace Section -->
    <div class="container">
        <div class="marketplace-container">
            <!-- Sidebar Filters -->
            <aside class="marketplace-sidebar">
                <form method="GET" action="marketplace.php" id="filterForm">
                    <!-- Search -->
                    <div class="filter-group">
                        <h3>Cari Proyek</h3>
                        <input type="text" name="search" placeholder="Nama proyek atau arsitek..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-group">
                        <h3>Kategori</h3>
                        <label><input type="radio" name="category" value="" <?php echo empty($category) ? 'checked' : ''; ?>> Semua Kategori</label>
                        <label><input type="radio" name="category" value="modern" <?php echo $category === 'modern' ? 'checked' : ''; ?>> Modern</label>
                        <label><input type="radio" name="category" value="minimalis" <?php echo $category === 'minimalis' ? 'checked' : ''; ?>> Minimalis</label>
                        <label><input type="radio" name="category" value="tradisional" <?php echo $category === 'tradisional' ? 'checked' : ''; ?>> Tradisional</label>
                        <label><input type="radio" name="category" value="contemporary" <?php echo $category === 'contemporary' ? 'checked' : ''; ?>> Contemporary</label>
                    </div>

                    <!-- Sort Filter -->
                    <div class="filter-group">
                        <h3>Urutkan Berdasarkan</h3>
                        <label><input type="radio" name="sort" value="latest" <?php echo $sort === 'latest' ? 'checked' : ''; ?>> Terbaru</label>
                        <label><input type="radio" name="sort" value="popular" <?php echo $sort === 'popular' ? 'checked' : ''; ?>> Populer</label>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Terapkan Filter</button>
                </form>
            </aside>

            <!-- Main Content -->
            <div class="marketplace-main">
                <!-- Results Header -->
                <div class="marketplace-header">
                    <div class="results-info">
                        Menampilkan <?php echo count($projects); ?> dari <?php echo $totalResults; ?> proyek
                    </div>
                </div>

                <!-- Projects Grid -->
                <?php if (count($projects) > 0): ?>
                    <div class="marketplace-grid">
                        <?php foreach ($projects as $project): ?>
                            <div class="marketplace-card">
                                <div class="marketplace-card-image" data-placeholder="🏠">
                                    <?php 
                                        $thumbnail_src = htmlspecialchars($project['image_thumbnail']);
                                        if ($project['image_thumbnail'] && !filter_var($project['image_thumbnail'], FILTER_VALIDATE_URL)) {
                                            if (strpos($project['image_thumbnail'], '/') !== 0) {
                                                $thumbnail_src = htmlspecialchars($project['image_thumbnail']);
                                            }
                                        }
                                    ?>
                                    <img src="<?php echo $thumbnail_src; ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" onerror="this.parentElement.classList.add('image-failed')">
                                    <span class="marketplace-card-badge"><?php echo htmlspecialchars(ucfirst($project['category'])); ?></span>
                                </div>
                                <div class="marketplace-card-content">
                                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                                    <p class="marketplace-card-architect">oleh <?php echo htmlspecialchars($project['architect_name']); ?></p>
                                    <p class="marketplace-card-desc"><?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>...</p>
                                    <div class="marketplace-card-meta">
                                        <span class="marketplace-card-location"><?php echo htmlspecialchars($project['location']); ?></span>
                                    </div>
                                    <!-- Fixed button layout with proper flexbox alignment -->
                                    <div class="marketplace-card-footer">
                                        <a href="pages/project-3d-viewer.php?id=<?php echo $project['id']; ?>" class="btn-view-3d">Lihat 3D</a>
                                        <a href="pages/project-reviews.php?id=<?php echo $project['id']; ?>" class="btn-view-review">Review</a>
                                        <a href="download-model.php?id=<?php echo $project['id']; ?>" class="btn-add-cart" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Download</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="marketplace.php?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h2>Tidak ada proyek ditemukan</h2>
                        <p>Coba ubah filter pencarian atau kategori Anda</p>
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
                    <!-- Updated footer description -->
                    <p>Platform portfolio arsitektur terpercaya untuk inspirasi desain rumah impian Anda</p>
                </div>
                <div class="footer-section">
                    <h4>Navigasi</h4>
                    <ul>
                        <!-- Changed Marketplace to Projects -->
                        <li><a href="marketplace.php">Projects</a></li>
                        <li><a href="#services">Layanan</a></li>
                        <li><a href="#about">Tentang</a></li>
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
    <!-- Add dropdown handler script for smooth dropdown behavior -->
    <script src="js/dropdown-handler.js"></script>
</body>
</html>
