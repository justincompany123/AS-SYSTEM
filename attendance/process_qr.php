<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| ATTENDANCE PROCESSOR
| SUPABASE POSTGRESQL VERSION
| St. Francis Xavier Cathedral
|--------------------------------------------------------------------------
*/

if (ob_get_level() === 0) {
    ob_start();
}

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

function sendAttendanceResponse(
    bool $success,
    string $message,
    array $extra = [],
    int $httpStatus = 200
): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($httpStatus);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');

    echo json_encode(
        array_merge([
            'success' => $success,
            'message' => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| REQUEST VALIDATION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendAttendanceResponse(
        false,
        'Invalid request method.',
        [],
        405
    );
}

/*
|--------------------------------------------------------------------------
| CSRF VALIDATION
|--------------------------------------------------------------------------
*/

$sessionToken = $_SESSION['csrf_token'] ?? '';
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($sessionToken) ||
    !is_string($submittedToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    sendAttendanceResponse(
        false,
        'Your session has expired. Refresh the page and try again.',
        [],
        403
    );
}

/*
|--------------------------------------------------------------------------
| DATABASE VALIDATION
|--------------------------------------------------------------------------
*/

if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log('Attendance Error: PDO connection unavailable.');

    sendAttendanceResponse(
        false,
        'Database connection is unavailable.',
        [],
        500
    );
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Attendance PDO Setup Error: ' . $e->getMessage());

    sendAttendanceResponse(
        false,
        'Unable to configure the database connection.',
        [],
        500
    );
}

/*
|--------------------------------------------------------------------------
| ALLOWED VALUES
|--------------------------------------------------------------------------
*/

$allowedActions = [
    'search',
    'record_qr',
    'record_manual'
];

$allowedMassTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

$allowedAttendanceStatuses = [
    'Present',
    'Excused'
];

/*
|--------------------------------------------------------------------------
| GET ACTION
|--------------------------------------------------------------------------
*/

$action = trim((string)($_POST['action'] ?? ''));

if (!in_array($action, $allowedActions, true)) {
    sendAttendanceResponse(
        false,
        'Invalid attendance action.',
        [],
        400
    );
}

/*
|--------------------------------------------------------------------------
| SEARCH MEMBERS
|--------------------------------------------------------------------------
*/

if ($action === 'search') {

    $search = trim((string)($_POST['search'] ?? ''));

    // Use mb_strlen when available; otherwise use strlen.
    $searchLength = function_exists('mb_strlen')
        ? mb_strlen($search, 'UTF-8')
        : strlen($search);

    if ($searchLength < 2) {
        sendAttendanceResponse(
            false,
            'Please enter at least two characters.'
        );
    }

    try {
        $searchValue = '%' . $search . '%';

        $stmt = $pdo->prepare("
            SELECT
                id,
                member_code,
                full_name
            FROM public.members
            WHERE LOWER(TRIM(status::text)) = 'active'
              AND (
                    full_name ILIKE :search_name
                    OR member_code ILIKE :search_code
              )
            ORDER BY full_name ASC
            LIMIT 20
        ");

        $stmt->execute([
            ':search_name' => $searchValue,
            ':search_code' => $searchValue
        ]);

        $members = $stmt->fetchAll();

        sendAttendanceResponse(
            true,
            count($members) > 0
                ? 'Members found.'
                : 'No active members found.',
            [
                'members' => $members
            ]
        );

    } catch (Throwable $e) {
        error_log('Member Search Error: ' . $e->getMessage());

        sendAttendanceResponse(
            false,
            'Unable to search members. Check the members table columns and Render logs.',
            [],
            500
        );
    }
}

/*
|--------------------------------------------------------------------------
| VALIDATE MASS DATE
|--------------------------------------------------------------------------
*/

$massDate = trim((string)($_POST['mass_date'] ?? ''));

$dateObject = DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $massDate,
    new DateTimeZone('Asia/Manila')
);

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $massDate
) {
    sendAttendanceResponse(
        false,
        'Invalid Mass date. Please select a valid date.'
    );
}

if ($massDate > date('Y-m-d')) {
    sendAttendanceResponse(
        false,
        'You cannot record attendance for a future date.'
    );
}

/*
|--------------------------------------------------------------------------
| VALIDATE MASS TIME
|--------------------------------------------------------------------------
*/

$massTime = trim((string)($_POST['mass_time'] ?? ''));

if (!in_array($massTime, $allowedMassTimes, true)) {
    sendAttendanceResponse(
        false,
        'Invalid Mass schedule.'
    );
}

/*
|--------------------------------------------------------------------------
| FIND MEMBER
|--------------------------------------------------------------------------
*/

