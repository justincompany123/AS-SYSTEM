<?php
// =====================================================
// ALTAR SERVERS MANAGEMENT SYSTEM
// MAIN DASHBOARD
// =====================================================

require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';

$adminName = htmlspecialchars(getAdminName(), ENT_QUOTES, 'UTF-8');

$totalMembers = 0;
$activeMembers = 0;
$archivedMembers = 0;
$totalAttendance = 0;

try {
    // Total members
    $stmt = $pdo->query("SELECT COUNT(*) FROM public.members");
    $totalMembers = (int) $stmt->fetchColumn();

    // Active members
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM public.members
        WHERE status = 'Active'
    ");
    $activeMembers = (int) $stmt->fetchColumn();

    // Archived members
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM public.members
        WHERE status = 'Archived'
    ");
    $archivedMembers = (int) $stmt->fetchColumn();

    // Attendance records
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM public.mass_attendance
    ");
    $totalAttendance = (int) $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log('DASHBOARD DATABASE ERROR: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Altar Servers Management System</title>

    <link rel="icon" href="/assets/images/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="sidebar-brand">
            <img src="/assets/images/logo.png"
                 alt="Cathedral Logo"
                 onerror="this.style.display='none'">

            <h2>Altar Servers</h2>
            <p>St. Francis Xavier Cathedral</p>
        </div>

        <nav class="sidebar-navigation">

            <a href="/index.php" class="active">
                <span>⌂</span> Dashboard
            </a>

            <a href="/members/">
                <span>♙</span> Member Management
            </a>

            <a href="/attendance/">
                <span>▣</span> Mass Attendance
            </a>

            <a href="/archived.php">
                <span>▤</span> Archived Members
            </a>

        </nav>

        <div class="sidebar-footer">
            <p>Amare et Servire</p>
            <small>Ministry of Altar Servers</small>
        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Dashboard</h1>
                <p>St. Francis Xavier Cathedral</p>
            </div>

            <div class="admin-profile">
                <span>Welcome, <?php echo $adminName; ?></span>
                <a href="/logout.php">Logout</a>
            </div>

        </header>


        <section class="dashboard-content">

            <div class="welcome-section">
                <div>
                    <h2>Welcome to the Management Dashboard</h2>
                    <p>
                        Manage the Ministry of Altar Servers
                        of St. Francis Xavier Cathedral.
                    </p>
                </div>

                <p class="motto">Amare et Servire</p>
            </div>


            <!-- STATISTICS -->
            <div class="statistics-grid">

                <div class="stat-card">
                    <div class="stat-icon">♙</div>
                    <div>
                        <p>Total Members</p>
                        <h2><?php echo $totalMembers; ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">✓</div>
                    <div>
                        <p>Active Members</p>
                        <h2><?php echo $activeMembers; ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">▤</div>
                    <div>
                        <p>Archived Members</p>
                        <h2><?php echo $archivedMembers; ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">▣</div>
                    <div>
                        <p>Attendance Records</p>
                        <h2><?php echo $totalAttendance; ?></h2>
                    </div>
                </div>

            </div>


            <!-- MANAGEMENT MODULES -->
            <div class="section-heading">
                <h2>Management Modules</h2>
                <p>Select a module to manage ministry records.</p>
            </div>

            <div class="modules-grid">

                <a href="/members/" class="module-card">
                    <span class="module-icon">♙</span>
                    <h3>Member Management</h3>
                    <p>Register, view, update, and manage altar servers.</p>
                    <span class="module-link">Open Module →</span>
                </a>

                <a href="/attendance/" class="module-card">
                    <span class="module-icon">▣</span>
                    <h3>Mass Attendance</h3>
                    <p>Manage attendance records for Sunday Mass schedules.</p>
                    <span class="module-link">Open Module →</span>
                </a>

                <a href="/archived.php" class="module-card">
                    <span class="module-icon">▤</span>
                    <h3>Archived Members</h3>
                    <p>View and manage archived ministry members.</p>
                    <span class="module-link">Open Module →</span>
                </a>

            </div>

        </section>

        <footer class="dashboard-footer">
            <p>
                © <?php echo date('Y'); ?>
                St. Francis Xavier Cathedral |
                Ministry of Altar Servers
            </p>
            <p>Amare et Servire</p>
        </footer>

    </main>

</div>

</body>
</html>
