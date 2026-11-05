<?php

// =====================================================
// AS-SYSTEM
// ARCHIVED MEMBERS MANAGEMENT
// SUPABASE POSTGRESQL VERSION
// =====================================================

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// =====================================================
// PAGE SETTINGS
// =====================================================

$pageTitle = 'Archived Members';
$activePage = 'archived';

// =====================================================
// ESCAPE FUNCTION
// =====================================================

function archivedPageEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// =====================================================
// SEARCH
// =====================================================

$search = trim($_GET['search'] ?? '');

$members = [];
$totalArchived = 0;
$loadError = false;
$errorMessage = '';

try {

    // TOTAL ARCHIVED MEMBERS
    $countStmt = $pdo->query("
        SELECT COUNT(*)
        FROM public.members
        WHERE status = 'Archived'
    ");

    $totalArchived = (int) $countStmt->fetchColumn();

    // FETCH ARCHIVED MEMBERS
    $sql = "
        SELECT
            id,
            member_code,
            full_name,
            sex,
            age,
            contact_number,
            email,
            status,
            created_at
        FROM public.members
        WHERE status = 'Archived'
    ";

    $params = [];

    if ($search !== '') {

        $sql .= "
            AND (
                full_name ILIKE :search
                OR CAST(member_code AS TEXT) ILIKE :search
                OR contact_number ILIKE :search
                OR email ILIKE :search
            )
        ";

        $params['search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY full_name ASC, id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log('Archived Members Error: ' . $e->getMessage());

    $loadError = true;
    $errorMessage = 'Unable to load archived members. Please check your database connection.';
}

// =====================================================
// LOAD HEADER
// =====================================================

require_once __DIR__ . '/../includes/header.php';

?>

<style>

/* =====================================================
   ARCHIVED MEMBERS PAGE
===================================================== */

.archived-page {
    padding: 25px;
    background: #F8F5ED;
    min-height: 100vh;
    color: #26372D;
    box-sizing: border-box;
}

/* PAGE HEADING */

.archived-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 22px;
}

.archived-heading h1 {
    margin: 5px 0 7px;
    color: #26372D;
    font-size: 27px;
}

.archived-heading p {
    color: #748078;
    margin: 0;
    font-size: 13px;
}

/* CARDS */

.archived-card {
    background: #FFFFFF;
    border: 1px solid #E2E9E3;
    border-radius: 10px;
    padding: 22px;
    margin-bottom: 20px;
    box-sizing: border-box;
}

/* BUTTONS */

.archived-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 6px;
    padding: 10px 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    font-size: 11px;
    text-align: center;
    white-space: nowrap;
    transition: .2s ease;
}

.back-btn,
.search-btn {
    background: #315B43;
    color: #FFFFFF;
}

.back-btn:hover,
.search-btn:hover {
    background: #244832;
}

/* SUMMARY */

.archived-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
}

.archived-summary h3 {
    margin: 0;
    color: #405448;
    font-size: 15px;
}

.archived-count {
    font-size: 30px;
    font-weight: 700;
    color: #315B43;
}

/* SEARCH */

.archived-search {
    display: flex;
    gap: 9px;
    flex-wrap: wrap;
    margin-top: 18px;
}

.archived-search input {
    flex: 1;
    min-width: 220px;
    padding: 11px 13px;
    border: 1px solid #CCD7CE;
    border-radius: 7px;
    outline: none;
    font-size: 13px;
}

.archived-search input:focus {
    border-color: #477C59;
}

/* TABLE */

.archived-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
}

.archived-table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
    table-layout: auto;
}

.archived-table th,
.archived-table td {
    padding: 13px 11px;
    border-bottom: 1px solid #E8EDE9;
    text-align: left;
    vertical-align: middle;
}

.archived-table th {
    background: #F3F6F3;
    color: #405448;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.archived-table td {
    font-size: 12px;
    color: #46534A;
}

.archived-table tbody tr:hover {
    background: #FCFCFA;
}

/* STATUS */

