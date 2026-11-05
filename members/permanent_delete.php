<?php

/*
|--------------------------------------------------------------------------
| AS-SYSTEM
| PERMANENTLY DELETE ARCHIVED MEMBER
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| REDIRECT FUNCTION
|--------------------------------------------------------------------------
*/

function permanentDeleteRedirect(string $message): void
{
    header(
        'Location: /members/archived.php?message=' . urlencode($message)
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| ALLOW POST REQUESTS ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    permanentDeleteRedirect('Invalid request.');
}

/*
|--------------------------------------------------------------------------
| VALIDATE CSRF TOKEN
|--------------------------------------------------------------------------
*/

requireValidCSRFToken();

/*
|--------------------------------------------------------------------------
| VALIDATE MEMBER ID
|--------------------------------------------------------------------------
*/

$memberId = filter_input(
    INPUT_POST,
    'member_id',
    FILTER_VALIDATE_INT
);

if (!$memberId || $memberId < 1) {
    permanentDeleteRedirect('Invalid member ID.');
}

/*
|--------------------------------------------------------------------------
| PERMANENT DELETION
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | CHECK IF MEMBER EXISTS AND IS ARCHIVED
    |--------------------------------------------------------------------------
    */

    $memberStmt = $pdo->prepare("
        SELECT id, full_name
        FROM public.members
        WHERE id = :member_id
          AND status = 'Archived'
        FOR UPDATE
    ");

    $memberStmt->execute([
        ':member_id' => $memberId
    ]);

    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        $pdo->rollBack();

        permanentDeleteRedirect(
            'Member not found or is not archived.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE RELATED ATTENDANCE RECORDS
    |--------------------------------------------------------------------------
    */

    $attendanceStmt = $pdo->prepare("
        DELETE FROM public.mass_attendance
        WHERE member_id = :member_id
    ");

    $attendanceStmt->execute([
        ':member_id' => $memberId
    ]);

    /*
    |--------------------------------------------------------------------------
    | DELETE ARCHIVED MEMBER
    |--------------------------------------------------------------------------
    */

    $deleteStmt = $pdo->prepare("
        DELETE FROM public.members
        WHERE id = :member_id
          AND status = 'Archived'
    ");

    $deleteStmt->execute([
        ':member_id' => $memberId
    ]);

    if ($deleteStmt->rowCount() !== 1) {

        throw new RuntimeException(
            'Member deletion was unsuccessful.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMMIT TRANSACTION
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    permanentDeleteRedirect(
        'Member and related attendance records permanently deleted.'
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'AS-SYSTEM PERMANENT DELETE ERROR: ' . $e->getMessage()
    );

    permanentDeleteRedirect(
        'Unable to delete this member. Please check the database relationships.'
    );
}