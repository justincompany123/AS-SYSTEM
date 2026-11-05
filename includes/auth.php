
<?php

// =====================================================
// ALTAR SERVERS MANAGEMENT SYSTEM
// AUTHENTICATION AND SESSION MANAGEMENT
// =====================================================

// Prevent accidental output
if (ob_get_level() === 0) {
    ob_start();
}

// Start session before any HTML output
if (session_status() === PHP_SESSION_NONE) {

    // Secure session settings
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) &&
                    $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// =====================================================
// CHECK LOGIN STATUS
// =====================================================

function isLoggedIn()
{
    return isset($_SESSION['admin_id']) &&
           !empty($_SESSION['admin_id']);
}

// =====================================================
// REQUIRE ADMIN LOGIN
// =====================================================

function requireLogin()
{
    if (!isLoggedIn()) {

        header("Location: /login.php");
        exit;
    }
}

// =====================================================
// REDIRECT IF ALREADY LOGGED IN
// =====================================================

function redirectIfLoggedIn()
{
    if (isLoggedIn()) {

        header("Location: /index.php");
        exit;
    }
}

// =====================================================
// GET CURRENT ADMIN ID
// =====================================================

function getAdminId()
{
    return $_SESSION['admin_id'] ?? null;
}

// =====================================================
// GET CURRENT ADMIN NAME
// =====================================================

function getAdminName()
{
    return $_SESSION['admin_name'] ?? 'Administrator';
}

// =====================================================
// LOGOUT ADMIN
// =====================================================

function logoutAdmin()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header("Location: /login.php");
    exit;
}
