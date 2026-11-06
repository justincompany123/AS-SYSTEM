<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| QR CODE ATTENDANCE SCANNER
| SUPABASE POSTGRESQL VERSION
| St. Francis Xavier Cathedral
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$pageTitle = 'QR Code Attendance';
$activePage = 'attendance';

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
    width: 100%;
    padding: 24px;
    box-sizing: border-box;
}

.attendance-header {
    margin-bottom: 24px;
}

.attendance-header h2 {
    font-size: 26px;
    font-weight: 700;
    margin: 0 0 6px;
    color: #17365d;
}

.attendance-header p {
    color: #777;
    margin: 0;
    line-height: 1.6;
}

.attendance-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(280px, .7fr);
    gap: 22px;
    align-items: start;
}

.attendance-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 3px 14px rgba(0, 0, 0, .07);
    min-width: 0;
}

.attendance-card h3 {
    font-size: 19px;
    font-weight: 700;
    margin: 0 0 18px;
    color: #17365d;
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
    color: #333;
}

.attendance-form input,
.attendance-form select,
.manual-search input {
    width: 100%;
    min-width: 0;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    background: #fff;
    font-size: 14px;
    box-sizing: border-box;
}

.attendance-form input:focus,
.attendance-form select:focus,
.manual-search input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
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
    padding: 11px;
    font-weight: 600;
    cursor: pointer;
    color: #333;
}