.archived-status {
    display: inline-block;
    padding: 5px 9px;
    background: #FFF0DC;
    color: #8A5818;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

/* ACTIONS COLUMN */

.archived-table th:last-child,
.archived-table td:last-child {
    min-width: 190px;
    width: 190px;
    white-space: nowrap;
}

.archived-actions {
    display: flex !important;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
    min-width: 175px;
    visibility: visible;
    opacity: 1;
}

.archived-actions form {
    display: inline-flex !important;
    margin: 0;
    padding: 0;
}

.restore-btn,
.delete-btn {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 9px 12px;
    border: none;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
}

.restore-btn {
    background: #E3F2E7;
    color: #24663A;
}

.restore-btn:hover {
    background: #CDE8D4;
}

.delete-btn {
    background: #FDE8E7;
    color: #A52B27;
}

.delete-btn:hover {
    background: #F8D4D2;
}

/* MESSAGES */

.archived-message {
    padding: 13px 16px;
    background: #EDF5EF;
    border: 1px solid #D5E7D9;
    color: #315B43;
    border-radius: 7px;
    margin-bottom: 18px;
    font-size: 13px;
}

.archived-error {
    padding: 13px 16px;
    background: #FFF0ED;
    border: 1px solid #EAC8C2;
    color: #A12E25;
    border-radius: 7px;
    margin-bottom: 18px;
    font-size: 13px;
}

/* EMPTY STATE */

.archived-empty {
    text-align: center !important;
    padding: 40px !important;
    color: #78837B !important;
}

/* FOOTNOTE */

.archived-note {
    margin-top: 15px;
    color: #858D86;
    font-size: 11px;
}

/* MOBILE */

@media (max-width: 768px) {

    .archived-page {
        padding: 15px;
    }

    .archived-card {
        padding: 15px;
    }

    .archived-heading {
        align-items: flex-start;
    }

    .archived-search {
        flex-direction: column;
    }

    .archived-search input {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .archived-search .archived-btn {
        width: 100%;
    }

}

</style>

<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main-content">

    <div class="archived-page">

        <!-- PAGE HEADING -->

        <div class="archived-heading">

            <div>

                <span style="
                    color:#B18A43;
                    font-size:10px;
                    font-weight:700;
                    letter-spacing:2px;
                ">
                    MINISTRY RECORDS
                </span>

                <h1>Archived Members</h1>

                <p>
                    Manage members who have been archived
                    from the active member list.
                </p>

            </div>

            <a
                href="index.php"
                class="archived-btn back-btn"
            >
                ← BACK TO MEMBERS
            </a>

        </div>

        <!-- SUCCESS MESSAGE -->

        <?php if (!empty($_GET['message'])): ?>

            <div class="archived-message">
                <?= archivedPageEscape($_GET['message']) ?>
            </div>

        <?php endif; ?>

        <!-- ERROR MESSAGE -->

        <?php if ($loadError): ?>

            <div class="archived-error">
                <?= archivedPageEscape($errorMessage) ?>
            </div>

        <?php endif; ?>

        <!-- SUMMARY AND SEARCH -->

        <section class="archived-card">

            <div class="archived-summary">

                <div>

                    <h3>Total Archived Members</h3>

                    <p style="
                        color:#89928A;
                        font-size:11px;
                        margin:5px 0 0;
                    ">
                        Members currently removed from the active list.
                    </p>

                </div>

                <div class="archived-count">
                    <?= number_format($totalArchived) ?>
                </div>

            </div>

            <!-- SEARCH FORM -->

            <form
                method="GET"
                action="archived.php"
                class="archived-search"
            >

                <input
                    type="search"
                    name="search"
                    placeholder="Search name, member code, contact, or email..."
                    value="<?= archivedPageEscape($search) ?>"
                >

                <button
                    type="submit"
                    class="archived-btn search-btn"
                >
                    SEARCH
                </button>

                <a
                    href="archived.php"
                    class="archived-btn back-btn"
                >
                    CLEAR
                </a>

            </form>

        </section>

        <!-- ARCHIVED MEMBERS TABLE -->

        <section class="archived-card">

            <div class="archived-table-wrap">

                <table class="archived-table">

                    <thead>
                        <tr>
                            <th>Member Code</th>
                            <th>Full Name</th>
                            <th>Sex</th>
                            <th>Age</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($loadError): ?>

                        <tr>
                            <td colspan="8" class="archived-empty">
                                Records could not be loaded.
                            </td>
                        </tr>

                    <?php elseif (empty($members)): ?>

                        <tr>
                            <td colspan="8" class="archived-empty">

                                <?php if ($search !== ''): ?>

                                    No archived members match
                                    "<?= archivedPageEscape($search) ?>".

                                <?php else: ?>

                                    No archived members found.

                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($members as $member): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= archivedPageEscape($member['member_code']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= archivedPageEscape($member['full_name']) ?>
                                </td>

                                <td>
                                    <?= archivedPageEscape($member['sex']) ?>
                                </td>

                                <td>
                                    <?= archivedPageEscape($member['age'] ?? '—') ?>
                                </td>

                                <td>
                                    <?= archivedPageEscape($member['contact_number'] ?: '—') ?>
                                </td>

                                <td>
                                    <?= archivedPageEscape($member['email'] ?: '—') ?>
                                </td>

                                <td>
                                    <span class="archived-status">
                                        ARCHIVED
                                    </span>
                                </td>

                                <!-- ACTION BUTTONS -->

                                <td>

                                    <div class="archived-actions">

                                        <!-- RESTORE -->

                                        <form
                                            method="POST"
                                            action="restore.php"
                                            onsubmit="return confirm('Are you sure you want to restore this member to the active member list?');"
                                        >

                                            <?= csrfField() ?>

                                            <input
                                                type="hidden"
                                                name="member_id"
                                                value="<?= (int)$member['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="restore-btn"
                                            >
                                                RESTORE
                                            </button>

                                        </form>

                                        <!-- PERMANENT DELETE -->

                                        <form
                                            method="POST"
                                            action="permanent_delete.php"
                                            onsubmit="return confirm('WARNING: This action may permanently delete this member. Continue?');"
                                        >

                                            <?= csrfField() ?>

                                            <input
                                                type="hidden"
                                                name="member_id"
                                                value="<?= (int)$member['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="delete-btn"
                                            >
                                                DELETE
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <p class="archived-note">
                Note: Permanent deletion may be blocked when attendance
                records are associated with a member.
            </p>

        </section>

    </div>

</main>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>
