
<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| MAIN DASHBOARD
| SUPABASE POSTGRESQL VERSION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';

date_default_timezone_set('Asia/Manila');

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function dashboardEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function dashboardTime($value): string
{
    if (empty($value)) {
        return '-';
    }

    try {
        $date = new DateTimeImmutable($value);

        // Convert timestamp to Philippine time.
        $date = $date->setTimezone(
            new DateTimeZone('Asia/Manila')
        );

        return $date->format('h:i A');

    } catch (Throwable $e) {
        return '-';
    }
}

// =====================================================
// DEFAULT VALUES
// =====================================================

$today = date('Y-m-d');

$totalMembers = 0;
$todayAttendance = 0;
$presentToday = 0;
$recentAttendance = [];

$massCounts = [
    '6:00 AM' => 0,
    '8:00 AM' => 0,
    '10:00 AM' => 0,
    '5:00 PM' => 0
];

$dashboardError = '';

// =====================================================
// DATABASE QUERIES
// =====================================================

try {

    // -------------------------------------------------
    // TOTAL ACTIVE MEMBERS
    // -------------------------------------------------

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM public.members
        WHERE status = :status
    ");

    $stmt->execute([
        ':status' => 'Active'
    ]);

    $totalMembers = (int) $stmt->fetchColumn();


    // -------------------------------------------------
    // TOTAL ATTENDANCE RECORDS TODAY
    // -------------------------------------------------

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM public.mass_attendance
        WHERE mass_date = :today
    ");

    $stmt->execute([
        ':today' => $today
    ]);

    $todayAttendance = (int) $stmt->fetchColumn();


    // -------------------------------------------------
    // PRESENT ATTENDANCE TODAY
    // -------------------------------------------------

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM public.mass_attendance
        WHERE mass_date = :today
        AND attendance_status = :status
    ");

    $stmt->execute([
        ':today' => $today,
        ':status' => 'Present'
    ]);

    $presentToday = (int) $stmt->fetchColumn();


    // -------------------------------------------------
    // ATTENDANCE PER MASS SCHEDULE
    // -------------------------------------------------

    $stmt = $pdo->prepare("
        SELECT
            mass_time,
            COUNT(*) AS total
        FROM public.mass_attendance
        WHERE mass_date = :today
        AND attendance_status = 'Present'
        GROUP BY mass_time
    ");

    $stmt->execute([
        ':today' => $today
    ]);

    $massResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($massResults as $row) {

        $massTime = $row['mass_time'];

        if (isset($massCounts[$massTime])) {
            $massCounts[$massTime] = (int) $row['total'];
        }
    }


    // -------------------------------------------------
    // RECENT ATTENDANCE RECORDS
    // -------------------------------------------------

    $stmt = $pdo->prepare("
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

        FROM public.mass_attendance ma

        LEFT JOIN public.members m
            ON m.id = ma.member_id

        ORDER BY
            ma.created_at DESC,
            ma.id DESC

        LIMIT 8
    ");

    $stmt->execute();

    $recentAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    error_log(
        'Dashboard Database Error: ' . $e->getMessage()
    );

    $dashboardError = 'Unable to load dashboard information. Please check your database connection and table structure.';
}

// =====================================================
// PAGE HEADER AND SIDEBAR
// =====================================================

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

?>

<style>

/* =====================================================
   DASHBOARD GENERAL
===================================================== */

.dashboard-page {
    padding: 25px;
    color: #243247;
}

.dashboard-welcome {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.dashboard-welcome h1 {
    color: #172d4b;
    font-size: 26px;
    margin: 0 0 7px;
}

.dashboard-welcome p {
    color: #718096;
    font-size: 14px;
    margin: 0;
}

.dashboard-date {
    background: #ffffff;
    border: 1px solid #e5e9ef;
    padding: 12px 18px;
    border-radius: 8px;
    color: #34445a;
    font-size: 13px;
    font-weight: 600;
}

/* =====================================================
   ERROR MESSAGE
===================================================== */

.dashboard-error {
    background: #fff0ef;
    border: 1px solid #f3c7c3;
    color: #a52b24;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
}

/* =====================================================
   STATISTICS
===================================================== */

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e5e9ef;
    border-radius: 11px;
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 17px;
    box-shadow: 0 3px 10px rgba(0,0,0,.035);
}

.stat-icon {
    width: 55px;
    height: 55px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 23px;
    flex-shrink: 0;
}

.stat-icon.members {
    background: #e9eff8;
    color: #244c78;
}

.stat-icon.attendance {
    background: #e3f5e9;
    color: #21864e;
}

.stat-icon.present {
    background: #fff3d9;
    color: #b78325;
}

.stat-info span {
    display: block;
    font-size: 12px;
    color: #718096;
    margin-bottom: 7px;
}

.stat-info h2 {
    margin: 0;
    font-size: 27px;
    color: #172d4b;
}

/* =====================================================
   SECTION HEADINGS
===================================================== */

.dashboard-section {
    margin-bottom: 25px;
}

.dashboard-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.dashboard-section-header h2 {
    font-size: 18px;
    color: #1b365d;
    margin: 0;
}

.dashboard-section-header p {
    font-size: 12px;
    color: #8491a3;
    margin: 5px 0 0;
}

/* =====================================================
   MASS SCHEDULE CARDS
===================================================== */

.mass-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
}

.mass-card {
    background: #ffffff;
    border: 1px solid #e5e9ef;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,.035);
}

.mass-card-label {
    font-size: 12px;
    color: #718096;
    margin-bottom: 12px;
}

.mass-card-time {
    font-size: 20px;
    font-weight: 700;
    color: #1b365d;
    margin-bottom: 8px;
}

.mass-card-count {
    font-size: 13px;
    color: #21864e;
    font-weight: 600;
}

/* =====================================================
   QUICK ACTIONS
===================================================== */

.quick-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
}

