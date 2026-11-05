<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| PERMANENT MEMBER QR CARD
| SUPABASE POSTGRESQL
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

// AUTHENTICATION
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// DATABASE
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| GET MEMBER ID
|--------------------------------------------------------------------------
*/

$memberId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$memberId || $memberId <= 0) {
    http_response_code(400);
    exit('Invalid member ID.');
}

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
| FETCH MEMBER INFORMATION
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

    error_log(
        'QR card database error: ' . $e->getMessage()
    );

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
| VALIDATE QR TOKEN
|--------------------------------------------------------------------------
*/

$qrToken = $member['qr_token'] ?? '';

if (
    !is_string($qrToken) ||
    !preg_match('/^[a-f0-9]{64}$/i', $qrToken)
) {
    http_response_code(500);
    exit('This member does not have a valid QR token.');
}

/*
|--------------------------------------------------------------------------
| PAGE INFORMATION
|--------------------------------------------------------------------------
*/

$pageTitle = 'Permanent Member QR Card';
$activePage = 'members';

$fullName = qrEscape($member['full_name']);
$memberCode = qrEscape($member['member_code']);

/*
|--------------------------------------------------------------------------
| SAFELY ENCODE QR TOKEN
|--------------------------------------------------------------------------
*/

$qrTokenJson = json_encode(
    $qrToken,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);

if ($qrTokenJson === false) {
    http_response_code(500);
    exit('Unable to prepare the QR code.');
}

/*
|--------------------------------------------------------------------------
| SHARED HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>

<main class="main-content">

    <div class="content-container">

        <!-- PAGE HEADING -->

        <div class="page-heading">

            <div>

                <span class="page-eyebrow">
                    MEMBER MANAGEMENT
                </span>

                <h1>
                    Permanent Member QR Card
                </h1>

                <p>
                    This QR code remains assigned to this member
                    for future Sunday Mass attendance.
                </p>

            </div>

        </div>

        <!-- SUCCESS MESSAGE -->

        <?php if (
            isset($_GET['created']) &&
            $_GET['created'] === '1'
        ): ?>

            <div class="success-message">

                Member successfully registered!
                Permanent QR code generated.

            </div>

        <?php endif; ?>

        <!-- QR CARD -->

        <section class="qr-card">

            <div class="qr-card-header">

                <h2>
                    ST. FRANCIS XAVIER CATHEDRAL
                </h2>

                <p>
                    MINISTRY OF ALTAR SERVERS
                </p>

            </div>

            <div class="qr-card-body">

                <p class="qr-member-name">
                    <?= $fullName ?>
                </p>

                <div class="qr-member-code">
                    <?= $memberCode ?>
                </div>

                <!-- QR CODE -->

                <div
                    id="memberQRCode"
                    class="qr-image"
                    aria-label="Permanent member QR code"
                    role="img"
                >

                    <span>
                        Generating QR code...
                    </span>

                </div>

                <div class="qr-card-note">

                    Present this QR code to the authorized
                    attendance scanner during Sunday Mass.

                    <br><br>

                    <strong>
                        One member. One permanent QR code.
                    </strong>

                </div>

            </div>

            <div class="qr-card-footer">

                AMARE ET SERVIRE — TO LOVE AND TO SERVE

            </div>

        </section>

        <!-- ACTION BUTTONS -->

        <div class="qr-actions">

            <button
                class="qr-print"
                type="button"
                onclick="window.print()"
            >
                PRINT QR CARD
            </button>

            <!-- CORRECTED BACK LINK -->

            <a
                class="qr-back"
                href="/members/add.php"
            >
                BACK TO ADD MEMBER
            </a>

        </div>

    </div>

</main>

<!-- QR CODE LIBRARY -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const qrContainer =
        document.getElementById('memberQRCode');

    const permanentToken = <?= $qrTokenJson ?>;

    if (!qrContainer) {
        return;
    }

    if (typeof QRCode === 'undefined') {

        qrContainer.textContent =
            'Unable to load QR generator. Please refresh the page.';

        return;
    }

    qrContainer.innerHTML = '';

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
