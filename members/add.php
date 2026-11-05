<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| ADD NEW MEMBER
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Add New Member';
$activePage = 'add-member';

/*
|--------------------------------------------------------------------------
| HTML ESCAPE
|--------------------------------------------------------------------------
*/

function addEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$firstName = '';
$middleName = '';
$lastName = '';
$sex = '';
$birthDate = '';
$address = '';
$contactNumber = '';
$email = '';
$status = 'Active';

$errors = [];

/*
|--------------------------------------------------------------------------
| PROCESS FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    */

    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $submittedToken)
    ) {
        $errors[] = 'Your form session has expired. Please refresh the page and try again.';
    }

    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($firstName === '') {
        $errors[] = 'First name is required.';
    } elseif (mb_strlen($firstName) > 100) {
        $errors[] = 'First name must not exceed 100 characters.';
    }

    if (mb_strlen($middleName) > 100) {
        $errors[] = 'Middle name must not exceed 100 characters.';
    }

    if ($lastName === '') {
        $errors[] = 'Last name is required.';
    } elseif (mb_strlen($lastName) > 100) {
        $errors[] = 'Last name must not exceed 100 characters.';
    }

    if (!in_array($sex, ['Male', 'Female'], true)) {
        $errors[] = 'Please select a valid sex.';
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $birthDate);
    $dateErrors = DateTime::getLastErrors();

    $validBirthDate =
        $dateObject !== false &&
        $dateObject->format('Y-m-d') === $birthDate &&
        ($dateErrors === false ||
            ($dateErrors['warning_count'] === 0 &&
             $dateErrors['error_count'] === 0));

    if (!$validBirthDate) {
        $errors[] = 'Please enter a valid birth date.';
    } elseif ($dateObject > new DateTime('today')) {
        $errors[] = 'Birth date cannot be in the future.';
    }

    if ($address === '') {
        $errors[] = 'Address is required.';
    } elseif (mb_strlen($address) > 255) {
        $errors[] = 'Address must not exceed 255 characters.';
    }

    if (mb_strlen($contactNumber) > 30) {
        $errors[] = 'Contact number must not exceed 30 characters.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (mb_strlen($email) > 150) {
        $errors[] = 'Email must not exceed 150 characters.';
    }

    if (!in_array($status, ['Active', 'Inactive'], true)) {
        $errors[] = 'Please select a valid member status.';
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT MEMBER
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $fullName = trim(
            $firstName . ' ' .
            ($middleName !== '' ? $middleName . ' ' : '') .
            $lastName
        );

        $today = new DateTime('today');
        $age = $dateObject->diff($today)->y;

        $memberCode = 'AS-' . date('Ymd') . '-' .
            strtoupper(bin2hex(random_bytes(3)));

        $qrToken = bin2hex(random_bytes(32));

        try {

            $sql = "
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
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':member_code' => $memberCode,
                ':full_name' => $fullName,
                ':sex' => $sex,
                ':age' => $age,
                ':birth_date' => $birthDate,
                ':address' => $address,
                ':contact_number' => $contactNumber !== ''
                    ? $contactNumber
                    : null,
                ':email' => $email !== ''
                    ? $email
                    : null,
                ':status' => $status,
                ':qr_token' => $qrToken
            ]);

            $newMemberId = $stmt->fetchColumn();

            if (!$newMemberId) {
                throw new RuntimeException('Member ID was not returned.');
            }

            header(
                'Location: /members/qr_card.php?id=' .
                urlencode((string)$newMemberId) .
                '&created=1'
            );
            exit;

        } catch (Throwable $e) {

            error_log('ADD MEMBER ERROR: ' . $e->getMessage());

            $errors[] = 'Unable to register this member. Please check the database and try again.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| SHARED HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.add-member-page {
    padding: 25px;
}

.add-member-heading {
    margin-bottom: 22px;
}

.add-member-heading h1 {
    margin: 0 0 6px;
    color: #142b4a;
    font-size: 27px;
}

.add-member-heading p {
    margin: 0;
    color: #718096;
}

.add-member-card {
    max-width: 950px;
    background: #fff;
    border: 1px solid #e5eaf0;
    border-radius: 10px;
    padding: 25px;
}

.add-member-card h2 {
    margin: 0 0 20px;
    color: #142b4a;
    font-size: 19px;
}

.add-member-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.add-member-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.add-member-field.full {
    grid-column: 1 / -1;
}

.add-member-field label {
    color: #344054;
    font-size: 13px;
    font-weight: 600;
}

.add-member-field input,
.add-member-field select,
.add-member-field textarea {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #d7dee8;
    border-radius: 7px;
    font-size: 14px;
    font-family: inherit;
    box-sizing: border-box;
}

