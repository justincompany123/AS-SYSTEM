
<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS WEB SYSTEM
| MEMBER MANAGEMENT
| SUPABASE POSTGRESQL
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

// =====================================================
// AUTHENTICATION, CSRF AND DATABASE
// =====================================================

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

// =====================================================
// PAGE SETTINGS
// =====================================================

$pageTitle = 'Member Management';
$activePage = 'members';

// =====================================================
// HTML ESCAPE FUNCTION
// =====================================================

function memberEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// =====================================================
// SEARCH INPUT
// =====================================================

$search = '';

if (
    isset($_GET['search']) &&
    is_string($_GET['search'])
) {
    $search = trim($_GET['search']);
}

// =====================================================
// INITIALIZE VARIABLES
// =====================================================

$members = [];
$pageError = '';

$totalMembers = 0;

// =====================================================
// FETCH ACTIVE MEMBERS
// =====================================================

try {

    $sql = "
        SELECT
            id,
            member_code,
            full_name,
            sex,
            age,
            address,
            contact_number,
            email,
            status,
            created_at
        FROM public.members
        WHERE status = :status
    ";

    $params = [
        ':status' => 'Active'
    ];

    // SEARCH FILTER

    if ($search !== '') {

        $sql .= "
            AND (
                member_code ILIKE :search
                OR full_name ILIKE :search
                OR contact_number ILIKE :search
                OR email ILIKE :search
            )
        ";

        $params[':search'] = '%' . $search . '%';
    }

    // SORT MEMBERS

    $sql .= "
        ORDER BY
            full_name ASC,
            id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalMembers = count($members);

} catch (PDOException $e) {

    error_log(
        'Member Management Error: ' . $e->getMessage()
    );

    $pageError =
        'Unable to load member records. Please check your database connection.';

}

// =====================================================
// SHARED HEADER
// =====================================================

require_once __DIR__ . '/../includes/header.php';

?>

<style>

/* =====================================================
   MEMBER MANAGEMENT
===================================================== */

.main-content {
    padding: 30px;
    background: #F8F5ED;
    min-height: 100vh;
}

.member-panel {
    background: #FFFFFF;
    border: 1px solid #E9E1D3;
    border-radius: 10px;
    padding: 22px;
}

/* =====================================================
   TOOLBAR
===================================================== */

.member-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.member-search {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.member-search input {
    padding: 10px 12px;
    border: 1px solid #DDD5C8;
    border-radius: 6px;
    min-width: 240px;
    font-family: inherit;
}

.member-search input:focus {
    outline: none;
    border-color: #B18A43;
    box-shadow: 0 0 0 2px rgba(177, 138, 67, 0.12);
}

/* =====================================================
   BUTTONS
===================================================== */

.member-btn {
    display: inline-block;
    padding: 10px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    text-align: center;
    font-family: inherit;
    transition: 0.2s ease;
}

.member-btn-primary {
    background: #172C46;
    color: #FFFFFF;
}

.member-btn-primary:hover {
    background: #243F60;
}

.member-btn-gold {
    background: #B18A43;
    color: #FFFFFF;
}

.member-btn-gold:hover {
    background: #947033;
}

.member-btn-danger {
    background: #A94336;
    color: #FFFFFF;
}

.member-btn-danger:hover {
    background: #873328;
}

.member-btn-light {
    background: #EEEAE2;
    color: #555555;
}

.member-btn-light:hover {
    background: #DED8CC;
}

.member-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.member-btn-small {
    padding: 7px 10px;
    font-size: 10px;
}

/* =====================================================
   MEMBER TABLE
===================================================== */

.member-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.member-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.member-table th,
.member-table td {
    padding: 13px 10px;
    text-align: left;
    border-bottom: 1px solid #EEE7DA;
    white-space: nowrap;
}

.member-table th {
    color: #7B6A50;
    background: #FBF9F4;
    font-weight: 700;
}

.member-table tbody tr:hover {
    background: #FCFAF5;
}

.member-table input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #172C46;
}

/* =====================================================
   STATUS
===================================================== */

.member-status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    background: #EAF4EA;
    color: #34713B;
}

/* =====================================================
   MESSAGES
===================================================== */

.empty-row {
    text-align: center !important;
    padding: 35px !important;
    color: #888888;
}

.member-error {
    background: #FFF0ED;
    border: 1px solid #EAC8C2;
    color: #A12E25;
    padding: 13px;
    border-radius: 6px;
    margin-bottom: 18px;
}

.member-success {
    background: #EAF4EA;
    border: 1px solid #C9E2C9;
    color: #34713B;
    padding: 13px;
    border-radius: 6px;
    margin-bottom: 18px;
}

/* =====================================================
   MEMBER COUNT
===================================================== */

