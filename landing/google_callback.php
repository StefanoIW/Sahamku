<?php
session_start();
require_once 'google_config.php';
require_once 'db_config.php';
require_once 'auth_handler.php';

// Check for errors
if (isset($_GET['error'])) {
    $_SESSION['error_message'] = 'Google login dibatalkan atau gagal.';
    header('Location: index.php');
    exit;
}

// Verify state (CSRF protection)
if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    $_SESSION['error_message'] = 'Invalid state parameter. Possible CSRF attack.';
    header('Location: index.php');
    exit;
}

// Get authorization code
$code = $_GET['code'] ?? '';

if (empty($code)) {
    $_SESSION['error_message'] = 'Authorization code tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Exchange code for access token
$tokenData = getGoogleAccessToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    $_SESSION['error_message'] = 'Gagal mendapatkan access token dari Google.';
    header('Location: index.php');
    exit;
}

$accessToken = $tokenData['access_token'];

// Get user info
$userInfo = getGoogleUserInfo($accessToken);

if (!$userInfo || !isset($userInfo['email'])) {
    $_SESSION['error_message'] = 'Gagal mendapatkan informasi user dari Google.';
    header('Location: index.php');
    exit;
}

// Extract user data
$googleId = $userInfo['id'];
$email = $userInfo['email'];
$name = $userInfo['name'] ?? 'Google User';
$profilePicture = $userInfo['picture'] ?? null;

// Check if user exists with this Google ID
$checkGoogleSql = "SELECT * FROM users WHERE google_id = ?";
$existingGoogleUser = fetchOne($checkGoogleSql, [$googleId]);

if ($existingGoogleUser) {
    // User exists with Google ID - Login
    loginUser($existingGoogleUser);
    exit;
}

// Check if user exists with this email
$checkEmailSql = "SELECT * FROM users WHERE email = ?";
$existingEmailUser = fetchOne($checkEmailSql, [$email]);

if ($existingEmailUser) {
    // User exists with email - Link Google account
    $updateSql = "UPDATE users SET google_id = ?, profile_picture = ?, email_verified = 1 WHERE id = ?";
    executeQuery($updateSql, [$googleId, $profilePicture, $existingEmailUser['id']]);
    
    loginUser($existingEmailUser);
    exit;
}

// New user - Register with Google
$password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
$insertSql = "INSERT INTO users (name, email, password, google_id, profile_picture, email_verified, subscription_plan) 
              VALUES (?, ?, ?, ?, ?, 1, 'starter')";

$result = executeQuery($insertSql, [$name, $email, $password, $googleId, $profilePicture]);

if ($result['success']) {
    // Get newly created user
    $newUser = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    
    if ($newUser) {
        logActivity($newUser['id'], 'register', ['method' => 'google']);
        loginUser($newUser);
    } else {
        $_SESSION['error_message'] = 'Registrasi gagal. Silakan coba lagi.';
        header('Location: index.php');
    }
} else {
    $_SESSION['error_message'] = 'Registrasi gagal: ' . $result['error'];
    header('Location: index.php');
}

exit;

// Helper function to login user
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_picture'] = $user['profile_picture'];
    $_SESSION['subscription_plan'] = $user['subscription_plan'];
    
    // Create session token
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $insertSession = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                     VALUES (?, ?, ?, ?, ?)";
    executeQuery($insertSession, [
        $user['id'],
        $sessionToken,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $expiresAt
    ]);
    
    setcookie('session_token', $sessionToken, strtotime('+30 days'), '/', '', false, true);
    
    // Update last login
    executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    // Log activity
    logActivity($user['id'], 'login', ['method' => 'google']);
    
    $_SESSION['success_message'] = 'Login berhasil! Selamat datang, ' . $user['name'] . '!';
    header('Location: ../dashboard.php');
}
?>
