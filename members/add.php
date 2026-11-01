
<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| Add New Member
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

    // Collect form data
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $memberStatus = $_POST['member_status'] ?? 'Active';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

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

        $date = DateTime::createFromFormat('!Y-m-d', $birthDate);

        if (!$date || $date->format('Y-m-d') !== $birthDate) {
            $errors[] = 'Please enter a valid birth date.';
        } elseif ($birthDate > date('Y-m-d')) {
            $errors[] = 'Birth date cannot be in the future.';
        }
    }

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


    /*
    |--------------------------------------------------------------------------
    | REGISTER MEMBER AND GENERATE PERMANENT QR TOKEN
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


            // Insert member into database
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

            // Get newly registered member ID
            $newMemberId = (int) $pdo->lastInsertId();

            // Commit database transaction
            $pdo->commit();


            /*
             * IMPORTANT:
             * Redirect directly to the member's permanent QR card.
             * The saved QR token will be reused for future Mass attendance.
             */

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

            error_log('Member registration error: ' . $e->getMessage());

            $errors[] = 'Unable to register member. Please try again.';
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
| SHARED HEADER
|--------------------------------------------------------------------------
*/

// header.php automatically includes sidebar.php.
require_once __DIR__ . '/../includes/header.php';

?>


<style>

/* PAGE LAYOUT */

.main-content {
    flex: 1;
    min-width: 0;
    min-height: 100vh;
    padding: 35px;
    background: #F8F5ED;
    color: #172C46;
    font-family: 'Inter', sans-serif;
}


/* PAGE HEADING */

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 25px;
}

.page-heading h1 {
    font-family: 'Cinzel', serif;
    font-size: 25px;
    margin: 0 0 8px;
    color: #172C46;
}

.page-heading p {
    color: #888;
    font-size: 12px;
    margin: 0;
}

.page-eyebrow {
    display: block;
    margin-bottom: 8px;
    color: #B18A43;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
}


/* FORM PANEL */

.form-panel {
    background: #FFFFFF;
    border: 1px solid #E9E1D3;
    border-radius: 10px;
    padding: 28px;
    max-width: 900px;
    box-shadow: 0 4px 15px rgba(23, 44, 70, 0.03);
}

.form-section-title {
    font-family: 'Cinzel', serif;
    font-size: 15px;
    color: #172C46;
    margin: 0 0 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #EEE7DA;
}

.form-section-title:not(:first-child) {
    margin-top: 28px;
}


/* FORM GRID */

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.form-group {
    margin-bottom: 18px;
    min-width: 0;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #34445A;
    margin-bottom: 8px;
}

.required {
    color: #A64A40;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 12px;
    border: 1px solid #DDD5C8;
    border-radius: 6px;
    font-family: inherit;
    font-size: 12px;
    background: #FFFFFF;
    color: #172C46;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #B18A43;
    box-shadow: 0 0 0 3px rgba(177, 138, 67, 0.12);
}

.form-group textarea {
    resize: vertical;
    min-height: 85px;
}

.form-hint {
    display: block;
    margin-top: 6px;
    color: #999;
    font-size: 10px;
}


/* PERMANENT QR NOTICE */

.qr-notice {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 17px;
    margin-top: 25px;
    border: 1px solid #E8D8B5;
    border-radius: 8px;
    background: #FBF7ED;
}

.qr-notice-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #F0E5CC;
    color: #8B682F;
    font-size: 21px;
}

.qr-notice h4 {
    margin: 0 0 5px;
    color: #172C46;
    font-size: 12px;
}

.qr-notice p {
    margin: 0;
    color: #777;
    font-size: 11px;
    line-height: 1.7;
}


/* ERROR BOX */

.error-box {
    padding: 14px 16px;
    margin-bottom: 20px;
    border: 1px solid #F0D0CC;
    border-radius: 6px;
    background: #FFF0EF;
    color: #963E35;
    font-size: 12px;
    line-height: 1.8;
}


/* FORM BUTTONS */

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    flex-wrap: wrap;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #EEE7DA;
}

.btn-save,
.btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-family: inherit;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: 0.2s;
}

.btn-save {
    background: #172C46;
    color: #FFFFFF;
}

.btn-save:hover {
    background: #243F60;
}

.btn-cancel {
    background: #EEEAE2;
    color: #555;
}

.btn-cancel:hover {
    background: #E3DDD2;
}


/* RESPONSIVE DESIGN */

@media (max-width: 768px) {

    .main-content {
        padding: 22px 15px;
    }

    .form-panel {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: 5px;
    }

    .form-group.full-width {
        grid-column: auto;
    }

    .page-heading {
        flex-direction: column;
    }

}

@media (max-width: 480px) {

    .form-actions {
        flex-direction: column-reverse;
    }

    .btn-save,
    .btn-cancel {
        width: 100%;
        box-sizing: border-box;
    }

}

</style>


<main class="main-content">

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


            <!-- PERMANENT QR CODE INFORMATION -->

            <div class="qr-notice">

                <div class="qr-notice-icon">
                    ▦
                </div>

                <div>

                    <h4>Permanent QR Code Registration</h4>

                    <p>
                        A unique permanent QR security token will automatically
                        be generated when this member is registered.
                        The member will use the same QR code for every Sunday
                        Mass attendance. After registration, the system will
                        open the member's QR identification card, where it can
                        be viewed and printed.
                    </p>

                </div>

            </div>


            <!-- FORM ACTIONS -->

            <div class="form-actions">

                <a
                    href="index.php"
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


</main>


<?php
/*
|--------------------------------------------------------------------------
| SHARED FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/footer.php';
?>
