<?php
session_start();
require 'config/database.php';
require 'config/session.php';

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$show_redirect = false;

// Fetch current user data
$stmt = $conn->prepare("SELECT id, name, email, profile_picture, phone, address, bio, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

if (!$user) {
    $user = array(
        'name' => '',
        'email' => '',
        'profile_picture' => '',
        'phone' => '',
        'address' => '',
        'bio' => ''
    );
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $profile_picture = $user['profile_picture'];

    // Validation
    if (empty($name)) {
        $error_message = 'Nama tidak boleh kosong.';
    } elseif (strlen($name) > 100) {
        $error_message = 'Nama terlalu panjang (maksimal 100 karakter).';
    } elseif (strlen($phone) > 15) {
        $error_message = 'Nomor telepon tidak valid.';
    } elseif (strlen($address) > 1000) {
        $error_message = 'Alamat terlalu panjang.';
    } elseif (strlen($bio) > 5000) {
        $error_message = 'Bio terlalu panjang (maksimal 5000 karakter).';
    } else {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                $error_message = 'Format gambar tidak valid. Gunakan JPG, PNG, GIF, atau WebP.';
            } elseif ($_FILES['profile_picture']['size'] > $max_size) {
                $error_message = 'Ukuran gambar terlalu besar (maksimal 5MB).';
            } else {
                $upload_dir = 'assets/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $filename = 'profile_' . $user_id . '_' . time() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {
                    // Delete old profile picture if exists
                    if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                        unlink($user['profile_picture']);
                    }
                    $profile_picture = $filepath;
                } else {
                    $error_message = 'Gagal mengupload gambar profil.';
                }
            }
        }

        // Update database if no errors
        if (empty($error_message)) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ?, bio = ?, profile_picture = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("sssssi", $name, $phone, $address, $bio, $profile_picture, $user_id);

            if ($stmt->execute()) {
                // Update session
                $_SESSION['user_name'] = $name;
                
                $success_message = 'Profil Anda berhasil diubah.';
                $show_redirect = true;
            } else {
                $error_message = 'Gagal memperbarui profil: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Archi.ID</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
    <style>
        /* Minimal inline styles for edit-profile specific layout */
        .edit-profile-wrapper {
            padding: 3rem 0;
            min-height: calc(100vh - 200px);
            background: linear-gradient(135deg, #f5f1ed 0%, #fafaf8 100%);
        }

        .profile-pic-section {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 2.5rem;
            border-bottom: 2px solid #f5f1ed;
        }

        .profile-pic-preview {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 4px solid var(--accent-warm);
            object-fit: cover;
            margin: 0 auto 1.5rem;
            display: block;
        }

        .file-upload-btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary-brown);
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .file-upload-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        #profile_picture {
            display: none;
        }

        .file-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 1rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
        }

        .btn-submit {
            flex: 1;
            padding: 14px 32px;
            background: var(--primary-brown);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-cancel {
            flex: 1;
            padding: 14px 32px;
            background: #e8e3dd;
            color: var(--text-primary);
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #ddd5ce;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
            }

            .dashboard-form .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
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
                <!-- Added inline style with flex-direction: row-reverse to position image on right -->
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
    </header>

    <!-- Edit Profile Content -->
    <div class="edit-profile-wrapper">
        <div class="container">
            <div class="dashboard-card" style="max-width: 700px; margin: 0 auto;">
                <div class="card-header">
                    <h2>Edit Profil</h2>
                    <p class="card-description">Perbarui informasi profil dan foto Anda</p>
                </div>

                <!-- Alert messages using dashboard-style classes -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                    <!-- Add JavaScript for auto-redirect after 2 seconds if success -->
                    <?php if ($show_redirect): ?>
                        <script>
                            setTimeout(function() {
                                window.location.href = 'profile.php';
                            }, 2000);
                        </script>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="dashboard-form">
                    <!-- Profile Picture Section -->
                    <div class="profile-pic-section">
                        <label class="profile-pic-label" style="display: block; font-weight: 600; margin-bottom: 1rem; color: var(--text-primary);">Foto Profil</label>
                        <img 
                            src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'assets/images/default-profile.png'; ?>" 
                            alt="Foto Profil" 
                            class="profile-pic-preview"
                            onerror="this.src='assets/images/default-profile.png'"
                        >
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp" onchange="updateFileName(this)">
                        <label for="profile_picture" class="file-upload-btn">Pilih Foto Profil</label>
                        <div class="file-info">
                            <div id="file-name"></div>
                            <p>JPG, PNG, GIF, atau WebP. Maksimal 5MB.</p>
                        </div>
                    </div>

                    <!-- Form Fields -->
                    <div class="form-group">
                        <label for="name">Nama Lengkap *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required maxlength="100" placeholder="Masukkan nama lengkap Anda">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled readonly placeholder="Email Anda">
                    </div>

                    <div class="form-group">
                        <label for="phone">Nomor Telepon</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="08xxxxxxxxxx" maxlength="15">
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Jalan, Kota, Provinsi">
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio / Deskripsi Diri</label>
                        <textarea id="bio" name="bio" placeholder="Ceritakan tentang Anda dan pengalaman sebagai arsitek..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="button-group">
                        <button type="submit" class="btn-submit">Simpan Perubahan</button>
                        <a href="profile.php" class="btn-cancel">Batal</a>
                    </div>
                </form>
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

    <script>
        function updateFileName(input) {
            const fileName = input.files[0]?.name || '';
            const fileNameElement = document.getElementById('file-name');
            if (fileName) {
                fileNameElement.textContent = 'File dipilih: ' + fileName;
            } else {
                fileNameElement.textContent = '';
            }
        }
    </script>
</body>
</html>
