<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| MAIN DASHBOARD
| ST. FRANCIS XAVIER CATHEDRAL
| SUPABASE POSTGRESQL
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/auth.php';

requireLogin();

require_once __DIR__ . '/config/database.php';

// Dashboard page settings
$pageTitle = 'Management Dashboard';
$activePage = 'dashboard';

// Administrator information
$adminName = getAdminName();

$safeAdminName = htmlspecialchars(
    $adminName,
    ENT_QUOTES,
    'UTF-8'
);

$adminInitial = strtoupper(
    substr(trim($adminName), 0, 1)
);

if ($adminInitial === '') {
    $adminInitial = 'A';
}

// Dashboard statistics
$totalMembers = 0;
$activeMembers = 0;
$archivedMembers = 0;
$totalAttendance = 0;

$databaseWarnings = [];

/*
|--------------------------------------------------------------------------
| DASHBOARD COUNT FUNCTION
|--------------------------------------------------------------------------
*/

if (!function_exists('getDashboardCount')) {

    function getDashboardCount(PDO $pdo, string $sql): int
    {
        try {

            $stmt = $pdo->query($sql);

            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {

            error_log(
                'AS-SYSTEM DASHBOARD ERROR: ' . $e->getMessage()
            );

            return 0;
        }
    }
}

/*
|--------------------------------------------------------------------------
| TOTAL MEMBERS
|--------------------------------------------------------------------------
*/

$totalMembers = getDashboardCount(
    $pdo,
    'SELECT COUNT(*) FROM public.members'
);

/*
|--------------------------------------------------------------------------
| ACTIVE AND ARCHIVED MEMBERS
|--------------------------------------------------------------------------
*/

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
            "
            SELECT COUNT(*)
            FROM public.members
            WHERE LOWER(status::text) = 'active'
            "
        );

        $archivedMembers = getDashboardCount(
            $pdo,
            "
            SELECT COUNT(*)
            FROM public.members
            WHERE LOWER(status::text) = 'archived'
            "
        );

    } else {

        $databaseWarnings[] =
            'The members table does not contain a status column. Active and archived counts are unavailable.';

    }

} catch (PDOException $e) {

    error_log(
        'AS-SYSTEM MEMBER STATUS ERROR: ' . $e->getMessage()
    );

    $databaseWarnings[] =
        'Unable to retrieve member status statistics.';
}

/*
|--------------------------------------------------------------------------
| TOTAL ATTENDANCE
|--------------------------------------------------------------------------
*/

$totalAttendance = getDashboardCount(
    $pdo,
    'SELECT COUNT(*) FROM public.mass_attendance'
);

/*
|--------------------------------------------------------------------------
| CURRENT DATE
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Asia/Manila');

$currentDate = date('l, F d, Y');

/*
|--------------------------------------------------------------------------
| LOAD SHARED HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';
?>

<!-- ==========================================
     DASHBOARD CONTENT
=========================================== -->

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

            <?= htmlspecialchars(
                $currentDate,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    </div>


    <!-- WELCOME PANEL -->

    <section class="welcome-panel">

        <div class="welcome-text">

            <span class="welcome-label">
                ST. FRANCIS XAVIER CATHEDRAL
            </span>

            <h2>
                Welcome, <?= $safeAdminName ?>!
            </h2>

            <p>
                Manage and monitor the members and attendance records
                of the Ministry of Altar Servers.
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
                    <?= htmlspecialchars(
                        $warning,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

            <?php endforeach; ?>

            <p>
                Please check your Supabase database structure
                and Render application logs.
            </p>

        </div>

    <?php endif; ?>


    <!-- DASHBOARD OVERVIEW -->

    <div class="section-title">

        <h2>Dashboard Overview</h2>

        <p>
            Summary of ministry records and activities.
        </p>

    </div>


    <div class="stats-grid">

        <!-- TOTAL MEMBERS -->

        <div class="stat-card">

            <div class="stat-icon">♙</div>

            <small>Total Members</small>

            <strong>
                <?= number_format($totalMembers) ?>
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
                <?= number_format($activeMembers) ?>
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
                <?= number_format($archivedMembers) ?>
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
                <?= number_format($totalAttendance) ?>
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

<?php
/*
|--------------------------------------------------------------------------
| LOAD SHARED FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/footer.php';
?>