try {

    $member = null;

    /*
    |--------------------------------------------------------------------------
    | QR CODE ATTENDANCE
    |--------------------------------------------------------------------------
    */

    if ($action === 'record_qr') {

        $qrToken = trim((string)($_POST['qr_token'] ?? ''));

        if (!preg_match('/^[a-f0-9]{64}$/i', $qrToken)) {
            sendAttendanceResponse(
                false,
                'Invalid QR code. The QR token is not valid.'
            );
        }

        $memberStmt = $pdo->prepare("
            SELECT
                id,
                member_code,
                full_name,
                status
            FROM public.members
            WHERE qr_token = :qr_token
            LIMIT 1
        ");

        $memberStmt->execute([
            ':qr_token' => $qrToken
        ]);

        $member = $memberStmt->fetch();
    }

    /*
    |--------------------------------------------------------------------------
    | MANUAL ATTENDANCE
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'record_manual') {

        $memberId = filter_var(
            $_POST['member_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$memberId || $memberId <= 0) {
            sendAttendanceResponse(
                false,
                'Invalid member ID.'
            );
        }

        $memberStmt = $pdo->prepare("
            SELECT
                id,
                member_code,
                full_name,
                status
            FROM public.members
            WHERE id = :member_id
            LIMIT 1
        ");

        $memberStmt->execute([
            ':member_id' => $memberId
        ]);

        $member = $memberStmt->fetch();
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK MEMBER EXISTENCE
    |--------------------------------------------------------------------------
    */

    if (!$member) {
        sendAttendanceResponse(
            false,
            $action === 'record_qr'
                ? 'No member is associated with this QR code.'
                : 'Member not found.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK MEMBER STATUS
    |--------------------------------------------------------------------------
    */

    $memberStatus = strtolower(
        trim((string)($member['status'] ?? ''))
    );

    if ($memberStatus !== 'active') {
        sendAttendanceResponse(
            false,
            'This member is not active and cannot be recorded.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE STATUS AND METHOD
    |--------------------------------------------------------------------------
    */

    if ($action === 'record_qr') {

        $attendanceStatus = 'Present';
        $attendanceMethod = 'QR Code';

    } else {

        $attendanceStatus = trim(
            (string)($_POST['attendance_status'] ?? 'Present')
        );

        if (!in_array(
            $attendanceStatus,
            $allowedAttendanceStatuses,
            true
        )) {
            sendAttendanceResponse(
                false,
                'Invalid attendance status.'
            );
        }

        $attendanceMethod = 'Manual';
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING ATTENDANCE
    |--------------------------------------------------------------------------
    */

    $existingStmt = $pdo->prepare("
        SELECT
            id,
            check_in_time,
            attendance_status,
            attendance_method
        FROM public.mass_attendance
        WHERE member_id = :member_id
          AND mass_date = CAST(:mass_date AS DATE)
          AND mass_time = :mass_time
        LIMIT 1
    ");

    $existingStmt->execute([
        ':member_id' => $member['id'],
        ':mass_date' => $massDate,
        ':mass_time' => $massTime
    ]);

    $existing = $existingStmt->fetch();

    if ($existing) {

        $existingCheckIn = $existing['check_in_time'];

        if ($existingCheckIn !== null) {
            $existingCheckIn = (new DateTimeImmutable(
                (string)$existingCheckIn
            ))
                ->setTimezone(new DateTimeZone('Asia/Manila'))
                ->format('h:i:s A');
        }

        sendAttendanceResponse(
            false,
            'Attendance has already been recorded for this member and Mass schedule.',
            [
                'member_name' => $member['full_name'],
                'member_code' => $member['member_code'],
                'mass_date' => $massDate,
                'mass_time' => $massTime,
                'attendance_status' => $existing['attendance_status'],
                'check_in_time' => $existingCheckIn,
                'attendance_method' => $existing['attendance_method'],
                'already_recorded' => true
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE ATTENDANCE
    |--------------------------------------------------------------------------
    |
    | This insert assumes recorded_by accepts NULL.
    | A unique constraint on member_id, mass_date, mass_time
    | is recommended to prevent duplicate records.
    |--------------------------------------------------------------------------
    */

    $insertStmt = $pdo->prepare("
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
            CAST(:mass_date AS DATE),
            :mass_time,
            :attendance_status,
            CURRENT_TIMESTAMP,
            :attendance_method,
            NULL
        )
        RETURNING
            id,
            check_in_time,
            attendance_status,
            attendance_method
    ");

    $insertStmt->execute([
        ':member_id' => $member['id'],
        ':mass_date' => $massDate,
        ':mass_time' => $massTime,
        ':attendance_status' => $attendanceStatus,
        ':attendance_method' => $attendanceMethod
    ]);

    $attendance = $insertStmt->fetch();

    if (!$attendance) {
        sendAttendanceResponse(
            false,
            'Attendance could not be saved.',
            [],
            500
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FORMAT CHECK-IN TIME
    |--------------------------------------------------------------------------
    */

    $checkInTime = $attendance['check_in_time'];

    if ($checkInTime !== null) {
        $checkInTime = (new DateTimeImmutable(
            (string)$checkInTime
        ))
            ->setTimezone(new DateTimeZone('Asia/Manila'))
            ->format('h:i:s A');
    }

    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    sendAttendanceResponse(
        true,
        'Attendance recorded successfully.',
        [
            'attendance_id' => $attendance['id'],
            'member_id' => $member['id'],
            'member_name' => $member['full_name'],
            'member_code' => $member['member_code'],
            'mass_date' => $massDate,
            'mass_time' => $massTime,
            'attendance_status' => $attendance['attendance_status'],
            'check_in_time' => $checkInTime,
            'attendance_method' => $attendance['attendance_method'],
            'already_recorded' => false
        ]
    );

} catch (Throwable $e) {

    error_log(
        'Attendance Processing Error: ' . $e->getMessage()
    );

    sendAttendanceResponse(
        false,
        'A database error occurred while processing attendance. Check the Render logs.',
        [],
        500
    );
}
