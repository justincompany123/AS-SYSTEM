
<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Member Management';
$activePage = 'members';

function memberEscape($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT id, member_code, first_name, middle_name, last_name,
           contact_number, member_status, created_at
    FROM members
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE member_code LIKE :search
           OR first_name LIKE :search
           OR middle_name LIKE :search
           OR last_name LIKE :search
    ";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY last_name ASC, first_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.main-content { padding: 30px; background:#F8F5ED; min-height:100vh; }
.member-panel { background:#fff; border:1px solid #E9E1D3; border-radius:10px; padding:22px; }
.member-toolbar { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.member-search { display:flex; gap:8px; flex-wrap:wrap; }
.member-search input { padding:10px 12px; border:1px solid #DDD5C8; border-radius:6px; }
.member-btn { display:inline-block; padding:10px 14px; border-radius:6px; text-decoration:none; font-size:12px; font-weight:600; border:0; cursor:pointer; }
.member-btn-primary { background:#172C46; color:#fff; }
.member-btn-gold { background:#B18A43; color:#fff; }
.member-table-wrap { overflow-x:auto; }
.member-table { width:100%; border-collapse:collapse; font-size:12px; }
.member-table th,.member-table td { padding:13px 10px; text-align:left; border-bottom:1px solid #EEE7DA; white-space:nowrap; }
.member-table th { color:#7B6A50; background:#FBF9F4; }
.member-status { display:inline-block; padding:5px 9px; border-radius:20px; font-size:10px; background:#EAF4EA; color:#34713B; }
.member-status.inactive { background:#F3ECE4; color:#87632D; }
.empty-row { text-align:center!important; padding:35px!important; color:#888; }
@media(max-width:600px) { .main-content { padding:18px 12px; } }
</style>

<main class="main-content">

    <div class="page-heading">
        <div>
            <span style="color:#B18A43;font-size:10px;font-weight:700;letter-spacing:2px;">MINISTRY RECORDS</span>
            <h1>Member Management</h1>
            <p>Register and manage altar server members and their permanent QR codes.</p>
        </div>
    </div>

    <section class="member-panel">

        <div class="member-toolbar">

            <form method="GET" class="member-search">
                <input
                    type="search"
                    name="search"
                    placeholder="Search member name or code..."
                    value="<?= memberEscape($search) ?>"
                >
                <button type="submit" class="member-btn member-btn-primary">SEARCH</button>
                <a href="index.php" class="member-btn" style="background:#EEEAE2;color:#555;">RESET</a>
            </form>

            <a href="add.php" class="member-btn member-btn-gold">+ REGISTER MEMBER</a>

        </div>

        <div class="member-table-wrap">
            <table class="member-table">
                <thead>
                    <tr>
                        <th>Member Code</th>
                        <th>Full Name</th>
                        <th>Contact Number</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>QR Card</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($members): ?>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><strong><?= memberEscape($member['member_code']) ?></strong></td>

                            <td>
                                <?= memberEscape(trim(
                                    $member['first_name'] . ' ' .
                                    ($member['middle_name'] ? $member['middle_name'] . ' ' : '') .
                                    $member['last_name']
                                )) ?>
                            </td>

                            <td><?= memberEscape($member['contact_number'] ?: '—') ?></td>

                            <td>
                                <span class="member-status <?= $member['member_status'] === 'Inactive' ? 'inactive' : '' ?>">
                                    <?= memberEscape($member['member_status']) ?>
                                </span>
                            </td>

                            <td><?= date('M d, Y', strtotime($member['created_at'])) ?></td>

                            <td>
                                <a
                                    class="member-btn member-btn-primary"
                                    href="qr_card.php?id=<?= (int)$member['id'] ?>"
                                    target="_blank"
                                >VIEW QR CARD</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-row">
                            No members found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p style="font-size:11px;color:#888;margin-top:16px;">
            Total records: <?= count($members) ?>
        </p>

    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
