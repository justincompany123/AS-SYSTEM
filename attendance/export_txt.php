
<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| EXPORT ATTENDANCE HISTORY TO TEXT FILE
| SUPABASE POSTGRESQL VERSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Manila');

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function txtClean($value): string
{
    return trim(
        preg_replace('/[\r\n\t]+/', ' ', (string)($value ?? ''))
    );
}

function txtCheckInTime($value): string
{
    if (empty($value)) {
        return '-';
    }

    try {

        $hasTimezone = preg_match(
            '/(Z|[+-][0-9]{2}:?[0-9]{2})$/i',
            trim($value)
        );

        $timezone = $hasTimezone
            ? new DateTimeZone('UTC')
            : new DateTimeZone('Asia/Manila');

        $date = new DateTimeImmutable($value, $timezone);

        return $date
            ->setTimezone(new DateTimeZone('Asia/Manila'))
            ->format('h:i A');

    } catch (Throwable $e) {
        return '-';
    }
}

// =====================================================
// RECEIVE FILTERS
// =====================================================

$today = date('Y-m-d');

$selectedDate = $_GET['date'] ?? $today;
$selectedMass = $_GET['mass_time'] ?? '';
$selectedStatus = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

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

// Validate date

$dateObject = DateTime::createFromFormat(
    '!Y-m-d',
    $selectedDate
);

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $selectedDate
) {
    $selectedDate = $today;
}

// Validate Mass time

if (
    $selectedMass !== '' &&
    !in_array($selectedMass, $allowedMassTimes, true)
) {
    $selectedMass = '';
}

// Validate status

if (
    $selectedStatus !== '' &&
    !in_array($selectedStatus, $allowedStatuses, true)
) {
    $selectedStatus = '';
}

// =====================================================
// FETCH ATTENDANCE RECORDS
// =====================================================

try {

    $sql = "
        SELECT
            ma.id,
            ma.mass_date,
            ma.mass_time,
            ma.attendance_status,
            ma.check_in_time,
            ma.attendance_method,
            m.member_code,
            m.full_name

        FROM public.mass_attendance ma

        INNER JOIN public.members m
            ON m.id = ma.member_id

        WHERE ma.mass_date = :mass_date
    ";

    $params = [
        ':mass_date' => $selectedDate
    ];

    // Mass schedule filter

    if ($selectedMass !== '') {

        $sql .= "
            AND ma.mass_time = :mass_time
        ";

        $params[':mass_time'] = $selectedMass;
    }

    // Attendance status filter

    if ($selectedStatus !== '') {

        $sql .= "
            AND ma.attendance_status = :status
        ";

        $params[':status'] = $selectedStatus;
    }

    // Member name or code search

    if ($search !== '') {

        $sql .= "
            AND (
                m.full_name ILIKE :search_name
                OR m.member_code ILIKE :search_code
            )
        ";

        $params[':search_name'] = '%' . $search . '%';
        $params[':search_code'] = '%' . $search . '%';
    }

    // Sort by Mass schedule and member name

    $sql .= "
        ORDER BY
            CASE ma.mass_time
                WHEN '6:00 AM' THEN 1
                WHEN '8:00 AM' THEN 2
                WHEN '10:00 AM' THEN 3
                WHEN '5:00 PM' THEN 4
                ELSE 5
            END,
            m.full_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        'Attendance TXT Export Error: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('Unable to generate attendance text report.');

}

// =====================================================
// CALCULATE TOTALS
// =====================================================

$totals = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0,
    'Excused' => 0
];

foreach ($records as $record) {

    $status = $record['attendance_status'];

    if (isset($totals[$status])) {
        $totals[$status]++;
    }
}

// =====================================================
// GENERATE TEXT REPORT
// =====================================================

$formattedDate = date(
    'F d, Y',
    strtotime($selectedDate)
);

$report = [];

$report[] = "============================================================";
$report[] = "             ST. FRANCIS XAVIER CATHEDRAL";
$report[] = "                MINISTRY OF ALTAR SERVERS";
$report[] = "                 DAILY ATTENDANCE REPORT";
$report[] = "============================================================";
$report[] = "";

$report[] = "Attendance Date : " . $formattedDate;
$report[] = "Mass Schedule   : " . ($selectedMass ?: 'All Mass Schedules');
$report[] = "Status Filter   : " . ($selectedStatus ?: 'All Statuses');
$report[] = "Member Search   : " . ($search ?: 'All Members');

$report[] = "";
$report[] = "------------------------------------------------------------";
$report[] = "                    ATTENDANCE SUMMARY";
$report[] = "------------------------------------------------------------";

$report[] = "Present : " . $totals['Present'];
$report[] = "Absent  : " . $totals['Absent'];
$report[] = "Late    : " . $totals['Late'];
$report[] = "Excused : " . $totals['Excused'];

$report[] = "";
$report[] = "------------------------------------------------------------";
$report[] = "                 MEMBER ATTENDANCE RECORDS";
$report[] = "------------------------------------------------------------";
$report[] = "";

if (empty($records)) {

    $report[] = "No attendance records found for the selected date.";

} else {

    foreach ($records as $index => $record) {

        $report[] = "RECORD NO. " . ($index + 1);
        $report[] = "Member Name    : " . txtClean($record['full_name']);
        $report[] = "Member Code    : " . txtClean($record['member_code']);
        $report[] = "Mass Date      : " . $formattedDate;
        $report[] = "Mass Schedule  : " . txtClean($record['mass_time']);
        $report[] = "Check-in Time  : " . txtCheckInTime($record['check_in_time']);
        $report[] = "Status         : " . txtClean($record['attendance_status']);
        $report[] = "Method         : " . txtClean($record['attendance_method'] ?: '-');

        $report[] = "------------------------------------------------------------";
    }
}

$report[] = "";
$report[] = "Total Records: " . count($records);
$report[] = "";
$report[] = "Prepared by: Altar Servers Coordinator";
$report[] = "";
$report[] = "                    AMARE ET SERVIRE";
$report[] = "";
$report[] = "St. Francis Xavier Cathedral";
$report[] = "Ministry of Altar Servers";
$report[] = "";
$report[] = "Generated on: " . date('F d, Y h:i A');
$report[] = "";
$report[] = "============================================================";

// =====================================================
// DOWNLOAD TXT FILE
// =====================================================

$filename = 'Altar_Servers_Attendance_' . $selectedDate . '.txt';

$content = implode("\r\n", $report);

header('Content-Type: text/plain; charset=UTF-8');

header(
    'Content-Disposition: attachment; filename="' .
    $filename . '"'
);

header('Content-Length: ' . strlen($content));

header('Cache-Control: no-store, no-cache, must-revalidate');

echo "\xEF\xBB\xBF";
echo $content;

exit;
