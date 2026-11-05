<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| QR SCANNER AND MANUAL ATTENDANCE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

date_default_timezone_set('Asia/Manila');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Create CSRF token if one does not exist.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$massTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

$selectedDate = date('Y-m-d');

$processUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/process_qr.php';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
.attendance-page {
    padding: 25px;
    color: #243247;
}

.attendance-heading {
    margin-bottom: 22px;
}

.attendance-heading h1 {
    color: #172d4b;
    font-size: 25px;
    margin: 0 0 7px;
}

.attendance-heading p {
    color: #718096;
    font-size: 13px;
    margin: 0;
}

.attendance-card {
    background: #fff;
    border: 1px solid #e5e9ef;
    border-radius: 11px;
    padding: 22px;
    margin-bottom: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,.035);
}

.attendance-card h2 {
    color: #1b365d;
    font-size: 17px;
    margin: 0 0 17px;
}

.attendance-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #45546a;
    margin-bottom: 8px;
}

.attendance-input {
    width: 100%;
    height: 43px;
    padding: 9px 12px;
    border: 1px solid #d8dee8;
    border-radius: 7px;
    background: #fff;
    color: #26364a;
    box-sizing: border-box;
    font-size: 13px;
}

.attendance-controls {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 16px;
    margin-bottom: 20px;
}

.mass-options {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 9px;
}

.mass-option {
    border: 1px solid #d8dee8;
    background: #f7f8fa;
    color: #34445a;
    padding: 12px 5px;
    border-radius: 7px;
    cursor: pointer;
    font-weight: 700;
    font-size: 12px;
}

.mass-option.active {
    background: #1b365d;
    color: white;
    border-color: #1b365d;
}

.attendance-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 17px;
}

.attendance-tab {
    padding: 10px 17px;
    border: 1px solid #d8dee8;
    background: #f7f8fa;
    border-radius: 7px;
    color: #34445a;
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
}

.attendance-tab.active {
    background: #1b365d;
    border-color: #1b365d;
    color: white;
}

.search-wrapper {
    position: relative;
    margin-bottom: 15px;
}

.search-wrapper input {
    width: 100%;
    height: 46px;
    border: 1px solid #d8dee8;
    border-radius: 7px;
    padding: 10px 14px 10px 40px;
    font-size: 13px;
    box-sizing: border-box;
}

.search-wrapper i {
    position: absolute;
    left: 14px;
    top: 16px;
    color: #8491a3;
}

.member-results {
    display: grid;
    gap: 10px;
}

.member-result {
    border: 1px solid #e5e9ef;
    border-radius: 9px;
    padding: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.member-result-name {
    color: #203b60;
    font-size: 14px;
    font-weight: 700;
}

.member-result-code {
    color: #8491a3;
    font-size: 11px;
    margin-top: 5px;
}

.member-result-actions {
    display: flex;
    gap: 8px;
}

.status-button {
    border: none;
    border-radius: 6px;
    padding: 10px 15px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    color: white;
}

.present-button {
    background: #21864e;
}

.excused-button {
    background: #426a9c;
}

.status-button:disabled {
    opacity: .6;
    cursor: wait;
}

.scanner-box {
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
}

#qr-reader {
    width: 100%;
    overflow: hidden;
    border-radius: 9px;
}

.scanner-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
}

