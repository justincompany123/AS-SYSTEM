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

// Already logged in? Go to dashboard.
redirectIfLoggedIn();

// Show loading screen only on the first login-page visit
// during this PHP session.
$showInitialLoading = empty($_SESSION['initial_loading_screen_shown']);

if ($showInitialLoading) {
    $_SESSION['initial_loading_screen_shown'] = true;
}

// Database connection
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

                // Regenerate session ID after successful login.
                if (!session_regenerate_id(true)) {
                    throw new RuntimeException(
                        'Unable to regenerate administrator session.'
                    );
                }

                // Save administrator information.
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_email'] = $admin['email'];

                // Tell the dashboard to display the loading screen.
                $_SESSION['show_loading_screen'] = true;

                // Save session before redirect.
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

    <link rel="icon" type="image/png" href="/assets/images/favicon.png">

    <link rel="stylesheet" href="/assets/css/loading-screen.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap"
          rel="stylesheet">

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

        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: var(--ivory);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .page-wrapper {
            width: 100%;
            max-width: 1050px;
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
                    rgba(16,33,55,.91),
                    rgba(16,33,55,.96)
                ),
                url('/assets/images/background.jpg') center/cover;

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

        .cross-emblem {
            width: 95px;
            height: 95px;
            border: 1px solid var(--gold);
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: var(--gold-light);
            font-family: 'Cinzel', serif;
            font-size: 54px;
            margin-bottom: 27px;
            box-shadow: 0 0 0 7px rgba(177,138,67,.08);
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

        /* MOBILE */

        @media(max-width: 750px) {

            body {
                padding: 15px;
            }

            .page-wrapper {
                grid-template-columns: 1fr;
                max-width: 460px;
            }

            .brand-panel {
                padding: 35px 20px;
            }

            .cross-emblem {
                width: 65px;
                height: 65px;
                font-size: 38px;
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

<div class="page-wrapper">

    <!-- CHURCH BRANDING -->

    <section class="brand-panel">

        <div class="cross-emblem">✝</div>

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
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="/login.php">

            <div class="form-group">

                <label for="username">USERNAME</label>

                <div class="input-wrapper">

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
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

<script src="/assets/js/loading-screen.js"></script>

</body>
</html>
