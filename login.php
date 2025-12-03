<?php
require 'config/database.php';
require 'config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    if (empty($email) || empty($password)) {
        $errors[] = "Email dan password harus diisi";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['login_time'] = time();

                if ($remember) {
                    setRememberMeCookie($user['id'], $user['email']);
                }

                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Email atau password salah";
            }
        } else {
            $errors[] = "Email atau password salah";
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
    <title>Login - Archi.ID</title>
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
                        <p>Login ke Akun Anda</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <p>• <?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="nama@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
                        </div>

                        <div class="form-group-row">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember">Ingat saya</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-login">Login</button>
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
                        Belum punya akun? <a href="register.php">Daftar di sini</a>
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
