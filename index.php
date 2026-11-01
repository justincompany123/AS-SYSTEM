
<?php

require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$today = date('Y-m-d');

try {

    $totalMembers = (int) $pdo->query("
        SELECT COUNT(*)
        FROM members
        WHERE member_status = 'Active'
    ")->fetchColumn();

    $todayAttendance = $pdo->prepare("
        SELECT COUNT(*)
        FROM mass_attendance
        WHERE mass_date = ?
    ");

    $todayAttendance->execute([$today]);
    $todayPresent = (int) $todayAttendance->fetchColumn();

    $massCountsStmt = $pdo->prepare("
        SELECT mass_time, COUNT(*) AS total
        FROM mass_attendance
        WHERE mass_date = ?
        GROUP BY mass_time
    ");

    $massCountsStmt->execute([$today]);

    $massCounts = [];

    foreach ($massCountsStmt->fetchAll() as $row) {
        $massCounts[$row['mass_time']] = (int) $row['total'];
    }

    $recentStmt = $pdo->query("
        SELECT
            ma.mass_date,
            ma.mass_time,
            ma.check_in_time,
            ma.attendance_status,
            m.member_code,
            m.first_name,
            m.last_name
        FROM mass_attendance ma
        INNER JOIN members m ON m.id = ma.member_id
        ORDER BY ma.created_at DESC
        LIMIT 8
    ");

    $recentRecords = $recentStmt->fetchAll();

} catch (PDOException $e) {

    error_log('Dashboard error: ' . $e->getMessage());

    $totalMembers = 0;
    $todayPresent = 0;
    $massCounts = [];
    $recentRecords = [];

    $pageError = 'Unable to load dashboard information. Check the database setup.';
}

require_once __DIR__ . '/includes/header.php';

$times = ['6:00 AM', '8:00 AM', '10:00 AM', '5:00 PM'];

?>

<div class="main-content">

    <div class="page-heading">
        <h1>Dashboard</h1>
        <p>Welcome to the Ministry of Altar Servers Management System.</p>
    </div>

    <?php if (!empty($pageError)): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($pageError) ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-info">
                <span>Active Members</span>
                <h2><?= number_format($totalMembers) ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Today's Attendance</span>
                <h2><?= number_format($todayPresent) ?></h2>
            </div>
        </div>

    </div>

    <div class="card" style="padding:22px; margin-top:22px;">

        <h2 style="font-family:Cinzel,serif; color:#172C46;">
            Sunday Mass Attendance
        </h2>

        <p style="color:#777; margin:8px 0 20px;">
            Attendance counts for <?= htmlspecialchars(date('F d, Y')) ?>
        </p>

        <div class="stats-grid">

            <?php foreach ($times as $time): ?>

                <div class="stat-card">
                    <div class="stat-info">
                        <span><?= htmlspecialchars($time) ?> Mass</span>
                        <h2>
                            <?= number_format($massCounts[$time] ?? 0) ?>
                        </h2>
                        <small>Recorded present</small>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <div class="card" style="padding:22px; margin-top:22px;">

        <h2 style="font-family:Cinzel,serif; color:#172C46;">
            Recent Attendance
        </h2>

        <div class="table-container" style="margin-top:18px;">

            <table>
                <thead>
                    <tr>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th>Mass Date</th>
                        <th>Mass Time</th>
                        <th>Check-in</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($recentRecords)): ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">
                                No attendance records yet.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentRecords as $record): ?>

                            <tr>
                                <td><?= htmlspecialchars($record['member_code']) ?></td>

                                <td>
                                    <?= htmlspecialchars(
                                        $record['first_name'] . ' ' . $record['last_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(date('M d, Y', strtotime($record['mass_date']))) ?>
                                </td>

                                <td><?= htmlspecialchars($record['mass_time']) ?></td>

                                <td>
                                    <?= $record['check_in_time']
                                        ? htmlspecialchars(date('h:i A', strtotime($record['check_in_time'])))
                                        : '—' ?>
                                </td>

                                <td><?= htmlspecialchars($record['attendance_status']) ?></td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

    </div>

    <div style="margin-top:22px;">
        <a href="attendance/scan.php" class="btn btn-primary">
            Open QR Attendance Scanner
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
