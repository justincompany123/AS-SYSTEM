
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
        $date = new DateTimeImmutable((string)$value);

        // Convert timestamps with timezone information to Manila time.
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
| VALIDATE DATE
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
| VALIDATE STATUS
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
        | Expected tables:
        | public.mass_attendance
        | public.members
        |
        | Expected member columns:
        | id, member_code, full_name
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

            WHERE 1 = 1
        ";

        $params = [];

        /*
        |--------------------------------------------------------------------------
        | DATE FILTER
        |--------------------------------------------------------------------------
        */

        if ($selectedDate !== '') {
            $sql .= " AND ma.mass_date = CAST(:mass_date AS DATE)";
            $params[':mass_date'] = $selectedDate;
        }

        /*
        |--------------------------------------------------------------------------
        | MASS TIME FILTER
        |--------------------------------------------------------------------------
        */

        if ($selectedMass !== '') {
            $sql .= " AND ma.mass_time = :mass_time";
            $params[':mass_time'] = $selectedMass;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS FILTER
        |--------------------------------------------------------------------------
        */

        if ($selectedStatus !== '') {
            $sql .= " AND LOWER(TRIM(ma.attendance_status)) = LOWER(:status)";
            $params[':status'] = $selectedStatus;
        }

        /*
        |--------------------------------------------------------------------------
        | MEMBER SEARCH
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | SORTING
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

                m.full_name ASC
        ";

        /*
        |--------------------------------------------------------------------------
        | EXECUTE QUERY
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $records = $stmt->fetchAll();

        /*
        |--------------------------------------------------------------------------
        | COMPUTE SUMMARY TOTALS
        |--------------------------------------------------------------------------
        */

        foreach ($records as $record) {

            $status = ucfirst(
                strtolower(trim((string)$record['attendance_status']))
            );

            if (isset($totals[$status])) {
                $totals[$status]++;
            }
        }

        $totalRecords = count($records);

    } catch (Throwable $e) {

        error_log(
            'Attendance History Error: ' . $e->getMessage()
        );

        $errorMessage = 'Unable to load attendance history. Please check your database tables and columns.';
    }
}

/*
|--------------------------------------------------------------------------
| EXPORT URLS
|--------------------------------------------------------------------------
*/

$exportParams = http_build_query([
    'date' => $selectedDate,
    'mass_time' => $selectedMass
]);

$exportDocUrl = 'export_doc.php?' . $exportParams;
$exportTxtUrl = 'export_txt.php?' . $exportParams;

/*
|--------------------------------------------------------------------------
| INCLUDE HEADER AND SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* =====================================================
   ATTENDANCE HISTORY
===================================================== */

.history-container {
    width: 100%;
    max-width: 100%;
    padding: 20px;
    box-sizing: border-box;
    color: #263238;
}

.history-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 24px;
}

.history-heading h1 {
    margin: 0 0 6px;
    font-size: 25px;
    font-weight: 700;
}

.history-heading p {
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
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.history-btn:hover {
    opacity: 0.88;
    transform: translateY(-1px);
}

.btn-primary {
    background: #244d3c;
    color: #fff;
}

.btn-secondary {
    background: #e8eee9;
    color: #244d3c;
}

.btn-document {
    background: #dce9f8;
    color: #23528b;
}

.btn-text {
    background: #eee7d7;
    color: #765b22;
}

/* SUMMARY CARDS */

.history-summary {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}

.summary-card {
    background: #fff;
    border: 1px solid #e6ebe7;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.035);
    min-width: 0;
}

.summary-card .summary-label {
    color: #718096;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 9px;
}

.summary-card .summary-number {
    font-size: 27px;
    font-weight: 800;
    line-height: 1.2;
}

.summary-total .summary-number {
    color: #244d3c;
}

.summary-present .summary-number {
    color: #23844d;
}

.summary-absent .summary-number {
    color: #c23d3d;
}

.summary-late .summary-number {
    color: #c18421;
}

.summary-excused .summary-number {
    color: #5476a4;
}

/* FILTERS */

.history-filter-card {
    background: #fff;
    border: 1px solid #e6ebe7;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 22px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.035);
}

.history-filter-card h3 {
    font-size: 15px;
    margin: 0 0 16px;
    color: #33443a;
}

.history-filter-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    align-items: end;
}

