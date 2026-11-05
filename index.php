<?php

// =====================================================
// ALTAR SERVERS MANAGEMENT SYSTEM
// MAIN ENTRY POINT
// =====================================================

// Start authentication and session
require_once __DIR__ . '/includes/auth.php';

// Check administrator login
requireLogin();

// Connect to Supabase database
require_once __DIR__ . '/config/database.php';

// Get current administrator information
$adminName = htmlspecialchars(
    getAdminName(),
    ENT_QUOTES,
    'UTF-8'
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<link rel="icon" type="image/png" href="/assets/images/favicon.png">

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Altar Servers Management System</title>

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Optional Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">

</head>

<body>

    <main class="main-content">

        <!-- HEADER -->
        <header class="topbar">

            <div>
                <h1>Altar Servers Management System</h1>

                <p>St. Francis Xavier Cathedral</p>
            </div>

            <div class="admin-profile">

                <span>
                    Welcome, <?php echo $adminName; ?>
                </span>

                <a href="logout.php">
                    Logout
                </a>

            </div>

        </header>

        <!-- DASHBOARD CONTENT -->
        <section class="dashboard-content">

            <h2>Welcome to the Dashboard</h2>

            <p>
                Ministry of Altar Servers Management System
            </p>

            <p>
                Amare et Servire
            </p>

        </section>

    </main>

</body>

</html>
