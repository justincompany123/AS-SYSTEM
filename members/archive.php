
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

function archiveRedirect(string $message): void
{
    header('Location: index.php?message=' . urlencode($message));
    exit;
}

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Validate CSRF token using your existing csrf.php.
requireValidCSRFToken();

// Accept one member or multiple selected members.
$memberIds = $_POST['member_ids'] ?? [];

if (!is_array($memberIds)) {
    $memberIds = [];
}

if (isset($_POST['member_id'])) {
    $memberIds[] = $_POST['member_id'];
}

// Keep only valid positive integer IDs.
$memberIds = array_values(array_unique(array_filter(
    array_map('intval', $memberIds),
    function ($id) {
        return $id > 0;
    }
)));

if (empty($memberIds)) {
    archiveRedirect('Please select at least one member.');
}

try {
    $pdo->beginTransaction();

    // Create the correct number of SQL placeholders.
    $placeholders = implode(
        ', ',
        array_fill(0, count($memberIds), '?')
    );

    $sql = "
        UPDATE public.members
        SET status = 'Archived'
        WHERE id IN ($placeholders)
          AND status = 'Active'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($memberIds);

    $affected = $stmt->rowCount();

    $pdo->commit();

    if ($affected > 0) {
        archiveRedirect(
            $affected . ' member(s) successfully archived.'
        );
    }

    archiveRedirect(
        'No active members were found to archive.'
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Temporary debugging for your local XAMPP website.
    archiveRedirect('Archive error: ' . $e->getMessage());
}
