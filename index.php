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

<style>

/* =========================================
   DASHBOARD SCROLLING
========================================= */

html,
body {
    min-height: 100%;
    height: auto;
    overflow-y: auto;
}

.dashboard-content {
    height: auto;
    min-height: 100vh;
    overflow: visible;
    padding-bottom: 50px;
}

/* =========================================
   ANNOUNCEMENT SECTION
========================================= */

.announcement-section {
    margin: 30px 0;
}

.announcement-card {
    position: relative;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #e8e2d7;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
}

.announcement-top {
    background: #263c32;
    color: #ffffff;
    padding: 22px 28px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.announcement-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.13);
    border-radius: 50%;
    font-size: 23px;
}

.announcement-top small {
    display: block;
    font-size: 10px;
    letter-spacing: 2px;
    color: #e0cda7;
    font-weight: 700;
    margin-bottom: 5px;
}

.announcement-top h2 {
    font-size: 21px;
    line-height: 1.4;
    margin: 0;
    color: #ffffff;
}

.announcement-body {
    padding: 28px;
}

.announcement-label {
    display: inline-block;
    color: #947343;
    background: #f6f0e5;
    border-radius: 30px;
    padding: 7px 13px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.announcement-body > p {
    color: #606060;
    font-size: 14px;
    line-height: 1.9;
    margin: 0 0 20px;
}

.announcement-body h3 {
    color: #263c32;
    font-size: 16px;
    margin: 22px 0 13px;
}

.application-details {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 13px;
    margin-top: 15px;
}

.application-item {
    background: #faf9f6;
    border: 1px solid #eeeae2;
    border-radius: 10px;
    padding: 15px;
}

.application-item strong {
    display: block;
    font-size: 12px;
    color: #947343;
    margin-bottom: 7px;
}

.application-item p {
    font-size: 13px;
    color: #555;
    line-height: 1.7;
    margin: 0;
    overflow-wrap: anywhere;
}

.facebook-link {
    color: #263c32;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 3px;
    overflow-wrap: anywhere;
}

.facebook-link:hover {
    color: #947343;
}

.application-item.full-width {
    grid-column: 1 / -1;
}

.announcement-about {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #eeeae2;
}

.announcement-about h3 {
    margin-top: 0;
}

.announcement-footer {
    background: #faf9f6;
    border-top: 1px solid #eeeae2;
    padding: 16px 28px;
    color: #947343;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
}

/* =========================================
   MASS SCHEDULE SECTION
========================================= */

.mass-schedule-section {
    margin: 35px 0 45px;
}

.mass-schedule-heading {
    margin-bottom: 18px;
}

.mass-schedule-heading h2 {
    font-size: 23px;
    font-weight: 700;
    margin: 0 0 7px;
    color: #263449;
}

.mass-schedule-heading p {
    color: #777;
    font-size: 14px;
    margin: 0;
    line-height: 1.6;
}

.mass-schedule-image {
    width: 100%;
    min-height: 250px;
    aspect-ratio: 16 / 7;
    background: #f0f1f2;
    border: 1px solid #e3e3e3;
    border-radius: 14px;
    overflow: hidden;
}

.mass-schedule-image img {
    display: block;
    width: 100%;
    height: 100%;
    min-height: 250px;
    object-fit: contain;
}

/* =========================================
   MINISTRY GALLERY
========================================= */

.ministry-gallery {
    margin-top: 45px;
    padding-bottom: 35px;
}

.gallery-heading {
    margin-bottom: 24px;
}

.gallery-heading h2 {
    font-size: 23px;
    font-weight: 700;
    margin: 0 0 7px;
}

.gallery-heading p {
    color: #777;
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
}

.gallery-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e9e9e9;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.045);

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;
}

.gallery-card:hover {
    transform: translateY(-5px);

    box-shadow:
        0 9px 24px rgba(0, 0, 0, 0.09);
}

.gallery-image {
    width: 100%;
    height: 210px;
    background: #f0f1f2;
    border-bottom: 1px solid #e8e8e8;
    overflow: hidden;
}

