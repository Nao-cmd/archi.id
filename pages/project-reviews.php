<?php
session_start();
require '../config/database.php';
require '../config/session.php';

// Get project ID
$project_id = intval($_GET['id'] ?? 0);

if ($project_id === 0) {
    die("Project ID tidak valid");
}

// Fetch project data
$stmt = $conn->prepare("SELECT p.*, u.name as architect_name, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count FROM projects p LEFT JOIN users u ON p.user_id = u.id LEFT JOIN reviews r ON p.id = r.project_id WHERE p.id = ? AND p.status = 'active' GROUP BY p.id");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Proyek tidak ditemukan");
}

$project = $result->fetch_assoc();
$project['avg_rating'] = $project['avg_rating'] ? round($project['avg_rating'], 1) : 0;
$stmt->close();

// Handle new review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_review') {
        $rating = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $user_id = $_SESSION['user_id'];
        $project_id = intval($_GET['id'] ?? 0);
        
        // Validate rating range
        if ($rating < 1 || $rating > 5) {
            $_SESSION['error_message'] = "Rating tidak valid";
            header("Location: project-reviews.php?id=$project_id");
            exit;
        }
        
        $checkStmt = $conn->prepare("SELECT id FROM reviews WHERE project_id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $project_id, $user_id);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            $_SESSION['error_message'] = "Anda sudah memberikan review untuk proyek ini";
            header("Location: project-reviews.php?id=$project_id");
            exit;
        }
        $checkStmt->close();
        
        $insertStmt = $conn->prepare("INSERT INTO reviews (project_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("iiss", $project_id, $user_id, $rating, $comment);
        
        if ($insertStmt->execute()) {
            $_SESSION['success_message'] = "Ulasan Anda telah dipublikasikan!";
            header("Location: project-reviews.php?id=$project_id");
            exit;
        }
        $insertStmt->close();
    }
}

// Fetch reviews with pagination
$page = intval($_GET['page'] ?? 1);
$perPage = 5;
$offset = ($page - 1) * $perPage;

$reviewsStmt = $conn->prepare("SELECT r.*, u.name, u.profile_picture FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.project_id = ? ORDER BY r.created_at DESC LIMIT ? OFFSET ?");
$reviewsStmt->bind_param("iii", $project_id, $perPage, $offset);
$reviewsStmt->execute();
$reviewsResult = $reviewsStmt->get_result();
$reviews = $reviewsResult->fetch_all(MYSQLI_ASSOC);
$reviewsStmt->close();

// Get total review count
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE project_id = ?");
$countStmt->bind_param("i", $project_id);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalReviews = $countResult['total'];
$totalPages = ceil($totalReviews / $perPage);
$countStmt->close();