.member-count {
    font-size: 11px;
    color: #888888;
    margin-top: 16px;
}

/* =====================================================
   BULK ARCHIVE
===================================================== */

.bulk-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    background: #FBF9F4;
    border: 1px solid #EEE7DA;
    border-radius: 7px;
    padding: 12px;
    margin-bottom: 15px;
}

.bulk-toolbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.select-all-label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: #5D5140;
    cursor: pointer;
}

.member-message {
    font-size: 11px;
    color: #777777;
}

/* =====================================================
   RESPONSIVE DESIGN
===================================================== */

@media (max-width: 768px) {

    .main-content {
        padding: 20px 15px;
    }

    .member-panel {
        padding: 16px;
    }

    .member-toolbar {
        align-items: stretch;
    }

}

@media (max-width: 600px) {

    .main-content {
        padding: 18px 12px;
    }

    .member-panel {
        padding: 15px;
    }

    .member-search {
        width: 100%;
    }

    .member-search input {
        width: 100%;
        min-width: 0;
    }

    .member-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .member-toolbar > div {
        width: 100%;
    }

    .member-toolbar > div .member-btn {
        flex: 1;
    }

    .bulk-toolbar {
        align-items: stretch;
    }

    .bulk-toolbar-left {
        justify-content: space-between;
    }

}

</style>

<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main-content">

    <!-- PAGE HEADING -->

    <div class="page-heading">

        <div>

            <span style="
                color:#B18A43;
                font-size:10px;
                font-weight:700;
                letter-spacing:2px;
            ">
                MINISTRY RECORDS
            </span>

            <h1>
                Member Management
            </h1>

            <p>
                Register and manage altar server members
                and their permanent QR codes.
            </p>

        </div>

    </div>

    <!-- ERROR MESSAGE -->

    <?php if ($pageError !== ''): ?>

        <div class="member-error">
            <?= memberEscape($pageError) ?>
        </div>

    <?php endif; ?>

    <!-- SUCCESS MESSAGE -->

    <?php if (
        isset($_GET['message']) &&
        is_string($_GET['message'])
    ): ?>

        <div class="member-success">
            <?= memberEscape($_GET['message']) ?>
        </div>

    <?php endif; ?>

    <!-- MEMBER PANEL -->

    <section class="member-panel">

        <!-- TOOLBAR -->

        <div class="member-toolbar">

            <!-- SEARCH FORM -->

            <form
                method="GET"
                action="/members/index.php"
                class="member-search"
            >

                <input
                    type="search"
                    name="search"
                    placeholder="Search member name or code..."
                    value="<?= memberEscape($search) ?>"
                    aria-label="Search active members"
                >

                <button
                    type="submit"
                    class="member-btn member-btn-primary"
                >
                    SEARCH
                </button>

                <a
                    href="/members/index.php"
                    class="member-btn member-btn-light"
                >
                    RESET
                </a>

            </form>

            <!-- PAGE ACTIONS -->

            <div style="display:flex;gap:8px;flex-wrap:wrap;">

                <a
                    href="/members/archived.php"
                    class="member-btn member-btn-light"
                >
                    VIEW ARCHIVED MEMBERS
                </a>

                <a
                    href="/members/add.php"
                    class="member-btn member-btn-gold"
                >
                    + REGISTER MEMBER
                </a>

            </div>

        </div>

        <!-- =================================================
             BULK ARCHIVE FORM
        ================================================== -->

        <form
            id="bulkArchiveForm"
            method="POST"
            action="/members/archive.php"
            onsubmit="return confirmBulkArchive();"
        >

            <?= csrfField() ?>

        </form>

        <!-- BULK TOOLBAR -->

        <div class="bulk-toolbar">

            <div class="bulk-toolbar-left">

                <label class="select-all-label">

                    <input
                        type="checkbox"
                        id="selectAllMembers"
                    >

                    <strong>
                        Select All
                    </strong>

                </label>

                <span
                    class="member-message"
                    id="selectedCount"
                >
                    0 members selected
                </span>

            </div>

            <button
                type="submit"
                form="bulkArchiveForm"
                class="member-btn member-btn-danger"
                id="archiveSelectedButton"
                disabled
            >
                ARCHIVE SELECTED
            </button>

        </div>

        <!-- =================================================
             MEMBER TABLE
        ================================================== -->

        <div class="member-table-wrap">

            <table class="member-table">

                <thead>

                    <tr>

                        <th>Select</th>
                        <th>Member Code</th>
                        <th>Full Name</th>
                        <th>Sex</th>
                        <th>Age</th>
                        <th>Contact Number</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>QR Card</th>
                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($members)): ?>

                        <?php foreach ($members as $member): ?>

                            <tr>

                                <!-- CHECKBOX -->

                                <td>

                                    <input
                                        type="checkbox"
                                        class="member-checkbox"
                                        name="member_ids[]"
                                        value="<?= (int) $member['id'] ?>"
                                        form="bulkArchiveForm"
                                        aria-label="Select member"
                                    >

                                </td>

                                <!-- MEMBER CODE -->

                                <td>

                                    <strong>
                                        <?= memberEscape(
                                            $member['member_code']
                                        ) ?>
                                    </strong>

                                </td>

                                <!-- FULL NAME -->

                                <td>
                                    <?= memberEscape(
                                        $member['full_name']
                                    ) ?>
                                </td>

                                <!-- SEX -->

                                <td>
                                    <?= memberEscape(
                                        $member['sex']
                                    ) ?>
                                </td>

                                <!-- AGE -->

                                <td>
                                    <?= memberEscape(
                                        $member['age'] ?? '—'
                                    ) ?>
                                </td>

                                <!-- CONTACT -->

                                <td>
                                    <?= memberEscape(
                                        $member['contact_number'] ?: '—'
                                    ) ?>
                                </td>

                                <!-- STATUS -->

                                <td>

                                    <span class="member-status">
                                        <?= memberEscape(
                                            $member['status']
                                        ) ?>
                                    </span>

                                </td>

                                <!-- REGISTRATION DATE -->

                                <td>

                                    <?php

                                    if (!empty($member['created_at'])) {

                                        $timestamp = strtotime(
                                            $member['created_at']
                                        );

                                        echo $timestamp !== false
                                            ? memberEscape(
                                                date('M d, Y', $timestamp)
                                            )
                                            : '—';

                                    } else {

                                        echo '—';

                                    }

                                    ?>

                                </td>

                                <!-- QR CARD -->

                                <td>

                                    <a
                                        class="member-btn member-btn-primary member-btn-small"
                                        href="/members/qr_card.php?id=<?= (int) $member['id'] ?>"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        VIEW QR CARD
                                    </a>

                                </td>

                                <!-- ARCHIVE ACTION -->

                                <td>

                                    <form
                                        method="POST"
                                        action="/members/archive.php"
                                        onsubmit="return confirm('Are you sure you want to archive this member?');"
                                    >

                                        <?= csrfField() ?>

                                        <input
                                            type="hidden"
                                            name="member_id"
                                            value="<?= (int) $member['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="member-btn member-btn-danger member-btn-small"
                                        >
                                            ARCHIVE
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="10"
                                class="empty-row"
                            >

                                <?php if ($search !== ''): ?>

                                    No active members found matching
                                    "<?= memberEscape($search) ?>".

                                <?php else: ?>

                                    No active members registered yet.

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- MEMBER COUNT -->

        <p class="member-count">

            Total active records:

            <strong>
                <?= number_format($totalMembers) ?>
            </strong>

        </p>

    </section>

