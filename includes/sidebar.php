<?php
/*
|--------------------------------------------------------------------------
| AS-SYSTEM
| SIDEBAR NAVIGATION
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

$currentPage = $activePage ?? 'dashboard';

// Navigation items
$navigationItems = [

    [
        'label' => 'Dashboard',
        'icon' => '⌂',
        'url' => '/index.php',
        'page' => 'dashboard'
    ],

    [
        'label' => 'Members',
        'icon' => '♙',
        'url' => '/members/index.php',
        'page' => 'members'
    ],

    [
        'label' => 'Register New Member',
        'icon' => '＋',
        'url' => '/members/add.php',
        'page' => 'add-member'
    ],

    [
        'label' => 'Archived Members',
        'icon' => '▤',
        'url' => '/members/archived.php',
        'page' => 'archived'
    ],

    [
        'label' => 'Attendance Records',
        'icon' => '◷',
        'url' => '/attendance/index.php',
        'page' => 'attendance'
    ],

    [
        'label' => 'QR Attendance Scanner',
        'icon' => '▦',
        'url' => '/attendance/scan.php',
        'page' => 'scanner'
    ]

];

?>

<!-- ==========================================
     SIDEBAR
=========================================== -->

<aside
    class="sidebar"
    id="sidebar"
>


    <!-- ======================================
         SIDEBAR BRAND
    ======================================= -->

    <div class="sidebar-brand">


        <!-- LOGO -->

        <div class="sidebar-logo">

            ✝

        </div>


        <!-- BRAND INFORMATION -->

        <div class="sidebar-brand-text">

            <h2>
                ALTAR SERVERS
            </h2>

            <span>
                ST. FRANCIS XAVIER CATHEDRAL
            </span>

        </div>

    </div>


    <!-- ======================================
         MAIN MENU LABEL
    ======================================= -->

    <div class="sidebar-section-label">

        MAIN MENU

    </div>


    <!-- ======================================
         NAVIGATION
    ======================================= -->

    <nav
        class="sidebar-nav"
        aria-label="Main navigation"
    >

        <?php foreach ($navigationItems as $item): ?>

            <?php

            $isActive = (
                $currentPage === $item['page']
            );

            ?>

            <a
                href="<?= htmlspecialchars(
                    $item['url'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="<?= $isActive ? 'active' : '' ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>
            >

                <span class="nav-icon">

                    <?= htmlspecialchars(
                        $item['icon'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

                <span class="nav-label">

                    <?= htmlspecialchars(
                        $item['label'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </a>

        <?php endforeach; ?>

    </nav>


    <!-- ======================================
         SIDEBAR BOTTOM
    ======================================= -->

    <div class="sidebar-bottom">


        <!-- MINISTRY MOTTO -->

        <div class="sidebar-motto">

            <strong>
                AMARE ET SERVIRE
            </strong>

            <span>
                To Love and To Serve
            </span>

        </div>


        <!-- LOGOUT -->

        <a
            href="/logout.php"
            class="sidebar-logout"
        >

            <span class="nav-icon">
                ↪
            </span>

            <span class="nav-label">
                Logout
            </span>

        </a>

    </div>

</aside>