.gallery-image img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.gallery-card:hover .gallery-image img {
    transform: scale(1.04);
}

.gallery-info {
    padding: 19px;
}

.gallery-category {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #9a7944;
    margin-bottom: 9px;
    text-transform: uppercase;
}

.gallery-info h3 {
    font-size: 17px;
    margin: 0 0 9px;
    color: #263449;
}

.gallery-info p {
    font-size: 13px;
    line-height: 1.7;
    color: #777;
    margin: 0;
}

/* =========================================
   RESPONSIVE DESIGN
========================================= */

@media (max-width: 1000px) {

    .gallery-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

}

@media (max-width: 600px) {

    .gallery-grid {
        grid-template-columns: 1fr;
    }

    .gallery-image {
        height: 220px;
    }

    .gallery-heading h2,
    .mass-schedule-heading h2 {
        font-size: 20px;
    }

    .announcement-top {
        padding: 18px;
    }

    .announcement-top h2 {
        font-size: 17px;
    }

    .announcement-body {
        padding: 20px;
    }

    .application-details {
        grid-template-columns: 1fr;
    }

    .application-item.full-width {
        grid-column: auto;
    }

    .announcement-footer {
        padding: 15px 20px;
    }

    .mass-schedule-image {
        aspect-ratio: 16 / 9;
        min-height: 200px;
    }

}

