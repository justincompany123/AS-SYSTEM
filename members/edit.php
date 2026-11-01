
<?php

/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| Add New Member
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

$pageTitle = 'Add New Member';
$activePage = 'members';

$errors = [];

$firstName = '';
$middleName = '';
$lastName = '';
$birthDate = '';
$address = '';
$contactNumber = '';
$email = '';
$memberStatus = 'Active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireValidCSRFToken();

    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $memberStatus = $_POST['member_status'] ?? 'Active';

    // Validate names
    if ($firstName === '' || strlen($firstName) > 100) {
        $errors[] = 'First name is required and must not exceed 100 characters.';
    }

    if (strlen($middleName) > 100) {
        $errors[] = 'Middle name must not exceed 100 characters.';
    }

    if ($lastName === '' || strlen($lastName) > 100) {
        $errors[] = 'Last name is required and must not exceed 100 characters.';
    }

    // Validate birth date
    if ($birthDate !== '') {

        $date = DateTime::createFromFormat('Y-m-d', $birthDate);

        if (!$date || $date->format('Y-m-d') !== $birthDate) {
            $errors[] = 'Please enter a valid birth date.';
        } elseif ($birthDate > date('Y-m-d')) {
            $errors[] = 'Birth date cannot be in the future.';
        }
    }

    // Validate other fields
    if (strlen($address) > 255) {
        $errors[] = 'Address must not exceed 255 characters.';
    }

    if (strlen($contactNumber) > 30) {
        $errors[] = 'Contact number must not exceed 30 characters.';
    }

    if (strlen($email) > 150) {
        $errors[] = 'Email must not exceed 150 characters.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!in_array($memberStatus, ['Active', 'Inactive'], true)) {
        $errors[] = 'Invalid member status.';
    }

    // Insert member
    if (empty($errors)) {

        try {

            $pdo->beginTransaction();

            $memberCode = 'AS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $qrToken = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("
                INSERT INTO members (
                    member_code,
                    qr_token,
                    first_name,
                    middle_name,
                    last_name,
                    birth_date,
                    address,
                    contact_number,
                    email,
                    member_status
                ) VALUES (
                    :member_code,
                    :qr_token,
                    :first_name,
                    :middle_name,
                    :last_name,
                    :birth_date,
                    :address,
                    :contact_number,
                    :email,
                    :member_status
                )
            ");

            $stmt->execute([
                ':member_code' => $memberCode,
                ':qr_token' => $qrToken,
                ':first_name' => $firstName,
                ':middle_name' => $middleName !== '' ? $middleName : null,
                ':last_name' => $lastName,
                ':birth_date' => $birthDate !== '' ? $birthDate : null,
                ':address' => $address !== '' ? $address : null,
                ':contact_number' => $contactNumber !== '' ? $contactNumber : null,
                ':email' => $email !== '' ? $email : null,
                ':member_status' => $memberStatus
            ]);

            $pdo->commit();

            header('Location: view.php?id=' . $pdo->lastInsertId() . '&created=1');
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] = 'Unable to register member. Please try again.';
        }
    }
}

function addEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
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

.form-panel {
    background: #FFFFFF;
    border: 1px solid #E9E1D3;
    border-radius: 10px;
    padding: 28px;
    max-width: 900px;
}

.form-section-title {
    font-family: 'Cinzel', serif;
    font-size: 15px;
    margin: 0 0 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #EEE7DA;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #DDD5C8;
    border-radius: 6px;
    font-family: inherit;
    font-size: 12px;
    background: #FFFFFF;
}

.form-group textarea {
    resize: vertical;
    min-height: 85px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 15px;
}

.btn-save,
.btn-cancel {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.btn-save {
    background: #172C46;
    color: #FFFFFF;
}

.btn-cancel {
    background: #EEEAE2;
    color: #555;
}

.error-box {
    padding: 14px;
    margin-bottom: 20px;
    border-radius: 6px;
    background: #FFF0EF;
    color: #963E35;
    font-size: 12px;
}

@media(max-width: 768px) {
    .main-content {
        padding: 22px 15px;
    }

    .form-panel {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="main-content">

    <div class="page-heading">
        <h1>Register New Member</h1>
        <p>Add a new altar server to the ministry records.</p>
    </div>

    <section class="form-panel">

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $error): ?>
                    <div>• <?= addEscape($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <?= csrfField() ?>

            <h3 class="form-section-title">Personal Information</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name"
                        maxlength="100" required
                        value="<?= addEscape($firstName) ?>">
                </div>

                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name"
                        maxlength="100"
                        value="<?= addEscape($middleName) ?>">
                </div>

                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name"
                        maxlength="100" required
                        value="<?= addEscape($lastName) ?>">
                </div>

                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="date" name="birth_date"
                        max="<?= date('Y-m-d') ?>"
                        value="<?= addEscape($birthDate) ?>">
                </div>

                <div class="form-group full-width">
                    <label>Address</label>
                    <textarea name="address"
                        maxlength="255"><?= addEscape($address) ?></textarea>
                </div>

            </div>

            <h3 class="form-section-title">Contact Information</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" name="contact_number"
                        maxlength="30"
                        value="<?= addEscape($contactNumber) ?>">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email"
                        maxlength="150"
                        value="<?= addEscape($email) ?>">
                </div>

                <div class="form-group">
                    <label>Member Status</label>
                    <select name="member_status">
                        <option value="Active" <?= $memberStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $memberStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

            </div>

            <div class="form-actions">

                <a href="index.php" class="btn-cancel">Cancel</a>

                <button type="submit" class="btn-save">
                    REGISTER MEMBER
                </button>

            </div>

        </form>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