// Get rating distribution
$ratingStmt = $conn->prepare("SELECT rating, COUNT(*) as count FROM reviews WHERE project_id = ? GROUP BY rating ORDER BY rating DESC");
$ratingStmt->bind_param("i", $project_id);
$ratingStmt->execute();
$ratingResult = $ratingStmt->get_result();
$ratingDistribution = [];
while ($row = $ratingResult->fetch_assoc()) {
    $ratingDistribution[$row['rating']] = $row['count'];
}
$ratingStmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - Reviews - Archi.ID</title>
	<link rel="icon" href="../assets/images/favicon.png?v=2" type="image/png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/ui-enhancements.css">
    <style>
        .reviews-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .reviews-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        .reviews-summary {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: center;
        }

        .rating-overview {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .rating-score {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .rating-stars {
            display: flex;
            gap: 0.25rem;
            font-size: 1.5rem;
        }

        .rating-stars .star {
            color: #ddd;
        }

        .rating-stars .star.filled {
            color: #ffc107;
        }

        .rating-count {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .rating-distribution {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .distribution-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .distribution-label {
            width: 60px;
            text-align: right;
            font-weight: 500;
        }

        .distribution-bar {
            flex: 1;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }

        .distribution-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffc107, #ffb300);
        }

        .distribution-count {
            width: 40px;
            text-align: right;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .review-form-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        .review-form-title {
            margin: 0 0 1.5rem 0;
            color: var(--primary-dark);
        }

        .form-group-rating {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group-rating label {
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Ganti style .rating-input label yang lama dengan ini */
        .rating-input {
            display: flex;
            gap: 0.5rem;
            flex-direction: row-reverse; /* Trik CSS: Membalik urutan agar selektor '~' bekerja */
            justify-content: flex-end; /* Ratakan kiri kembali karena dibalik */
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .rating-input label {
            cursor: pointer;
            font-size: 2rem;
            color: #ddd; /* Warna default abu-abu */
            transition: all 0.2s ease;
            margin: 0;
        }

        /* Warnai bintang saat di-hover, dan semua bintang SETELAHnya (karena row-reverse, ini visualnya jadi SEBELUMnya) */
        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input[type="radio"]:checked ~ label {
            color: #ffc107; /* Warna Kuning */
        }

        /* Efek membesar sedikit saat hover */
        .rating-input label:hover {
            transform: scale(1.2);
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .review-item {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--accent-warm);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .reviewer-info {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-warm);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .reviewer-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .reviewer-avatar-initial {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-warm);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .reviewer-details h4 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 0.95rem;
        }

        .reviewer-details p {
            margin: 2px 0 0 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .review-rating {
            display: flex;
            gap: 0.25rem;
        }

        .review-rating .star {
            color: #ffc107;
            font-size: 0.9rem;
        }

        .review-date {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .review-comment {
            color: var(--text-primary);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
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

        .empty-reviews {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-group textarea {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: inherit;
            resize: vertical;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary-dark);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-warm);
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .alert-info a {
            color: var(--accent-warm);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .reviews-summary {
                grid-template-columns: 1fr;
            }

            .review-header {
                flex-direction: column;
            }

            .reviews-container {
                margin: 1rem auto;
            }
        }
    </style>
</head>
<body>
    <!-- Fixed navbar with consistent styling like other pages -->
    <header class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <h1><a href="../index.php" style="text-decoration: none; color: inherit;">Archi.ID</a></h1>
            </div>
            <nav class="navbar-menu">
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../marketplace.php">Projects</a></li>
                </ul>
            </nav>
            <div class="navbar-auth">
                <?php if (isLoggedIn()): ?>
                    <!-- Fixed profile picture positioning and styling to match other pages -->
                    <div class="user-menu" style="display: flex; flex-direction: row-reverse; align-items: center; gap: 0.75rem;">
                        <img 
                            src="<?php 
                                // 1. Ambil data user terbaru
                                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $user_nav = $result->fetch_assoc();
                                $stmt->close();

                                // 2. Tentukan path gambar
                                $nav_avatar = '../assets/images/default-profile.png'; // Default path (mundur satu folder)
                                
                                if (!empty($user_nav['profile_picture'])) {
                                    // Jika ada foto di database, tambahkan '../' di depannya
                                    $nav_avatar = '../' . htmlspecialchars($user_nav['profile_picture']);
                                }
                                
                                echo $nav_avatar;
                            ?>" 
                            alt="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" 
                            class="navbar-profile-pic"
                            onerror="this.onerror=null; this.src='../assets/images/default-profile.png'"
                        >
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <div class="dropdown-menu">
                            <a href="../dashboard.php">Dashboard</a>
                            <a href="../profile.php">Profil</a>
                            <a href="../logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container reviews-container">
        <h1><?php echo htmlspecialchars($project['title']); ?></h1>
        <p style="color: var(--text-secondary);">oleh <?php echo htmlspecialchars($project['architect_name']); ?></p>

        <!-- Reviews Header -->
        <div class="reviews-header">
            <div class="reviews-summary">
                <div class="rating-overview">
                    <div class="rating-score"><?php echo $project['avg_rating']; ?></div>
                    <div class="rating-stars">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <span class="star <?php echo $i < round($project['avg_rating']) ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-count"><?php echo $totalReviews; ?> ulasan</div>
                </div>

                <?php if (!empty($ratingDistribution)): ?>
                    <div class="rating-distribution">
                        <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                            <?php 
                            $count = $ratingDistribution[$rating] ?? 0;
                            $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                            ?>
                            <div class="distribution-row">
                                <div class="distribution-label"><?php echo $rating; ?>★</div>
                                <div class="distribution-bar">
                                    <div class="distribution-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="distribution-count"><?php echo $count; ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Review Form -->
        <?php if (isLoggedIn()): ?>
            <div class="review-form-container">
                <h3 class="review-form-title">Berikan Ulasan Anda</h3>
                <form method="POST" id="reviewForm">
                    <input type="hidden" name="action" value="add_review">
                    <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project_id); ?>">
                    
                    <div class="form-group-rating">
                        <label>Rating</label>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="rating<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                <label for="rating<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comment">Komentar</label>
                        <textarea id="comment" name="comment" placeholder="Bagikan pengalaman Anda dengan desain ini..." rows="4" maxlength="500" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info" style="margin-bottom: 2rem;">
                <p><a href="../login.php">Login</a> untuk memberikan ulasan</p>
            </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <p><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem; padding: 12px; background-color: #fee2e2; color: #991b1b; border-radius: 4px;">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="reviews-list">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item fade-in">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <?php
                                $reviewer_avatar = '../assets/images/default-profile.png'; // Default path (pakai ../)
                                
                                if (!empty($review['profile_picture'])) {
                                    // Tambahkan ../ di depan path dari database
                                    $reviewer_avatar = '../' . htmlspecialchars($review['profile_picture']);
                                }
                                ?>
                                <img src="<?php echo $reviewer_avatar; ?>" 
                                    alt="<?php echo htmlspecialchars($review['name']); ?>" 
                                    class="reviewer-avatar" 
                                    onerror="this.onerror=null; this.src='../assets/images/default-profile.png'">
                                    
                                <div class="reviewer-details">
                                    <h4><?php echo htmlspecialchars($review['name']); ?></h4>
                                    <p><?php echo date('d M Y', strtotime($review['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                    <span class="star">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($review['comment'])): ?>
                            <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-reviews">
                    <p>Belum ada ulasan untuk proyek ini</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="project-reviews.php?id=<?php echo $project_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Added footer using same template from other pages -->
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
                        <li><a href="../marketplace.php">Projects</a></li>
                        <li><a href="../index.php">Home</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Untuk Arsitek</h4>
                    <ul>
                        <li><a href="<?php echo isLoggedIn() ? '../dashboard.php' : '../register.php'; ?>">Bergabung Sekarang</a></li>
                        <li><a href="<?php echo isLoggedIn() ? '../dashboard.php' : '#'; ?>">Dashboard</a></li>
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
        // Star rating interactivity
        const ratingInputs = document.querySelectorAll('.rating-input input[type="radio"]');
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                const labels = document.querySelectorAll('.rating-input label');
                labels.forEach(label => label.style.color = '');
                
                const checkedValue = parseInt(this.value);
                for (let i = 1; i <= checkedValue; i++) {
                    document.querySelector(`label[for="rating${i}"]`).style.color = '#ffc107';
                }
            });
        });
    </script>
    <!-- Add dropdown handler script -->
    <script src="../js/dropdown-handler.js"></script>
</body>
</html>
