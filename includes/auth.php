
<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| Authentication Protection
|--------------------------------------------------------------------------
*/

// Start PHP session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define project base URL BEFORE using requireLogin().
if (!defined('BASE_URL')) {
    define('BASE_URL', '/AS-SYSTEM/');
}

// Check administrator login.
function requireLogin(): void
{
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Get administrator name.
function getAdminName(): string
{
    return htmlspecialchars(
        $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'Administrator',
        ENT_QUOTES,
        'UTF-8'
    );
}

// Check if administrator is logged in.
function isLoggedIn(): bool
{
    return isset($_SESSION['admin_id']);
}
