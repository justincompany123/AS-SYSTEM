<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| ADD NEW MEMBER - SUPABASE POSTGRESQL
| St. Francis Xavier Cathedral
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
$sex = '';
$address = '';
$contactNumber = '';
$email = '';
$memberStatus = 'Active';

/*
|--------------------------------------------------------------------------
| FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireValidCSRFToken();

    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $memberStatus = trim($_POST['member_status'] ?? 'Active');

    // Validate first name
    if ($firstName === '' || strlen($firstName) > 100) {
        $errors[] = 'First name is required and must not exceed 100 characters.';
    }

    // Validate middle name
    if (strlen($middleName) > 100) {
        $errors[] = 'Middle name must not exceed 100 characters.';
    }

    // Validate last name
    if ($lastName === '' || strlen($lastName) > 100) {
        $errors[] = 'Last name is required and must not exceed 100 characters.';
    }

    // Validate sex
    if (!in_array($sex, ['Male', 'Female'], true)) {
        $errors[] = 'Please select a valid sex.';
    }

    // Validate birth date and calculate age
    $age = null;

    if ($birthDate !== '') {

        $date = DateTime::createFromFormat('!Y-m-d', $birthDate);

        if (!$date || $date->format('Y-m-d') !== $birthDate) {
            $errors[] = 'Please enter a valid birth date.';
        } elseif ($birthDate > date('Y-m-d')) {
            $errors[] = 'Birth date cannot be in the future.';
        } else {
            $today = new DateTime('today');
            $age = $date->diff($today)->y;
        }
    }

    // Validate address
    if (strlen($address) > 255) {
        $errors[] = 'Address must not exceed 255 characters.';
    }

    // Validate contact number
    if (strlen($contactNumber) > 30) {
        $errors[] = 'Contact number must not exceed 30 characters.';
    }

    // Validate email
    if (strlen($email) > 150) {
        $errors[] = 'Email must not exceed 150 characters.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate status
    if (!in_array($memberStatus, ['Active', 'Inactive'], true)) {
        $errors[] = 'Invalid member status.';
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTER MEMBER
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $pdo->beginTransaction();

            // Generate unique member code
            $memberCode = 'AS-' . date('Ymd') . '-' .
                strtoupper(bin2hex(random_bytes(3)));

            // Generate permanent QR security token
            $qrToken = bin2hex(random_bytes(32));

            // Combine names
            $fullName = trim(
                $firstName . ' ' .
                ($middleName !== '' ? $middleName . ' ' : '') .
                $lastName
            );

            // Insert member into Supabase PostgreSQL
            $stmt = $pdo->prepare("
                INSERT INTO public.members (
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
                )
                VALUES (
                    :member_code,
                    :full_name,
                    :sex,
                    :age,
                    :birth_date,
                    :address,
                    :contact_number,
                    :email,
                    :status,
                    :qr_token
                )
                RETURNING id
            ");

            $stmt->execute([
                ':member_code' => $memberCode,
                ':full_name' => $fullName,
                ':sex' => $sex,
                ':age' => $age,
                ':birth_date' => $birthDate !== '' ? $birthDate : null,
                ':address' => $address !== '' ? $address : null,
                ':contact_number' => $contactNumber !== '' ? $contactNumber : null,
                ':email' => $email !== '' ? $email : null,
                ':status' => $memberStatus,
                ':qr_token' => $qrToken
            ]);

            $newMemberId = (int) $stmt->fetchColumn();

            $pdo->commit();

            // Redirect to QR identification card
            header(
                'Location: qr_card.php?id=' .
                $newMemberId .
                '&created=1'
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Member registration error: ' . $e->getMessage()
            );

            $errors[] = 'Unable to register member. Please check the database structure and try again.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| HTML ESCAPE FUNCTION
|--------------------------------------------------------------------------
*/

function addEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| SHARED HEADER AND SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>

<!-- =====================================================
     ADD MEMBER PAGE
===================================================== -->