</main>

<!-- =====================================================
     JAVASCRIPT: SELECT ALL AND BULK ARCHIVE
===================================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const selectAll = document.getElementById(
        'selectAllMembers'
    );

    const checkboxes = document.querySelectorAll(
        '.member-checkbox'
    );

    const selectedCount = document.getElementById(
        'selectedCount'
    );

    const archiveButton = document.getElementById(
        'archiveSelectedButton'
    );

    function updateSelection() {

        const checkedBoxes = document.querySelectorAll(
            '.member-checkbox:checked'
        );

        const total = checkedBoxes.length;

        selectedCount.textContent =
            total +
            (total === 1
                ? ' member selected'
                : ' members selected'
            );

        archiveButton.disabled = total === 0;

        selectAll.checked =
            checkboxes.length > 0 &&
            total === checkboxes.length;

        selectAll.indeterminate =
            total > 0 &&
            total < checkboxes.length;
    }

    // SELECT ALL

    selectAll.addEventListener('change', function () {

        checkboxes.forEach(function (checkbox) {

            checkbox.checked = selectAll.checked;

        });

        updateSelection();

    });

    // INDIVIDUAL CHECKBOXES

    checkboxes.forEach(function (checkbox) {

        checkbox.addEventListener(
            'change',
            updateSelection
        );

    });

    updateSelection();

});

// CONFIRM BULK ARCHIVE

function confirmBulkArchive() {

    const selected = document.querySelectorAll(
        '.member-checkbox:checked'
    ).length;

    if (selected === 0) {

        alert('Please select at least one member.');

        return false;
    }

    return confirm(
        'Are you sure you want to archive ' +
        selected +
        ' selected member(s)?'
    );
}

</script>

<?php

// =====================================================
// SHARED FOOTER
// =====================================================

require_once __DIR__ . '/../includes/footer.php';

?>
