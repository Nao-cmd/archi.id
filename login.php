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
    <div class="auth-video-overlay"></div>

    <div class="auth-page">
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

                    <!-- Removed OAuth buttons section -->
                    
                    <p class="auth-link">
                        Belum punya akun? <a href="register.php">Daftar di sini</a>
                    </p>
                </div>
            </div>

            <!-- Updated image with Unsplash architecture photo -->
            <div class="auth-image-section">
                <img src="https://images.unsplash.com/photo-1486325212027-8081e485255e?w=600&h=700&fit=crop&q=80" alt="Modern Architecture Design" class="auth-image">
            </div>
        </div>
    </div>
</body>
</html>