<main class="main-content">

    <div class="content-container">

        <!-- PAGE HEADING -->

        <div class="page-heading">

            <div>
                <span class="page-eyebrow">
                    MEMBER MANAGEMENT
                </span>

                <h1>Register New Member</h1>

                <p>
                    Add a new altar server to the ministry records.
                </p>
            </div>

        </div>

        <!-- REGISTRATION FORM PANEL -->

        <section class="form-panel">

            <!-- ERROR MESSAGES -->

            <?php if (!empty($errors)): ?>

                <div class="error-box">

                    <strong>Please correct the following:</strong>

                    <?php foreach ($errors as $error): ?>

                        <div>
                            • <?= addEscape($error) ?>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <!-- REGISTRATION FORM -->

            <form method="POST" data-prevent-double-submit>

                <?= csrfField() ?>

                <!-- PERSONAL INFORMATION -->

                <h3 class="form-section-title">
                    Personal Information
                </h3>

                <div class="form-grid">

                    <div class="form-group">

                        <label>
                            First Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            maxlength="100"
                            placeholder="Enter first name"
                            autocomplete="given-name"
                            required
                            value="<?= addEscape($firstName) ?>"
                        >

                    </div>

                    <div class="form-group">

                        <label>Middle Name</label>

                        <input
                            type="text"
                            name="middle_name"
                            maxlength="100"
                            placeholder="Enter middle name"
                            autocomplete="additional-name"
                            value="<?= addEscape($middleName) ?>"
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Last Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            maxlength="100"
                            placeholder="Enter last name"
                            autocomplete="family-name"
                            required
                            value="<?= addEscape($lastName) ?>"
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Sex <span class="required">*</span>
                        </label>

                        <select name="sex" required>

                            <option value="">Select sex</option>

                            <option
                                value="Male"
                                <?= $sex === 'Male' ? 'selected' : '' ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?= $sex === 'Female' ? 'selected' : '' ?>
                            >
                                Female
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label>Birth Date</label>

                        <input
                            type="date"
                            name="birth_date"
                            max="<?= date('Y-m-d') ?>"
                            value="<?= addEscape($birthDate) ?>"
                        >

                    </div>

                    <div class="form-group full-width">

                        <label>Address</label>

                        <textarea
                            name="address"
                            maxlength="255"
                            placeholder="Enter complete address"
                        ><?= addEscape($address) ?></textarea>

                    </div>

                </div>

                <!-- CONTACT INFORMATION -->

                <h3 class="form-section-title">
                    Contact Information
                </h3>

                <div class="form-grid">

                    <div class="form-group">

                        <label>Contact Number</label>

                        <input
                            type="tel"
                            name="contact_number"
                            maxlength="30"
                            placeholder="Enter contact number"
                            autocomplete="tel"
                            value="<?= addEscape($contactNumber) ?>"
                        >

                    </div>

                    <div class="form-group">

                        <label>Email Address</label>

                        <input
                            type="email"
                            name="email"
                            maxlength="150"
                            placeholder="Enter email address"
                            autocomplete="email"
                            value="<?= addEscape($email) ?>"
                        >

                    </div>

                    <div class="form-group">

                        <label>Member Status</label>

                        <select name="member_status">

                            <option
                                value="Active"
                                <?= $memberStatus === 'Active' ? 'selected' : '' ?>
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?= $memberStatus === 'Inactive' ? 'selected' : '' ?>
                            >
                                Inactive
                            </option>

                        </select>

                        <small class="form-hint">
                            Only active members are eligible for QR attendance.
                        </small>

                    </div>

                </div>

                <!-- QR CODE INFORMATION -->

                <div class="qr-notice">

                    <div class="qr-notice-icon">
                        ▦
                    </div>

                    <div>

                        <h4>Permanent QR Code Registration</h4>

                        <p>
                            A unique permanent QR security token will
                            automatically be generated when this member
                            is registered. The member will use the same
                            QR code for every Sunday Mass attendance.
                            After registration, the system will open the
                            member's QR identification card, where it
                            can be viewed and printed.
                        </p>

                    </div>

                </div>

                <!-- FORM ACTIONS -->

                <div class="form-actions">

                    <a
                        href="/index.php"
                        class="btn-cancel"
                    >
                        CANCEL
                    </a>

                    <button
                        type="submit"
                        class="btn-save"
                    >
                        REGISTER MEMBER
                    </button>

                </div>

            </form>

        </section>

    </div>

</main>

<?php
/*
|--------------------------------------------------------------------------
| SHARED FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/footer.php';
?>
