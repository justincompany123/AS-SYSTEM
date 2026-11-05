
<?php
/**
 * ALTAR SERVERS MANAGEMENT SYSTEM
 * Authentication and Session Management
 */

// Detect output sent before authentication was loaded.
if (headers_sent($file, $line)) {
    error_log(
        "AS-SYSTEM ERROR: Output started in {$file} on line {$line} before auth.php."
    );

    exit("Application initialization error. Please check PHP file encoding and include order.");
}

// Start output buffering before application output.
if (ob_get_level() === 0) {
    ob_start();
}

// Configure and start the session.
if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
        )
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Check login status.
function isLoggedIn()
{
    return isset($_SESSION['admin_id'])
        && !empty($_SESSION['admin_id']);
}

// Require administrator login.
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

// Redirect already logged-in administrators.
function redirectIfLoggedIn()
{
    if (isLoggedIn()) {
        header('Location: /index.php');
        exit;
    }
}

// Get current administrator ID.
function getAdminId()
{
    return $_SESSION['admin_id'] ?? null;
}

// Get current administrator name.
function getAdminName()
{
    return $_SESSION['admin_name'] ?? 'Administrator';
}

// Log out administrator.
function logoutAdmin()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax'
        ]);
    }

    session_destroy();

    header('Location: /login.php');
    exit;
}
