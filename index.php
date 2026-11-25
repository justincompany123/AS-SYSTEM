<?php
/*
|--------------------------------------------------------------------------
| AS-SYSTEM
| ADMINISTRATOR DASHBOARD
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

// Authentication
require_once __DIR__ . '/includes/auth.php';

requireLogin();

// Database connection
require_once __DIR__ . '/config/database.php';

// Page settings
$pageTitle = 'Management Dashboard';
$activePage = 'dashboard';

// Philippines timezone
date_default_timezone_set('Asia/Manila');

$currentDate = date('l, F d, Y');

// Administrator name
$adminName = $_SESSION['admin_name']
    ?? $_SESSION['username']
    ?? 'Administrator';

$safeAdminName = htmlspecialchars(
    (string) $adminName,
    ENT_QUOTES,
    'UTF-8'
);

// Dashboard statistics
$totalMembers = 0;
$activeMembers = 0;
$archivedMembers = 0;
$totalAttendance = 0;

$dashboardError = false;

/*
|--------------------------------------------------------------------------
| RETRIEVE DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

try {

    // Total registered members
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM public.members
    ");

    $totalMembers = (int) $stmt->fetchColumn();


    // Check whether members.status exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'members'
          AND column_name = 'status'
    ");

    $stmt->execute();

    $hasStatusColumn = ((int) $stmt->fetchColumn() > 0);


    // Active and archived members
    if ($hasStatusColumn) {

        $stmt = $pdo->query("
            SELECT
                COUNT(*) FILTER (
                    WHERE LOWER(TRIM(status::text)) = 'active'
                ) AS active_count,

                COUNT(*) FILTER (
                    WHERE LOWER(TRIM(status::text)) = 'archived'
                ) AS archived_count

            FROM public.members
        ");

        $memberStats = $stmt->fetch(PDO::FETCH_ASSOC);

        $activeMembers = (int) (
            $memberStats['active_count'] ?? 0
        );

        $archivedMembers = (int) (
            $memberStats['archived_count'] ?? 0
        );

    }


    // Total attendance records
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM public.mass_attendance
    ");

    $totalAttendance = (int) $stmt->fetchColumn();


} catch (PDOException $e) {

    $dashboardError = true;

    error_log(
        'AS-SYSTEM DASHBOARD DATABASE ERROR: '
        . $e->getMessage()
    );

}


/*
|--------------------------------------------------------------------------
| LOAD SHARED HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

?>

<style>

/* =========================================
   DASHBOARD GENERAL
========================================= */

.dashboard-content {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 30px;
    min-height: 100vh;
    overflow: visible;
}

.dashboard-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.dashboard-heading h1 {
    color: #263c32;
    font-size: 27px;
    font-weight: 700;
    margin: 0 0 7px;
}

.dashboard-heading p {
    color: #777;
    font-size: 14px;
    margin: 0;
}

.dashboard-date {
    background: #fff;
    border: 1px solid #e8e5dc;
    padding: 11px 16px;
    border-radius: 8px;
    color: #59635b;
    font-size: 12px;
}

/* =========================================
   WELCOME SECTION
========================================= */

.dashboard-welcome {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    padding: 35px;
    margin-bottom: 35px;
    border-radius: 12px;
    overflow: hidden;
    background: #263c32;
    color: #fff;
}

.welcome-content {
    max-width: 650px;
    position: relative;
    z-index: 1;
}

.welcome-eyebrow {
    display: inline-block;
    color: #d9c18e;
    font-size: 10px;
    letter-spacing: 2px;
    font-weight: 700;
    margin-bottom: 13px;
}

.welcome-content h2 {
    font-family: "Cinzel", Georgia, serif;
    font-size: clamp(23px, 3vw, 34px);
    line-height: 1.4;
    margin: 0 0 12px;
    color: #fff;
}

.welcome-content p {
    font-size: 14px;
    line-height: 1.8;
    color: #e3e9e3;
    margin: 0;
}

.welcome-motto {
    min-width: 190px;
    text-align: center;
    padding: 20px;
    border-left: 1px solid rgba(255,255,255,.2);
}

.welcome-cross {
    display: block;
    color: #d5b878;
    font-size: 34px;
    margin-bottom: 10px;
}

.welcome-motto strong {
    display: block;
    color: #e3cc9a;
    font-family: "Cinzel", Georgia, serif;
    font-size: 14px;
    letter-spacing: 1px;
}

