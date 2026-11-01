
<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

$pageTitle = 'QR Attendance Scanner';
$activePage = 'scanner';

$massTimes = [
    '6:00 AM',
    '8:00 AM',
    '10:00 AM',
    '5:00 PM'
];

$selectedDate = date('Y-m-d');

require_once __DIR__ . '/../includes/header.php';

?>

<style>

/* ==========================================
   QR ATTENDANCE SCANNER
   ST. FRANCIS XAVIER CATHEDRAL
========================================== */

.mass-scanner {
    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
}

/* PAGE HEADING */

.scanner-heading {
    margin-bottom: 25px;
}

.scanner-heading h1 {
    font-family: 'Cinzel', serif;
    color: #172C46;
    font-size: 27px;
    margin-bottom: 8px;
}

.scanner-heading p {
    color: #777;
    font-size: 14px;
    line-height: 1.6;
}

/* MAIN PANEL */

.scanner-panel {
    background: #FFFFFF;
    border: 1px solid #E8DFD0;
    border-radius: 14px;
    padding: 25px;
    margin-bottom: 22px;
    box-shadow: 0 4px 15px rgba(23, 44, 70, 0.04);
}

.scanner-panel h2 {
    font-family: 'Cinzel', serif;
    font-size: 18px;
    color: #172C46;
    margin-bottom: 15px;
}

/* MASS DATE */

.mass-date-group {
    margin-bottom: 25px;
}

.mass-date-group label {
    display: block;
    color: #172C46;
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 9px;
}

.mass-date {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #DCD4C7;
    border-radius: 8px;
    background: #FFFFFF;
    color: #172C46;
    font-size: 14px;
    outline: none;
}

.mass-date:focus {
    border-color: #B18A43;
    box-shadow: 0 0 0 3px rgba(177, 138, 67, 0.12);
}

/* MASS TIME OPTIONS */

.mass-options {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 13px;
    margin-bottom: 25px;
}

.mass-option {
    border: 2px solid #E8DFD0;
    background: #FFFFFF;
    color: #172C46;
    padding: 20px 10px;
    border-radius: 11px;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mass-option:hover {
    border-color: #B18A43;
    transform: translateY(-2px);
}

.mass-option.active {
    background: #172C46;
    color: #FFFFFF;
    border-color: #B18A43;
    box-shadow: 0 4px 12px rgba(23, 44, 70, 0.15);
}

.mass-option small {
    display: block;
    margin-top: 7px;
    font-size: 11px;
    font-weight: 500;
    color: inherit;
    opacity: 0.8;
}

.mass-option:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* CURRENT SESSION */

.session-display {
    background: #F8F5ED;
    border-left: 4px solid #B18A43;
    border-radius: 8px;
    padding: 17px;
    margin-bottom: 22px;
}

.session-display .session-label {
    display: block;
    color: #8A7651;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 7px;
}

.session-display strong {
    display: block;
    color: #172C46;
    font-family: 'Cinzel', serif;
    font-size: 18px;
}

.session-display small {
    display: block;
    color: #777;
    font-size: 12px;
    margin-top: 6px;
}

/* CAMERA BUTTONS */

.scanner-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.scanner-actions button {
    border: none;
    border-radius: 8px;
    padding: 13px 22px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s ease;
}

.start-camera {
    background: #172C46;
    color: #FFFFFF;
}

.start-camera:hover {
    background: #102137;
}

.stop-camera {
    background: #EEE9DF;
    color: #172C46;
}

.scanner-actions button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* QR CAMERA */

#qr-reader {
    width: 100%;
    max-width: 520px;
    margin: 25px auto;
    overflow: hidden;
    border-radius: 12px;
}

#qr-reader video {
    border-radius: 10px;
}

/* SCANNER STATUS */

.scanner-status {
    display: none;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
    font-size: 13px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.scanner-status.success,
.scanner-status.error,
.scanner-status.warning,
.scanner-status.info {
    display: block;
}

.scanner-status.success {
    background: #E9F5EC;
    border: 1px solid #C9E5D0;
    color: #236238;
}

.scanner-status.error {
    background: #FBECEC;
    border: 1px solid #EDCCCC;
    color: #963B3B;
}

.scanner-status.warning {
    background: #FFF6DF;
    border: 1px solid #EEDCAE;
    color: #80601E;
}

.scanner-status.info {
    background: #EDF3FA;
    border: 1px solid #D4E1EF;
    color: #315575;
}

/* INSTRUCTIONS */

.scanner-instructions {
    background: #F8F5ED;
    border: 1px solid #E8DFD0;
    border-radius: 9px;
    padding: 17px;
    margin-top: 22px;
    color: #625D52;
    font-size: 13px;
    line-height: 1.8;
}

.scanner-instructions strong {
    color: #172C46;
}

.scanner-instructions ol {
    padding-left: 20px;
    margin: 10px 0;
}

/* MOBILE */

@media (max-width: 768px) {

    .mass-options {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .scanner-panel {
        padding: 19px;
    }

}

@media (max-width: 480px) {

    .scanner-heading h1 {
        font-size: 22px;
    }

    .scanner-panel {
        padding: 15px;
    }

    .mass-option {
        padding: 17px 8px;
        font-size: 15px;
    }

    .scanner-actions {
        flex-direction: column;
    }

    .scanner-actions button {
        width: 100%;
    }

}

</style>


<div class="main-content">

    <div class="mass-scanner">

        <!-- PAGE HEADING -->

        <div class="scanner-heading">

            <h1>QR Attendance Scanner</h1>

            <p>
                Scan each altar server's permanent QR code
                to automatically record their attendance
                for the selected Sunday Mass.
            </p>

        </div>


        <!-- SCANNER PANEL -->

        <div class="scanner-panel">

            <!-- MASS DATE -->

            <div class="mass-date-group">

                <label for="mass-date">
                    SELECT MASS DATE
                </label>

                <input
                    type="date"
                    id="mass-date"
                    class="mass-date"
                    value="<?= htmlspecialchars($selectedDate) ?>"
                    required
                >

            </div>


            <!-- MASS TIME -->

            <h2>Select Mass Time</h2>

            <div class="mass-options">

                <?php foreach ($massTimes as $index => $time): ?>

                    <button
                        type="button"
                        class="mass-option <?= $index === 0 ? 'active' : '' ?>"
                        data-mass-time="<?= htmlspecialchars($time) ?>"
                        aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                    >

                        <?= htmlspecialchars($time) ?>

                        <small>
                            Sunday Mass
                        </small>

                    </button>

                <?php endforeach; ?>

            </div>


            <!-- CURRENT SESSION -->

            <div class="session-display">

                <span class="session-label">
                    CURRENT ATTENDANCE SESSION
                </span>

                <strong id="selected-mass-display">
                    <?= htmlspecialchars($selectedDate) ?> — 6:00 AM
                </strong>

                <small>
                    All successfully scanned active members
                    will be recorded as Present for this Mass.
                </small>

            </div>


            <!-- CAMERA CONTROLS -->

            <div class="scanner-actions">

                <button
                    type="button"
                    id="start-scanner"
                    class="start-camera"
                >
                    <span>▦</span>
                    START CAMERA
                </button>

                <button
                    type="button"
                    id="stop-scanner"
                    class="stop-camera"
                    disabled
                >
                    STOP CAMERA
                </button>

            </div>


            <!-- CAMERA DISPLAY -->

            <div id="qr-reader"></div>


            <!-- SCANNER RESULT -->

            <div
                id="scanner-status"
                class="scanner-status"
                role="status"
                aria-live="polite"
            ></div>


            <!-- INSTRUCTIONS -->

            <div class="scanner-instructions">

                <strong>Scanner Instructions</strong>

                <ol>

                    <li>
                        Select the date of the Mass.
                    </li>

                    <li>
                        Select the correct Mass time.
                    </li>

                    <li>
                        Click START CAMERA.
                    </li>

                    <li>
                        Allow camera permission when requested.
                    </li>

                    <li>
                        Scan the altar server's permanent QR code.
                    </li>

                    <li>
                        Wait for the attendance confirmation.
                    </li>

                </ol>

                <strong>Important:</strong>

                Each member has one permanent QR code.
                The same QR code can be used for different
                Mass times, but duplicate attendance for
                the same date and Mass time will be rejected.

            </div>

        </div>


        <!-- ATTENDANCE HISTORY LINK -->

        <div class="scanner-panel">

            <h2>Attendance Management</h2>

            <p style="color:#777; font-size:14px; margin-bottom:17px;">

                Review attendance records and check-in times
                for all Sunday Mass schedules.

            </p>

            <a
                href="/AS-SYSTEM/attendance/index.php"
                class="btn btn-primary"
            >
                VIEW ATTENDANCE RECORDS
            </a>

        </div>

    </div>

</div>


<!-- CSRF TOKEN -->

<div id="csrf-container" hidden>

    <?= csrfField() ?>

</div>


<!-- QR SCANNER LIBRARY -->

<script src="https://unpkg.com/html5-qrcode"></script>


<!-- QR SCANNER CONFIGURATION -->

<script>

window.QR_ATTENDANCE_CONFIG = {

    processUrl: '/AS-SYSTEM/attendance/process_qr.php'

};

</script>


<!-- CUSTOM QR SCANNER JAVASCRIPT -->

<script src="/AS-SYSTEM/assets/js/qr-scanner.js"></script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
