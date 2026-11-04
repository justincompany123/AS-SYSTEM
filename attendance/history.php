
<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| ATTENDANCE HISTORY
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
        return '-';
    }

    try {
        /*
         * If the database timestamp contains a timezone
         * offset, PHP will respect it automatically.
         *
         * If no offset exists, this assumes Manila time.
         */

        $hasTimezone = preg_match(
            '/(Z|[+-][0-9]{2}:?[0-9]{2})$/i',
            trim($value)
        );

        $timezone = $hasTimezone
            ? new DateTimeZone('UTC')
            : new DateTimeZone('Asia/Manila');

        $date = new DateTimeImmutable($value, $timezone);

        $date = $date->setTimezone(
            new DateTimeZone('Asia/Manila')
        );

        return $date->format('h:i A');

    } catch (Throwable $e) {
        return '-';
    }
}

// =====================================================
// FILTER VALUES
// =====================================================

$today = date('Y-m-d');

// Blank means display attendance from all dates.
$selectedDate = $_GET['date'] ?? '';
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

// Validate date filter
if ($selectedDate !== '') {
    $dateObject = DateTime::createFromFormat(
        '!Y-m-d',
        $selectedDate
    );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $selectedDate
    ) {
        $selectedDate = '';
    }
}
// Validate Mass time
if (
    $selectedMass !== '' &&
    !in_array($selectedMass, $allowedMassTimes, true)
) {
    $selectedMass = '';
}

// Validate attendance status
if (
    $selectedStatus !== '' &&
    !in_array($selectedStatus, $allowedStatuses, true)
) {
    $selectedStatus = '';
}


// =====================================================
// DATABASE QUERY
// =====================================================

$records = [];

$totals = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0,
    'Excused' => 0
];

$errorMessage = '';

try {

    $sql = "
        SELECT
            ma.id,
            ma.member_id,
            ma.mass_date,
            ma.mass_time,
            ma.attendance_status,
            ma.check_in_time,
            ma.attendance_method,
            ma.created_at,

            COALESCE(m.member_code, 'N/A') AS member_code,
            COALESCE(m.full_name, 'Unknown Member') AS full_name

        FROM public.mass_attendance ma

        LEFT JOIN public.members m
            ON m.id = ma.member_id

        WHERE 1=1
    ";

    $params = [];

    // Date filter: only filter when a date is selected
    if ($selectedDate !== '') {

        $sql .= "
            AND ma.mass_date = :mass_date
        ";

        $params[':mass_date'] = $selectedDate;
    }

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
            AND ma.attendance_status = :attendance_status
        ";

        $params[':attendance_status'] = $selectedStatus;
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

    // Sort attendance records
    $sql .= "
        ORDER BY
            ma.mass_date DESC,

            CASE ma.mass_time
                WHEN '6:00 AM' THEN 1
                WHEN '8:00 AM' THEN 2
                WHEN '10:00 AM' THEN 3
                WHEN '5:00 PM' THEN 4
                ELSE 5
            END,

            ma.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    foreach ($records as $record) {

        $status = $record['attendance_status'];

        if (isset($totals[$status])) {
            $totals[$status]++;
        }
    }

} catch (PDOException $e) {

    error_log(
        'Attendance History Error: ' . $e->getMessage()
    );

    $errorMessage = 'Unable to load attendance records. Please check your database connection and table structure.';
}


// =====================================================
// PAGE DISPLAY
// =====================================================

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>

/* =====================================================
   ATTENDANCE HISTORY
===================================================== */

.history-page {
    padding: 25px;
    color: #243247;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.history-header h1 {
    font-size: 25px;
    margin: 0 0 6px;
    color: #172d4b;
}

.history-header p {
    margin: 0;
    color: #718096;
    font-size: 14px;
}

.history-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.history-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 15px;
    border: none;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-doc {
    background: #244c78;
    color: white;
}

.btn-txt {
    background: #27734a;
    color: white;
}

.history-btn:hover {
    opacity: .88;
}

/* SUMMARY CARDS */

.history-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.summary-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #e5e9ef;
    box-shadow: 0 3px 10px rgba(0,0,0,.035);
}

