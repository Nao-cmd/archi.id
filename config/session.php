<?php
// Session and Cookie Management Helper
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get current user
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ];
    }
    return null;
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Function to set remember me cookie
function setRememberMeCookie($user_id, $email) {
    $token = md5($user_id . $email . time());
    setcookie('user_id', $user_id, time() + (30 * 24 * 60 * 60), '/');
    setcookie('user_token', $token, time() + (30 * 24 * 60 * 60), '/');
}

// Function to clear remember me cookies
function clearRememberMeCookies() {
    setcookie('user_id', '', time() - 3600, '/');
    setcookie('user_token', '', time() - 3600, '/');
}

// Auto-login using remember me cookies
function autoLoginFromCookie($conn) {
    if (isset($_COOKIE['user_id']) && !isLoggedIn()) {
        $user_id = intval($_COOKIE['user_id']);
        
        // Fetch user from database
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['login_time'] = time();
        }
        $stmt->close();
    }
}
?>
