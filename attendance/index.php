
<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| ATTENDANCE RECORDS
| St. Francis Xavier Cathedral
| PHP + SUPABASE POSTGRESQL
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle = 'Attendance Records';
$activePage = 'attendance';

$massTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

$statuses = [
    'Present',
    'Absent',
    'Late',
    'Excused'
];

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
        return 'N/A';
    }

    try {
        return (new DateTime($date))
            ->format('F d, Y');
    } catch (Throwable $e) {
        return 'N/A';
    }
}

function attendanceFormatTime($time): string
{
    if (empty($time)) {
        return 'N/A';
    }

    try {
        return (new DateTime($time))
            ->setTimezone(new DateTimeZone('Asia/Manila'))
            ->format('h:i A');
    } catch (Throwable $e) {
        return attendanceEscape($time);
    }
}

function attendanceStatusClass($status): string
{
    return match (strtolower(trim((string)$status))) {
        'present' => 'present',
        'absent' => 'absent',
        'late' => 'late',
        'excused' => 'excused',
        default => 'unknown'
    };
}

/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$filterDate = trim($_GET['date'] ?? '');
$filterMass = trim($_GET['mass_time'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

if ($filterDate !== '') {
    $dateObject = DateTime::createFromFormat('Y-m-d', $filterDate);

    if (!$dateObject || $dateObject->format('Y-m-d') !== $filterDate) {
        $filterDate = '';
    }
}

if (!in_array($filterMass, $massTimes, true)) {
    $filterMass = '';
}

if (!in_array($filterStatus, $statuses, true)) {
    $filterStatus = '';
}

/*
|--------------------------------------------------------------------------
| DATABASE DATA
|--------------------------------------------------------------------------
*/

$records = [];
$totalRecords = 0;
$presentCount = 0;
$absentCount = 0;
$lateCount = 0;
$excusedCount = 0;
$errorMessage = '';

try {

    /*
    |--------------------------------------------------------------------------
    | BUILD FILTER QUERY
    |--------------------------------------------------------------------------
    */

    $where = [];
    $params = [];

    if ($filterDate !== '') {
        $where[] = 'ma.mass_date = CAST(:filter_date AS DATE)';
        $params[':filter_date'] = $filterDate;
    }

    if ($filterMass !== '') {
        $where[] = 'ma.mass_time = :filter_mass';
        $params[':filter_mass'] = $filterMass;
    }

    if ($filterStatus !== '') {
        $where[] = 'ma.attendance_status = :filter_status';
        $params[':filter_status'] = $filterStatus;
    }

    if ($search !== '') {
        $where[] = "(
            m.full_name ILIKE :search_name
            OR m.member_code ILIKE :search_code
        )";

        $params[':search_name'] = '%' . $search . '%';
        $params[':search_code'] = '%' . $search . '%';
    }

    $whereSQL = '';

    if (!empty($where)) {
        $whereSQL = 'WHERE ' . implode(' AND ', $where);
    }

    /*
    |--------------------------------------------------------------------------
    | GET ATTENDANCE RECORDS
    |--------------------------------------------------------------------------
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

            COALESCE(m.member_code, 'N/A') AS member_code,
            COALESCE(m.full_name, 'Unknown Member') AS full_name

        FROM public.mass_attendance AS ma

        LEFT JOIN public.members AS m
            ON m.id = ma.member_id

        $whereSQL

        ORDER BY
            ma.mass_date DESC,

            CASE ma.mass_time
                WHEN '6:00 AM' THEN 1
                WHEN '8:00 AM' THEN 2
                WHEN '10:00 AM' THEN 3
                WHEN '5:00 PM' THEN 4
                ELSE 5
            END,

            ma.check_in_time DESC NULLS LAST
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalRecords = count($records);

    /*
    |--------------------------------------------------------------------------
    | COUNT ATTENDANCE STATUSES
    |--------------------------------------------------------------------------
    */

    foreach ($records as $record) {

        $status = strtolower(
            trim((string)$record['attendance_status'])
        );

        switch ($status) {

            case 'present':
                $presentCount++;
                break;

            case 'absent':
                $absentCount++;
                break;

            case 'late':
                $lateCount++;
                break;

            case 'excused':
                $excusedCount++;
                break;
        }
    }

} catch (Throwable $e) {

    error_log(
        'Attendance Records Error: ' . $e->getMessage()
    );

    $errorMessage = 'Unable to load attendance records. Please check your database configuration and table structure.';
}

/*
|--------------------------------------------------------------------------
| INCLUDE WEBSITE HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>
/* =========================================================
   ATTENDANCE RECORDS PAGE
========================================================= */

.attendance-page {
    width: 100%;
    max-width: 100%;
    padding: 25px;
    box-sizing: border-box;
    color: #263445;
}

.attendance-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.attendance-heading h1 {
    margin: 0 0 6px;
    font-size: 27px;
    font-weight: 700;
}

.attendance-heading p {
    margin: 0;
    color: #718096;
    font-size: 14px;
}

.attendance-heading-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.attendance-btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 7px;
    padding: 11px 16px;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.attendance-btn:hover {
    opacity: 0.88;
    transform: translateY(-1px);
}

.btn-primary {
    background: #183b35;
    color: white;
}

.btn-secondary {
    background: #edf1f5;
    color: #344054;
}

/* SUMMARY CARDS */

.attendance-summary {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.attendance-card {
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    padding: 19px;
    min-width: 0;
    box-shadow: 0 3px 12px rgba(0,0,0,0.035);
}

.attendance-card-label {
    color: #788493;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 12px;
}

.attendance-card-value {
    font-size: 27px;
    font-weight: 750;
    color: #253746;
}

.attendance-card-icon {
    font-size: 20px;
    margin-bottom: 10px;
}

/* FILTER SECTION */

.attendance-panel {
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 22px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.035);
}

.attendance-panel-title {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 17px;
}

.attendance-filter-form {
    display: grid;
    grid-template-columns: 1.2fr 1fr 1fr 1.5fr auto auto;
    gap: 12px;
    align-items: end;
}

.attendance-field label {
    display: block;
    font-size: 12px;
    font-weight: 650;
    margin-bottom: 7px;
    color: #526171;
}

.attendance-field input,
.attendance-field select {
    width: 100%;
    min-width: 0;
    height: 42px;
    padding: 0 11px;
    border: 1px solid #dce2e8;
    border-radius: 7px;
    background: #fff;
    color: #344054;
    font-size: 13px;
    box-sizing: border-box;
}

.attendance-field input:focus,
.attendance-field select:focus {
    outline: none;
    border-color: #426d60;
    box-shadow: 0 0 0 3px rgba(66,109,96,0.1);
}

/* TABLE */

.attendance-table-panel {
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 3px 12px rgba(0,0,0,0.035);
}

.attendance-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    padding: 20px;
    border-bottom: 1px solid #edf0f2;
}

