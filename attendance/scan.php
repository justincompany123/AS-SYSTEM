<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| QR CODE ATTENDANCE SCANNER
| St. Francis Xavier Cathedral
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$today = date('Y-m-d');

$processUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/process_qr.php';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
.attendance-container {
    padding: 24px;
    width: 100%;
}

.attendance-header {
    margin-bottom: 24px;
}

.attendance-header h2 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 6px;
}

.attendance-header p {
    color: #777;
    margin: 0;
}

.attendance-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(300px, 0.7fr);
    gap: 22px;
    align-items: start;
}

.attendance-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 3px 14px rgba(0,0,0,.07);
    min-width: 0;
}

.attendance-card h3 {
    font-size: 19px;
    font-weight: 700;
    margin-bottom: 18px;
}

.attendance-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 22px;
}

.attendance-form label,
.manual-search label {
    display: block;
    font-weight: 600;
    margin-bottom: 7px;
}

.attendance-form input,
.attendance-form select,
.manual-search input,
.manual-search select {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    background: #fff;
}

.attendance-form input:focus,
.attendance-form select:focus,
.manual-search input:focus,
.manual-search select:focus {
    border-color: #2563eb;
}

.scanner-box {
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
}

#reader {
    width: 100%;
    overflow: hidden;
    border-radius: 12px;
}

.scanner-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 18px;
}

.btn {
    border: none;
    border-radius: 8px;
    padding: 11px 17px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}

.btn:disabled {
    opacity: .55;
    cursor: not-allowed;
}

.btn-primary {
    background: #17365d;
    color: #fff;
}

.btn-primary:hover:not(:disabled) {
    background: #0f2745;
}

.btn-success {
    background: #198754;
    color: #fff;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-warning {
    background: #f0ad4e;
    color: #212529;
}

.scan-result {
    margin-top: 18px;
    padding: 14px;
    border-radius: 9px;
    display: none;
    overflow-wrap: anywhere;
}

.scan-result.success {
    display: block;
    background: #e8f7ee;
    border: 1px solid #b7e4c7;
    color: #155724;
}

.scan-result.error {
    display: block;
    background: #fdeaea;
    border: 1px solid #f5c2c7;
    color: #842029;
}

.scan-result.info {
    display: block;
    background: #e7f1ff;
    border: 1px solid #b6d4fe;
    color: #084298;
}

.tab-buttons {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}

.tab-button {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
    padding: 10px;
    font-weight: 600;
    cursor: pointer;
}

.tab-button.active {
    background: #17365d;
    color: #fff;
    border-color: #17365d;
}

.manual-search {
    display: none;
}

.manual-search input {
    margin-bottom: 12px;
}

.member-results {
    display: grid;
    gap: 12px;
    margin-top: 16px;
}

.member-item {
    border: 1px solid #e5e7eb;
    padding: 14px;
    border-radius: 10px;
}

.member-item strong {
    display: block;
    margin-bottom: 4px;
}

.member-item small {
    color: #777;
}

.member-item-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.member-item-actions button {
    flex: 1;
    min-width: 100px;
}

.empty-message {
    color: #777;
    text-align: center;
    padding: 18px;
}

@media (max-width: 900px) {
    .attendance-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .attendance-container {
        padding: 14px;
    }

    .attendance-card {
        padding: 16px;
    }

    .attendance-form {
        grid-template-columns: 1fr;
    }

    .attendance-header h2 {
        font-size: 22px;
    }

    .scanner-actions {
        flex-direction: column;
    }

    .scanner-actions .btn {
        width: 100%;
    }
}
</style>

