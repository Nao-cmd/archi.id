<?php
// OAuth Configuration for Google and Apple

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', 'http://localhost/callback-google.php');

// Apple OAuth Configuration
define('APPLE_CLIENT_ID', 'YOUR_APPLE_CLIENT_ID_HERE');
define('APPLE_TEAM_ID', 'YOUR_APPLE_TEAM_ID_HERE');
define('APPLE_KEY_ID', 'YOUR_APPLE_KEY_ID_HERE');
define('APPLE_PRIVATE_KEY_PATH', __DIR__ . '/../keys/apple-private-key.p8');
define('APPLE_REDIRECT_URI', 'http://localhost/callback-apple.php');

// OAuth State for CSRF protection
function generateOAuthState() {
    $state = bin2hex(random_bytes(32));
    $_SESSION['oauth_state'] = $state;
    return $state;
}

function validateOAuthState($state) {
    return isset($_SESSION['oauth_state']) && $_SESSION['oauth_state'] === $state;
}

function getGoogleAuthURL() {
    $state = generateOAuthState();
    $scopes = urlencode('openid email profile');
    return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => $scopes,
        'state' => $state,
        'access_type' => 'offline'
    ]);
}

function getAppleAuthURL() {
    $state = generateOAuthState();
    return "https://appleid.apple.com/auth/authorize?" . http_build_query([
        'client_id' => APPLE_CLIENT_ID,
        'redirect_uri' => APPLE_REDIRECT_URI,
        'response_type' => 'code id_token',
        'response_mode' => 'form_post',
        'scope' => 'openid email name',
        'state' => $state
    ]);
}
?>