.attendance-table-header h2 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
}

.attendance-table-header span {
    font-size: 12px;
    color: #778493;
}

.attendance-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.attendance-table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

.attendance-table th {
    padding: 14px 16px;
    background: #f7f9fa;
    color: #657383;
    font-size: 11px;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}

.attendance-table td {
    padding: 15px 16px;
    border-bottom: 1px solid #edf0f2;
    font-size: 13px;
    color: #344054;
    vertical-align: middle;
}

.attendance-table tbody tr:hover {
    background: #fafcfb;
}

.member-name {
    font-weight: 650;
    color: #253746;
}

.member-code {
    color: #8994a1;
    font-size: 11px;
    margin-top: 4px;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 700;
}

.status-present {
    color: #18794e;
    background: #e5f6ed;
}

.status-absent {
    color: #b42318;
    background: #feeceb;
}

.status-late {
    color: #a15c00;
    background: #fff2d8;
}

.status-excused {
    color: #2458a6;
    background: #e8f0ff;
}

.status-unknown {
    color: #667085;
    background: #edf0f2;
}

.method-badge {
    display: inline-block;
    padding: 5px 9px;
    background: #eef3f5;
    color: #526171;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.empty-records {
    padding: 55px 20px;
    text-align: center;
    color: #8994a1;
}

