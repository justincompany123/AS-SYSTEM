
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

function permanentDeleteRedirect(string $message): void
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
    permanentDeleteRedirect('Invalid member ID.');
}

try {

    $pdo->beginTransaction();

    // Confirm that the member exists and is archived.
    // Lock the row while processing the deletion.
    $memberStmt = $pdo->prepare("
        SELECT id, full_name
        FROM public.members
        WHERE id = :member_id
          AND status = 'Archived'
        FOR UPDATE
    ");

    $memberStmt->execute([
        'member_id' => $memberId
    ]);

    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        $pdo->rollBack();

        permanentDeleteRedirect(
            'Member not found or is not archived.'
        );
    }

    // Check whether attendance history exists.
    $attendanceStmt = $pdo->prepare("
        SELECT EXISTS (
            SELECT 1
            FROM public.mass_attendance
            WHERE member_id = :member_id
        )
    ");

    $attendanceStmt->execute([
        'member_id' => $memberId
    ]);

    $hasAttendance = $attendanceStmt->fetchColumn();

    if ($hasAttendance) {

        $pdo->rollBack();

        permanentDeleteRedirect(
            'This member has attendance records. Permanent deletion was blocked to protect the attendance history.'
        );
    }

    // Delete only an archived member with no attendance records.
    $deleteStmt = $pdo->prepare("
        DELETE FROM public.members
        WHERE id = :member_id
          AND status = 'Archived'
    ");

    $deleteStmt->execute([
        'member_id' => $memberId
    ]);

    if ($deleteStmt->rowCount() === 0) {
        $pdo->rollBack();

        permanentDeleteRedirect(
            'The member could not be deleted.'
        );
    }

    $pdo->commit();

    permanentDeleteRedirect(
        'Member permanently deleted successfully.'
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Permanent member deletion error: ' . $e->getMessage()
    );

    permanentDeleteRedirect(
        'Unable to permanently delete this member. A related database record may prevent deletion.'
    );
}
