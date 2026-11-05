<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| Shared Page Header
| St. Francis Xavier Cathedral
| Theme: Cathedral Navy, Gold, and Ivory
|--------------------------------------------------------------------------
*/

// Start session if not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default page information.
$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';

// Administrator information.
$adminName = $_SESSION['admin_name']
    ?? $_SESSION['username']
    ?? 'Administrator';

$safeAdminName = htmlspecialchars(
    $adminName,
    ENT_QUOTES,
    'UTF-8'
);

$adminInitial = strtoupper(substr(trim($adminName), 0, 1));

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

    <meta name="description" content="St. Francis Xavier Cathedral Ministry of Altar Servers Management System">

    <title>
        <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
        | Altar Servers Management System
    </title>

    <!-- GOOGLE FONTS -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="/AS-SYSTEM/assets/css/style.css"
    >

    <!-- PRINT CSS -->

    <?php if (!empty($usePrintStylesheet)): ?>

        <link
            rel="stylesheet"
            href="/AS-SYSTEM/assets/css/print.css"
        >

    <?php endif; ?>

    <!-- OPTIONAL PAGE-SPECIFIC CSS -->

    <?php if (!empty($additionalStylesheet)): ?>

        <link
            rel="stylesheet"
            href="<?= htmlspecialchars($additionalStylesheet, ENT_QUOTES, 'UTF-8') ?>"
        >

    <?php endif; ?>

</head>

<body>

    <!-- MAIN APPLICATION LAYOUT -->

    <div class="app-layout">

        <!-- SIDEBAR OVERLAY FOR MOBILE -->

        <div class="sidebar-overlay"></div>

        <!-- SIDEBAR -->

        <?php
        $sidebarFile = __DIR__ . '/sidebar.php';

        if (file_exists($sidebarFile)) {
            require_once $sidebarFile;
        }
        ?>

        <!-- MAIN CONTENT AREA -->

        <div class="application-content" style="flex: 1; min-width: 0;">

            <!-- TOP NAVIGATION BAR -->

            <header class="topbar">

                <!-- LEFT SIDE -->

                <div style="display: flex; align-items: center; gap: 12px;">

                    <!-- MOBILE MENU BUTTON -->

                    <button
                        type="button"
                        class="mobile-menu-button"
                        aria-label="Open navigation menu"
                        aria-expanded="false"
                    >
                        ☰
                    </button>

                    <!-- CATHEDRAL NAME -->

                    <div>

                        <div class="topbar-title">
                            ST. FRANCIS XAVIER CATHEDRAL
                        </div>

                        <div style="font-size: 10px; color: #888;">
                            Ministry of Altar Servers
                        </div>

                    </div>

                </div>

                <!-- ADMINISTRATOR PROFILE -->

                <div class="admin-profile">

                    <div class="admin-avatar">
                        <?= htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <div>

                        <strong>
                            <?= $safeAdminName ?>
                        </strong>

                        <div style="font-size: 10px; color: #888;">
                            Administrator
                        </div>

                    </div>

                </div>

            </header>

            <!-- PAGE CONTENT STARTS BELOW -->