.filter-group {
    min-width: 0;
}

.filter-group label {
    display: block;
    margin-bottom: 7px;
    font-size: 12px;
    font-weight: 700;
    color: #526157;
}

.filter-group input,
.filter-group select {
    width: 100%;
    height: 42px;
    box-sizing: border-box;
    padding: 9px 11px;
    border: 1px solid #dce4dd;
    border-radius: 7px;
    outline: none;
    background: #fff;
    color: #33443a;
    font-size: 13px;
}

.filter-group input:focus,
.filter-group select:focus {
    border-color: #527c65;
    box-shadow: 0 0 0 3px rgba(82,124,101,0.10);
}

.filter-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* TABLE */

.history-table-card {
    background: #fff;
    border: 1px solid #e6ebe7;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.035);
}

.history-table-header {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    border-bottom: 1px solid #edf0ed;
}

.history-table-header h3 {
    margin: 0;
    font-size: 16px;
    color: #33443a;
}

.history-table-header span {
    color: #718096;
    font-size: 12px;
}

.history-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.history-table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

.history-table th {
    background: #f5f8f5;
    color: #64746a;
    padding: 13px 15px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}

.history-table td {
    padding: 14px 15px;
    border-bottom: 1px solid #edf0ed;
    font-size: 13px;
    color: #46544a;
    vertical-align: middle;
}

.history-table tbody tr:hover {
    background: #fafcfb;
}

.member-name {
    font-weight: 700;
    color: #33443a;
}

.member-code {
    display: block;
    margin-top: 4px;
    color: #89958d;
    font-size: 11px;
}

.status-badge,
.method-badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.status-present {
    background: #e1f4e8;
    color: #21834b;
}

.status-absent {
    background: #fde8e8;
    color: #bd3b3b;
}

.status-late {
    background: #fff1d6;
    color: #a56a12;
}

.status-excused {
    background: #e5edfa;
    color: #456ba3;
}

.status-default {
    background: #edf0ed;
    color: #64746a;
}

.method-badge {
    background: #edf3ef;
    color: #496452;
}

.empty-state {
    padding: 45px 20px !important;
    text-align: center;
    color: #829087 !important;
}

.empty-state strong {
    display: block;
    margin-bottom: 7px;
    color: #526157;
    font-size: 15px;
}

.history-error {
    padding: 14px 16px;
    border-radius: 8px;
    background: #fff0f0;
    color: #a52d2d;
    border: 1px solid #f1cccc;
    margin-bottom: 18px;
    font-size: 13px;
}

/* RESPONSIVE */

@media (max-width: 1100px) {
    .history-summary {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .history-filter-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .history-container {
        padding: 13px;
    }

    .history-heading {
        align-items: stretch;
    }

    .history-heading h1 {
        font-size: 21px;
    }

    .history-actions {
        width: 100%;
    }

    .history-actions .history-btn {
        flex: 1;
    }

    .history-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }

    .summary-card {
        padding: 14px;
    }

    .summary-card .summary-number {
        font-size: 23px;
    }

    .history-filter-form {
        grid-template-columns: 1fr;
    }

    .filter-buttons .history-btn {
        flex: 1;
    }

    .history-table-header {
        padding: 15px;
    }
}

@media (max-width: 380px) {
    .history-summary {
        grid-template-columns: 1fr;
    }

    .history-actions {
        flex-direction: column;
    }
}
</style>

