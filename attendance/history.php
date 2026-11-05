<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| ATTENDANCE HISTORY
| SUPABASE POSTGRESQL VERSION
| St. Francis Xavier Cathedral
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Manila');

/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle = 'Attendance History';
$activePage = 'attendance';

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function historyEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function historyCheckInTime($value): string
{
    if (empty($value)) {
        return '—';
    }

    try {
        $date = new DateTimeImmutable(
            (string)$value,
            new DateTimeZone('UTC')
        );

        return $date
            ->setTimezone(new DateTimeZone('Asia/Manila'))
            ->format('h:i A');

    } catch (Throwable $e) {
        return '—';
    }
}

function historyFormatDate($value): string
{
    if (empty($value)) {
        return '—';
    }

    try {
        return (new DateTimeImmutable((string)$value))
            ->format('M d, Y');

    } catch (Throwable $e) {
        return '—';
    }
}

function historyStatusClass($status): string
{
    switch (strtolower(trim((string)$status))) {
        case 'present':
            return 'status-present';

        case 'absent':
            return 'status-absent';

        case 'late':
            return 'status-late';

        case 'excused':
            return 'status-excused';

        default:
            return 'status-default';
    }
}

/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$selectedDate = trim((string)($_GET['date'] ?? ''));
$selectedMass = trim((string)($_GET['mass_time'] ?? ''));
$selectedStatus = trim((string)($_GET['status'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));

$allowedMassTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

$allowedStatuses = [
    'Present',
    'Absent',
    'Late',
    'Excused'
];

/*
|--------------------------------------------------------------------------
| VALIDATE DATE FILTER
|--------------------------------------------------------------------------
*/

if ($selectedDate !== '') {

    $dateObject = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $selectedDate,
        new DateTimeZone('Asia/Manila')
    );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $selectedDate
    ) {
        $selectedDate = '';
    }
}

/*
|--------------------------------------------------------------------------
| VALIDATE MASS TIME
|--------------------------------------------------------------------------
*/

if (
    $selectedMass !== '' &&
    !in_array($selectedMass, $allowedMassTimes, true)
) {
    $selectedMass = '';
}

/*
|--------------------------------------------------------------------------
| VALIDATE ATTENDANCE STATUS
|--------------------------------------------------------------------------
*/

if (
    $selectedStatus !== '' &&
    !in_array($selectedStatus, $allowedStatuses, true)
) {
    $selectedStatus = '';
}

/*
|--------------------------------------------------------------------------
| INITIALIZE DATA
|--------------------------------------------------------------------------
*/

$records = [];

$totals = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0,
    'Excused' => 0
];

$totalRecords = 0;
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION CHECK
|--------------------------------------------------------------------------
*/

if (!isset($pdo) || !($pdo instanceof PDO)) {

    $errorMessage = 'Database connection is unavailable.';

    error_log('Attendance History: PDO connection unavailable.');

} else {

    try {

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        /*
        |--------------------------------------------------------------------------
        | ATTENDANCE HISTORY QUERY
        |--------------------------------------------------------------------------
        |
        | Supabase tables:
