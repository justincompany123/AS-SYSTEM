<?php
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| QR CODE ATTENDANCE SCANNER
| ST. FRANCIS XAVIER CATHEDRAL
| SUPABASE POSTGRESQL
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth.php';

requireLogin();

date_default_timezone_set('Asia/Manila');

// Create CSRF token if one does not exist.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// Shared page settings.
$pageTitle = 'QR Attendance Scanner';
$activePage = 'attendance';

// Attendance processing endpoint.
$processUrl = '/attendance/process_qr.php';

// Available Sunday Mass schedules.
$massTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

$today = date('Y-m-d');

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* =========================================
   QR ATTENDANCE SCANNER
========================================= */

.scanner-page {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 25px 20px;
}

.scanner-heading {
    margin-bottom: 25px;
}

.scanner-heading h1 {
    color: #172c46;
    font-size: 27px;
    font-weight: 700;
    margin-bottom: 8px;
}

.scanner-heading p {
    color: #78818c;
    font-size: 13px;
    line-height: 1.6;
}

.scanner-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(300px, .8fr);
    gap: 22px;
    align-items: start;
}

.scanner-card {
    background: #ffffff;
    border: 1px solid #e7dfcf;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(16, 33, 55, .05);
    min-width: 0;
}

.scanner-card h2 {
    color: #172c46;
    font-size: 17px;
    margin-bottom: 8px;
}

.scanner-card-description {
    color: #78818c;
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 20px;
}

.scanner-form-group {
    margin-bottom: 18px;
}

.scanner-form-group label {
    display: block;
    color: #172c46;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.scanner-form-group input,
.scanner-form-group select {
    width: 100%;
    min-height: 45px;
    padding: 10px 12px;
    border: 1px solid #e7dfcf;
    border-radius: 6px;
    background: #fffefa;
    color: #283444;
    font-family: inherit;
    font-size: 13px;
    outline: none;
}

.scanner-form-group input:focus,
.scanner-form-group select:focus {
    border-color: #b18a43;
    box-shadow: 0 0 0 3px rgba(177, 138, 67, .10);
}

.mass-time-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 9px;
}

.mass-time-button {
    min-height: 43px;
    border: 1px solid #e7dfcf;
    border-radius: 6px;
    background: #ffffff;
    color: #172c46;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.mass-time-button:hover {
    border-color: #b18a43;
}

.mass-time-button.active {
    background: #172c46;
    border-color: #172c46;
    color: #ffffff;
}

.camera-container {
    width: 100%;
    min-height: 250px;
    overflow: hidden;
    border-radius: 9px;
    background: #101d2c;
    display: flex;
    align-items: center;
    justify-content: center;
}

#qr-reader {
    width: 100%;
    border: none !important;
}

#qr-reader video {
    width: 100% !important;
    border-radius: 8px;
    object-fit: cover;
}

#qr-reader__dashboard {
    display: none !important;
}

#qr-reader__scan_region {
    min-height: 230px;
}

.camera-placeholder {
    color: #ffffff;
    text-align: center;
    padding: 35px 20px;
}

.camera-placeholder .camera-icon {
    font-size: 38px;
    margin-bottom: 12px;
}

.camera-placeholder p {
    color: #d5dce5;
    font-size: 12px;
    line-height: 1.6;
}

.camera-controls {
    display: flex;
    gap: 10px;
    margin-top: 17px;
}