.quick-action-card {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #ffffff;
    border: 1px solid #e5e9ef;
    border-radius: 10px;
    padding: 18px;
    text-decoration: none;
    color: #243247;
    transition: .2s ease;
}

.quick-action-card:hover {
    transform: translateY(-3px);
    border-color: #c5a15b;
    box-shadow: 0 5px 15px rgba(0,0,0,.07);
}

.quick-action-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: #edf2f8;
    color: #1b365d;
    font-size: 21px;
    flex-shrink: 0;
}

.quick-action-content {
    flex: 1;
}

.quick-action-content h3 {
    margin: 0 0 5px;
    font-size: 14px;
    color: #1b365d;
}

.quick-action-content p {
    margin: 0;
    font-size: 11px;
    color: #718096;
    line-height: 1.5;
}

.quick-action-arrow {
    color: #a68a50;
    font-size: 14px;
}

/* =====================================================
   RECENT ATTENDANCE TABLE
===================================================== */

.recent-card {
    background: #ffffff;
    border: 1px solid #e5e9ef;
    border-radius: 10px;
    overflow: hidden;
}

.recent-heading {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    border-bottom: 1px solid #e8edf2;
}

.recent-heading h2 {
    margin: 0;
    font-size: 17px;
    color: #1b365d;
}

.view-history {
    text-decoration: none;
    color: #244c78;
    font-size: 12px;
    font-weight: 700;
}

.view-history:hover {
    text-decoration: underline;
}

.recent-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.recent-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

