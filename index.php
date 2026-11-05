<?php
// ============================================================
// ALTAR SERVERS MANAGEMENT SYSTEM
// MAIN DASHBOARD
// St. Francis Xavier Cathedral
// ============================================================

// Authentication and session
require_once __DIR__ . '/includes/auth.php';
requireLogin();

// Supabase PostgreSQL connection
require_once __DIR__ . '/config/database.php';

// Administrator information
$adminName = htmlspecialchars(
    getAdminName(),
    ENT_QUOTES,
    'UTF-8'
);

// Dashboard statistics
$totalMembers = 0;
$activeMembers = 0;
$archivedMembers = 0;
$totalAttendance = 0;

$databaseWarnings = [];

// Safely count records in a table
if (!function_exists('getDashboardCount')) {
    function getDashboardCount(PDO $pdo, string $sql): int
    {
        try {
            $stmt = $pdo->query($sql);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('AS-SYSTEM DASHBOARD ERROR: ' . $e->getMessage());
            return 0;
        }
    }
}

// Total members
$totalMembers = getDashboardCount(
    $pdo,
    'SELECT COUNT(*) FROM public.members'
);

// Check whether members.status exists
try {
    $stmt = $pdo->prepare("
        SELECT EXISTS (
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = 'members'
            AND column_name = 'status'
        )
    ");

    $stmt->execute();

    $hasStatusColumn = filter_var(
        $stmt->fetchColumn(),
        FILTER_VALIDATE_BOOLEAN
    );

    if ($hasStatusColumn) {

        $activeMembers = getDashboardCount(
            $pdo,
            "SELECT COUNT(*)
             FROM public.members
             WHERE LOWER(status::text) = 'active'"
        );

        $archivedMembers = getDashboardCount(
            $pdo,
            "SELECT COUNT(*)
             FROM public.members
             WHERE LOWER(status::text) = 'archived'"
        );

    } else {

        $databaseWarnings[] =
            'The members table does not contain a status column. Active and archived counts are unavailable.';
    }

} catch (PDOException $e) {

    error_log('AS-SYSTEM MEMBER STATUS ERROR: ' . $e->getMessage());

    $databaseWarnings[] =
        'Unable to retrieve member status statistics.';
}

// Total attendance records
$totalAttendance = getDashboardCount(
    $pdo,
    'SELECT COUNT(*) FROM public.mass_attendance'
);

// Current date
$currentDate = date('l, F d, Y');

// Initial for administrator avatar
$adminInitial = strtoupper(
    substr(trim(getAdminName()), 0, 1)
);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Altar Servers Management System</title>

    <link rel="icon" type="image/png" href="/assets/images/favicon.png">

    <link rel="stylesheet" href="/assets/css/style.css">

</head>

<body>

<div class="app-layout">

    <!-- ==========================================
         SIDEBAR
    =========================================== -->

    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">

            <img
                src="/assets/images/logo.png"
                alt="St. Francis Xavier Cathedral Logo"
                onerror="this.style.display='none'"
            >

            <h2>Altar Servers</h2>

            <p>St. Francis Xavier Cathedral</p>

        </div>

        <nav class="sidebar-nav">

            <!-- DASHBOARD -->
            <a href="/index.php" class="active">
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <!-- MEMBER MANAGEMENT -->
            <a href="/members/add.php">
                <span class="nav-icon">♙</span>
                <span>Member Management</span>
            </a>

            <!-- MASS ATTENDANCE -->
            <a href="/attendance/">
                <span class="nav-icon">▣</span>
                <span>Mass Attendance</span>
            </a>

            <!-- ARCHIVED MEMBERS -->
            <a href="/members/archived.php">
                <span class="nav-icon">▤</span>
                <span>Archived Members</span>
            </a>

        </nav>

        <div class="sidebar-footer">

            <p>Amare et Servire</p>

            <small>Ministry of Altar Servers</small>

        </div>

    </aside>


    <!-- MOBILE SIDEBAR OVERLAY -->

    <div class="sidebar-overlay" id="sidebarOverlay"></div>


    <!-- ==========================================
         MAIN CONTENT
    =========================================== -->

    <main class="main-content">

        <div class="content-container">


            <!-- TOPBAR -->

            <header class="topbar">

                <div class="topbar-left">

                    <button
                        type="button"
                        class="mobile-menu-button"
                        id="menuButton"
                        aria-label="Open navigation menu"
                        aria-expanded="false"
                    >
                        ☰ Menu
                    </button>

                    <div>

                        <div class="topbar-title">
                            Altar Servers Management System
                        </div>

                        <p>St. Francis Xavier Cathedral</p>

                    </div>

                </div>


                <div class="admin-profile">

                    <div class="admin-avatar">
                        <?php echo htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div class="admin-details">

                        <strong>
                            <?php echo $adminName; ?>
                        </strong>

                        <small>Administrator</small>

                    </div>

                    <a href="/logout.php" class="btn btn-outline">
                        Logout
                    </a>

                </div>

            </header>


            <!-- ==================================
                 DASHBOARD BODY
            =================================== -->

            <section class="dashboard-content">


                <!-- PAGE HEADING -->

                <div class="page-heading">

                    <div>

                        <h1>Management Dashboard</h1>

                        <p>
                            Welcome to the Ministry of Altar Servers Management System.
                        </p>

                    </div>

                    <div class="dashboard-date">

                        <?php
                        echo htmlspecialchars(
                            $currentDate,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                    </div>

                </div>


                <!-- WELCOME PANEL -->

                <section class="welcome-panel">

                    <div class="welcome-text">

                        <span class="welcome-label">
                            ST. FRANCIS XAVIER CATHEDRAL
                        </span>

                        <h2>
                            Welcome, <?php echo $adminName; ?>!
                        </h2>

                        <p>
                            Manage and monitor the members and
                            attendance records of the Ministry
                            of Altar Servers.
                        </p>

                    </div>

                    <div class="welcome-motto">

                        <span>✦</span>

                        <strong>Amare et Servire</strong>

                        <small>To Love and To Serve</small>

                    </div>

                </section>


                <!-- DATABASE WARNINGS -->

                <?php if (!empty($databaseWarnings)): ?>

                    <div class="alert alert-warning">

                        <strong>Database Notice</strong>

                        <?php foreach ($databaseWarnings as $warning): ?>

                            <p>
                                <?php
                                echo htmlspecialchars(
                                    $warning,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </p>

                        <?php endforeach; ?>

                        <p>
                            Please check your Supabase database structure
                            and Render application logs.
                        </p>

                    </div>

                <?php endif; ?>


                <!-- STATISTICS -->

                <div class="section-title">

                    <h2>Dashboard Overview</h2>

                    <p>Summary of ministry records and activities.</p>

                </div>


                <div class="stats-grid">


                    <!-- TOTAL MEMBERS -->

                    <div class="stat-card">

                        <div class="stat-icon">♙</div>

                        <small>Total Members</small>

                        <strong>
                            <?php echo number_format($totalMembers); ?>
                        </strong>

                        <span class="stat-description">
                            Registered members
                        </span>

                    </div>


                    <!-- ACTIVE MEMBERS -->

                    <div class="stat-card">

                        <div class="stat-icon">✓</div>

                        <small>Active Members</small>

                        <strong>
                            <?php echo number_format($activeMembers); ?>
                        </strong>

                        <span class="stat-description">
                            Currently active
                        </span>

                    </div>


                    <!-- ARCHIVED MEMBERS -->

                    <div class="stat-card">

                        <div class="stat-icon">▤</div>

                        <small>Archived Members</small>

                        <strong>
                            <?php echo number_format($archivedMembers); ?>
                        </strong>

                        <span class="stat-description">
                            Archived records
                        </span>

                    </div>


                    <!-- ATTENDANCE RECORDS -->

                    <div class="stat-card">

                        <div class="stat-icon">▣</div>

                        <small>Attendance Records</small>

                        <strong>
                            <?php echo number_format($totalAttendance); ?>
                        </strong>

                        <span class="stat-description">
                            Recorded Mass attendance
                        </span>

                    </div>


                </div>


                <!-- MANAGEMENT MODULES -->

                <div class="section-title">

                    <h2>Management Modules</h2>

                    <p>
                        Select a module to manage ministry information.
                    </p>

                </div>


                <div class="dashboard-modules">


                    <!-- MEMBER MANAGEMENT -->

                    <a
                        href="/members/add.php"
                        class="module-card"
                    >

                        <div class="module-icon">♙</div>

                        <div class="module-content">

                            <h3>Member Management</h3>

                            <p>
                                Register, view, update, and manage
                                altar server member information.
                            </p>

                            <span class="module-link">
                                Open Module →
                            </span>

                        </div>

                    </a>


                    <!-- MASS ATTENDANCE -->

                    <a
                        href="/attendance/"
                        class="module-card"
                    >

                        <div class="module-icon">▣</div>

                        <div class="module-content">

                            <h3>Mass Attendance</h3>

                            <p>
                                Manage attendance for the 6 AM,
                                8 AM, 10 AM, and 5 PM Sunday Masses.
                            </p>

                            <span class="module-link">
                                Open Module →
                            </span>

                        </div>

                    </a>


                    <!-- ARCHIVED MEMBERS -->

                    <a
                        href="/members/archived.php"
                        class="module-card"
                    >

                        <div class="module-icon">▤</div>

                        <div class="module-content">

                            <h3>Archived Members</h3>

                            <p>
                                View and manage previously archived
                                altar server member records.
                            </p>

                            <span class="module-link">
                                Open Module →
                            </span>

                        </div>

                    </a>


                </div>


            </section>


            <!-- FOOTER -->

            <footer class="page-footer">

                <p>
                    © <?php echo date('Y'); ?>
                    St. Francis Xavier Cathedral
                </p>

                <p>
                    Ministry of Altar Servers | Amare et Servire
                </p>

            </footer>


        </div>

    </main>

</div>


<!-- ==========================================
     MOBILE SIDEBAR SCRIPT
=========================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('sidebar');

    const menuButton = document.getElementById('menuButton');

    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !menuButton || !overlay) {
        return;
    }

    function openMenu() {

        sidebar.classList.add('open');

        overlay.classList.add('show');

        menuButton.setAttribute('aria-expanded', 'true');

    }

    function closeMenu() {

        sidebar.classList.remove('open');

        overlay.classList.remove('show');

        menuButton.setAttribute('aria-expanded', 'false');

    }

    menuButton.addEventListener('click', function () {

        if (sidebar.classList.contains('open')) {

            closeMenu();

        } else {

            openMenu();

        }

    });

    overlay.addEventListener('click', closeMenu);

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {

            closeMenu();

        }

    });

    // Close sidebar after selecting a navigation link
    document.querySelectorAll('.sidebar-nav a').forEach(function (link) {

        link.addEventListener('click', closeMenu);

    });

});

</script>


</body>

</html>
