
<?php

// =====================================================
// ALTAR SERVERS MANAGEMENT SYSTEM
// MAIN ENTRY POINT
// =====================================================

// IMPORTANT: No HTML or whitespace before this PHP tag.

require_once __DIR__ . '/includes/auth.php';

// Check administrator login
requireLogin();

// Database connection
require_once __DIR__ . '/config/database.php';

// Current administrator information
$adminName = htmlspecialchars(
    getAdminName(),
    ENT_QUOTES,
    'UTF-8'
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Altar Servers Management System</title>

    <link rel="stylesheet"
          href="/assets/css/style.css">

</head>

<body>

    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Altar Servers Management System</h1>

                <p>St. Francis Xavier Cathedral</p>
            </div>

            <div class="admin-profile">

                <span>
                    Welcome, <?php echo $adminName; ?>
                </span>

                <a href="/logout.php">
                    Logout
                </a>

            </div>

        </header>

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
