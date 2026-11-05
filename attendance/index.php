<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| ATTENDANCE HISTORY
| SUPABASE POSTGRESQL
| St. Francis Xavier Cathedral
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Manila');

$pageTitle = 'Attendance History';
$activePage = 'attendance';

$massTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

$filterDate = trim((string)($_GET['date'] ?? ''));
$filterTime = trim((string)($_GET['mass_time'] ?? ''));

$records = [];
$pageError = '';

/*
|--------------------------------------------------------------------------
| VALIDATE FILTERS
|--------------------------------------------------------------------------
*/

if ($filterDate !== '') {
    $dateObject = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $filterDate,
        new DateTimeZone('Asia/Manila')
    );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $filterDate
    ) {
        $filterDate = '';
        $pageError = 'The selected date is invalid.';
    }
}

if (!in_array($filterTime, $massTimes, true)) {
    $filterTime = '';
}

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pageError = 'Database connection is unavailable.';
    error_log('Attendance history: PDO connection unavailable.');
} else {

    try {

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        /*
        |--------------------------------------------------------------------------
        | GET ATTENDANCE RECORDS
        |--------------------------------------------------------------------------
        |
        | Supabase PostgreSQL tables:
        | public.mass_attendance
        | public.members
        |
        | Member name is stored in members.full_name.
        |
        */

        $sql = "
            SELECT
                ma.id,
                ma.member_id,
                ma.mass_date,
                ma.mass_time,
                ma.attendance_status,
                ma.check_in_time,
                ma.attendance_method,
                m.member_code,
                m.full_name
            FROM public.mass_attendance AS ma
            INNER JOIN public.members AS m
                ON m.id = ma.member_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($filterDate !== '') {
            $sql .= " AND ma.mass_date = CAST(:mass_date AS DATE)";
            $params[':mass_date'] = $filterDate;
        }

        if ($filterTime !== '') {
            $sql .= " AND ma.mass_time = :mass_time";
            $params[':mass_time'] = $filterTime;
        }

        /*
        |--------------------------------------------------------------------------
        | POSTGRESQL MASS TIME SORTING
        |--------------------------------------------------------------------------
        */

        $sql .= "
            ORDER BY
                ma.mass_date DESC,
                CASE ma.mass_time
                    WHEN '6:00 AM' THEN 1
                    WHEN '8:00 AM' THEN 2
                    WHEN '10:00 AM' THEN 3
                    WHEN '5:00 PM' THEN 4
                    ELSE 5
                END ASC,
                ma.check_in_time DESC NULLS LAST,
                ma.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $records = $stmt->fetchAll();

    } catch (Throwable $e) {

        error_log(
            'Attendance history database error: ' . $e->getMessage()
        );

        $records = [];
        $pageError = 'Unable to load attendance records. Please check the database configuration and table structure.';
    }
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function attendanceEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function attendanceFormatDate($date): string
{
    if (empty($date)) {
        return '—';
    }

    try {
        return (new DateTimeImmutable($date))
            ->setTimezone(new DateTimeZone('Asia/Manila'))
            ->format('M d, Y');
    } catch (Throwable $e) {
        return attendanceEscape($date);
    }
}

function attendanceFormatTime($time): string
{
    if (empty($time)) {
        return '—';
    }

    try {
        return (new DateTimeImmutable($time))
            ->setTimezone(new DateTimeZone('Asia/Manila'))
            ->format('h:i:s A');
    } catch (Throwable $e) {
        return attendanceEscape($time);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.attendance-history-page {
    width: 100%;
    max-width: 100%;
    padding: 20px;
    box-sizing: border-box;
}

.attendance-history-page .page-heading {
    margin-bottom: 22px;
}

.attendance-history-page .page-heading h1 {
    margin-bottom: 6px;
    font-size: 26px;
    font-weight: 700;
}

.attendance-history-page .page-heading p {
    color: #777;
    margin: 0;
}

.attendance-history-page .card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 3px 14px rgba(0, 0, 0, .06);
    min-width: 0;
}

.attendance-history-page .form-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    align-items: end;
}

.attendance-history-page .form-group {
    min-width: 0;
}

.attendance-history-page .form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 7px;
}

.attendance-history-page .form-group input,
.attendance-history-page .form-group select {
    width: 100%;
    min-height: 42px;
    padding: 9px 12px;
    border: 1px solid #d7dce2;
    border-radius: 7px;
    background: #fff;
    box-sizing: border-box