<?php
require 'config/database.php';
require 'config/session.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $agree_terms = isset($_POST['agree_terms']) ? true : false;

    // Validation
    if (empty($name)) $errors[] = "Nama harus diisi";
    if (empty($email)) $errors[] = "Email harus diisi";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
    if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";
    if ($password !== $password_confirm) $errors[] = "Password tidak cocok";
    if (!$agree_terms) $errors[] = "Anda harus setuju dengan syarat & ketentuan";

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Email sudah terdaftar";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $role = 'architect';
        $address = '';

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $hashed_password, $phone, $address, $role);

        if ($stmt->execute()) {
            $success = true;
            // Redirect to login after 2 seconds
            header("refresh:2;url=login.php");
        } else {
            $errors[] = "Gagal mendaftar. Silahkan coba lagi.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Archi.ID</title>
    <link rel="icon" href="assets/images/favicon.png?v=2" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth-style.css">
</head>
<body>
    <div class="auth-video-overlay"></div>

    <div class="auth-page">
        <div class="auth-content">
            <div class="auth-form-section">
                <div class="auth-form-wrapper">
                    <div class="auth-header">
                        <h1>Archi.ID</h1>
                        <p>Daftar sebagai Arsitek</p>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Pendaftaran berhasil! Silahkan login dengan akun Anda.
                        </div>
                        <script>
                            setTimeout(function() {
                                window.location.href = 'login.php';
                            }, 2000);
                        </script>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <p>• <?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="name">Nama Lengkap</label>
                            <input type="text" id="name" name="name" placeholder="Masukkan nama Anda" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="nama@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">Nomor Telepon</label>
                            <input type="tel" id="phone" name="phone" placeholder="08xx-xxxx-xxxx" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Konfirmasi Password</label>
                            <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi password" required minlength="6">
                        </div>

                        <div class="form-group-row">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="agree_terms" name="agree_terms" <?php echo isset($_POST['agree_terms']) ? 'checked' : ''; ?>>
                                <label for="agree_terms">Saya setuju dengan&nbsp;<a href="#" onclick="openServiceModal('Syarat & Ketentuan', 'Dengan menggunakan platform Archi.ID, Anda menyetujui untuk tidak mengunggah konten yang melanggar hak cipta, mengandung unsur SARA, atau penipuan. Archi.ID bertindak sebagai perantara dan tidak bertanggung jawab atas kesepakatan di luar platform.', 'legal'); return false;">syarat & ketentuan</a></label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register">Daftar</button>
                    </form>

                    <!-- Removed OAuth buttons section -->
                    
                    <p class="auth-link">
                        Sudah punya akun? <a href="login.php">Login di sini</a>
                    </p>
                </div>
            </div>

            <!-- Updated image with different Unsplash architecture photo -->
            <div class="auth-image-section">
                <img src="https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=600&h=700&fit=crop&q=80" alt="Building Architecture Interior" class="auth-image">
            </div>
        </div>
    </div>
    <div id="serviceDetailModal" class="service-modal" onclick="closeServiceModalOnOutside(event)">
        <div class="service-modal-content">
            <span class="service-modal-close" onclick="closeServiceModal()">&times;</span>
            
            <div class="service-modal-header">
                <div class="service-modal-icon-large" id="modalIconContainer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </div>
                <h3 id="modalTitle">Judul Legal</h3>
            </div>
            
            <div class="service-modal-body">
                <p id="modalDescription">Deskripsi legal akan muncul di sini.</p>
            </div>
            
            <div class="service-modal-footer">
                <button class="btn-modal-action" onclick="closeServiceModal()">Mengerti</button>
            </div>
        </div>
    </div>

    <script>
        // Data Icon SVG (Hanya untuk keperluan Modal ini)
        const serviceIcons = {
            'legal': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>'
        };

        // Fungsi Buka Modal
        function openServiceModal(title, description, iconType) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalDescription').textContent = description;
            
            // Set icon
            const iconHtml = serviceIcons[iconType] || '';
            document.getElementById('modalIconContainer').innerHTML = iconHtml;

            const modal = document.getElementById('serviceDetailModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Mencegah scroll di belakang modal
        }

        // Fungsi Tutup Modal
        function closeServiceModal() {
            const modal = document.getElementById('serviceDetailModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
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