.summary-card .summary-label {
    color: #718096;
    font-size: 13px;
    margin-bottom: 10px;
}

.summary-card .summary-number {
    font-size: 28px;
    font-weight: 700;
    color: #172d4b;
}

.summary-card.present .summary-number {
    color: #21864e;
}

.summary-card.absent .summary-number {
    color: #c0392b;
}

.summary-card.late .summary-number {
    color: #c58a22;
}

.summary-card.excused .summary-number {
    color: #426a9c;
}

/* FILTER SECTION */

.history-filter {
    background: white;
    border: 1px solid #e5e9ef;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
}

.filter-group label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #45546a;
    margin-bottom: 7px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    height: 42px;
    padding: 8px 11px;
    border: 1px solid #d8dee8;
    border-radius: 6px;
    background: #fff;
    color: #26364a;
    font-size: 13px;
    box-sizing: border-box;
}

.filter-buttons {
    display: flex;
    gap: 8px;
    margin-top: 16px;
}

.filter-submit {
    background: #1b365d;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 10px 18px;
    cursor: pointer;
    font-weight: 600;
}

.filter-reset {
    background: #edf1f5;
    color: #34445a;
    text-decoration: none;
    border-radius: 6px;
    padding: 10px 18px;
    font-size: 13px;
}

/* TABLE */

.history-table-card {
    background: white;
    border: 1px solid #e5e9ef;
    border-radius: 10px;
    overflow: hidden;
}

.table-heading {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    border-bottom: 1px solid #e8edf2;
}

.table-heading h2 {
    margin: 0;
    font-size: 17px;
    color: #1b365d;
}

