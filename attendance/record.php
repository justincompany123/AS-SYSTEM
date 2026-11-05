<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| RECORD QR ATTENDANCE
| SUPABASE POSTGRESQL VERSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

// =====================================================
// JSON RESPONSE
// =====================================================

function attendanceResponse(
    bool $success,
    string $message,
    int $status = 200,
    array $extra = []
): void {

    http_response_code($status);

    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));

    exit;
}

// =====================================================
// VALIDATE REQUEST METHOD
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    attendanceResponse(false, 'Invalid request method.', 405);
}

// =====================================================
// VALIDATE CSRF TOKEN
// =====================================================

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    attendanceResponse(
        false,
        'Your session token is invalid. Refresh the page and try again.',
        403
    );
}

// =====================================================
// RECEIVE FORM DATA
// =====================================================

$qrToken = strtolower(trim($_POST['qr_token'] ?? ''));
$massDate = trim($_POST['mass_date'] ?? '');
$massTime = trim($_POST['mass_time'] ?? '');

$allowedTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

// =====================================================
// VALIDATE QR TOKEN
// =====================================================

if (!preg_match('/^[a-f0-9]{64}$/', $qrToken)) {
    attendanceResponse(false, 'Invalid QR code token.', 400);
}

// =====================================================
// VALIDATE MASS DATE
// =====================================================

$dateObject = DateTime::createFromFormat('!Y-m-d', $massDate);

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $massDate
) {
    attendanceResponse(false, 'Invalid Mass date.', 400);
}

// =====================================================
// VALIDATE MASS TIME
// =====================================================

if (!in_array($massTime, $allowedTimes, true)) {
    attendanceResponse(false, 'Invalid Mass schedule.', 400);
}

// =====================================================
// VALIDATE ADMIN SESSION
// =====================================================

$adminId = $_SESSION['admin_id'] ?? null;

if (!$adminId) {
    attendanceResponse(
        false,
        'Admin session expired. Please log in again.',
        401
    );
}

// =====================================================
// DATABASE PROCESSING
// =====================================================

try {

    // 1. FIND MEMBER USING QR TOKEN

    $stmt = $pdo->prepare("
        SELECT
            id,
            member_code,
            full_name,
            status
        FROM public.members
        WHERE qr_token = :qr_token
        LIMIT 1
    ");

    $stmt->execute([
        ':qr_token' => $qrToken
    ]);

    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        attendanceResponse(
            false,
            'QR code is not registered in the system.',
            404
        );
    }

    // 2. CHECK MEMBER STATUS

    if ($member['status'] !== 'Active') {
        attendanceResponse(
            false,
            'This member is not currently active.',
            403
        );
    }

    // 3. INSERT ATTENDANCE

    $stmt = $pdo->prepare("
        INSERT INTO public.mass_attendance (
            member_id,
            mass_date,
            mass_time,
            attendance_status,
            check_in_time,
            attendance_method,
            recorded_by
        )
        VALUES (
            :member_id,
            :mass_date,
            :mass_time,
            'Present',
            CURRENT_TIMESTAMP,
            'QR Code',
            :recorded_by
        )
        ON CONFLICT (
            member_id,
            mass_date,
            mass_time
        )
        DO NOTHING
        RETURNING id, check_in_time
    ");

    $stmt->execute([
        ':member_id' => $member['id'],
        ':mass_date' => $massDate,
        ':mass_time' => $massTime,
        ':recorded_by' => $adminId
    ]);

    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. CHECK DUPLICATE ATTENDANCE

    if (!$attendance) {
        attendanceResponse(
            false,
            $member['full_name'] .
            ' has already been recorded for this Mass.',
            409
        );
    }

    // 5. FORMAT CHECK-IN TIME
    // Assumes the database timestamp represents UTC.

    $checkIn = new DateTimeImmutable(
        $attendance['check_in_time'],
        new DateTimeZone('UTC')
    );

    $checkIn = $checkIn->setTimezone(
        new DateTimeZone('Asia/Manila')
    );

    // 6. RETURN SUCCESS RESPONSE

    attendanceResponse(
        true,
        'Attendance recorded successfully for ' .
        $member['full_name'] . '.',
        200,
        [
            'member_name' => $member['full_name'],
            'member_code' => $member['member_code'],
            'mass_date' => $massDate,
            'mass_time' => $massTime,
            'attendance_status' => 'Present',
            'check_in_time' => $checkIn->format('h:i:s A')
        ]
    );

} catch (PDOException $e) {

    error_log(
        'QR Attendance Error: ' . $e->getMessage()
    );

    attendanceResponse(
        false,
        'A database error occurred while recording attendance.',
        500
    );

} catch (Throwable $e) {

    error_log(
        'QR Attendance Processing Error: ' . $e->getMessage()
    );

    attendanceResponse(
        false,
        'An unexpected error occurred.',
        500
    );

}