.add-member-field textarea {
    min-height: 85px;
    resize: vertical;
}

.add-member-field input:focus,
.add-member-field select:focus,
.add-member-field textarea:focus {
    outline: none;
    border-color: #174a7e;
    box-shadow: 0 0 0 3px rgba(23, 74, 126, 0.10);
}

.add-member-help {
    color: #8491a3;
    font-size: 12px;
}

.add-member-errors {
    background: #fff0f0;
    border: 1px solid #f3caca;
    color: #a12626;
    border-radius: 7px;
    padding: 14px 18px;
    margin-bottom: 20px;
}

.add-member-errors ul {
    margin: 8px 0 0;
    padding-left: 20px;
}

.add-member-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #edf0f4;
}

.add-member-button {
    display: inline-block;
    padding: 12px 18px;
    border: 0;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.add-member-submit {
    background: #142b4a;
    color: #fff;
}

.add-member-cancel {
    background: #e9edf2;
    color: #26384d;
}

@media (max-width: 650px) {
    .add-member-page {
        padding: 15px;
    }

    .add-member-card {
        padding: 17px;
    }

    .add-member-grid {
        grid-template-columns: 1fr;
    }

    .add-member-field.full {
        grid-column: auto;
    }
}
</style>

<main class="add-member-page">

    <div class="add-member-heading">
        <h1>Register New Member</h1>
        <p>Add a new altar server to the ministry membership records.</p>
    </div>

    <section class="add-member-card">

        <h2>Member Information</h2>

        <?php if (!empty($errors)): ?>

            <div class="add-member-errors">
                <strong>Please correct the following:</strong>

                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= addEscape($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        <?php endif; ?>

        <form method="POST" action="/members/add.php">

            <?= csrfField() ?>

            <div class="add-member-grid">

                <div class="add-member-field">
                    <label for="first_name">First Name *</label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        maxlength="100"
                        value="<?= addEscape($firstName) ?>"
                        required
                    >
                </div>

                <div class="add-member-field">
                    <label for="middle_name">Middle Name</label>
                    <input
                        type="text"
                        id="middle_name"
                        name="middle_name"
                        maxlength="100"
                        value="<?= addEscape($middleName) ?>"
                    >
                </div>

                <div class="add-member-field">
                    <label for="last_name">Last Name *</label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        maxlength="100"
                        value="<?= addEscape($lastName) ?>"
                        required
                    >
                </div>

                <div class="add-member-field">
                    <label for="sex">Sex *</label>
                    <select id="sex" name="sex" required>
                        <option value="">Select sex</option>
                        <option value="Male"
                            <?= $sex === 'Male' ? 'selected' : '' ?>>
                            Male
                        </option>
                        <option value="Female"
                            <?= $sex === 'Female' ? 'selected' : '' ?>>
                            Female
                        </option>
                    </select>
                </div>

                <div class="add-member-field">
                    <label for="birth_date">Date of Birth *</label>
                    <input
                        type="date"
                        id="birth_date"
                        name="birth_date"
                        max="<?= date('Y-m-d') ?>"
                        value="<?= addEscape($birthDate) ?>"
                        required
                    >
                </div>

                <div class="add-member-field">
                    <label for="contact_number">Contact Number</label>
                    <input
                        type="tel"
                        id="contact_number"
                        name="contact_number"
                        maxlength="30"
                        value="<?= addEscape($contactNumber) ?>"
                        placeholder="Enter contact number"
                    >
                </div>

                <div class="add-member-field full">
                    <label for="address">Complete Address *</label>
                    <textarea
                        id="address"
                        name="address"
                        maxlength="255"
                        required
                        placeholder="Enter complete address"
                    ><?= addEscape($address) ?></textarea>
                </div>

                <div class="add-member-field">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        value="<?= addEscape($email) ?>"
                        placeholder="example@email.com"
                    >
                    <span class="add-member-help">
                        Optional
                    </span>
                </div>

                <div class="add-member-field">
                    <label for="status">Member Status *</label>
                    <select id="status" name="status" required>
                        <option value="Active"
                            <?= $status === 'Active' ? 'selected' : '' ?>>
                            Active
                        </option>

                        <option value="Inactive"
                            <?= $status === 'Inactive' ? 'selected' : '' ?>>
                            Inactive
                        </option>
                    </select>
                </div>

            </div>

            <div class="add-member-actions">

                <button type="submit"
                        class="add-member-button add-member-submit">
                    Register Member
                </button>

                <a href="/members/index.php"
                   class="add-member-button add-member-cancel">
                    Cancel
                </a>

            </div>

        </form>

    </section>

</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