.recent-table th {
    background: #f4f6f9;
    color: #526176;
    padding: 13px 15px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.recent-table td {
    padding: 14px 15px;
    border-bottom: 1px solid #edf0f4;
    font-size: 12px;
    color: #34445a;
}

.recent-table tbody tr:hover {
    background: #fafbfd;
}

.recent-member-name {
    font-weight: 700;
    color: #203b60;
}

.recent-member-code {
    color: #8491a3;
    font-size: 10px;
    margin-top: 4px;
}

.attendance-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.badge-present {
    background: #e3f5e9;
    color: #21864e;
}

.badge-absent {
    background: #fde9e7;
    color: #bd3b31;
}

.badge-late {
    background: #fff3d9;
    color: #a87515;
}

.badge-excused {
    background: #e8effa;
    color: #426a9c;
}

.no-records {
    text-align: center;
    padding: 40px 20px;
    color: #8491a3;
}

.no-records h3 {
    color: #45546a;
    font-size: 15px;
    margin-bottom: 7px;
}

.no-records p {
    font-size: 12px;
    margin: 0;
}

/* =====================================================
   RESPONSIVE DESIGN
===================================================== */

@media (max-width: 1000px) {

    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .mass-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 600px) {

    .dashboard-page {
        padding: 14px;
    }

    .dashboard-welcome h1 {
        font-size: 22px;
    }

    .dashboard-stats {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .mass-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .mass-card {
        padding: 14px;
    }

    .mass-card-time {
        font-size: 17px;
    }

    .quick-actions {
        grid-template-columns: 1fr;
    }

}

</style>

<div class="dashboard-page">

    <!-- =========================================
         DASHBOARD WELCOME
    ========================================== -->

    <div class="dashboard-welcome">

        <div>

            <h1>Dashboard</h1>

            <p>
                Welcome to the Ministry of Altar Servers Management System.
            </p>

        </div>

        <div class="dashboard-date">
            <i class="fas fa-calendar-alt"></i>
            <?= dashboardEscape(
                date('F d, Y')
            ) ?>
        </div>

    </div>


    <!-- DATABASE ERROR -->

    <?php if ($dashboardError !== ''): ?>

        <div class="dashboard-error">
            <?= dashboardEscape($dashboardError) ?>
        </div>

    <?php endif; ?>


    <!-- =========================================
         DASHBOARD STATISTICS
    ========================================== -->

    <div class="dashboard-stats">

        <div class="stat-card">

            <div class="stat-icon members">
                <i class="fas fa-users"></i>
            </div>

            <div class="stat-info">

                <span>Total Active Members</span>

                <h2>
                    <?= number_format($totalMembers) ?>
                </h2>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon attendance">
                <i class="fas fa-clipboard-check"></i>
            </div>

            <div class="stat-info">

                <span>Attendance Records Today</span>

                <h2>
                    <?= number_format($todayAttendance) ?>
                </h2>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon present">
                <i class="fas fa-user-check"></i>
            </div>

            <div class="stat-info">

                <span>Present Today</span>

                <h2>
                    <?= number_format($presentToday) ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- =========================================
         MASS SCHEDULE
    ========================================== -->

    <section class="dashboard-section">

        <div class="dashboard-section-header">

            <div>

                <h2>Sunday Mass Attendance</h2>

                <p>
                    Attendance recorded for <?= dashboardEscape(
                        date('F d, Y')
                    ) ?>
                </p>

            </div>

        </div>


        <div class="mass-grid">

            <?php foreach ($massCounts as $massTime => $count): ?>

                <div class="mass-card">

                    <div class="mass-card-label">
                        Sunday Mass
                    </div>

                    <div class="mass-card-time">
                        <?= dashboardEscape($massTime) ?>
                    </div>

                    <div class="mass-card-count">

                        <i class="fas fa-user-check"></i>

                        <?= number_format($count) ?> Present

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- =========================================
         QUICK ACTIONS
    ========================================== -->

    <section class="dashboard-section">

        <div class="dashboard-section-header">

            <div>

                <h2>Quick Actions</h2>

                <p>
                    Access important system features.
                </p>

            </div>

        </div>


        <div class="quick-actions">


            <!-- MEMBERS -->

            <a href="members/index.php"
               class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fas fa-users"></i>
                </div>

                <div class="quick-action-content">

                    <h3>Manage Members</h3>

                    <p>
                        View and manage altar server members.
                    </p>

                </div>

                <i class="fas fa-arrow-right quick-action-arrow"></i>

            </a>


            <!-- QR SCANNER -->

            <a href="attendance/scan.php"
               class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fas fa-qrcode"></i>
                </div>

                <div class="quick-action-content">

                    <h3>QR Attendance Scanner</h3>

                    <p>
                        Scan member QR codes to record attendance.
                    </p>

                </div>

                <i class="fas fa-arrow-right quick-action-arrow"></i>

            </a>


            <!-- ATTENDANCE HISTORY -->

            <a href="attendance/history.php"
               class="quick-action-card">

                <div class="quick-action-icon">
                    <i class="fas fa-history"></i>
                </div>

                <div class="quick-action-content">

                    <h3>Attendance History</h3>

                    <p>
                        View, filter, and download attendance records.
                    </p>

                </div>

                <i class="fas fa-arrow-right quick-action-arrow"></i>

            </a>

        </div>

    </section>


    <!-- =========================================
         RECENT ATTENDANCE
    ========================================== -->

    <section class="dashboard-section">

        <div class="recent-card">

            <div class="recent-heading">

                <h2>Recent Attendance Records</h2>

                <a href="attendance/history.php"
                   class="view-history">

                    View All History
                    <i class="fas fa-arrow-right"></i>

                </a>

            </div>


            <div class="recent-table-wrapper">

                <table class="recent-table">

                    <thead>

                        <tr>

                            <th>No.</th>
                            <th>Member</th>
                            <th>Mass Date</th>
                            <th>Mass Schedule</th>
                            <th>Check-in Time</th>
                            <th>Status</th>
                            <th>Method</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($recentAttendance)): ?>

                        <tr>

                            <td colspan="7">

                                <div class="no-records">

                                    <h3>No Attendance Records Yet</h3>

                                    <p>
                                        Attendance records will appear here
                                        after members are checked in.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $recentAttendance as $index => $record
                        ): ?>

                            <?php

                            $statusClass = match (
                                $record['attendance_status']
                            ) {
                                'Present' => 'badge-present',
                                'Absent' => 'badge-absent',
                                'Late' => 'badge-late',
                                'Excused' => 'badge-excused',
                                default => ''
                            };

                            ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <div class="recent-member-name">

                                        <?= dashboardEscape(
                                            $record['full_name']
                                        ) ?>

                                    </div>

                                    <div class="recent-member-code">

                                        <?= dashboardEscape(
                                            $record['member_code']
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <?= dashboardEscape(
                                        date(
                                            'M d, Y',
                                            strtotime($record['mass_date'])
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= dashboardEscape(
                                        $record['mass_time']
                                    ) ?>

                                </td>


                                <td>

                                    <?= dashboardEscape(
                                        dashboardTime(
                                            $record['check_in_time']
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <span class="attendance-badge <?= dashboardEscape($statusClass) ?>">

                                        <?= dashboardEscape(
                                            $record['attendance_status']
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= dashboardEscape(
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

    </section>


    <!-- =========================================
         FOOTER
    ========================================== -->

    <div style="
        text-align:center;
        padding:20px 10px;
        color:#8491a3;
        font-size:11px;
    ">

        <p>
            St. Francis Xavier Cathedral
        </p>

        <p>
            Ministry of Altar Servers
        </p>

        <strong style="color:#a68a50;">
            AMARE ET SERVIRE
        </strong>

    </div>

</div>