<div class="attendance-container">

    <div class="attendance-header">
        <h2>QR Code Attendance Scanner</h2>
        <p>Scan an altar server's QR code to record Mass attendance.</p>
    </div>

    <div class="attendance-layout">

        <div class="attendance-card">

            <h3>Attendance Scanner</h3>

            <div class="attendance-form">

                <div>
                    <label for="mass_date">Mass Date</label>
                    <input
                        type="date"
                        id="mass_date"
                        value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>"
                        max="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div>
                    <label for="mass_time">Mass Schedule</label>
                    <select id="mass_time">
                        <?php foreach ($massTimes as $time): ?>
                            <option value="<?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="tab-buttons">
                <button type="button" class="tab-button active" id="qrTab">
                    QR Scanner
                </button>

                <button type="button" class="tab-button" id="manualTab">
                    Manual Search
                </button>
            </div>

            <div id="scannerPanel">

                <div class="scanner-box">
                    <div id="reader"></div>
                </div>

                <div class="scanner-actions">
                    <button type="button" class="btn btn-primary" id="startScanner">
                        Start Scanner
                    </button>

                    <button type="button" class="btn btn-secondary" id="stopScanner" disabled>
                        Stop Scanner
                    </button>
                </div>

            </div>

            <div class="manual-search" id="manualPanel">

                <label for="memberSearch">Search Member</label>

                <input
                    type="text"
                    id="memberSearch"
                    placeholder="Enter member name or member code..."
                    autocomplete="off"
                >

                <div id="memberResults" class="member-results">
                    <div class="empty-message">
                        Enter a member name or code to search.
                    </div>
                </div>

            </div>

            <div id="scanResult" class="scan-result"></div>

        </div>

        <div class="attendance-card">

            <h3>Attendance Instructions</h3>

            <ol>
                <li>Select the correct Mass date.</li>
                <li>Select the correct Mass schedule.</li>
                <li>Click Start Scanner.</li>
                <li>Place the member's QR code in front of the camera.</li>
                <li>Wait for the attendance confirmation.</li>
            </ol>

            <hr>

            <h3>Available Mass Schedules</h3>

            <ul>
                <?php foreach ($massTimes as $time): ?>
                    <li><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>

            <p style="color:#777; font-size:13px;">
                Each member can only have one attendance record for the same
                date and Mass schedule.
            </p>

        </div>

    </div>

</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
const PROCESS_URL = <?= json_encode($processUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;

let html5QrCode = null;
let scannerRunning = false;
let processingScan = false;
let lastScannedToken = '';
let lastScanTime = 0;
let searchTimer = null;

const reader = document.getElementById('reader');
const scanResult = document.getElementById('scanResult');
const startButton = document.getElementById('startScanner');
const stopButton = document.getElementById('stopScanner');

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char];
    });
}

function showResult(message, type = 'info') {
    scanResult.className = 'scan-result ' + type;
    scanResult.textContent = message;
}

function getAttendanceDetails() {
    return {
        mass_date: document.getElementById('mass_date').value,
        mass_time: document.getElementById('mass_time').value
    };
}

async function sendRequest(formData) {
    const response = await fetch(PROCESS_URL, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json'
        }
    });

    const responseText = await response.text();

    let result;

    try {
        result = JSON.parse(responseText);
    } catch (error) {
        console.error('Server returned a non-JSON response:', responseText);
        throw new Error(
            'The server returned an invalid response. Check your PHP error logs.'
        );
    }

    if (!response.ok && !result.message) {
        throw new Error('The server could not process the request.');
    }

    return result;
}

async function recordQrAttendance(decodedText) {

    const token = String(decodedText).trim();

    if (!/^[a-f0-9]{64}$/i.test(token)) {
        showResult('Invalid QR code. This is not a valid member QR token.', 'error');
        return;
    }

    const now = Date.now();

    if (
        token === lastScannedToken &&
        now - lastScanTime < 3000
    ) {
        return;
    }

    if (processingScan) {
        return;
    }

    processingScan = true;
    lastScannedToken = token;
    lastScanTime = now;

    showResult('Processing QR code attendance...', 'info');

    try {
        const details = getAttendanceDetails();

        const formData = new FormData();

        formData.append('action', 'record_qr');
        formData.append('csrf_token', CSRF_TOKEN);
        formData.append('qr_token', token);
        formData.append('mass_date', details.mass_date);
        formData.append('mass_time', details.mass_time);

        const result = await sendRequest(formData);

        if (result.success) {
            showResult(
                'Attendance recorded successfully!\n' +
                'Member: ' + result.member_name + '\n' +
                'Date: ' + result.mass_date + '\n' +
                'Mass: ' + result.mass_time + '\n' +
                'Status: ' + result.attendance_status + '\n' +
                'Check-in: ' + result.check_in_time,
                'success'
            );
        } else {
            showResult(result.message || 'Unable to record attendance.', 'error');
        }

    } catch (error) {
        console.error(error);
        showResult(error.message || 'An unexpected error occurred.', 'error');
    } finally {
        processingScan = false;
    }
}

