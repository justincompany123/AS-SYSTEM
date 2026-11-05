<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| Attendance History
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Attendance History';
$activePage = 'attendance';

$assignmentId = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
$filterDate = trim($_GET['date'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$allowedStatuses = ['Present', 'Absent', 'Late', 'Excused'];

if (!in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = '';
}

// Load schedules for the filter dropdown.
$stmt = $pdo->query("
    SELECT id, mass_date, mass_time, mass_title
    FROM assignments
    ORDER BY mass_date DESC, mass_time ASC
");

$schedules = $stmt->fetchAll();

// Build the attendance query.
$sql = "
    SELECT
        att.id,
        att.attendance_status,
        att.check_in_time,
        att.attendance_method,
        att.created_at,

        m.member_code,
        m.first_name,
        m.middle_name,
        m.last_name,

        a.mass_date,
        a.mass_time,
        a.mass_title

    FROM attendance att

    INNER JOIN members m
        ON m.id = att.member_id

    INNER JOIN assignments a
        ON a.id = att.assignment_id

    WHERE 1 = 1
";

$params = [];

if ($assignmentId) {
    $sql .= " AND a.id = :assignment_id";
    $params[':assignment_id'] = $assignmentId;
}

if ($filterDate !== '') {
    $dateObject = DateTime::createFromFormat('Y-m-d', $filterDate);

    if ($dateObject && $dateObject->format('Y-m-d') === $filterDate) {
        $sql .= " AND a.mass_date = :mass_date";
        $params[':mass_date'] = $filterDate;
    } else {
        $filterDate = '';
    }
}

if ($filterStatus !== '') {
    $sql .= " AND att.attendance_status = :attendance_status";
    $params[':attendance_status'] = $filterStatus;
}

if ($search !== '') {
    $sql .= "
        AND (
            m.first_name LIKE :search_first
            OR m.middle_name LIKE :search_middle
            OR m.last_name LIKE :search_last
            OR m.member_code LIKE :search_code
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[':search_first'] = $searchValue;
    $params[':search_middle'] = $searchValue;
    $params[':search_last'] = $searchValue;
    $params[':search_code'] = $searchValue;
}

$sql .= "
    ORDER BY
        a.mass_date DESC,
        a.mass_time ASC,
        m.last_name ASC,
        m.first_name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

function historyEscape($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function historyStatusClass($status): string {
    return match ($status) {
        'Present' => 'present',
        'Late' => 'late',
        'Absent' => 'absent',
        'Excused' => 'excused',
        default => ''
    };
}

// Calculate totals for the currently filtered records.
$totals = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0,
    'Excused' => 0
];

foreach ($records as $record) {
    if (isset($totals[$record['attendance_status']])) {
        $totals[$record['attendance_status']]++;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>
.main-content {
    flex: 1;
    min-width: 0;
    min-height: 100vh;
    padding: 35px;
    background: #F8F5ED;
    color: #172C46;
    font-family: Inter, sans-serif;
}

.heading {
    margin-bottom: 25px;
}

.heading h1 {
    font: 25px Cinzel, serif;
    margin: 0 0 8px;
}

.heading p {
    font-size: 12px;
    color: #888;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}

.summary-card {
    background: white;
    border: 1px solid #E9E1D3;
    border-radius: 9px;
    padding: 18px;
}

.summary-card small {
    display: block;
    color: #999;
    font-size: 10px;
    margin-bottom: 10px;
}

.summary-card strong {
    font-size: 24px;
    color: #172C46;
}

.panel {
    background: white;
    border: 1px solid #E9E1D3;
    border-radius: 10px;
    padding: 22px;
}

.filters {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1.5fr auto auto;
    gap: 10px;
    margin-bottom: 20px;
}

.filters input,
.filters select {
    width: 100%;
    min-width: 0;
    padding: 10px;
    border: 1px solid #DDD5C8;
    border-radius: 6px;
    font-size: 11px;
}

.btn {
    display: inline-block;
    padding: 10px 13px;
    border: 0;
    border-radius: 6px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.primary {
    background: #172C46;
    color: white;
}

.light {
    background: #EEEAE2;
    color: #555;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #F5F0E5;
    text-align: left;
    padding: 13px;
    font-size: 10px;
}

td {
    padding: 13px;
    border-bottom: 1px solid #EEEAE2;
    font-size: 11px;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.present {
    background: #E8F3E9;
    color: #347343;
}

.late {
    background: #FFF0DD;
    color: #9A6928;
}

.absent {
    background: #FCEBE8;
    color: #A3463D;
}

.excused {
    background: #E9EDF5;
    color: #405C88;
}

.empty {
    text-align: center;
    padding: 35px;
    color: #999;
}

@media(max-width:1000px) {
    .filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width:768px) {
    .main-content {
        padding: 22px 15px;
    }

    .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .panel {
        padding: 15px;
    }
}
</style>

<main class="main-content">

    <div class="heading">
        <h1>Attendance History</h1>
        <p>Review recorded attendance for altar servers and Sunday Mass schedules.</p>
    </div>

    <section class="summary-grid">

        <div class="summary-card">
            <small>PRESENT</small>
            <strong><?= $totals['Present'] ?></strong>
        </div>

        <div class="summary-card">
            <small>LATE</small>
            <strong><?= $totals['Late'] ?></strong>
        </div>

        <div class="summary-card">
            <small>ABSENT</small>
            <strong><?= $totals['Absent'] ?></strong>
        </div>

        <div class="summary-card">
            <small>EXCUSED</small>
            <strong><?= $totals['Excused'] ?></strong>
        </div>

    </section>

    <section class="panel">

        <form method="GET" class="filters">

            <select name="assignment_id">
                <option value="">All Mass Schedules</option>

                <?php foreach ($schedules as $schedule): ?>
                    <option value="<?= (int)$schedule['id'] ?>"
                        <?= $assignmentId === (int)$schedule['id'] ? 'selected' : '' ?>>

                        <?= historyEscape(date('M d, Y', strtotime($schedule['mass_date']))) ?>
                        —
                        <?= historyEscape(date('g:i A', strtotime($schedule['mass_time']))) ?>

                    </option>
                <?php endforeach; ?>

            </select>

            <input type="date" name="date"
                value="<?= historyEscape($filterDate) ?>">

            <select name="status">
                <option value="">All Statuses</option>

                <?php foreach ($allowedStatuses as $status): ?>
                    <option value="<?= $status ?>"
                        <?= $filterStatus === $status ? 'selected' : '' ?>>
                        <?= $status ?>
                    </option>
                <?php endforeach; ?>

            </select>

            <input type="search" name="search"
                placeholder="Search member or code..."
                value="<?= historyEscape($search) ?>">

            <button type="submit" class="btn primary">
                FILTER
            </button>

            <a href="history.php" class="btn light">
                RESET
            </a>

        </form>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>MEMBER CODE</th>
                        <th>ALTAR SERVER</th>
                        <th>MASS DATE</th>
                        <th>MASS TIME</th>
                        <th>STATUS</th>
                        <th>CHECK-IN TIME</th>
                        <th>METHOD</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($records): ?>

                    <?php foreach ($records as $record): ?>

                        <?php
                        $fullName = trim(
                            $record['first_name'] . ' ' .
                            ($record['middle_name'] ?? '') . ' ' .
                            $record['last_name']
                        );
                        ?>

                        <tr>

                            <td>
                                <?= historyEscape($record['member_code']) ?>
                            </td>

                            <td>
                                <strong><?= historyEscape($fullName) ?></strong>
                            </td>

                            <td>
                                <?= historyEscape(
                                    date('M d, Y', strtotime($record['mass_date']))
                                ) ?>
                            </td>

                            <td>
                                <?= historyEscape(
                                    date('g:i A', strtotime($record['mass_time']))
                                ) ?>
                            </td>

                            <td>
                                <span class="status <?= historyStatusClass($record['attendance_status']) ?>">
                                    <?= historyEscape($record['attendance_status']) ?>
                                </span>
                            </td>

                            <td>
                                <?= $record['check_in_time']
                                    ? historyEscape(date('g:i A', strtotime($record['check_in_time'])))
                                    : '—' ?>
                            </td>

                            <td>
                                <?= historyEscape($record['attendance_method']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty">
                            No attendance records found for the selected filters.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <p style="font-size:11px;color:#888;margin:15px 0 0">
            Total records displayed: <?= count($records) ?>
        </p>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
