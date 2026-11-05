<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| SHARED PAGE HEADER
| ST. FRANCIS XAVIER CATHEDRAL
| Theme: Cathedral Navy, Gold, and Ivory
|--------------------------------------------------------------------------
*/

// Session is managed by includes/auth.php.
// Do not call session_start() here.

// Default page information.
$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';

// Administrator information.
$adminName = $_SESSION['admin_name']
    ?? $_SESSION['username']
    ?? 'Administrator';

$adminName = (string) $adminName;

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
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#172C46">

    <meta
        name="description"
        content="St. Francis Xavier Cathedral Ministry of Altar Servers Management System"
    >

    <title>
        <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
        | Altar Servers Management System
    </title>

    <!-- GOOGLE FONTS -->

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

    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="/assets/css/style.css"
    >

    <!-- PRINT CSS -->

    <?php if (!empty($usePrintStylesheet)): ?>

        <link
            rel="stylesheet"
            href="/assets/css/print.css"
        >

    <?php endif; ?>

    <!-- OPTIONAL PAGE-SPECIFIC CSS -->

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

<body>

    <!-- MAIN APPLICATION LAYOUT -->

    <div class="app-layout">

        <!-- MOBILE SIDEBAR OVERLAY -->

        <div class="sidebar-overlay"></div>

        <!-- SIDEBAR -->

        <?php

        $sidebarFile = __DIR__ . '/sidebar.php';

        if (file_exists($sidebarFile)) {
            require_once $sidebarFile;
        }

        ?>

        <!-- MAIN APPLICATION CONTENT -->

        <div class="application-content">

            <!-- TOP NAVIGATION BAR -->

            <header class="topbar">

                <!-- LEFT SIDE -->

                <div class="topbar-left">

                    <!-- MOBILE MENU BUTTON -->

                    <button
                        type="button"
                        class="mobile-menu-button"
                        aria-label="Open navigation menu"
                        aria-expanded="false"
                    >
                        ☰
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

                <!-- ADMINISTRATOR PROFILE -->

                <div class="admin-profile">

                    <div class="admin-avatar">
                        <?= htmlspecialchars(
                            $adminInitial,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <div class="admin-information">

                        <strong>
                            <?= $safeAdminName ?>
                        </strong>

                        <div class="admin-role">
                            Administrator
                        </div>

                    </div>

                    <a
                        href="/logout.php"
                        class="header-logout"
                    >
                        Logout
                    </a>

                </div>

            </header>

            <!-- PAGE CONTENT STARTS HERE -->
