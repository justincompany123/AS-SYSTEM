
<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| Record QR Attendance
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

function attendanceResponse(bool $success, string $message, int $status = 200): void {
    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    attendanceResponse(false, 'Invalid request method.', 405);
}

// Validate CSRF token for AJAX requests.
$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    attendanceResponse(false, 'Your session token is invalid. Refresh the page and try again.', 403);
}

$assignmentId = filter_var(
    $_POST['assignment_id'] ?? null,
    FILTER_VALIDATE_INT
);

$qrToken = trim($_POST['qr_token'] ?? '');

if (!$assignmentId || $qrToken === '') {
    attendanceResponse(false, 'Mass schedule and QR token are required.', 400);
}

if (strlen($qrToken) !== 64) {
    attendanceResponse(false, 'Invalid QR code token.', 400);
}

try {

    // Verify that the selected Mass schedule exists.
    $stmt = $pdo->prepare("
        SELECT id, mass_date, mass_time, mass_title
        FROM assignments
        WHERE id = :id
    ");

    $stmt->execute([':id' => $assignmentId]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        attendanceResponse(false, 'Selected Mass schedule was not found.', 404);
    }

    // Find the member using the unique QR token.
    $stmt = $pdo->prepare("
        SELECT id, member_code, first_name, middle_name,
               last_name, member_status
        FROM members
        WHERE qr_token = :qr_token
        LIMIT 1
    ");

    $stmt->execute([':qr_token' => $qrToken]);
    $member = $stmt->fetch();

    if (!$member) {
        attendanceResponse(false, 'QR code is not registered in the system.', 404);
    }

    if ($member['member_status'] !== 'Active') {
        attendanceResponse(false, 'This member is not currently active.', 403);
    }

    // Check whether the member is assigned to this Mass.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM assignment_members am
        INNER JOIN assignment_duties ad
            ON ad.id = am.assignment_duty_id
        WHERE ad.assignment_id = :assignment_id
        AND am.member_id = :member_id
    ");

    $stmt->execute([
        ':assignment_id' => $assignmentId,
        ':member_id' => $member['id']
    ]);

    $isAssigned = (int)$stmt->fetchColumn() > 0;

    if (!$isAssigned) {
        attendanceResponse(
            false,
            'Member identified, but this altar server is not assigned to the selected Mass.',
            403
        );
    }

    // Check for an existing attendance record.
    $stmt = $pdo->prepare("
        SELECT id, attendance_status, check_in_time
        FROM attendance
        WHERE assignment_id = :assignment_id
        AND member_id = :member_id
        LIMIT 1
    ");

    $stmt->execute([
        ':assignment_id' => $assignmentId,
        ':member_id' => $member['id']
    ]);

    $existing = $stmt->fetch();

    $fullName = trim(
        $member['first_name'] . ' ' .
        ($member['middle_name'] ?? '') . ' ' .
        $member['last_name']
    );

    if ($existing) {
        attendanceResponse(
            false,
            $fullName . ' has already been recorded for this Mass.',
            409
        );
    }

    // Save attendance.
    $stmt = $pdo->prepare("
        INSERT INTO attendance (
            assignment_id,
            member_id,
            attendance_status,
            check_in_time,
            attendance_method,
            recorded_by
        ) VALUES (
            :assignment_id,
            :member_id,
            'Present',
            NOW(),
            'QR Code',
            :recorded_by
        )
    ");

    $stmt->execute([
        ':assignment_id' => $assignmentId,
        ':member_id' => $member['id'],
        ':recorded_by' => $_SESSION['admin_id'] ?? null
    ]);

    attendanceResponse(
        true,
        'Attendance recorded successfully for ' . $fullName . '.'
    );

} catch (PDOException $e) {

    error_log('Attendance recording error: ' . $e->getMessage());

    // Handles a duplicate insert if two scans happen almost simultaneously.
    if ($e->getCode() === '23000') {
        attendanceResponse(
            false,
            'Attendance has already been recorded for this member.',
            409
        );
    }

    attendanceResponse(
        false,
        'A database error occurred while recording attendance.',
        500
    );
}
