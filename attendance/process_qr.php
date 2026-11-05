<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| ATTENDANCE PROCESSING ENDPOINT
| SUPABASE POSTGRESQL VERSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json; charset=UTF-8');

function sendAttendanceResponse(
    bool $success,
    string $message,
    array $extra = [],
    int $httpCode = 200
): void {
    http_response_code($httpCode);

    echo json_encode(
        array_merge([
            'success' => $success,
            'message' => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// =====================================================
// REQUEST VALIDATION
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendAttendanceResponse(
        false,
        'Invalid request method.',
        [],
        405
    );
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verify CSRF token.
$sessionToken = $_SESSION['csrf_token'] ?? '';
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    empty($sessionToken) ||
    empty($submittedToken) ||
    !hash_equals($sessionToken, $submittedToken)
) {
    sendAttendanceResponse(
        false,
        'Your session token is invalid. Refresh the page and try again.',
        [],
        403
    );
}

// =====================================================
// INPUT VALUES
// =====================================================

$action = trim($_POST['action'] ?? '');

$massDate = trim($_POST['mass_date'] ?? '');

$massTime = trim($_POST['mass_time'] ?? '');

$allowedMassTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

if (!in_array($action, [
    'search',
    'record_manual',
    'record_qr'
], true)) {
    sendAttendanceResponse(
        false,
        'Invalid attendance action.',
        [],
        400
    );
}

if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $massDate) ||
    !checkdate(
        (int) substr($massDate, 5, 2),
        (int) substr($massDate, 8, 2),
        (int) substr($massDate, 0, 4)
    )
) {
    sendAttendanceResponse(
        false,
        'Please select a valid attendance date.',
        [],
        400
    );
}

if (!in_array($massTime, $allowedMassTimes, true)) {
    sendAttendanceResponse(
        false,
        'Please select a valid Mass schedule.',
        [],
        400
    );
}

// =====================================================
// SEARCH ACTIVE MEMBERS
// =====================================================

if ($action === 'search') {

    $search = trim($_POST['search'] ?? '');

    if (mb_strlen($search) < 2) {
        sendAttendanceResponse(
            true,
            'Enter at least two characters.',
            [
                'members' => []
            ]
        );
    }

    try {

        $sql = "
            SELECT
                id,
                member_code,
                full_name

            FROM public.members

            WHERE status = 'Active'

            AND (
                full_name ILIKE :name
                OR member_code ILIKE :code
            )

            ORDER BY full_name ASC

            LIMIT 30
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':name' => '%' . $search . '%',
            ':code' => '%' . $search . '%'
        ]);

        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendAttendanceResponse(
            true,
            'Member search completed.',
            [
                'members' => $members
            ]
        );

    } catch (PDOException $e) {

        error_log(
            'Attendance Member Search Error: ' . $e->getMessage()
        );

        sendAttendanceResponse(
            false,
            'Unable to search members. Please check the database.',
            [],
            500
        );
    }
}

// =====================================================
// GET MEMBER INFORMATION
// =====================================================

$member = null;

try {

    if ($action === 'record_qr') {

        $qrToken = trim($_POST['qr_token'] ?? '');

        if (!preg_match('/^[a-f0-9]{64}$/i', $qrToken)) {
            sendAttendanceResponse(
                false,
                'Invalid QR code.',
                [],
                400
            );
        }

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

    } else {

        $memberId = filter_var(
            $_POST['member_id'] ?? '',
            FILTER_VALIDATE_INT
        );

        if (!$memberId || $memberId < 1) {
            sendAttendanceResponse(
                false,
                'Please select a valid member.',
                [],
                400
            );
        }

        $stmt = $pdo->prepare("
            SELECT
                id,
                member_code,
                full_name,
                status

            FROM public.members

            WHERE id = :member_id

            LIMIT 1
        ");

        $stmt->execute([
            ':member_id' => $memberId
        ]);
    }

    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        sendAttendanceResponse(
            false,
            'Member not found.',
            [],
            404
        );
    }

    if ($member['status'] !== 'Active') {
        sendAttendanceResponse(
            false,
            'This member is not currently active.',
            [],
            400
        );
    }

} catch (PDOException $e) {

    error_log(
        'Attendance Member Lookup Error: ' . $e->getMessage()
    );

    sendAttendanceResponse(
        false,
        'Unable to retrieve member information.',
        [],
        500
    );
}

// =====================================================
// DETERMINE ATTENDANCE STATUS AND METHOD
// =====================================================

if ($action === 'record_qr') {

    // Scanning a valid member QR code records Present.
    $attendanceStatus = 'Present';
    $attendanceMethod = 'QR Code';

} else {

    $attendanceStatus = trim(
        $_POST['attendance_status'] ?? ''
    );

    if (!in_array(
        $attendanceStatus,
        ['Present', 'Excused'],
        true
    )) {
        sendAttendanceResponse(
            false,
            'Only Present or Excused attendance is allowed.',
            [],
            400
        );
    }

    $attendanceMethod = 'Manual';
}

// =====================================================
// SAVE ATTENDANCE TO SUPABASE
// =====================================================

try {

    /*
     * The unique constraint on
     * (member_id, mass_date, mass_time)
     * prevents duplicate attendance records.
     *
     * If the same member is recorded again for the
     * same date and Mass schedule, the existing
     * record is updated.
     */

    $sql = "
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
            :attendance_status,
            CURRENT_TIMESTAMP,
            :attendance_method,
            NULL
        )

        ON CONFLICT (
            member_id,
            mass_date,
            mass_time
        )

        DO UPDATE SET

            attendance_status = EXCLUDED.attendance_status,

            check_in_time = CURRENT_TIMESTAMP,

            attendance_method = EXCLUDED.attendance_method

        RETURNING
            id,
            check_in_time,
            attendance_status,
            attendance_method
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':member_id' => $member['id'],
        ':mass_date' => $massDate,
        ':mass_time' => $massTime,
        ':attendance_status' => $attendanceStatus,
        ':attendance_method' => $attendanceMethod
    ]);

    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attendance) {
        sendAttendanceResponse(
            false,
            'Attendance could not be saved.',
            [],
            500
        );
    }

    // Convert database timestamp to Philippine time.
    $checkInTime = '-';

    if (!empty($attendance['check_in_time'])) {

        try {

            $timestamp = new DateTimeImmutable(
                $attendance['check_in_time']
            );

            $timestamp = $timestamp->setTimezone(
                new DateTimeZone('Asia/Manila')
            );

            $checkInTime = $timestamp->format('h:i A');

        } catch (Throwable $e) {

            $checkInTime = '-';

        }
    }

    // =================================================
    // SUCCESS RESPONSE
    // =================================================

    $message = $attendanceStatus === 'Present'
        ? 'Attendance successfully recorded as Present.'
        : 'Attendance successfully recorded as Excused.';

    sendAttendanceResponse(
        true,
        $message,
        [
            'attendance_id' => $attendance['id'],
            'member_id' => $member['id'],
            'member_name' => $member['full_name'],
            'member_code' => $member['member_code'],
            'mass_date' => $massDate,
            'mass_time' => $massTime,
            'attendance_status' => $attendanceStatus,
            'attendance_method' => $attendanceMethod,
            'check_in_time' => $checkInTime
        ]
    );

} catch (PDOException $e) {

    error_log(
        'Attendance Save Error: ' . $e->getMessage()
    );

    sendAttendanceResponse(
        false,
        'Unable to save attendance. Please check your database table and unique constraint.',
        [],
        500
    );
}