</style>


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


    <!-- ==========================================
         ANNOUNCEMENT SECTION
    =========================================== -->

    <section class="announcement-section">

        <div class="announcement-card">

            <div class="announcement-top">

                <div class="announcement-icon">
                    📢
                </div>

                <div>

                    <small>
                        ST. FRANCIS XAVIER CATHEDRAL
                    </small>

                    <h2>
                        CALL FOR NEW ALTAR SERVERS!
                    </h2>

                </div>

            </div>


            <div class="announcement-body">

                <span class="announcement-label">
                    OFFICIAL MINISTRY ANNOUNCEMENT
                </span>

                <p>
                    The Ministry of the Altar Servers is now officially
                    accepting applications! If you feel called to serve
                    God and our parish community at the Holy Altar, we
                    invite you to join our ministry.
                </p>


                <!-- HOW TO APPLY -->

                <h3>How to Apply</h3>

                <div class="application-details">

                    <div class="application-item full-width">

                        <strong>VISIT US</strong>

                        <p>
                            New St. Francis Xavier Cathedral,
                            Hda. Clementina, Brgy. Talubangi,
                            Kabankalan City, Negros Occidental
                            6111, Philippines.
                        </p>

                    </div>


                    <div class="application-item">

                        <strong>CONTACT PERSON</strong>

                        <p>
                            Justin Ramos
                        </p>

                    </div>


                    <div class="application-item">

                        <strong>FACEBOOK</strong>

                        <p>
                            <a
                                href="https://www.facebook.com/profile.php?id=61590899823528"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="facebook-link"
                            >
                                Ministry of the Altar Servers
                            </a>
                        </p>

                        <p>
                            <a
                                href="https://www.facebook.com/TheKabankalanCathedral"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="facebook-link"
                            >
                                The Kabankalan Cathedral
                            </a>
                        </p>

                    </div>

                </div>


                <!-- ABOUT THE MINISTRY -->

                <div class="announcement-about">

                    <h3>
                        About the Ministry of Altar Servers
                    </h3>

                    <p>
                        The Ministry of Altar Servers is a dedicated
                        group of young men and women who assist the
                        clergy during the celebration of the Holy Mass
                        and other liturgical services.
                    </p>

                    <p>
                        Members learn the traditions, reverence, and
                        procedures of the Church's liturgy while growing
                        in faith, discipline, and fellowship with fellow
                        servers.
                    </p>

                    <p>
                        Serving at the altar is a privileged opportunity
                        to draw closer to Christ and actively participate
                        in the spiritual life of the parish.
                    </p>

                </div>

            </div>


            <div class="announcement-footer">

                AMARE ET SERVIRE — TO LOVE AND TO SERVE

            </div>

        </div>

    </section>


    <!-- ==========================================
         MASS SCHEDULE IMAGE
    =========================================== -->

    <section class="mass-schedule-section">

        <div class="mass-schedule-heading">

            <h2>Mass Schedule</h2>

            <p>
                Refer to the official Mass schedule of
                St. Francis Xavier Cathedral.
            </p>

        </div>


        <!-- MASS SCHEDULE IMAGE -->

        <div class="mass-schedule-image">

            <img
                src="/assets/images/mass-schedule.png"
                alt="Mass Schedule of St. Francis Xavier Cathedral"
                loading="lazy"
            >

        </div>

    </section>


    <!-- ==========================================
         DATABASE WARNINGS
    =========================================== -->

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


    <!-- ==========================================
         DASHBOARD OVERVIEW
    =========================================== -->

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


    <!-- ==========================================
         MANAGEMENT MODULES
    =========================================== -->

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


    <!-- ==========================================
         MINISTRY GALLERY
    =========================================== -->

    <section class="ministry-gallery">

        <div class="gallery-heading">

            <h2>Ministry Gallery</h2>

            <p>
                Discover the activities, traditions, and service
                of the St. Francis Xavier Cathedral Ministry of Altar Servers.
            </p>

        </div>


        <div class="gallery-grid">


            <!-- GALLERY 1: OUR MINISTRY -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <!-- ADD IMAGE 1 HERE LATER -->

                </div>

                <div class="gallery-info">

                    <span class="gallery-category">
                        Ministry Identity
                    </span>

                    <h3>Our Ministry</h3>

                    <p>
                        A community of altar servers committed to serving
                        God and the Church with faith, dedication, and humility.
                    </p>

                </div>

            </div>


            <!-- GALLERY 2: ALTAR SERVICE -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <!-- ADD IMAGE 2 HERE LATER -->

                </div>

                <div class="gallery-info">

                    <span class="gallery-category">
                        Liturgical Service
                    </span>

                    <h3>Serving at the Altar</h3>

                    <p>
                        Altar servers assisting the priest during the
                        celebration of the Holy Eucharist.
                    </p>

                </div>

            </div>


            <!-- GALLERY 3: FORMATION -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <!-- ADD IMAGE 3 HERE LATER -->

                </div>

                <div class="gallery-info">

                    <span class="gallery-category">
                        Formation
                    </span>

                    <h3>Training and Formation</h3>

                    <p>
                        Developing the knowledge, discipline, and
                        responsibility needed for liturgical service.
                    </p>

                </div>

            </div>


            <!-- GALLERY 4: CELEBRATIONS -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <!-- ADD IMAGE 4 HERE LATER -->

                </div>

                <div class="gallery-info">

                    <span class="gallery-category">
                        Church Celebrations
                    </span>

                    <h3>Liturgical Celebrations</h3>

                    <p>
                        Participating in important celebrations and
                        special occasions of the Church.
                    </p>

                </div>

            </div>


            <!-- GALLERY 5: FELLOWSHIP -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <!-- ADD IMAGE 5 HERE LATER -->

                </div>

                <div class="gallery-info">

                    <span class="gallery-category">
                        Community
                    </span>

                    <h3>Brotherhood and Fellowship</h3>

                    <p>
                        Building friendship, unity, and a stronger
                        sense of community among altar servers.
                    </p>

                </div>

            </div>


            <!-- GALLERY 6: AMARE ET SERVIRE -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <!-- ADD IMAGE 6 HERE LATER -->

                </div>

                <div class="gallery-info">

                    <span class="gallery-category">
                        Commitment
                    </span>

                    <h3>Amare et Servire</h3>

                    <p>
                        Living our ministry motto through love,
                        commitment, faith, and dedicated service.
                    </p>

                </div>

            </div>


        </div>

    </section>


</section>


<?php
/*
|--------------------------------------------------------------------------
| LOAD SHARED FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/footer.php';
?>