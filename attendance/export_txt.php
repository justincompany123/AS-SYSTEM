<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| EXPORT PRESENT ATTENDANCE TO TXT
| SUPABASE POSTGRESQL
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

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

    error_log('TXT export error: ' . $e->getMessage());

    http_response_code(500);
    exit('Unable to generate the attendance text report. Please check the server logs.');
}

$filename = 'Attendance_Present_' .
    ($filterDate !== '' ? $filterDate : date('Y-m-d')) .
    '.txt';

header('Content-Type: text/plain; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$dateLabel = $filterDate !== ''
    ? date('F d, Y', strtotime($filterDate))
    : 'All Dates';

$massLabel = $filterMass !== ''
    ? $filterMass
    : 'All Mass Schedules';

echo "ST. FRANCIS XAVIER CATHEDRAL\n";
echo "MINISTRY OF THE ALTAR SERVERS\n";
echo "ATTENDANCE REPORT\n";

echo str_repeat('=', 65) . "\n";

echo "Mass Date: " . $dateLabel . "\n";
echo "Mass Schedule: " . $massLabel . "\n";
echo "Attendance Status: PRESENT\n";

echo "Total Present Members: " . count($records) . "\n";

echo "Generated: " . date('F d, Y h:i A') . "\n";

echo str_repeat('=', 65) . "\n\n";

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

        echo ($index + 1) . ". " .
            $record['full_name'] . "\n";

        echo "   Member Code: " .
            $record['member_code'] . "\n";

        echo "   Mass Date: " .
            $massDate . "\n";

        echo "   Mass Schedule: " .
            $record['mass_time'] . "\n";

        echo "   Check-in Time: " .
            $checkIn . "\n";

        echo "   Attendance Method: " .
            $record['attendance_method'] . "\n";

        echo str_repeat('-', 65) . "\n";
    }

} else {

    echo "No present members found for the selected filters.\n";
}

echo "\n";
echo "Prepared for the Ministry of the Altar Servers.\n";
echo "St. Francis Xavier Cathedral\n";

exit;
?>
