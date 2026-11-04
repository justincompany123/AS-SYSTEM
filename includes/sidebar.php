
<?php
$currentPage = $activePage ?? '';
?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">

        <div class="sidebar-logo">
            ✝
        </div>

        <div class="sidebar-brand-text">
            <h2>ALTAR SERVERS</h2>
            <span>ST. FRANCIS XAVIER CATHEDRAL</span>
        </div>

    </div>

    <div class="sidebar-section-label">
        MAIN MENU
    </div>

    <nav class="sidebar-nav">

        <a
            href="/AS-SYSTEM/index.php"
            class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>"
        >
            <span class="nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>

        <a
            href="/AS-SYSTEM/members/index.php"
            class="<?= in_array($currentPage, ['members', 'archived'], true) ? 'active' : '' ?>"
        >
            <span class="nav-icon">♙</span>
            <span>Members</span>
        </a>

        <a
            href="/AS-SYSTEM/members/archived.php"
            class="<?= $currentPage === 'archived' ? 'active' : '' ?>"
        >
            <span class="nav-icon">▤</span>
            <span>Archived Members</span>
        </a>

        <a
            href="/AS-SYSTEM/attendance/index.php"
            class="<?= $currentPage === 'attendance' ? 'active' : '' ?>"
        >
            <span class="nav-icon">◷</span>
            <span>Attendance Records</span>
        </a>

        <a
            href="/AS-SYSTEM/attendance/scan.php"
            class="<?= $currentPage === 'scanner' ? 'active' : '' ?>"
        >
            <span class="nav-icon">▦</span>
            <span>QR Attendance Scanner</span>
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="sidebar-motto">
            <strong>AMARE ET SERVIRE</strong>
            <span>To Love and To Serve</span>
        </div>

        <a href="/AS-SYSTEM/logout.php" class="sidebar-logout">
            <span class="nav-icon">↪</span>
            <span>Logout</span>
        </a>

    </div>

</aside>
