
<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, bool $success, string $message, array $extra = []): void
{
    http_response_code($status);

    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Invalid request method.');
}

try {
    requireValidCSRFToken();
} catch (Throwable $e) {
    respond(403, false, 'Security validation failed. Refresh the page and try again.');
}

$qrToken = strtolower(trim($_POST['qr_token'] ?? ''));
$massDate = trim($_POST['mass_date'] ?? '');
$massTime = trim($_POST['mass_time'] ?? '');

$allowedTimes = ['6:00 AM', '8:00 AM', '10:00 AM', '5:00 PM'];

if (!preg_match('/^[a-f0-9]{64}$/', $qrToken)) {
    respond(400, false, 'Invalid QR code.');
}

$dateObject = DateTime::createFromFormat('!Y-m-d', $massDate);

if (!$dateObject || $dateObject->format('Y-m-d') !== $massDate) {
    respond(400, false, 'Invalid Mass date.');
}

if (!in_array($massTime, $allowedTimes, true)) {
    respond(400, false, 'Invalid Mass time.');
}

$adminId = $_SESSION['admin_id'] ?? null;

if (!$adminId) {
    respond(401, false, 'Your admin session has expired. Please log in again.');
}

try {

    $memberStmt = $pdo->prepare("
        SELECT
            id,
            member_code,
            first_name,
            middle_name,
            last_name,
            member_status
        FROM members
        WHERE qr_token = ?
        LIMIT 1
    ");

    $memberStmt->execute([$qrToken]);
    $member = $memberStmt->fetch();

    if (!$member) {
        respond(404, false, 'QR code not recognized. Member is not registered.');
    }

    if ($member['member_status'] !== 'Active') {
        respond(403, false, 'This member is not currently active.');
    }

    $fullName = trim(
        $member['first_name'] . ' ' .
        ($member['middle_name'] ? $member['middle_name'] . ' ' : '') .
        $member['last_name']
    );

    $insertStmt = $pdo->prepare("
        INSERT INTO mass_attendance (
            member_id,
            mass_date,
            mass_time,
            attendance_status,
            check_in_time,
            attendance_method,
            recorded_by
        ) VALUES (
            ?, ?, ?, 'Present', NOW(), 'QR Code', ?
        )
    ");

    $insertStmt->execute([
        $member['id'],
        $massDate,
        $massTime,
        $adminId
    ]);

    respond(200, true, 'Attendance successfully recorded!', [
        'member_name' => $fullName,
        'member_code' => $member['member_code'],
        'mass_date' => $massDate,
        'mass_time' => $massTime,
        'attendance_status' => 'Present',
        'check_in_time' => date('h:i:s A')
    ]);

} catch (PDOException $e) {

    if ($e->getCode() === '23000') {
        respond(409, false, 'This member has already been marked present for this Mass.');
    }

    error_log('Mass attendance error: ' . $e->getMessage());

    respond(500, false, 'A database error occurred while recording attendance.');
}
