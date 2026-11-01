
<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| Archive Member
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

$pageTitle = 'Archive Member';
$activePage = 'members';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

// Retrieve member
$stmt = $pdo->prepare("
    SELECT id, member_code, first_name, middle_name,
           last_name, member_status
    FROM members
    WHERE id = :id
");

$stmt->execute([':id' => $id]);

$member = $stmt->fetch();

if (!$member) {
    http_response_code(404);
    exit('Member not found.');
}

$errors = [];

$fullName = trim(
    $member['first_name'] . ' ' .
    ($member['middle_name'] ?? '') . ' ' .
    $member['last_name']
);

// Process archive request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireValidCSRFToken();

    if ($member['member_status'] === 'Archived') {

        $errors[] = 'This member is already archived.';

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE members
                SET member_status = 'Archived'
                WHERE id = :id
            ");

            $stmt->execute([':id' => $id]);

            header('Location: index.php?archived=1');
            exit;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $errors[] = 'Unable to archive this member. Please try again.';

        }
    }
}

function archiveEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

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
    margin-bottom: 25px;
}

.page-heading h1 {
    font-family: 'Cinzel', serif;
    font-size: 25px;
    margin: 0 0 8px;
}

.page-heading p {
    color: #888;
    font-size: 12px;
}

.confirm-panel {
    max-width: 600px;
    margin: 35px auto;
    padding: 35px;
    background: #FFFFFF;
    border: 1px solid #E9E1D3;
    border-radius: 10px;
    text-align: center;
}

.warning-icon {
    width: 65px;
    height: 65px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #FFF0DD;
    color: #A46C26;
    font-size: 28px;
}

.confirm-panel h2 {
    font-family: 'Cinzel', serif;
    font-size: 19px;
    margin-bottom: 12px;
}

.confirm-panel p {
    font-size: 12px;
    line-height: 1.8;
    color: #777;
}

.member-summary {
    background: #F8F5ED;
    border: 1px solid #E9E1D3;
    padding: 17px;
    border-radius: 7px;
    margin: 22px 0;
    text-align: left;
}

.member-summary small {
    display: block;
    color: #999;
    font-size: 10px;
    margin-bottom: 6px;
}

.member-summary strong {
    display: block;
    font-size: 13px;
    color: #172C46;
    margin-bottom: 13px;
}

.member-summary strong:last-child {
    margin-bottom: 0;
}

.warning-note {
    background: #FFF8E9;
    border: 1px solid #EAD9B4;
    padding: 13px;
    border-radius: 6px;
    color: #80642F;
    font-size: 11px;
    text-align: left;
    line-height: 1.7;
}

.form-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 25px;
    flex-wrap: wrap;
}

.btn-archive,
.btn-cancel {
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.btn-archive {
    background: #A3463D;
    color: #FFFFFF;
}

.btn-cancel {
    background: #EEEAE2;
    color: #555;
}

.error-box {
    padding: 13px;
    background: #FFF0EF;
    color: #963E35;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 12px;
}

@media(max-width: 600px) {
    .main-content {
        padding: 22px 15px;
    }

    .confirm-panel {
        padding: 25px 18px;
    }
}
</style>

<main class="main-content">

    <div class="page-heading">

        <h1>Archive Member</h1>

        <p>Confirm the archiving of this altar server record.</p>

    </div>

    <section class="confirm-panel">

        <div class="warning-icon">!</div>

        <h2>Confirm Member Archiving</h2>

        <p>
            Are you sure you want to archive this member?
            The member will no longer be considered active.
        </p>

        <?php if (!empty($errors)): ?>

            <div class="error-box">

                <?php foreach ($errors as $error): ?>
                    <div>• <?= archiveEscape($error) ?></div>
                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <div class="member-summary">

            <small>Member Name</small>

            <strong>
                <?= archiveEscape($fullName) ?>
            </strong>

            <small>Member Code</small>

            <strong>
                <?= archiveEscape($member['member_code']) ?>
            </strong>

            <small>Current Status</small>

            <strong>
                <?= archiveEscape($member['member_status']) ?>
            </strong>

        </div>

        <div class="warning-note">

            <strong>Important:</strong>

            Archiving does not permanently delete the member.
            Existing attendance records and assignment history
            will be preserved.

        </div>

        <form method="POST">

            <?= csrfField() ?>

            <div class="form-actions">

                <a href="index.php" class="btn-cancel">
                    CANCEL
                </a>

                <?php if ($member['member_status'] !== 'Archived'): ?>

                    <button type="submit" class="btn-archive">
                        CONFIRM ARCHIVE
                    </button>

                <?php endif; ?>

            </div>

        </form>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
