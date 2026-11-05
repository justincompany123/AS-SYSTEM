<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| EXPORT PRESENT ATTENDANCE TO DOC
| SUPABASE POSTGRESQL
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

function docEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$massTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

$filterDate = trim($_GET['date'] ?? '');
$filterMass = trim($_GET['mass_time'] ?? '');

if ($filterDate !== '') {
    $dateObject = DateTime::createFromFormat('!Y-m-d', $filterDate);

    if (!$dateObject || $dateObject->format('Y-m-d') !== $filterDate) {
        http_response_code(400);
        exit('Invalid attendance date.');
    }
}

if (!in_array($filterMass, $massTimes, true)) {
    $filterMass = '';
}

try {

    $where = [
        "LOWER(TRIM(ma.attendance_status)) = 'present'"
    ];

    $params = [];

    if ($filterDate !== '') {
        $where[] = 'ma.mass_date = CAST(:filter_date AS DATE)';
        $params[':filter_date'] = $filterDate;
    }

    if ($filterMass !== '') {
        $where[] = 'ma.mass_time = :filter_mass';
        $params[':filter_mass'] = $filterMass;
    }

    $sql = "
        SELECT
            ma.id,
            ma.mass_date,
            ma.mass_time,
            ma.check_in_time,
            ma.attendance_status,
            ma.attendance_method,
            COALESCE(m.member_code, 'N/A') AS member_code,
            COALESCE(m.full_name, 'Unknown Member') AS full_name

        FROM public.mass_attendance ma

        LEFT JOIN public.members m
            ON m.id = ma.member_id

        WHERE " . implode(' AND ', $where) . "

        ORDER BY
            ma.mass_date ASC,
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

} catch (Throwable $e) {

    error_log('DOC export error: ' . $e->getMessage());

    http_response_code(500);
    exit('Unable to generate the attendance document. Please check the server logs.');
}

$filename = 'Attendance_Present_' .
    ($filterDate !== '' ? $filterDate : date('Y-m-d')) .
    '.doc';

header('Content-Type: application/msword; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$dateLabel = $filterDate !== ''
    ? date('F d, Y', strtotime($filterDate))
    : 'All Dates';

$massLabel = $filterMass !== ''
    ? $filterMass
    : 'All Mass Schedules';

$totalPresent = count($records);

echo '<!DOCTYPE html>';
echo '<html><head><meta charset="UTF-8">';
echo '<title>Present Attendance Report</title>';

echo '<style>
body {
    font-family: Arial, sans-serif;
    color: #222;
}
h1, h2, p {
    text-align: center;
}
h1 {
    font-size: 22px;
}
h2 {
    font-size: 17px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    border: 1px solid #333;
    padding: 8px;
    font-size: 11pt;
    text-align: left;
}
th {
    background: #e9eee9;
}
.report-footer {
    margin-top: 35px;
}
.signature {
    width: 45%;
    display: inline-block;
    text-align: center;
    margin-top: 45px;
}
</style>';

echo '</head><body>';

echo '<h1>ST. FRANCIS XAVIER CATHEDRAL</h1>';
echo '<h2>MINISTRY OF THE ALTAR SERVERS</h2>';
echo '<h2>ATTENDANCE REPORT</h2>';

echo '<p><strong>Mass Date:</strong> ' .
    docEscape($dateLabel) . '</p>';

echo '<p><strong>Mass Schedule:</strong> ' .
    docEscape($massLabel) . '</p>';

echo '<p><strong>Attendance Status:</strong> PRESENT</p>';

echo '<p><strong>Total Present Members:</strong> ' .
    $totalPresent . '</p>';

echo '<table>';

echo '<thead><tr>';
echo '<th>No.</th>';
echo '<th>Member Code</th>';
echo '<th>Full Name</th>';
echo '<th>Mass Date</th>';
echo '<th>Mass Schedule</th>';
echo '<th>Check-in Time</th>';
echo '<th>Method</th>';
echo '</tr></thead>';

echo '<tbody>';

if (!empty($records)) {

    foreach ($records as $index => $record) {

        $checkIn = 'N/A';

        if (!empty($record['check_in_time'])) {
            try {
                $checkIn = (new DateTime(
                    $record['check_in_time']
                ))
                ->setTimezone(new DateTimeZone('Asia/Manila'))
                ->format('h:i A');
            } catch (Throwable $e) {
                $checkIn = 'N/A';
            }
        }

        $massDate = 'N/A';

        if (!empty($record['mass_date'])) {
            $massDate = date(
                'F d, Y',
                strtotime($record['mass_date'])
            );
        }

        echo '<tr>';

        echo '<td>' . ($index + 1) . '</td>';

        echo '<td>' . docEscape($record['member_code']) . '</td>';

        echo '<td>' . docEscape($record['full_name']) . '</td>';

        echo '<td>' . docEscape($massDate) . '</td>';

        echo '<td>' . docEscape($record['mass_time']) . '</td>';

        echo '<td>' . docEscape($checkIn) . '</td>';

        echo '<td>' . docEscape($record['attendance_method']) . '</td>';

        echo '</tr>';
    }

} else {

    echo '<tr>';
    echo '<td colspan="7" style="text-align:center;">';
    echo 'No present members found for the selected filters.';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody></table>';

echo '<div class="report-footer">';

echo '<p>Prepared for the Ministry of the Altar Servers.</p>';

echo '<div class="signature">';
echo '____________________________<br>';
echo 'Prepared by';
echo '</div>';

echo '<div class="signature">';
echo '____________________________<br>';
echo 'Approved by';
echo '</div>';

echo '</div>';

echo '</body></html>';

exit;
?>