.welcome-motto span {
    display: block;
    color: #e3e9e3;
    font-size: 11px;
    margin-top: 7px;
}

/* =========================================
   SECTION HEADINGS
========================================= */

.dashboard-section {
    margin-top: 38px;
}

.section-heading {
    margin-bottom: 18px;
}

.section-heading h2 {
    color: #263c32;
    font-size: 21px;
    margin: 0 0 7px;
}

.section-heading p {
    color: #7a7a7a;
    font-size: 13px;
    line-height: 1.7;
    margin: 0;
}

/* =========================================
   ANNOUNCEMENT
========================================= */

.announcement-card {
    background: #fff;
    border: 1px solid #e9e5dc;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 3px 14px rgba(0,0,0,.035);
}

.announcement-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 22px 25px;
    background: #263c32;
    color: #fff;
}

.announcement-symbol {
    width: 43px;
    height: 43px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: rgba(255,255,255,.12);
    font-size: 21px;
}

.announcement-header small {
    display: block;
    color: #d9c18e;
    font-size: 10px;
    letter-spacing: 1.5px;
    margin-bottom: 5px;
}

.announcement-header h3 {
    color: #fff;
    font-size: 19px;
    line-height: 1.5;
    margin: 0;
}

.announcement-body {
    padding: 25px;
}

.announcement-tag {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 20px;
    background: #f4f0e6;
    color: #8d7548;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.announcement-body p {
    color: #606060;
    font-size: 13px;
    line-height: 1.9;
    margin: 0 0 15px;
}

.announcement-body h4 {
    color: #263c32;
    font-size: 15px;
    margin: 22px 0 13px;
}

.application-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.application-item {
    padding: 16px;
    background: #faf9f6;
    border: 1px solid #ece9e1;
    border-radius: 8px;
    overflow-wrap: anywhere;
}

.application-item.full {
    grid-column: 1 / -1;
}

.application-item strong {
    display: block;
    color: #947747;
    font-size: 10px;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.application-item p {
    margin: 0;
    font-size: 13px;
}

.announcement-about {
    padding-top: 18px;
    margin-top: 20px;
    border-top: 1px solid #eeeae2;
}

.announcement-footer {
    padding: 15px 25px;
    background: #faf9f6;
    border-top: 1px solid #eeeae2;
    color: #8d7548;
    font-size: 10px;
    letter-spacing: 1px;
    font-weight: 700;
}

/* =========================================
   MASS SCHEDULE
========================================= */

.mass-schedule-frame {
    width: 100%;
    min-height: 250px;
    aspect-ratio: 16 / 7;
    background: #f0f1ef;
    border: 1px solid #e4e5df;
    border-radius: 12px;
    overflow: hidden;
}

.mass-schedule-frame img {
    width: 100%;
    height: 100%;
    min-height: 250px;
    display: block;
    object-fit: contain;
}

/* =========================================
   STATISTICS
========================================= */

.statistics-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 17px;
}

.statistics-card {
    background: #fff;
    border: 1px solid #e9e8e1;
    border-radius: 10px;
    padding: 21px;
    box-shadow: 0 3px 12px rgba(0,0,0,.025);
}

.statistics-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 17px;
}

.statistics-icon {
    width: 39px;
    height: 39px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: #f3f0e7;
    color: #947747;
    font-size: 19px;
}

.statistics-label {
    display: block;
    color: #777;
    font-size: 12px;
    margin-bottom: 8px;
}

.statistics-number {
    display: block;
    color: #263c32;
    font-size: 27px;
    font-weight: 700;
}

.statistics-note {
    display: block;
    color: #999;
    font-size: 11px;
    margin-top: 5px;
}

/* =========================================
   MANAGEMENT MODULES
========================================= */

.modules-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 17px;
}

.module-card {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    padding: 22px;
    background: #fff;
    border: 1px solid #e9e8e1;
    border-radius: 10px;
    text-decoration: none;
    transition: .2s ease;
}

.module-card:hover {
    transform: translateY(-3px);
    border-color: #c6b17f;
    box-shadow: 0 7px 18px rgba(0,0,0,.06);
}

.module-icon {
    width: 43px;
    height: 43px;
    min-width: 43px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: #f3f0e7;
    color: #947747;
    font-size: 20px;
}

.module-details h3 {
    color: #263c32;
    font-size: 15px;
    margin: 2px 0 8px;
}