.scanner-button {
    min-height: 45px;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.scanner-button:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.scanner-button-primary {
    background: #172c46;
    color: #ffffff;
    flex: 1;
}

.scanner-button-primary:hover:not(:disabled) {
    background: #102137;
}

.scanner-button-secondary {
    background: #f2eee5;
    color: #172c46;
    border: 1px solid #e7dfcf;
}

.scanner-button-secondary:hover:not(:disabled) {
    background: #e9e2d5;
}

.scanner-status {
    margin-top: 16px;
    padding: 13px 15px;
    border-radius: 7px;
    background: #f4f6f8;
    border: 1px solid #e1e5e9;
    color: #526071;
    font-size: 12px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.scanner-status.success {
    background: #edf8f0;
    color: #216b3a;
    border-color: #c8e7d0;
}

.scanner-status.error {
    background: #fff1ef;
    color: #9c3028;
    border-color: #efc6c0;
}

.scanner-status.warning {
    background: #fff8e9;
    color: #8a6420;
    border-color: #f0dfb7;
}

.scanner-instructions {
    margin-top: 20px;
    padding: 15px;
    background: #f8f5ed;
    border-radius: 7px;
    border: 1px solid #e7dfcf;
}

.scanner-instructions h3 {
    color: #172c46;
    font-size: 12px;
    margin-bottom: 8px;
}

.scanner-instructions ul {
    padding-left: 19px;
    color: #687482;
    font-size: 11px;
    line-height: 1.9;
}

/* =========================================
   MANUAL MEMBER SEARCH
========================================= */

.member-search-results {
    margin-top: 15px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.member-result {
    padding: 15px;
    border: 1px solid #e7dfcf;
    border-radius: 8px;
    background: #fffefa;
}

.member-result-header {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: center;
    margin-bottom: 12px;
}

.member-result-name {
    color: #172c46;
    font-size: 13px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.member-result-code {
    color: #78818c;
    font-size: 10px;
    margin-top: 4px;
}

.manual-status-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.manual-status-button {
    min-height: 37px;
    border: none;
    border-radius: 5px;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.manual-status-button:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.status-present {
    background: #26834a;
}

.status-late {
    background: #c47b20;
}

.status-absent {
    background: #b83a36;
}

.status-excused {
    background: #25849a;
}

.manual-empty {
    color: #78818c;
    font-size: 12px;
    padding: 18px;
    text-align: center;
    background: #f8f5ed;
    border-radius: 7px;
}

.attendance-history-link {
    display: inline-block;
    margin-top: 18px;
    color: #9b7637;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

.attendance-history-link:hover {
    text-decoration: underline;
}

/* =========================================
   RESPONSIVE DESIGN
========================================= */

@media (max-width: 900px) {
    .scanner-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .scanner-page {
        padding: 18px 12px;
    }

    .scanner-card {
        padding: 18px 14px;
    }

    .scanner-heading h1 {
        font-size: 22px;
    }

    .mass-time-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .camera-controls {
        flex-direction: column;
    }

    .scanner-button {
        width: 100%;
    }

    .member-result-header {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>


<main class="scanner-page">

    <!-- PAGE HEADING -->

    <div class="scanner-heading">

        <h1>QR Code Attendance Scanner</h1>

        <p>
            Scan an altar server's QR code to record attendance
            for the selected Sunday Mass.
        </p>

    </div>


    <div class="scanner-grid">

        <!-- ==================================
             QR SCANNER
        =================================== -->

        <section class="scanner-card">

            <h2>QR Code Scanner</h2>

            <p class="scanner-card-description">
                Select the Mass schedule and date before scanning
                the member's QR code.
            </p>


            <!-- ATTENDANCE DATE -->

            <div class="scanner-form-group">

                <label for="massDate">
                    Attendance Date
                </label>

                <input
                    type="date"
                    id="massDate"
                    value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>"
                    max="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

            </div>


            <!-- MASS SCHEDULE -->

            <div class="scanner-form-group">

                <label>
                    Sunday Mass Schedule
                </label>

                <div class="mass-time-grid">

                    <?php foreach ($massTimes as $index => $time): ?>

                        <button
                            type="button"
                            class="mass-time-button <?= $index === 0 ? 'active' : '' ?>"
                            data-time="<?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>
                        </button>

                    <?php endforeach; ?>

                </div>

                <input
                    type="hidden"
                    id="massTime"
                    value="6:00 AM"
                >

            </div>


            <!-- CAMERA -->

            <div class="camera-container">

                <div id="qr-reader">

                    <div class="camera-placeholder" id="cameraPlaceholder">

                        <div class="camera-icon">▣</div>

                        <p>
                            Camera is not active.<br>
                            Select Start Camera to begin scanning.
                        </p>

                    </div>

                </div>

            </div>


            <!-- CAMERA CONTROLS -->

            <div class="camera-controls">

                <button
                    type="button"
                    class="scanner-button scanner-button-primary"
                    id="startScanner"
                >
                    START CAMERA
                </button>

                <button
                    type="button"
                    class="scanner-button scanner-button-secondary"
                    id="stopScanner"
                    disabled
                >
                    STOP CAMERA
                </button>

            </div>


            <!-- SCANNER STATUS -->

            <div
                class="scanner-status"
                id="scannerStatus"
                role="status"
                aria-live="polite"
            >
                Ready to scan. Select your Mass schedule and start the camera.
            </div>


            <!-- INSTRUCTIONS -->

            <div class="scanner-instructions">

                <h3>Scanning Instructions</h3>

                <ul>
                    <li>Select the correct attendance date.</li>
                    <li>Select the correct Sunday Mass schedule.</li>
                    <li>Allow camera access when requested.</li>
                    <li>Position the member's QR code inside the camera view.</li>
                    <li>Wait for the attendance confirmation message.</li>
                </ul>

            </div>

        </section>


        <!-- ==================================
             MANUAL ATTENDANCE
        =================================== -->

        <section class="scanner-card">

            <h2>Manual Attendance</h2>

            <p class="scanner-card-description">
                Search for a registered member and record attendance
                manually when QR scanning is unavailable.
            </p>


            <div class="scanner-form-group">

                <label for="memberSearch">
                    Search Member
                </label>

                <input
                    type="search"
                    id="memberSearch"
                    placeholder="Enter member name or member code..."
                    autocomplete="off"
                >

            </div>


            <div
                id="manualSearchStatus"
                class="scanner-status"
                role="status"
                aria-live="polite"
            >
                Enter a member name or member code to search.
            </div>


            <div
                id="memberSearchResults"
                class="member-search-results"
            ></div>


            <a
                href="/attendance/"
                class="attendance-history-link"
            >
                View Attendance Records →
            </a>

        </section>

    </div>

</main>


<!-- CSRF TOKEN -->

<input
    type="hidden"
    id="csrfToken"
    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
>


<!-- QR CODE LIBRARY -->

<script src="https://unpkg.com/html5-qrcode"></script>


<script>
/*
|--------------------------------------------------------------------------
| ALTAR SERVERS QR ATTENDANCE SCANNER
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function () {

    'use strict';

    const processUrl = <?= json_encode($processUrl) ?>;

    const csrfToken = document.getElementById('csrfToken').value;

    const massDateInput = document.getElementById('massDate');

    const massTimeInput = document.getElementById('massTime');

    const startButton = document.getElementById('startScanner');

    const stopButton = document.getElementById('stopScanner');

    const scannerStatus = document.getElementById('scannerStatus');

    const cameraPlaceholder = document.getElementById('cameraPlaceholder');

    const memberSearch = document.getElementById('memberSearch');

    const memberSearchResults = document.getElementById('memberSearchResults');

    const manualSearchStatus = document.getElementById('manualSearchStatus');


    let html5QrCode = null;

    let scannerRunning = false;

    let processingQR = false;

    let searchTimer = null;

    let audioContext = null;


    /*
    |--------------------------------------------------------------------------
    | FORMAL ONE-SECOND SUCCESS SOUND
    |--------------------------------------------------------------------------
    |
    | A gentle two-note confirmation chime.
    | No external MP3 file is required.
    | Total duration: approximately one second.
    |
    */

    function prepareSuccessSound() {

        const AudioContextClass =
            window.AudioContext || window.webkitAudioContext;

        if (!AudioContextClass) {
            return;
        }

        if (!audioContext) {
            audioContext = new AudioContextClass();
        }

        if (audioContext.state === 'suspended') {
            audioContext.resume().catch(function () {});
        }
    }


    function playQRSuccessSound() {

        if (!audioContext) {
            return;
        }

        if (audioContext.state !== 'running') {
            return;
        }

        const startTime = audioContext.currentTime;

        const masterGain = audioContext.createGain();

        masterGain.gain.setValueAtTime(0.0001, startTime);

        masterGain.connect(audioContext.destination);


        function playNote(frequency, startOffset, duration, volume) {

            const oscillator = audioContext.createOscillator();

            const gain = audioContext.createGain();

            const noteStart = startTime + startOffset;

            const noteEnd = noteStart + duration;

            oscillator.type = 'sine';

            oscillator.frequency.setValueAtTime(
                frequency,
                noteStart
            );

            gain.gain.setValueAtTime(0.0001, noteStart);

            gain.gain.exponentialRampToValueAtTime(
                volume,
                noteStart + 0.04
            );

            gain.gain.exponentialRampToValueAtTime(
                0.0001,
                noteEnd
            );

            oscillator.connect(gain);

            gain.connect(masterGain);

            oscillator.start(noteStart);

            oscillator.stop(noteEnd + 0.02);
        }


        // Soft, formal two-note confirmation.
        playNote(523.25, 0.00, 0.48, 0.12);

        playNote(659.25, 0.43, 0.55, 0.10);

        masterGain.gain.setValueAtTime(1, startTime);

        masterGain.gain.setValueAtTime(1, startTime + 0.80);

        masterGain.gain.exponentialRampToValueAtTime(
            0.0001,
            startTime + 1.02
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS MESSAGE
    |--------------------------------------------------------------------------
    */

    function setScannerStatus(message, type = '') {

        scannerStatus.textContent = message;

        scannerStatus.className = 'scanner-status';

        if (type) {
            scannerStatus.classList.add(type);
        }
    }


    function setManualStatus(message, type = '') {

        manualSearchStatus.textContent = message;

        manualSearchStatus.className = 'scanner-status';

        if (type) {
            manualSearchStatus.classList.add(type);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MASS TIME BUTTONS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.mass-time-button').forEach(function (button) {

        button.addEventListener('click', function () {

            document.querySelectorAll('.mass-time-button').forEach(function (item) {
                item.classList.remove('active');
            });

            button.classList.add('active');

            massTimeInput.value = button.dataset.time;

            setScannerStatus(
                'Selected Mass schedule: ' + button.dataset.time
            );

        });

    });


    /*
    |--------------------------------------------------------------------------
    | POST REQUEST HELPER
    |--------------------------------------------------------------------------
    */

    async function sendAttendanceRequest(data) {

        const formData = new URLSearchParams();

        Object.keys(data).forEach(function (key) {
            formData.append(key, data[key]);
        });

        const response = await fetch(processUrl, {

            method: 'POST',

            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },

            body: formData.toString(),

            credentials: 'same-origin'

        });

        const responseText = await response.text();

        let result;

        try {

            result = JSON.parse(responseText);

        } catch (error) {

            console.error('Invalid server response:', responseText);

            throw new Error(
                'The server returned an invalid response. Please check the application logs.'
            );
        }

        if (!response.ok && !result.message) {

            throw new Error(
                'Unable to communicate with the attendance server.'
            );
        }

        return result;
    }


    /*
    |--------------------------------------------------------------------------
    | RECORD QR ATTENDANCE
    |--------------------------------------------------------------------------
    */

    async function recordQRAttendance(qrToken) {

        if (processingQR) {
            return;
        }

        processingQR = true;

        setScannerStatus(
            'QR code detected. Verifying member and recording attendance...'
        );

        try {

            const result = await sendAttendanceRequest({

                action: 'record_qr',

                csrf_token: csrfToken,

                qr_token: qrToken,

                mass_date: massDateInput.value,

                mass_time: massTimeInput.value

            });


            if (result.success === true) {

                // Play sound only after the server confirms successful saving.
                playQRSuccessSound();

                const memberName = result.member_name || 'Member';

                const memberCode = result.member_code
                    ? ' (' + result.member_code + ')'
                    : '';

                setScannerStatus(
                    'Attendance successfully recorded for ' +
                    memberName +
                    memberCode +
                    '. Status: ' +
                    (result.attendance_status || 'Present') +
                    '.',
                    'success'
                );

                // Stop scanning after a successful record.
                await stopCamera(false);

            } else {

                setScannerStatus(
                    result.message || 'Attendance could not be recorded.',
                    result.already_recorded ? 'warning' : 'error'
                );

            }

        } catch (error) {

            console.error('QR attendance error:', error);

            setScannerStatus(
                error.message || 'An error occurred while recording attendance.',
                'error'
            );

        } finally {

            processingQR = false;

        }
    }


    /*
    |--------------------------------------------------------------------------
    | QR CODE SUCCESS CALLBACK
    |--------------------------------------------------------------------------
    */

    async function onScanSuccess(decodedText) {

        if (processingQR) {
            return;
        }

        const qrToken = String(decodedText).trim();

        // Expected QR token format: 64 hexadecimal characters.
        if (!/^[a-fA-F0-9]{64}$/.test(qrToken)) {

            setScannerStatus(
                'Invalid QR code. Please scan a valid altar server QR code.',
                'error'
            );

            return;
        }

        await recordQRAttendance(qrToken);
    }


    /*
    |--------------------------------------------------------------------------
    | START CAMERA
    |--------------------------------------------------------------------------
    */

    async function startCamera() {

        if (scannerRunning) {
            return;
        }

        if (!massDateInput.value || !massTimeInput.value) {

            setScannerStatus(
                'Please select the attendance date and Mass schedule.',
                'error'
            );

            return;
        }

        if (typeof Html5Qrcode === 'undefined') {

            setScannerStatus(
                'QR scanner library could not be loaded. Please refresh the page.',
                'error'
            );

            return;
        }

        try {

            prepareSuccessSound();

            startButton.disabled = true;

            setScannerStatus('Starting camera. Please wait...');

            if (cameraPlaceholder) {
                cameraPlaceholder.style.display = 'none';
            }

            html5QrCode = new Html5Qrcode('qr-reader');

            await html5QrCode.start(

                { facingMode: 'environment' },

                {
                    fps: 10,

                    qrbox: function (viewfinderWidth, viewfinderHeight) {

                        const size = Math.min(
                            viewfinderWidth,
                            viewfinderHeight,
                            250
                        );

                        return {
                            width: size,
                            height: size
                        };

                    },

                    aspectRatio: 1.0

                },

                onScanSuccess,

                function () {
                    // Ignore normal camera scan misses.
                }

            );

            scannerRunning = true;

            startButton.disabled = true;

            stopButton.disabled = false;

            setScannerStatus(
                'Camera is active. Position the member QR code inside the scanner.'
            );

        } catch (error) {

            console.error('Camera error:', error);

            startButton.disabled = false;

            stopButton.disabled = true;

            setScannerStatus(
                'Unable to start camera. Please allow camera permission and try again.',
                'error'
            );

            if (cameraPlaceholder) {
                cameraPlaceholder.style.display = 'block';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | STOP CAMERA
    |--------------------------------------------------------------------------
    */

    async function stopCamera(showMessage = true) {

        if (!html5QrCode || !scannerRunning) {

            scannerRunning = false;

            startButton.disabled = false;

            stopButton.disabled = true;

            return;
        }

        try {

            await html5QrCode.stop();

            await html5QrCode.clear();

            html5QrCode = null;

            scannerRunning = false;

            startButton.disabled = false;

            stopButton.disabled = true;

            if (cameraPlaceholder) {
                cameraPlaceholder.style.display = 'block';
            }

            if (showMessage) {

                setScannerStatus(
                    'Camera stopped. You can start scanning again.'
                );

            }

        } catch (error) {

            console.error('Camera stop error:', error);

            scannerRunning = false;

            startButton.disabled = false;

            stopButton.disabled = true;

        }
    }


    startButton.addEventListener('click', startCamera);

    stopButton.addEventListener('click', function () {
        stopCamera(true);
    });


    /*
    |--------------------------------------------------------------------------
    | MANUAL MEMBER SEARCH
    |--------------------------------------------------------------------------
    */

    async function searchMembers(searchValue) {

        if (searchValue.length < 2) {

            memberSearchResults.innerHTML = '';

            setManualStatus(
                'Enter at least two characters to search.'
            );

            return;
        }

        setManualStatus('Searching members...');

        try {

            const result = await sendAttendanceRequest({

                action: 'search',

                csrf_token: csrfToken,

                search: searchValue

            });

            if (result.success !== true) {

                setManualStatus(
                    result.message || 'Unable to search members.',
                    'error'
                );

                return;
            }

            const members = Array.isArray(result.members)
                ? result.members
                : [];

            memberSearchResults.innerHTML = '';

            if (members.length === 0) {

                setManualStatus(
                    'No active members found.',
                    'warning'
                );

                return;
            }

            setManualStatus(
                members.length + ' member(s) found.',
                'success'
            );

            members.forEach(function (member) {

                const card = document.createElement('div');

                card.className = 'member-result';


                const header = document.createElement('div');

                header.className = 'member-result-header';


                const information = document.createElement('div');


                const name = document.createElement('div');

                name.className = 'member-result-name';

                name.textContent = member.full_name || 'Unnamed Member';


                const code = document.createElement('div');

                code.className = 'member-result-code';

                code.textContent = member.member_code || 'No member code';


                information.appendChild(name);

                information.appendChild(code);

                header.appendChild(information);

                card.appendChild(header);


                const buttonGrid = document.createElement('div');

                buttonGrid.className = 'manual-status-grid';


                const statuses = [

                    {
                        label: 'Present',
                        className: 'status-present'
                    },

                    {
                        label: 'Late',
                        className: 'status-late'
                    },

                    {
                        label: 'Absent',
                        className: 'status-absent'
                    },

                    {
                        label: 'Excused',
                        className: 'status-excused'
                    }

                ];


                statuses.forEach(function (status) {

                    const button = document.createElement('button');

                    button.type = 'button';

                    button.className =
                        'manual-status-button ' + status.className;

                    button.textContent = status.label;

                    button.addEventListener('click', function () {

                        recordManualAttendance(
                            member.id,
                            status.label,
                            button
                        );

                    });

                    buttonGrid.appendChild(button);

                });


                card.appendChild(buttonGrid);

                memberSearchResults.appendChild(card);

            });

        } catch (error) {

            console.error('Member search error:', error);

            setManualStatus(
                error.message || 'Unable to search members.',
                'error'
            );

        }
    }


    memberSearch.addEventListener('input', function () {

        clearTimeout(searchTimer);

        const searchValue = memberSearch.value.trim();

        searchTimer = setTimeout(function () {

            searchMembers(searchValue);

        }, 350);

    });


    /*
    |--------------------------------------------------------------------------
    | RECORD MANUAL ATTENDANCE
    |--------------------------------------------------------------------------
    */

    async function recordManualAttendance(memberId, status, button) {

        if (!massDateInput.value || !massTimeInput.value) {

            setManualStatus(
                'Please select the attendance date and Mass schedule.',
                'error'
            );

            return;
        }

        const confirmed = window.confirm(
            'Record ' + status + ' attendance for this member?'
        );

        if (!confirmed) {
            return;
        }

        button.disabled = true;

        button.textContent = 'Saving...';

        try {

            const result = await sendAttendanceRequest({

                action: 'record_manual',

                csrf_token: csrfToken,

                member_id: memberId,

                attendance_status: status,

                mass_date: massDateInput.value,

                mass_time: massTimeInput.value

            });

            if (result.success === true) {

                setManualStatus(
                    result.message || 'Manual attendance successfully recorded.',
                    'success'
                );

                button.textContent = 'Recorded';

            } else {

                setManualStatus(
                    result.message || 'Unable to record manual attendance.',
                    result.already_recorded ? 'warning' : 'error'
                );

                button.disabled = false;

                button.textContent = status;

            }

        } catch (error) {

            console.error('Manual attendance error:', error);

            setManualStatus(
                error.message || 'Unable to record manual attendance.',
                'error'
            );

            button.disabled = false;

            button.textContent = status;

        }
    }


    /*
    |--------------------------------------------------------------------------
    | CLEAN UP CAMERA WHEN LEAVING PAGE
    |--------------------------------------------------------------------------
    */

    window.addEventListener('beforeunload', function () {

        if (html5QrCode && scannerRunning) {

            html5QrCode.stop().catch(function () {});

        }

    });

});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
