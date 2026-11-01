
<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| View Member Profile
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Member Profile';
$activePage = 'members';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM members
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([':id' => $id]);

$member = $stmt->fetch();

if (!$member) {
    http_response_code(404);
    exit('Member not found.');
}

$fullName = trim(
    $member['first_name'] . ' ' .
    ($member['middle_name'] ?? '') . ' ' .
    $member['last_name']
);

function viewEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusClass = match ($member['member_status']) {
    'Active' => 'active',
    'Inactive' => 'inactive',
    default => 'archived'
};

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>
.main-content {
    flex: 1;
    min-width: 0;
    min-height: 100vh;
    padding: 35px;
    background: #F8F5ED;
    color: #172C46;
    font-family: 'Inter', sans-serif;
}

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.page-heading h1 {
    font-family: 'Cinzel', serif;
    font-size: 25px;
    margin: 0 0 8px;
}

.page-heading p {
    font-size: 12px;
    color: #888;
}

.btn-edit,
.btn-back {
    padding: 11px 17px;
    text-decoration: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.btn-edit {
    background: #172C46;
    color: #FFFFFF;
}

.btn-back {
    background: #EEEAE2;
    color: #555;
}

.profile-panel {
    max-width: 950px;
    background: #FFFFFF;
    border: 1px solid #E9E1D3;
    border-radius: 10px;
    overflow: hidden;
}

.profile-header {
    background: #172C46;
    color: #FFFFFF;
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.profile-avatar {
    width: 75px;
    height: 75px;
    flex-shrink: 0;
    border-radius: 50%;
    background: #D8BD83;
    color: #172C46;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Cinzel', serif;
    font-size: 25px;
    font-weight: 700;
}

.profile-header h2 {
    font-family: 'Cinzel', serif;
    font-size: 20px;
    margin: 0 0 8px;
}

.profile-header p {
    font-size: 11px;
    color: #E4E4E4;
    margin: 0;
}

.profile-body {
    padding: 28px;
}

.section-title {
    font-family: 'Cinzel', serif;
    font-size: 14px;
    margin: 0 0 18px;
    padding-bottom: 12px;
    border-bottom: 1px solid #EEE7DA;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
    margin-bottom: 30px;
}

.detail-item small {
    display: block;
    color: #999;
    font-size: 10px;
    margin-bottom: 7px;
}

.detail-item strong {
    display: block;
    color: #172C46;
    font-size: 13px;
    overflow-wrap: anywhere;
}

.status {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.active {
    background: #E8F3E9;
    color: #347343;
}

.inactive {
    background: #FFF0DD;
    color: #9A6928;
}

.archived {
    background: #ECECEC;
    color: #666;
}

.qr-panel {
    padding: 20px;
    background: #F8F5ED;
    border: 1px solid #E9E1D3;
    border-radius: 8px;
}

.qr-panel small {
    display: block;
    color: #888;
    font-size: 10px;
    margin-bottom: 8px;
}

.qr-panel code {
    font-size: 11px;
    word-break: break-all;
    color: #172C46;
}

.notice {
    padding: 13px;
    margin-bottom: 20px;
    background: #EDF7EF;
    border: 1px solid #C6E2CB;
    color: #28643A;
    border-radius: 6px;
    font-size: 12px;
}

@media(max-width: 600px) {
    .main-content {
        padding: 22px 15px;
    }

    .profile-body {
        padding: 20px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="main-content">

    <div class="page-heading">

        <div>
            <h1>Member Profile</h1>
            <p>Complete information about the selected altar server.</p>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">

            <a href="index.php" class="btn-back">BACK</a>

            <a href="edit.php?id=<?= (int) $member['id'] ?>" class="btn-edit">
                EDIT PROFILE
            </a>

        </div>

    </div>

    <?php if (isset($_GET['created'])): ?>
        <div class="notice">Member successfully registered.</div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice">Member information successfully updated.</div>
    <?php endif; ?>

    <section class="profile-panel">

        <div class="profile-header">

            <div class="profile-avatar">
                <?= viewEscape(strtoupper(substr($member['first_name'], 0, 1))) ?>
            </div>

            <div>

                <h2><?= viewEscape($fullName) ?></h2>

                <p>
                    Member Code:
                    <?= viewEscape($member['member_code']) ?>
                </p>

            </div>

        </div>

        <div class="profile-body">

            <h3 class="section-title">Personal Information</h3>

            <div class="details-grid">

                <div class="detail-item">
                    <small>First Name</small>
                    <strong><?= viewEscape($member['first_name']) ?></strong>
                </div>

                <div class="detail-item">
                    <small>Middle Name</small>
                    <strong><?= viewEscape($member['middle_name'] ?: 'Not provided') ?></strong>
                </div>

                <div class="detail-item">
                    <small>Last Name</small>
                    <strong><?= viewEscape($member['last_name']) ?></strong>
                </div>

                <div class="detail-item">
                    <small>Birth Date</small>
                    <strong>
                        <?= $member['birth_date']
                            ? viewEscape(date('F d, Y', strtotime($member['birth_date'])))
                            : 'Not provided' ?>
                    </strong>
                </div>

                <div class="detail-item">
                    <small>Address</small>
                    <strong><?= viewEscape($member['address'] ?: 'Not provided') ?></strong>
                </div>

                <div class="detail-item">
                    <small>Member Status</small>
                    <strong>
                        <span class="status <?= $statusClass ?>">
                            <?= viewEscape($member['member_status']) ?>
                        </span>
                    </strong>
                </div>

            </div>

            <h3 class="section-title">Contact Information</h3>

            <div class="details-grid">

                <div class="detail-item">
                    <small>Contact Number</small>
                    <strong><?= viewEscape($member['contact_number'] ?: 'Not provided') ?></strong>
                </div>

                <div class="detail-item">
                    <small>Email Address</small>
                    <strong><?= viewEscape($member['email'] ?: 'Not provided') ?></strong>
                </div>

            </div>

            <h3 class="section-title">Registration Information</h3>

            <div class="details-grid">

                <div class="detail-item">
                    <small>Registered On</small>
                    <strong><?= viewEscape(date('F d, Y h:i A', strtotime($member['created_at']))) ?></strong>
                </div>

                <div class="detail-item">
                    <small>Last Updated</small>
                    <strong><?= viewEscape(date('F d, Y h:i A', strtotime($member['updated_at']))) ?></strong>
                </div>

            </div>

            <h3 class="section-title">QR Code Information</h3>

            <div class="qr-panel">

                <small>Unique Member QR Token</small>

                <code><?= viewEscape($member['qr_token']) ?></code>

                <p style="font-size:10px;color:#888;margin-bottom:0;">
                    This token identifies the member in the attendance system.
                    Do not share it publicly.
                </p>

            </div>

        </div>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
