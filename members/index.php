<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| MEMBER MANAGEMENT
| ST. FRANCIS XAVIER CATHEDRAL
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Member Management';
$activePage = 'members';

/*
|--------------------------------------------------------------------------
| HTML ESCAPE
|--------------------------------------------------------------------------
*/

function memberEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$members = [];
$errorMessage = '';

try {
    if ($search !== '') {
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
            WHERE status = 'Active'
              AND (
                    member_code ILIKE :search
                    OR full_name ILIKE :search
                    OR contact_number ILIKE :search
                    OR email ILIKE :search
              )
            ORDER BY full_name ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':search' => '%' . $search . '%'
        ]);
    } else {
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
            WHERE status = 'Active'
            ORDER BY full_name ASC
        ";

        $stmt = $pdo->query($sql);
    }

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('MEMBER LIST ERROR: ' . $e->getMessage());
    $errorMessage = 'Unable to load the members list. Please try again.';
}

$totalMembers = count($members);

/*
|--------------------------------------------------------------------------
| SHARED HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.member-page {
    padding: 25px;
}

.member-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.member-heading h1 {
    margin: 0 0 6px;
    color: #142b4a;
    font-size: 27px;
}

.member-heading p {
    margin: 0;
    color: #718096;
}

.member-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.member-btn {
    display: inline-block;
    padding: 11px 17px;
    border-radius: 7px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
}

.member-btn-primary {
    background: #142b4a;
    color: #fff;
}

.member-btn-secondary {
    background: #e9edf2;
    color: #26384d;
}

.member-btn-danger {
    background: #b42318;
    color: #fff;
}

.member-summary {
    background: #fff;
    border: 1px solid #e5eaf0;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    max-width: 260px;
}

.member-summary span {
    display: block;
    color: #718096;
    font-size: 13px;
}

.member-summary strong {
    display: block;
    color: #142b4a;
    font-size: 28px;
    margin-top: 5px;
}

.member-panel {
    background: #fff;
    border: 1px solid #e5eaf0;
    border-radius: 10px;
    padding: 20px;
}

.member-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.member-search {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.member-search input {
    width: 280px;
    max-width: 100%;
    padding: 11px 13px;
    border: 1px solid #d7dee8;
    border-radius: 7px;
    font-size: 14px;
}

.member-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.member-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.member-table th,
.member-table td {
    padding: 13px 12px;
    text-align: left;
    border-bottom: 1px solid #edf0f4;
    font-size: 13px;
}

.member-table th {
    background: #f5f7fa;
    color: #526174;
    font-weight: 700;
}

.member-table td {
    color: #344054;
}

.member-table tr:hover td {
    background: #fafbfd;
}

.member-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    background: #e7f6ec;
    color: #18723a;
    font-size: 12px;
    font-weight: 700;
}

.member-link {
    color: #174a7e;
    text-decoration: none;
    font-weight: 600;
}

.member-link:hover {
    text-decoration: underline;
}

.member-empty {
    text-align: center;
    padding: 35px 15px;
    color: #718096;
}

.member-alert {
    padding: 13px 15px;
    border-radius: 7px;
    margin-bottom: 18px;
    background: #fff0f0;
    color: #a12626;
}

.member-bulk-form {
    display: inline;
}

@media (max-width: 700px) {
    .member-page {
        padding: 15px;
    }

    .member-heading {
        align-items: flex-start;
    }

    .member-search {
        width: 100%;
    }

    .member-search input {
        width: 100%;
    }

    .member-panel {
        padding: 13px;
    }
}
</style>

<main class="member-page">

    <div class="member-heading">

        <div>
            <h1>Member Management</h1>
            <p>View and manage the registered altar servers.</p>
        </div>

        <div class="member-actions">

            <a href="/members/archived.php"
               class="member-btn member-btn-secondary">
                Archived Members
            </a>

            <a href="/members/add.php"
               class="member-btn member-btn-primary">
                + Register New Member
            </a>

        </div>

    </div>

    <div class="member-summary">
        <span>Active Members</span>
        <strong><?= number_format($totalMembers) ?></strong>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="member-alert">
            <?= memberEscape($errorMessage) ?>
        </div>
    <?php endif; ?>

    <section class="member-panel">

        <div class="member-toolbar">

            <div>
                <h2 style="margin:0; color:#142b4a;">
                    Registered Members
                </h2>
                <p style="margin:5px 0 0; color:#718096; font-size:13px;">
                    List of active members in the ministry.
                </p>
            </div>

            <form method="GET"
                  action="/members/index.php"
                  class="member-search">

                <input
                    type="search"
                    name="search"
                    placeholder="Search name, code, contact..."
                    value="<?= memberEscape($search) ?>"
                >

                <button type="submit"
                        class="member-btn member-btn-primary">
                    Search
                </button>

                <?php if ($search !== ''): ?>
                    <a href="/members/index.php"
                       class="member-btn member-btn-secondary">
                        Clear
                    </a>
                <?php endif; ?>

            </form>

        </div>

        <?php if (!$errorMessage): ?>

            <form method="POST"
                  action="/members/archive.php"
                  id="bulkArchiveForm"
                  onsubmit="return confirm('Archive the selected members?');">

                <?= csrfField() ?>

                <div style="margin-bottom:15px;">
                    <button type="submit"
                            class="member-btn member-btn-danger"
                            id="archiveSelectedButton"
                            disabled>
                        Archive Selected
                    </button>
                </div>

                <div class="member-table-wrap">

                    <table class="member-table">

                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox"
                                           id="selectAll"
                                           aria-label="Select all members">
                                </th>
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

                        <?php if (empty($members)): ?>

                            <tr>
                                <td colspan="9" class="member-empty">
                                    <?php if ($search !== ''): ?>
                                        No members found matching
                                        "<?= memberEscape($search) ?>".
                                    <?php else: ?>
                                        No active members found.
                                        Register a new member to get started.
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($members as $member): ?>

                                <tr>

                                    <td>
                                        <input
                                            type="checkbox"
                                            class="member-checkbox"
                                            name="member_ids[]"
                                            value="<?= (int)$member['id'] ?>"
                                            aria-label="Select member"
                                        >
                                    </td>

                                    <td>
                                        <?= memberEscape($member['member_code']) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= memberEscape($member['full_name']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= memberEscape($member['sex']) ?>
                                    </td>

                                    <td>
                                        <?= memberEscape($member['age']) ?>
                                    </td>

                                    <td>
                                        <?= memberEscape($member['contact_number']) ?>
                                    </td>

                                    <td>
                                        <?= memberEscape($member['email']) ?>
                                    </td>

                                    <td>
                                        <span class="member-status">
                                            <?= memberEscape($member['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            class="member-link"
                                            href="/members/qr_card.php?id=<?= (int)$member['id'] ?>"
                                        >
                                            View QR
                                        </a>

                                        <span style="color:#b8c0cc;"> | </span>

                                        <button
                                            type="button"
                                            class="member-link"
                                            style="background:none;border:0;cursor:pointer;"
                                            onclick="archiveOneMember(<?= (int)$member['id'] ?>)"
                                        >
                                            Archive
                                        </button>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </form>

            <!-- Individual archive form -->
            <form method="POST"
                  action="/members/archive.php"
                  id="singleArchiveForm"
                  style="display:none;">

                <?= csrfField() ?>

                <input type="hidden"
                       name="member_id"
                       id="singleArchiveMemberId">

            </form>

        <?php endif; ?>

    </section>

</main>

<script>
const selectAll = document.getElementById('selectAll');
const memberCheckboxes = document.querySelectorAll('.member-checkbox');
const archiveSelectedButton = document.getElementById('archiveSelectedButton');

function updateArchiveButton() {
    if (!archiveSelectedButton) return;

    const selectedCount = document.querySelectorAll(
        '.member-checkbox:checked'
    ).length;

    archiveSelectedButton.disabled = selectedCount === 0;
    archiveSelectedButton.textContent = selectedCount > 0
        ? 'Archive Selected (' + selectedCount + ')'
        : 'Archive Selected';
}

if (selectAll) {
    selectAll.addEventListener('change', function () {
        memberCheckboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });

        updateArchiveButton();
    });
}

memberCheckboxes.forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
        if (selectAll) {
            selectAll.checked =
                memberCheckboxes.length > 0 &&
                document.querySelectorAll('.member-checkbox:checked').length
                    === memberCheckboxes.length;
        }

        updateArchiveButton();
    });
});

function archiveOneMember(memberId) {
    if (!confirm('Are you sure you want to archive this member?')) {
        return;
    }

    document.getElementById('singleArchiveMemberId').value = memberId;
    document.getElementById('singleArchiveForm').submit();
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
