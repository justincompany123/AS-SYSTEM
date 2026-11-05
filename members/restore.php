<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

function restoreRedirect(string $message): void
{
    header('Location: archived.php?message=' . urlencode($message));
    exit;
}

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: archived.php');
    exit;
}

// Validate CSRF token.
requireValidCSRFToken();

// Validate member ID.
$memberId = filter_input(
    INPUT_POST,
    'member_id',
    FILTER_VALIDATE_INT
);

if (!$memberId || $memberId < 1) {
    restoreRedirect('Invalid member ID.');
}

try {

    $stmt = $pdo->prepare("
        UPDATE public.members
        SET status = 'Active'
        WHERE id = :member_id
          AND status = 'Archived'
    ");

    $stmt->execute([
        'member_id' => $memberId
    ]);

    if ($stmt->rowCount() > 0) {
        restoreRedirect(
            'Member successfully restored to the active member list.'
        );
    }

    restoreRedirect(
        'Member not found or is no longer archived.'
    );

} catch (Throwable $e) {

    error_log('Restore member error: ' . $e->getMessage());

    restoreRedirect(
        'Unable to restore this member. Please try again.'
    );
}
