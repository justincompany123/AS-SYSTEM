
<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$memberId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$memberId) {
    http_response_code(400);
    exit('Invalid member ID.');
}

$stmt = $pdo->prepare("
    SELECT id, member_code, qr_token, first_name, middle_name,
           last_name, member_status
    FROM members
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $memberId]);
$member = $stmt->fetch();

if (!$member) {
    http_response_code(404);
    exit('Member not found.');
}

if (empty($member['qr_token']) || !preg_match('/^[a-f0-9]{64}$/', $member['qr_token'])) {
    http_response_code(500);
    exit('This member does not have a valid QR token.');
}

$fullName = trim(
    $member['first_name'] . ' ' .
    ($member['middle_name'] ? $member['middle_name'] . ' ' : '') .
    $member['last_name']
);

$pageTitle = 'Member QR Card';
$activePage = 'members';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.main-content { padding:30px; background:#F8F5ED; min-height:100vh; }
.qr-card { width:340px; max-width:100%; margin:25px auto; background:#fff; border:2px solid #B18A43; border-radius:14px; overflow:hidden; text-align:center; box-shadow:0 8px 25px rgba(23,44,70,.10); }
.qr-card-header { background:#172C46; color:#fff; padding:20px 15px; }
.qr-card-header h2 { font-family:'Cinzel',serif; font-size:17px; margin:0 0 6px; }
.qr-card-header p { font-size:10px; margin:0; letter-spacing:1px; color:#E7D6B3; }
.qr-card-body { padding:22px 20px; }
.qr-member-name { font-family:'Cinzel',serif; color:#172C46; font-size:17px; font-weight:700; margin:0 0 7px; }
.qr-member-code { color:#9A773A; font-size:11px; font-weight:700; letter-spacing:1px; }
.qr-image { display:flex; justify-content:center; margin:18px auto; min-height:210px; align-items:center; }
.qr-image img { max-width:210px; height:auto; }
.qr-card-note { color:#777; font-size:10px; line-height:1.6; margin-top:12px; }
.qr-card-footer { background:#F8F5ED; color:#7A694E; padding:12px; font-size:10px; letter-spacing:1px; }
.qr-actions { display:flex; justify-content:center; gap:10px; flex-wrap:wrap; margin:20px auto; }
.qr-actions button,.qr-actions a { border:0; border-radius:6px; padding:11px 16px; font-size:12px; font-weight:700; text-decoration:none; cursor:pointer; }
.qr-print { background:#172C46; color:#fff; }
.qr-back { background:#EAE5DB; color:#444; }

@media print {
    @page { size: 3.5in 5in; margin:0.15in; }
    body { background:#fff!important; }
    .sidebar,.sidebar-overlay,.topbar,.page-footer,.qr-actions { display:none!important; }
    .app-layout,.application-content,.main-content { display:block!important; width:100%!important; min-height:0!important; padding:0!important; margin:0!important; background:#fff!important; }
    .qr-card { margin:0 auto!important; box-shadow:none!important; break-inside:avoid; }
}
</style>

<main class="main-content">

    <div class="page-heading">
        <h1>Permanent Member QR Card</h1>
        <p>This QR code remains assigned to this member for future Mass attendance.</p>
    </div>

    <div class="qr-card">

        <div class="qr-card-header">
            <h2>ST. FRANCIS XAVIER CATHEDRAL</h2>
            <p>MINISTRY OF ALTAR SERVERS</p>
        </div>

        <div class="qr-card-body">

            <p class="qr-member-name"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></p>

            <div class="qr-member-code">
                <?= htmlspecialchars($member['member_code'], ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div
                id="memberQRCode"
                class="qr-image"
                aria-label="Permanent member QR code"
            ></div>

            <div class="qr-card-note">
                Present this QR code to the authorized attendance scanner
                during your assigned Sunday Mass.
                <br><strong>One member. One permanent QR code.</strong>
            </div>

        </div>

        <div class="qr-card-footer">
            AMARE ET SERVIRE — TO LOVE AND TO SERVE
        </div>

    </div>

    <div class="qr-actions">
        <button class="qr-print" type="button" onclick="window.print()">PRINT QR CARD</button>
        <a class="qr-back" href="index.php">BACK TO MEMBERS</a>
    </div>

</main>

<!-- QRCode.js generates the image locally in the browser. -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const qrContainer = document.getElementById('memberQRCode');
    const permanentToken = <?= json_encode($member['qr_token']) ?>;

    if (typeof QRCode === 'undefined') {
        qrContainer.textContent = 'QR generator could not load. Check your internet connection.';
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
