
<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| CSRF Protection
|--------------------------------------------------------------------------
*/

// Start session if not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve CSRF token.
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Display hidden CSRF input field.
 */
function csrfField(): string
{
    $token = htmlspecialchars(
        generateCSRFToken(),
        ENT_QUOTES,
        'UTF-8'
    );

    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate submitted CSRF token.
 */
function requireValidCSRFToken(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $submittedToken)
    ) {
        http_response_code(403);

        exit(
            'Security validation failed. Please refresh the page and try again.'
        );
    }
}
