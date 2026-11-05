<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| AUTHENTICATION AND SESSION MANAGEMENT
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

// Do not allow output before session initialization.
if (headers_sent($file, $line)) {
    error_log(
        "AS-SYSTEM: Headers already sent in {$file} on line {$line}."
    );

    exit('Application initialization error. Please check PHP file encoding and include order.');
}

// Buffer accidental output from included PHP files.
if (ob_get_level() === 0) {
    ob_start();
}

/*
|--------------------------------------------------------------------------
| DETECT HTTPS
|--------------------------------------------------------------------------
| Render may terminate HTTPS at its proxy and forward the request to
| Apache over HTTP. Check X-Forwarded-Proto as well.
*/

$isSecure = false;

if (
    isset($_SERVER['HTTPS']) &&
    strtolower((string)$_SERVER['HTTPS']) !== 'off' &&
    (string)$_SERVER['HTTPS'] !== ''
) {
    $isSecure = true;
}

if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $forwardedProtocols = explode(
        ',',
        strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO'])
    );

    $firstProtocol = trim($forwardedProtocols[0]);

    if ($firstProtocol === 'https') {
        $isSecure = true;
    }
}

/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    if (!session_start()) {
        error_log('AS-SYSTEM: Unable to start PHP session.');
        exit('Unable to start your session. Please try again.');
    }
}

/*
|--------------------------------------------------------------------------
| CHECK LOGIN STATUS
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    return isset($_SESSION['admin_id'])
        && $_SESSION['admin_id'] !== ''
        && $_SESSION['admin_id'] !== null;
}

/*
|--------------------------------------------------------------------------
| REQUIRE ADMINISTRATOR LOGIN
|--------------------------------------------------------------------------
*/

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| REDIRECT ALREADY LOGGED-IN ADMINISTRATORS
|--------------------------------------------------------------------------
*/

function redirectIfLoggedIn(): void
{
    if (isLoggedIn()) {
        header('Location: /index.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| GET ADMINISTRATOR INFORMATION
|--------------------------------------------------------------------------
*/

function getAdminId()
{
    return $_SESSION['admin_id'] ?? null;
}

function getAdminName(): string
{
    return $_SESSION['admin_name'] ?? 'Administrator';
}

/*
|--------------------------------------------------------------------------
| LOGOUT ADMINISTRATOR
|--------------------------------------------------------------------------
*/

function logoutAdmin(): void
{
    // Clear session data.
    $_SESSION = [];

    // Remove the session cookie.
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }

    // Destroy the session.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    header('Location: /login.php');
    exit;
}