async function startScanner() {

    if (scannerRunning) {
        return;
    }

    try {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('reader');
        }

        startButton.disabled = true;

        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                }
            },
            recordQrAttendance,
            function() {
                // Ignore temporary QR decoding failures.
            }
        );

        scannerRunning = true;
        startButton.disabled = true;
        stopButton.disabled = false;

        showResult('Scanner started. Point the camera at a member QR code.', 'info');

    } catch (error) {
        console.error(error);

        startButton.disabled = false;
        showResult(
            'Unable to start the scanner. Allow camera permission and use HTTPS.',
            'error'
        );
    }
}

async function stopScanner() {

    if (!html5QrCode || !scannerRunning) {
        return;
    }

    try {
        await html5QrCode.stop();
        scannerRunning = false;

        startButton.disabled = false;
        stopButton.disabled = true;

        showResult('Scanner stopped.', 'info');

    } catch (error) {
        console.error(error);
        showResult('Unable to stop the scanner properly.', 'error');
    }
}

startButton.addEventListener('click', startScanner);
stopButton.addEventListener('click', stopScanner);

document.getElementById('qrTab').addEventListener('click', async function() {

    await stopScanner();

    document.getElementById('qrTab').classList.add('active');
    document.getElementById('manualTab').classList.remove('active');

    document.getElementById('scannerPanel').style.display = 'block';
    document.getElementById('manualPanel').style.display = 'none';
});

document.getElementById('manualTab').addEventListener('click', async function() {

    await stopScanner();

    document.getElementById('manualTab').classList.add('active');
    document.getElementById('qrTab').classList.remove('active');

    document.getElementById('scannerPanel').style.display = 'none';
    document.getElementById('manualPanel').style.display = 'block';
});

document.getElementById('memberSearch').addEventListener('input', function() {

    clearTimeout(searchTimer);

    const search = this.value.trim();

    if (search.length < 2) {
        document.getElementById('memberResults').innerHTML =
            '<div class="empty-message">Enter at least two characters.</div>';
        return;
    }

    searchTimer = setTimeout(function() {
        searchMembers(search);
    }, 350);
});

async function searchMembers(search) {

    const formData = new FormData();

    formData.append('action', 'search');
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('search', search);

    try {
        const result = await sendRequest(formData);

        if (!result.success) {
            document.getElementById('memberResults').innerHTML =
                '<div class="empty-message">' +
                escapeHtml(result.message || 'No members found.') +
                '</div>';
            return;
        }

        renderMembers(result.members || []);

    } catch (error) {
        console.error(error);

        document.getElementById('memberResults').innerHTML =
            '<div class="empty-message">' +
            escapeHtml(error.message) +
            '</div>';
    }
}

function renderMembers(members) {

    const container = document.getElementById('memberResults');

    if (!members.length) {
        container.innerHTML =
            '<div class="empty-message">No active members found.</div>';
        return;
    }

    container.innerHTML = members.map(function(member) {

        return `
            <div class="member-item">
                <strong>${escapeHtml(member.full_name)}</strong>
                <small>${escapeHtml(member.member_code)}</small>

                <div class="member-item-actions">
                    <button
                        type="button"
                        class="btn btn-success"
                        onclick="recordManualAttendance(${Number(member.id)}, 'Present')"
                    >
                        Present
                    </button>

                    <button
                        type="button"
                        class="btn btn-warning"
                        onclick="recordManualAttendance(${Number(member.id)}, 'Excused')"
                    >
                        Excused
                    </button>
                </div>
            </div>
        `;

    }).join('');
}

async function recordManualAttendance(memberId, status) {

    if (!Number.isInteger(memberId) || memberId <= 0) {
        showResult('Invalid member ID.', 'error');
        return;
    }

    const details = getAttendanceDetails();

    const formData = new FormData();

    formData.append('action', 'record_manual');
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('member_id', memberId);
    formData.append('attendance_status', status);
    formData.append('mass_date', details.mass_date);
    formData.append('mass_time', details.mass_time);

    try {
        showResult('Saving attendance...', 'info');

        const result = await sendRequest(formData);

        if (result.success) {
            showResult(
                'Attendance recorded successfully!\n' +
                'Member: ' + result.member_name + '\n' +
                'Status: ' + result.attendance_status + '\n' +
                'Check-in: ' + result.check_in_time,
                'success'
            );
        } else {
            showResult(result.message || 'Unable to record attendance.', 'error');
        }

    } catch (error) {
        console.error(error);
        showResult(error.message || 'An unexpected error occurred.', 'error');
    }
}

window.addEventListener('beforeunload', function() {
    if (scannerRunning && html5QrCode) {
        html5QrCode.stop().catch(function() {});
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```