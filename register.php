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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth-style.css">
</head>
<body>
    <!-- Added video background container -->
    <video class="auth-video-bg" autoplay muted loop playsinline>
        <source src="assets/videos/background.mp4" type="video/mp4">
    </video>
    <div class="auth-video-overlay"></div>

    <div class="auth-page">
        <!-- Split layout - left form, right decorative -->
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
                                <label for="agree_terms">Saya setuju dengan <a href="#">syarat & ketentuan</a></label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register">Daftar</button>
                    </form>

                    <div class="auth-divider">
                        <span>atau</span>
                    </div>

                    <div class="social-login">
                        <button type="button" class="btn-social">
                            <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google Icon" width="20" height="20">
                            Google
                        </button>
                        <button type="button" class="btn-social">
                            <!-- Using Apple logo image instead of emoji -->
                            <img src="assets/images/apple-logo.png" alt="Apple Icon" width="20" height="20">
                            Apple
                        </button>
                    </div>

                    <p class="auth-link">
                        Sudah punya akun? <a href="login.php">Login di sini</a>
                    </p>
                </div>
            </div>

            <div class="auth-image-section">
                <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&h=700&fit=crop" alt="Architecture Building" class="auth-image">
            </div>
        </div>
    </div>
</body>
</html>