.module-details p {
    color: #777;
    font-size: 12px;
    line-height: 1.7;
    margin: 0 0 12px;
}

.module-details span {
    color: #947747;
    font-size: 11px;
    font-weight: 700;
}

/* =========================================
   MINISTRY GALLERY
========================================= */

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

.gallery-card {
    background: #fff;
    border: 1px solid #e9e8e1;
    border-radius: 10px;
    overflow: hidden;
    transition: .2s ease;
}

.gallery-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 7px 18px rgba(0,0,0,.06);
}

.gallery-image {
    height: 210px;
    background: #f0f1ef;
    border-bottom: 1px solid #e9e8e1;
    overflow: hidden;
}

.gallery-image img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.gallery-details {
    padding: 19px;
}

.gallery-category {
    color: #947747;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.gallery-details h3 {
    color: #263c32;
    font-size: 16px;
    margin: 9px 0;
}

.gallery-details p {
    color: #777;
    font-size: 12px;
    line-height: 1.7;
    margin: 0;
}

/* =========================================
   DATABASE ERROR MESSAGE
========================================= */

.dashboard-alert {
    padding: 15px 18px;
    border: 1px solid #e7c9a0;
    background: #fff8ed;
    color: #79552d;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.7;
    margin-bottom: 25px;
}

/* =========================================
   RESPONSIVE DESIGN
========================================= */

@media (max-width: 1100px) {

    .statistics-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .modules-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

}

@media (max-width: 750px) {

    .dashboard-content {
        padding: 20px 15px;
    }

    .dashboard-welcome {
        flex-direction: column;
        align-items: flex-start;
        padding: 25px;
    }

    .welcome-motto {
        border-left: none;
        border-top: 1px solid rgba(255,255,255,.2);
        width: 100%;
        padding: 18px 0 0;
        text-align: left;
    }

    .gallery-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

}

@media (max-width: 520px) {

    .statistics-grid,
    .modules-grid,
    .gallery-grid,
    .application-grid {
        grid-template-columns: 1fr;
    }

    .application-item.full {
        grid-column: auto;
    }

    .dashboard-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .announcement-header {
        padding: 18px;
    }

    .announcement-header h3 {
        font-size: 16px;
    }

    .announcement-body {
        padding: 19px;
    }

    .gallery-image {
        height: 220px;
    }

    .mass-schedule-frame {
        aspect-ratio: 4 / 3;
    }

}

</style>


<!-- ==========================================
     DASHBOARD MAIN CONTENT
=========================================== -->

