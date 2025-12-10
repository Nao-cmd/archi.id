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
    <!-- Updated page title and meta tags to reflect Projects platform instead of Marketplace -->
    <title>Archi.ID - Platform Portfolio Arsitektur</title>
    <meta name="description" content="Temukan desain rumah impian dengan teknologi 3D viewer 360 derajat">
    <meta name="keywords" content="arsitektur, desain rumah, portfolio">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth-style.css">
    <link rel="stylesheet" href="css/ui-enhancements.css">
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
                    <!-- Changed Marketplace to Projects -->
                    <li><a href="marketplace.php">Projects</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
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
    <section id="hero" class="hero fade-in">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Desain Rumah<br>Impian Anda</h1>
                    <p>Temukan arsitektur terbaik dan lihat 3D model rumah impian Anda dengan teknologi viewer 360°</p>
                    <div class="hero-cta">
                        <a href="marketplace.php" class="btn btn-primary">Jelajahi Proyek</a>
                        <!-- Changed "Jual Proyek" to "Buat Proyek" -->
                        <a href="<?php echo isLoggedIn() ? 'dashboard.php' : 'register.php'; ?>" class="btn btn-secondary">Buat Proyek</a>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-preview">
                        <canvas id="previewCanvas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services slide-in">
        <div class="container">
            <h2>Layanan Kami</h2>
            <div class="services-grid">
                <div class="service-card" onclick="openServiceModal('Upload & Showcase', 'Fitur ini memungkinkan arsitek profesional untuk mengunggah portofolio desain lengkap. Anda dapat menyertakan gambar resolusi tinggi, deskripsi detail, spesifikasi material, dan data teknis lainnya untuk menarik calon klien potensial.', 'upload')">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
                            <path d="M12 2v12m0 0l-4-4m4 4l4-4M3 15h18c1.1 0 2 .9 2 2v4c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2v-4c0-1.1.9-2 2-2z"></path>
                        </svg>
                    </div>
                    <h3>Upload & Showcase</h3>
                    <p>Upload proyek desain rumah Anda dan tampilkan kepada komunitas arsitektur profesional</p>
                </div>

                <div class="service-card" onclick="openServiceModal('Visualisasi 3D', 'Bawa desain Anda menjadi hidup. Dengan teknologi viewer 3D berbasis web kami, klien dapat menjelajahi setiap sudut ruangan dalam tampilan 360 derajat yang imersif langsung dari browser tanpa perlu menginstal aplikasi tambahan.', '3d')">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
                            <path d="M21 17.5L12 23l-9-5.5V6.5L12 1l9 5.5z"/>
                            <path d="M3 6.5L12 12m9-5.5L12 12m0 0v11"/>
                        </svg>
                    </div>
                    <h3>Visualisasi 3D</h3>
                    <p>Lihat desain rumah dalam bentuk 3D interaktif 360 derajat dengan teknologi viewer advanced</p>
                </div>

                <div class="service-card" onclick="openServiceModal('Komunitas & Review', 'Bangun reputasi dan kredibilitas Anda. Sistem ulasan transparan kami memungkinkan klien dan rekan sejawat memberikan rating bintang dan umpan balik konstruktif, membantu Anda meningkatkan kualitas layanan dan kepercayaan pasar.', 'review')">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                        </svg>
                    </div>
                    <h3>Komunitas & Review</h3>
                    <p>Dapatkan feedback dan rating dari komunitas untuk meningkatkan kualitas desain Anda</p>
                </div>

                <div class="service-card" onclick="openServiceModal('Kategori Proyek', 'Temukan inspirasi dengan mudah. Proyek dikategorikan secara cerdas berdasarkan gaya arsitektur (Modern, Minimalis, Tradisional, Contemporary) dan lokasi, memudahkan klien menemukan spesialisasi desain yang Anda tawarkan.', 'category')">
                    <div class="service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
                            <rect x="3" y="3" width="18" height="4" rx="1"></rect>
                            <rect x="3" y="9" width="18" height="4" rx="1"></rect>
                            <rect x="3" y="15" width="18" height="4" rx="1"></rect>
                        </svg>
                    </div>
                    <h3>Kategori Proyek</h3>
                    <p>Jelajahi proyek berdasarkan gaya arsitektur: Modern, Minimalis, Tradisional, dan Contemporary</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Projects Section -->
    <!-- Changed "Proyek Unggulan" to "Kategori Proyek" and removed "Lihat 3D" buttons -->
    <section id="projects" class="projects">
        <div class="container">
            <h2>Kategori Proyek</h2>
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all">Semua</button>
                <button class="filter-btn" data-filter="modern">Modern</button>
                <button class="filter-btn" data-filter="minimalis">Minimalis</button>
                <button class="filter-btn" data-filter="tradisional">Tradisional</button>
            </div>
            <div class="projects-grid">
                <div class="project-card slide-in" data-category="modern">
                    <div class="project-image">
                        <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=500&h=300&fit=crop" alt="Modern House Design">
                        <span class="project-badge">Modern</span>
                    </div>
                    <div class="project-info">
                        <h3>Rumah Minimalis Modern</h3>
                        <p class="project-architect">by Arsitek Profesional</p>
                        <p class="project-desc">Desain rumah minimalis dengan konsep modern yang elegan dan fungsional</p>
                    </div>
                </div>

                <div class="project-card slide-in" data-category="minimalis">
                    <div class="project-image">
                        <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=500&h=300&fit=crop" alt="Minimalist House">
                        <span class="project-badge">Minimalis</span>
                    </div>
                    <div class="project-info">
                        <h3>Rumah Minimalis Skandinavia</h3>
                        <p class="project-architect">by Arsitek Studio</p>
                        <p class="project-desc">Konsep Skandinavia dengan sentuhan minimalis yang nyaman untuk keluarga</p>
                    </div>
                </div>

                <div class="project-card slide-in" data-category="tradisional">
                    <div class="project-image">
                        <img src="https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=500&h=300&fit=crop" alt="Traditional House">
                        <span class="project-badge">Tradisional</span>
                    </div>
                    <div class="project-info">
                        <h3>Rumah Tradisional Jawa</h3>
                        <p class="project-architect">by Studio Heritage</p>
                        <p class="project-desc">Preservasi budaya tradisional Jawa dengan sentuhan modern yang kontemporer</p>
                    </div>
                </div>

                <div class="project-card slide-in" data-category="modern">
                    <div class="project-image">
                        <!-- Replaced Villa Mewah image with better quality architecture photo -->
                        <img src="https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?w=500&h=300&fit=crop" alt="Villa Mewah Kontemporer">
                        <span class="project-badge">Modern</span>
                    </div>
                    <div class="project-info">
                        <h3>Villa Mewah Kontemporer</h3>
                        <p class="project-architect">by Premium Architects</p>
                        <p class="project-desc">Desain villa mewah dengan fasad modern dan interior yang sophisticated</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about slide-in">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Tentang Archi.ID</h2>
                    <p>Kami adalah platform portfolio terdepan yang memamerkan karya-karya terbaik dari arsitek profesional Indonesia.</p>
                    <ul class="about-list">
                        <li>✓ Ribuan desain arsitektur dari profesional terbaik</li>
                        <li>✓ Teknologi viewer 3D interaktif 360°</li>
                        <li>✓ Sistem rating dan review dari komunitas</li>
                        <li>✓ Portfolio terverifikasi dari arsitek berpengalaman</li>
                        <li>✓ Inspirasi desain untuk proyek Anda</li>
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
    <!-- Replaced emoji icons with SVG icons -->
    <section id="contact" class="contact">
        <div class="container">
            <h2>Hubungi Kami</h2>
            <div class="contact-wrapper">
                <form class="contact-form" id="contactForm" method="POST">
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
                        <h4>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="display: inline-block; margin-right: 8px; vertical-align: middle;">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            Lokasi
                        </h4>
                        <p>Makassar, Indonesia</p>
                    </div>
                    <div class="info-item">
                        <h4>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="display: inline-block; margin-right: 8px; vertical-align: middle;">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            Telepon
                        </h4>
                        <p>+62 812 3456 7890</p>
                    </div>
                    <div class="info-item">
                        <h4>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="display: inline-block; margin-right: 8px; vertical-align: middle;">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            Email
                        </h4>
                        <p>info@archi.id</p>
                    </div>
                    <div class="info-item">
                        <h4>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" style="display: inline-block; margin-right: 8px; vertical-align: middle;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Jam Operasional
                        </h4>
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
                        <li><a href="#" onclick="openServiceModal('Kebijakan Privasi', 'Kami sangat menghargai privasi Anda. Data yang dikumpulkan hanya digunakan untuk keperluan fungsionalitas website, seperti menampilkan profil dan menghubungi arsitek. Kami tidak menjual data Anda ke pihak ketiga.', 'legal'); return false;">Kebijakan Privasi</a></li>
                        
                        <li><a href="#" onclick="openServiceModal('Syarat & Ketentuan', 'Dengan menggunakan platform Archi.ID, Anda menyetujui untuk tidak mengunggah konten yang melanggar hak cipta, mengandung unsur SARA, atau penipuan. Archi.ID bertindak sebagai perantara dan tidak bertanggung jawab atas kesepakatan di luar platform.', 'legal'); return false;">Syarat & Ketentuan</a></li>
                        
                        <li><a href="#" onclick="openServiceModal('FAQ (Tanya Jawab)', 'Q: Apakah berbayar? A: Saat ini platform gratis.\nQ: Bagaimana cara upload? A: Login, masuk dashboard, klik Buat Proyek.\nQ: Format file model? A: Saat ini kami mendukung .glb dan .gltf.', 'legal'); return false;">FAQ</a></li>
                    </ul>
                </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Archi.ID. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
    <script src="js/dropdown-handler.js"></script>
    <script type="importmap">
    {
        "imports": {
            "three": "https://esm.sh/three@r128",
            "three/addons/loaders/GLTFLoader.js": "https://esm.sh/three@r128/examples/jsm/loaders/GLTFLoader.js",
            "three/addons/controls/OrbitControls.js": "https://esm.sh/three@r128/examples/jsm/controls/OrbitControls.js"
        }
    }
    </script>
    <script type="module" src="js/3d-preview.js"></script>

    <div id="serviceDetailModal" class="service-modal" onclick="closeServiceModalOnOutside(event)">
        <div class="service-modal-content">
            <span class="service-modal-close" onclick="closeServiceModal()">&times;</span>
            
            <div class="service-modal-header">
                <div class="service-modal-icon-large" id="modalIconContainer">
                    </div>
                <h3 id="modalTitle">Judul Layanan</h3>
            </div>
            
            <div class="service-modal-body">
                <p id="modalDescription">Deskripsi layanan akan muncul di sini.</p>
            </div>
            
            <div class="service-modal-footer">
                <button class="btn-modal-action" onclick="closeServiceModal()">Mengerti</button>
            </div>
        </div>
    </div>

    <script>
        // Data Icon SVG (Updated dengan icon 'legal')
        const serviceIcons = {
            'upload': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32"><path d="M12 2v12m0 0l-4-4m4 4l4-4M3 15h18c1.1 0 2 .9 2 2v4c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2v-4c0-1.1.9-2 2-2z"></path></svg>',
            '3d': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32"><path d="M21 17.5L12 23l-9-5.5V6.5L12 1l9 5.5z"/><path d="M3 6.5L12 12m9-5.5L12 12m0 0v11"/></svg>',
            'review': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>',
            'category': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32"><rect x="3" y="3" width="18" height="4" rx="1"></rect><rect x="3" y="9" width="18" height="4" rx="1"></rect><rect x="3" y="15" width="18" height="4" rx="1"></rect></svg>',
            
            // TAMBAHAN BARU: Icon Dokumen/Legal
            'legal': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>'
        };
        // Fungsi Buka Modal
        function openServiceModal(title, description, iconType) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalDescription').textContent = description;
            
            // Set icon
            const iconHtml = serviceIcons[iconType] || serviceIcons['category'];
            document.getElementById('modalIconContainer').innerHTML = iconHtml;

            // Tampilkan dengan animasi
            const modal = document.getElementById('serviceDetailModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Cegah scroll background
        }

        // Fungsi Tutup Modal
        function closeServiceModal() {
            const modal = document.getElementById('serviceDetailModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto'; // Aktifkan scroll lagi
        }

        // Tutup jika klik di luar area modal (background gelap)
        function closeServiceModalOnOutside(event) {
            if (event.target.id === 'serviceDetailModal') {
                closeServiceModal();
            }
        }
        
        // Tutup dengan tombol ESC keyboard
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeServiceModal();
            }
        });
    </script>
</body>
</html>

