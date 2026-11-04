
<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| Permanent Member QR Card
| Supabase PostgreSQL
| St. Francis Xavier Cathedral
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| GET MEMBER ID
|--------------------------------------------------------------------------
*/

$memberId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$memberId || $memberId <= 0) {
    http_response_code(400);
    exit('Invalid member ID.');
}

/*
|--------------------------------------------------------------------------
| FETCH MEMBER FROM SUPABASE
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            member_code,
            full_name,
            sex,
            age,
            birth_date,
            address,
            contact_number,
            email,
            status,
            qr_token
        FROM public.members
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $memberId
    ]);

    $member = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log('QR card database error: ' . $e->getMessage());

    http_response_code(500);
    exit('Unable to retrieve member information.');
}

/*
|--------------------------------------------------------------------------
| CHECK MEMBER
|--------------------------------------------------------------------------
*/

if (!$member) {
    http_response_code(404);
    exit('Member not found.');
}

/*
|--------------------------------------------------------------------------
| VALIDATE PERMANENT QR TOKEN
|--------------------------------------------------------------------------
*/

if (
    empty($member['qr_token']) ||
    !preg_match('/^[a-f0-9]{64}$/', $member['qr_token'])
) {
    http_response_code(500);
    exit('This member does not have a valid QR token.');
}

/*
|--------------------------------------------------------------------------
| MEMBER INFORMATION
|--------------------------------------------------------------------------
*/

$fullName = $member['full_name'] ?? '';

$memberCode = $member['member_code'] ?? '';

$pageTitle = 'Member QR Card';
$activePage = 'members';

/*
|--------------------------------------------------------------------------
| HTML ESCAPE FUNCTION
|--------------------------------------------------------------------------
*/

function qrEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| SHARED HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>

<style>

/* PAGE LAYOUT */

.main-content {
    padding: 30px;
    background: #F8F5ED;
    min-height: 100vh;
    color: #172C46;
    font-family: 'Inter', sans-serif;
}

/* PAGE HEADING */

.page-heading {
    margin-bottom: 20px;
}

.page-heading h1 {
    font-family: 'Cinzel', serif;
    color: #172C46;
    font-size: 24px;
    margin: 0 0 8px;
}

.page-heading p {
    color: #888;
    font-size: 12px;
    margin: 0;
}

/* QR CARD */

.qr-card {
    width: 340px;
    max-width: 100%;
    margin: 25px auto;
    background: #FFFFFF;
    border: 2px solid #B18A43;
    border-radius: 14px;
    overflow: hidden;
    text-align: center;
    box-shadow: 0 8px 25px rgba(23, 44, 70, .10);
}

/* QR HEADER */

.qr-card-header {
    background: #172C46;
    color: #FFFFFF;
    padding: 20px 15px;
}

.qr-card-header h2 {
    font-family: 'Cinzel', serif;
    font-size: 17px;
    margin: 0 0 6px;
}

.qr-card-header p {
    font-size: 10px;
    margin: 0;
    letter-spacing: 1px;
    color: #E7D6B3;
}

/* QR BODY */

.qr-card-body {
    padding: 22px 20px;
}

.qr-member-name {
    font-family: 'Cinzel', serif;
    color: #172C46;
    font-size: 17px;
    font-weight: 700;
    margin: 0 0 7px;
}

.qr-member-code {
    color: #9A773A;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
}

/* QR IMAGE */

.qr-image {
    display: flex;
    justify-content: center;
    margin: 18px auto;
    min-height: 210px;
    align-items: center;
}

.qr-image img {
    max-width: 210px;
    height: auto;
}

/* QR NOTE */

.qr-card-note {
    color: #777;
    font-size: 10px;
    line-height: 1.6;
    margin-top: 12px;
}

/* QR FOOTER */

.qr-card-footer {
    background: #F8F5ED;
    color: #7A694E;
    padding: 12px;
    font-size: 10px;
    letter-spacing: 1px;
}

/* BUTTONS */

.qr-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    margin: 20px auto;
}