.table-heading span {
    font-size: 12px;
    color: #718096;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.history-table th {
    background: #f4f6f9;
    color: #526176;
    padding: 13px 15px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.history-table td {
    padding: 14px 15px;
    border-bottom: 1px solid #edf0f4;
    font-size: 13px;
    color: #34445a;
}

.history-table tbody tr:hover {
    background: #fafbfd;
}

.member-name {
    font-weight: 700;
    color: #203b60;
}

.member-code {
    color: #7b8797;
    font-size: 11px;
    margin-top: 4px;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.status-present {
    background: #e3f5e9;
    color: #21864e;
}

.status-absent {
    background: #fde9e7;
    color: #bd3b31;
}

.status-late {
    background: #fff3d9;
    color: #a87515;
}

.status-excused {
    background: #e8effa;
    color: #426a9c;
}

.empty-state {
    padding: 45px 20px;
    text-align: center;
    color: #8491a3;
}

.empty-state h3 {
    color: #45546a;
    font-size: 16px;
    margin-bottom: 6px;
}

.history-error {
    background: #fff0ef;
    border: 1px solid #f3c7c3;
    color: #a52b24;
    padding: 14px;
    border-radius: 7px;
    margin-bottom: 18px;
    font-size: 13px;
}

/* RESPONSIVE */

@media (max-width: 900px) {
    .history-summary {
        grid-template-columns: repeat(2, 1fr);
    }

    .filter-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 550px) {
    .history-page {
        padding: 14px;
    }

    .history-summary {
        grid-template-columns: 1fr 1fr;
        gap: 9px;
    }

    .summary-card {
        padding: 14px;
    }

    .summary-card .summary-number {
        font-size: 23px;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .history-actions {
        width: 100%;
    }

    .history-btn {
        flex: 1;
    }
}

</style>

<div class="history-page">

    <!-- PAGE HEADER -->

    <div class="history-header">

        <div>
            <h1>Attendance History</h1>

            <p>
                View and export altar server attendance records.
            </p>
        </div>

        <div class="history-actions">

            <a href="export_doc.php?<?= http_build_query($_GET) ?>">
    Download DOC
</a>

<a href="export_txt.php?<?= http_build_query($_GET) ?>">
    Download TXT
</a>

        </div>

    </div>

    <!-- ERROR MESSAGE -->

    <?php if ($errorMessage !== ''): ?>

        <div class="history-error">
            <?= historyEscape($errorMessage) ?>
        </div>

    <?php endif; ?>

    <!-- SUMMARY CARDS -->

    <div class="history-summary">

        <div class="summary-card present">
            <div class="summary-label">Present</div>
            <div class="summary-number">
                <?= $totals['Present'] ?>
            </div>
        </div>

        <div class="summary-card absent">
            <div class="summary-label">Absent</div>
            <div class="summary-number">
                <?= $totals['Absent'] ?>
            </div>
        </div>

        <div class="summary-card late">
            <div class="summary-label">Late</div>
            <div class="summary-number">
                <?= $totals['Late'] ?>
            </div>
        </div>

        <div class="summary-card excused">
            <div class="summary-label">Excused</div>
            <div class="summary-number">
                <?= $totals['Excused'] ?>
            </div>
        </div>

    </div>

    <!-- FILTER SECTION -->

    <div class="history-filter">

        <form method="GET" action="history.php">

            <div class="filter-grid">

                <div class="filter-group">

                    <label for="date">Attendance Date</label>

                    <input
    type="date"
    id="date"
    name="date"
    value="<?= historyEscape($selectedDate) ?>"
>

                </div>

                <div class="filter-group">

                    <label for="mass_time">Mass Schedule</label>

                    <select id="mass_time" name="mass_time">

                        <option value="">All Mass Schedules</option>

                        <?php foreach ($allowedMassTimes as $time): ?>

                            <option
                                value="<?= historyEscape($time) ?>"
                                <?= $selectedMass === $time ? 'selected' : '' ?>
                            >
                                <?= historyEscape($time) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="status">Attendance Status</label>

                    <select id="status" name="status">

                        <option value="">All Statuses</option>

                        <?php foreach ($allowedStatuses as $status): ?>

                            <option
                                value="<?= historyEscape($status) ?>"
                                <?= $selectedStatus === $status ? 'selected' : '' ?>
                            >
                                <?= historyEscape($status) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="search">Search Member</label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Enter member name or code"
                        value="<?= historyEscape($search) ?>"
                    >

                </div>

            </div>

            <div class="filter-buttons">

                <button type="submit" class="filter-submit">
                    Apply Filters
                </button>

                <a href="history.php" class="filter-reset">
                    Reset
                </a>

            </div>

        </form>

    </div>

    <!-- ATTENDANCE TABLE -->

    <div class="history-table-card">

        <div class="table-heading">

            <h2>Attendance Records</h2>

            <span>
                <?= count($records) ?> record(s) found
            </span>

        </div>

        <div class="table-responsive">

            <table class="history-table">

                <thead>

                    <tr>
                        <th>No.</th>
                        <th>Member Name</th>
                        <th>Mass Date</th>
                        <th>Mass Schedule</th>
                        <th>Check-in Time</th>
                        <th>Status</th>
                        <th>Method</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (empty($records)): ?>

                    <tr>
                        <td colspan="7">

                            <div class="empty-state">

                                <h3>No Attendance Records Found</h3>

                                <p>
                                    No records match your selected date
                                    or filters.
                                </p>

                            </div>

                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($records as $index => $record): ?>

                        <?php

                        $statusClass = match (
                            $record['attendance_status']
                        ) {
                            'Present' => 'status-present',
                            'Absent' => 'status-absent',
                            'Late' => 'status-late',
                            'Excused' => 'status-excused',
                            default => ''
                        };

                        ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>

                            <td>

                                <div class="member-name">
                                    <?= historyEscape($record['full_name']) ?>
                                </div>

                                <div class="member-code">
                                    <?= historyEscape($record['member_code']) ?>
                                </div>

                            </td>

                            <td>
                                <?= historyEscape(
                                    date(
                                        'M d, Y',
                                        strtotime($record['mass_date'])
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= historyEscape($record['mass_time']) ?>
                            </td>

                            <td>
                                <?= historyEscape(
                                    historyCheckInTime(
                                        $record['check_in_time']
                                    )
                                ) ?>
                            </td>

                            <td>

                                <span class="status-badge <?= historyEscape($statusClass) ?>">
                                    <?= historyEscape($record['attendance_status']) ?>
                                </span>

                            </td>

                            <td>
                                <?= historyEscape(
                                    $record['attendance_method'] ?: '-'
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php
// PAGE END
?>
