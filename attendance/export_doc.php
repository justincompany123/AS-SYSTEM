
<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| EXPORT ATTENDANCE HISTORY TO WORD DOCUMENT
| SUPABASE POSTGRESQL VERSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

// =====================================================
// TIMEZONE
// =====================================================

date_default_timezone_set('Asia/Manila');

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function docEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function docCheckInTime($value): string
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

    // Mass filter

    if ($selectedMass !== '') {

        $sql .= "
            AND ma.mass_time = :mass_time
        ";

        $params[':mass_time'] = $selectedMass;
    }

    // Status filter

    if ($selectedStatus !== '') {

        $sql .= "
            AND ma.attendance_status = :status
        ";

        $params[':status'] = $selectedStatus;
    }

    // Member search

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

    // Sort records

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
        'Attendance DOC Export Error: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('Unable to generate attendance report. Please check the database.');
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
// FORMAT DATE
// =====================================================

$formattedDate = date(
    'F d, Y',
    strtotime($selectedDate)
);

// =====================================================
// GENERATE WORD-COMPATIBLE HTML
// =====================================================

$filename = 'Altar_Servers_Attendance_' .
    $selectedDate . '.doc';

// Remove whitespace from output before headers
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/msword; charset=utf-8');
header(
    'Content-Disposition: attachment; filename="' .
    $filename . '"'
);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

echo '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>

body {
    font-family: Arial, sans-serif;
    color: #26364a;
    font-size: 11pt;
}

h1 {
    text-align: center;
    color: #18375d;
    font-size: 19pt;
    margin-bottom: 5px;
}

h2 {
    text-align: center;
    color: #18375d;
    font-size: 14pt;
    margin-top: 5px;
}

p {
    text-align: center;
    margin: 5px 0;
}

.report-info {
    margin-top: 25px;
    margin-bottom: 15px;
}

.summary {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0 25px;
}

.summary td {
    border: 1px solid #cbd5e1;
    padding: 10px;
    text-align: center;
}

.summary strong {
    font-size: 15pt;
}

table.attendance {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

table.attendance th {
    background-color: #18375d;
    color: white;
    padding: 9px;
    border: 1px solid #18375d;
    text-align: left;
}

table.attendance td {
    padding: 8px;
    border: 1px solid #cbd5e1;
}

.footer {
    margin-top: 35px;
    text-align: center;
    font-size: 9pt;
    color: #718096;
}

.signature {
    margin-top: 55px;
    width: 100%;
}

.signature td {
    width: 50%;
    text-align: center;
    padding: 10px;
}

</style>
</head>

<body>

<h1>ST. FRANCIS XAVIER CATHEDRAL</h1>

<h2>MINISTRY OF ALTAR SERVERS</h2>

<p><strong>DAILY ATTENDANCE REPORT</strong></p>

<div class="report-info">

<p style="text-align:left;">
<strong>Attendance Date:</strong> ' .
docEscape($formattedDate) . '
</p>

<p style="text-align:left;">
<strong>Mass Schedule:</strong> ' .
docEscape($selectedMass ?: 'All Mass Schedules') . '
</p>

<p style="text-align:left;">
<strong>Status Filter:</strong> ' .
docEscape($selectedStatus ?: 'All Statuses') . '
</p>

<p style="text-align:left;">
<strong>Search:</strong> ' .
docEscape($search ?: 'All Members') . '
</p>

</div>

<h3>ATTENDANCE SUMMARY</h3>

<table class="summary">

<tr>
<td>
<strong>' . $totals['Present'] . '</strong><br>
Present
</td>

<td>
<strong>' . $totals['Absent'] . '</strong><br>
Absent
</td>

<td>
<strong>' . $totals['Late'] . '</strong><br>
Late
</td>

<td>
<strong>' . $totals['Excused'] . '</strong><br>
Excused
</td>
</tr>

</table>

<h3>MEMBER ATTENDANCE RECORDS</h3>

<table class="attendance">

<thead>
<tr>
<th>No.</th>
<th>Member Name</th>
<th>Member Code</th>
<th>Mass Schedule</th>
<th>Check-in Time</th>
<th>Status</th>
<th>Method</th>
</tr>
</thead>

<tbody>';

if (empty($records)) {

    echo '
    <tr>
        <td colspan="7" style="text-align:center;">
            No attendance records found for this date.
        </td>
    </tr>';

} else {

    foreach ($records as $index => $record) {

        echo '
        <tr>

            <td>' . ($index + 1) . '</td>

            <td>' . docEscape($record['full_name']) . '</td>

            <td>' . docEscape($record['member_code']) . '</td>

            <td>' . docEscape($record['mass_time']) . '</td>

            <td>' . docEscape(
                docCheckInTime($record['check_in_time'])
            ) . '</td>

            <td>' . docEscape(
                $record['attendance_status']
            ) . '</td>

            <td>' . docEscape(
                $record['attendance_method'] ?: '-'
            ) . '</td>

        </tr>';
    }
}

echo '
</tbody>
</table>

<table class="signature">

<tr>
<td>
____________________________<br>
<strong>Prepared By</strong><br>
Altar Servers Coordinator
</td>

<td>
____________________________<br>
<strong>Noted By</strong><br>
Parish Representative
</td>
</tr>

</table>

<div class="footer">

<p>Amare et Servire</p>

<p>
St. Francis Xavier Cathedral<br>
Ministry of Altar Servers
</p>

<p>Generated on ' .
docEscape(date('F d, Y h:i A')) . '</p>

</div>

</body>
</html>';

exit;