.tab-button.active {
    background: #17365d;
    color: #fff;
    border-color: #17365d;
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

#reader video {
    width: 100% !important;
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
    font-size: 14px;
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

.btn-success:hover:not(:disabled) {
    background: #146c43;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-warning {
    background: #f0ad4e;
    color: #212529;
}

.btn-warning:hover:not(:disabled) {
    background: #e09b35;
}

.scan-result {
    display: none;
    margin-top: 18px;
    padding: 14px;
    border-radius: 9px;
    overflow-wrap: anywhere;
    white-space: pre-line;
    line-height: 1.6;
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

.scan-result.warning {
    display: block;
    background: #fff3cd;
    border: 1px solid #ffecb5;
    color: #664d03;
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
    padding: 15px;
    border-radius: 10px;
    background: #fff;
}

.member-item strong {
    display: block;
    margin-bottom: 5px;
    color: #17365d;
    font-size: 15px;
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
    border: 1px dashed #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.instructions-list,
.schedule-list {
    padding-left: 22px;
    line-height: 1.9;
    color: #555;
}

.attendance-note {
    color: #777;
    font-size: 13px;
    line-height: 1.6;
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

    .member-item-actions {
        flex-direction: column;
    }

    .member-item-actions button {
        width: 100%;
    }

    .tab-button {
        font-size: 13px;
    }
}
</style>

<div class="attendance-container">

    <div class="attendance-header">
        <h2>QR Code Attendance Scanner</h2>
        <p>Scan an altar server's QR code or search for a member manually to record Mass attendance.</p>
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

                <button
                    type="button"
                    class="tab-button active"
                    id="qrTab"
                >
                    QR Scanner
                </button>

                <button
                    type="button"
                    class="tab-button"
                    id="manualTab"
                >
                    Manual Search
                </button>

            </div>

            <div id="scannerPanel">

                <div class="scanner-box">
                    <div id="reader"></div>
                </div>

                <div class="scanner-actions">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="startScanner"
                    >
                        Start Scanner
                    </button>

                    <button
                        type="button"
                        class="btn btn-secondary"
                        id="stopScanner"
                        disabled
                    >
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
                    maxlength="100"
                >

                <div id="memberResults" class="member-results">
                    <div class="empty-message">
                        Enter a member name or code to search.
                    </div>
                </div>

            </div>

            <div
                id="scanResult"
                class="scan-result"
                role="status"
                aria-live="polite"
            ></div>

        </div>

        <div class="attendance-card">

            <h3>Attendance Instructions</h3>

            <ol class="instructions-list">
                <li>Select the correct Mass date.</li>
                <li>Select the correct Mass schedule.</li>
                <li>Click Start Scanner or choose Manual Search.</li>
                <li>Scan the member's QR code or search for their name.</li>
                <li>Wait for the attendance confirmation.</li>
            </ol>

            <hr>

            <h3>Available Mass Schedules</h3>

            <ul class="schedule-list">
                <?php foreach ($massTimes as $time): ?>
                    <li><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>

            <p class="attendance-note">
                Each member should have only one attendance record for
                the same date and Mass schedule.
            </p>

        </div>

    </div>

</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
'use strict';

const PROCESS_URL = <?= json_encode($processUrl) ?>;
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
const TODAY = <?= json_encode($today) ?>;

let html5QrCode = null;
let scannerRunning = false;
let processingScan = false;
let manualRequestRunning = false;
let searchTimer = null;
let searchRequestNumber = 0;
let lastScannedToken = '';
let lastScanTime = 0;

const scanResult = document.getElementById('scanResult');
const startButton = document.getElementById('startScanner');
const stopButton = document.getElementById('stopScanner');
const memberResults = document.getElementById('memberResults');
const memberSearch = document.getElementById('memberSearch');

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

function validateAttendanceDetails() {
    const details = getAttendanceDetails();

    if (!details.mass_date) {
        showResult('Please select a Mass date.', 'error');
        return false;
    }

    if (!details.mass_time) {
        showResult('Please select a Mass schedule.', 'error');
        return false;
    }

    if (details.mass_date > TODAY) {
        showResult('You cannot record attendance for a future date.', 'error');
        return false;
    }

    return true;
}

async function sendRequest(formData) {
    let response;

    try {
        response = await fetch(PROCESS_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        });
    } catch (error) {
        console.error('Network error:', error);
        throw new Error('Unable to connect to the attendance server. Check your internet connection.');
    }

    const responseText = await response.text();

    console.log('Attendance endpoint:', PROCESS_URL);
    console.log('HTTP status:', response.status);
    console.log('Server response:', responseText);

    let result;

    try {
        result = JSON.parse(responseText);
    } catch (error) {
        if (response.redirected) {
            throw new Error('Your session may have expired. Log in again and refresh this page.');
        }

        throw new Error(
            'The server returned an invalid response (HTTP ' +
            response.status +
            '). Check the Render logs.'
        );
    }

    if (!response.ok && !result.message) {
        throw new Error('Server error: HTTP ' + response.status);
    }

    return result;
}

async function startScanner() {
    if (scannerRunning || processingScan) {
        return;
    }

    if (!validateAttendanceDetails()) {
        return;
    }

    if (typeof Html5Qrcode === 'undefined') {
        showResult('QR scanner library could not load. Check your internet connection.', 'error');
        return;
    }

    try {
        startButton.disabled = true;

        showResult('Starting camera. Please allow camera access.', 'info');

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('reader');
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
            recordQrAttendance,
            function() {}
        );

        scannerRunning = true;
        startButton.disabled = true;
        stopButton.disabled = false;

        showResult('Scanner started. Point the camera at a member QR code.', 'info');

    } catch (error) {
        console.error('Scanner start error:', error);

        scannerRunning = false;
        startButton.disabled = false;
        stopButton.disabled = true;

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
        console.error('Scanner stop error:', error);
        showResult('Unable to stop the scanner properly.', 'error');
    }
}

async function recordQrAttendance(decodedText) {
    if (processingScan) {
        return;
    }

    const token = String(decodedText).trim();

    if (!/^[a-f0-9]{64}$/i.test(token)) {
        showResult('Invalid QR code. Please scan an official member QR card.', 'error');
        return;
    }

    const now = Date.now();

    if (token === lastScannedToken && now - lastScanTime < 4000) {
        return;
    }

    if (!validateAttendanceDetails()) {
        return;
    }

    processingScan = true;
    lastScannedToken = token;
    lastScanTime = now;

    showResult('QR recognized. Recording attendance...', 'info');

    try {
        const details = getAttendanceDetails();
        const formData = new FormData();

        formData.append('action', 'record_qr');
        formData.append('csrf_token', CSRF_TOKEN);
        formData.append('qr_token', token);
        formData.append('mass_date', details.mass_date);
        formData.append('mass_time', details.mass_time);

        const result = await sendRequest(formData);

        if (result.already_recorded) {
            showResult(
                'ATTENDANCE ALREADY RECORDED\n' +
                'Member: ' + (result.member_name || '') + '\n' +
                'Date: ' + (result.mass_date || '') + '\n' +
                'Mass: ' + (result.mass_time || '') + '\n' +
                'Check-in: ' + (result.check_in_time || ''),
                'warning'
            );
            return;
        }

        if (!result.success) {
            showResult(result.message || 'Unable to record attendance.', 'error');
            return;
        }

        showResult(
            'ATTENDANCE RECORDED SUCCESSFULLY\n' +
            'Member: ' + (result.member_name || '') + '\n' +
            'Member Code: ' + (result.member_code || '') + '\n' +
            'Date: ' + (result.mass_date || '') + '\n' +
            'Mass: ' + (result.mass_time || '') + '\n' +
            'Status: ' + (result.attendance_status || '') + '\n' +
            'Check-in: ' + (result.check_in_time || ''),
            'success'
        );

    } catch (error) {
        console.error('QR attendance error:', error);
        showResult(error.message || 'Unable to contact the attendance server.', 'error');

    } finally {
        processingScan = false;
    }
}

async function searchMembers(search) {
    const requestNumber = ++searchRequestNumber;

    const formData = new FormData();
    formData.append('action', 'search');
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('search', search);

    memberResults.innerHTML =
        '<div class="empty-message">Searching members...</div>';

    try {
        const result = await sendRequest(formData);

        if (requestNumber !== searchRequestNumber) {
            return;
        }

        if (!result.success) {
            memberResults.innerHTML =
                '<div class="empty-message">' +
                escapeHtml(result.message || 'Unable to search members.') +
                '</div>';
            return;
        }

        renderMembers(result.members || []);

    } catch (error) {
        console.error('Member search error:', error);

        if (requestNumber !== searchRequestNumber) {
            return;
        }

        memberResults.innerHTML =
            '<div class="empty-message">' +
            escapeHtml(error.message) +
            '</div>';
    }
}

function renderMembers(members) {
    if (!Array.isArray(members) || members.length === 0) {
        memberResults.innerHTML =
            '<div class="empty-message">No active members found.</div>';
        return;
    }

    memberResults.innerHTML = members.map(function(member) {
        const memberId = Number(member.id);

        if (!Number.isInteger(memberId) || memberId <= 0) {
            return '';
        }

        return `
            <div class="member-item">
                <strong>${escapeHtml(member.full_name)}</strong>

                <small>
                    Member Code: ${escapeHtml(member.member_code)}
                </small>

                <div class="member-item-actions">
                    <button
                        type="button"
                        class="btn btn-success"
                        data-member-id="${memberId}"
                        data-status="Present"
                    >
                        Present
                    </button>

                    <button
                        type="button"
                        class="btn btn-warning"
                        data-member-id="${memberId}"
                        data-status="Excused"
                    >
                        Excused
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

memberResults.addEventListener('click', function(event) {
    const button = event.target.closest('button[data-member-id]');

    if (!button) {
        return;
    }

    recordManualAttendance(
        Number(button.dataset.memberId),
        button.dataset.status,
        button
    );
});

async function recordManualAttendance(memberId, status, button) {
    if (manualRequestRunning) {
        return;
    }

    if (!Number.isInteger(memberId) || memberId <= 0) {
        showResult('Invalid member ID.', 'error');
        return;
    }

    if (!['Present', 'Excused'].includes(status)) {
        showResult('Invalid attendance status.', 'error');
        return;
    }

    if (!validateAttendanceDetails()) {
        return;
    }

    manualRequestRunning = true;

    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Saving...';

    try {
        const details = getAttendanceDetails();
        const formData = new FormData();

        formData.append('action', 'record_manual');
        formData.append('csrf_token', CSRF_TOKEN);
        formData.append('member_id', memberId);
        formData.append('attendance_status', status);
        formData.append('mass_date', details.mass_date);
        formData.append('mass_time', details.mass_time);

        showResult('Saving attendance...', 'info');

        const result = await sendRequest(formData);

        if (result.already_recorded) {
            showResult(
                'Attendance has already been recorded for this member and Mass schedule.',
                'warning'
            );
            return;
        }

        if (!result.success) {
            showResult(result.message || 'Unable to record attendance.', 'error');
            return;
        }

        showResult(
            'ATTENDANCE RECORDED SUCCESSFULLY\n' +
            'Member: ' + (result.member_name || '') + '\n' +
            'Member Code: ' + (result.member_code || '') + '\n' +
            'Date: ' + (result.mass_date || '') + '\n' +
            'Mass: ' + (result.mass_time || '') + '\n' +
            'Status: ' + (result.attendance_status || '') + '\n' +
            'Method: ' + (result.attendance_method || 'Manual') + '\n' +
            'Check-in: ' + (result.check_in_time || ''),
            'success'
        );

    } catch (error) {
        console.error('Manual attendance error:', error);
        showResult(error.message || 'Unable to record manual attendance.', 'error');

    } finally {
        manualRequestRunning = false;
        button.disabled = false;
        button.textContent = originalText;
    }
}

memberSearch.addEventListener('input', function() {
    clearTimeout(searchTimer);

    const search = this.value.trim();

    if (search.length < 2) {
        searchRequestNumber++;

        memberResults.innerHTML =
            '<div class="empty-message">Enter at least two characters.</div>';

        return;
    }

    searchTimer = setTimeout(function() {
        searchMembers(search);
    }, 400);
});

document.getElementById('qrTab').addEventListener('click', async function() {
    await stopScanner();

    this.classList.add('active');
    document.getElementById('manualTab').classList.remove('active');

    document.getElementById('scannerPanel').style.display = 'block';
    document.getElementById('manualPanel').style.display = 'none';
});

document.getElementById('manualTab').addEventListener('click', async function() {
    await stopScanner();

    this.classList.add('active');
    document.getElementById('qrTab').classList.remove('active');

    document.getElementById('scannerPanel').style.display = 'none';
    document.getElementById('manualPanel').style.display = 'block';
});

startButton.addEventListener('click', startScanner);
stopButton.addEventListener('click', stopScanner);

stopButton.disabled = true;

window.addEventListener('beforeunload', function() {
    if (scannerRunning && html5QrCode) {
        html5QrCode.stop().catch(function(error) {
            console.error('Camera cleanup error:', error);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>