.qr-actions button,
.qr-actions a {
    border: 0;
    border-radius: 6px;
    padding: 11px 16px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}

.qr-print {
    background: #172C46;
    color: #FFFFFF;
}

.qr-print:hover {
    background: #243F60;
}

.qr-back {
    background: #EAE5DB;
    color: #444;
}

/* SUCCESS MESSAGE */

.success-message {
    max-width: 500px;
    margin: 15px auto;
    padding: 12px 15px;
    background: #EDF7EF;
    border: 1px solid #C9E5CE;
    border-radius: 6px;
    color: #356B40;
    font-size: 12px;
    text-align: center;
}

/* RESPONSIVE */

@media (max-width: 480px) {

    .main-content {
        padding: 20px 12px;
    }

    .qr-card {
        width: 100%;
    }

    .qr-actions {
        flex-direction: column;
    }

    .qr-actions button,
    .qr-actions a {
        width: 100%;
        box-sizing: border-box;
        text-align: center;
    }
}

/* PRINT DESIGN */

@media print {

    @page {
        size: 3.5in 5in;
        margin: 0.15in;
    }

    body {
        background: #FFFFFF !important;
    }

    .sidebar,
    .sidebar-overlay,
    .topbar,
    .page-footer,
    .qr-actions,
    .success-message,
    .page-heading {
        display: none !important;
    }

    .app-layout,
    .application-content,
    .main-content {
        display: block !important;
        width: 100% !important;
        min-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #FFFFFF !important;
    }

    .qr-card {
        margin: 0 auto !important;
        box-shadow: none !important;
        break-inside: avoid;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

</style>

<main class="main-content">

    <!-- PAGE HEADING -->

    <div class="page-heading">

        <h1>Permanent Member QR Card</h1>

        <p>
            This QR code remains assigned to this member
            for future Mass attendance.
        </p>

    </div>

    <!-- SUCCESS MESSAGE -->

    <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>

        <div class="success-message">
            Member successfully registered!
            Permanent QR code generated.
        </div>

    <?php endif; ?>

    <!-- MEMBER QR CARD -->

    <div class="qr-card">

        <div class="qr-card-header">

            <h2>ST. FRANCIS XAVIER CATHEDRAL</h2>

            <p>MINISTRY OF ALTAR SERVERS</p>

        </div>

        <div class="qr-card-body">

            <p class="qr-member-name">
                <?= qrEscape($fullName) ?>
            </p>

            <div class="qr-member-code">
                <?= qrEscape($memberCode) ?>
            </div>

            <div
                id="memberQRCode"
                class="qr-image"
                aria-label="Permanent member QR code"
            ></div>

            <div class="qr-card-note">

                Present this QR code to the authorized
                attendance scanner during your assigned Sunday Mass.

                <br>

                <strong>One member. One permanent QR code.</strong>

            </div>

        </div>

        <div class="qr-card-footer">

            AMARE ET SERVIRE — TO LOVE AND TO SERVE

        </div>

    </div>

    <!-- QR ACTIONS -->

    <div class="qr-actions">

        <button
            class="qr-print"
            type="button"
            onclick="window.print()"
        >
            PRINT QR CARD
        </button>

        <a
            class="qr-back"
            href="index.php"
        >
            BACK TO MEMBERS
        </a>

    </div>

</main>

<!-- QR CODE GENERATOR -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const qrContainer = document.getElementById('memberQRCode');

    const permanentToken = <?= json_encode(
        $member['qr_token'],
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) ?>;

    if (typeof QRCode === 'undefined') {

        qrContainer.textContent =
            'QR generator could not load. Check your internet connection.';

        return;
    }

    new QRCode(qrContainer, {

        text: permanentToken,

        width: 210,

        height: 210,

        colorDark: '#172C46',

        colorLight: '#FFFFFF',

        correctLevel: QRCode.CorrectLevel.H

    });

});

</script>

<?php

/*
|--------------------------------------------------------------------------
| SHARED FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/footer.php';

?>
