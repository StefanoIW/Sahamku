<?php
// Set timezone to Jakarta
date_default_timezone_set('Asia/Jakarta');

session_start();
require_once 'db_config.php';
require_once 'send_email.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'register':
            handleRegister();
            break;
        case 'login':
            handleLogin();
            break;
        case 'verify':
            handleVerify();
            break;
        case 'resend_code':
            handleResendCode();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Handle user registration
 */
function handleRegister() {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        redirectWithError('index.php', 'Semua field harus diisi!');
        return;
    }
    
    if ($password !== $confirmPassword) {
        redirectWithError('index.php', 'Password tidak sama!');
        return;
    }
    
    if (strlen($password) < 8) {
        redirectWithError('index.php', 'Password minimal 8 karakter!');
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithError('index.php', 'Email tidak valid!');
        return;
    }
    
    // Check if email exists
    $checkSql = "SELECT id, email_verified FROM users WHERE email = ?";
    $existing = fetchOne($checkSql, [$email]);
    
    if ($existing) {
        if ($existing['email_verified'] == 1) {
            redirectWithError('index.php', 'Email sudah terdaftar! Silakan login.');
            return;
        } else {
            // Email exists but not verified - resend code
            $verificationCode = generateVerificationCode();
            $verificationExpires = date('Y-m-d H:i:s', time() + 600); // 10 minutes
            
            error_log("Resend for existing - Code: $verificationCode, Expires: $verificationExpires");
            
            $updateSql = "UPDATE users SET verification_code = ?, verification_expires = ?, updated_at = NOW() WHERE email = ?";
            executeQuery($updateSql, [$verificationCode, $verificationExpires, $email]);
            
            if (sendVerificationEmail($email, $verificationCode, $name)) {
                $_SESSION['success_message'] = "✅ Akun Anda belum diverifikasi. Kode baru telah dikirim ke $email";
                header('Location: index.php?verify=true&email=' . urlencode($email));
                exit;
            } else {
                redirectWithError('index.php', 'Gagal mengirim email verifikasi. Silakan coba lagi.');
                return;
            }
        }
    }
    
    // Generate verification code
    $verificationCode = generateVerificationCode();
    $verificationExpires = date('Y-m-d H:i:s', time() + 600); // 10 minutes
    
    // Debug log
    error_log("Register - Email: $email, Code: $verificationCode, Expires: $verificationExpires, Current: " . date('Y-m-d H:i:s'));
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert user
    $insertSql = "INSERT INTO users (name, email, password, verification_code, verification_expires, subscription_plan, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, 'starter', NOW(), NOW())";
    $result = executeQuery($insertSql, [$name, $email, $hashedPassword, $verificationCode, $verificationExpires]);
    
    if ($result['success']) {
        // Send verification email
        $emailSent = sendVerificationEmail($email, $verificationCode, $name);
        
        if ($emailSent) {
            $_SESSION['pending_verification'] = $email;
            $_SESSION['pending_name'] = $name;
            $_SESSION['success_message'] = "✅ Registrasi berhasil! Kode verifikasi telah dikirim ke $email. Kode berlaku 10 menit.";
            header('Location: index.php?verify=true&email=' . urlencode($email));
            exit;
        } else {
            $_SESSION['pending_verification'] = $email;
            $_SESSION['error_message'] = "⚠️ Registrasi berhasil, tapi gagal mengirim email. Klik 'Kirim Ulang' untuk mencoba lagi.";
            header('Location: index.php?verify=true&email=' . urlencode($email));
            exit;
        }
    } else {
        redirectWithError('index.php', 'Registrasi gagal: ' . ($result['error'] ?? 'Unknown error'));
    }
}

/**
 * Handle user login
 */
