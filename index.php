<?php
session_start();
require 'config/database.php';
require 'config/session.php';

// Auto-login from cookie if available
autoLoginFromCookie($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archi.ID - Marketplace Arsitektur Premium</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth-style.css">
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
                    <li><a href="#hero">Home</a></li>
                    <li><a href="#projects">Projects</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <!-- Add My Project menu for logged-in users -->
                    <?php if (isLoggedIn()): ?>
                        <li><a href="dashboard.php">My Project</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="navbar-auth">
                <!-- Dynamic navbar based on login status -->
                <?php if (isLoggedIn()): ?>
                    <div class="user-menu">
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
    <section id="hero" class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Desain Rumah<br>Impian Anda</h1>
                    <p>Temukan arsitektur terbaik dan lihat 3D model rumah impian Anda dengan teknologi viewer 360°</p>
                    <div class="hero-cta">
                        <a href="#projects" class="btn btn-primary">Jelajahi Proyek</a>
                        <a href="<?php echo isLoggedIn() ? 'dashboard.php' : 'register.php'; ?>" class="btn btn-secondary">Jual Proyek</a>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-preview">
                        <!-- Added canvas element for 3D viewer placeholder -->
                        <canvas id="previewCanvas"></canvas>
                        <div class="preview-controls">
                            <p>Model 3D Preview</p>
                            <p class="small-text">(Drag untuk rotasi 360°)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <h2>Layanan Kami</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">🏗️</div>
                    <h3>Desain Custom</h3>
                    <p>Dapatkan desain rumah yang disesuaikan dengan kebutuhan dan gaya hidup Anda</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">🎨</div>
                    <h3>Visualisasi 3D</h3>
                    <p>Lihat desain rumah Anda dalam bentuk 3D interaktif 360 derajat sebelum dibangun</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">👨‍💼</div>
                    <h3>Konsultasi Arsitek</h3>
                    <p>Konsultasi langsung dengan arsitek profesional untuk mewujudkan visi Anda</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">📋</div>
                    <h3>Portfolio Lengkap</h3>
                    <p>Jelajahi portfolio lengkap dari berbagai arsitek terkemuka di Indonesia</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Projects Section -->
    <section id="projects" class="projects">
        <div class="container">
            <h2>Proyek Unggulan</h2>
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all">Semua</button>
                <button class="filter-btn" data-filter="modern">Modern</button>
                <button class="filter-btn" data-filter="minimalis">Minimalis</button>
                <button class="filter-btn" data-filter="tradisional">Tradisional</button>
            </div>
            <div class="projects-grid">
                <div class="project-card" data-category="modern">
                    <div class="project-image">
                        <!-- Replace placeholder with Unsplash image -->
                        <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=500&h=300&fit=crop" alt="Modern House Design">
                        <span class="project-badge">Modern</span>
                    </div>
                    <div class="project-info">
                        <h3>Rumah Minimalis Modern</h3>
                        <p class="project-architect">by Arsitek Profesional</p>
                        <p class="project-desc">Desain rumah minimalis dengan konsep modern yang elegan dan fungsional</p>
                        <div class="project-footer">
                            <span class="project-price">Rp 450 Juta</span>
                            <a href="#" class="btn-detail">Lihat 3D</a>
                        </div>
                    </div>
                </div>

                <div class="project-card" data-category="minimalis">
                    <div class="project-image">
                        <!-- Replace placeholder with Unsplash image -->
                        <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=500&h=300&fit=crop" alt="Minimalist House">
                        <span class="project-badge">Minimalis</span>
                    </div>
                    <div class="project-info">
                        <h3>Rumah Minimalis Skandinavia</h3>
                        <p class="project-architect">by Arsitek Studio</p>
                        <p class="project-desc">Konsep Skandinavia dengan sentuhan minimalis yang nyaman untuk keluarga</p>
                        <div class="project-footer">
                            <span class="project-price">Rp 380 Juta</span>
                            <a href="#" class="btn-detail">Lihat 3D</a>
                        </div>
                    </div>
                </div>

                <div class="project-card" data-category="tradisional">
                    <div class="project-image">
                        <!-- Replace placeholder with Unsplash image -->
                        <img src="https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=500&h=300&fit=crop" alt="Traditional House">
                        <span class="project-badge">Tradisional</span>
                    </div>
                    <div class="project-info">
                        <h3>Rumah Tradisional Jawa</h3>
                        <p class="project-architect">by Studio Heritage</p>
                        <p class="project-desc">Preservasi budaya tradisional Jawa dengan sentuhan modern yang kontemporer</p>
                        <div class="project-footer">
                            <span class="project-price">Rp 520 Juta</span>
                            <a href="#" class="btn-detail">Lihat 3D</a>
                        </div>
                    </div>
                </div>

                <div class="project-card" data-category="modern">
                    <div class="project-image">
                        <!-- Updated Villa Mewah image with different Unsplash URL (modern luxury villa) -->
                        <img src="https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=500&h=300&fit=crop" alt="Villa Mewah Kontemporer">
                        <span class="project-badge">Modern</span>
                    </div>
                    <div class="project-info">
                        <h3>Villa Mewah Kontemporer</h3>
                        <p class="project-architect">by Premium Architects</p>
                        <p class="project-desc">Desain villa mewah dengan fasad modern dan interior yang sophisticated</p>
                        <div class="project-footer">
                            <span class="project-price">Rp 800 Juta</span>
                            <a href="#" class="btn-detail">Lihat 3D</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Tentang Archi.ID</h2>
                    <p>Kami adalah platform marketplace terdepan yang menghubungkan arsitek profesional dengan klien yang mencari desain rumah impian mereka.</p>
                    <ul class="about-list">
                        <li>✓ Ribuan desain rumah dari arsitek terbaik</li>
                        <li>✓ Teknologi viewer 3D interaktif 360°</li>
                        <li>✓ Sistem pembayaran yang aman dan terpercaya</li>
                        <li>✓ Dukungan konsultasi langsung dengan arsitek</li>
                        <li>✓ Portfolio yang terverifikasi dan berkualitas</li>
                    </ul>
                </div>
                <div class="about-stats">
                    <div class="stat-item">
                        <h3>5000+</h3>
                        <p>Desain Tersedia</p>
                    </div>
                    <div class="stat-item">
                        <h3>800+</h3>
                        <p>Arsitek Profesional</p>
                    </div>
                    <div class="stat-item">
                        <h3>3000+</h3>
                        <p>Klien Puas</p>
                    </div>
                    <div class="stat-item">
                        <h3>98%</h3>
                        <p>Rating Kepuasan</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <h2>Hubungi Kami</h2>
            <div class="contact-wrapper">
                <form class="contact-form" id="contactForm">
                    <div class="form-group">
                        <input type="text" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="form-group">
                        <input type="email" placeholder="Email Anda" required>
                    </div>
                    <div class="form-group">
                        <textarea placeholder="Pesan Anda" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim Pesan</button>
                </form>
                <div class="contact-info">
                    <div class="info-item">
                        <h4>📍 Lokasi</h4>
                        <p>Jakarta, Indonesia</p>
                    </div>
                    <div class="info-item">
                        <h4>📞 Telepon</h4>
                        <p>+62 812 3456 7890</p>
                    </div>
                    <div class="info-item">
                        <h4>✉️ Email</h4>
                        <p>info@archi.id</p>
                    </div>
                    <div class="info-item">
                        <h4>⏰ Jam Operasional</h4>
                        <p>Senin - Jumat: 09:00 - 18:00</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Archi.ID</h4>
                    <p>Marketplace arsitektur terpercaya untuk desain rumah impian Anda</p>
                </div>
                <div class="footer-section">
                    <h4>Navigasi</h4>
                    <ul>
                        <li><a href="#projects">Proyek</a></li>
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

    <script src="js/script.js"></script>
</body>
</html>
