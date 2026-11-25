<?php
/*
|--------------------------------------------------------------------------
| AS-SYSTEM
| SHARED PAGE HEADER
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
|
| Requirements:
| - Authentication must be handled before including this file.
| - Session must already be active.
| - Each page should define $pageTitle and $activePage.
|
*/

// Default page information
$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';

// Administrator information
$adminName = $_SESSION['admin_name']
    ?? $_SESSION['username']
    ?? 'Administrator';

$adminName = trim((string) $adminName);

if ($adminName === '') {
    $adminName = 'Administrator';
}

$safeAdminName = htmlspecialchars(
    $adminName,
    ENT_QUOTES,
    'UTF-8'
);

$adminInitial = strtoupper(
    substr($adminName, 0, 1)
);

// Loading screen session flag
$showLoadingScreen = !empty(
    $_SESSION['show_loading_screen']
);

if ($showLoadingScreen) {
    unset($_SESSION['show_loading_screen']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="#263C32"
    >

    <meta
        name="description"
        content="St. Francis Xavier Cathedral Ministry of Altar Servers Management System"
    >

    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
        | Altar Servers Management System
    </title>


    <!-- ======================================
         GOOGLE FONTS
    ======================================= -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- ======================================
         MAIN STYLESHEET
    ======================================= -->

    <link
        rel="stylesheet"
        href="/assets/css/style.css?v=2"
    >


    <!-- ======================================
         LOADING SCREEN STYLESHEET
    ======================================= -->

    <link
        rel="stylesheet"
        href="/assets/css/loading-screen.css?v=2"
    >


    <!-- ======================================
         PRINT STYLESHEET
    ======================================= -->

    <?php if (!empty($usePrintStylesheet)): ?>

        <link
            rel="stylesheet"
            href="/assets/css/print.css"
        >

    <?php endif; ?>


    <!-- ======================================
         OPTIONAL PAGE-SPECIFIC STYLESHEET
    ======================================= -->

    <?php if (!empty($additionalStylesheet)): ?>

        <link
            rel="stylesheet"
            href="<?= htmlspecialchars(
                $additionalStylesheet,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    <?php endif; ?>

</head>


<body class="admin-page">


    <!-- ======================================
         LOADING SCREEN
    ======================================= -->

    <?php if ($showLoadingScreen): ?>

        <div id="loading-screen">

            <h1 class="loading-title">
                THE KABANKALAN CATHEDRAL MINISTRY OF THE ALTAR SERVERS SYSTEM
            </h1>

        </div>

    <?php endif; ?>


    <!-- ======================================
         MAIN APPLICATION LAYOUT
    ======================================= -->

    <div class="app-layout">


        <!-- MOBILE SIDEBAR OVERLAY -->

        <div
            class="sidebar-overlay"
            id="sidebarOverlay"
        ></div>


        <!-- ==================================
             SIDEBAR
        =================================== -->

        <?php

        $sidebarFile = __DIR__ . '/sidebar.php';

        if (is_file($sidebarFile)) {

            require $sidebarFile;

        } else {

            error_log(
                'AS-SYSTEM ERROR: includes/sidebar.php was not found.'
            );

        }

        ?>


        <!-- ==================================
             APPLICATION CONTENT
        =================================== -->

        <div class="application-content">


            <!-- ==================================
                 TOP NAVIGATION BAR
            =================================== -->

            <header class="topbar">


                <!-- LEFT SIDE -->

                <div class="topbar-left">


                    <!-- MOBILE MENU BUTTON -->

                    <button
                        type="button"
                        class="mobile-menu-button"
                        id="mobileMenuButton"
                        aria-label="Open navigation menu"
                        aria-expanded="false"
                        aria-controls="sidebar"
                    >

                        <span>☰</span>

                    </button>


                    <!-- CATHEDRAL INFORMATION -->

                    <div class="cathedral-heading">

                        <div class="topbar-title">

                            ST. FRANCIS XAVIER CATHEDRAL

                        </div>

                        <div class="topbar-subtitle">

                            Ministry of Altar Servers

                        </div>

                    </div>

                </div>


                <!-- ==================================
                     ADMINISTRATOR PROFILE
                =================================== -->

                <div class="admin-profile">


                    <!-- ADMIN AVATAR -->

                    <div class="admin-avatar">

                        <?= htmlspecialchars(
                            $adminInitial,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>


                    <!-- ADMIN INFORMATION -->

                    <div class="admin-information">

                        <strong>
                            <?= $safeAdminName ?>
                        </strong>

                        <div class="admin-role">
                            Administrator
                        </div>

                    </div>


                    <!-- LOGOUT -->

                    <a
                        href="/logout.php"
                        class="header-logout"
                    >

                        Logout

                    </a>

                </div>

            </header>


            <!-- ==================================
                 PAGE CONTENT STARTS HERE
            =================================== -->
