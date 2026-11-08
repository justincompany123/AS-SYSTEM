<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| ADMINISTRATOR LOGIN
| SUPABASE POSTGRESQL
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/auth.php';

// Redirect logged-in administrators.
redirectIfLoggedIn();

// Show loading screen only on the first login-page visit.
$showInitialLoading = empty($_SESSION['initial_loading_screen_shown']);

if ($showInitialLoading) {
    $_SESSION['initial_loading_screen_shown'] = true;
}

// Database connection.
require_once __DIR__ . '/config/database.php';

$error = '';
$username = '';

/*
|--------------------------------------------------------------------------
| PROCESS LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = 'Please enter your username and password.';

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    full_name,
                    username,
                    email,
                    password,
                    role,
                    status
                FROM public.admins
                WHERE username = :username
                LIMIT 1
            ");

            $stmt->execute([
                ':username' => $username
            ]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $admin &&
                $admin['status'] === 'Active' &&
                password_verify($password, $admin['password'])
            ) {

                if (!session_regenerate_id(true)) {
                    throw new RuntimeException(
                        'Unable to regenerate administrator session.'
                    );
                }

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_email'] = $admin['email'];

                $_SESSION['show_loading_screen'] = true;

                session_write_close();

                header('Location: /index.php');
                exit;

            } else {

                $error = 'Invalid username or password.';

            }

        } catch (PDOException $e) {

            error_log(
                'AS-SYSTEM LOGIN DATABASE ERROR: ' . $e->getMessage()
            );

            $error = 'Unable to process your login. Please try again.';

        } catch (Throwable $e) {

            error_log(
                'AS-SYSTEM LOGIN ERROR: ' . $e->getMessage()
            );

            $error = 'An unexpected error occurred. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrator Login | ALTAR SERVERS</title>

    <!-- Cathedral logo as favicon -->
    <link
        rel="icon"
        type="image/png"
        href="/assets/images/cathedral-logo.png"
    >

    <!-- Loading screen -->
    <link rel="stylesheet" href="/assets/css/loading-screen.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {
            --navy: #172c46;
            --navy-dark: #102137;
            --gold: #b18a43;
            --gold-light: #d8bd83;
            --ivory: #f8f5ed;
            --white: #ffffff;
            --text: #283444;
            --muted: #78818c;
            --border: #e7dfcf;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: var(--ivory);
            padding: 35px 20px 60px;
            color: var(--text);
        }

        .site-container {
            width: 100%;
            max-width: 1050px;
            margin: 0 auto;
        }

        /* =========================================
           LOGIN SECTION
        ========================================= */

        .page-wrapper {
            width: 100%;
            min-height: 610px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 15px 55px rgba(16,33,55,.12);
            border: 1px solid var(--border);
        }

        /* BRAND PANEL */

        .brand-panel {
            background:
                linear-gradient(
                    rgba(16,33,55,.86),
                    rgba(16,33,55,.94)
                ),
                url('/assets/images/background.jpg') center/cover no-repeat;

            color: white;
            padding: 55px 38px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
        }

        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            width: 65px;
            height: 65px;
            border-color: rgba(216,189,131,.5);
            border-style: solid;
        }

        .brand-panel::before {
            top: 20px;
            left: 20px;
            border-width: 1px 0 0 1px;
        }

        .brand-panel::after {
            right: 20px;
            bottom: 20px;
            border-width: 0 1px 1px 0;
        }

        .cathedral-logo {
            width: 105px;
            height: 105px;
            object-fit: contain;
            margin-bottom: 25px;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,.25));
        }

        .cathedral-name {
            font-size: 11px;
            letter-spacing: 3px;
            color: var(--gold-light);
            text-transform: uppercase;
            margin-bottom: 17px;
        }

        .brand-panel h1 {
            font-family: 'Cinzel', serif;
            font-size: clamp(25px, 3vw, 35px);
            font-weight: 500;
            letter-spacing: 2px;
            line-height: 1.5;
        }

        .subtitle {
            font-size: 12px;
            color: #d7dce4;
            letter-spacing: 2px;
            margin-top: 12px;
            line-height: 1.8;
        }

        .brand-divider {
            width: 85px;
            height: 1px;
            background: var(--gold);
            margin: 28px auto;
        }

        .brand-motto {
            font-family: 'Cinzel', serif;
            font-size: 16px;
            color: var(--gold-light);
            letter-spacing: 3px;
        }

        .brand-translation {
            font-size: 11px;
            color: #c4ccd6;
            margin-top: 9px;
            font-style: italic;
        }

        /* LOGIN PANEL */

        .login-panel {
            padding: 65px 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-heading {
            margin-bottom: 35px;
        }

        .small-title {
            color: var(--gold);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 13px;
        }

        .login-heading h2 {
            color: var(--navy);
            font-family: 'Cinzel', serif;
            font-size: 25px;
            font-weight: 600;
        }

        .login-heading p {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.7;
            margin-top: 10px;
        }

        .error-message {
            background: #fff1ef;
            color: #9c3028;
            border: 1px solid #efc6c0;
            border-radius: 5px;
            padding: 12px 14px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 9px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            height: 49px;
            padding: 0 15px;
            border: 1px solid var(--border);
            border-radius: 5px;
            background: #fffefa;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            outline: none;
            transition: .2s;
        }

        .input-wrapper input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(177,138,67,.10);
        }

        .password-input {
            padding-right: 65px !important;
        }

        .toggle-password {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--gold);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        .login-button {
            width: 100%;
            height: 50px;
            background: var(--navy);
            color: white;
            border: 1px solid var(--gold);
            border-radius: 5px;
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 2px;
            cursor: pointer;
            transition: .2s;
            margin-top: 8px;
        }

        .login-button:hover {
            background: var(--navy-dark);
            color: var(--gold-light);
        }

        .login-footer {
            border-top: 1px solid var(--border);
            margin-top: 35px;
            padding-top: 22px;
            text-align: center;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.8;
        }

        .login-footer strong {
            font-family: 'Cinzel', serif;
            color: var(--gold);
            letter-spacing: 1px;
        }

        /* =========================================
           ANNOUNCEMENT
        ========================================= */

        .announcement-section {
            margin-top: 35px;
        }

        .announcement-card {
            background: #ffffff;
            border: 1px solid #e8e2d7;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
        }

        .announcement-top {
            background: #263c32;
            color: white;
            padding: 23px 28px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .announcement-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,.13);
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
            line-height: 1.5;
            color: white;
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
            margin-bottom: 16px;
        }

        .announcement-body > p {
            color: #606060;
            font-size: 14px;
            line-height: 1.9;
            margin-bottom: 20px;
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
            padding: 16px;
        }

        .application-item.full-width {
            grid-column: 1 / -1;
        }

        .application-item strong {
            display: block;
            font-size: 11px;
            letter-spacing: 1px;
            color: #947343;
            margin-bottom: 8px;
        }

        .application-item p {
            font-size: 13px;
            color: #555;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .announcement-about {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eeeae2;
        }

        .announcement-about h3 {
            margin-top: 0;
        }

        .announcement-about p {
            color: #606060;
            font-size: 13px;
            line-height: 1.9;
            margin-bottom: 13px;
        }

        .announcement-footer {
            background: #faf9f6;
            border-top: 1px solid #eeeae2;
            padding: 17px 28px;
            color: #947343;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        /* =========================================
           MASS SCHEDULE IMAGE
        ========================================= */

        .mass-schedule-section {
            margin-top: 42px;
        }

        .section-heading {
            margin-bottom: 18px;
        }

        .section-heading h2 {
            font-family: 'Cinzel', serif;
            color: var(--navy);
            font-size: 23px;
            margin-bottom: 8px;
        }

        .section-heading p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.7;
        }

        .mass-schedule-image {
            width: 100%;
            background: #ffffff;
            border: 1px solid #e3e3e3;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,.04);
        }

        .mass-schedule-image img {
            display: block;
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        /* =========================================
           MINISTRY GALLERY
        ========================================= */

        .ministry-gallery {
            margin-top: 45px;
        }

        .gallery-heading {
            margin-bottom: 22px;
        }

        .gallery-heading h2 {
            font-family: 'Cinzel', serif;
            color: var(--navy);
            font-size: 23px;
            margin-bottom: 8px;
        }

        .gallery-heading p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.7;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .gallery-card {
            background: #fff;
            border: 1px solid #e9e4da;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,.04);
            transition: .25s ease;
        }

        .gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 9px 24px rgba(0,0,0,.08);
        }

        .gallery-image {
            width: 100%;
            height: 205px;
            background: #f0f1f2;
            border-bottom: 1px solid #e8e8e8;
            overflow: hidden;
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            transition: transform .35s ease;
        }

        .gallery-card:hover .gallery-image img {
            transform: scale(1.04);
        }

        .gallery-info {
            padding: 18px;
        }

        .gallery-category {
            display: inline-block;
            color: #947343;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 9px;
        }

        .gallery-info h3 {
            color: var(--navy);
            font-size: 16px;
            margin-bottom: 9px;
        }

        .gallery-info p {
            color: #777;
            font-size: 12px;
            line-height: 1.8;
        }

        /* =========================================
           PAGE FOOTER
        ========================================= */

        .page-bottom-footer {
            text-align: center;
            margin-top: 45px;
            padding: 22px 10px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 10px;
            line-height: 1.9;
        }

        .page-bottom-footer strong {
            font-family: 'Cinzel', serif;
            color: var(--gold);
            letter-spacing: 2px;
        }

        /* =========================================
           RESPONSIVE DESIGN
        ========================================= */

        @media(max-width: 850px) {

            .gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media(max-width: 750px) {

            body {
                padding: 18px 12px 40px;
            }

            .page-wrapper {
                grid-template-columns: 1fr;
                max-width: 460px;
                margin: auto;
            }

            .brand-panel {
                padding: 35px 20px;
            }

            .cathedral-logo {
                width: 75px;
                height: 75px;
                margin-bottom: 17px;
            }

            .brand-panel h1 {
                font-size: 24px;
            }

            .brand-divider {
                margin: 17px auto;
            }

            .brand-motto {
                font-size: 13px;
            }

            .login-panel {
                padding: 38px 27px;
            }

            .login-heading {
                margin-bottom: 25px;
            }

            .announcement-section {
                margin-top: 25px;
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

        }

        @media(max-width: 500px) {

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .gallery-image {
                height: 220px;
            }

            .section-heading h2,
            .gallery-heading h2 {
                font-size: 20px;
            }

        }

    </style>

</head>

<body>


<?php if ($showInitialLoading): ?>

    <div id="loading-screen">

        <h1 class="loading-title">
            THE KABANKALAN CATHEDRAL MINISTRY OF THE ALTAR SERVERS SYSTEM
        </h1>

    </div>

<?php endif; ?>


<div class="site-container">

    <!-- ==========================================
         LOGIN SECTION
    =========================================== -->

    <div class="page-wrapper">

        <!-- CHURCH BRANDING -->

        <section class="brand-panel">

            <img
                src="/assets/images/cathedral-logo.png"
                alt="St. Francis Xavier Cathedral Logo"
                class="cathedral-logo"
            >

            <div class="cathedral-name">
                St. Francis Xavier Cathedral
            </div>

            <h1>ALTAR SERVERS</h1>

            <p class="subtitle">
                MINISTRY MANAGEMENT SYSTEM
            </p>

            <div class="brand-divider"></div>

            <div class="brand-motto">
                AMARE ET SERVIRE
            </div>

            <div class="brand-translation">
                To Love and To Serve
            </div>

        </section>


        <!-- LOGIN FORM -->

        <section class="login-panel">

            <div class="login-heading">

                <div class="small-title">
                    Administrator Portal
                </div>

                <h2>Welcome Back</h2>

                <p>
                    Sign in to access the Altar Servers Management System
                    and manage ministry activities.
                </p>

            </div>


            <?php if ($error !== ''): ?>

                <div class="error-message">

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- The current website URL is /login.php. -->

            <form method="POST" action="/login.php">

                <div class="form-group">

                    <label for="username">USERNAME</label>

                    <div class="input-wrapper">

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            value="<?= htmlspecialchars(
                                $username,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            autocomplete="username"
                            required
                            autofocus
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="password">PASSWORD</label>

                    <div class="input-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="password-input"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                        >
                            SHOW
                        </button>

                    </div>

                </div>


                <button type="submit" class="login-button">
                    SIGN IN
                </button>

            </form>


            <footer class="login-footer">

                <strong>AMARE ET SERVIRE</strong><br>

                St. Francis Xavier Cathedral<br>

                ALTAR SERVERS WEB &copy; <?= date('Y') ?>

            </footer>

        </section>

    </div>


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
                            Visit the official Facebook page
                            of the Ministry of Altar Servers.
                        </p>

                        <!-- Add your verified Facebook page link here. -->

                    </div>

                </div>


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

        <div class="section-heading">

            <h2>MASS SCHEDULE</h2>

            <p>
                View the official Mass schedule of
                St. Francis Xavier Cathedral.
            </p>

        </div>


        <div class="mass-schedule-image">

            <img
                src="/assets/images/mass-schedule.png"
                alt="Official Mass Schedule of St. Francis Xavier Cathedral"
                loading="lazy"
            >

        </div>

    </section>


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

            <!-- IMAGE 1: OUR MINISTRY -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <img
                        src="/assets/images/ministry.jpg"
                        alt="St. Francis Xavier Cathedral Ministry of Altar Servers"
                        loading="lazy"
                    >

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


            <!-- IMAGE 2: SERVING AT THE ALTAR -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <img
                        src="/assets/images/altar-service.jpg"
                        alt="Altar servers serving during Holy Mass"
                        loading="lazy"
                    >

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


            <!-- IMAGE 3: TRAINING AND FORMATION -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <img
                        src="/assets/images/formation.jpg"
                        alt="Altar Servers Training and Formation"
                        loading="lazy"
                    >

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


            <!-- IMAGE 4: LITURGICAL CELEBRATIONS -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <img
                        src="/assets/images/celebrations.jpg"
                        alt="Church and Liturgical Celebrations"
                        loading="lazy"
                    >

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


            <!-- IMAGE 5: BROTHERHOOD AND FELLOWSHIP -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <img
                        src="/assets/images/fellowship.jpg"
                        alt="Brotherhood and Fellowship of Altar Servers"
                        loading="lazy"
                    >

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


            <!-- IMAGE 6: AMARE ET SERVIRE -->

            <div class="gallery-card">

                <div class="gallery-image">

                    <img
                        src="/assets/images/amare-et-servire.jpg"
                        alt="Amare et Servire - To Love and To Serve"
                        loading="lazy"
                    >

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


    <!-- ==========================================
         PAGE FOOTER
    =========================================== -->

    <footer class="page-bottom-footer">

        <strong>AMARE ET SERVIRE</strong>

        <p>
            St. Francis Xavier Cathedral
        </p>

        <p>
            Ministry of Altar Servers &copy; <?= date('Y') ?>
        </p>

    </footer>

</div>


<!-- ==========================================
     PASSWORD VISIBILITY
=========================================== -->

<script>

const toggleButton = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

if (toggleButton && passwordInput) {

    toggleButton.addEventListener('click', function () {

        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';

        toggleButton.textContent = isPassword ? 'HIDE' : 'SHOW';

    });

}

</script>


<!-- LOADING SCREEN SCRIPT -->

<script src="/assets/js/loading-screen.js"></script>

</body>
</html>