function handleLogin() {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        redirectWithError('index.php', 'Email dan password harus diisi!');
        return;
    }
    
    // Get user
    $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
    $user = fetchOne($sql, [$email]);
    
    if (!$user) {
        redirectWithError('index.php', 'Email atau password salah!');
        return;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        redirectWithError('index.php', 'Email atau password salah!');
        return;
    }
    
    // Check email verification
    if (!$user['email_verified']) {
        // Resend verification code
        $newCode = generateVerificationCode();
        $newExpires = date('Y-m-d H:i:s', time() + 600); // 10 minutes
        
        error_log("Login - User not verified. New code: $newCode, Expires: $newExpires");
        
        executeQuery("UPDATE users SET verification_code = ?, verification_expires = ?, updated_at = NOW() WHERE id = ?", 
                    [$newCode, $newExpires, $user['id']]);
        sendVerificationEmail($email, $newCode, $user['name']);
        
        $_SESSION['pending_verification'] = $email;
        $_SESSION['error_message'] = '⚠️ Email belum diverifikasi! Kode baru telah dikirim ke email Anda.';
        header('Location: index.php?verify=true&email=' . urlencode($email));
        exit;
    }
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_picture'] = $user['profile_picture'];
    $_SESSION['subscription_plan'] = $user['subscription_plan'];
    
    // Create session token
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + ($remember ? 2592000 : 86400)); // 30 days or 24 hours
    
    $insertSession = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
    executeQuery($insertSession, [
        $user['id'],
        $sessionToken,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        $expiresAt
    ]);
    
    if ($remember) {
        setcookie('session_token', $sessionToken, time() + 2592000, '/', '', false, true);
    }
    
    // Update last login
    executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    // Log activity
    logActivity($user['id'], 'login', ['method' => 'email']);
    
    $_SESSION['success_message'] = '✅ Login berhasil! Selamat datang, ' . $user['name'] . '!';
    
    // Redirect to dashboard
   header('Location: ../main/dashboard.php');

    exit;
}

/**
 * Handle email verification - FULLY FIXED
 */
function handleVerify() {
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    
    // Debug logging
    error_log("=== VERIFY ATTEMPT ===");
    error_log("Email: $email");
    error_log("Code received: $code");
    error_log("Code length: " . strlen($code));
    error_log("Current time: " . date('Y-m-d H:i:s'));
    
    if (empty($email) || empty($code)) {
        redirectWithError('index.php', 'Kode verifikasi tidak valid!');
        return;
    }
    
    // Validate code format
    if (!preg_match('/^\d{6}$/', $code)) {
        redirectWithError('index.php?verify=true&email=' . urlencode($email), 
                         '❌ Kode harus 6 digit angka!');
        return;
    }
    
    // Get user - FIXED: No reserved keywords
    $sql = "SELECT 
            id,
            name,
            email,
            profile_picture,
            subscription_plan,
            verification_code,
            verification_expires,
            email_verified,
            verification_expires > NOW() as is_valid,
            TIMESTAMPDIFF(SECOND, NOW(), verification_expires) as seconds_left
            FROM users 
            WHERE email = ? AND email_verified = 0";
    $user = fetchOne($sql, [$email]);
    
    if (!$user) {
        error_log("User not found or already verified");
        redirectWithError('index.php?verify=true&email=' . urlencode($email), 
                         '❌ Email tidak ditemukan atau sudah diverifikasi!');
        return;
    }
    
    // Debug log
    error_log("Code in DB: {$user['verification_code']}");
    error_log("Code from form: $code");
    error_log("Expires: {$user['verification_expires']}");
    error_log("Is valid: {$user['is_valid']}");
    error_log("Seconds left: {$user['seconds_left']}");
    
    // Check if code expired
    if ($user['is_valid'] == 0 || $user['seconds_left'] <= 0) {
        error_log("Code expired!");
        redirectWithError('index.php?verify=true&email=' . urlencode($email), 
                         '⏱️ Kode sudah kadaluarsa! Klik "Kirim Ulang" untuk mendapatkan kode baru.');
        return;
    }
    
    // Check if code matches
    if ($user['verification_code'] !== $code) {
        error_log("Code mismatch! DB: {$user['verification_code']}, Input: $code");
        redirectWithError('index.php?verify=true&email=' . urlencode($email), 
                         '❌ Kode verifikasi salah! Pastikan Anda memasukkan 6 digit dengan benar.');
        return;
    }
    
    error_log("Code matched! Proceeding with verification...");
    
    // Update user - set email as verified
    $updateSql = "UPDATE users SET 
                  email_verified = 1, 
                  verification_code = NULL, 
                  verification_expires = NULL, 
                  updated_at = NOW() 
                  WHERE id = ?";
    $result = executeQuery($updateSql, [$user['id']]);
    
    if ($result['success']) {
        error_log("User verified successfully!");
        
        // Auto login after verification
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_picture'] = $user['profile_picture'];
        $_SESSION['subscription_plan'] = $user['subscription_plan'];
        
        // Create session token
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 2592000); // 30 days
        
        $insertSession = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
        executeQuery($insertSession, [
            $user['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            $expiresAt
        ]);
        
        setcookie('session_token', $sessionToken, time() + 2592000, '/', '', false, true);
        
        // Log activity
        logActivity($user['id'], 'register', ['method' => 'email_verification']);
        
        $_SESSION['success_message'] = '🎉 Email berhasil diverifikasi! Selamat datang di SahamQu, ' . $user['name'] . '!';
        
        error_log("Redirecting to dashboard...");
        header('Location: ../main/dashboard.php');
        exit;
    } else {
        error_log("Failed to update user: " . ($result['error'] ?? 'Unknown error'));
        redirectWithError('index.php', 'Verifikasi gagal. Silakan coba lagi.');
    }
}