.scanner-button {
    padding: 11px 18px;
    border: none;
    border-radius: 7px;
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.start-button {
    background: #21864e;
}

.stop-button {
    background: #b83b32;
}

.scanner-status {
    padding: 12px;
    margin-top: 15px;
    border-radius: 7px;
    background: #f1f4f8;
    color: #45546a;
    font-size: 12px;
    text-align: center;
}

.scanner-status.success {
    background: #e3f5e9;
    color: #21864e;
}

.scanner-status.error {
    background: #fde9e7;
    color: #bd3b31;
}

.empty-results {
    padding: 25px;
    text-align: center;
    color: #8491a3;
    font-size: 13px;
}

.manual-section,
.scanner-section {
    display: none;
}

.manual-section.active,
.scanner-section.active {
    display: block;
}

@media(max-width:700px) {
    .attendance-page {
        padding: 14px;
    }

    .attendance-controls {
        grid-template-columns: 1fr;
    }

    .mass-options {
        grid-template-columns: repeat(2, 1fr);
    }

    .member-result {
        align-items: flex-start;
    }

    .member-result-actions {
        width: 100%;
    }

    .member-result-actions button {
        flex: 1;
    }
}
</style>

<div class="attendance-page">

    <div class="attendance-heading">
        <h1>Attendance Recording</h1>
        <p>Record altar server attendance through QR scanning or manual search.</p>
    </div>

    <div class="attendance-card">

        <h2>Attendance Details</h2>

        <div class="attendance-controls">

            <div>
                <label class="attendance-label" for="mass-date">
                    Attendance Date
                </label>

                <input
                    type="date"
                    id="mass-date"
                    class="attendance-input"
                    value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>"
                >
            </div>

            <div>
                <label class="attendance-label">
                    Select Sunday Mass Schedule
                </label>

                <div class="mass-options">

                    <?php foreach ($massTimes as $index => $time): ?>

                        <button
                            type="button"
                            class="mass-option <?= $index === 0 ? 'active' : '' ?>"
                            data-mass-time="<?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>
                        </button>

                    <?php endforeach; ?>

                </div>
            </div>

        </div>

        <div class="attendance-tabs">

            <button
                type="button"
                class="attendance-tab active"
                data-tab="scanner"
            >
                <i class="fas fa-qrcode"></i>
                QR Scanner
            </button>

            <button
                type="button"
                class="attendance-tab"
                data-tab="manual"
            >
                <i class="fas fa-search"></i>
                Search Member
            </button>

        </div>

        <!-- QR SCANNER -->

        <div class="scanner-section active" id="scanner-section">

            <h2>Scan Member QR Code</h2>

            <div class="scanner-box">

                <div id="qr-reader"></div>

                <div class="scanner-buttons">

                    <button
                        type="button"
                        class="scanner-button start-button"
                        id="start-scanner"
                    >
                        <i class="fas fa-camera"></i>
                        Start Scanner
                    </button>

                    <button
                        type="button"
                        class="scanner-button stop-button"
                        id="stop-scanner"
                    >
                        Stop Scanner
                    </button>

                </div>

                <div class="scanner-status" id="scanner-status">
                    Select a Mass schedule and start scanning.
                </div>

            </div>

        </div>

        <!-- MANUAL ATTENDANCE -->

        <div class="manual-section" id="manual-section">

            <h2>Search and Record Member Attendance</h2>

            <div class="search-wrapper">

                <i class="fas fa-search"></i>

                <input
                    type="text"
                    id="member-search"
                    placeholder="Search member name or member code..."
                    autocomplete="off"
                >

            </div>

            <div class="member-results" id="member-results">

                <div class="empty-results">
                    Enter a member name or member code to search.
                </div>

            </div>

        </div>

    </div>

</div>

<input
    type="hidden"
    id="csrf-token"
    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
(() => {

    const processUrl = <?= json_encode($processUrl) ?>;

    const csrfToken = document.getElementById('csrf-token').value;
    const dateInput = document.getElementById('mass-date');
    const searchInput = document.getElementById('member-search');
    const resultsContainer = document.getElementById('member-results');
    const statusBox = document.getElementById('scanner-status');

    let selectedMassTime = '6:00 AM';
    let html5QrCode = null;
    let scannerRunning = false;
    let searchTimeout = null;
    let lastScannedToken = '';
    let lastScanTime = 0;

    // -------------------------------------------------
    // NOTIFICATIONS
    // -------------------------------------------------

    function showStatus(message, type = '') {

        statusBox.textContent = message;
        statusBox.className = 'scanner-status ' + type;

    }

    // -------------------------------------------------
    // SELECT MASS TIME
    // -------------------------------------------------

    document.querySelectorAll('.mass-option').forEach(button => {

        button.addEventListener('click', () => {

            document.querySelectorAll('.mass-option').forEach(item => {
                item.classList.remove('active');
            });

            button.classList.add('active');

            selectedMassTime = button.dataset.massTime;

            showStatus('Selected Mass schedule: ' + selectedMassTime);

        });

    });

    // -------------------------------------------------
    // SWITCH TABS
    // -------------------------------------------------

    document.querySelectorAll('.attendance-tab').forEach(button => {

        button.addEventListener('click', () => {

            document.querySelectorAll('.attendance-tab').forEach(item => {
                item.classList.remove('active');
            });

            button.classList.add('active');

            document.querySelectorAll('.scanner-section, .manual-section')
                .forEach(section => section.classList.remove('active'));

            document.getElementById(
                button.dataset.tab + '-section'
            ).classList.add('active');

            if (button.dataset.tab === 'manual' && scannerRunning) {
                stopScanner();
            }

        });

    });

    // -------------------------------------------------
    // SEND REQUEST
    // -------------------------------------------------

    async function sendRequest(data) {

        const formData = new FormData();

        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        formData.append('csrf_token', csrfToken);
        formData.append('mass_date', dateInput.value);
        formData.append('mass_time', selectedMassTime);

        const response = await fetch(processUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to process attendance.');
        }

        return result;
    }

    // -------------------------------------------------
    // MANUAL MEMBER SEARCH
    // -------------------------------------------------

    searchInput.addEventListener('input', () => {

        clearTimeout(searchTimeout);

        const query = searchInput.value.trim();

        if (query.length < 2) {

            resultsContainer.innerHTML = `
                <div class="empty-results">
                    Enter at least two characters to search.
                </div>
            `;

            return;
        }

        resultsContainer.innerHTML = `
            <div class="empty-results">Searching members...</div>
        `;

        searchTimeout = setTimeout(async () => {

            try {

                const result = await sendRequest({
                    action: 'search',
                    search: query
                });

                renderMembers(result.members || []);

            } catch (error) {

                resultsContainer.innerHTML = `
                    <div class="empty-results">
                        ${escapeHtml(error.message)}
                    </div>
                `;

            }

        }, 350);

    });

    // -------------------------------------------------
    // DISPLAY SEARCH RESULTS
    // -------------------------------------------------

    function renderMembers(members) {

        if (members.length === 0) {

            resultsContainer.innerHTML = `
                <div class="empty-results">
                    No matching active members found.
                </div>
            `;

            return;
        }

        resultsContainer.innerHTML = '';

        members.forEach(member => {

            const card = document.createElement('div');

            card.className = 'member-result';

            card.innerHTML = `
                <div>
                    <div class="member-result-name">
                        ${escapeHtml(member.full_name)}
                    </div>

                    <div class="member-result-code">
                        Member Code: ${escapeHtml(member.member_code)}
                    </div>
                </div>

                <div class="member-result-actions">

                    <button
                        type="button"
                        class="status-button present-button"
                        data-status="Present"
                    >
                        <i class="fas fa-check"></i>
                        Present
                    </button>

                    <button
                        type="button"
                        class="status-button excused-button"
                        data-status="Excused"
                    >
                        <i class="fas fa-calendar-check"></i>
                        Excused
                    </button>

                </div>
            `;

            card.querySelectorAll('.status-button').forEach(button => {

                button.addEventListener('click', async () => {

                    const attendanceStatus = button.dataset.status;

                    card.querySelectorAll('button').forEach(item => {
                        item.disabled = true;
                    });

                    button.textContent = 'Saving...';

                    try {

                        const result = await sendRequest({
                            action: 'record_manual',
                            member_id: member.id,
                            attendance_status: attendanceStatus
                        });

                        card.innerHTML = `
                            <div>
                                <div class="member-result-name">
                                    ${escapeHtml(member.full_name)}
                                </div>

                                <div class="member-result-code">
                                    ${escapeHtml(result.message)}
                                </div>
                            </div>

                            <span class="attendance-badge">
                                ${escapeHtml(attendanceStatus)}
                            </span>
                        `;

                        card.style.borderColor = '#21864e';

                    } catch (error) {

                        card.querySelectorAll('button').forEach(item => {
                            item.disabled = false;
                        });

                        button.textContent = attendanceStatus;

                        alert(error.message);

                    }

                });

            });

            resultsContainer.appendChild(card);

        });

    }

    // -------------------------------------------------
    // QR SCAN CALLBACK
    // -------------------------------------------------

    async function onScanSuccess(decodedText) {

        const now = Date.now();

        if (
            decodedText === lastScannedToken &&
            now - lastScanTime < 3000
        ) {
            return;
        }

        lastScannedToken = decodedText;
        lastScanTime = now;

        if (!/^[a-f0-9]{64}$/i.test(decodedText)) {

            showStatus('Invalid QR code. Please scan a registered member QR code.', 'error');
            return;

        }

        showStatus('QR code detected. Recording attendance...');

        try {

            const result = await sendRequest({
                action: 'record_qr',
                qr_token: decodedText
            });

            showStatus(
                result.member_name +
                ' successfully recorded as Present. ' +
                result.check_in_time,
                'success'
            );

        } catch (error) {

            showStatus(error.message, 'error');

        }

    }

    // -------------------------------------------------
    // START SCANNER
    // -------------------------------------------------

    async function startScanner() {

        if (scannerRunning) {
            return;
        }

        try {

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode('qr-reader');
            }

            await html5QrCode.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: {
                        width: 250,
                        height: 250
                    }
                },
                onScanSuccess
            );

            scannerRunning = true;

            showStatus('Camera started. Scan a member QR code.');

        } catch (error) {

            showStatus(
                'Unable to start camera. Allow camera permission and use localhost or HTTPS.',
                'error'
            );

        }

    }

    // -------------------------------------------------
    // STOP SCANNER
    // -------------------------------------------------

    async function stopScanner() {

        if (!html5QrCode || !scannerRunning) {
            return;
        }

        try {

            await html5QrCode.stop();

            scannerRunning = false;

            showStatus('Scanner stopped.');

        } catch (error) {

            showStatus('Unable to stop scanner.', 'error');

        }

    }

    document.getElementById('start-scanner')
        .addEventListener('click', startScanner);

    document.getElementById('stop-scanner')
        .addEventListener('click', stopScanner);

    // -------------------------------------------------
    // ESCAPE HTML
    // -------------------------------------------------

    function escapeHtml(value) {

        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[character]);

    }

    // Stop scanner when leaving page.
    window.addEventListener('beforeunload', () => {

        if (scannerRunning && html5QrCode) {
            html5QrCode.stop().catch(() => {});
        }

    });

})();
</script>

<?php
// END OF ATTENDANCE SCANNER
?>
