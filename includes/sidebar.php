
<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| SIDEBAR NAVIGATION
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

$currentPage = $activePage ?? '';

?>

<aside class="sidebar" id="sidebar">

    <!-- SIDEBAR BRAND -->
    <div class="sidebar-brand">

        <div class="sidebar-logo">
            ✝
        </div>

        <div class="sidebar-brand-text">
            <h2>ALTAR SERVERS</h2>
            <span>ST. FRANCIS XAVIER CATHEDRAL</span>
        </div>

    </div>

    <!-- MAIN MENU LABEL -->
    <div class="sidebar-section-label">
        MAIN MENU
    </div>

    <!-- NAVIGATION -->
    <nav class="sidebar-nav">

        <!-- DASHBOARD -->
        <a
            href="/index.php"
            class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>"
        >
            <span class="nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>

        <!-- MEMBERS -->
        <a
            href="/members/index.php"
            class="<?= $currentPage === 'members' ? 'active' : '' ?>"
        >
            <span class="nav-icon">♙</span>
            <span>Members</span>
        </a>

        <!-- ARCHIVED MEMBERS -->
        <a
            href="/members/archived.php"
            class="<?= $currentPage === 'archived' ? 'active' : '' ?>"
        >
            <span class="nav-icon">▤</span>
            <span>Archived Members</span>
        </a>

        <!-- ATTENDANCE RECORDS -->
        <a
            href="/attendance/index.php"
            class="<?= $currentPage === 'attendance' ? 'active' : '' ?>"
        >
            <span class="nav-icon">◷</span>
            <span>Attendance Records</span>
        </a>

        <!-- QR ATTENDANCE SCANNER -->
        <a
            href="/attendance/scan.php"
            class="<?= $currentPage === 'scanner' ? 'active' : '' ?>"
        >
            <span class="nav-icon">▦</span>
            <span>QR Attendance Scanner</span>
        </a>

    </nav>

    <!-- SIDEBAR BOTTOM -->
    <div class="sidebar-bottom">

        <div class="sidebar-motto">
            <strong>AMARE ET SERVIRE</strong>
            <span>To Love and To Serve</span>
        </div>

        <!-- LOGOUT -->
        <a href="/logout.php" class="sidebar-logout">
            <span class="nav-icon">↪</span>
            <span>Logout</span>
        </a>

    </div>

</aside>