/**
 * Handle resend verification code (AJAX)
 */
function handleResendCode() {
    header('Content-Type: application/json');
    
    $email = trim($_POST['email'] ?? '');
    
    error_log("=== RESEND CODE ===");
    error_log("Email: $email");
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email tidak valid']);
        exit;
    }
    
    // Get user
    $sql = "SELECT id, name, email FROM users WHERE email = ? AND email_verified = 0";
    $user = fetchOne($sql, [$email]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan atau sudah terverifikasi']);
        exit;
    }
    
    // Generate new code
    $newCode = generateVerificationCode();
    $newExpires = date('Y-m-d H:i:s', time() + 600); // 10 minutes
    
    error_log("New code: $newCode, Expires: $newExpires, Current: " . date('Y-m-d H:i:s'));
    
    // Update code
    $updateSql = "UPDATE users SET verification_code = ?, verification_expires = ?, updated_at = NOW() WHERE id = ?";
    $result = executeQuery($updateSql, [$newCode, $newExpires, $user['id']]);
    
    if ($result['success'] && sendVerificationEmail($email, $newCode, $user['name'])) {
        error_log("Email sent successfully");
        echo json_encode([
            'success' => true, 
            'message' => '✅ Kode verifikasi baru telah dikirim ke ' . $email . '. Kode berlaku 10 menit.'
        ]);
    } else {
        error_log("Failed to send email");
        echo json_encode([
            'success' => false, 
            'message' => '❌ Gagal mengirim kode. Silakan coba lagi atau hubungi support.'
        ]);
    }
    exit;
}

/**
 * Log user activity
 */
function logActivity($userId, $type, $data = null) {
    $sql = "INSERT INTO user_activity_log (user_id, activity_type, activity_data, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    executeQuery($sql, [
        $userId,
        $type,
        $data ? json_encode($data) : null,
        $_SERVER['REMOTE_ADDR']
    ]);
}

/**
 * Redirect with error message
 */
function redirectWithError($url, $message) {
    $_SESSION['error_message'] = $message;
    header('Location: ' . $url);
    exit;
}

/**
 * Get current logged in user
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $sql = "SELECT id, name, email, profile_picture, subscription_plan, email_verified, created_at, last_login 
            FROM users WHERE id = ? AND status = 'active'";
    return fetchOne($sql, [$_SESSION['user_id']]);
}

/**
 * Require login (protect pages)
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = 'Anda harus login terlebih dahulu!';
        header('Location: landing/index.php');
        exit;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Handle logout
if (isset($_GET['logout'])) {
    error_log("Logout request");
    
    // Delete session token
    if (isset($_COOKIE['session_token'])) {
        $deleteSql = "DELETE FROM user_sessions WHERE session_token = ?";
        executeQuery($deleteSql, [$_COOKIE['session_token']]);
        setcookie('session_token', '', time() - 3600, '/');
    }
    
    // Log activity
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout');
    }
    
    // Destroy session
    session_destroy();
    $_SESSION = [];
    
    header('Location: index.php');
    exit;
}

// Auto-login from cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['session_token'])) {
    $token = $_COOKIE['session_token'];
    
    $sessionSql = "SELECT s.*, u.id as user_id, u.name, u.email, u.profile_picture, u.subscription_plan 
                   FROM user_sessions s 
                   JOIN users u ON s.user_id = u.id 
                   WHERE s.session_token = ? AND s.expires_at > NOW() AND u.status = 'active'";
    $session = fetchOne($sessionSql, [$token]);
    
    if ($session) {
        $_SESSION['user_id'] = $session['user_id'];
        $_SESSION['user_name'] = $session['name'];
        $_SESSION['user_email'] = $session['email'];
        $_SESSION['user_picture'] = $session['profile_picture'];
        $_SESSION['subscription_plan'] = $session['subscription_plan'];
        
        executeQuery("UPDATE user_sessions SET created_at = NOW() WHERE session_token = ?", [$token]);
        executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$session['user_id']]);
        
        error_log("Auto-login successful for: {$session['email']}");
    } else {
        setcookie('session_token', '', time() - 3600, '/');
        error_log("Auto-login failed - invalid token");
    }
}
?>
