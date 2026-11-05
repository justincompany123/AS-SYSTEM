<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Attendance History';
$activePage = 'attendance';

$massTimes = ['6:00 AM', '8:00 AM', '10:00 AM', '5:00 PM'];

$filterDate = trim($_GET['date'] ?? '');
$filterTime = trim($_GET['mass_time'] ?? '');

$sql = "
    SELECT
        ma.id,
        ma.mass_date,
        ma.mass_time,
        ma.attendance_status,
        ma.check_in_time,
        ma.attendance_method,
        m.member_code,
        m.first_name,
        m.middle_name,
        m.last_name
    FROM mass_attendance ma
    INNER JOIN members m ON m.id = ma.member_id
    WHERE 1 = 1
";

$params = [];

if ($filterDate !== '') {
    $sql .= " AND ma.mass_date = ?";
    $params[] = $filterDate;
}

if (in_array($filterTime, $massTimes, true)) {
    $sql .= " AND ma.mass_time = ?";
    $params[] = $filterTime;
}

$sql .= " ORDER BY ma.mass_date DESC,
          FIELD(ma.mass_time, '6:00 AM', '8:00 AM', '10:00 AM', '5:00 PM'),
          ma.check_in_time DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Attendance history error: ' . $e->getMessage());
    $records = [];
    $pageError = 'Unable to load attendance records.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="main-content">

    <div class="page-heading">
        <h1>Attendance History</h1>
        <p>View altar server attendance by Mass date and time.</p>
    </div>

    <?php if (!empty($pageError)): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($pageError) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding:20px; margin-bottom:20px;">

        <form method="GET" class="form-grid">

            <div class="form-group">
                <label for="date">Mass Date</label>
                <input
                    type="date"
                    id="date"
                    name="date"
                    value="<?= htmlspecialchars($filterDate) ?>"
                >
            </div>

            <div class="form-group">
                <label for="mass_time">Mass Time</label>

                <select name="mass_time" id="mass_time">
                    <option value="">All Mass Times</option>

                    <?php foreach ($massTimes as $time): ?>
                        <option
                            value="<?= htmlspecialchars($time) ?>"
                            <?= $filterTime === $time ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($time) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group" style="align-self:end;">
                <button type="submit" class="btn btn-primary">
                    Apply Filters
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Reset
                </a>
            </div>

        </form>

    </div>

    <div class="card" style="padding:20px;">

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
            <h2>Attendance Records</h2>
            <a href="scan.php" class="btn btn-primary">
                Open QR Scanner
            </a>
        </div>

        <p style="margin-bottom:15px;">
            Total records: <strong><?= count($records) ?></strong>
        </p>

        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th>Mass Date</th>
                        <th>Mass Time</th>
                        <th>Check-in Time</th>
                        <th>Status</th>
                        <th>Method</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($records)): ?>

                        <tr>
                            <td colspan="8" style="text-align:center; padding:25px;">
                                No attendance records found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($records as $index => $record): ?>

                            <?php
                            $fullName = trim(
                                $record['first_name'] . ' ' .
                                ($record['middle_name'] ? $record['middle_name'] . ' ' : '') .
                                $record['last_name']
                            );
                            ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <?= htmlspecialchars($record['member_code']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($fullName) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(date('M d, Y', strtotime($record['mass_date']))) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($record['mass_time']) ?>
                                </td>

                                <td>
                                    <?= $record['check_in_time']
                                        ? htmlspecialchars(date('h:i A', strtotime($record['check_in_time'])))
                                        : '—' ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($record['attendance_status']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($record['attendance_method']) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