.empty-records .empty-icon {
    font-size: 35px;
    margin-bottom: 12px;
}

.empty-records h3 {
    color: #344054;
    font-size: 16px;
    margin: 0 0 7px;
}

.empty-records p {
    font-size: 13px;
    margin: 0;
}

.attendance-error {
    padding: 15px;
    margin-bottom: 20px;
    background: #fff0f0;
    border: 1px solid #f2caca;
    color: #a42323;
    border-radius: 8px;
    font-size: 13px;
}

/* RESPONSIVE */

@media (max-width: 1100px) {
    .attendance-summary {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .attendance-filter-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .attendance-page {
        padding: 15px;
    }

    .attendance-heading {
        align-items: flex-start;
    }

    .attendance-heading h1 {
        font-size: 22px;
    }

    .attendance-heading-actions {
        width: 100%;
    }

    .attendance-heading-actions .attendance-btn {
        flex: 1;
    }

    .attendance-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .attendance-card {
        padding: 14px;
    }

    .attendance-card-value {
        font-size: 23px;
    }

    .attendance-filter-form {
        grid-template-columns: 1fr;
    }

    .attendance-panel {
        padding: 15px;
    }

    .attendance-table-header {
        padding: 15px;
    }
}

@media (max-width: 380px) {
    .attendance-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="main-content">
    <div class="attendance-page">

        <!-- PAGE HEADING -->

        <div class="attendance-heading">

            <div>
                <h1>Attendance Records</h1>

                <p>
                    View and manage recorded Mass attendance
                    of altar servers.
                </p>
            </div>

            <div class="attendance-heading-actions">

                <a
                    href="scan.php"
                    class="attendance-btn btn-primary"
                >
                    <span>📷</span>
                    Scan QR Code
                </a>

                <a
                    href="history.php"
                    class="attendance-btn btn-secondary"
                >
                    <span>📋</span>
                    Attendance History
                </a>

            </div>

        </div>

        <!-- ERROR MESSAGE -->

        <?php if ($errorMessage !== ''): ?>

            <div class="attendance-error">
                <strong>Database Error:</strong>
                <?= attendanceEscape($errorMessage) ?>
            </div>

        <?php endif; ?>

        <!-- SUMMARY CARDS -->

        <section class="attendance-summary">

            <div class="attendance-card">
                <div class="attendance-card-icon">📊</div>
                <div class="attendance-card-label">
                    Total Records
                </div>
                <div class="attendance-card-value">
                    <?= number_format($totalRecords) ?>
                </div>
            </div>

            <div class="attendance-card">
                <div class="attendance-card-icon">✅</div>
                <div class="attendance-card-label">
                    Present
                </div>
                <div class="attendance-card-value">
                    <?= number_format($presentCount) ?>
                </div>
            </div>

            <div class="attendance-card">
                <div class="attendance-card-icon">❌</div>
                <div class="attendance-card-label">
                    Absent
                </div>
                <div class="attendance-card-value">
                    <?= number_format($absentCount) ?>
                </div>
            </div>

            <div class="attendance-card">
                <div class="attendance-card-icon">⏰</div>
                <div class="attendance-card-label">
                    Late
                </div>
                <div class="attendance-card-value">
                    <?= number_format($lateCount) ?>
                </div>
            </div>

            <div class="attendance-card">
                <div class="attendance-card-icon">📝</div>
                <div class="attendance-card-label">
                    Excused
                </div>
                <div class="attendance-card-value">
                    <?= number_format($excusedCount) ?>
                </div>
            </div>

        </section>

        <!-- FILTERS -->

        <section class="attendance-panel">

            <h2 class="attendance-panel-title">
                Filter Attendance Records
            </h2>

            <form
                action="index.php"
                method="GET"
                class="attendance-filter-form"
            >

                <div class="attendance-field">
                    <label for="date">Mass Date</label>

                    <input
                        type="date"
                        id="date"
                        name="date"
                        value="<?= attendanceEscape($filterDate) ?>"
                    >
                </div>

                <div class="attendance-field">
                    <label for="mass_time">Mass Schedule</label>

                    <select
                        id="mass_time"
                        name="mass_time"
                    >
                        <option value="">All Schedules</option>

                        <?php foreach ($massTimes as $time): ?>

                            <option
                                value="<?= attendanceEscape($time) ?>"
                                <?= $filterMass === $time ? 'selected' : '' ?>
                            >
                                <?= attendanceEscape($time) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="attendance-field">
                    <label for="status">Attendance Status</label>

                    <select
                        id="status"
                        name="status"
                    >
                        <option value="">All Statuses</option>

                        <?php foreach ($statuses as $status): ?>

                            <option
                                value="<?= attendanceEscape($status) ?>"
                                <?= $filterStatus === $status ? 'selected' : '' ?>
                            >
                                <?= attendanceEscape($status) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="attendance-field">
                    <label for="search">Search Member</label>

                    <input
                        type="search"
                        id="search"
                        name="search"
                        placeholder="Name or member code..."
                        value="<?= attendanceEscape($search) ?>"
                    >
                </div>

                <button
                    type="submit"
                    class="attendance-btn btn-primary"
                >
                    🔎 Search
                </button>

                <a
                    href="index.php"
                    class="attendance-btn btn-secondary"
                >
                    Reset
                </a>

            </form>

        </section>

        <!-- ATTENDANCE TABLE -->

        <section class="attendance-table-panel">

            <div class="attendance-table-header">

                <h2>Attendance Records</h2>

                <span>
                    <?= number_format($totalRecords) ?>
                    record(s) found
                </span>

            </div>

            <div class="attendance-table-wrapper">

                <table class="attendance-table">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member</th>
                            <th>Mass Date</th>
                            <th>Mass Schedule</th>
                            <th>Check-in Time</th>
                            <th>Status</th>
                            <th>Method</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($records)): ?>

                        <?php foreach ($records as $index => $record): ?>

                            <?php
                                $status = $record['attendance_status'] ?? '';
                                $statusClass = attendanceStatusClass($status);
                            ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <div class="member-name">
                                        <?= attendanceEscape($record['full_name']) ?>
                                    </div>

                                    <div class="member-code">
                                        <?= attendanceEscape($record['member_code']) ?>
                                    </div>
                                </td>

                                <td>
                                    <?= attendanceEscape(
                                        attendanceFormatDate($record['mass_date'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= attendanceEscape($record['mass_time']) ?>
                                </td>

                                <td>
                                    <?= attendanceEscape(
                                        attendanceFormatTime($record['check_in_time'])
                                    ) ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?= attendanceEscape($statusClass) ?>">
                                        <?= attendanceEscape($status ?: 'Unknown') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="method-badge">
                                        <?= attendanceEscape(
                                            $record['attendance_method'] ?? 'N/A'
                                        ) ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7">

                                <div class="empty-records">

                                    <div class="empty-icon">
                                        📋
                                    </div>

                                    <h3>No Attendance Records Found</h3>

                                    <p>
                                        No records match your filters.
                                        Scan a member's QR code to record attendance.
                                    </p>

                                </div>

                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </div>
</main>

<?php
/*
|--------------------------------------------------------------------------
| WEBSITE FOOTER
|--------------------------------------------------------------------------
*/

$footerPath = __DIR__ . '/../includes/footer.php';

if (file_exists($footerPath)) {
    require_once $footerPath;
}
?>