<div class="history-container">

    <!-- PAGE HEADING -->

    <div class="history-heading">

        <div>
            <h1>Attendance History</h1>
            <p>View and monitor recorded Mass attendance of Altar Servers.</p>
        </div>

        <div class="history-actions">

            <a href="index.php" class="history-btn btn-secondary">
                ← Attendance
            </a>

            <a
                href="<?= historyEscape($exportDocUrl) ?>"
                class="history-btn btn-document"
            >
                Export DOC
            </a>

            <a
                href="<?= historyEscape($exportTxtUrl) ?>"
                class="history-btn btn-text"
            >
                Export TXT
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

        <div class="summary-card summary-total">
            <div class="summary-label">Total Records</div>
            <div class="summary-number">
                <?= number_format($totalRecords) ?>
            </div>
        </div>

        <div class="summary-card summary-present">
            <div class="summary-label">Present</div>
            <div class="summary-number">
                <?= number_format($totals['Present']) ?>
            </div>
        </div>

        <div class="summary-card summary-absent">
            <div class="summary-label">Absent</div>
            <div class="summary-number">
                <?= number_format($totals['Absent']) ?>
            </div>
        </div>

        <div class="summary-card summary-late">
            <div class="summary-label">Late</div>
            <div class="summary-number">
                <?= number_format($totals['Late']) ?>
            </div>
        </div>

        <div class="summary-card summary-excused">
            <div class="summary-label">Excused</div>
            <div class="summary-number">
                <?= number_format($totals['Excused']) ?>
            </div>
        </div>

    </div>

    <!-- FILTER SECTION -->

    <div class="history-filter-card">

        <h3>Filter Attendance Records</h3>

        <form
            method="GET"
            action="history.php"
            class="history-filter-form"
        >

            <div class="filter-group">

                <label for="date">Mass Date</label>

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

                    <option value="">All Schedules</option>

                    <?php foreach ($allowedMassTimes as $massTime): ?>

                        <option
                            value="<?= historyEscape($massTime) ?>"
                            <?= $selectedMass === $massTime ? 'selected' : '' ?>
                        >
                            <?= historyEscape($massTime) ?>
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
                    type="search"
                    id="search"
                    name="search"
                    placeholder="Name or member code..."
                    value="<?= historyEscape($search) ?>"
                >

            </div>

            <div class="filter-buttons">

                <button
                    type="submit"
                    class="history-btn btn-primary"
                >
                    Search Records
                </button>

                <a
                    href="history.php"
                    class="history-btn btn-secondary"
                >
                    Reset Filters
                </a>

            </div>

        </form>

    </div>

    <!-- ATTENDANCE TABLE -->

    <div class="history-table-card">

        <div class="history-table-header">

            <h3>Recorded Attendance</h3>

            <span>
                <?= number_format($totalRecords) ?> record(s) found
            </span>

        </div>

        <div class="history-table-wrapper">

            <table class="history-table">

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

                    <?php if (empty($records)): ?>

                        <tr>

                            <td colspan="7" class="empty-state">

                                <strong>No Attendance Records Found</strong>

                                No records match your selected filters.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($records as $index => $record): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>

                                    <span class="member-name">
                                        <?= historyEscape($record['full_name']) ?>
                                    </span>

                                    <span class="member-code">
                                        <?= historyEscape($record['member_code']) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= historyEscape(
                                        historyFormatDate($record['mass_date'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= historyEscape($record['mass_time']) ?>
                                </td>

                                <td>
                                    <?= historyEscape(
                                        historyCheckInTime($record['check_in_time'])
                                    ) ?>
                                </td>

                                <td>

                                    <span class="status-badge <?= historyEscape(
                                        historyStatusClass($record['attendance_status'])
                                    ) ?>">
                                        <?= historyEscape($record['attendance_status']) ?>
                                    </span>

                                </td>

                                <td>

                                    <span class="method-badge">
                                        <?= historyEscape(
                                            $record['attendance_method'] ?: 'Manual'
                                        ) ?>
                                    </span>

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
/*
|--------------------------------------------------------------------------
| END ATTENDANCE HISTORY
|--------------------------------------------------------------------------
*/
?>