<main class="dashboard-content">


    <!-- ======================================
         PAGE HEADING
    ======================================= -->

    <div class="dashboard-heading">

        <div>

            <h1>Management Dashboard</h1>

            <p>
                Overview of the Ministry of Altar Servers.
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


    <!-- ======================================
         WELCOME SECTION
    ======================================= -->

    <section class="dashboard-welcome">

        <div class="welcome-content">

            <span class="welcome-eyebrow">
                ST. FRANCIS XAVIER CATHEDRAL
            </span>

            <h2>
                Welcome, <?= $safeAdminName ?>!
            </h2>

            <p>
                Welcome to the Ministry of Altar Servers Management
                System. Manage member information, monitor Mass
                attendance, and keep ministry records organized
                in one place.
            </p>

        </div>

        <div class="welcome-motto">

            <span class="welcome-cross">✝</span>

            <strong>AMARE ET SERVIRE</strong>

            <span>To Love and To Serve</span>

        </div>

    </section>


    <!-- ======================================
         DATABASE NOTICE
    ======================================= -->

    <?php if ($dashboardError): ?>

        <div class="dashboard-alert">

            <strong>Database Notice</strong>

            <br>

            Some dashboard statistics could not be loaded.
            Please check your database connection and Render logs.

            No database credentials or technical error details
            are displayed here.

        </div>

    <?php endif; ?>


    <!-- ======================================
         ANNOUNCEMENT
    ======================================= -->

    <section class="dashboard-section">

        <div class="section-heading">

            <h2>Ministry Announcement</h2>

            <p>
                Important information and updates from the ministry.
            </p>

        </div>


        <div class="announcement-card">


            <div class="announcement-header">

                <div class="announcement-symbol">
                    📢
                </div>

                <div>

                    <small>
                        OFFICIAL MINISTRY ANNOUNCEMENT
                    </small>

                    <h3>
                        CALL FOR NEW ALTAR SERVERS!
                    </h3>

                </div>

            </div>


            <div class="announcement-body">

                <span class="announcement-tag">
                    APPLICATIONS ARE NOW OPEN
                </span>

                <p>
                    The Ministry of the Altar Servers is now officially
                    accepting applications! If you feel called to serve
                    God and our parish community at the Holy Altar,
                    we invite you to join our ministry.
                </p>


                <h4>How to Apply</h4>


                <div class="application-grid">


                    <div class="application-item full">

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
                            Visit the official Facebook page
                            of the Ministry of Altar Servers.
                        </p>

                        <!-- Add your verified Facebook URL here later. -->

                    </div>


                </div>


                <div class="announcement-about">

                    <h4>
                        About the Ministry of Altar Servers
                    </h4>

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


    <!-- ======================================
         MASS SCHEDULE
    ======================================= -->

    <section class="dashboard-section">

        <div class="section-heading">

            <h2>Mass Schedule</h2>

            <p>
                Official Mass schedule of St. Francis Xavier Cathedral.
            </p>

        </div>


        <div class="mass-schedule-frame">

            <!--
                MASS SCHEDULE IMAGE PLACEHOLDER

                When you upload your image, use:

                <img
                    src="/assets/images/mass-schedule.png"
                    alt="Cathedral Mass Schedule"
                >

            -->

        </div>

    </section>


    <!-- ======================================
         DASHBOARD STATISTICS
    ======================================= -->

    <section class="dashboard-section">

        <div class="section-heading">

            <h2>Dashboard Overview</h2>

            <p>
                Summary of the ministry's current records.
            </p>

        </div>


        <div class="statistics-grid">


            <!-- TOTAL MEMBERS -->

            <div class="statistics-card">

                <div class="statistics-top">

                    <div class="statistics-icon">
                        ♙
                    </div>

                </div>

                <span class="statistics-label">
                    Total Members
                </span>

                <strong class="statistics-number">
                    <?= number_format($totalMembers) ?>
                </strong>

                <span class="statistics-note">
                    Registered member records
                </span>

            </div>


            <!-- ACTIVE MEMBERS -->

            <div class="statistics-card">

                <div class="statistics-top">

                    <div class="statistics-icon">
                        ✓
                    </div>

                </div>

                <span class="statistics-label">
                    Active Members
                </span>

                <strong class="statistics-number">
                    <?= number_format($activeMembers) ?>
                </strong>

                <span class="statistics-note">
                    Currently active members
                </span>

            </div>


            <!-- ARCHIVED MEMBERS -->

            <div class="statistics-card">

                <div class="statistics-top">

                    <div class="statistics-icon">
                        ▤
                    </div>

                </div>

                <span class="statistics-label">
                    Archived Members
                </span>

                <strong class="statistics-number">
                    <?= number_format($archivedMembers) ?>
                </strong>

                <span class="statistics-note">
                    Archived member records
                </span>

            </div>


            <!-- ATTENDANCE -->

            <div class="statistics-card">

                <div class="statistics-top">

                    <div class="statistics-icon">
                        ◷
                    </div>

                </div>

                <span class="statistics-label">
                    Attendance Records
                </span>

                <strong class="statistics-number">
                    <?= number_format($totalAttendance) ?>
                </strong>

                <span class="statistics-note">
                    Recorded Mass attendance
                </span>

            </div>


        </div>

    </section>


    <!-- ======================================
         MANAGEMENT MODULES
    ======================================= -->

    <section class="dashboard-section">

        <div class="section-heading">

            <h2>Management Modules</h2>

            <p>
                Select a module to manage ministry information.
            </p>

        </div>


        <div class="modules-grid">


            <!-- MEMBERS -->

            <a
                href="/members/index.php"
                class="module-card"
            >

                <div class="module-icon">
                    ♙
                </div>

                <div class="module-details">

                    <h3>Member Management</h3>

                    <p>
                        View, register, and manage altar server
                        member information.
                    </p>

                    <span>
                        OPEN MODULE →
                    </span>

                </div>

            </a>


            <!-- REGISTER MEMBER -->

            <a
                href="/members/add.php"
                class="module-card"
            >

                <div class="module-icon">
                    ＋
                </div>

                <div class="module-details">

                    <h3>Register New Member</h3>

                    <p>
                        Add a new member to the Ministry of
                        Altar Servers.
                    </p>

                    <span>
                        OPEN MODULE →
                    </span>

                </div>

            </a>


            <!-- ATTENDANCE -->

            <a
                href="/attendance/index.php"
                class="module-card"
            >

                <div class="module-icon">
                    ◷
                </div>

                <div class="module-details">

                    <h3>Attendance Records</h3>

                    <p>
                        View and manage Sunday Mass attendance
                        records.
                    </p>

                    <span>
                        OPEN MODULE →
                    </span>

                </div>

            </a>


            <!-- QR SCANNER -->

            <a
                href="/attendance/scan.php"
                class="module-card"
            >

                <div class="module-icon">
                    ▦
                </div>

                <div class="module-details">

                    <h3>QR Attendance Scanner</h3>

                    <p>
                        Record altar server attendance using
                        the QR scanner.
                    </p>

                    <span>
                        OPEN MODULE →
                    </span>

                </div>

            </a>


            <!-- ARCHIVED MEMBERS -->

            <a
                href="/members/archived.php"
                class="module-card"
            >

                <div class="module-icon">
                    ▤
                </div>

                <div class="module-details">

                    <h3>Archived Members</h3>

                    <p>
                        View and manage archived member records.
                    </p>

                    <span>
                        OPEN MODULE →
                    </span>

                </div>

            </a>


        </div>

    </section>


    <!-- ======================================
         MINISTRY GALLERY
    ======================================= -->

    <section class="dashboard-section">

        <div class="section-heading">

            <h2>Ministry Gallery</h2>

            <p>
                A collection of ministry activities, service,
                formation, and fellowship.
            </p>

        </div>


        <div class="gallery-grid">


            <!-- IMAGE 1 -->

            <article class="gallery-card">

                <div class="gallery-image">

                    <!-- Add ministry image here later -->

                </div>

                <div class="gallery-details">

                    <span class="gallery-category">
                        MINISTRY IDENTITY
                    </span>

                    <h3>Our Ministry</h3>

                    <p>
                        A community dedicated to serving God
                        and the Church with faith and humility.
                    </p>

                </div>

            </article>


            <!-- IMAGE 2 -->

            <article class="gallery-card">

                <div class="gallery-image">

                    <!-- Add altar service image here later -->

                </div>

                <div class="gallery-details">

                    <span class="gallery-category">
                        LITURGICAL SERVICE
                    </span>

                    <h3>Serving at the Altar</h3>

                    <p>
                        Assisting the clergy during the
                        celebration of the Holy Eucharist.
                    </p>

                </div>

            </article>


            <!-- IMAGE 3 -->

            <article class="gallery-card">

                <div class="gallery-image">

                    <!-- Add formation image here later -->

                </div>

                <div class="gallery-details">

                    <span class="gallery-category">
                        FORMATION
                    </span>

                    <h3>Training and Formation</h3>

                    <p>
                        Learning the traditions and responsibilities
                        of liturgical service.
                    </p>

                </div>

            </article>


            <!-- IMAGE 4 -->

            <article class="gallery-card">

                <div class="gallery-image">

                    <!-- Add celebration image here later -->

                </div>

                <div class="gallery-details">

                    <span class="gallery-category">
                        CHURCH CELEBRATIONS
                    </span>

                    <h3>Liturgical Celebrations</h3>

                    <p>
                        Participating in important celebrations
                        and special occasions of the Church.
                    </p>

                </div>

            </article>


            <!-- IMAGE 5 -->

            <article class="gallery-card">

                <div class="gallery-image">

                    <!-- Add fellowship image here later -->

                </div>

                <div class="gallery-details">

                    <span class="gallery-category">
                        COMMUNITY
                    </span>

                    <h3>Brotherhood and Fellowship</h3>

                    <p>
                        Building unity, friendship, and fellowship
                        among fellow altar servers.
                    </p>

                </div>

            </article>


            <!-- IMAGE 6 -->

            <article class="gallery-card">

                <div class="gallery-image">

                    <!-- Add ministry motto image here later -->

                </div>

                <div class="gallery-details">

                    <span class="gallery-category">
                        MINISTRY COMMITMENT
                    </span>

                    <h3>Amare et Servire</h3>

                    <p>
                        Living our ministry motto through love,
                        faith, and dedicated service.
                    </p>

                </div>

            </article>


        </div>

    </section>


</main>


<?php

/*
|--------------------------------------------------------------------------
| LOAD SHARED FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/footer.php';

?>
